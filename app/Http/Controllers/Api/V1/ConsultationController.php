<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Consultation\StoreConsultationRequest;
use App\Http\Requests\Api\V1\Consultation\UpdateConsultationRequest;
use App\Models\Consultation;
use Illuminate\Http\JsonResponse;

class ConsultationController extends Controller
{
    public function index(): JsonResponse
    {
        $user = auth('user_api')->user();

        $consultations = Consultation::with(['patient', 'user', 'clinicBranch', 'vitalSign', 'labRequest'])
            ->when($user->role === 'DOCTOR', fn($q) => $q->where('user_id', $user->id))
            ->when($user->role === 'PATIENT', fn($q) => $q->where('patient_id', $user->patient->id ?? null))
            ->latest()
            ->paginate(20);

        return response()->json(['data' => $consultations]);
    }

    public function store(StoreConsultationRequest $request): JsonResponse
    {
        $consultation = Consultation::create($request->validated());

        return response()->json(['data' => $consultation->load(['patient', 'user', 'clinicBranch'])], 201);
    }

    public function show(string $id): JsonResponse
    {
        $consultation = Consultation::with(['patient', 'user', 'clinicBranch', 'formTemplate', 'vitalSign', 'labRequest', 'prescription', 'followUps'])
            ->findOrFail($id);

        $user = auth('user_api')->user();
        if ($user->role === 'DOCTOR' && $consultation->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json(['data' => $consultation]);
    }

    public function update(UpdateConsultationRequest $request, string $id): JsonResponse
    {
        $consultation = Consultation::findOrFail($id);

        $user = auth('user_api')->user();
        if ($user->role === 'DOCTOR' && $consultation->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $consultation->update($request->validated());

        return response()->json(['data' => $consultation->load(['patient', 'user', 'clinicBranch', 'vitalSign', 'labRequest', 'prescription', 'followUps'])]);
    }

    public function destroy(string $id): JsonResponse
    {
        $consultation = Consultation::findOrFail($id);

        $user = auth('user_api')->user();
        if ($user->role !== 'ADMIN' && $consultation->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $consultation->delete();

        return response()->json(null, 204);
    }
}
