<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\SyncRequest;
use App\Services\SyncService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class SyncController extends Controller
{
    public function sync(SyncRequest $request, SyncService $service): JsonResponse
    {
        $user = Auth::guard('user_api')->user();

        $lastSyncTimestamp = $request->input('last_sync_timestamp')
            ? Carbon::parse($request->input('last_sync_timestamp'))
            : null;

        $push = $request->input('push', []);

        $result = $service->sync($push, $lastSyncTimestamp, $user);

        return response()->json($result);
    }
}
