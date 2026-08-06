<?php

namespace App\Http\Controllers\Api\V1\Phase5;

use App\Http\Controllers\Controller;
use App\Services\PatientCheckoutService;
use Illuminate\Http\Request;

class PatientCheckoutController extends Controller
{
    protected PatientCheckoutService $checkoutService;

    public function __construct(PatientCheckoutService $checkoutService)
    {
        $this->checkoutService = $checkoutService;
    }

    public function checkout(Request $request, $offerId)
    {
        $validated = $request->validate([
            'currency' => 'required|string|in:USD,VES,EUR',
        ]);

        $patientAccount = auth('patient_api')->user();

        try {
            $order = $this->checkoutService->createOrderFromOffer($patientAccount, $offerId, $validated['currency']);

            return response()->json([
                'message' => 'Reserva creada exitosamente',
                'data' => $order
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
