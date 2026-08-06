<?php

namespace App\Services;

use App\Models\QuoteOffer;
use App\Models\PharmacyOrder;
use App\Models\PharmacyOrderItem;
use App\Models\PatientAccount;
use Illuminate\Support\Facades\DB;

class PatientCheckoutService
{
    /**
     * Crea una PharmacyOrder a partir de un QuoteOffer para un paciente.
     * Marca la orden como 'pendiente' o estado inicial (Reserva).
     */
    public function createOrderFromOffer(PatientAccount $patientAccount, int $offerId, string $currency): PharmacyOrder
    {
        return DB::transaction(function () use ($patientAccount, $offerId, $currency) {
            $offer = QuoteOffer::with('quoteOfferItems')->findOrFail($offerId);

            // Verificar que el quote request pertenezca a un paciente de este patient_account
            $quoteRequest = $offer->quoteRequest;
            $belongsToPatient = $quoteRequest->patient->patient_account_id === $patientAccount->id;
            
            if (!$belongsToPatient) {
                throw new \Exception('Esta oferta no te pertenece.');
            }

            // Crear la PharmacyOrder
            $order = PharmacyOrder::create([
                'quote_offer_id' => $offer->id,
                'provider_id' => $offer->provider_id,
                'patient_account_id' => $patientAccount->id,
                'status' => 'pending', // Estado de reserva inicial
                'selected_currency_payment' => ['currency' => $currency],
                'stock_deducted' => false,
            ]);

            // Crear los ítems de la orden basados en la oferta
            foreach ($offer->quoteOfferItems as $item) {
                PharmacyOrderItem::create([
                    'pharmacy_order_id' => $order->id,
                    'pharmacy_inventory_id' => $item->is_substituted ? $item->substituted_inventory_id : $item->pharmacy_inventory_id,
                    'product_name' => $item->custom_product_name ?? 'Producto de farmacia',
                    'sell_format' => $item->sell_format,
                    'quantity' => $item->quantity,
                    'unit_prices_manual' => $item->prices_manual,
                ]);
            }

            // Opcional: Actualizar el estado del QuoteRequest a COMPLETED o similar
            if ($quoteRequest->status === 'OPEN') {
                $quoteRequest->update(['status' => 'COMPLETED']);
            }

            return $order->load('items');
        });
    }
}
