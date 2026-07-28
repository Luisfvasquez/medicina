<?php

namespace App\Http\Controllers\MedicalSupply;

use App\Http\Controllers\Controller;
use App\Models\MedicalSupplyInventory;
use App\Models\MedicalSupplyStaff;
use Illuminate\Http\Request;

class SupplyInventoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $staff = MedicalSupplyStaff::where('user_id', $request->user()->id)->firstOrFail();

        $items = MedicalSupplyInventory::where('provider_profile_id', $staff->provider_profile_id)
            ->when($request->query('active_only'), function($query) {
                $query->where('is_active', true);
            })
            ->get();

        return response()->json($items);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $staff = MedicalSupplyStaff::where('user_id', $request->user()->id)->firstOrFail();

        $validated = $request->validate([
            'item_name' => 'required|string',
            'sku' => 'nullable|string',
            'price_usd' => 'required|numeric|min:0',
            'price_bs' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $item = MedicalSupplyInventory::create(array_merge($validated, [
            'provider_profile_id' => $staff->provider_profile_id,
        ]));

        return response()->json($item, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        $staff = MedicalSupplyStaff::where('user_id', $request->user()->id)->firstOrFail();
        $item = MedicalSupplyInventory::where('provider_profile_id', $staff->provider_profile_id)->findOrFail($id);

        return response()->json($item);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $staff = MedicalSupplyStaff::where('user_id', $request->user()->id)->firstOrFail();
        $item = MedicalSupplyInventory::where('provider_profile_id', $staff->provider_profile_id)->findOrFail($id);

        $validated = $request->validate([
            'item_name' => 'string',
            'sku' => 'nullable|string',
            'price_usd' => 'numeric|min:0',
            'price_bs' => 'numeric|min:0',
            'stock' => 'integer|min:0',
            'is_active' => 'boolean',
        ]);

        $item->update($validated);

        return response()->json($item);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {
        $staff = MedicalSupplyStaff::where('user_id', $request->user()->id)->firstOrFail();
        
        if ($staff->role->value !== 'MANAGER') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $item = MedicalSupplyInventory::where('provider_profile_id', $staff->provider_profile_id)->findOrFail($id);
        $item->delete();

        return response()->json(null, 204);
    }
}
