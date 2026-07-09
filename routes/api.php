<?php

use App\Http\Controllers\Api\ActivityLogController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\FollowUpController;
use App\Http\Controllers\Api\LeadController;
use App\Http\Controllers\Api\RolePermissionController;
use App\Http\Controllers\Api\SiteVisitController;
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
    Route::post('/logout', [AuthController::class, 'logout']);

    // Dashboard (role-aware response)
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // Leads
    Route::get('/leads/counts', [LeadController::class, 'counts']);
    Route::patch('/leads/bulk-assign', [LeadController::class, 'bulkAssign']);
    Route::post('/leads/check-duplicates', [LeadController::class, 'checkDuplicates']);
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

    // ── Admin & Super Admin ──────────────────────────────────────────────
    Route::middleware('role:admin|superadmin')->group(function () {
        // User management
        Route::get('/users/employees', [UserController::class, 'employees']);
        Route::apiResource('users', UserController::class)->except(['destroy']);

        // RBAC Management
        Route::get('/rbac/roles', [RolePermissionController::class, 'getRoles']);
        Route::post('/rbac/roles', [RolePermissionController::class, 'createRole']);
        Route::get('/rbac/permissions', [RolePermissionController::class, 'getPermissions']);
        Route::patch('/rbac/roles/{role}/permissions', [RolePermissionController::class, 'syncPermissions']);
    });
});
