<?php

namespace App\Services;

use App\Models\PharmacyInventory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class PharmacyInventoryService
{
    /**
     * Obtener listado de inventario filtrado por proveedor y criterios
     */
    public function getInventoryList(int $providerId, array $filters = [])
    {
        $query = PharmacyInventory::where('provider_id', $providerId)
            ->with('medication');

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $q) use ($search) {
                $q->whereRaw("unaccent(active_ingredient) ilike unaccent(?)", ["%{$search}%"])
                  ->orWhereRaw("unaccent(ean_code) ilike unaccent(?)", ["%{$search}%"])
                  ->orWhereRaw("unaccent(laboratory) ilike unaccent(?)", ["%{$search}%"])
                  ->orWhereHas('medication', function (Builder $mq) use ($search) {
                      $mq->whereRaw("unaccent(active_principle) ilike unaccent(?)", ["%{$search}%"])
                         ->orWhereRaw("unaccent(commercial_name) ilike unaccent(?)", ["%{$search}%"]);
                  });
            });
        }

        if (!empty($filters['sale_condition'])) {
            $query->where('sale_condition', $filters['sale_condition']);
        }

        if (isset($filters['low_stock']) && $filters['low_stock']) {
            $query->whereColumn('package_stock', '<=', 'min_stock_alert');
        }

        if (isset($filters['expiring_days'])) {
            $days = (int) $filters['expiring_days'];
            $targetDate = Carbon::now()->addDays($days);
            $query->whereBetween('expiration_date', [Carbon::now(), $targetDate]);
        }

        return $query->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Reporte de Medicamentos Próximos a Vencer (30, 60, 90 días)
     */
    public function getExpirationReport(int $providerId, int $days = 60)
    {
        return PharmacyInventory::where('provider_id', $providerId)
            ->with('medication')
            ->whereNotNull('expiration_date')
            ->where('expiration_date', '<=', Carbon::now()->addDays($days))
            ->orderBy('expiration_date', 'asc')
            ->get();
    }

    /**
     * Reporte / Libro Digital de Psicotrópicos y Medicamentos Controlados
     */
    public function getControlledBookReport(int $providerId)
    {
        return PharmacyInventory::where('provider_id', $providerId)
            ->where('sale_condition', 'controlled')
            ->with('medication')
            ->orderBy('active_ingredient', 'asc')
            ->get();
    }
}
