<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Consultation\StoreConsultationRequest;
use App\Http\Requests\Api\V1\Consultation\UpdateConsultationRequest;
use App\Models\Consultation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConsultationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = auth('user_api')->user();
        $clinicBranchId = $request->query('clinic_branch_id');
        $patientUuid = $request->query('patient_uuid');

        $query = Consultation::with(['patient', 'user', 'clinicBranch', 'vitalSign', 'labRequest'])
            ->when($user->role === 'DOCTOR', fn($q) => $q->where('user_id', $user->id))
            ->when($user->role === 'PATIENT', fn($q) => $q->where('patient_id', $user->patient->id ?? null))
            ->when($clinicBranchId, fn($q) => $q->where('clinic_branch_id', $clinicBranchId));

        if ($patientUuid) {
            $query->whereHas('patient', function ($q) use ($patientUuid) {
                $q->where('uuid', $patientUuid);
            });
        }

        $consultations = $query->latest()
            ->paginate(20);

        return response()->json(['data' => $consultations]);
    }

    public function store(StoreConsultationRequest $request): JsonResponse
    {
        $user = auth('user_api')->user();
        
        // Resolver IDs desde UUIDs públicos
        $patient = \App\Models\Patient::where('uuid', $request->patient_uuid)->firstOrFail();
        $appointment = $request->appointment_uuid 
            ? \App\Models\Appointment::where('uuid', $request->appointment_uuid)->first() 
            : null;
        $clinicBranch = $request->clinic_branch_uuid 
            ? \App\Models\ClinicBranch::where('uuid', $request->clinic_branch_uuid)->first() 
            : null;

        $consultation = Consultation::create([
            'user_id' => $user->id,
            'patient_id' => $patient->id,
            'appointment_id' => $appointment?->id,
            'clinic_branch_id' => $clinicBranch?->id ?? $appointment?->clinic_branch_id,
            'form_template_id' => $request->form_template_id,
            'date' => $request->date ?? \Carbon\Carbon::now(),
            'status' => 'in-progress',
            'reason' => $request->reason,
            'physical_exam' => $request->physical_exam,
            'diagnosis' => $request->diagnosis,
            'treatment_plan' => $request->treatment_plan,
            'dynamic_data' => $request->dynamic_data,
        ]);

        if ($appointment) {
            $appointment->update(['status' => 'in-progress']);
        }

        $vitalsData = $request->input('vitals');
        if (is_array($vitalsData)) {
            \App\Models\VitalSign::create(array_merge($vitalsData, [
                'consultation_id' => $consultation->id,
                'patient_id' => $patient->id,
                'date' => \Carbon\Carbon::now(),
            ]));
        }

        return response()->json(['data' => $consultation->load(['patient', 'user', 'clinicBranch', 'vitalSign'])], 201);
    }

    public function show(string $id): JsonResponse
    {
        $consultation = Consultation::with(['patient', 'user', 'clinicBranch', 'formTemplate', 'vitalSign', 'labRequest', 'prescription.items.medication', 'followUps'])->where('uuid', $id)->firstOrFail();

        $user = auth('user_api')->user();
        if ($user->role === 'DOCTOR' && $consultation->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json(['data' => $consultation]);
    }

    public function update(UpdateConsultationRequest $request, string $id): JsonResponse
    {
        $consultation = Consultation::where('uuid', $id)->firstOrFail();

        $user = auth('user_api')->user();
        $role = $user && $user->role instanceof \App\Enums\UserRole ? $user->role->value : ($user->role ?? null);
        if ($role === 'DOCTOR' && $consultation->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Resolver UUIDs opcionales si vienen
        $updateData = $request->validated();
        if (isset($updateData['patient_uuid'])) {
            $updateData['patient_id'] = \App\Models\Patient::where('uuid', $updateData['patient_uuid'])->firstOrFail()->id;
        }
        if (isset($updateData['appointment_uuid'])) {
            $updateData['appointment_id'] = \App\Models\Appointment::where('uuid', $updateData['appointment_uuid'])->firstOrFail()->id;
        }
        if (isset($updateData['clinic_branch_uuid'])) {
            $updateData['clinic_branch_id'] = \App\Models\ClinicBranch::where('uuid', $updateData['clinic_branch_uuid'])->firstOrFail()->id;
        }

        $consultation->update($updateData);

        $vitalsData = $request->input('vitals');
        if (is_array($vitalsData)) {
            \App\Models\VitalSign::updateOrCreate(
                ['consultation_id' => $consultation->id],
                array_merge($vitalsData, [
                    'patient_id' => $consultation->patient_id,
                    'date' => \Carbon\Carbon::now(),
                ])
            );
        }

        // Si el estado pasa a completed, actualizar la cita correspondiente
        if ($consultation->status === \App\Enums\ConsultationStatus::COMPLETED || $request->input('status') === 'completed') {
            if ($consultation->appointment) {
                $consultation->appointment->update(['status' => 'completed']);
            }
        }

        // Procesar recetas si se envían en el request
        $prescriptionsData = $request->input('prescriptions');
        if (is_array($prescriptionsData)) {
            // Eliminar anterior físicamente (incluso si está soft-deleted) para evitar violar la restricción unique de Postgres
            $consultation->prescription()->withTrashed()->forceDelete();

            if (count($prescriptionsData) > 0) {
                $rx = \App\Models\Prescription::create([
                    'user_id' => $consultation->user_id,
                    'patient_id' => $consultation->patient_id,
                    'consultation_id' => $consultation->id,
                    'clinic_branch_id' => $consultation->clinic_branch_id,
                    'date' => \Carbon\Carbon::now(),
                    'expiration_date' => \Carbon\Carbon::now()->addDays(30),
                    'notes' => $request->input('treatment_plan') ?? $consultation->treatment_plan ?? '',
                    'public_token' => \Illuminate\Support\Str::random(12),
                    'status' => 'ACTIVE',
                ]);

                foreach ($prescriptionsData as $item) {
                    if (!empty($item['medicationId'])) {
                        $medication = \App\Models\Medication::where('uuid', $item['medicationId'])->first();
                        if ($medication) {
                            \App\Models\PrescriptionItem::create([
                                'prescription_id' => $rx->id,
                                'medication_id' => $medication->id,
                                'dose' => $item['dose'] ?? '',
                                'frequency' => $item['frequency'] ?? '',
                                'duration' => $item['duration'] ?? '',
                                'notes' => $item['notes'] ?? '',
                            ]);
                        }
                    }
                }
            }
        }

        return response()->json(['data' => $consultation->load(['patient', 'user', 'clinicBranch', 'vitalSign', 'labRequest', 'prescription.items.medication', 'followUps'])]);
    }

    public function destroy(string $id): JsonResponse
    {
        $consultation = Consultation::where('uuid', $id)->firstOrFail();

        $user = auth('user_api')->user();
        if ($user->role !== 'ADMIN' && $consultation->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $consultation->delete();

        return response()->json(null, 204);
    }
}
