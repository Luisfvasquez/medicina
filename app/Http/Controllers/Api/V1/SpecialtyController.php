<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Specialty;
use Illuminate\Http\JsonResponse;

class SpecialtyController extends Controller
{
    public function index(): JsonResponse
    {
        $specialties = Specialty::all();

        return response()->json([
            'data' => $specialties->map(function ($spec) {
                return [
                    'id' => $spec->id,
                    'name' => $spec->name,
                    'description' => $spec->description,
                ];
            })
        ]);
    }
}
