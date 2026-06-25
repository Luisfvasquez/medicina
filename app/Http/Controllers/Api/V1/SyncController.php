<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\SyncRequest;
use App\Models\PatientAccount;
use App\Services\SyncService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class SyncController extends Controller
{
    public function sync(SyncRequest $request, SyncService $service): JsonResponse
    {
        $user = Auth::guard('user_api')->user();
        $patientAccount = Auth::guard('patient_api')->user();

        $lastSyncTimestamp = $request->input('last_sync_timestamp')
            ? Carbon::parse($request->input('last_sync_timestamp'))
            : null;

        $push = $request->input('push', []);

        // Doctor/Provider sync
        if ($user) {
            $result = $service->syncForUser($push, $lastSyncTimestamp, $user);
            return response()->json($result);
        }

        // Patient sync
        if ($patientAccount) {
            $result = $service->syncForPatient($push, $lastSyncTimestamp, $patientAccount);
            return response()->json($result);
        }

        return response()->json(['error' => 'Unauthenticated'], 401);
    }
}
