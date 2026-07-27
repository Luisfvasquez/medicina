<?php

namespace App\Services;

use App\Models\LabQuoteOffer;
use App\Models\LabRequest;
use Illuminate\Support\Str;

class LabQuoteService
{
    public function createQuoteOffer(int $requestId, int $providerProfileId, array $data): LabQuoteOffer
    {
        $request = LabRequest::findOrFail($requestId);

        return LabQuoteOffer::create([
            'uuid' => (string) Str::uuid(),
            'lab_request_id' => $request->id,
            'provider_profile_id' => $providerProfileId,
            'total_price_base' => $data['total_price_base'] ?? 0,
            'currency' => $data['currency'] ?? 'USD',
            'prices_manual' => $data['prices_manual'] ?? null,
            'items_detail' => $data['items_detail'] ?? [],
            'comments' => $data['comments'] ?? null,
            'status' => 'pending',
        ]);
    }

    public function acceptQuoteOffer(int $offerId): LabQuoteOffer
    {
        $offer = LabQuoteOffer::findOrFail($offerId);
        $offer->update(['status' => 'accepted']);

        if ($offer->labRequest) {
            $offer->labRequest->update(['is_completed' => true]);
        }

        return $offer;
    }
}
