<?php

namespace App\Http\Controllers\Api\V1\Phase3;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Medication\StoreMedicationRequest;
use App\Http\Requests\Api\V1\Medication\UpdateMedicationRequest;
use App\Models\Medication;
use Illuminate\Http\JsonResponse;

class MedicationController extends Controller
{
    public function index(): JsonResponse
    {
        $medications = Medication::with('user')
            ->whereNull('user_id')
            ->orWhere('user_id', auth('user_api')->id())
            ->latest()
            ->paginate(20);

        return response()->json(['data' => $medications]);
    }

    public function store(StoreMedicationRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['user_id'] = auth('user_api')->id();

        $medication = Medication::create($data);

        return response()->json(['data' => $medication->load('user')], 201);
    }

    public function show(string $id): JsonResponse
    {
        $medication = Medication::with('user')->findOrFail($id);

        if ($medication->user_id && $medication->user_id !== auth('user_api')->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json(['data' => $medication]);
    }

    public function update(UpdateMedicationRequest $request, string $id): JsonResponse
    {
        $medication = Medication::findOrFail($id);

        if ($medication->user_id !== auth('user_api')->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $medication->update($request->validated());

        return response()->json(['data' => $medication->load('user')]);
    }

    public function destroy(string $id): JsonResponse
    {
        $medication = Medication::findOrFail($id);

        if ($medication->user_id !== auth('user_api')->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $medication->delete();

        return response()->json(null, 204);
    }
}
