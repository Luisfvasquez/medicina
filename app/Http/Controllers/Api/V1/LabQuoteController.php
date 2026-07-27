<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\LabQuoteOffer;
use App\Models\LabRequest;
use App\Services\LabQuoteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LabQuoteController extends Controller
{
    public function __construct(protected LabQuoteService $quoteService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $query = LabRequest::with(['patient', 'user'])->where('is_completed', false);

        $requests = $query->orderByDesc('created_at')->paginate($request->input('per_page', 15));

        return response()->json($requests);
    }

    public function store(Request $request, int $requestId): JsonResponse
    {
        $validated = $request->validate([
            'provider_profile_id' => 'required|integer|exists:provider_profiles,id',
            'total_price_base' => 'required|numeric|min:0',
            'currency' => 'nullable|string|max:10',
            'prices_manual' => 'nullable|array',
            'items_detail' => 'nullable|array',
            'comments' => 'nullable|string',
        ]);

        $offer = $this->quoteService->createQuoteOffer($requestId, $validated['provider_profile_id'], $validated);

        return response()->json([
            'message' => 'Cotización de laboratorio creada exitosamente.',
            'data' => $offer,
        ], 201);
    }

    public function accept(int $offerId): JsonResponse
    {
        $offer = $this->quoteService->acceptQuoteOffer($offerId);

        return response()->json([
            'message' => 'Cotización aceptada por el paciente.',
            'data' => $offer,
        ]);
    }
}
