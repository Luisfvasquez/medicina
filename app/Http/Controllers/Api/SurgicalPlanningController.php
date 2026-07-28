<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SurgicalOperation;
use App\Models\SurgicalTeam;
use App\Models\MedicalSupplyOrder;
use Illuminate\Http\Request;

class SurgicalPlanningController extends Controller
{
    // Operations
    public function indexOperations(Request $request, $branch_id)
    {
        $operations = SurgicalOperation::with(['patient', 'service', 'teamMembers.staff.user'])
            ->where('clinic_branch_id', $branch_id)
            ->get();
        return response()->json($operations);
    }

    public function storeOperation(Request $request, $branch_id)
    {
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'clinic_service_id' => 'required|exists:clinic_services,id',
            'hospital_admission_id' => 'nullable|exists:hospital_admissions,id',
            'scheduled_at' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $operation = SurgicalOperation::create(array_merge($validated, [
            'clinic_branch_id' => $branch_id,
            'status' => 'scheduled'
        ]));

        return response()->json($operation->load(['patient', 'service']), 201);
    }

    // Team Members
    public function storeTeamMember(Request $request, $operation_id)
    {
        $validated = $request->validate([
            'clinic_staff_id' => 'required|exists:clinic_staff,id',
            'role' => 'required|string|max:255',
            'is_primary' => 'boolean',
        ]);

        $member = SurgicalTeam::create(array_merge($validated, [
            'surgical_operation_id' => $operation_id
        ]));

        return response()->json($member->load('staff.user'), 201);
    }

    // Medical Supply Orders
    public function indexSupplyOrders(Request $request, $branch_id)
    {
        $orders = MedicalSupplyOrder::with(['patient', 'operation', 'prescribingDoctor.user'])
            ->where('clinic_branch_id', $branch_id)
            ->get();
        return response()->json($orders);
    }

    public function storeSupplyOrder(Request $request, $branch_id)
    {
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'surgical_operation_id' => 'nullable|exists:surgical_operations,id',
            'prescribing_doctor_id' => 'required|exists:clinic_staff,id',
            'supplies_list' => 'required|string',
            'medical_house_name' => 'nullable|string|max:255',
        ]);

        $order = MedicalSupplyOrder::create(array_merge($validated, [
            'clinic_branch_id' => $branch_id,
            'status' => 'pending'
        ]));

        return response()->json($order, 201);
    }

    public function emitSupplyOrder(Request $request, $order_id)
    {
        $order = MedicalSupplyOrder::findOrFail($order_id);
        
        // Simulating the emission to an external provider (Medical House)
        $order->update(['status' => 'emitted']);
        
        return response()->json([
            'message' => 'Supply order emitted successfully',
            'order' => $order
        ]);
    }

    // Patient History
    public function recentPatientHistory(Request $request, $operation_id)
    {
        $operation = SurgicalOperation::findOrFail($operation_id);
        $oneMonthAgo = now()->subDays(30);

        // Fetch recent consultations for the same branch
        $consultations = \App\Models\Consultation::where('patient_id', $operation->patient_id)
            // Assuming consultations might have clinic_branch_id, if not, we adapt
            // For now, assume we just fetch recent ones (or use provider_branch_id if applicable)
            ->where('created_at', '>=', $oneMonthAgo)
            ->get();

        // Fetch recent hospital admissions for the same branch
        $admissions = \App\Models\HospitalAdmission::with('treatmentNotes')
            ->where('patient_id', $operation->patient_id)
            ->where('clinic_branch_id', $operation->clinic_branch_id)
            ->where('created_at', '>=', $oneMonthAgo)
            ->get();

        return response()->json([
            'operation' => $operation,
            'recent_history' => [
                'consultations' => $consultations,
                'hospital_admissions' => $admissions,
            ]
        ]);
    }
}
