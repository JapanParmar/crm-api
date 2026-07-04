<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function __construct(private DashboardService $dashboard) {}

    /**
     * GET /api/users
     * Admin only — list all users with performance stats.
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::with('roles');

        if ($role = $request->input('role')) {
            $query->role($role, 'api');
        }

        if ($request->input('active') !== null) {
            $query->where('is_active', (bool) $request->input('active'));
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->orderBy('name')->get();

        // Attach performance metrics
        $data = $users->map(function (User $user) {
            $roles = $user->getRoleNames();
            return [
                'id'                 => $user->id,
                'name'               => $user->name,
                'email'              => $user->email,
                'phone'              => $user->phone,
                'is_active'          => $user->is_active,
                'roles'              => $roles,
                'assigned_leads'     => \App\Models\Lead::where('assigned_to', $user->id)->count(),
                'closed_deals'       => \App\Models\Lead::where('assigned_to', $user->id)->where('status', 'closed_won')->count(),
                'pending_follow_ups' => \App\Models\FollowUp::where('assigned_to', $user->id)->where('status', 'scheduled')->count(),
                'created_at'         => $user->created_at,
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    /**
     * GET /api/users/employees
     * Lightweight list for dropdowns (assign lead, etc.)
     */
    public function employees(): JsonResponse
    {
        $employees = User::role('employee', 'api')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'phone']);

        return response()->json([
            'success' => true,
            'data'    => $employees,
        ]);
    }

    /**
     * POST /api/users
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = User::create([
            'name'      => $request->name,
            'email'     => $request->email,
            'password'  => Hash::make($request->password),
            'phone'     => $request->phone,
            'is_active' => $request->boolean('is_active', true),
        ]);

        $user->assignRole($request->role);

        return response()->json([
            'success' => true,
            'message' => 'User created successfully.',
            'data'    => new UserResource($user->load('roles')),
        ], 201);
    }

    /**
     * GET /api/users/{user}
     */
    public function show(User $user): JsonResponse
    {
        $user->load('roles');

        return response()->json([
            'success' => true,
            'data'    => new UserResource($user),
        ]);
    }

    /**
     * PATCH /api/users/{user}
     */
    public function update(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'name'      => ['sometimes', 'string', 'max:255'],
            'phone'     => ['nullable', 'string', 'max:20'],
            'is_active' => ['sometimes', 'boolean'],
            'role'      => ['sometimes', 'string', 'exists:roles,name'],
            'password'  => ['sometimes', 'string', 'min:8'],
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        if (isset($validated['role'])) {
            $user->syncRoles([$validated['role']]);
            unset($validated['role']);
        }

        $user->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'User updated.',
            'data'    => new UserResource($user->load('roles')),
        ]);
    }
}
