<?php

use App\Http\Controllers\Api\AdmissionController;
use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DoctorScheduleController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\LabController;
use App\Http\Controllers\Api\MedicalTestController;
use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\Api\PharmacyController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\VisitController;
use App\Http\Controllers\Api\WardBedController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);
Route::post('/invitations/{token}/accept', [UserController::class, 'acceptInvitation']);
Route::get('/invitations/{token}/validate', [UserController::class, 'validateInvitation']);

// Protected routes (require authentication)
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::put('/auth/me', [AuthController::class, 'updateProfile']);
    Route::post('/auth/change-password', [AuthController::class, 'changePassword']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/user/{user}', [AuthController::class, 'show']);
    Route::get('/doctors', [AuthController::class, 'getDoctors']);

    // Medical Tests (read for all, mutations admin only)
    Route::get('/medical-tests', [MedicalTestController::class, 'index']);
    Route::middleware('role:admin')->group(function () {
        Route::post('/medical-tests', [MedicalTestController::class, 'store']);
        Route::put('/medical-tests/{medicalTest}', [MedicalTestController::class, 'update']);
        Route::delete('/medical-tests/{medicalTest}', [MedicalTestController::class, 'destroy']);
    });

    // Doctor Schedules (read for all authenticated, mutations admin only)
    Route::get('/doctor-schedules', [DoctorScheduleController::class, 'index']);
    Route::get('/doctor-schedules/check-availability', [DoctorScheduleController::class, 'checkAvailability']);
    Route::middleware('role:admin')->group(function () {
        Route::post('/doctor-schedules', [DoctorScheduleController::class, 'store']);
        Route::get('/doctor-schedules/{schedule}', [DoctorScheduleController::class, 'show']);
        Route::put('/doctor-schedules/{schedule}', [DoctorScheduleController::class, 'update']);
        Route::delete('/doctor-schedules/{schedule}', [DoctorScheduleController::class, 'destroy']);
    });

    // Admissions & Referrals (view for doctor/nurse/admin/receptionist, manage for doctor)
    Route::middleware('role:admin,doctor,nurse,receptionist')->group(function () {
        Route::get('/admissions', [AdmissionController::class, 'index']);
        Route::get('/admissions/{admission}', [AdmissionController::class, 'show']);
    });
    Route::middleware('role:doctor')->group(function () {
        Route::post('/admissions', [AdmissionController::class, 'store']);
        Route::post('/admissions/{admission}/discharge', [AdmissionController::class, 'discharge']);
        Route::post('/admissions/{admission}/complete-referral', [AdmissionController::class, 'completeReferral']);
    });

    // Wards & Beds (admin only)
    Route::middleware('role:admin')->group(function () {
        Route::get('/wards', [WardBedController::class, 'indexWards']);
        Route::post('/wards', [WardBedController::class, 'storeWard']);
        Route::put('/wards/{ward}', [WardBedController::class, 'updateWard']);
        Route::delete('/wards/{ward}', [WardBedController::class, 'destroyWard']);

        Route::get('/beds', [WardBedController::class, 'indexBeds']);
        Route::post('/beds', [WardBedController::class, 'storeBed']);
        Route::put('/beds/{bed}', [WardBedController::class, 'updateBed']);
        Route::delete('/beds/{bed}', [WardBedController::class, 'destroyBed']);
    });
    Route::get('/beds', [WardBedController::class, 'indexBeds']);

    // Admin only routes
    Route::middleware('role:admin')->group(function () {
        Route::post('/auth/register', [AuthController::class, 'register']);
        
        // User Management
        Route::get('/users', [UserController::class, 'index']);
        Route::get('/users/{user}', [UserController::class, 'show']);
        Route::put('/users/{user}', [UserController::class, 'update']);
        Route::delete('/users/{user}', [UserController::class, 'destroy']);
        Route::post('/invitations', [UserController::class, 'createInvitation']);
        Route::get('/invitations', [UserController::class, 'getInvitations']);
    });

    // Patient Management (admin, receptionist, doctor, nurse)
    Route::middleware('role:admin,receptionist,doctor,nurse')->group(function () {
        Route::get('/patients', [PatientController::class, 'index']);
        Route::get('/patients/{patient}', [PatientController::class, 'show']);
        Route::get('/patients/{patient}/visits', [PatientController::class, 'getVisits']);
        Route::put('/patients/{patient}', [PatientController::class, 'update']);
    });

    // Patient Management (admin, receptionist)
    Route::middleware('role:admin,receptionist')->group(function () {
        Route::post('/patients', [PatientController::class, 'store']);
        Route::delete('/patients/{patient}', [PatientController::class, 'destroy']);
    });

    // Appointment Management (all authenticated can view based on role)
    Route::get('/appointments', [AppointmentController::class, 'index']);
    Route::get('/appointments/{appointment}', [AppointmentController::class, 'show']);

    // Appointment Management (receptionist)
    Route::middleware('role:receptionist')->group(function () {
        Route::post('/appointments', [AppointmentController::class, 'store']);
        Route::put('/appointments/{appointment}', [AppointmentController::class, 'update']);
        Route::patch('/appointments/{appointment}/cancel', [AppointmentController::class, 'cancel']);
    });

    // Appointment Workflow Transitions
    Route::middleware('role:doctor')->group(function () {
        Route::post('/appointments/{appointment}/doctor-review', [AppointmentController::class, 'doctorReview']);
        Route::post('/appointments/{appointment}/prescribe', [AppointmentController::class, 'prescribe']);
    });

    Route::middleware('role:cashier')->group(function () {
        Route::post('/appointments/{appointment}/mark-paid', [AppointmentController::class, 'markPaid']);
    });

    Route::middleware('role:pharmacist')->group(function () {
        Route::post('/appointments/{appointment}/dispense', [AppointmentController::class, 'dispense']);
    });

    // Visit/EMR Management (admin, doctor, nurse, receptionist)
    Route::middleware('role:admin,doctor,nurse,receptionist')->group(function () {
        Route::get('/visits', [VisitController::class, 'index']);
        Route::get('/visits/{visit}', [VisitController::class, 'show']);
    });

    // Visit/EMR Management (receptionist, nurse)
    Route::middleware('role:receptionist,nurse')->group(function () {
        Route::post('/visits', [VisitController::class, 'store']);
        Route::put('/visits/{visit}', [VisitController::class, 'update']);
    });

    Route::middleware('role:doctor')->group(function () {
        Route::post('/visits/{visit}/doctor-review', [VisitController::class, 'doctorReview']);
        Route::post('/visits/{visit}/prescribe', [VisitController::class, 'prescribe']);
    });

    Route::middleware('role:cashier')->group(function () {
        Route::post('/visits/{visit}/mark-paid', [VisitController::class, 'markPaid']);
    });

    Route::middleware('role:pharmacist')->group(function () {
        Route::post('/visits/{visit}/dispense', [VisitController::class, 'dispense']);
    });

    // Lab Orders (admin, doctor, lab_technician)
    Route::middleware('role:admin,doctor,lab_technician')->group(function () {
        Route::get('/labs/orders', [LabController::class, 'indexOrders']);
        Route::get('/labs/orders/{labOrder}', [LabController::class, 'showOrder']);
    });

    // Lab Orders (doctor)
    Route::middleware('role:doctor')->group(function () {
        Route::post('/labs/orders', [LabController::class, 'storeOrder']);
        Route::put('/labs/orders/{labOrder}', [LabController::class, 'updateOrder']);
    });

    // Lab Results (lab_technician)
    Route::middleware('role:lab_technician')->group(function () {
        Route::post('/labs/results', [LabController::class, 'storeResult']);
    });

    // Lab Results (admin, doctor, lab_technician)
    Route::middleware('role:admin,doctor,lab_technician')->group(function () {
        Route::get('/labs/results/{labOrder}', [LabController::class, 'showResult']);
    });

    // Prescriptions (admin, doctor, pharmacist)
    Route::middleware('role:admin,doctor,pharmacist')->group(function () {
        Route::get('/pharmacy/prescriptions', [PharmacyController::class, 'indexPrescriptions']);
        Route::get('/pharmacy/prescriptions/{prescription}', [PharmacyController::class, 'showPrescription']);
    });

    // Prescriptions (admin, doctor)
    Route::middleware('role:admin,doctor')->group(function () {
        Route::post('/pharmacy/prescriptions', [PharmacyController::class, 'storePrescription']);
    });

    // Prescriptions (admin, doctor, pharmacist)
    Route::middleware('role:admin,doctor,pharmacist')->group(function () {
        Route::put('/pharmacy/prescriptions/{prescription}', [PharmacyController::class, 'updatePrescription']);
    });

    // Pharmacy Inventory (admin, pharmacist)
    Route::middleware('role:admin,pharmacist')->group(function () {
        Route::get('/pharmacy/inventory', [PharmacyController::class, 'indexInventory']);
        Route::post('/pharmacy/inventory', [PharmacyController::class, 'storeInventory']);
        Route::put('/pharmacy/inventory/{inventory}', [PharmacyController::class, 'updateInventory']);
    });

    // Invoices (admin, receptionist, cashier)
    Route::middleware('role:admin,receptionist,cashier')->group(function () {
        Route::get('/billing/invoices', [InvoiceController::class, 'index']);
        Route::get('/billing/invoices/{invoice}', [InvoiceController::class, 'show']);
    });

    // Invoices (receptionist)
    Route::middleware('role:receptionist')->group(function () {
        Route::post('/billing/invoices', [InvoiceController::class, 'store']);
        Route::put('/billing/invoices/{invoice}', [InvoiceController::class, 'update']);
    });

    // Invoices (cashier)
    Route::middleware('role:cashier')->group(function () {
        Route::patch('/billing/invoices/{invoice}/pay', [InvoiceController::class, 'markAsPaid']);
    });
});
