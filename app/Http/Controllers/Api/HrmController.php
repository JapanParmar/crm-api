<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\Payroll;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HrmController extends Controller
{
    /**
     * Get employee record associated with current user or fallback to employee_id param
     */
    private function resolveEmployee(Request $request)
    {
        $user = $request->user();
        if ($user) {
            $employee = Employee::where('user_id', $user->id)->orWhere('email', $user->email)->first();
            if ($employee) return $employee;
        }
        
        if ($request->has('employee_id')) {
            return Employee::find($request->employee_id);
        }

        return null;
    }

    // =========================================================================
    // ATTENDANCE
    // =========================================================================

    public function todayStatus(Request $request): JsonResponse
    {
        $employee = $this->resolveEmployee($request);
        $date = Carbon::today()->format('Y-m-d');

        if (!$employee) {
            return response()->json([
                'success' => true,
                'data' => [
                    'has_employee_profile' => false,
                    'attendance' => null,
                ],
            ]);
        }

        $attendance = Attendance::where('employee_id', $employee->id)
            ->where('date', $date)
            ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'has_employee_profile' => true,
                'employee' => $employee,
                'attendance' => $attendance,
            ],
        ]);
    }

    /**
     * Calculate distance between two coordinate points using the Haversine formula (in meters)
     */
    private function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000; // in meters

        $latFrom = deg2rad($lat1);
        $lonFrom = deg2rad($lon1);
        $latTo = deg2rad($lat2);
        $lonTo = deg2rad($lon2);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));

        return $angle * $earthRadius;
    }

    public function clockIn(Request $request): JsonResponse
    {
        $employee = $this->resolveEmployee($request);
        if (!$employee) {
            return response()->json(['success' => false, 'message' => 'No employee profile linked to your user account.'], 404);
        }

        $today = Carbon::today()->format('Y-m-d');
        $now = Carbon::now();
        $timeStr = $now->format('H:i:s');

        // GPS Geofence Check
        $latitude = $request->input('latitude');
        $longitude = $request->input('longitude');

        $targetLat = $employee->work_latitude ?? 23.0225; // default Ahmedabad Office
        $targetLon = $employee->work_longitude ?? 72.5714;
        $allowedRadius = 500; // 500 meters geofence

        if ($latitude !== null && $longitude !== null) {
            $distance = $this->calculateDistance((float)$latitude, (float)$longitude, (float)$targetLat, (float)$targetLon);
            if ($distance > $allowedRadius) {
                return response()->json([
                    'success' => false,
                    'message' => 'Clock-in failed: You are outside the authorized work boundary. Distance: ' . round($distance) . 'm. Allowed radius: ' . $allowedRadius . 'm.'
                ], 400);
            }
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Clock-in failed: GPS coordinates are required to verify your location.'
            ], 400);
        }

        $attendance = Attendance::firstOrCreate(
            ['employee_id' => $employee->id, 'date' => $today],
            [
                'user_id' => $request->user()?->id,
                'clock_in' => $timeStr,
                'status' => $now->hour >= 10 ? 'late' : 'present',
                'ip_address' => $request->ip(),
                'latitude' => $latitude,
                'longitude' => $longitude,
                'notes' => $request->notes,
            ]
        );

        if (!$attendance->wasRecentlyCreated && !$attendance->clock_in) {
            $attendance->update([
                'clock_in' => $timeStr,
                'status' => $now->hour >= 10 ? 'late' : 'present',
                'latitude' => $latitude,
                'longitude' => $longitude,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Clocked in successfully at ' . $now->format('h:i A'),
            'data' => $attendance,
        ]);
    }

    public function clockOut(Request $request): JsonResponse
    {
        $employee = $this->resolveEmployee($request);
        if (!$employee) {
            return response()->json(['success' => false, 'message' => 'No employee profile linked to your user account.'], 404);
        }

        $today = Carbon::today()->format('Y-m-d');
        $attendance = Attendance::where('employee_id', $employee->id)
            ->where('date', $today)
            ->first();

        if (!$attendance) {
            return response()->json(['success' => false, 'message' => 'No clock-in record found for today.'], 400);
        }

        $now = Carbon::now();
        $clockInTime = Carbon::createFromFormat('H:i:s', $attendance->clock_in);
        $hours = round($clockInTime->diffInMinutes($now) / 60, 2);

        $attendance->update([
            'clock_out' => $now->format('H:i:s'),
            'work_hours' => $hours,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Clocked out successfully at ' . $now->format('h:i A') . " ({$hours} hrs)",
            'data' => $attendance,
        ]);
    }

    public function attendances(Request $request): JsonResponse
    {
        $user = $request->user();
        $isHR = $user->hasRole(['admin', 'super-admin']) || $user->can('manage-users');

        $query = Attendance::with(['employee', 'user']);

        if (!$isHR) {
            $emp = $this->resolveEmployee($request);
            if ($emp) {
                $query->where('employee_id', $emp->id);
            } else {
                $query->where('user_id', $user->id);
            }
        } elseif ($request->has('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('date')) {
            $query->where('date', $request->date);
        }

        $attendances = $query->orderBy('date', 'desc')->paginate($request->get('limit', 15));

        return response()->json([
            'success' => true,
            'data' => $attendances->items(),
            'meta' => [
                'current_page' => $attendances->currentPage(),
                'total_pages'  => $attendances->lastPage(),
                'total'        => $attendances->total(),
            ],
        ]);
    }

    // =========================================================================
    // LEAVE MANAGEMENT
    // =========================================================================

    public function leaves(Request $request): JsonResponse
    {
        $user = $request->user();
        $isHR = $user->hasRole(['admin', 'super-admin']) || $user->can('manage-users');

        $query = Leave::with(['employee', 'approver']);

        if (!$isHR) {
            $emp = $this->resolveEmployee($request);
            if ($emp) {
                $query->where('employee_id', $emp->id);
            } else {
                $query->where('user_id', $user->id);
            }
        } elseif ($request->has('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $leaves = $query->orderBy('created_at', 'desc')->paginate($request->get('limit', 15));

        return response()->json([
            'success' => true,
            'data' => $leaves->items(),
            'meta' => [
                'current_page' => $leaves->currentPage(),
                'total_pages'  => $leaves->lastPage(),
                'total'        => $leaves->total(),
            ],
        ]);
    }

    public function applyLeave(Request $request): JsonResponse
    {
        $request->validate([
            'leave_type' => 'required|in:casual,sick,earned,unpaid',
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
            'reason'     => 'required|string',
        ]);

        $employee = $this->resolveEmployee($request);
        if (!$employee) {
            return response()->json(['success' => false, 'message' => 'No employee profile linked to account.'], 404);
        }

        $start = Carbon::parse($request->start_date);
        $end = Carbon::parse($request->end_date);
        $days = $start->diffInDays($end) + 1;

        $leave = Leave::create([
            'user_id'     => $request->user()?->id,
            'employee_id' => $employee->id,
            'leave_type'  => $request->leave_type,
            'start_date'  => $request->start_date,
            'end_date'    => $request->end_date,
            'days_count'  => $days,
            'reason'      => $request->reason,
            'status'      => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Leave application submitted successfully.',
            'data'    => $leave->load('employee'),
        ], 201);
    }

    public function updateLeaveStatus(Request $request, Leave $leave): JsonResponse
    {
        $user = $request->user();
        if (!$user->hasRole(['admin', 'super-admin']) && !$user->can('manage-users')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized to approve/reject leaves.'], 403);
        }

        $request->validate([
            'status'      => 'required|in:approved,rejected,pending',
            'admin_notes' => 'nullable|string',
        ]);

        $leave->update([
            'status'      => $request->status,
            'approved_by' => $user->id,
            'admin_notes' => $request->admin_notes,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Leave status updated to ' . $request->status,
            'data'    => $leave->load(['employee', 'approver']),
        ]);
    }

    // =========================================================================
    // PAYROLL / SALARY MANAGEMENT
    // =========================================================================

    public function payrolls(Request $request): JsonResponse
    {
        $user = $request->user();
        $isHR = $user->hasRole(['admin', 'super-admin']) || $user->can('manage-users');

        $query = Payroll::with('employee');

        if (!$isHR) {
            $emp = $this->resolveEmployee($request);
            if ($emp) {
                $query->where('employee_id', $emp->id);
            } else {
                return response()->json(['success' => true, 'data' => [], 'meta' => ['current_page' => 1, 'total_pages' => 1, 'total' => 0]]);
            }
        } elseif ($request->has('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->has('month')) {
            $query->where('month', $request->month);
        }
        if ($request->has('year')) {
            $query->where('year', $request->year);
        }

        $payrolls = $query->orderBy('year', 'desc')->orderBy('month', 'desc')->paginate($request->get('limit', 15));

        return response()->json([
            'success' => true,
            'data' => $payrolls->items(),
            'meta' => [
                'current_page' => $payrolls->currentPage(),
                'total_pages'  => $payrolls->lastPage(),
                'total'        => $payrolls->total(),
            ],
        ]);
    }

    public function processPayroll(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user->hasRole(['admin', 'super-admin']) && !$user->can('manage-users')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized to process payroll.'], 403);
        }

        $request->validate([
            'month' => 'required|integer|between:1,12',
            'year'  => 'required|integer',
        ]);

        $month = (int)$request->month;
        $year = (int)$request->year;

        $employees = Employee::where('status', 'active')->get();
        $count = 0;

        foreach ($employees as $emp) {
            $baseSalary = $emp->salary ?? 50000;
            $hra = $emp->hra !== null ? (float)$emp->hra : round($baseSalary * 0.20, 2);
            $allowances = $emp->allowances !== null ? (float)$emp->allowances : round($baseSalary * 0.10, 2);
            $deductions = $emp->deductions !== null ? (float)$emp->deductions : round($baseSalary * 0.05, 2);
            $netSalary = $baseSalary + $hra + $allowances - $deductions;

            Payroll::updateOrCreate(
                ['employee_id' => $emp->id, 'month' => $month, 'year' => $year],
                [
                    'basic_salary' => $baseSalary,
                    'hra'          => $hra,
                    'allowances'   => $allowances,
                    'deductions'   => $deductions,
                    'net_salary'   => $netSalary,
                    'status'       => 'paid',
                    'payment_date' => Carbon::now()->format('Y-m-d'),
                    'payment_method' => 'Bank Transfer',
                    'notes'        => "Automated monthly payroll disbursement for " . Carbon::create()->month($month)->format('F') . " {$year}",
                ]
            );
            $count++;
        }

        return response()->json([
            'success' => true,
            'message' => "Successfully processed payroll for {$count} active employees for " . Carbon::create()->month($month)->format('F') . " {$year}.",
        ]);
    }
}
