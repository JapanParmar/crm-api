<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionController extends Controller
{
    /**
     * Get all roles along with their assigned permissions.
     */
    public function getRoles(): JsonResponse
    {
        $roles = Role::with('permissions')->get();

        $data = $roles->map(function (Role $role) {
            return [
                'id'          => $role->id,
                'name'        => $role->name,
                'permissions' => $role->permissions->pluck('name')->toArray(),
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    /**
     * Get all available permissions.
     */
    public function getPermissions(): JsonResponse
    {
        $permissions = Permission::all()->pluck('name')->toArray();

        return response()->json([
            'success' => true,
            'data'    => $permissions,
        ]);
    }

    /**
     * Create a new role.
     */
    public function createRole(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|unique:roles,name',
        ]);

        $role = Role::create([
            'name'       => strtolower($request->name),
            'guard_name' => 'api',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Role created successfully.',
            'data'    => [
                'id'          => $role->id,
                'name'        => $role->name,
                'permissions' => [],
            ],
        ], 201);
    }

    /**
     * Sync permissions to a specific role.
     */
    public function syncPermissions(Request $request, Role $role): JsonResponse
    {
        $request->validate([
            'permissions'   => 'required|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        // Prevent modifying the superadmin role if not authorized
        if ($role->name === 'superadmin' && !auth('api')->user()->hasRole('superadmin')) {
            return response()->json([
                'success' => false,
                'message' => 'Only a Super Admin can modify the superadmin role.',
            ], 403);
        }

        $role->syncPermissions($request->permissions);

        return response()->json([
            'success' => true,
            'message' => "Permissions for role '{$role->name}' updated successfully.",
            'data'    => [
                'id'          => $role->id,
                'name'        => $role->name,
                'permissions' => $role->permissions()->pluck('name')->toArray(),
            ],
        ]);
    }
}
