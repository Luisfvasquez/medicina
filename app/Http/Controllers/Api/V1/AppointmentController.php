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
        $timeframe = $request->query('timeframe');
        $status = $request->query('status');
        $search = $request->query('search');

        $today = \Carbon\Carbon::today()->toDateString();

        $query = Appointment::with(['patient', 'doctor', 'clinicBranch'])
            ->when($user->role === 'DOCTOR', fn($q) => $q->where('user_id', $user->id))
            ->when($user->role === 'PATIENT', fn($q) => $q->where('patient_id', $user->patient->id ?? null))
            ->when($clinicBranchId, fn($q) => $q->where('clinic_branch_id', $clinicBranchId));

        // 1. Filtro Temporal (timeframe)
        if ($timeframe === 'today') {
            $query->where('date', $today);
        } elseif ($timeframe === 'upcoming') {
            $query->where('date', '>', $today)
                  ->where('status', '!=', \App\Enums\AppointmentStatus::CANCELLED->value);
        } elseif ($timeframe === 'past') {
            $query->where('date', '<', $today);
        }

        // 2. Filtro por Estado (status)
        if ($status) {
            $query->where('status', $status);
        }

        // 3. Búsqueda Avanzada Multicampo (search)
        if ($search) {
            $query->whereHas('patient', function ($q) use ($search) {
                $q->where(function($sub) use ($search) {
                    $sub->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('national_id', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            });
        }

        // Ordenar cronológicamente
        if ($timeframe === 'past') {
            $query->orderBy('date', 'desc')->orderBy('time', 'desc');
        } else {
            $query->orderBy('date', 'asc')->orderBy('time', 'asc');
        }

        $appointments = $query->paginate(20);

        $role = $user && $user->role instanceof \App\Enums\UserRole ? $user->role->value : ($user->role ?? null);
        if ($role === 'DOCTOR') {
            $appointments->getCollection()->transform(function ($appointment) {
                if ($appointment->patient) {
                    $appointment->patient->clinical_summary = $this->getClinicalSummaryForPatient($appointment->patient);
                }
                return $appointment;
            });
        }

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
        $appointment = Appointment::with([
            'patient',
            'doctor',
            'clinicBranch',
            'consultation.vitalSign',
            'consultation.prescription.items.medication'
        ])->where('uuid', $id)->firstOrFail();

        $user = auth('user_api')->user();
        $role = $user && $user->role instanceof \App\Enums\UserRole ? $user->role->value : ($user->role ?? null);
        if ($role === 'DOCTOR' && $appointment->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Si el paciente tiene cita, adjuntar los signos vitales más recientes
        if ($appointment->patient) {
            $vitals = null;
            if ($appointment->consultation && $appointment->consultation->vitalSign) {
                $vitals = $appointment->consultation->vitalSign;
            } else {
                $vitals = \App\Models\VitalSign::where('patient_id', $appointment->patient->id)
                    ->latest('date')
                    ->first();
            }
            $appointment->patient->latest_vital_signs = $vitals;
        }

        return response()->json(['data' => $appointment]);
    }

    public function update(UpdateAppointmentRequest $request, string $id): JsonResponse
    {
        $appointment = Appointment::where('uuid', $id)->firstOrFail();

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
        $appointment = Appointment::where('uuid', $id)->firstOrFail();

        $user = auth('user_api')->user();
        if ($user->role === 'DOCTOR' && $appointment->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $appointment->delete();

        return response()->json(null, 204);
    }

    private function getClinicalSummaryForPatient($patient)
    {
        if (!$patient) {
            return null;
        }

        $today = \Carbon\Carbon::today()->toDateString();

        // 1. Estilo de vida
        $lifestyleModel = \App\Models\Lifestyle::where('patient_id', $patient->id)->first();
        $lifestyle = $lifestyleModel ? [
            'smoking_status' => $lifestyleModel->smoking_status,
            'alcohol_consumption' => $lifestyleModel->alcohol_consumption,
            'activity_level' => $lifestyleModel->activity_level,
            'diet_type' => $lifestyleModel->diet_type,
        ] : null;

        // 2. Antecedentes Quirúrgicos
        $surgical = \App\Models\SurgicalHistory::where('patient_id', $patient->id)
            ->get()
            ->map(function ($item) {
                $year = $item->date ? \Carbon\Carbon::parse($item->date)->year : null;
                return $item->procedure . ($year ? " ({$year})" : "");
            })
            ->toArray();

        // 3. Antecedentes Familiares
        $family = \App\Models\FamilyHistory::where('patient_id', $patient->id)
            ->get()
            ->map(function ($item) {
                return $item->condition . " (" . strtolower($item->relationship) . ")";
            })
            ->toArray();

        // 4. Medicamentos Activos (Recetas Activas)
        $activeMeds = \App\Models\Prescription::with(['items.medication'])
            ->where('patient_id', $patient->id)
            ->where('status', 'ACTIVE')
            ->where('expiration_date', '>=', $today)
            ->get()
            ->flatMap(function ($rx) {
                return $rx->items->map(function ($item) {
                    $comm = $item->medication->commercial_name;
                    $act = $item->medication->active_principle;
                    $medName = $comm && $act ? "{$comm} ({$act})" : ($comm ?: ($act ?: 'Medicamento'));
                    return "{$medName} {$item->dose} - {$item->frequency} ({$item->duration})";
                });
            })
            ->unique()
            ->values()
            ->toArray();

        // 5. Historial Reciente (Últimas 2 consultas)
        $recentConsultations = \App\Models\Consultation::with('user')
            ->where('patient_id', $patient->id)
            ->latest('date')
            ->take(2)
            ->get()
            ->map(function ($c) {
                return [
                    'date' => $c->date,
                    'diagnosis' => $c->diagnosis,
                    'reason' => $c->reason,
                    'doctor_name' => $c->user->full_name ?? 'Médico Tratante',
                ];
            })
            ->toArray();

        return [
            'allergies' => $patient->allergies,
            'chronic_conditions' => $patient->chronic_conditions,
            'lifestyle' => $lifestyle,
            'surgical_history' => $surgical,
            'family_history' => $family,
            'active_medications' => $activeMeds,
            'recent_history' => $recentConsultations,
        ];
    }
}
