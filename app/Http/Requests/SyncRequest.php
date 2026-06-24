<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SyncRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('user_api')->check();
    }

    public function rules(): array
    {
        $pushEntities = [
            'patients',
            'appointments',
            'consultations',
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

        $rules = [
            'last_sync_timestamp' => ['nullable', 'date'],
            'push'                => ['nullable', 'array'],
        ];

        foreach ($pushEntities as $entity) {
            $rules["push.{$entity}"]               = ['nullable', 'array'];
            $rules["push.{$entity}.*.uuid"]          = ['required', 'string'];
            $rules["push.{$entity}.*.updated_at"]    = ['required', 'date'];
        }

        return $rules;
    }
}
