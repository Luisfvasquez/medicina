<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\ProviderProfile;
use App\Models\Clinic;
use App\Services\AvailabilityService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicCatalogController extends Controller
{
    public function doctors(Request $request): JsonResponse
    {
        $query = User::where('role', 'DOCTOR')
            ->where('is_active', true)
            ->whereHas('verificationDocuments', fn($q) => $q->where('status', 'APPROVED'))
            ->with(['specialties:id,name', 'city:id,name']);

        if ($request->filled('city_id')) {
            $query->where('city_id', $request->city_id);
        }

        if ($request->filled('specialty_id')) {
            $query->whereHas('specialties', fn($q) => $q->where('specialty_id', $request->specialty_id));
        }

        $doctors = $query->get();

        $data = $doctors->map(fn($doctor) => [
            'id' => $doctor->uuid,
            'full_name' => $doctor->full_name,
            'specialties' => $doctor->specialties->map(fn($s) => [
                'id' => $s->id,
                'name' => $s->name,
            ]),
            'city' => $doctor->city ? [
                'id' => $doctor->city->id,
                'name' => $doctor->city->name,
            ] : null,
            'logo_url' => $doctor->logo_url,
            'is_verified' => true,
        ]);

        return response()->json(['data' => $data]);
    }

    public function pharmacies(Request $request): JsonResponse
    {
        $query = ProviderProfile::where('type', 'PHARMACY')
            ->where('is_verified', true)
            ->with(['branches', 'city:id,name', 'user']);

        if ($request->filled('city_id')) {
            $query->where('city_id', $request->city_id);
        }

        $pharmacies = $query->get();

        $data = $pharmacies->map(fn($pharmacy) => [
            'id' => $pharmacy->uuid,
            'commercial_name' => $pharmacy->commercial_name,
            'rif' => $pharmacy->rif,
            'address' => $pharmacy->address,
            'phone' => $pharmacy->phone,
            'is_open' => $pharmacy->is_open,
            'is_verified' => true,
            'logo_url' => $pharmacy->user?->logo_url,
            'city' => $pharmacy->city ? [
                'id' => $pharmacy->city->id,
                'name' => $pharmacy->city->name,
            ] : null,
            'branches' => $pharmacy->branches->map(fn($b) => [
                'id' => $b->uuid,
                'name' => $b->name,
                'address' => $b->address,
                'phone' => $b->phone,
                'is_open' => $b->is_open,
                'latitude' => $b->latitude,
                'longitude' => $b->longitude,
                'google_maps_url' => $b->google_maps_url,
            ]),
        ]);

        return response()->json(['data' => $data]);
    }

    public function clinics(Request $request): JsonResponse
    {
        $query = Clinic::with([
            'branches',
            'branches.city:id,name',
            'branches.members' => fn($q) => $q->where('is_active', true),
            'branches.members.user:id,full_name,logo_url',
        ]);

        if ($request->filled('city_id')) {
            $query->whereHas('branches', fn($q) => $q->where('city_id', $request->city_id));
        }

        $clinics = $query->get();

        $data = $clinics->map(fn($clinic) => [
            'id' => $clinic->uuid,
            'name' => $clinic->name,
            'rif' => $clinic->rif,
            'logo_url' => $clinic->logo_url,
            'website' => $clinic->website,
            'branches' => $clinic->branches->map(fn($b) => [
                'id' => $b->uuid,
                'name' => $b->name,
                'address' => $b->address,
                'phone' => $b->phone,
                'is_main_branch' => $b->is_main_branch,
                'latitude' => $b->latitude,
                'longitude' => $b->longitude,
                'google_maps_url' => $b->google_maps_url,
                'city' => $b->city ? [
                    'id' => $b->city->id,
                    'name' => $b->city->name,
                ] : null,
                'doctors' => $b->members
                    ->where('user.role', 'DOCTOR')
                    ->map(fn($m) => [
                        'id' => $m->user->uuid,
                        'full_name' => $m->user->full_name,
                        'logo_url' => $m->user->logo_url,
                        'department' => $m->department,
                        'office_number' => $m->office_number,
                    ]),
            ]),
        ]);

        return response()->json(['data' => $data]);
    }

    public function doctorAvailability(Request $request, string $doctorId): JsonResponse
    {
        $request->validate([
            'date' => ['required', 'date', 'after_or_equal:today'],
        ]);

        $doctor = User::where('role', 'DOCTOR')
            ->where('is_active', true)
            ->where('uuid', $doctorId)
            ->whereHas('verificationDocuments', fn($q) => $q->where('status', 'APPROVED'))
            ->firstOrFail();

        $date = $request->date;
        $weekday = strtoupper(Carbon::parse($date)->format('l'));

        $availabilityService = app(AvailabilityService::class);
        $slots = $availabilityService->getAvailableSlots($doctor->id, $date);

        return response()->json([
            'data' => [
                'doctor_id' => $doctor->uuid,
                'date' => $date,
                'weekday' => $weekday,
                ...$slots,
            ]
        ]);
    }
}
