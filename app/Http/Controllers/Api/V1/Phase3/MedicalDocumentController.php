<?php

namespace App\Http\Controllers\Api\V1\Phase3;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\MedicalDocument\StoreMedicalDocumentRequest;
use App\Http\Requests\Api\V1\MedicalDocument\UpdateMedicalDocumentRequest;
use App\Models\MedicalDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class MedicalDocumentController extends Controller
{
    public function index(): JsonResponse
    {
        $user = auth('user_api')->user();

        $documents = MedicalDocument::with(['patient', 'user'])
            ->when($user->role === 'DOCTOR', fn($q) => $q->where('user_id', $user->id))
            ->latest()
            ->paginate(20);

        return response()->json(['data' => $documents]);
    }

    public function store(StoreMedicalDocumentRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['user_id'] = auth('user_api')->id();
        $data['public_token'] = $this->generatePublicToken();

        $document = MedicalDocument::create($data);

        return response()->json(['data' => $document->load(['patient', 'user'])], 201);
    }

    public function show(string $id): JsonResponse
    {
        $document = MedicalDocument::with(['patient', 'user'])->findOrFail($id);

        $user = auth('user_api')->user();
        if ($user->role === 'DOCTOR' && $document->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json(['data' => $document]);
    }

    public function update(UpdateMedicalDocumentRequest $request, string $id): JsonResponse
    {
        $document = MedicalDocument::findOrFail($id);

        $user = auth('user_api')->user();
        if ($user->role === 'DOCTOR' && $document->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $document->update($request->validated());

        return response()->json(['data' => $document->load(['patient', 'user'])]);
    }

    public function destroy(string $id): JsonResponse
    {
        $document = MedicalDocument::findOrFail($id);

        $user = auth('user_api')->user();
        if ($user->role !== 'ADMIN' && $document->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $document->delete();

        return response()->json(null, 204);
    }

    // ponytail: 16-char random hex; upgrade to crypto-secure if document verification becomes sensitive
    private function generatePublicToken(): string
    {
        do {
            $token = Str::random(16);
        } while (MedicalDocument::where('public_token', $token)->exists());

        return $token;
    }
}
