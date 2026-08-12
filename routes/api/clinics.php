<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ClinicOrganizationController;
use App\Http\Controllers\Api\ClinicStaffController;
use App\Http\Controllers\Api\InpatientController;
use App\Http\Controllers\Api\SurgicalPlanningController;

Route::prefix('v1')->middleware(['auth:user_api', 'user.status'])->group(function () {
    // ─── CLINICS MODULE ROUTES ─────────────────
    Route::prefix('clinics/{branch_id}')->group(function () {
        // Organization
        Route::get('departments', [ClinicOrganizationController::class, 'indexDepartments']);
        Route::post('departments', [ClinicOrganizationController::class, 'storeDepartment']);
        Route::get('roles', [ClinicOrganizationController::class, 'indexRoles']);
        Route::post('roles', [ClinicOrganizationController::class, 'storeRole']);
        Route::get('services', [ClinicOrganizationController::class, 'indexServices']);
        Route::post('services', [ClinicOrganizationController::class, 'storeService']);

        // Staff
        Route::get('staff', [ClinicStaffController::class, 'index']);
        Route::post('staff', [ClinicStaffController::class, 'store']);
        Route::put('staff/{id}', [ClinicStaffController::class, 'update']);

        // Inpatient
        Route::get('rooms', [InpatientController::class, 'indexRooms']);
        Route::get('admissions', [InpatientController::class, 'indexAdmissions']);
        Route::post('admissions', [InpatientController::class, 'storeAdmission']);
        Route::post('admissions/{admission_id}/treatment-notes', [InpatientController::class, 'storeTreatmentNote']);
        Route::post('admissions/{admission_id}/medications', [InpatientController::class, 'storeMedication']);
        Route::post('admissions/{admission_id}/service-charges', [InpatientController::class, 'storeCharge']);

        // Surgical Planning
        Route::get('operations', [SurgicalPlanningController::class, 'indexOperations']);
        Route::post('operations', [SurgicalPlanningController::class, 'storeOperation']);
        Route::get('operations/{operation_id}/recent-history', [SurgicalPlanningController::class, 'recentPatientHistory']);
        Route::post('operations/{operation_id}/team', [SurgicalPlanningController::class, 'storeTeamMember']);
        Route::get('supply-orders', [SurgicalPlanningController::class, 'indexSupplyOrders']);
        Route::post('supply-orders', [SurgicalPlanningController::class, 'storeSupplyOrder']);
        Route::post('supply-orders/{order_id}/emit', [SurgicalPlanningController::class, 'emitSupplyOrder']);
    });
});
