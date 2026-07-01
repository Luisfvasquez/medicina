<?php

namespace App\Http\Resources\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array{id: string, fullName: string, email: ?string, phone: ?string}
     */
    public function toArray(Request $request): array
    {
        return [
            'id'       => $this->uuid,
            'fullName' => $this->full_name,
            'email'    => $this->email,
            'phone'    => $this->phone,
        ];
    }
}
