<?php

namespace App\Services;

use App\Models\LabAppointment;
use App\Models\LabQuoteOffer;
use App\Models\LabSetting;
use Illuminate\Support\Str;

class LabAppointmentService
{
    public function bookSlot(int $quoteOfferId, string $scheduledDate, ?string $timeSlot, ?string $notes = null): LabAppointment
    {
        $offer = LabQuoteOffer::with('labRequest')->findOrFail($quoteOfferId);
        $providerProfileId = $offer->provider_profile_id;

        // Check daily max slots
        $setting = LabSetting::where('provider_profile_id', $providerProfileId)->first();
        $maxSlots = $setting?->daily_max_slots ?? 20;

        $existingCount = LabAppointment::where('provider_profile_id', $providerProfileId)
            ->where('scheduled_date', $scheduledDate)
            ->whereNotIn('status', ['cancelled'])
            ->count();

        if ($existingCount >= $maxSlots) {
            throw new \Exception("Cupos agotados para la fecha seleccionada ({$scheduledDate}). Límite máximo: {$maxSlots}.");
        }

        return LabAppointment::create([
            'uuid' => (string) Str::uuid(),
            'lab_quote_offer_id' => $offer->id,
            'patient_id' => $offer->labRequest?->patient_id,
            'patient_account_id' => $offer->labRequest?->user_id,
            'provider_profile_id' => $providerProfileId,
            'scheduled_date' => $scheduledDate,
            'time_slot' => $timeSlot,
            'status' => 'reserved',
            'notes' => $notes,
        ]);
    }

    public function updateStatus(int $appointmentId, string $status): LabAppointment
    {
        $appointment = LabAppointment::findOrFail($appointmentId);
        $appointment->update(['status' => $status]);
        return $appointment;
    }
}
