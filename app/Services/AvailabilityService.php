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
    public function validateAppointment(int $doctorId, string $date, string $time, ?int $excludeAppointmentId = null): bool
    {
        $dateCarbon = Carbon::parse($date);
        $timeCarbon = Carbon::parse($time);
        $weekday = Weekday::fromCarbon($dateCarbon);

        // 1. Check for schedule exceptions
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

        // 2. Get regular schedule for this weekday
        $schedule = DoctorSchedule::where('user_id', $doctorId)
            ->where('weekday', $weekday)
            ->where('is_active', true)
            ->first();

        if (!$schedule) {
            throw new AvailabilityException('El doctor no tiene horario definido para este día', self::NO_SCHEDULE_FOR_DAY);
        }

        // 3. Check time is within range
        $scheduleStart = Carbon::parse($schedule->start_time);
        $scheduleEnd = Carbon::parse($schedule->end_time);

        if ($timeCarbon->lt($scheduleStart) || $timeCarbon->gte($scheduleEnd)) {
            throw new AvailabilityException(
                "Horario fuera del horario de atención ({$schedule->start_time} - {$schedule->end_time})",
                self::OUTSIDE_SCHEDULE_HOURS
            );
        }

        // 4. Check slot capacity
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
     * If branchId is provided, filters by the clinic's schedule as well.
     */
    public function getAvailableSlots(int $doctorId, string $date, ?string $branchId = null): array
    {
        $dateCarbon = Carbon::parse($date);
        $weekday = Weekday::fromCarbon($dateCarbon);

        // Check for exception first
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

        // Get doctor's schedule
        $schedule = DoctorSchedule::where('user_id', $doctorId)
            ->where('weekday', $weekday)
            ->where('is_active', true)
            ->first();

        if (!$schedule) {
            return [
                'is_available' => false,
                'exception' => null,
                'slots' => [],
            ];
        }

        // Determine effective time range (doctor schedule + custom hours if exists)
        $doctorStart = Carbon::parse($schedule->start_time);
        $doctorEnd = Carbon::parse($schedule->end_time);

        if ($exception && $exception->exception_type === ExceptionType::CUSTOM_HOURS) {
            $doctorStart = Carbon::parse($exception->custom_start_time);
            $doctorEnd = Carbon::parse($exception->custom_end_time);
        }

        // If branch_id provided, intersect with clinic schedule
        $clinicStart = null;
        $clinicEnd = null;

        if ($branchId) {
            $clinicSchedule = ClinicSchedule::where('clinic_branch_id', $branchId)
                ->where('weekday', $weekday)
                ->where('is_active', true)
                ->first();

            if ($clinicSchedule) {
                $clinicStart = Carbon::parse($clinicSchedule->start_time);
                $clinicEnd = Carbon::parse($clinicSchedule->end_time);

                // Intersection: latest start time, earliest end time
                $effectiveStart = $doctorStart->max($clinicStart);
                $effectiveEnd = $doctorEnd->min($clinicEnd);

                // If no intersection, no availability
                if ($effectiveStart->gte($effectiveEnd)) {
                    return [
                        'is_available' => false,
                        'exception' => [
                            'type' => 'CLINIC_CLOSED',
                            'reason' => 'La clínica no está abierta en este horario',
                        ],
                        'slots' => [],
                    ];
                }

                $doctorStart = $effectiveStart;
                $doctorEnd = $effectiveEnd;
            }
        }

        // Generate time slots
        $slots = [];
        $current = $doctorStart->copy();
        $end = $doctorEnd;

        while ($current->lt($end)) {
            $timeString = $current->format('H:i');

            // Check availability for this slot
            $isAvailable = $this->isSlotAvailable($doctorId, $dateCarbon, $current, $schedule->max_per_slot);

            $slots[] = [
                'time' => $timeString,
                'available' => $isAvailable,
            ];

            $current->addMinutes($schedule->appointment_duration);
        }

        return [
            'is_available' => true,
            'exception' => null,
            'schedule' => [
                'start_time' => $doctorStart->format('H:i'),
                'end_time' => $doctorEnd->format('H:i'),
                'appointment_duration' => $schedule->appointment_duration,
                'max_per_slot' => $schedule->max_per_slot,
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
