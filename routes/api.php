<?php

use App\Http\Controllers\Api\ActivityLogController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\FollowUpController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\HrmController;
use App\Http\Controllers\Api\LeadController;
use App\Http\Controllers\Api\ChunkedUploadController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\ProjectConfigurationController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\RolePermissionController;
use App\Http\Controllers\Api\SiteVisitController;
use App\Http\Controllers\Api\TowerController;
use App\Http\Controllers\Api\UnitController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — Property CRM
|--------------------------------------------------------------------------
*/

// ── Public ───────────────────────────────────────────────────────────────
Route::post('/login', [AuthController::class, 'login']);

// ── Authenticated ─────────────────────────────────────────────────────────
Route::middleware('auth:api')->group(function () {

    // Auth
    Route::get('/me', [AuthController::class, 'me']);
    Route::put('/me/preferences', [AuthController::class, 'updatePreferences']);
    Route::post('/me/profile-image', [AuthController::class, 'updateProfileImage']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Dashboard (role-aware response)
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // Lightweight list of employees for dropdowns/assignments
    Route::get('/users/employees', [UserController::class, 'employees']);

    // Leads
    Route::get('/leads/counts', [LeadController::class, 'counts']);
    Route::patch('/leads/bulk-assign', [LeadController::class, 'bulkAssign']);
    Route::post('/leads/check-duplicates', [LeadController::class, 'checkDuplicates']);
    Route::delete('/leads/bulk-delete', [LeadController::class, 'bulkDelete']);
    Route::post('/leads/{lead}/accept', [LeadController::class, 'accept']);
    Route::post('/leads/{lead}/reject', [LeadController::class, 'reject']);
    Route::get('/leads/{lead}/follow-ups', [LeadController::class, 'followUps']);
    Route::get('/leads/{lead}/site-visits', [LeadController::class, 'siteVisits']);
    Route::get('/leads/{lead}/activity', [LeadController::class, 'activity']);
    Route::apiResource('leads', LeadController::class);

    // Follow-ups
    Route::get('/follow-ups/counts', [FollowUpController::class, 'counts']);
    Route::get('/follow-ups', [FollowUpController::class, 'index']);
    Route::post('/leads/{lead}/follow-ups', [FollowUpController::class, 'store']);
    Route::patch('/follow-ups/{followUp}/complete', [FollowUpController::class, 'complete']);
    Route::patch('/follow-ups/{followUp}/miss', [FollowUpController::class, 'miss']);

    // Site Visits
    Route::get('/site-visits/counts', [SiteVisitController::class, 'counts']);
    Route::get('/site-visits', [SiteVisitController::class, 'index']);
    Route::post('/leads/{lead}/site-visits', [SiteVisitController::class, 'store']);
    Route::patch('/site-visits/{siteVisit}/complete', [SiteVisitController::class, 'complete']);

    // Activity Log
    Route::get('/activity', [ActivityLogController::class, 'index']);

    // Reports & Analytics
    Route::get('/reports/leads', [ReportController::class, 'leadPerformance']);
    Route::get('/reports/sales', [ReportController::class, 'salesPerformance']);
    Route::get('/reports/employees', [ReportController::class, 'employeePerformance']);
    Route::get('/reports/projects', [ReportController::class, 'projectPerformance']);
    Route::get('/reports/inventory', [ReportController::class, 'inventoryReports']);
    Route::get('/reports/marketing', [ReportController::class, 'marketingPerformance']);
    Route::get('/reports/sla', [ReportController::class, 'slaAdherence']);

    // Projects
    Route::get('/projects/counts', [ProjectController::class, 'counts']);
    Route::post('/projects/{project}/upload-image', [ProjectController::class, 'uploadImage']);
    Route::post('/projects/{project}/delete-image', [ProjectController::class, 'deleteImage']);
    Route::get('/media-proxy', [ProjectController::class, 'proxyMedia']);
    Route::apiResource('projects', ProjectController::class);

    // Project Configurations (BHK types)
    Route::get('/projects/{project}/configurations', [ProjectConfigurationController::class, 'index']);
    Route::post('/projects/{project}/configurations', [ProjectConfigurationController::class, 'store']);
    Route::patch('/projects/{project}/configurations/{configuration}', [ProjectConfigurationController::class, 'update']);
    Route::delete('/projects/{project}/configurations/{configuration}', [ProjectConfigurationController::class, 'destroy']);

    // Inventory — Towers
    Route::get('/projects/{project}/towers', [TowerController::class, 'index']);
    Route::post('/projects/{project}/towers', [TowerController::class, 'store']);
    Route::get('/towers/{tower}', [TowerController::class, 'show']);
    Route::patch('/towers/{tower}', [TowerController::class, 'update']);
    Route::delete('/towers/{tower}', [TowerController::class, 'destroy']);

    // Inventory — Units
    Route::get('/towers/{tower}/units', [UnitController::class, 'index']);
    Route::post('/towers/{tower}/units', [UnitController::class, 'store']);
    Route::get('/units/{unit}', [UnitController::class, 'show']);
    Route::patch('/units/{unit}/status', [UnitController::class, 'changeStatus']);
    Route::patch('/units/{unit}', [UnitController::class, 'update']);
    Route::delete('/units/{unit}', [UnitController::class, 'destroy']);

    // Bookings
    Route::get('/bookings', [BookingController::class, 'index']);
    Route::post('/bookings', [BookingController::class, 'store']);
    Route::get('/bookings/{booking}', [BookingController::class, 'show']);
    Route::patch('/bookings/{booking}', [BookingController::class, 'update']);

    // Payments
    Route::get('/bookings/{booking}/payments', [PaymentController::class, 'index']);
    Route::post('/bookings/{booking}/payments', [PaymentController::class, 'store']);
    Route::patch('/payments/{payment}', [PaymentController::class, 'update']);

    // Chunked Uploads (for large PDFs / files)
    Route::post('/uploads/init', [ChunkedUploadController::class, 'init']);
    Route::post('/uploads/{uploadId}/chunk', [ChunkedUploadController::class, 'chunk']);
    Route::post('/uploads/{uploadId}/complete', [ChunkedUploadController::class, 'complete']);
    Route::delete('/uploads/{uploadId}', [ChunkedUploadController::class, 'abort']);

    // HR Employees
    Route::get('/employees/stats', [EmployeeController::class, 'stats']);
    Route::post('/employees/{employee}/upload-image', [EmployeeController::class, 'uploadImage']);
    Route::apiResource('employees', EmployeeController::class);

    // HRM System (Attendance, Leaves, Payrolls)
    Route::get('/hrm/attendance/today', [HrmController::class, 'todayStatus']);
    Route::post('/hrm/attendance/clock-in', [HrmController::class, 'clockIn']);
    Route::post('/hrm/attendance/clock-out', [HrmController::class, 'clockOut']);
    Route::get('/hrm/attendances', [HrmController::class, 'attendances']);

    Route::get('/hrm/leaves', [HrmController::class, 'leaves']);
    Route::post('/hrm/leaves', [HrmController::class, 'applyLeave']);
    Route::patch('/hrm/leaves/{leave}/status', [HrmController::class, 'updateLeaveStatus']);

    Route::get('/hrm/payrolls', [HrmController::class, 'payrolls']);
    Route::post('/hrm/payrolls/process', [HrmController::class, 'processPayroll']);

    // ── Admin & Super Admin ──────────────────────────────────────────────
    Route::middleware('role:admin|superadmin')->group(function () {
        // User management
        Route::apiResource('users', UserController::class)->except(['destroy']);

        // RBAC Management
        Route::get('/rbac/roles', [RolePermissionController::class, 'getRoles']);
        Route::post('/rbac/roles', [RolePermissionController::class, 'createRole']);
        Route::get('/rbac/permissions', [RolePermissionController::class, 'getPermissions']);
        Route::patch('/rbac/roles/{role}/permissions', [RolePermissionController::class, 'syncPermissions']);
    });
});
