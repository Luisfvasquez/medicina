<?php

namespace App\Http\Controllers\Api\V1\Phase4;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\PharmacyInventory\StorePharmacyInventoryRequest;
use App\Http\Requests\Api\V1\PharmacyInventory\UpdatePharmacyInventoryRequest;
use App\Models\PharmacyInventory;
use Illuminate\Http\JsonResponse;

class PharmacyInventoryController extends Controller
{
    public function index(): JsonResponse
    {
        $user = auth('user_api')->user();

        // Providers see their own inventory, admins see all
        $query = PharmacyInventory::with(['provider', 'medication'])
            ->when($user->role === 'PROVIDER', fn($q) => $q->whereHas('provider', fn($p) => $p->where('user_id', $user->id)));

        // Filter options
        if (request('low_stock')) {
            $query->whereColumn('stock', '<=', 'min_stock_alert');
        }

        if (request('expired')) {
            $query->whereNotNull('expiration_date')
                ->where('expiration_date', '<', now()->toDateString());
        }

        $inventories = $query->latest()->paginate(20);

        return response()->json(['data' => $inventories]);
    }

    public function show(string $id): JsonResponse
    {
        $inventory = PharmacyInventory::with(['provider', 'medication'])->findOrFail($id);

        $user = auth('user_api')->user();
        if ($user->role === 'PROVIDER' && $inventory->provider->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json(['data' => $inventory]);
    }

    public function store(StorePharmacyInventoryRequest $request): JsonResponse
    {
        $inventory = PharmacyInventory::create($request->validated());

        return response()->json(['data' => $inventory->load(['provider', 'medication'])], 201);
    }

    public function update(UpdatePharmacyInventoryRequest $request, string $id): JsonResponse
    {
        $inventory = PharmacyInventory::findOrFail($id);

        $inventory->update($request->validated());

        return response()->json(['data' => $inventory->load(['provider', 'medication'])]);
    }

    public function destroy(string $id): JsonResponse
    {
        $inventory = PharmacyInventory::findOrFail($id);

        $user = auth('user_api')->user();
        if ($user->role === 'PROVIDER' && $inventory->provider->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $inventory->delete();

        return response()->json(null, 204);
    }

    public function lowStockAlerts(): JsonResponse
    {
        $alerts = PharmacyInventory::with(['provider', 'medication'])
            ->whereColumn('stock', '<=', 'min_stock_alert')
            ->where('stock', '>', 0)
            ->latest()
            ->paginate(20);

        return response()->json(['data' => $alerts]);
    }

    public function expired(): JsonResponse
    {
        $expired = PharmacyInventory::with(['provider', 'medication'])
            ->whereNotNull('expiration_date')
            ->where('expiration_date', '<', now()->toDateString())
            ->latest()
            ->paginate(20);

        return response()->json(['data' => $expired]);
    }
}
