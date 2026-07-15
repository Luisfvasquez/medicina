<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Country;
use Illuminate\Http\JsonResponse;

class LocationController extends Controller
{
    public function countries(): JsonResponse
    {
        $countries = Country::orderBy('name')->get();

        return response()->json([
            'data' => $countries->map(function ($country) {
                return [
                    'id' => $country->uuid,
                    'name' => $country->name,
                    'code' => $country->code,
                ];
            })
        ]);
    }

    public function countryCities(string $countryUuid): JsonResponse
    {
        $country = Country::where('uuid', $countryUuid)->firstOrFail();
        
        $cities = City::whereHas('state', function ($query) use ($country) {
            $query->where('country_id', $country->id);
        })->with('state')->orderBy('name')->get();

        return response()->json([
            'data' => $cities->map(function ($city) {
                return [
                    'id' => $city->uuid,
                    'name' => $city->name,
                    'state' => [
                        'id' => $city->state->uuid,
                        'name' => $city->state->name,
                    ]
                ];
            })
        ]);
    }

    public function cities(): JsonResponse
    {
        $cities = City::with('state.country')->get();

        return response()->json([
            'data' => $cities->map(function ($city) {
                return [
                    'id' => $city->uuid,
                    'name' => $city->name,
                    'state' => [
                        'id' => $city->state->uuid,
                        'name' => $city->state->name,
                    ],
                    'country' => [
                        'id' => $city->state->country->uuid,
                        'name' => $city->state->country->name,
                        'code' => $city->state->country->code,
                    ]
                ];
            })
        ]);
    }
}
