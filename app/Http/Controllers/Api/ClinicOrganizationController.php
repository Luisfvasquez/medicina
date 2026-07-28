<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClinicDepartment;
use App\Models\ClinicRole;
use App\Models\ClinicService;
use Illuminate\Http\Request;

class ClinicOrganizationController extends Controller
{
    // Departments
    public function indexDepartments(Request $request, $branch_id)
    {
        $departments = ClinicDepartment::where('clinic_branch_id', $branch_id)->get();
        return response()->json($departments);
    }

    public function storeDepartment(Request $request, $branch_id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $department = ClinicDepartment::create(array_merge($validated, ['clinic_branch_id' => $branch_id]));
        return response()->json($department, 201);
    }

    // Roles
    public function indexRoles(Request $request, $branch_id)
    {
        $roles = ClinicRole::where('clinic_branch_id', $branch_id)->get();
        return response()->json($roles);
    }

    public function storeRole(Request $request, $branch_id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'permissions' => 'nullable|array',
        ]);

        $role = ClinicRole::create(array_merge($validated, ['clinic_branch_id' => $branch_id]));
        return response()->json($role, 201);
    }

    // Services
    public function indexServices(Request $request, $branch_id)
    {
        $services = ClinicService::where('clinic_branch_id', $branch_id)->get();
        return response()->json($services);
    }

    public function storeService(Request $request, $branch_id)
    {
        $validated = $request->validate([
            'clinic_department_id' => 'nullable|exists:clinic_departments,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'base_price' => 'required|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        $service = ClinicService::create(array_merge($validated, ['clinic_branch_id' => $branch_id]));
        return response()->json($service, 201);
    }
}
