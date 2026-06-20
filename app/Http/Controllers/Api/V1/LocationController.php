<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\City;
use Illuminate\Http\JsonResponse;

class LocationController extends Controller
{
    public function cities(): JsonResponse
    {
        $cities = City::with('state.country')->get();

        return response()->json([
            'data' => $cities->map(function ($city) {
                return [
                    'id' => $city->id,
                    'name' => $city->name,
                    'state' => [
                        'id' => $city->state->id,
                        'name' => $city->state->name,
                    ],
                    'country' => [
                        'id' => $city->state->country->id,
                        'name' => $city->state->country->name,
                        'code' => $city->state->country->code,
                    ]
                ];
            })
        ]);
    }
}
