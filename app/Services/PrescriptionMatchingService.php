<?php

namespace App\Services;

use App\Models\Prescription;
use App\Models\ProviderProfile;
use App\Models\PharmacyInventory;
use App\Models\QuoteOffer;
use App\Models\QuoteOfferItem;
use App\Models\QuoteRequest;
use App\Models\PharmacySetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PrescriptionMatchingService
{
    /**
     * Executes the matching logic to find pharmacies that have the prescribed medications.
     */
    public function processMatch(Prescription $prescription)
    {
        $prescription->load(['items.medication', 'patient', 'clinicBranch']);

        if ($prescription->items->isEmpty()) {
            return false;
        }

        $patient = $prescription->patient;
        $clinic = $prescription->clinicBranch;

        // 1. Gather all requested medications
        $requestedMedicationIds = [];
        $requestedActiveIngredients = []; // For fallback
        
        foreach ($prescription->items as $item) {
            if ($item->medication_id) {
                $requestedMedicationIds[] = $item->medication_id;
            } elseif ($item->notes) {
                // Assuming 'notes' or a generic field stores free text when medication_id is null
                // We'll normalize it to lowercase for fuzzy matching
                $requestedActiveIngredients[] = strtolower(trim($item->notes));
            }
        }

        // 2. Find eligible pharmacies (Providers) that have auto-quoting enabled
        $eligiblePharmacies = PharmacySetting::where('auto_quoting_enabled', true)
            ->with(['providerProfile.branches'])
            ->get();

        foreach ($eligiblePharmacies as $setting) {
            $provider = $setting->providerProfile;
            if (!$provider) continue;

            // Location Check: 
            // Is it in the patient's city OR near the clinic?
            $isNearPatient = $provider->city_id === $patient->city_id;
            
            $isNearClinic = false;
            if ($clinic && $clinic->city_id === $provider->city_id) {
                $isNearClinic = true;
            }
            // Add Haversine distance logic here if branches have lat/lng and clinic has lat/lng
            // For now, simple city_id check is used as base

            if (!$isNearPatient && !$isNearClinic) {
                continue; // Skip if neither near patient nor clinic
            }

            // 3. Check Inventory
            $inventoryMatches = [];
            $totalItemsMatched = 0;
            $cartTotalUSD = 0;
            $cartTotalVES = 0;

            // Fetch provider inventory that matches either exact ID or active_ingredient fallback
            $inventory = PharmacyInventory::where('provider_id', $provider->id)
                ->where('stock', '>', 0)
                ->get();

            foreach ($prescription->items as $pItem) {
                $matchedInv = null;

                if ($pItem->medication_id) {
                    $matchedInv = $inventory->firstWhere('medication_id', $pItem->medication_id);
                } else {
                    $freeText = strtolower(trim($pItem->notes ?? ''));
                    if ($freeText) {
                        $matchedInv = $inventory->first(function ($inv) use ($freeText) {
                            return strtolower(trim($inv->active_ingredient)) === $freeText;
                        });
                    }
                }

                if ($matchedInv) {
                    // Check if they have enough stock (packages or fractions)
                    $requiredQty = $pItem->quantity ?? 1;
                    if ($matchedInv->stock >= $requiredQty || $matchedInv->fraction_stock >= $requiredQty || $matchedInv->package_stock >= $requiredQty) {
                        $inventoryMatches[] = [
                            'prescription_item_id' => $pItem->id,
                            'inventory_item' => $matchedInv,
                            'qty' => $requiredQty
                        ];
                        $totalItemsMatched++;

                        // Calculate price using prices_manual
                        $prices = is_string($matchedInv->prices_manual) ? json_decode($matchedInv->prices_manual, true) : $matchedInv->prices_manual;
                        $usdPrice = $prices['USD'] ?? ($matchedInv->unit_price ?? 0);
                        $vesPrice = $prices['VES'] ?? 0;
                        
                        $cartTotalUSD += ($usdPrice * $requiredQty);
                        $cartTotalVES += ($vesPrice * $requiredQty);
                    }
                }
            }

            // 4. Determine if we should generate an offer (Partial matching allowed based on setting or business rule)
            // User requested: "No importan, ejemplo si tienen 1 de 3 aun asi deberian listar a la farmacia"
            if ($totalItemsMatched > 0) {
                $this->createQuoteOffer($prescription, $provider, $inventoryMatches, $cartTotalUSD, $isNearClinic, $isNearPatient);
            } else {
                Log::info("Pharmacy {$provider->id} has auto-matching enabled but NO inventory for Prescription {$prescription->id}. Needs notification.");
                
                // 6. Notify pharmacies that had auto-quoting on but no matching inventory
                \App\Models\Notification::create([
                    'user_id' => $provider->user_id,
                    'type' => \App\Enums\NotifType::MISSED_SALE_OPPORTUNITY,
                    'title' => 'Oportunidad de Venta Perdida',
                    'message' => 'Un paciente cercano está buscando medicamentos que no tienes en stock, pero tienes el Auto-Matching activado. ¡Actualiza tu inventario!',
                    'link' => '/dashboard/pharmacy/inventory',
                ]);
            }
        }

        return true;
    }

    private function createQuoteOffer($prescription, $provider, $matches, $cartTotalUSD, $isNearClinic, $isNearPatient)
    {
        DB::transaction(function () use ($prescription, $provider, $matches, $cartTotalUSD, $isNearClinic, $isNearPatient) {
            
            // Generate a QuoteRequest for this prescription if it doesn't exist
            $quoteRequest = QuoteRequest::firstOrCreate(
                ['prescription_id' => $prescription->id],
                [
                    'patient_id' => $prescription->patient_id,
                    'city_id' => $prescription->patient->city_id,
                    'status' => 'pending',
                ]
            );

            // Add proximity flag to comments or a new field
            $proximityNotes = [];
            if ($isNearClinic) $proximityNotes[] = "Cercana a la clínica";
            if ($isNearPatient) $proximityNotes[] = "Cercana al paciente";
            
            $offer = QuoteOffer::create([
                'quote_request_id' => $quoteRequest->id,
                'provider_id' => $provider->id,
                'price' => $cartTotalUSD,
                'currency' => 'USD',
                'availability' => 'partial', // We can calculate 'full' if count matches == prescription->items->count()
                'comments' => implode(" | ", $proximityNotes),
            ]);

            foreach ($matches as $match) {
                $inv = $match['inventory_item'];
                $prices = is_string($inv->prices_manual) ? json_decode($inv->prices_manual, true) : $inv->prices_manual;
                $usdPrice = $prices['USD'] ?? ($inv->unit_price ?? 0);

                QuoteOfferItem::create([
                    'quote_offer_id' => $offer->id,
                    'prescription_item_id' => $match['prescription_item_id'],
                    'pharmacy_inventory_id' => $inv->id,
                    'quantity' => $match['qty'],
                    'prices_manual' => is_string($inv->prices_manual) ? json_decode($inv->prices_manual, true) : $inv->prices_manual,
                    'is_substituted' => false,
                ]);
            }
        });
    }
}
