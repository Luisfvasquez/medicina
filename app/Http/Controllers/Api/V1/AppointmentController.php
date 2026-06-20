<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Appointment\StoreAppointmentRequest;
use App\Http\Requests\Api\V1\Appointment\UpdateAppointmentRequest;
use App\Models\Appointment;
use Illuminate\Http\JsonResponse;

class AppointmentController extends Controller
{
    public function index(): JsonResponse
    {
        $user = auth('user_api')->user();

        $appointments = Appointment::with(['patient', 'doctor', 'clinicBranch'])
            ->when($user->role === 'DOCTOR', fn($q) => $q->where('user_id', $user->id))
            ->when($user->role === 'PATIENT', fn($q) => $q->where('patient_id', $user->patient->id ?? null))
            ->latest()
            ->paginate(20);

        return response()->json(['data' => $appointments]);
    }

    public function store(StoreAppointmentRequest $request): JsonResponse
    {
        $appointment = Appointment::create($request->validated());

        return response()->json(['data' => $appointment->load(['patient', 'doctor', 'clinicBranch'])], 201);
    }

    public function show(string $id): JsonResponse
    {
        $appointment = Appointment::with(['patient', 'doctor', 'clinicBranch', 'consultation'])->findOrFail($id);

        $user = auth('user_api')->user();
        if ($user->role === 'DOCTOR' && $appointment->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json(['data' => $appointment]);
    }

    public function update(UpdateAppointmentRequest $request, string $id): JsonResponse
    {
        $appointment = Appointment::findOrFail($id);

        $user = auth('user_api')->user();
        if ($user->role === 'DOCTOR' && $appointment->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $appointment->update($request->validated());

        return response()->json(['data' => $appointment->load(['patient', 'doctor', 'clinicBranch'])]);
    }

    public function destroy(string $id): JsonResponse
    {
        $appointment = Appointment::findOrFail($id);

        $user = auth('user_api')->user();
        if ($user->role === 'DOCTOR' && $appointment->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $appointment->delete();

        return response()->json(null, 204);
    }
}
