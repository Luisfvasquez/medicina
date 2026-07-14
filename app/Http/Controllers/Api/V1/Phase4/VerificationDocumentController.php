<?php

namespace App\Http\Controllers\Api\V1\Phase4;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\VerificationDocument\StoreVerificationDocumentRequest;
use App\Http\Requests\Api\V1\VerificationDocument\UpdateVerificationDocumentRequest;
use App\Models\VerificationDocument;
use Illuminate\Http\JsonResponse;

class VerificationDocumentController extends Controller
{
    public function index(): JsonResponse
    {
        $user = auth('user_api')->user();

        $documents = VerificationDocument::where('user_id', $user->id)
            ->latest()
            ->paginate(20);

        return response()->json(['data' => $documents]);
    }

    public function store(StoreVerificationDocumentRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['user_id'] = auth('user_api')->id();

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName());
            $uuid = (string) \Illuminate\Support\Str::uuid();
            $path = sprintf('verification_documents/%s/%s', $data['user_id'], $uuid);

            $storedPath = \Illuminate\Support\Facades\Storage::disk(config('filesystems.default'))->putFileAs($path, $file, $safeName);

            $data['file_url'] = $storedPath;
        }

        $document = VerificationDocument::create($data);

        // Audit Log
        \App\Models\AuditLog::logCreate(
            auth('user_api')->user(),
            'VerificationDocument',
            $document->id,
            $document->toArray()
        );

        return response()->json(['data' => $document], 201);
    }

    public function show(string $id): JsonResponse
    {
        $document = VerificationDocument::where('user_id', auth('user_api')->id())
            ->findOrFail($id);

        return response()->json(['data' => $document]);
    }

    public function update(UpdateVerificationDocumentRequest $request, string $id): JsonResponse
    {
        $document = VerificationDocument::where('user_id', auth('user_api')->id())
            ->findOrFail($id);

        $status = $document->status instanceof \App\Enums\VerificationStatus 
            ? $document->status->value 
            : $document->status;

        // Permitir actualizar si está PENDING o REJECTED
        if ($status !== 'PENDING' && $status !== 'REJECTED') {
            return response()->json(['error' => 'Cannot update document with status: ' . $status], 422);
        }

        $oldData = $document->toArray();
        $updateData = $request->validated();

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName());
            $uuid = (string) \Illuminate\Support\Str::uuid();
            $path = sprintf('verification_documents/%s/%s', $document->user_id, $uuid);

            $storedPath = \Illuminate\Support\Facades\Storage::disk(config('filesystems.default'))->putFileAs($path, $file, $safeName);

            $updateData['file_url'] = $storedPath;
        }

        // Si estaba REJECTED, vuelve a PENDING y limpia comentarios
        if ($status === 'REJECTED') {
            $updateData['status'] = 'PENDING';
            $updateData['comments'] = null;
        }

        $document->update($updateData);

        // Audit Log
        \App\Models\AuditLog::logUpdate(
            auth('user_api')->user(),
            'VerificationDocument',
            $document->id,
            $oldData,
            $document->toArray()
        );

        return response()->json(['data' => $document]);
    }
}
