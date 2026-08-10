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
        $providerProfile = $request->user()->providerProfile;
        if (!$providerProfile) {
            return response()->json(['error' => 'Usuario no es proveedor.'], 403);
        }

        $requests = QuoteRequest::forPharmacyLocation(
                $providerProfile->latitude, 
                $providerProfile->longitude, 
                $providerProfile->city_id
            )
            ->with([
                'prescription.items.medication', 
                'prescription.user', 
                'patient',
                'offers' => function ($query) use ($providerProfile) {
                    $query->where('provider_id', $providerProfile->id)->with('quoteOfferItems.inventory.medication', 'quoteOfferItems.substitutedInventory.medication');
                }
            ])
            ->when($request->query('status'), function ($query, $status) {
                return $query->where('status', $status);
            })
            ->when($request->query('start_date'), function ($query, $startDate) {
                return $query->whereDate('created_at', '>=', $startDate);
            })
            ->when($request->query('end_date'), function ($query, $endDate) {
                return $query->whereDate('created_at', '<=', $endDate);
            })
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        // Transformar para proteger privacidad del paciente
        $requests->getCollection()->transform(function ($quoteRequest) {
            $data = $quoteRequest->toArray();
            if (isset($data['patient'])) {
                unset($data['patient']['address']);
                unset($data['patient']['phone']);
                unset($data['patient']['email']);
                unset($data['patient']['national_id']);
                unset($data['patient']['emergency_contact_name']);
                unset($data['patient']['emergency_contact_phone']);
            }
            return $data;
        });

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

        \App\Models\AuditLog::logCreate(
            $request->user(),
            'QuoteOffer',
            $offer->id,
            $offer->toArray()
        );

        return response()->json([
            'message' => 'Cotización registrada exitosamente.',
            'data' => $offer
        ], 201);
    }

    public function updateOffer(Request $request, $requestId, $offerId)
    {
        $providerId = $request->user()->providerProfile?->id;
        if (!$providerId) {
            return response()->json(['error' => 'Usuario no es proveedor.'], 403);
        }

        $offer = \App\Models\QuoteOffer::where('id', $offerId)
            ->where('quote_request_id', $requestId)
            ->where('provider_id', $providerId)
            ->first();

        if (!$offer) {
            return response()->json(['error' => 'Oferta no encontrada o no pertenece a este proveedor.'], 404);
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

        $oldData = $offer->toArray();
        $updatedOffer = $this->quoteService->updateQuoteOffer($offer, $validated);

        \App\Models\AuditLog::logUpdate(
            $request->user(),
            'QuoteOffer',
            $updatedOffer->id,
            $oldData,
            $updatedOffer->toArray()
        );

        return response()->json([
            'message' => 'Cotización actualizada exitosamente.',
            'data' => $updatedOffer
        ], 200);
    }

    public function upsellSuggestions(Request $request)
    {
        $providerId = $request->user()->providerProfile?->id;
        $activeIngredients = $request->input('active_ingredients', []);

        $suggestions = $this->upsellService->getRecommendationsForPrescription($providerId, (array) $activeIngredients);
        return response()->json(['data' => $suggestions]);
    }
}
