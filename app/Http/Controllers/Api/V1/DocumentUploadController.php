<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\DocumentUploadRequest;
use App\Models\MedicalDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class DocumentUploadController extends Controller
{
    public function upload(DocumentUploadRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $file = $validated['file'];

        $document = MedicalDocument::where('uuid', $validated['uuid'])->first();

        if (!$document) {
            return response()->json(['message' => 'Document not found'], 404);
        }

        $user = auth('user_api')->user();
        if ($document->user_id !== $user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName());
        $path = sprintf('medical_documents/%s/%s', $user->id, $document->uuid);

        $storedPath = Storage::disk('local')->putFileAs($path, $file, $safeName);

        $document->update([
            'pending_upload' => false,
            'file_path'      => $storedPath,
            'file_type'      => $file->getMimeType(),
            'file_size'      => $file->getSize(),
        ]);

        return response()->json([
            'message' => 'File uploaded successfully',
            'uuid'    => $document->uuid,
        ]);
    }
}
