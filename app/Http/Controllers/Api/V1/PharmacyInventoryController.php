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

    public function store(\App\Http\Requests\Api\V1\PharmacyInventory\StorePharmacyInventoryRequest $request)
    {
        $providerId = $request->user()->providerProfile?->id;
        if (!$providerId) {
            return response()->json(['error' => 'Usuario no tiene perfil de proveedor.'], 403);
        }

        $validated = $request->validated();

        $validated['provider_id'] = $providerId;
        $validated['uuid'] = (string) \Illuminate\Support\Str::uuid();

        $item = \Illuminate\Support\Facades\DB::transaction(function () use ($validated, $request) {
            if (empty($validated['medication_id'])) {
                $validated['medication_id'] = null;
                // Notify admins about the new medication request (bulk insert)
                $adminIds = \App\Models\User::where('role', \App\Enums\UserRole::ADMIN)->pluck('id');
                $now = now();
                $notifications = $adminIds->map(function ($adminId) use ($validated, $now) {
                    return [
                        'user_id' => $adminId,
                        'type' => \App\Enums\NotifType::NEW_MEDICATION_REQUEST,
                        'title' => 'Nuevo Medicamento Detectado',
                        'message' => 'Una farmacia ha cargado un medicamento no catalogado: ' . ($validated['active_ingredient'] ?? 'Desconocido') . ' - ' . ($validated['laboratory'] ?? 'Desconocido'),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                })->toArray();
                
                if (!empty($notifications)) {
                    \App\Models\Notification::insert($notifications);
                }
            }

            $item = PharmacyInventory::create($validated);

            return $item;
        });

        return response()->json(['message' => 'Producto registrado en inventario con éxito.', 'data' => $item->load('medication')], 201);
    }

    public function update(\App\Http\Requests\Api\V1\PharmacyInventory\UpdatePharmacyInventoryRequest $request, $id)
    {
        $providerId = $request->user()->providerProfile?->id;
        $item = PharmacyInventory::where('provider_id', $providerId)->findOrFail($id);

        $validated = $request->validated();

        $item->update($validated);

        return response()->json(['message' => 'Producto actualizado con éxito.', 'data' => $item->load('medication')]);
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
