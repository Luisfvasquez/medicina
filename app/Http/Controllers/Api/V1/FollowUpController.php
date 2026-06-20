<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\FollowUp\StoreFollowUpRequest;
use App\Http\Requests\Api\V1\FollowUp\UpdateFollowUpRequest;
use App\Models\FollowUp;
use Illuminate\Http\JsonResponse;

class FollowUpController extends Controller
{
    public function index(): JsonResponse
    {
        $user = auth('user_api')->user();

        $followUps = FollowUp::with(['patient', 'user', 'consultation'])
            ->when($user->role === 'DOCTOR', fn($q) => $q->where('user_id', $user->id))
            ->when($user->role === 'PATIENT', fn($q) => $q->where('patient_id', $user->patient->id ?? null))
            ->latest()
            ->paginate(20);

        return response()->json(['data' => $followUps]);
    }

    public function store(StoreFollowUpRequest $request): JsonResponse
    {
        $followUp = FollowUp::create($request->validated());

        return response()->json(['data' => $followUp->load(['patient', 'user', 'consultation'])], 201);
    }

    public function show(string $id): JsonResponse
    {
        $followUp = FollowUp::with(['patient', 'user', 'consultation'])->findOrFail($id);

        $user = auth('user_api')->user();
        if ($user->role === 'DOCTOR' && $followUp->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json(['data' => $followUp]);
    }

    public function update(UpdateFollowUpRequest $request, string $id): JsonResponse
    {
        $followUp = FollowUp::findOrFail($id);

        $user = auth('user_api')->user();
        if ($user->role === 'DOCTOR' && $followUp->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $followUp->update($request->validated());

        return response()->json(['data' => $followUp->fresh()->load(['patient', 'user', 'consultation'])]);
    }

    public function destroy(string $id): JsonResponse
    {
        $followUp = FollowUp::findOrFail($id);

        $user = auth('user_api')->user();
        if ($user->role !== 'ADMIN' && $followUp->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $followUp->delete();

        return response()->json(null, 204);
    }
}
