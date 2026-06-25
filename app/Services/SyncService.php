<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\City;
use App\Models\ClinicBranch;
use App\Models\ClinicSchedule;
use App\Models\Consultation;
use App\Models\DoctorSchedule;
use App\Models\FamilyHistory;
use App\Models\FollowUp;
use App\Models\FormTemplate;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\LabRequest;
use App\Models\LabResult;
use App\Models\Lifestyle;
use App\Models\MedicalBackground;
use App\Models\Medication;
use App\Models\Notification;
use App\Models\ObstetricHistory;
use App\Models\Patient;
use App\Models\PatientAccount;
use App\Models\Payment;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\QuoteOffer;
use App\Models\QuoteRequest;
use App\Models\ScheduleException;
use App\Models\Specialty;
use App\Models\SurgicalHistory;
use App\Models\User;
use App\Models\Vaccination;
use App\Models\VitalSign;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SyncService
{
    /** Entities that are pull-only (global catalogs, no push accepted). */
    private const PULL_ONLY_ENTITIES = ['cities', 'specialties', 'form_templates', 'clinic_branches', 'clinic_schedules'];

    /** Push entities processed in topological dependency order. */
    private const PUSH_ENTITIES_ORDERED = [
        'doctor_schedules',         // no FK dependency, doctor-owned
        'schedule_exceptions',      // no FK dependency, doctor-owned
        'medications',              // no FK dependency
        'medical_backgrounds',      // depends on patient
        'lifestyles',               // depends on patient
        'obstetric_histories',      // depends on patient
        'surgical_histories',       // depends on patient
        'family_histories',         // depends on patient
        'vaccinations',             // depends on patient
        'vital_signs',              // depends on consultation
        'lab_requests',             // depends on consultation
        'prescriptions',            // depends on patient
        'prescription_items',       // depends on prescription
        'follow_ups',               // depends on patient
        'lab_results',              // depends on lab_request
        'invoices',                 // depends on patient
        'invoice_items',            // depends on invoice
        'payments',                 // depends on invoice
        'quote_requests',           // depends on patient
        'quote_offers',             // depends on quote_request
        'notifications',            // depends on user
    ];

    /** Entities that patients can push (excludes doctor-specific entities). */
    private const PATIENT_PUSH_ENTITIES = [
        'medications',
        'medical_backgrounds',
        'lifestyles',
        'obstetric_histories',
        'surgical_histories',
        'family_histories',
        'vaccinations',
        'vital_signs',
        'lab_requests',
        'prescriptions',
        'prescription_items',
        'follow_ups',
        'lab_results',
        'invoices',
        'invoice_items',
        'payments',
        'quote_requests',
        'quote_offers',
        'notifications',
    ];

    /**
     * Entity configuration: model class, fillable fields (excluding FK/uuid/user_id), FK map.
     * FK map: uuid_key => class to query (null = use patient, from patient_uuid).
     */
    private const ENTITY_CONFIG = [
        'patients'               => [Patient::class,             ['first_name', 'last_name', 'national_id', 'birth_date', 'gender', 'email', 'phone', 'address', 'city_id', 'blood_type', 'allergies', 'chronic_conditions', 'private_notes', 'emergency_contact_name', 'emergency_contact_phone']],
        'appointments'           => [Appointment::class,         ['clinic_branch_id', 'date', 'time', 'slot_time', 'type', 'status', 'notes'],                                      'patient_uuid', Patient::class],
        'consultations'          => [Consultation::class,        ['appointment_id', 'clinic_branch_id', 'form_template_id', 'date', 'status', 'reason', 'physical_exam', 'diagnosis', 'treatment_plan', 'dynamic_data'], 'patient_uuid', Patient::class],
        'medical_backgrounds'    => [MedicalBackground::class,   ['has_diabetes', 'has_hypertension', 'has_asthma', 'other_conditions', 'past_hospitalizations'],       'patient_uuid', Patient::class],
        'lifestyles'             => [Lifestyle::class,           ['smoking_status', 'alcohol_consumption', 'activity_level', 'diet_type'],                               'patient_uuid', Patient::class],
        'obstetric_histories'    => [ObstetricHistory::class,    ['last_period_date', 'pregnancies', 'births', 'cesareans', 'abortions', 'contraceptive_method'],        'patient_uuid', Patient::class],
        'surgical_histories'     => [SurgicalHistory::class,     ['procedure', 'date', 'hospital', 'notes'],                                                             'patient_uuid', Patient::class],
        'family_histories'       => [FamilyHistory::class,       ['condition', 'relationship', 'note'],                                                                  'patient_uuid', Patient::class],
        'vaccinations'           => [Vaccination::class,         ['vaccine', 'dose_number', 'date'],                                                                     'patient_uuid', Patient::class],
        'prescriptions'          => [Prescription::class,        ['consultation_id', 'clinic_branch_id', 'date', 'expiration_date', 'notes', 'public_token', 'status'], 'patient_uuid', Patient::class],
        'follow_ups'             => [FollowUp::class,            ['consultation_id', 'scheduled_date', 'status', 'response'],                                           'patient_uuid', Patient::class],
        'invoices'               => [Invoice::class,             ['patient_account_id', 'clinic_branch_id', 'consultation_id', 'prescription_id', 'subtotal', 'tax', 'discount', 'total', 'currency', 'status', 'due_date', 'notes'], 'patient_uuid', Patient::class],
        'quote_requests'         => [QuoteRequest::class,        ['city_id', 'status'],                                                                    [['patient_uuid', Patient::class], ['prescription_uuid', Prescription::class]]],
        'vital_signs'            => [VitalSign::class,           ['patient_id', 'weight', 'height', 'systolic_bp', 'diastolic_bp', 'heart_rate', 'respiratory_rate', 'temperature', 'oxygen_sat', 'date'], 'consultation_uuid', Consultation::class],
        'lab_requests'           => [LabRequest::class,          ['exams_list', 'instructions', 'is_completed'],                                                          'consultation_uuid', Consultation::class],
        'prescription_items'     => [PrescriptionItem::class,    ['medication_id', 'dose', 'frequency', 'duration', 'quantity', 'notes'],                                'prescription_uuid', Prescription::class],
        'lab_results'            => [LabResult::class,           ['patient_id', 'file_url', 'result_json', 'notes', 'reviewed_by', 'reviewed_at', 'status', 'performed_at'], 'lab_request_uuid', LabRequest::class],
        'invoice_items'          => [InvoiceItem::class,         ['description', 'quantity', 'unit_price', 'total'],                                                      'invoice_uuid', Invoice::class],
        'payments'               => [Payment::class,             ['amount', 'method', 'reference', 'paid_at', 'notes'],                                                  'invoice_uuid', Invoice::class],
        'quote_offers'           => [QuoteOffer::class,          ['provider_id', 'price', 'currency', 'availability', 'comments'],                                       'quote_request_uuid', QuoteRequest::class],
        'notifications'          => [Notification::class,        ['patient_account_id', 'type', 'title', 'message', 'is_read', 'link']],
        'medications'            => [Medication::class,           ['active_principle', 'concentration', 'presentation', 'administration_route', 'commercial_name', 'requires_prescription', 'contraindications', 'is_active']],
        'doctor_schedules'       => [DoctorSchedule::class,       ['weekday', 'start_time', 'end_time', 'appointment_duration', 'max_per_slot', 'is_active']],
        'schedule_exceptions'    => [ScheduleException::class,   ['exception_date', 'exception_type', 'custom_start_time', 'custom_end_time', 'reason']],
    ];

    /**
     * Process a bulk sync request for doctors/providers: push changes and pull server updates.
     */
    public function syncForUser(array $push, ?Carbon $lastSyncTimestamp, User $user): array
    {
        // Initialize push results for all entity types
        $pushResults = [];
        $allEntityKeys = array_merge(
            ['patients', 'appointments', 'consultations'],
            self::PUSH_ENTITIES_ORDERED
        );
        foreach ($allEntityKeys as $entity) {
            $pushResults[$entity] = ['success' => [], 'errors' => []];
        }

        DB::transaction(function () use ($push, $user, &$pushResults) {
            // Track UUID→ID maps for each entity that can be a FK target
            $uuidMaps = [];

            // 1. Patients first (everything depends on them)
            $pushResults['patients'] = $this->upsertEntities(
                Patient::class,
                $push['patients'] ?? [],
                [],
                [Patient::class, ['first_name', 'last_name', 'national_id', 'birth_date', 'gender', 'email', 'phone', 'address', 'city_id', 'blood_type', 'allergies', 'chronic_conditions', 'private_notes', 'emergency_contact_name', 'emergency_contact_phone']],
                $user,
                true
            );
            $uuidMaps[Patient::class] = $this->buildPatientUuidMapFromPush($push);

            // 2. Appointments (depend on patient)
            $pushResults['appointments'] = $this->upsertEntities(
                Appointment::class,
                $push['appointments'] ?? [],
                ['patient_uuid' => $uuidMaps[Patient::class]],
                [Appointment::class, ['clinic_branch_id', 'date', 'time', 'type', 'status', 'notes']],
                $user,
                true
            );

            // 3. Consultations (depend on patient)
            $pushResults['consultations'] = $this->upsertEntities(
                Consultation::class,
                $push['consultations'] ?? [],
                ['patient_uuid' => $uuidMaps[Patient::class]],
                [Consultation::class, ['appointment_id', 'clinic_branch_id', 'form_template_id', 'date', 'status', 'reason', 'physical_exam', 'diagnosis', 'treatment_plan', 'dynamic_data']],
                $user,
                true
            );
            $uuidMaps[Consultation::class] = $this->buildModelUuidMap(Consultation::class, $push['consultations'] ?? []);

            // 4. Process remaining entities in topological order
            foreach (self::PUSH_ENTITIES_ORDERED as $entity) {
                $items = $push[$entity] ?? [];
                if (empty($items)) {
                    continue;
                }

                // For entities that depend on Prescription, ensure the map exists
                if (in_array($entity, ['prescription_items', 'quote_requests'], true) && ! isset($uuidMaps[Prescription::class])) {
                    $uuidMaps[Prescription::class] = $this->buildFkMapFromPush($push, Prescription::class, 'prescription_uuid');
                }
                // For entities that depend on Invoice, ensure the map exists
                if (in_array($entity, ['invoice_items', 'payments'], true) && ! isset($uuidMaps[Invoice::class])) {
                    $uuidMaps[Invoice::class] = $this->buildFkMapFromPush($push, Invoice::class, 'invoice_uuid');
                }
                // For entities that depend on QuoteRequest, ensure the map exists
                if (in_array($entity, ['quote_offers'], true) && ! isset($uuidMaps[QuoteRequest::class])) {
                    $uuidMaps[QuoteRequest::class] = $this->buildFkMapFromPush($push, QuoteRequest::class, 'quote_request_uuid');
                }
                // For entities that depend on LabRequest, ensure the map exists
                if (in_array($entity, ['lab_results'], true) && ! isset($uuidMaps[LabRequest::class])) {
                    $uuidMaps[LabRequest::class] = $this->buildFkMapFromPush($push, LabRequest::class, 'lab_request_uuid');
                }
                // For entities that depend on Consultation, ensure the map exists
                if (in_array($entity, ['vital_signs', 'lab_requests'], true) && ! isset($uuidMaps[Consultation::class])) {
                    $uuidMaps[Consultation::class] = $this->buildFkMapFromPush($push, Consultation::class, 'consultation_uuid');
                }

                $fkMaps = $this->resolveFkMaps($entity, $uuidMaps);
                [$modelClass, $fillable] = self::ENTITY_CONFIG[$entity];
                $needsUserPatch = in_array('user_id', (new $modelClass())->getFillable());

                $pushResults[$entity] = $this->upsertEntities(
                    $modelClass,
                    $items,
                    $fkMaps,
                    [$modelClass, $fillable],
                    $user,
                    $needsUserPatch
                );

                // Build UUID map for this entity so dependents can find it
                $uuidMaps[$modelClass] = $this->buildModelUuidMap($modelClass, $items);
            }
        });

        // Pull phase (outside transaction — read-only)
        $hasMore = false;
        $pull    = $this->pullChanges($lastSyncTimestamp, $hasMore);

        return [
            'sync_timestamp' => Carbon::now()->format('Y-m-d\TH:i:s.v\Z'),
            'has_more'       => $hasMore,
            'push_results'   => $pushResults,
            'pull'           => $pull,
        ];
    }

    /**
     * Process a bulk sync request for patients: push changes and pull their own data.
     */
    public function syncForPatient(array $push, ?Carbon $lastSyncTimestamp, PatientAccount $patientAccount): array
    {
        // Find or create the Patient record for this account
        $patient = $patientAccount->patients()->first();

        // Initialize push results
        $pushResults = [];
        $allEntityKeys = array_merge(
            ['patients', 'appointments', 'consultations'],
            self::PATIENT_PUSH_ENTITIES
        );
        foreach ($allEntityKeys as $entity) {
            $pushResults[$entity] = ['success' => [], 'errors' => []];
        }

        DB::transaction(function () use ($push, $patient, &$pushResults, $patientAccount) {
            $uuidMaps = [];

            // 1. Patients - patient account updates their own record
            if ($patient) {
                $pushResults['patients'] = $this->upsertPatientEntities(
                    Patient::class,
                    $push['patients'] ?? [],
                    [],
                    [Patient::class, ['first_name', 'last_name', 'national_id', 'birth_date', 'gender', 'email', 'phone', 'address', 'city_id', 'blood_type', 'allergies', 'chronic_conditions', 'private_notes', 'emergency_contact_name', 'emergency_contact_phone']],
                    $patient,
                    $patientAccount
                );
                $uuidMaps[Patient::class] = $this->buildPatientUuidMapFromPush($push);
            }

            // 2. Appointments (depend on patient)
            if ($patient) {
                $pushResults['appointments'] = $this->upsertPatientEntities(
                    Appointment::class,
                    $push['appointments'] ?? [],
                    ['patient_uuid' => $uuidMaps[Patient::class] ?? []],
                    [Appointment::class, ['clinic_branch_id', 'date', 'time', 'slot_time', 'type', 'status', 'notes']],
                    $patient,
                    $patientAccount
                );
            }

            // 3. Consultations (depend on patient)
            if ($patient) {
                $pushResults['consultations'] = $this->upsertPatientEntities(
                    Consultation::class,
                    $push['consultations'] ?? [],
                    ['patient_uuid' => $uuidMaps[Patient::class] ?? []],
                    [Consultation::class, ['appointment_id', 'clinic_branch_id', 'form_template_id', 'date', 'status', 'reason', 'physical_exam', 'diagnosis', 'treatment_plan', 'dynamic_data']],
                    $patient,
                    $patientAccount
                );
                $uuidMaps[Consultation::class] = $this->buildModelUuidMap(Consultation::class, $push['consultations'] ?? []);
            }

            // 4. Process remaining patient entities in topological order
            foreach (self::PATIENT_PUSH_ENTITIES as $entity) {
                $items = $push[$entity] ?? [];
                if (empty($items)) {
                    continue;
                }

                // For entities that depend on Prescription, ensure the map exists
                if (in_array($entity, ['prescription_items', 'quote_requests'], true) && ! isset($uuidMaps[Prescription::class])) {
                    $uuidMaps[Prescription::class] = $this->buildFkMapFromPush($push, Prescription::class, 'prescription_uuid');
                }
                // For entities that depend on Invoice, ensure the map exists
                if (in_array($entity, ['invoice_items', 'payments'], true) && ! isset($uuidMaps[Invoice::class])) {
                    $uuidMaps[Invoice::class] = $this->buildFkMapFromPush($push, Invoice::class, 'invoice_uuid');
                }
                // For entities that depend on QuoteRequest, ensure the map exists
                if (in_array($entity, ['quote_offers'], true) && ! isset($uuidMaps[QuoteRequest::class])) {
                    $uuidMaps[QuoteRequest::class] = $this->buildFkMapFromPush($push, QuoteRequest::class, 'quote_request_uuid');
                }
                // For entities that depend on LabRequest, ensure the map exists
                if (in_array($entity, ['lab_results'], true) && ! isset($uuidMaps[LabRequest::class])) {
                    $uuidMaps[LabRequest::class] = $this->buildFkMapFromPush($push, LabRequest::class, 'lab_request_uuid');
                }
                // For entities that depend on Consultation, ensure the map exists
                if (in_array($entity, ['vital_signs', 'lab_requests'], true) && ! isset($uuidMaps[Consultation::class])) {
                    $uuidMaps[Consultation::class] = $this->buildFkMapFromPush($push, Consultation::class, 'consultation_uuid');
                }

                $fkMaps = $this->resolveFkMaps($entity, $uuidMaps);
                [$modelClass, $fillable] = self::ENTITY_CONFIG[$entity];

                $pushResults[$entity] = $this->upsertPatientEntities(
                    $modelClass,
                    $items,
                    $fkMaps,
                    [$modelClass, $fillable],
                    $patient,
                    $patientAccount
                );

                // Build UUID map for this entity so dependents can find it
                $uuidMaps[$modelClass] = $this->buildModelUuidMap($modelClass, $items);
            }
        });

        // Pull phase (outside transaction — read-only) - filtered by patient
        $hasMore = false;
        $pull    = $this->pullChangesForPatient($lastSyncTimestamp, $hasMore, $patient, $patientAccount);

        return [
            'sync_timestamp' => Carbon::now()->format('Y-m-d\TH:i:s.v\Z'),
            'has_more'       => $hasMore,
            'push_results'   => $pushResults,
            'pull'           => $pull,
        ];
    }

    /**
     * Patient-specific entity upsert - sets patient_id instead of user_id.
     */
    private function upsertPatientEntities(
        string $modelClass,
        array $items,
        array $fkMaps,
        array $config,
        ?Patient $patient,
        PatientAccount $patientAccount
    ): array {
        [$_] = $config;
        $fillableFields = $config[1] ?? [];
        $result = ['success' => [], 'errors' => []];

        // If no patient, only allow creating if this entity can create a new patient record
        if (!$patient && $modelClass !== Patient::class) {
            // Can't push dependent entities without a patient
            foreach ($items as $item) {
                $result['errors'][] = [
                    'uuid'    => $item['uuid'] ?? 'unknown',
                    'field'   => 'patient',
                    'message' => 'No patient record found for this account.',
                ];
            }
            return $result;
        }

        foreach ($items as $item) {
            $itemUuid = $item['uuid'];

            // Resolve all FK references
            $fkData = [];
            foreach ($fkMaps as $uuidField => $idMap) {
                $uuidValue = $item[$uuidField] ?? null;
                $idField   = $this->uuidFieldToIdField($uuidField);

                if ($uuidValue && isset($idMap[$uuidValue])) {
                    $fkData[$idField] = $idMap[$uuidValue];
                }
            }

            try {
                $existing = $modelClass::where('uuid', $itemUuid)->first();

                $data = $this->onlyFillable(new $modelClass(), $item, $fillableFields);
                foreach ($fkData as $col => $val) {
                    $data[$col] = $val;
                }

                // Set patient_id for patient entities
                if ($patient && in_array('patient_id', (new $modelClass())->getFillable())) {
                    $data['patient_id'] = $patient->id;
                }

                if ($modelClass === Patient::class) {
                    $data['patient_account_id'] = $patientAccount->id;
                }

                if (!$existing) {
                    $data['uuid'] = $itemUuid;
                    $modelClass::create($data);
                    $result['success'][] = $itemUuid;
                    continue;
                }

                // LWW: accept only if client timestamp is newer
                $clientTs = Carbon::parse($item['updated_at']);
                if ($clientTs->gt($existing->updated_at)) {
                    $existing->update($data);
                    $result['success'][] = $itemUuid;
                } else {
                    $result['errors'][] = [
                        'uuid'    => $itemUuid,
                        'field'   => 'updated_at',
                        'message' => 'Server version is newer.',
                    ];
                }
            } catch (\Illuminate\Database\QueryException $e) {
                $result['errors'][] = [
                    'uuid'    => $itemUuid,
                    'field'   => $this->extractFkField($e),
                    'message' => $e->getMessage(),
                ];
            } catch (\Throwable $e) {
                throw $e;
            }
        }

        return $result;
    }

    /**
     * Pull filtered changes for a specific patient.
     */
    private function pullChangesForPatient(?Carbon $lastSyncTimestamp, bool &$hasMore, ?Patient $patient, PatientAccount $patientAccount): array
    {
        if (!$lastSyncTimestamp) {
            return [
                'patients' => [],
                'appointments' => [],
                'consultations' => [],
            ];
        }

        if (!$patient) {
            return [
                'patients' => [],
                'appointments' => [],
                'consultations' => [],
            ];
        }

        $limit = 500;
        $hasMore = false;
        $ts = $lastSyncTimestamp->format('Y-m-d H:i:s');
        $patientId = $patient->id;

        $pull = [];

        // Pull patient's own record
        $patients = Patient::where('id', $patientId)
            ->where('updated_at', '>', $ts)
            ->withTrashed()
            ->limit($limit)
            ->get()
            ->map(fn ($p) => $p->toArray())
            ->toArray();
        if (count($patients) >= $limit) {
            $hasMore = true;
        }
        $pull['patients'] = $patients;

        // Pull patient's appointments
        $appointments = Appointment::where('patient_id', $patientId)
            ->where('updated_at', '>', $ts)
            ->with('patient')
            ->withTrashed()
            ->limit($limit)
            ->get()
            ->map(function ($a) {
                $data = $a->toArray();
                $data['patient_uuid'] = $a->patient->uuid ?? null;
                return $data;
            })
            ->toArray();
        if (count($appointments) >= $limit) {
            $hasMore = true;
        }
        $pull['appointments'] = $appointments;

        // Pull patient's consultations
        $consultations = Consultation::where('patient_id', $patientId)
            ->where('updated_at', '>', $ts)
            ->with('patient')
            ->withTrashed()
            ->limit($limit)
            ->get()
            ->map(function ($c) {
                $data = $c->toArray();
                $data['patient_uuid'] = $c->patient->uuid ?? null;
                return $data;
            })
            ->toArray();
        if (count($consultations) >= $limit) {
            $hasMore = true;
        }
        $pull['consultations'] = $consultations;

        // Pull patient-accessible entities filtered by patient_id
        $patientEntityModels = [
            'medical_backgrounds'   => MedicalBackground::class,
            'lifestyles'            => Lifestyle::class,
            'obstetric_histories'   => ObstetricHistory::class,
            'surgical_histories'    => SurgicalHistory::class,
            'family_histories'      => FamilyHistory::class,
            'vaccinations'          => Vaccination::class,
            'vital_signs'           => VitalSign::class,
            'lab_requests'          => LabRequest::class,
            'prescriptions'         => Prescription::class,
            'prescription_items'    => PrescriptionItem::class,
            'follow_ups'            => FollowUp::class,
            'lab_results'           => LabResult::class,
            'invoices'              => Invoice::class,
            'invoice_items'         => InvoiceItem::class,
            'payments'              => Payment::class,
            'quote_requests'        => QuoteRequest::class,
            'quote_offers'          => QuoteOffer::class,
            'notifications'         => Notification::class,
        ];

        foreach (self::PATIENT_PUSH_ENTITIES as $entity) {
            $modelClass = $patientEntityModels[$entity] ?? null;
            if (!$modelClass) {
                $pull[$entity] = [];
                continue;
            }

            $query = $modelClass::where('updated_at', '>', $ts);

            // Filter by patient_id if the model has it
            if (in_array('patient_id', (new $modelClass())->getFillable())) {
                $query->where('patient_id', $patientId);
            }

            // Filter by patient_account_id if the model has it
            if (in_array('patient_account_id', (new $modelClass())->getFillable())) {
                $query->where('patient_account_id', $patientAccount->id ?? 0);
            }

            $records = $query->limit($limit)
                ->get()
                ->map(fn ($r) => $r->toArray())
                ->toArray();
            if (count($records) >= $limit) {
                $hasMore = true;
            }
            $pull[$entity] = $records;
        }

        // Pull global catalogs (no filter needed)
        $catalogModels = [
            'cities'           => City::class,
            'specialties'      => Specialty::class,
            'medications'      => Medication::class,
            'form_templates'   => FormTemplate::class,
            'clinic_branches'  => ClinicBranch::class,
            'clinic_schedules' => ClinicSchedule::class,
        ];
        foreach (self::PULL_ONLY_ENTITIES as $entity) {
            $modelClass = $catalogModels[$entity];
            $records = $modelClass::where('updated_at', '>', $ts)
                ->limit($limit)
                ->get()
                ->map(fn ($r) => $r->toArray())
                ->toArray();
            if (count($records) >= $limit) {
                $hasMore = true;
            }
            $pull[$entity] = $records;
        }

        return $pull;
    }

    // -------------------------------------------------------------------------
    //  Generic upsert engine
    // -------------------------------------------------------------------------

    /**
     * Generic entity upsert with FK resolution and LWW conflict handling.
     *
     * @param class-string<\Illuminate\Database\Eloquent\Model> $modelClass
     * @param array[]  $items
     * @param array    $fkMaps      uuid_field => [uuid => id]
     * @param array    $config      [modelClass, fillableFields]
     * @param User     $user
     * @param bool     $setUserId   whether to set user_id from auth
     */
    private function upsertEntities(
        string $modelClass,
        array $items,
        array $fkMaps,
        array $config,
        User $user,
        bool $setUserId = false
    ): array {
        [$_, $fillableFields] = $config;
        $result = ['success' => [], 'errors' => []];

        foreach ($items as $item) {
            $itemUuid = $item['uuid'];

            // Resolve all FK references
            $fkData = [];
            foreach ($fkMaps as $uuidField => $idMap) {
                $uuidValue = $item[$uuidField] ?? null;
                $idField   = $this->uuidFieldToIdField($uuidField);

                if (! $uuidValue || ! isset($idMap[$uuidValue])) {
                    $result['errors'][] = [
                        'uuid'    => $itemUuid,
                        'field'   => $uuidField,
                        'message' => 'Referenced ' . $this->fkLabel($uuidField) . ' does not exist.',
                    ];
                    continue 2; // Skip to next item
                }
                $fkData[$idField] = $idMap[$uuidValue];
            }

            // Special case: patient_uuid for consultations, appointments already handled.
            // For entities that need patient_id derived from patient_uuid, the fkMaps handles it.

            try {
                $existing = $modelClass::where('uuid', $itemUuid)->first();

                $data = $this->onlyFillable(new $modelClass(), $item, $fillableFields);
                foreach ($fkData as $col => $val) {
                    $data[$col] = $val;
                }
                if ($setUserId) {
                    $data['user_id'] = $user->id;
                }

                if (! $existing) {
                    $data['uuid'] = $itemUuid;
                    // Special handling for patient: resolve PatientAccount
                    if ($modelClass === Patient::class) {
                        $data['patient_account_id'] = $this->resolvePatientAccountId($data);
                    }
                    $modelClass::create($data);
                    $result['success'][] = $itemUuid;
                    continue;
                }

                // LWW: accept only if client timestamp is newer
                $clientTs = Carbon::parse($item['updated_at']);
                if ($clientTs->gt($existing->updated_at)) {
                    $existing->update($data);
                    $result['success'][] = $itemUuid;
                } else {
                    $result['errors'][] = [
                        'uuid'    => $itemUuid,
                        'field'   => 'updated_at',
                        'message' => 'Server version is newer.',
                    ];
                }
            } catch (\Illuminate\Database\QueryException $e) {
                $result['errors'][] = [
                    'uuid'    => $itemUuid,
                    'field'   => $this->extractFkField($e),
                    'message' => $e->getMessage(),
                ];
            } catch (\Throwable $e) {
                throw $e;
            }
        }

        return $result;
    }

    // -------------------------------------------------------------------------
    //  Pull phase
    // -------------------------------------------------------------------------

    private function pullChanges(?Carbon $lastSyncTimestamp, bool &$hasMore): array
    {
        if (! $lastSyncTimestamp) {
            $pull = ['patients' => [], 'appointments' => [], 'consultations' => []];
            foreach (self::PULL_ONLY_ENTITIES as $entity) {
                $pull[$entity] = [];
            }
            foreach (self::PUSH_ENTITIES_ORDERED as $entity) {
                $pull[$entity] = [];
            }
            return $pull;
        }

        $limit   = 500;
        $hasMore = false;
        $ts      = $lastSyncTimestamp->format('Y-m-d H:i:s');

        $pull = [];

        // Pull patients
        $patients = Patient::where('updated_at', '>', $ts)
            ->withTrashed()
            ->limit($limit)
            ->get()
            ->map(fn ($p) => $p->toArray())
            ->toArray();
        if (count($patients) >= $limit) {
            $hasMore = true;
        }
        $pull['patients'] = $patients;

        // Pull appointments with patient_uuid
        $appointments = Appointment::where('updated_at', '>', $ts)
            ->with('patient')
            ->withTrashed()
            ->limit($limit)
            ->get()
            ->map(function ($a) {
                $data = $a->toArray();
                $data['patient_uuid'] = $a->patient->uuid ?? null;
                return $data;
            })
            ->toArray();
        if (count($appointments) >= $limit) {
            $hasMore = true;
        }
        $pull['appointments'] = $appointments;

        // Pull consultations with patient_uuid
        $consultations = Consultation::where('updated_at', '>', $ts)
            ->with('patient')
            ->withTrashed()
            ->limit($limit)
            ->get()
            ->map(function ($c) {
                $data = $c->toArray();
                $data['patient_uuid'] = $c->patient->uuid ?? null;
                return $data;
            })
            ->toArray();
        if (count($consultations) >= $limit) {
            $hasMore = true;
        }
        $pull['consultations'] = $consultations;

        // Pull PULL_ONLY_ENTITIES (global catalogs, no user_id filter)
        $catalogModels = [
            'cities'           => City::class,
            'specialties'      => Specialty::class,
            'medications'      => Medication::class,
            'form_templates'   => FormTemplate::class,
            'clinic_branches'  => ClinicBranch::class,
            'clinic_schedules' => ClinicSchedule::class,
        ];
        foreach (self::PULL_ONLY_ENTITIES as $entity) {
            $modelClass = $catalogModels[$entity];
            $records = $modelClass::where('updated_at', '>', $ts)
                ->limit($limit)
                ->get()
                ->map(fn ($r) => $r->toArray())
                ->toArray();
            if (count($records) >= $limit) {
                $hasMore = true;
            }
            $pull[$entity] = $records;
        }

        // Pull push entities
        $pushModels = [
            'medications'           => Medication::class,
            'medical_backgrounds'   => MedicalBackground::class,
            'lifestyles'            => Lifestyle::class,
            'obstetric_histories'   => ObstetricHistory::class,
            'surgical_histories'    => SurgicalHistory::class,
            'family_histories'      => FamilyHistory::class,
            'vaccinations'          => Vaccination::class,
            'vital_signs'           => VitalSign::class,
            'lab_requests'          => LabRequest::class,
            'prescriptions'         => Prescription::class,
            'prescription_items'    => PrescriptionItem::class,
            'follow_ups'            => FollowUp::class,
            'lab_results'           => LabResult::class,
            'invoices'              => Invoice::class,
            'invoice_items'         => InvoiceItem::class,
            'payments'              => Payment::class,
            'quote_requests'        => QuoteRequest::class,
            'quote_offers'          => QuoteOffer::class,
            'notifications'         => Notification::class,
            'doctor_schedules'      => DoctorSchedule::class,
            'schedule_exceptions'   => ScheduleException::class,
        ];

        foreach (self::PUSH_ENTITIES_ORDERED as $entity) {
            $modelClass = $pushModels[$entity];
            $records = $modelClass::where('updated_at', '>', $ts)
                ->limit($limit)
                ->get()
                ->map(fn ($r) => $r->toArray())
                ->toArray();
            if (count($records) >= $limit) {
                $hasMore = true;
            }
            $pull[$entity] = $records;
        }

        return $pull;
    }

    // -------------------------------------------------------------------------
    //  Helpers
    // -------------------------------------------------------------------------

    /** Build a UUID→ID map from an array of pushed items for a given model. */
    private function buildModelUuidMap(string $modelClass, array $items): array
    {
        $map = [];
        if (empty($items)) {
            return $map;
        }

        $uuids = array_column($items, 'uuid');
        $records = $modelClass::whereIn('uuid', $uuids)->get();
        foreach ($records as $record) {
            $map[$record->uuid] = $record->id;
        }

        return $map;
    }

    /** Build a UUID→ID map for a FK-referenced model by scanning the push for references. */
    private function buildFkMapFromPush(array $push, string $modelClass, string $uuidField): array
    {
        $uuids = [];

        $allEntities = array_merge(['appointments', 'consultations'], self::PUSH_ENTITIES_ORDERED);
        foreach ($allEntities as $entity) {
            if (! isset(self::ENTITY_CONFIG[$entity])) {
                continue;
            }

            // Check if this entity itself is the model class (collect its own UUIDs)
            if (self::ENTITY_CONFIG[$entity][0] === $modelClass) {
                foreach ($push[$entity] ?? [] as $item) {
                    if (isset($item['uuid'])) {
                        $uuids[] = $item['uuid'];
                    }
                }
            }

            // Check if this entity references the model class via FK
            $fkConfigs = $this->extractFkConfigs(self::ENTITY_CONFIG[$entity]);
            foreach ($fkConfigs as [$fkField, $fkClass]) {
                if ($fkClass === $modelClass) {
                    foreach ($push[$entity] ?? [] as $item) {
                        if (isset($item[$fkField])) {
                            $uuids[] = $item[$fkField];
                        }
                    }
                }
            }
        }

        if (empty($uuids)) {
            return [];
        }

        $map = [];
        $records = $modelClass::whereIn('uuid', array_unique($uuids))->get();
        foreach ($records as $record) {
            $map[$record->uuid] = $record->id;
        }

        return $map;
    }

    /** Build patient UUID→ID map from all entities in the push that reference patients. */
    private function buildPatientUuidMapFromPush(array $push): array
    {
        $uuids = [];

        foreach ($push['patients'] ?? [] as $item) {
            $uuids[] = $item['uuid'];
        }

        $allEntities = array_merge(['appointments', 'consultations'], self::PUSH_ENTITIES_ORDERED);
        foreach ($allEntities as $entity) {
            if (! isset(self::ENTITY_CONFIG[$entity])) {
                continue;
            }
            $fkConfigs = $this->extractFkConfigs(self::ENTITY_CONFIG[$entity]);
            foreach ($fkConfigs as [$_, $fkModelClass]) {
                if ($fkModelClass === Patient::class) {
                    foreach ($push[$entity] ?? [] as $item) {
                        if (isset($item['patient_uuid'])) {
                            $uuids[] = $item['patient_uuid'];
                        }
                    }
                    break;
                }
            }
        }

        if (empty($uuids)) {
            return [];
        }

        $map = [];
        $patients = Patient::whereIn('uuid', array_unique($uuids))->get();
        foreach ($patients as $patient) {
            $map[$patient->uuid] = $patient->id;
        }

        return $map;
    }

    /** Build FK maps for an entity based on its configuration and available UUID maps. */
    private function resolveFkMaps(string $entity, array $uuidMaps): array
    {
        $config = self::ENTITY_CONFIG[$entity] ?? null;
        if (! $config) {
            return [];
        }

        // Determine FK configs from entity config
        $fkConfigs = $this->extractFkConfigs($config);
        if (empty($fkConfigs)) {
            return [];
        }

        $maps = [];
        foreach ($fkConfigs as [$fkUuidField, $fkModelClass]) {
            $maps[$fkUuidField] = $uuidMaps[$fkModelClass] ?? [];
        }

        return $maps;
    }

    /** Extract FK configuration pairs from an entity config entry. */
    private function extractFkConfigs(array $config): array
    {
        if (! isset($config[2])) {
            return [];
        }

        // New format: element 2 is an array of [uuidField, modelClass] pairs
        if (is_array($config[2])) {
            // Check if it's an array of pairs: [[f1, c1], [f2, c2]]
            if (is_array($config[2][0] ?? null)) {
                return $config[2];
            }
            // Single pair: [f1, c1]
            if (isset($config[2][1])) {
                return [$config[2]];
            }
        }

        // Old format: elements 2 and 3 are the FK pair
        if (isset($config[3])) {
            return [[$config[2], $config[3]]];
        }

        return [];
    }

    /** Convert 'patient_uuid' → 'patient_id', 'consultation_uuid' → 'consultation_id', etc. */
    private function uuidFieldToIdField(string $uuidField): string
    {
        return str_replace('_uuid', '_id', $uuidField);
    }

    /** Friendly label for FK resolution errors. */
    private function fkLabel(string $uuidField): string
    {
        return str_replace('_uuid', '', $uuidField);
    }

    /** Extract only the fillable keys present in the source array. */
    private function onlyFillable($model, array $source, array $allowed): array
    {
        $data = [];
        foreach ($allowed as $key) {
            if (array_key_exists($key, $source)) {
                $data[$key] = $source[$key];
            }
        }
        return $data;
    }

    /** Resolve or create a PatientAccount for a new patient. */
    private function resolvePatientAccountId(array $data): int
    {
        $email = $data['email'] ?? null;

        if ($email) {
            $account = PatientAccount::where('email', $email)->first();
            if ($account) {
                return $account->id;
            }
        }

        $account = PatientAccount::create([
            'email'         => $email ?? 'placeholder-' . ($data['uuid'] ?? uniqid()) . '@luca.local',
            'password_hash' => bcrypt(bin2hex(random_bytes(16))),
            'full_name'     => trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? '')),
            'phone'         => $data['phone'] ?? null,
        ]);

        return $account->id;
    }

    /** Try to extract the FK column name from a QueryException message. */
    private function extractFkField(\Illuminate\Database\QueryException $e): string
    {
        $msg = $e->getMessage();

        if (preg_match('/FOREIGN KEY constraint failed/', $msg)) {
            return 'relation';
        }

        if (preg_match('/column \'([^\']+)\'/', $msg, $m)) {
            return $m[1];
        }
        if (preg_match('/key \'([^\']+)\'/', $msg, $m)) {
            return $m[1];
        }

        return 'relation';
    }
}
