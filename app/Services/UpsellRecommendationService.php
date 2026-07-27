<?php

namespace App\Services;

use App\Models\UpsellRule;
use App\Models\PharmacyInventory;

class UpsellRecommendationService
{
    /**
     * Obtener sugerencias de venta libre (OTC) basadas en los principios activos de la receta
     */
    public function getRecommendationsForPrescription(int $providerId, array $activeIngredients)
    {
        if (empty($activeIngredients)) {
            return collect();
        }

        return UpsellRule::where('provider_id', $providerId)
            ->where('is_active', true)
            ->whereIn('trigger_active_ingredient', $activeIngredients)
            ->with(['recommendedInventory' => function ($query) {
                $query->with('medication');
            }])
            ->get();
    }
}
