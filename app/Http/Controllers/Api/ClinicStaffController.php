<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClinicStaff;
use Illuminate\Http\Request;

class ClinicStaffController extends Controller
{
    public function index(Request $request, $branch_id)
    {
        $staff = ClinicStaff::with(['user', 'department', 'role'])
            ->where('clinic_branch_id', $branch_id)
            ->get();
            
        return response()->json($staff);
    }

    public function store(Request $request, $branch_id)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'clinic_department_id' => 'nullable|exists:clinic_departments,id',
            'clinic_role_id' => 'required|exists:clinic_roles,id',
            'is_active' => 'boolean',
        ]);

        $staff = ClinicStaff::create(array_merge($validated, ['clinic_branch_id' => $branch_id]));
        
        return response()->json($staff->load(['user', 'department', 'role']), 201);
    }

    public function update(Request $request, $branch_id, $id)
    {
        $staff = ClinicStaff::where('clinic_branch_id', $branch_id)->findOrFail($id);
        
        $validated = $request->validate([
            'clinic_department_id' => 'nullable|exists:clinic_departments,id',
            'clinic_role_id' => 'exists:clinic_roles,id',
            'is_active' => 'boolean',
        ]);
        
        $staff->update($validated);
        
        return response()->json($staff->load(['user', 'department', 'role']));
    }
}
