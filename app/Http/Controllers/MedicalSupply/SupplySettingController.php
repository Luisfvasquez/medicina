<?php

namespace App\Http\Controllers\MedicalSupply;

use App\Http\Controllers\Controller;
use App\Models\MedicalSupplySetting;
use Illuminate\Http\Request;

class SupplySettingController extends Controller
{
    /**
     * Display the specified resource.
     */
    public function show(Request $request)
    {
        $staff = \App\Models\MedicalSupplyStaff::where('user_id', $request->user()->id)->firstOrFail();
        $profileId = $staff->provider_profile_id;

        $setting = MedicalSupplySetting::firstOrCreate(
            ['provider_profile_id' => $profileId]
        );

        return response()->json($setting);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'is_24_hours' => 'boolean',
            'working_days' => 'array',
            'opening_time' => 'date_format:H:i',
            'closing_time' => 'date_format:H:i',
            'auto_matching_enabled' => 'boolean',
        ]);

        $staff = \App\Models\MedicalSupplyStaff::where('user_id', $request->user()->id)->firstOrFail();
        
        if ($staff->role->value !== \App\Enums\MedicalSupplyRole::MANAGER->value) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $profileId = $staff->provider_profile_id;

        $setting = MedicalSupplySetting::updateOrCreate(
            ['provider_profile_id' => $profileId],
            $validated
        );

        return response()->json($setting);
    }
}
