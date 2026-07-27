<?php

namespace App\Services;

use App\Mail\LabResultUploadedMail;
use App\Models\LabResult;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class LabResultService
{
    public function uploadResult(array $data, ?int $reviewedById = null): LabResult
    {
        $labResult = LabResult::create([
            'uuid' => (string) Str::uuid(),
            'lab_request_id' => $data['lab_request_id'] ?? null,
            'patient_id' => $data['patient_id'] ?? null,
            'file_url' => $data['file_url'] ?? null,
            'result_json' => $data['result_json'] ?? [],
            'attachments_json' => $data['attachments_json'] ?? [],
            'notes' => $data['notes'] ?? null,
            'reviewed_by' => $reviewedById,
            'reviewed_at' => now(),
            'status' => 'completed',
            'performed_at' => $data['performed_at'] ?? now(),
        ]);

        // Trigger email notification if patient email is available
        $patientEmail = $labResult->patient?->email ?? $labResult->labRequest?->user?->email;
        if ($patientEmail) {
            try {
                Mail::to($patientEmail)->send(new LabResultUploadedMail($labResult));
                $labResult->update(['email_sent_at' => now()]);
            } catch (\Throwable $e) {
                // Log mail exception gracefully
                logger()->error("Error sending lab result email to {$patientEmail}: " . $e->getMessage());
            }
        }

        return $labResult;
    }
}
