<?php

namespace App\Http\Controllers\Api\V1\Scheduling;

use App\Enums\Weekday;
use App\Http\Controllers\Controller;
use App\Models\DoctorSchedule;
use App\Models\ScheduleException;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    public function myIndex(): JsonResponse
    {
        $user = auth('user_api')->user();

        $schedules = DoctorSchedule::where('user_id', $user->id)
            ->orderByRaw("FIELD(weekday, 'MONDAY', 'TUESDAY', 'WEDNESDAY', 'THURSDAY', 'FRIDAY', 'SATURDAY', 'SUNDAY')")
            ->get();

        return response()->json(['data' => $schedules]);
    }

    public function myStore(Request $request): JsonResponse
    {
        $user = auth('user_api')->user();

        $validated = $request->validate([
            'weekday' => ['required', 'string', 'in:MONDAY,TUESDAY,WEDNESDAY,THURSDAY,FRIDAY,SATURDAY,SUNDAY'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'appointment_duration' => ['nullable', 'integer', 'min:5', 'max:240'],
            'max_per_slot' => ['nullable', 'integer', 'min:1', 'max:10'],
        ]);

        $schedule = DoctorSchedule::updateOrCreate(
            [
                'user_id' => $user->id,
                'weekday' => $validated['weekday'],
            ],
            [
                'start_time' => $validated['start_time'],
                'end_time' => $validated['end_time'],
                'appointment_duration' => $validated['appointment_duration'] ?? 30,
                'max_per_slot' => $validated['max_per_slot'] ?? 1,
                'is_active' => true,
            ]
        );

        return response()->json(['data' => $schedule], 201);
    }

    public function myUpdate(Request $request, string $id): JsonResponse
    {
        $user = auth('user_api')->user();

        $schedule = DoctorSchedule::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $validated = $request->validate([
            'start_time' => ['sometimes', 'date_format:H:i'],
            'end_time' => ['sometimes', 'date_format:H:i', 'after:start_time'],
            'appointment_duration' => ['sometimes', 'integer', 'min:5', 'max:240'],
            'max_per_slot' => ['sometimes', 'integer', 'min:1', 'max:10'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $schedule->update($validated);

        return response()->json(['data' => $schedule]);
    }

    public function myDestroy(string $id): JsonResponse
    {
        $user = auth('user_api')->user();

        $schedule = DoctorSchedule::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $schedule->delete();

        return response()->json(null, 204);
    }

    // Schedule Exceptions
    public function exceptionsIndex(Request $request): JsonResponse
    {
        $user = auth('user_api')->user();

        $query = ScheduleException::where('user_id', $user->id);

        if ($request->filled('from_date')) {
            $query->where('exception_date', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->where('exception_date', '<=', $request->to_date);
        }

        $exceptions = $query->orderBy('exception_date')->get();

        return response()->json(['data' => $exceptions]);
    }

    public function exceptionsStore(Request $request): JsonResponse
    {
        $user = auth('user_api')->user();

        $validated = $request->validate([
            'exception_date' => ['required', 'date', 'after_or_equal:today'],
            'exception_type' => ['required', 'string', 'in:VACATION,DAY_OFF,CUSTOM_HOURS'],
            'custom_start_time' => ['required_if:exception_type,CUSTOM_HOURS', 'nullable', 'date_format:H:i'],
            'custom_end_time' => ['required_if:exception_type,CUSTOM_HOURS', 'nullable', 'date_format:H:i', 'after:custom_start_time'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $exception = ScheduleException::updateOrCreate(
            [
                'user_id' => $user->id,
                'exception_date' => $validated['exception_date'],
            ],
            [
                'exception_type' => $validated['exception_type'],
                'custom_start_time' => $validated['custom_start_time'] ?? null,
                'custom_end_time' => $validated['custom_end_time'] ?? null,
                'reason' => $validated['reason'] ?? null,
            ]
        );

        return response()->json(['data' => $exception], 201);
    }

    public function exceptionsDestroy(string $id): JsonResponse
    {
        $user = auth('user_api')->user();

        $exception = ScheduleException::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $exception->delete();

        return response()->json(null, 204);
    }
}
