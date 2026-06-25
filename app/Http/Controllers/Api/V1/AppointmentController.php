<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Appointment\StoreAppointmentRequest;
use App\Http\Requests\Api\V1\Appointment\UpdateAppointmentRequest;
use App\Models\Appointment;
use App\Services\AvailabilityException;
use App\Services\AvailabilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = auth('user_api')->user();
        $clinicBranchId = $request->query('clinic_branch_id');

        $appointments = Appointment::with(['patient', 'doctor', 'clinicBranch'])
            ->when($user->role === 'DOCTOR', fn($q) => $q->where('user_id', $user->id))
            ->when($user->role === 'PATIENT', fn($q) => $q->where('patient_id', $user->patient->id ?? null))
            ->when($clinicBranchId, fn($q) => $q->where('clinic_branch_id', $clinicBranchId))
            ->latest()
            ->paginate(20);

        return response()->json(['data' => $appointments]);
    }

    public function store(StoreAppointmentRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $doctorId = $validated['user_id'];
        $date = $validated['date'];
        $time = $validated['time'];
        $clinicBranchId = $validated['clinic_branch_id'] ?? null;

        // Validate slot availability (pass branch_id if provided)
        $availabilityService = app(AvailabilityService::class);
        try {
            $branchId = $clinicBranchId
                ? \App\Models\ClinicBranch::where('uuid', $clinicBranchId)->first()?->id
                : null;
            $availabilityService->validateAppointment($doctorId, $date, $time, null, $branchId);
        } catch (AvailabilityException $e) {
            return response()->json([
                'error' => 'Slot no disponible',
                'code' => $e->code,
                'message' => $e->getMessage(),
            ], 409);
        }

        // Normalize slot_time
        $validated['slot_time'] = \Carbon\Carbon::parse($time)->format('H:i:s');

        $appointment = Appointment::create($validated);

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

        $validated = $request->validated();

        // If date/time changed, validate new slot
        if (isset($validated['date']) || isset($validated['time'])) {
            $doctorId = $validated['user_id'] ?? $appointment->user_id;
            $date = $validated['date'] ?? $appointment->date;
            $time = $validated['time'] ?? $appointment->time;
            $branchId = isset($validated['clinic_branch_id'])
                ? \App\Models\ClinicBranch::where('uuid', $validated['clinic_branch_id'])->first()?->id
                : $appointment->clinic_branch_id;

            $availabilityService = app(AvailabilityService::class);
            try {
                $availabilityService->validateAppointment($doctorId, $date, $time, $appointment->id, $branchId);
            } catch (AvailabilityException $e) {
                return response()->json([
                    'error' => 'Slot no disponible',
                    'code' => $e->code,
                    'message' => $e->getMessage(),
                ], 409);
            }

            $validated['slot_time'] = \Carbon\Carbon::parse($time)->format('H:i:s');
        }

        $appointment->update($validated);

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
