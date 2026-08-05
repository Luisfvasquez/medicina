<?php

namespace App\Models;

use App\Enums\ConsultationStatus;
use App\Traits\HasPublicUuid;
use Illuminate\Database\Eloquent\Model;

class Consultation extends Model
{
    use HasPublicUuid, \Illuminate\Database\Eloquent\SoftDeletes, \App\Traits\SyncsPatientDataBindings;

    protected $fillable = [
        'uuid',
        'user_id',
        'patient_id',
        'patient_account_id',
        'appointment_id',
        'clinic_branch_id',
        'form_template_id',
        'form_schema_snapshot',
        'date',
        'status',
        'reason',
        'physical_exam',
        'diagnosis',
        'treatment_plan',
        'dynamic_data',
        'services_performed',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'datetime',
            'status' => ConsultationStatus::class,
            'dynamic_data' => 'array',
            'form_schema_snapshot' => 'array',
        ];
    }

    protected function servicesPerformed(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: function ($value) {
                $services = is_string($value) ? json_decode($value, true) : $value;
                if (!is_array($services)) return [];
                foreach ($services as &$service) {
                    if (isset($service['attachments']) && is_array($service['attachments'])) {
                        foreach ($service['attachments'] as &$attachment) {
                            $path = $attachment;
                            if (filter_var($attachment, FILTER_VALIDATE_URL) && str_contains($attachment, 'consultations/services_attachments')) {
                                $parts = explode('consultations/services_attachments', $attachment);
                                if (isset($parts[1])) {
                                    $path = 'consultations/services_attachments' . explode('?', $parts[1])[0];
                                }
                            }
                            
                            if (!filter_var($path, FILTER_VALIDATE_URL)) {
                                try {
                                    $extension = strtolower(pathinfo(parse_url($path, PHP_URL_PATH), PATHINFO_EXTENSION));
                                    $isDocument = in_array($extension, ['pdf', 'doc', 'docx']);
                                    $diskName = $isDocument ? 'r2_documents' : 'r2_images';
                                    
                                    $attachment = \Illuminate\Support\Facades\Storage::disk($diskName)->temporaryUrl($path, now()->addMinutes(60));
                                } catch (\Exception $e) { }
                            }
                        }
                    }
                }
                return $services;
            },
            set: function ($value) {
                $services = is_string($value) ? json_decode($value, true) : $value;
                if (!is_array($services)) return $value;
                
                foreach ($services as &$service) {
                    if (isset($service['attachments']) && is_array($service['attachments'])) {
                        foreach ($service['attachments'] as &$attachment) {
                            if (filter_var($attachment, FILTER_VALIDATE_URL) && str_contains($attachment, 'consultations/services_attachments')) {
                                $parts = explode('consultations/services_attachments', $attachment);
                                if (isset($parts[1])) {
                                    $cleanPath = explode('?', $parts[1])[0];
                                    $attachment = 'consultations/services_attachments' . $cleanPath;
                                }
                            }
                        }
                    }
                }
                
                return json_encode($services);
            }
        );
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function patientAccount()
    {
        return $this->belongsTo(PatientAccount::class);
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function clinicBranch()
    {
        return $this->belongsTo(ClinicBranch::class);
    }

    public function formTemplate()
    {
        return $this->belongsTo(FormTemplate::class);
    }

    public function vitalSign()
    {
        return $this->hasOne(VitalSign::class);
    }

    public function labRequests()
    {
        return $this->hasMany(LabRequest::class);
    }

    public function prescription()
    {
        return $this->hasOne(Prescription::class);
    }

    public function followUps()
    {
        return $this->hasMany(FollowUp::class);
    }

    public function toArray(): array
    {
        $array = parent::toArray();
        
        if (!empty($this->form_schema_snapshot)) {
            if (empty($array['form_template'])) {
                $array['form_template'] = [
                    'uuid' => null,
                    'name' => 'Plantilla Histórica',
                    'schema_json' => $this->form_schema_snapshot,
                ];
            } else {
                $array['form_template']['schema_json'] = $this->form_schema_snapshot;
            }
        }
        
        return $array;
    }


}
