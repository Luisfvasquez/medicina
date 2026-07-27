<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\LabAppointment;
use App\Services\LabAppointmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LabAppointmentController extends Controller
{
    public function __construct(protected LabAppointmentService $appointmentService)
    {
    }

    public function book(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'lab_quote_offer_id' => 'required|integer|exists:lab_quote_offers,id',
            'scheduled_date' => 'required|date|after_or_equal:today',
            'time_slot' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        try {
            $appointment = $this->appointmentService->bookSlot(
                $validated['lab_quote_offer_id'],
                $validated['scheduled_date'],
                $validated['time_slot'] ?? null,
                $validated['notes'] ?? null
            );

            return response()->json([
                'message' => 'Cupo reservado con éxito para la fecha seleccionada.',
                'data' => $appointment,
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function index(Request $request): JsonResponse
    {
        $date = $request->input('date', now()->toDateString());
        $appointments = LabAppointment::with(['patient', 'labQuoteOffer'])
            ->where('scheduled_date', $date)
            ->orderBy('time_slot')
            ->get();

        return response()->json(['data' => $appointments]);
    }
}
