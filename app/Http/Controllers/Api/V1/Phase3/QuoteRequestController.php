<?php

namespace App\Http\Controllers\Api\V1\Phase3;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\QuoteRequest\StoreQuoteRequestRequest;
use App\Http\Requests\Api\V1\QuoteRequest\UpdateQuoteRequestRequest;
use App\Models\QuoteRequest;
use Illuminate\Http\JsonResponse;

class QuoteRequestController extends Controller
{
    public function index(): JsonResponse
    {
        $user = auth('user_api')->user();

        $requests = QuoteRequest::with(['prescription.user', 'patient', 'city', 'offers.providerProfile'])
            ->when($user->role === 'DOCTOR', function ($q) use ($user) {
                $q->whereHas('prescription', fn($sq) => $sq->where('user_id', $user->id));
            })
            ->when($user->role === 'PATIENT', fn($q) => $q->where('patient_id', $user->patient->id ?? null))
            ->latest()
            ->paginate(20);

        return response()->json(['data' => $requests]);
    }

    public function store(StoreQuoteRequestRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['status'] = 'OPEN';

        $quoteRequest = QuoteRequest::create($data);

        return response()->json(['data' => $quoteRequest->load(['prescription', 'patient', 'city'])], 201);
    }

    public function show(string $id): JsonResponse
    {
        $quoteRequest = QuoteRequest::with(['prescription.user', 'patient', 'city', 'offers.providerProfile'])
            ->findOrFail($id);

        return response()->json(['data' => $quoteRequest]);
    }

    public function update(UpdateQuoteRequestRequest $request, string $id): JsonResponse
    {
        $quoteRequest = QuoteRequest::findOrFail($id);
        $quoteRequest->update($request->validated());

        return response()->json(['data' => $quoteRequest->load(['prescription', 'patient', 'offers'])]);
    }

    public function destroy(string $id): JsonResponse
    {
        $quoteRequest = QuoteRequest::findOrFail($id);

        $user = auth('user_api')->user();
        if ($user->role !== 'ADMIN') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $quoteRequest->delete();

        return response()->json(null, 204);
    }
}
