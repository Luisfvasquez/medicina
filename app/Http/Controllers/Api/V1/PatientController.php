<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Patient\StorePatientRequest;
use App\Http\Requests\Api\V1\Patient\UpdatePatientRequest;
use App\Models\Patient;
use App\Models\PatientAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PatientController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = auth('user_api')->user();
        
        $patients = Patient::where('user_id', $user->id)
            ->orWhereHas('appointments', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->orWhereHas('consultations', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->latest()
            ->get();

        return response()->json(['data' => $patients]);
    }

    public function store(StorePatientRequest $request): JsonResponse
    {
        $user = auth('user_api')->user();
        $data = $request->validated();

        $patientAccountId = $this->resolvePatientAccountId($data);

        $patient = Patient::create(array_merge($data, [
            'user_id' => $user->id,
            'patient_account_id' => $patientAccountId,
        ]));

        return response()->json(['data' => $patient], 201);
    }

    public function show(string $uuid): JsonResponse
    {
        $user = auth('user_api')->user();

        $patient = Patient::where('uuid', $uuid)
            ->where(function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->orWhereHas('appointments', function ($q) use ($user) {
                        $q->where('user_id', $user->id);
                    })
                    ->orWhereHas('consultations', function ($q) use ($user) {
                        $q->where('user_id', $user->id);
                    });
            })
            ->firstOrFail();

        return response()->json(['data' => $patient]);
    }

    public function update(UpdatePatientRequest $request, string $uuid): JsonResponse
    {
        $user = auth('user_api')->user();

        $patient = Patient::where('uuid', $uuid)
            ->where(function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->orWhereHas('appointments', function ($q) use ($user) {
                        $q->where('user_id', $user->id);
                    })
                    ->orWhereHas('consultations', function ($q) use ($user) {
                        $q->where('user_id', $user->id);
                    });
            })
            ->firstOrFail();

        $data = $request->validated();
        if (isset($data['email'])) {
            $data['patient_account_id'] = $this->resolvePatientAccountId($data);
        }

        $patient->update($data);

        return response()->json(['data' => $patient->fresh()]);
    }

    public function destroy(string $uuid): JsonResponse
    {
        $user = auth('user_api')->user();

        // Only the owner doctor who created the file is allowed to delete it
        $patient = Patient::where('uuid', $uuid)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $patient->delete();

        return response()->json(null, 204);
    }

    /**
     * Resolve or create a PatientAccount for the patient.
     */
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
            'email'         => $email ?? 'placeholder-' . ($data['uuid'] ?? (string) Str::uuid()) . '@luca.local',
            'password_hash' => bcrypt(bin2hex(random_bytes(16))),
            'full_name'     => trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? '')),
            'phone'         => $data['phone'] ?? null,
        ]);

        return $account->id;
    }
}
