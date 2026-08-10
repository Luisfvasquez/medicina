<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PharmacyInventory;
use App\Services\PharmacyInventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PharmacyInventoryController extends Controller
{
    protected PharmacyInventoryService $inventoryService;

    public function __construct(PharmacyInventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    public function index(Request $request)
    {
        $providerId = $request->user()->providerProfile?->id;
        if (!$providerId) {
            return response()->json(['error' => 'Usuario no tiene perfil de proveedor.'], 403);
        }

        $filters = $request->only(['search', 'sale_condition', 'low_stock', 'expiring_days', 'per_page']);
        $inventory = $this->inventoryService->getInventoryList($providerId, $filters);

        return response()->json($inventory);
    }

    public function store(Request $request)
    {
        $providerId = $request->user()->providerProfile?->id;
        if (!$providerId) {
            return response()->json(['error' => 'Usuario no tiene perfil de proveedor.'], 403);
        }

        $validated = $request->validate([
            'medication_id' => 'nullable|exists:medications,id',
            'ean_code' => 'nullable|string|max:50',
            'active_ingredient' => 'nullable|string|max:255',
            'laboratory' => 'nullable|string|max:255',
            'sale_condition' => 'required|in:free,prescription,controlled',
            'stock' => 'integer|min:0',
            'min_stock_alert' => 'integer|min:0',
            'batch_number' => 'nullable|string|max:100',
            'expiration_date' => 'nullable|date',
            'location_rack' => 'nullable|string|max:100',
            'allows_fractioning' => 'boolean',
            'units_per_package' => 'integer|min:1',
            'fraction_unit_name' => 'string|max:50',
            'package_stock' => 'integer|min:0',
            'fraction_stock' => 'integer|min:0',
            'unit_price' => 'nullable|numeric|min:0',
            'prices_manual' => 'nullable|array',
        ]);

        $validated['provider_id'] = $providerId;
        $validated['uuid'] = (string) \Illuminate\Support\Str::uuid();

        if (empty($validated['medication_id'])) {
            $validated['medication_id'] = null;
            // Notify admins about the new medication request
            $admins = \App\Models\User::where('role', \App\Enums\UserRole::ADMIN)->get();
            foreach ($admins as $admin) {
                \App\Models\Notification::create([
                    'user_id' => $admin->id,
                    'type' => \App\Enums\NotifType::NEW_MEDICATION_REQUEST,
                    'title' => 'Nuevo Medicamento Detectado',
                    'message' => 'Una farmacia ha cargado un medicamento no catalogado: ' . ($validated['active_ingredient'] ?? 'Desconocido') . ' - ' . ($validated['laboratory'] ?? 'Desconocido'),
                ]);
            }
        }

        $item = PharmacyInventory::create($validated);

        \App\Models\AuditLog::logCreate(
            $request->user(),
            'PharmacyInventory',
            $item->id,
            $item->toArray()
        );

        return response()->json(['message' => 'Producto registrado en inventario con éxito.', 'data' => $item->load('medication')], 201);
    }

    public function update(Request $request, $id)
    {
        $providerId = $request->user()->providerProfile?->id;
        $item = PharmacyInventory::where('provider_id', $providerId)->findOrFail($id);

        $validated = $request->validate([
            'ean_code' => 'nullable|string|max:50',
            'active_ingredient' => 'nullable|string|max:255',
            'laboratory' => 'nullable|string|max:255',
            'sale_condition' => 'in:free,prescription,controlled',
            'stock' => 'integer|min:0',
            'min_stock_alert' => 'integer|min:0',
            'batch_number' => 'nullable|string|max:100',
            'expiration_date' => 'nullable|date',
            'location_rack' => 'nullable|string|max:100',
            'allows_fractioning' => 'boolean',
            'units_per_package' => 'integer|min:1',
            'fraction_unit_name' => 'string|max:50',
            'package_stock' => 'integer|min:0',
            'fraction_stock' => 'integer|min:0',
            'unit_price' => 'nullable|numeric|min:0',
            'prices_manual' => 'nullable|array',
        ]);

        $oldData = $item->toArray();
        $item->update($validated);

        \App\Models\AuditLog::logUpdate(
            $request->user(),
            'PharmacyInventory',
            $item->id,
            $oldData,
            $item->fresh()->toArray()
        );

        return response()->json(['message' => 'Producto de inventario actualizado.', 'data' => $item]);
    }

    public function expirationsReport(Request $request)
    {
        $providerId = $request->user()->providerProfile?->id;
        $days = (int) $request->get('days', 60);

        $report = $this->inventoryService->getExpirationReport($providerId, $days);
        return response()->json(['data' => $report]);
    }

    public function controlledBookReport(Request $request)
    {
        $providerId = $request->user()->providerProfile?->id;

        $report = $this->inventoryService->getControlledBookReport($providerId);
        return response()->json(['data' => $report]);
    }
}
