<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DocumentUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('user_api')->check();
    }

    public function rules(): array
    {
        return [
            'uuid' => ['required', 'string'],
            'file' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],
        ];
    }
}
