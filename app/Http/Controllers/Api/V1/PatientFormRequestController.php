<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\FormTemplate;
use App\Models\Clinic;
use App\Models\PatientFormRequest;
use App\Models\PatientRecord;
use App\Models\Consultation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class PatientFormRequestController extends Controller
{
    // Médico: Compartir plantilla con paciente
    public function share(Request $request): JsonResponse
    {
        $request->validate([
            'patient_uuid' => 'required|uuid|exists:patients,uuid',
            'form_template_uuid' => 'required|uuid|exists:form_templates,uuid',
            'clinic_uuid' => 'nullable|uuid|exists:clinics,uuid',
        ]);

        $doctor = auth('user_api')->user();

        $patient = Patient::where('uuid', $request->patient_uuid)->firstOrFail();
        $template = FormTemplate::where('uuid', $request->form_template_uuid)->firstOrFail();
        
        $clinic = $request->clinic_uuid 
            ? Clinic::where('uuid', $request->clinic_uuid)->first()
            : null;

        $formRequest = PatientFormRequest::create([
            'uuid' => (string) Str::uuid(),
            'patient_id' => $patient->id,
            'user_id' => $doctor->id,
            'clinic_id' => $clinic?->id,
            'form_template_id' => $template->id,
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Plantilla compartida correctamente.',
            'data' => $formRequest->load(['patient', 'formTemplate'])
        ], 201);
    }

    // Paciente: Listar sus solicitudes pendientes
    public function patientIndex(): JsonResponse
    {
        $patientAccount = auth('patient_api')->user();
        
        // Buscar el perfil de paciente asociado a esta cuenta global
        $patient = Patient::where('patient_account_id', $patientAccount->id)->first();
        if (!$patient) {
            return response()->json(['data' => []]);
        }

        $requests = PatientFormRequest::with(['formTemplate', 'user', 'clinic'])
            ->where('patient_id', $patient->id)
            ->where('status', 'pending')
            ->latest()
            ->get();

        return response()->json(['data' => $requests]);
    }

    // Paciente: Ver una solicitud y su plantilla schema
    public function patientShow(string $uuid): JsonResponse
    {
        $patientAccount = auth('patient_api')->user();
        $patient = Patient::where('patient_account_id', $patientAccount->id)->firstOrFail();

        $formRequest = PatientFormRequest::with(['formTemplate', 'user', 'clinic'])
            ->where('uuid', $uuid)
            ->where('patient_id', $patient->id)
            ->firstOrFail();

        return response()->json(['data' => $formRequest]);
    }

    // Paciente: Completar y enviar el formulario dinámico
    public function patientSubmit(Request $request, string $uuid): JsonResponse
    {
        $request->validate([
            'dynamic_data' => 'required|array',
        ]);

        $patientAccount = auth('patient_api')->user();
        $patient = Patient::where('patient_account_id', $patientAccount->id)->firstOrFail();

        $formRequest = PatientFormRequest::with('formTemplate')
            ->where('uuid', $uuid)
            ->where('patient_id', $patient->id)
            ->where('status', 'pending')
            ->firstOrFail();

        $record = DB::transaction(function () use ($formRequest, $patient, $request) {
            // 1. Crear el registro clínico del paciente (PatientRecord)
            $record = PatientRecord::create([
                'uuid' => (string) Str::uuid(),
                'user_id' => $formRequest->user_id,
                'patient_id' => $patient->id,
                'clinic_id' => $formRequest->clinic_id,
                'form_template_id' => $formRequest->form_template_id,
                'form_schema_snapshot' => $formRequest->formTemplate->schema_json,
                'dynamic_data' => $request->dynamic_data,
            ]);

            // 2. Ejecutar bindings para sincronizar perfil general del paciente
            $record->syncPatientDataBindings();

            // 3. Completar la solicitud compartida
            $formRequest->update([
                'status' => 'completed',
                'completed_at' => now(),
                'patient_record_id' => $record->id,
            ]);

            return $record;
        });

        return response()->json([
            'message' => 'Formulario enviado correctamente.',
            'data' => $record->load(['patient', 'user', 'formTemplate'])
        ]);
    }
}
