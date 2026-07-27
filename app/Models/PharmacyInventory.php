<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasPublicUuid;

class PharmacyInventory extends Model
{
    use HasPublicUuid;

    protected $fillable = [
        'provider_id',
        'medication_id',
        'ean_code',
        'active_ingredient',
        'laboratory',
        'sale_condition',
        'stock',
        'min_stock_alert',
        'batch_number',
        'expiration_date',
        'location_rack',
        'allows_fractioning',
        'units_per_package',
        'fraction_unit_name',
        'package_stock',
        'fraction_stock',
        'unit_price',
        'prices_manual',
    ];

    protected function casts(): array
    {
        return [
            'stock' => 'integer',
            'min_stock_alert' => 'integer',
            'allows_fractioning' => 'boolean',
            'units_per_package' => 'integer',
            'package_stock' => 'integer',
            'fraction_stock' => 'integer',
            'expiration_date' => 'date',
            'unit_price' => 'decimal:2',
            'prices_manual' => 'array',
        ];
    }

    public function provider()
    {
        return $this->belongsTo(ProviderProfile::class, 'provider_id');
    }

    public function medication()
    {
        return $this->belongsTo(Medication::class);
    }

    public function isLowStock(): bool
    {
        return $this->package_stock <= $this->min_stock_alert;
    }

    public function isExpired(): bool
    {
        return $this->expiration_date && $this->expiration_date->isPast();
    }

    public function isControlled(): bool
    {
        return $this->sale_condition === 'controlled';
    }

    /**
     * Total de unidades individuales (empaques * unidades_por_empaque + fracciones)
     */
    public function getTotalIndividualUnitsAttribute(): int
    {
        return ($this->package_stock * $this->units_per_package) + $this->fraction_stock;
    }

    /**
     * Descuento de stock diferido en formato paquete o fraccionado
     */
    public function deductStock(int $quantity, string $format = 'package'): bool
    {
        if ($format === 'fraction') {
            $totalUnits = $this->total_individual_units;
            if ($totalUnits < $quantity) {
                return false;
            }
            $remaining = $totalUnits - $quantity;
            $this->package_stock = intdiv($remaining, $this->units_per_package > 0 ? $this->units_per_package : 1);
            $this->fraction_stock = $remaining % ($this->units_per_package > 0 ? $this->units_per_package : 1);
        } else {
            if ($this->package_stock < $quantity) {
                return false;
            }
            $this->package_stock -= $quantity;
        }

        $this->stock = $this->package_stock;
        return $this->save();
    }
}
