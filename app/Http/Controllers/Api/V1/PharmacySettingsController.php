<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PharmacySetting;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PharmacySettingsController extends Controller
{
    public function show(Request $request)
    {
        $providerId = $request->user()->providerProfile?->id;
        if (!$providerId) {
            return response()->json(['error' => 'Usuario no es un proveedor de farmacia.'], 403);
        }

        $settings = PharmacySetting::firstOrCreate(
            ['provider_id' => $providerId],
            [
                'uuid' => (string) Str::uuid(),
                'auto_quoting_enabled' => false,
                'allow_partial_quotes' => true,
                'default_currency' => 'USD',
            ]
        );

        return response()->json(['data' => $settings]);
    }

    public function update(Request $request)
    {
        $providerId = $request->user()->providerProfile?->id;
        if (!$providerId) {
            return response()->json(['error' => 'Usuario no es un proveedor de farmacia.'], 403);
        }

        $validated = $request->validate([
            'auto_quoting_enabled' => 'boolean',
            'allow_partial_quotes' => 'boolean',
            'default_currency' => 'string|max:5',
            'custom_terms' => 'nullable|string',
        ]);

        $settings = PharmacySetting::updateOrCreate(
            ['provider_id' => $providerId],
            $validated
        );

        return response()->json(['message' => 'Configuración de farmacia actualizada correctamente.', 'data' => $settings]);
    }
}
