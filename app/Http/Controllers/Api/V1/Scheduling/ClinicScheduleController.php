<?php

namespace App\Http\Controllers\Api\V1\Scheduling;

use App\Http\Controllers\Controller;
use App\Models\ClinicBranch;
use App\Models\ClinicSchedule;
use App\Models\ClinicBranchMember;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClinicScheduleController extends Controller
{
    public function show(string $clinicBranchId): JsonResponse
    {
        $branch = ClinicBranch::findOrFail($clinicBranchId);

        // Verify user has access (is member of the branch or admin)
        $user = auth('user_api')->user();
        if ($user->role !== 'ADMIN') {
            $isMember = ClinicBranchMember::where('user_id', $user->id)
                ->where('clinic_branch_id', $branch->id)
                ->where('is_active', true)
                ->exists();

            if (!$isMember) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
        }

        $schedules = ClinicSchedule::where('clinic_branch_id', $branch->id)
            ->orderByRaw("FIELD(weekday, 'MONDAY', 'TUESDAY', 'WEDNESDAY', 'THURSDAY', 'FRIDAY', 'SATURDAY', 'SUNDAY')")
            ->get();

        return response()->json(['data' => $schedules]);
    }

    public function store(Request $request, string $clinicBranchId): JsonResponse
    {
        $branch = ClinicBranch::findOrFail($clinicBranchId);

        // Verify user has access (is member of the branch or admin)
        $user = auth('user_api')->user();
        if ($user->role !== 'ADMIN') {
            $isMember = ClinicBranchMember::where('user_id', $user->id)
                ->where('clinic_branch_id', $branch->id)
                ->whereIn('role', ['OWNER', 'ADMIN'])
                ->where('is_active', true)
                ->exists();

            if (!$isMember) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
        }

        $validated = $request->validate([
            'schedules' => ['required', 'array', 'min:1'],
            'schedules.*.weekday' => ['required', 'string', 'in:MONDAY,TUESDAY,WEDNESDAY,THURSDAY,FRIDAY,SATURDAY,SUNDAY'],
            'schedules.*.start_time' => ['required', 'date_format:H:i'],
            'schedules.*.end_time' => ['required', 'date_format:H:i', 'after:schedules.*.start_time'],
        ]);

        $created = [];
        foreach ($validated['schedules'] as $scheduleData) {
            $schedule = ClinicSchedule::updateOrCreate(
                [
                    'clinic_branch_id' => $branch->id,
                    'weekday' => $scheduleData['weekday'],
                ],
                [
                    'start_time' => $scheduleData['start_time'],
                    'end_time' => $scheduleData['end_time'],
                    'is_active' => true,
                ]
            );
            $created[] = $schedule;
        }

        return response()->json(['data' => $created], 201);
    }

    public function destroy(string $clinicBranchId, string $weekday): JsonResponse
    {
        $branch = ClinicBranch::findOrFail($clinicBranchId);

        // Verify user has access (is member of the branch or admin)
        $user = auth('user_api')->user();
        if ($user->role !== 'ADMIN') {
            $isMember = ClinicBranchMember::where('user_id', $user->id)
                ->where('clinic_branch_id', $branch->id)
                ->whereIn('role', ['OWNER', 'ADMIN'])
                ->where('is_active', true)
                ->exists();

            if (!$isMember) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
        }

        $schedule = ClinicSchedule::where('clinic_branch_id', $branch->id)
            ->where('weekday', $weekday)
            ->firstOrFail();

        $schedule->delete();

        return response()->json(null, 204);
    }
}
