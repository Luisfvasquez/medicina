<?php

namespace App\Http\Controllers\Api\V1\Phase3;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\QuoteOffer\StoreQuoteOfferRequest;
use App\Http\Requests\Api\V1\QuoteOffer\UpdateQuoteOfferRequest;
use App\Models\QuoteOffer;
use App\Models\QuoteRequest;
use Illuminate\Http\JsonResponse;

class QuoteOfferController extends Controller
{
    public function index(string $quoteRequestId): JsonResponse
    {
        QuoteRequest::findOrFail($quoteRequestId);

        $offers = QuoteOffer::with('providerProfile')
            ->where('quote_request_id', $quoteRequestId)
            ->latest()
            ->get();

        return response()->json(['data' => $offers]);
    }

    public function store(StoreQuoteOfferRequest $request, string $quoteRequestId): JsonResponse
    {
        QuoteRequest::findOrFail($quoteRequestId);

        $data = $request->validated();
        $data['quote_request_id'] = $quoteRequestId;

        $offer = QuoteOffer::create($data);

        return response()->json(['data' => $offer->load('providerProfile')], 201);
    }

    public function show(string $quoteRequestId, string $id): JsonResponse
    {
        $offer = QuoteOffer::with('providerProfile')
            ->where('quote_request_id', $quoteRequestId)
            ->findOrFail($id);

        return response()->json(['data' => $offer]);
    }

    public function update(UpdateQuoteOfferRequest $request, string $quoteRequestId, string $id): JsonResponse
    {
        $offer = QuoteOffer::where('quote_request_id', $quoteRequestId)->findOrFail($id);

        $user = auth('user_api')->user();
        if ($user->role === 'PROVIDER' && $offer->provider_id !== $user->providerProfile->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $offer->update($request->validated());

        return response()->json(['data' => $offer->load('providerProfile')]);
    }

    public function destroy(string $quoteRequestId, string $id): JsonResponse
    {
        $offer = QuoteOffer::where('quote_request_id', $quoteRequestId)->findOrFail($id);

        $user = auth('user_api')->user();
        if ($user->role !== 'ADMIN' && $offer->provider_id !== ($user->providerProfile->id ?? null)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $offer->delete();

        return response()->json(null, 204);
    }
}
