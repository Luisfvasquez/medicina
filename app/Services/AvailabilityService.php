<?php

namespace App\Services;

use App\Enums\ExceptionType;
use App\Enums\Weekday;
use App\Models\Appointment;
use App\Models\ClinicBranch;
use App\Models\ClinicSchedule;
use App\Models\DoctorSchedule;
use App\Models\ScheduleException;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AvailabilityService
{
    public const SLOT_FULL = 'SLOT_FULL';
    public const DOCTOR_ON_VACATION = 'DOCTOR_ON_VACATION';
    public const DOCTOR_DAY_OFF = 'DOCTOR_DAY_OFF';
    public const NO_SCHEDULE_FOR_DAY = 'NO_SCHEDULE_FOR_DAY';
    public const OUTSIDE_SCHEDULE_HOURS = 'OUTSIDE_SCHEDULE_HOURS';

    /**
     * Validates if an appointment can be scheduled.
     *
     * @throws AvailabilityException
     */
    public function validateAppointment(int $doctorId, string $date, string $time, ?int $excludeAppointmentId = null, ?int $clinicBranchId = null): bool
    {
        $dateCarbon = Carbon::parse($date);
        $timeCarbon = Carbon::parse($time);
        $weekday = Weekday::fromCarbon($dateCarbon);

        // 1. Check for schedule exceptions (applies to all branches)
        $exception = ScheduleException::where('user_id', $doctorId)
            ->where('exception_date', $dateCarbon->toDateString())
            ->first();

        if ($exception) {
            if ($exception->exception_type === ExceptionType::VACATION) {
                throw new AvailabilityException('El doctor está de vacaciones este día', self::DOCTOR_ON_VACATION);
            }

            if ($exception->exception_type === ExceptionType::DAY_OFF) {
                throw new AvailabilityException('El doctor no atiende este día', self::DOCTOR_DAY_OFF);
            }

            // CUSTOM_HOURS: validate against custom hours instead of regular schedule
            if ($exception->exception_type === ExceptionType::CUSTOM_HOURS) {
                $customStart = Carbon::parse($exception->custom_start_time);
                $customEnd = Carbon::parse($exception->custom_end_time);

                if ($timeCarbon->lt($customStart) || $timeCarbon->gte($customEnd)) {
                    throw new AvailabilityException(
                        "Horario fuera del horario especial de atención ({$exception->custom_start_time} - {$exception->custom_end_time})",
                        self::OUTSIDE_SCHEDULE_HOURS
                    );
                }

                return $this->checkSlotCapacity($doctorId, $dateCarbon, $timeCarbon, $exception->max_per_slot ?? 1, $excludeAppointmentId);
            }
        }

        // 2. Get doctor's schedule for this weekday (optionally filtered by branch)
        $scheduleQuery = DoctorSchedule::where('user_id', $doctorId)
            ->where('weekday', $weekday)
            ->where('is_active', true);

        if ($clinicBranchId) {
            $scheduleQuery->where('clinic_branch_id', $clinicBranchId);
        }

        $schedule = $scheduleQuery->first();

        if (!$schedule) {
            throw new AvailabilityException('El doctor no tiene horario definido para este día en esta sucursal', self::NO_SCHEDULE_FOR_DAY);
        }

        // 3. Check time is within doctor's schedule range
        $scheduleStart = Carbon::parse($schedule->start_time);
        $scheduleEnd = Carbon::parse($schedule->end_time);

        if ($timeCarbon->lt($scheduleStart) || $timeCarbon->gte($scheduleEnd)) {
            throw new AvailabilityException(
                "Horario fuera del horario de atención ({$schedule->start_time} - {$schedule->end_time})",
                self::OUTSIDE_SCHEDULE_HOURS
            );
        }

        // 4. If branch specified, verify it intersects with clinic's schedule
        if ($clinicBranchId) {
            $clinicSchedule = ClinicSchedule::where('clinic_branch_id', $clinicBranchId)
                ->where('weekday', $weekday)
                ->where('is_active', true)
                ->first();

            if ($clinicSchedule) {
                $clinicStart = Carbon::parse($clinicSchedule->start_time);
                $clinicEnd = Carbon::parse($clinicSchedule->end_time);

                if ($timeCarbon->lt($clinicStart) || $timeCarbon->gte($clinicEnd)) {
                    throw new AvailabilityException(
                        "La clínica está cerrada en este horario ({$clinicSchedule->start_time} - {$clinicSchedule->end_time})",
                        self::OUTSIDE_SCHEDULE_HOURS
                    );
                }
            }
        }

        // 5. Check slot capacity
        return $this->checkSlotCapacity($doctorId, $dateCarbon, $timeCarbon, $schedule->max_per_slot, $excludeAppointmentId);
    }

    /**
     * Check if the slot has available capacity.
     *
     * @throws AvailabilityException
     */
    private function checkSlotCapacity(int $doctorId, Carbon $date, Carbon $time, int $maxPerSlot, ?int $excludeAppointmentId): bool
    {
        $query = Appointment::where('user_id', $doctorId)
            ->where('date', $date->toDateString())
            ->where('slot_time', $time->format('H:i:s'))
            ->whereNotIn('status', ['cancelled', 'no_show']);

        if ($excludeAppointmentId) {
            $query->where('id', '!=', $excludeAppointmentId);
        }

        $existingCount = $query->count();

        if ($existingCount >= $maxPerSlot) {
            throw new AvailabilityException(
                "Este horario ya está lleno. Máximo {$maxPerSlot} paciente(s) por slot",
                self::SLOT_FULL
            );
        }

        return true;
    }

    /**
     * Get available slots for a doctor on a specific date.
     * If branchId is provided, only returns slots for that branch.
     * Otherwise returns all slots from all branches where the doctor works.
     */
    public function getAvailableSlots(int $doctorId, string $date, ?string $branchId = null): array
    {
        $dateCarbon = Carbon::parse($date);
        $weekday = Weekday::fromCarbon($dateCarbon);

        // Check for exception first (applies to all branches)
        $exception = ScheduleException::where('user_id', $doctorId)
            ->where('exception_date', $dateCarbon->toDateString())
            ->first();

        if ($exception && $exception->exception_type !== ExceptionType::CUSTOM_HOURS) {
            return [
                'is_available' => false,
                'exception' => [
                    'type' => $exception->exception_type->value,
                    'reason' => $exception->reason,
                ],
                'slots' => [],
            ];
        }

        // Get doctor's schedules
        $scheduleQuery = DoctorSchedule::where('user_id', $doctorId)
            ->where('weekday', $weekday)
            ->where('is_active', true);

        if ($branchId) {
            $scheduleQuery->where('clinic_branch_id', $branchId);
        }

        $schedules = $scheduleQuery->get();

        if ($schedules->isEmpty()) {
            return [
                'is_available' => false,
                'exception' => null,
                'slots' => [],
            ];
        }

        // Generate slots from all applicable schedules
        $allSlots = [];

        foreach ($schedules as $schedule) {
            $doctorStart = Carbon::parse($schedule->start_time);
            $doctorEnd = Carbon::parse($schedule->end_time);

            // If custom hours exception exists, use those instead for this schedule
            if ($exception && $exception->exception_type === ExceptionType::CUSTOM_HOURS) {
                $doctorStart = Carbon::parse($exception->custom_start_time);
                $doctorEnd = Carbon::parse($exception->custom_end_time);
            }

            // Intersect with clinic schedule if branch is specified
            if ($branchId) {
                $clinicSchedule = ClinicSchedule::where('clinic_branch_id', $branchId)
                    ->where('weekday', $weekday)
                    ->where('is_active', true)
                    ->first();

                if ($clinicSchedule) {
                    $clinicStart = Carbon::parse($clinicSchedule->start_time);
                    $clinicEnd = Carbon::parse($clinicSchedule->end_time);

                    $effectiveStart = $doctorStart->max($clinicStart);
                    $effectiveEnd = $doctorEnd->min($clinicEnd);

                    if ($effectiveStart->lt($effectiveEnd)) {
                        $doctorStart = $effectiveStart;
                        $doctorEnd = $effectiveEnd;
                    } else {
                        continue; // No intersection, skip this schedule
                    }
                }
            }

            // Generate time slots for this schedule
            $current = $doctorStart->copy();
            while ($current->lt($doctorEnd)) {
                $timeString = $current->format('H:i');

                $isAvailable = $this->isSlotAvailable($doctorId, $dateCarbon, $current, $schedule->max_per_slot);

                $allSlots[] = [
                    'time' => $timeString,
                    'available' => $isAvailable,
                    'branch_id' => $schedule->clinic_branch_id,
                ];

                $current->addMinutes($schedule->appointment_duration);
            }
        }

        // Sort slots by time
        usort($allSlots, fn($a, $b) => strcmp($a['time'], $b['time']));

        // Remove branch_id from output (internal use only) and dedupe by time
        $seenTimes = [];
        $slots = [];
        foreach ($allSlots as $slot) {
            if (!isset($seenTimes[$slot['time']])) {
                $seenTimes[$slot['time']] = true;
                unset($slot['branch_id']);
                $slots[] = $slot;
            }
        }

        // Determine overall schedule window
        $firstSchedule = $schedules->first();
        $scheduleStart = $exception && $exception->exception_type === ExceptionType::CUSTOM_HOURS
            ? $exception->custom_start_time
            : $firstSchedule->start_time;
        $scheduleEnd = $exception && $exception->exception_type === ExceptionType::CUSTOM_HOURS
            ? $exception->custom_end_time
            : $firstSchedule->end_time;

        return [
            'is_available' => true,
            'exception' => null,
            'schedule' => [
                'start_time' => $scheduleStart,
                'end_time' => $scheduleEnd,
                'appointment_duration' => $firstSchedule->appointment_duration,
                'max_per_slot' => $firstSchedule->max_per_slot,
            ],
            'slots' => $slots,
        ];
    }

    /**
     * Check if a specific slot is available.
     */
    private function isSlotAvailable(int $doctorId, Carbon $date, Carbon $time, int $maxPerSlot): bool
    {
        $existingCount = Appointment::where('user_id', $doctorId)
            ->where('date', $date->toDateString())
            ->where('slot_time', $time->format('H:i:s'))
            ->whereNotIn('status', ['cancelled', 'no_show'])
            ->count();

        return $existingCount < $maxPerSlot;
    }
}
