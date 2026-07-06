<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $guard = 'api';

        // All CRM permissions
        $allPermissions = [
            // User Management
            'manage-users',
            
            // RBAC Management
            'manage-rbac',

            // Lead Management
            'view-all-leads',
            'create-leads',
            'update-leads',
            'delete-leads',
            'import-leads',
            'assign-leads',

            // Employee Lead Access
            'view-own-leads',

            // Follow-ups
            'create-followups',
            'update-followups',

            // Site Visits
            'create-site-visits',
            'update-site-visits',

            // Dashboard & Logs
            'view-dashboard',
            'view-activity-log',
        ];

        foreach ($allPermissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => $guard]);
        }

        // ── Super Admin Role ──────────────────────────────────────────────────
        $superAdmin = Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => $guard]);
        $superAdmin->syncPermissions($allPermissions);

        // ── Admin Role ────────────────────────────────────────────────────────
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => $guard]);
        $admin->syncPermissions([
            'manage-users',
            'manage-rbac',
            'view-all-leads',
            'create-leads',
            'update-leads',
            'delete-leads',
            'import-leads',
            'assign-leads',
            'create-followups',
            'update-followups',
            'create-site-visits',
            'update-site-visits',
            'view-dashboard',
            'view-activity-log',
        ]);

        // ── Employee Role ─────────────────────────────────────────────────────
        $employee = Role::firstOrCreate(['name' => 'employee', 'guard_name' => $guard]);
        $employee->syncPermissions([
            'view-own-leads',
            'create-followups',
            'update-followups',
            'create-site-visits',
            'update-site-visits',
            'view-dashboard',
        ]);

        // Future roles can be added here:
        // $manager = Role::firstOrCreate(['name' => 'sales-manager', 'guard_name' => $guard]);
        // $manager->syncPermissions([...]);
    }
}
