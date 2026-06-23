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

        $document = VerificationDocument::create($data);

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

        // Users can only update documents in PENDING status
        if ($document->status !== 'PENDING') {
            return response()->json(['error' => 'Cannot update document with status: ' . $document->status], 422);
        }

        $document->update($request->validated());

        return response()->json(['data' => $document]);
    }
}
