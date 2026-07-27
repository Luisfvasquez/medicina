<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\LabAnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LabAnalyticsController extends Controller
{
    public function __construct(protected LabAnalyticsService $analyticsService)
    {
    }

    public function getMetrics(Request $request): JsonResponse
    {
        $metrics = $this->analyticsService->getDashboardMetrics($request->input('provider_profile_id'));

        return response()->json([
            'data' => $metrics,
        ]);
    }
}
