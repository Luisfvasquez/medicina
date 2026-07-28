<?php

namespace App\Http\Controllers\MedicalSupply;

use App\Http\Controllers\Controller;
use App\Models\MedicalSupplyStaff;
use App\Models\User;
use Illuminate\Http\Request;

class SupplyStaffController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $staff = MedicalSupplyStaff::where('user_id', $request->user()->id)->firstOrFail();
        $profileId = $staff->provider_profile_id;

        $staffList = MedicalSupplyStaff::with('user')->where('provider_profile_id', $profileId)->get();

        return response()->json($staffList);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|exists:users,email',
            'role' => 'required|in:MANAGER,SALES_REP',
        ]);

        $managerStaff = MedicalSupplyStaff::where('user_id', $request->user()->id)->firstOrFail();
        
        if ($managerStaff->role->value !== 'MANAGER') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $user = User::where('email', $validated['email'])->firstOrFail();

        $newStaff = MedicalSupplyStaff::firstOrCreate([
            'user_id' => $user->id,
            'provider_profile_id' => $managerStaff->provider_profile_id,
        ], [
            'role' => $validated['role'],
        ]);

        return response()->json($newStaff, 201);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {
        $managerStaff = MedicalSupplyStaff::where('user_id', $request->user()->id)->firstOrFail();
        
        if ($managerStaff->role->value !== 'MANAGER') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $staff = MedicalSupplyStaff::where('provider_profile_id', $managerStaff->provider_profile_id)->findOrFail($id);
        
        if ($staff->user_id === $request->user()->id) {
            return response()->json(['message' => 'Cannot remove yourself'], 400);
        }

        $staff->delete();

        return response()->json(null, 204);
    }
}
