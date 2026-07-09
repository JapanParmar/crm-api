<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use Illuminate\Http\JsonResponse;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    /**
     * Login and return JWT with full user context.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');
        $token       = auth('api')->attempt($credentials);

        if (! $token) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials.',
            ], 401);
        }

        $user = auth('api')->user();

        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'data'    => [
                'token'      => $token,
                'token_type' => 'bearer',
                'expires_in' => auth('api')->factory()->getTTL() * 60,
                'user'       => $this->buildUserPayload($user),
            ],
        ]);
    }

    /**
     * Return authenticated user with roles, permissions, and menu access.
     */
    public function me(): JsonResponse
    {
        $user = auth('api')->user();

        return response()->json([
            'success' => true,
            'message' => 'Authenticated user retrieved.',
            'data'    => $this->buildUserPayload($user),
        ]);
    }

    /**
     * Update authenticated user's layout/styling preferences.
     */
    public function updatePreferences(\Illuminate\Http\Request $request): JsonResponse
    {
        $user = auth('api')->user();

        $validated = $request->validate([
            'preferences' => 'required|array',
        ]);

        $user->update([
            'preferences' => $validated['preferences'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Preferences updated successfully.',
            'data'    => $user->preferences,
        ]);
    }

    /**
     * Invalidate token.
     */
    public function logout(): JsonResponse
    {
        JWTAuth::invalidate(JWTAuth::getToken());

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully.',
        ]);
    }

    /**
     * Build the user payload for login/me responses.
     * Includes roles, permissions, and menu access flags for the frontend.
     */
    private function buildUserPayload($user): array
    {
        $roles       = $user->getRoleNames()->toArray();
        $permissions = $user->getAllPermissions()->pluck('name')->toArray();
        $isSuperAdmin = in_array('superadmin', $roles);
        $isAdmin     = in_array('admin', $roles) || $isSuperAdmin;

        return [
            'id'          => $user->id,
            'name'        => $user->name,
            'email'       => $user->email,
            'phone'       => $user->phone,
            'is_active'   => $user->is_active,
            'preferences' => $user->preferences,
            'roles'       => $roles,
            'permissions' => $permissions,
            // Navigation access flags — frontend uses this to render sidebar/menus
            'access'      => [
                'dashboard'         => true, // everyone
                'leads'             => true, // everyone
                'all_leads'         => $isAdmin || in_array('view-all-leads', $permissions),
                'my_leads'          => in_array('view-own-leads', $permissions),
                'import_leads'      => in_array('import-leads', $permissions),
                'assign_leads'      => in_array('assign-leads', $permissions),
                'follow_ups'        => true, // everyone
                'site_visits'       => true, // everyone
                'users'             => $isAdmin || in_array('manage-users', $permissions),
                'activity_log'      => in_array('view-activity-log', $permissions),
                'settings'          => $isAdmin,
                'rbac'              => $isAdmin || in_array('manage-rbac', $permissions),
            ],
        ];
    }
}
