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

        $query = Consultation::with(['patient', 'user', 'clinicBranch', 'vitalSign', 'labRequests'])
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

        $formTemplate = $request->form_template_id 
            ? \App\Models\FormTemplate::where('uuid', $request->form_template_id)->first()
            : null;

        $consultation = Consultation::create([
            'user_id' => $user->id,
            'patient_id' => $patient->id,
            'appointment_id' => $appointment?->id,
            'clinic_branch_id' => $clinicBranch?->id ?? $appointment?->clinic_branch_id,
            'form_template_id' => $formTemplate?->id,
            'form_schema_snapshot' => $formTemplate?->schema_json,
            'date' => $request->date ?? \Carbon\Carbon::now(),
            'status' => 'in-progress',
            'reason' => $request->reason,
            'physical_exam' => $request->physical_exam,
            'diagnosis' => $request->diagnosis,
            'treatment_plan' => $request->treatment_plan,
            'dynamic_data' => $request->dynamic_data,
            'services_performed' => $request->services_performed,
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

        $followUpData = $request->input('follow_up');
        if (is_array($followUpData) && !empty($followUpData['scheduled_date'])) {
            \App\Models\FollowUp::create([
                'uuid' => $followUpData['uuid'] ?? \Illuminate\Support\Str::uuid()->toString(),
                'user_id' => $consultation->user_id,
                'patient_id' => $consultation->patient_id,
                'consultation_id' => $consultation->id,
                'scheduled_date' => $followUpData['scheduled_date'],
                'channel' => $followUpData['channel'] ?? 'MANUAL_CALL',
                'message_template' => $followUpData['message_template'] ?? null,
                'status' => \App\Enums\FollowStatus::PENDING->value,
            ]);
        }

        $consultation->syncPatientDataBindings();

        return response()->json(['data' => $consultation->load(['patient', 'user', 'clinicBranch', 'vitalSign', 'followUps'])], 201);
    }

    public function show(string $id): JsonResponse
    {
        $consultation = Consultation::with(['patient', 'user', 'clinicBranch', 'formTemplate', 'vitalSign', 'labRequests', 'prescription.items.medication', 'followUps'])->where('uuid', $id)->firstOrFail();

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
        if (isset($updateData['form_template_id'])) {
            $formTemplate = \App\Models\FormTemplate::where('uuid', $updateData['form_template_id'])->first();
            $updateData['form_template_id'] = $formTemplate?->id;
            $updateData['form_schema_snapshot'] = $formTemplate?->schema_json;
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

        // Si el estado pasa a completed, actualizar la cita correspondiente y generar pre-factura interna
        if ($consultation->status === \App\Enums\ConsultationStatus::COMPLETED || $request->input('status') === 'completed') {
            if ($consultation->appointment) {
                $consultation->appointment->update(['status' => 'completed']);
            }

            // Generar factura interna administrativa
            $this->createInternalInvoice($consultation);
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

                // Generar automáticamente la solicitud de cotización (QuoteRequest) para farmacias
                $latitude = null;
                $longitude = null;
                $cityId = null;

                if ($rx->clinic_branch_id) {
                    $clinicBranch = \App\Models\ClinicBranch::find($rx->clinic_branch_id);
                    if ($clinicBranch) {
                        $latitude = $clinicBranch->latitude;
                        $longitude = $clinicBranch->longitude;
                        $cityId = $clinicBranch->city_id;
                    }
                } else {
                    $doctor = auth('user_api')->user();
                    if ($doctor) {
                        $latitude = $doctor->latitude;
                        $longitude = $doctor->longitude;
                        $cityId = $doctor->city_id;
                    }
                }

                if ($latitude && $longitude) {
                    \App\Models\QuoteRequest::create([
                        'prescription_id' => $rx->id,
                        'patient_id' => $rx->patient_id,
                        'city_id' => $cityId,
                        'latitude' => $latitude,
                        'longitude' => $longitude,
                        'search_radius_km' => 15,
                        'status' => 'OPEN',
                    ]);
                }

                \App\Jobs\MatchPrescriptionWithInventoryJob::dispatch($rx);
            }
        }

        // Procesar seguimiento si se envía en el request
        $followUpData = $request->input('follow_up');
        if (is_array($followUpData) && !empty($followUpData['scheduled_date'])) {
            \App\Models\FollowUp::updateOrCreate(
                ['consultation_id' => $consultation->id],
                [
                    'uuid' => $followUpData['uuid'] ?? \Illuminate\Support\Str::uuid()->toString(),
                    'user_id' => $consultation->user_id,
                    'patient_id' => $consultation->patient_id,
                    'scheduled_date' => $followUpData['scheduled_date'],
                    'channel' => $followUpData['channel'] ?? 'MANUAL_CALL',
                    'message_template' => $followUpData['message_template'] ?? null,
                    'status' => \App\Enums\FollowStatus::PENDING->value,
                ]
            );
        } else if ($request->has('follow_up') && empty($followUpData['scheduled_date'])) {
            $consultation->followUps()->delete();
        }

        $consultation->syncPatientDataBindings();

        return response()->json(['data' => $consultation->load(['patient', 'user', 'clinicBranch', 'vitalSign', 'labRequests', 'prescription.items.medication', 'followUps'])]);
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

    private function createInternalInvoice(Consultation $consultation): void
    {
        // Verificar si ya tiene factura interna para no duplicar
        $exists = \App\Models\Invoice::where('consultation_id', $consultation->id)
            ->where('type', 'INTERNAL')
            ->exists();
        if ($exists) {
            return;
        }

        // Calcular costo de la consulta (base: $50)
        $subtotal = 50.00;
        
        // Sumar costo de servicios realizados
        $items = [];
        $items[] = [
            'description' => 'Consulta Médica de Control',
            'quantity' => 1,
            'unit_price' => 50.00,
            'total' => 50.00,
        ];

        $servicesPerformed = $consultation->services_performed;
        if (is_array($servicesPerformed)) {
            foreach ($servicesPerformed as $sp) {
                $price = isset($sp['price']) ? (float)$sp['price'] : 0.00;
                $qty = isset($sp['qty']) ? (int)$sp['qty'] : 1;
                $name = $sp['name'] ?? 'Procedimiento Médico';
                
                $totalItem = $price * $qty;
                $subtotal += $totalItem;

                $items[] = [
                    'description' => $name,
                    'quantity' => $qty,
                    'unit_price' => $price,
                    'total' => $totalItem,
                ];
            }
        }

        // Crear la factura
        $invoice = \App\Models\Invoice::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'user_id' => $consultation->user_id,
            'patient_id' => $consultation->patient_id,
            'patient_account_id' => $consultation->patient_account_id,
            'clinic_branch_id' => $consultation->clinic_branch_id,
            'consultation_id' => $consultation->id,
            'subtotal' => $subtotal,
            'tax' => 0.00,
            'discount' => 0.00,
            'total' => $subtotal,
            'currency' => 'USD',
            'status' => \App\Enums\InvoiceStatus::PAID,
            'due_date' => \Carbon\Carbon::now(),
            'notes' => 'Factura administrativa interna de honorarios y procedimientos.',
            'type' => 'INTERNAL',
        ]);

        // Crear los items de la factura
        foreach ($items as $item) {
            \App\Models\InvoiceItem::create([
                'uuid' => (string) \Illuminate\Support\Str::uuid(),
                'invoice_id' => $invoice->id,
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'total' => $item['total'],
            ]);
        }
    }
    public function downloadServiceAttachments(string $id, int $serviceIndex): \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\JsonResponse
    {
        $consultation = Consultation::where('uuid', $id)->firstOrFail();
        
        $rawServices = $consultation->getRawOriginal('services_performed');
        $services = is_string($rawServices) ? json_decode($rawServices, true) : ($rawServices ?? []);
        
        if (!isset($services[$serviceIndex])) {
            return response()->json(['error' => 'Service not found'], 404);
        }

        $attachments = $services[$serviceIndex]['attachments'] ?? [];
        if (empty($attachments)) {
            return response()->json(['error' => 'No attachments found for this service'], 404);
        }

        $zipFileName = 'attachments_' . $consultation->uuid . '_service_' . $serviceIndex . '.zip';
        $tempPath = sys_get_temp_dir() . '/' . uniqid('luca_zip_') . '.zip';

        $zip = new \ZipArchive();
        if ($zip->open($tempPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
            foreach ($attachments as $index => $pathOrUrl) {
                try {
                    $path = $pathOrUrl;
                    if (filter_var($pathOrUrl, FILTER_VALIDATE_URL) && str_contains($pathOrUrl, 'consultations/services_attachments')) {
                        $parts = explode('consultations/services_attachments', $pathOrUrl);
                        if (isset($parts[1])) {
                            $path = 'consultations/services_attachments' . explode('?', $parts[1])[0];
                        }
                    }

                    $extension = strtolower(pathinfo(parse_url($path, PHP_URL_PATH), PATHINFO_EXTENSION));
                    $isDocument = in_array($extension, ['pdf', 'doc', 'docx']);
                    $diskName = $isDocument ? 'r2_documents' : 'r2_images';

                    $fileContent = \Illuminate\Support\Facades\Storage::disk($diskName)->get($path);
                    
                    if ($fileContent !== null) {
                        if (!$extension) {
                            $extension = 'file';
                        }
                        $zip->addFromString('attachment_' . ($index + 1) . '.' . $extension, $fileContent);
                    }
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error("Error reading attachment from disk for zip: " . $e->getMessage());
                }
            }
            $zip->close();
        } else {
            return response()->json(['error' => 'Failed to create zip file'], 500);
        }

        return response()->download($tempPath, $zipFileName)->deleteFileAfterSend(true);
    }
}
