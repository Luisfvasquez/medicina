<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\QuoteRequest;
use App\Services\PharmacyQuoteService;
use App\Services\UpsellRecommendationService;
use Illuminate\Http\Request;

class PharmacyQuoteController extends Controller
{
    protected PharmacyQuoteService $quoteService;
    protected UpsellRecommendationService $upsellService;

    public function __construct(
        PharmacyQuoteService $quoteService,
        UpsellRecommendationService $upsellService
    ) {
        $this->quoteService = $quoteService;
        $this->upsellService = $upsellService;
    }

    public function indexRequests(Request $request)
    {
        $providerId = $request->user()->providerProfile?->id;
        if (!$providerId) {
            return response()->json(['error' => 'Usuario no es proveedor.'], 403);
        }

        $requests = QuoteRequest::with(['prescription.items.medication', 'patientAccount'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json($requests);
    }

    public function storeOffer(Request $request, $requestId)
    {
        $providerId = $request->user()->providerProfile?->id;
        if (!$providerId) {
            return response()->json(['error' => 'Usuario no es proveedor.'], 403);
        }

        $validated = $request->validate([
            'total_price_base' => 'required|numeric|min:0',
            'currency' => 'string|max:5',
            'availability' => 'string|nullable',
            'comments' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.prescription_item_id' => 'nullable|exists:prescription_items,id',
            'items.*.pharmacy_inventory_id' => 'nullable|exists:pharmacy_inventories,id',
            'items.*.custom_product_name' => 'nullable|string|max:255',
            'items.*.is_substituted' => 'boolean',
            'items.*.substituted_inventory_id' => 'nullable|exists:pharmacy_inventories,id',
            'items.*.substitution_reason' => 'nullable|string',
            'items.*.sell_format' => 'in:package,fraction',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.prices_manual' => 'nullable|array',
            'items.*.notes' => 'nullable|string',
        ]);

        $offer = $this->quoteService->createQuoteOffer($requestId, $providerId, $validated);

        return response()->json([
            'message' => 'Cotización registrada exitosamente.',
            'data' => $offer
        ], 201);
    }

    public function upsellSuggestions(Request $request)
    {
        $providerId = $request->user()->providerProfile?->id;
        $activeIngredients = $request->input('active_ingredients', []);

        $suggestions = $this->upsellService->getRecommendationsForPrescription($providerId, (array) $activeIngredients);
        return response()->json(['data' => $suggestions]);
    }
}
