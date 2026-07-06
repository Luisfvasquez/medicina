<?php

namespace App\Http\Controllers\Api\V1\Phase5;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use Illuminate\Http\JsonResponse;

class PatientAppointmentController extends Controller
{
    public function index(\Illuminate\Http\Request $request): JsonResponse
    {
        $patientAccount = auth('patient_api')->user();
        $filter = $request->query('filter', 'all');
        $today = now()->toDateString();

        $query = Appointment::whereHas('patient', function ($q) use ($patientAccount) {
            $q->where('patient_account_id', $patientAccount->id);
        });

        if ($filter === 'upcoming') {
            $query->where('date', '>=', $today)
                ->whereNotIn('status', ['cancelled', 'completed']);
        } elseif ($filter === 'past') {
            $query->where(function ($q) use ($today) {
                $q->where('status', 'completed')
                  ->orWhere(function ($sub) use ($today) {
                      $sub->where('date', '<', $today)
                          ->where('status', '!=', 'cancelled');
                  });
            });
        } elseif ($filter === 'cancelled') {
            $query->where('status', 'cancelled');
        }

        $appointments = $query->with(['patient', 'doctor', 'clinicBranch'])
            ->latest('date')
            ->latest('time')
            ->paginate(20);

        return response()->json($appointments);
    }

    public function show(string $id): JsonResponse
    {
        $patientAccount = auth('patient_api')->user();

        $appointment = Appointment::whereHas('patient', function ($q) use ($patientAccount) {
            $q->where('patient_account_id', $patientAccount->id);
        })
            ->with(['patient', 'doctor', 'clinicBranch', 'consultation'])
            ->findOrFail($id);

        return response()->json(['data' => $appointment]);
    }
}
