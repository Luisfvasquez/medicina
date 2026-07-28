<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HospitalAdmission;
use App\Models\HospitalizationRoom;
use App\Models\HospitalBed;
use App\Models\InpatientTreatmentNote;
use App\Models\InpatientMedicationSchedule;
use App\Models\ServiceCharge;
use Illuminate\Http\Request;

class InpatientController extends Controller
{
    // Rooms & Beds
    public function indexRooms(Request $request, $branch_id)
    {
        $rooms = HospitalizationRoom::with('beds')->where('clinic_branch_id', $branch_id)->get();
        return response()->json($rooms);
    }

    // Admissions
    public function indexAdmissions(Request $request, $branch_id)
    {
        $admissions = HospitalAdmission::with(['patient', 'bed.room', 'admittingDoctor.user'])
            ->where('clinic_branch_id', $branch_id)
            ->get();
        return response()->json($admissions);
    }

    public function storeAdmission(Request $request, $branch_id)
    {
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'hospital_bed_id' => 'required|exists:hospital_beds,id',
            'admitting_doctor_id' => 'nullable|exists:clinic_staff,id',
            'admission_date' => 'required|date',
            'reason_for_admission' => 'nullable|string',
        ]);

        // Mark bed as occupied
        $bed = HospitalBed::findOrFail($validated['hospital_bed_id']);
        $bed->update(['status' => 'occupied']);

        $admission = HospitalAdmission::create(array_merge($validated, [
            'clinic_branch_id' => $branch_id,
            'status' => 'admitted'
        ]));

        return response()->json($admission->load(['patient', 'bed']), 201);
    }

    // Medical Notes
    public function storeTreatmentNote(Request $request, $admission_id)
    {
        $validated = $request->validate([
            'clinic_staff_id' => 'required|exists:clinic_staff,id',
            'note' => 'required|string',
            'type' => 'required|string',
        ]);

        $note = InpatientTreatmentNote::create(array_merge($validated, [
            'hospital_admission_id' => $admission_id
        ]));

        return response()->json($note, 201);
    }

    // Medication Schedule
    public function storeMedication(Request $request, $admission_id)
    {
        $validated = $request->validate([
            'medication_name' => 'required|string|max:255',
            'dosage' => 'required|string|max:255',
            'route' => 'required|string|max:255',
            'scheduled_time' => 'required|date',
        ]);

        $med = InpatientMedicationSchedule::create(array_merge($validated, [
            'hospital_admission_id' => $admission_id,
            'status' => 'pending'
        ]));

        return response()->json($med, 201);
    }

    // Service Charges
    public function storeCharge(Request $request, $admission_id)
    {
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'clinic_service_id' => 'required|exists:clinic_services,id',
            'amount' => 'required|numeric|min:0',
            'quantity' => 'integer|min:1',
            'charged_by' => 'nullable|exists:clinic_staff,id',
        ]);

        $charge = ServiceCharge::create(array_merge($validated, [
            'hospital_admission_id' => $admission_id
        ]));

        return response()->json($charge, 201);
    }
}
