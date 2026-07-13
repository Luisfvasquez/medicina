<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Prescription;
use App\Models\LabResult;
use App\Models\FollowUp;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DoctorDashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = auth('user_api')->user();
        $role = $user && $user->role instanceof \App\Enums\UserRole ? $user->role->value : ($user->role ?? null);
        if (!$user || $role !== 'DOCTOR') {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        Carbon::setLocale('es');
        $timezone = $request->header('X-Timezone') ?? $request->input('timezone') ?? 'America/Caracas';
        if (!in_array($timezone, timezone_identifiers_list(), true)) {
            $timezone = 'America/Caracas';
        }
        $today = Carbon::today($timezone)->toDateString();

        // 1. Calcular KPIs
        $pacientesHoyCount = Appointment::where('user_id', $user->id)
            ->where('date', $today)
            ->where('status', '!=', \App\Enums\AppointmentStatus::CANCELLED->value)
            ->count();

        $citasPendientesCount = Appointment::where('user_id', $user->id)
            ->where('date', $today)
            ->whereIn('status', [
                \App\Enums\AppointmentStatus::PENDING->value,
                \App\Enums\AppointmentStatus::IN_PROGRESS->value
            ])
            ->count();

        $recetasEmitidasCount = Prescription::where('user_id', $user->id)
            ->whereDate('created_at', $today)
            ->count();

        // 2. Agenda Diaria
        $appointmentsToday = Appointment::where('user_id', $user->id)
            ->where('date', $today)
            ->with(['patient'])
            ->orderBy('time')
            ->get();

        $agendaList = [];
        foreach ($appointmentsToday as $apt) {
            $agendaList[] = [
                'id' => $apt->uuid,
                'patientName' => $apt->patient ? ($apt->patient->first_name . ' ' . $apt->patient->last_name) : 'Paciente',
                'type' => $apt->type === 'ONLINE' ? 'Virtual' : 'Presencial',
                'time' => Carbon::parse($apt->time)->format('H:i'),
                'status' => $apt->status,
            ];
        }

        // 3. Siguiente Paciente
        $nextApt = Appointment::where('user_id', $user->id)
            ->where('date', $today)
            ->whereIn('status', [
                \App\Enums\AppointmentStatus::PENDING->value,
                \App\Enums\AppointmentStatus::IN_PROGRESS->value
            ])
            ->with(['patient'])
            ->orderBy('time')
            ->first();

        $nextPatient = null;
        if ($nextApt && $nextApt->patient) {
            $patient = $nextApt->patient;
            $alerts = [];

            if (!empty($patient->allergies)) {
                $alerts[] = [
                    'type' => 'allergy',
                    'label' => 'Alergia: ' . $patient->allergies,
                ];
            }

            if (!empty($patient->chronic_conditions)) {
                $alerts[] = [
                    'type' => 'chronic',
                    'label' => 'Crónico: ' . $patient->chronic_conditions,
                ];
            }

            $nextPatient = [
                'id' => $nextApt->uuid,
                'name' => $patient->first_name . ' ' . $patient->last_name,
                'time' => Carbon::parse($nextApt->time)->format('H:i'),
                'type' => $nextApt->type === 'ONLINE' ? 'Virtual' : 'Presencial',
                'reason' => $nextApt->reason ?? $nextApt->notes ?? 'Consulta de control',
                'alerts' => $alerts,
            ];
        }

        // 4. Acciones Requeridas Dinámicas
        $actions = [];

        // Acción A: Resultados de laboratorio pendientes de revisión
        $pendingLabs = LabResult::whereNull('reviewed_at')
            ->whereHas('labRequest.consultation', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->with('patient')
            ->get();

        foreach ($pendingLabs as $lab) {
            $actions[] = [
                'id' => 'lab-' . $lab->uuid,
                'label' => 'Revisar resultado crítico de laboratorio',
                'type' => 'lab',
                'patientName' => $lab->patient ? ($lab->patient->first_name . ' ' . $lab->patient->last_name) : 'Paciente',
                'completed' => false,
            ];
        }

        // Acción B: Citas pasadas que quedaron pendientes (Inasistencias / No-show)
        $missedApts = Appointment::where('user_id', $user->id)
            ->where('date', '<', $today)
            ->where('status', \App\Enums\AppointmentStatus::PENDING->value)
            ->with('patient')
            ->get();

        foreach ($missedApts as $apt) {
            $actions[] = [
                'id' => 'call-' . $apt->uuid,
                'label' => 'Llamar a paciente que no asistió',
                'type' => 'call',
                'patientName' => $apt->patient ? ($apt->patient->first_name . ' ' . $apt->patient->last_name) : 'Paciente',
                'completed' => false,
            ];
        }

        // Acción C: Seguimientos pendientes
        $pendingFollows = FollowUp::where('user_id', $user->id)
            ->where('status', \App\Enums\FollowStatus::PENDING->value)
            ->with('patient')
            ->get();

        foreach ($pendingFollows as $follow) {
            $actions[] = [
                'id' => 'follow-' . $follow->uuid,
                'label' => 'Agendar seguimiento / Contacto médico',
                'type' => 'follow-up',
                'patientName' => $follow->patient ? ($follow->patient->first_name . ' ' . $follow->patient->last_name) : 'Paciente',
                'completed' => false,
            ];
        }

        return response()->json([
            'kpis' => [
                [
                    'label' => 'Pacientes hoy',
                    'value' => $pacientesHoyCount,
                    'trend' => 0,
                    'subtitle' => 'hoy',
                ],
                [
                    'label' => 'Recetas emitidas',
                    'value' => $recetasEmitidasCount,
                    'trend' => 0,
                    'subtitle' => 'hoy',
                ],
                [
                    'label' => 'Citas pendientes',
                    'value' => $citasPendientesCount,
                    'trend' => 0,
                    'subtitle' => 'restantes hoy',
                ],
            ],
            'agenda' => $agendaList,
            'next_patient' => $nextPatient,
            'actions' => $actions,
        ]);
    }
}
