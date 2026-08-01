<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    /**
     * GET /api/employees
     */
    public function index(Request $request): JsonResponse
    {
        $query = Employee::with('user:id,name,email');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('employee_code', 'like', "%{$search}%")
                  ->orWhere('designation', 'like', "%{$search}%");
            });
        }

        if ($department = $request->input('department')) {
            $departments = is_array($department) ? $department : explode(',', $department);
            $query->whereIn('department', $departments);
        }

        if ($status = $request->input('status')) {
            $statuses = is_array($status) ? $status : explode(',', $status);
            $query->whereIn('status', $statuses);
        }

        if ($type = $request->input('employment_type')) {
            $types = is_array($type) ? $type : explode(',', $type);
            $query->whereIn('employment_type', $types);
        }

        $sortBy  = in_array($request->input('sort_by'), ['created_at', 'first_name', 'employee_code', 'department', 'joining_date', 'salary'])
            ? $request->input('sort_by')
            : 'created_at';
        $sortDir = $request->input('sort_dir', 'desc') === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortBy, $sortDir);

        $limit = min((int) $request->input('limit', 25), 100);
        $employees = $query->paginate($limit);

        return response()->json([
            'success' => true,
            'message' => 'Employees retrieved successfully.',
            'data'    => $employees->items(),
            'meta'    => [
                'page'        => $employees->currentPage(),
                'limit'       => $employees->perPage(),
                'total'       => $employees->total(),
                'total_pages' => $employees->lastPage(),
            ],
        ]);
    }

    /**
     * GET /api/employees/stats
     */
    public function stats(): JsonResponse
    {
        $byDept = Employee::selectRaw('department, count(*) as count')
            ->groupBy('department')
            ->pluck('count', 'department');

        return response()->json([
            'success' => true,
            'data'    => [
                'all'            => Employee::count(),
                'active'         => Employee::where('status', 'active')->count(),
                'on_leave'       => Employee::where('status', 'on_leave')->count(),
                'full_time'      => Employee::where('employment_type', 'full_time')->count(),
                'total_payroll'  => (float) Employee::where('status', 'active')->sum('salary'),
                'departments'    => $byDept,
            ],
        ]);
    }

    /**
     * POST /api/employees
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id'                 => 'nullable|exists:users,id',
            'employee_code'           => 'required|string|max:50|unique:employees,employee_code',
            'first_name'              => 'required|string|max:255',
            'last_name'               => 'required|string|max:255',
            'email'                   => 'required|email|max:255|unique:employees,email',
            'phone'                   => 'required|string|max:20',
            'department'              => 'required|string|in:' . implode(',', Employee::DEPARTMENTS),
            'designation'             => 'required|string|max:255',
            'employment_type'         => 'required|string|in:' . implode(',', Employee::EMPLOYMENT_TYPES),
            'status'                  => 'required|string|in:' . implode(',', Employee::STATUSES),
            'joining_date'            => 'required|date',
            'salary'                  => 'nullable|numeric|min:0',
            'pan_number'              => 'nullable|string|max:20',
            'aadhar_number'           => 'nullable|string|max:20',
            'emergency_contact_name'  => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:20',
            'address'                 => 'nullable|string',
            'bank_name'               => 'nullable|string|max:255',
            'account_number'          => 'nullable|string|max:50',
            'ifsc_code'               => 'nullable|string|max:20',
            'notes'                   => 'nullable|string',
            'work_latitude'           => 'nullable|numeric|between:-90,90',
            'work_longitude'          => 'nullable|numeric|between:-180,180',
            'hra'                     => 'nullable|numeric|min:0',
            'allowances'              => 'nullable|numeric|min:0',
            'deductions'              => 'nullable|numeric|min:0',
        ]);

        $employee = Employee::create($validated);
        $employee->load('user:id,name,email');

        return response()->json([
            'success' => true,
            'message' => 'Employee record created successfully.',
            'data'    => $employee,
        ], 201);
    }

    /**
     * GET /api/employees/{employee}
     */
    public function show(Employee $employee): JsonResponse
    {
        $employee->load('user:id,name,email');

        return response()->json([
            'success' => true,
            'data'    => $employee,
        ]);
    }

    /**
     * PATCH /api/employees/{employee}
     */
    public function update(Request $request, Employee $employee): JsonResponse
    {
        $validated = $request->validate([
            'user_id'                 => 'nullable|exists:users,id',
            'employee_code'           => 'sometimes|required|string|max:50|unique:employees,employee_code,' . $employee->id,
            'first_name'              => 'sometimes|required|string|max:255',
            'last_name'               => 'sometimes|required|string|max:255',
            'email'                   => 'sometimes|required|email|max:255|unique:employees,email,' . $employee->id,
            'phone'                   => 'sometimes|required|string|max:20',
            'department'              => 'sometimes|required|string|in:' . implode(',', Employee::DEPARTMENTS),
            'designation'             => 'sometimes|required|string|max:255',
            'employment_type'         => 'sometimes|required|string|in:' . implode(',', Employee::EMPLOYMENT_TYPES),
            'status'                  => 'sometimes|required|string|in:' . implode(',', Employee::STATUSES),
            'joining_date'            => 'sometimes|required|date',
            'salary'                  => 'nullable|numeric|min:0',
            'pan_number'              => 'nullable|string|max:20',
            'aadhar_number'           => 'nullable|string|max:20',
            'emergency_contact_name'  => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:20',
            'address'                 => 'nullable|string',
            'bank_name'               => 'nullable|string|max:255',
            'account_number'          => 'nullable|string|max:50',
            'ifsc_code'               => 'nullable|string|max:20',
            'notes'                   => 'nullable|string',
            'work_latitude'           => 'nullable|numeric|between:-90,90',
            'work_longitude'          => 'nullable|numeric|between:-180,180',
            'hra'                     => 'nullable|numeric|min:0',
            'allowances'              => 'nullable|numeric|min:0',
            'deductions'              => 'nullable|numeric|min:0',
        ]);

        $employee->update($validated);
        $employee->load('user:id,name,email');

        return response()->json([
            'success' => true,
            'message' => 'Employee record updated successfully.',
            'data'    => $employee,
        ]);
    }

    /**
     * DELETE /api/employees/{employee}
     */
    public function destroy(Employee $employee): JsonResponse
    {
        $employee->delete();

        return response()->json([
            'success' => true,
            'message' => 'Employee deleted successfully.',
        ]);
    }

    /**
     * POST /api/employees/{employee}/upload-image
     */
    public function uploadImage(Request $request, Employee $employee): JsonResponse
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ]);

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $path = $file->store('avatars', 'public');
            $url = asset('storage/' . $path);

            if ($employee->profile_image) {
                $storagePrefix = asset('storage/');
                if (str_starts_with($employee->profile_image, $storagePrefix)) {
                    $relativePath = str_replace($storagePrefix . '/', '', $employee->profile_image);
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($relativePath);
                }
            }

            $employee->profile_image = $url;
            $employee->save();

            if ($employee->user_id) {
                $user = \App\Models\User::find($employee->user_id);
                if ($user) {
                    $user->profile_image = $url;
                    $user->save();
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Profile image uploaded successfully.',
                'url' => $url,
                'data' => $employee->load('user:id,name,email'),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'No image file provided.',
        ], 400);
    }
}
