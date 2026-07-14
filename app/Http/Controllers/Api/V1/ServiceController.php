<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\ProviderService;
use App\Models\User;
use App\Models\ClinicBranch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ServiceController extends Controller
{
    public function globalIndex(): JsonResponse
    {
        $services = Service::all();

        return response()->json([
            'data' => $services->map(function ($s) {
                return [
                    'uuid' => $s->uuid,
                    'name' => $s->name,
                    'category' => $s->category,
                    'description' => $s->description,
                    'basePrice' => (float) $s->base_price,
                    'code' => $s->code,
                ];
            })
        ]);
    }

    public function providerIndex(string $providerUuid): JsonResponse
    {
        $provider = User::where('uuid', $providerUuid)->first()
            ?? ClinicBranch::where('uuid', $providerUuid)->first();

        if (!$provider) {
            return response()->json(['message' => 'Provider not found'], 404);
        }

        $services = ProviderService::where('provider_id', $provider->id)
            ->where('provider_type', get_class($provider))
            ->with('service')
            ->get();

        return response()->json([
            'data' => $services->map(function ($ps) {
                return [
                    'uuid' => $ps->uuid,
                    'serviceUuid' => $ps->service->uuid ?? null,
                    'providerUuid' => $ps->provider->uuid ?? null,
                    'providerType' => get_class($ps->provider) === User::class ? 'DOCTOR' : 'CLINIC',
                    'price' => (float) $ps->price,
                    'durationMinutes' => (int) $ps->duration_minutes,
                    'isStandaloneBookable' => (bool) $ps->is_standalone_bookable,
                    'isActive' => (bool) $ps->is_active,
                    'customName' => $ps->custom_name,
                    'customDescription' => $ps->custom_description,
                ];
            })
        ]);
    }

    public function storeProviderService(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'uuid' => 'nullable|uuid',
            'serviceUuid' => 'required|exists:services,uuid',
            'providerUuid' => 'required',
            'providerType' => 'required|in:DOCTOR,CLINIC',
            'price' => 'required|numeric|min:0',
            'durationMinutes' => 'required|integer|min:1',
            'isStandaloneBookable' => 'required|boolean',
            'isActive' => 'required|boolean',
            'customName' => 'nullable|string|max:255',
            'customDescription' => 'nullable|string',
        ]);

        $service = Service::where('uuid', $validated['serviceUuid'])->firstOrFail();

        $provider = null;
        if ($validated['providerType'] === 'DOCTOR') {
            $provider = User::where('uuid', $validated['providerUuid'])->firstOrFail();
        } else {
            $provider = ClinicBranch::where('uuid', $validated['providerUuid'])->firstOrFail();
        }

        $providerService = ProviderService::create([
            'uuid' => $validated['uuid'] ?? (string) Str::uuid(),
            'service_id' => $service->id,
            'provider_id' => $provider->id,
            'provider_type' => get_class($provider),
            'price' => $validated['price'],
            'duration_minutes' => $validated['durationMinutes'],
            'is_standalone_bookable' => $validated['isStandaloneBookable'],
            'is_active' => $validated['isActive'],
            'custom_name' => $validated['customName'] ?? null,
            'custom_description' => $validated['customDescription'] ?? null,
        ]);

        return response()->json([
            'message' => 'Provider service created successfully',
            'data' => [
                'uuid' => $providerService->uuid,
                'serviceUuid' => $service->uuid,
                'providerUuid' => $provider->uuid,
                'providerType' => $validated['providerType'],
                'price' => (float) $providerService->price,
                'durationMinutes' => (int) $providerService->duration_minutes,
                'isStandaloneBookable' => (bool) $providerService->is_standalone_bookable,
                'isActive' => (bool) $providerService->is_active,
                'customName' => $providerService->custom_name,
                'customDescription' => $providerService->custom_description,
            ]
        ], 201);
    }

    public function updateProviderService(Request $request, string $uuid): JsonResponse
    {
        $providerService = ProviderService::where('uuid', $uuid)->firstOrFail();

        $validated = $request->validate([
            'price' => 'sometimes|required|numeric|min:0',
            'durationMinutes' => 'sometimes|required|integer|min:1',
            'isStandaloneBookable' => 'sometimes|required|boolean',
            'isActive' => 'sometimes|required|boolean',
            'customName' => 'nullable|string|max:255',
            'customDescription' => 'nullable|string',
        ]);

        $providerService->update([
            'price' => $validated['price'] ?? $providerService->price,
            'duration_minutes' => $validated['durationMinutes'] ?? $providerService->duration_minutes,
            'is_standalone_bookable' => $validated['isStandaloneBookable'] ?? $providerService->is_standalone_bookable,
            'is_active' => $validated['isActive'] ?? $providerService->is_active,
            'custom_name' => $validated['customName'] ?? $providerService->custom_name,
            'custom_description' => $validated['customDescription'] ?? $providerService->custom_description,
        ]);

        return response()->json([
            'message' => 'Provider service updated successfully',
            'data' => [
                'uuid' => $providerService->uuid,
                'serviceUuid' => $providerService->service->uuid ?? null,
                'providerUuid' => $providerService->provider->uuid ?? null,
                'providerType' => get_class($providerService->provider) === User::class ? 'DOCTOR' : 'CLINIC',
                'price' => (float) $providerService->price,
                'durationMinutes' => (int) $providerService->duration_minutes,
                'isStandaloneBookable' => (bool) $providerService->is_standalone_bookable,
                'isActive' => (bool) $providerService->is_active,
                'customName' => $providerService->custom_name,
                'customDescription' => $providerService->custom_description,
            ]
        ]);
    }

    public function destroyProviderService(string $uuid): JsonResponse
    {
        $providerService = ProviderService::where('uuid', $uuid)->firstOrFail();
        $providerService->delete();

        return response()->json([
            'message' => 'Provider service deleted successfully',
            'uuid' => $uuid
        ]);
    }
}
