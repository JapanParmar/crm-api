<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Unit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    /**
     * GET /api/bookings
     */
    public function index(Request $request): JsonResponse
    {
        $query = Booking::with([
            'unit:id,unit_number,floor_number,bhk_type,status,tower_id',
            'unit.tower:id,tower_name,project_id',
            'unit.tower.project:id,name,code',
            'lead:id,lead_number,name,phone',
            'assignedTo:id,name',
        ]);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('customer_name', 'like', "%{$search}%")
                  ->orWhere('customer_phone', 'like', "%{$search}%")
                  ->orWhere('customer_email', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('agreement_status')) {
            $query->where('agreement_status', $status);
        }

        $limit    = min((int) $request->input('limit', 25), 100);
        $bookings = $query->orderBy('booking_date', 'desc')->paginate($limit);

        return response()->json([
            'success' => true,
            'data'    => $bookings->items(),
            'meta'    => [
                'page'        => $bookings->currentPage(),
                'limit'       => $bookings->perPage(),
                'total'       => $bookings->total(),
                'total_pages' => $bookings->lastPage(),
            ],
        ]);
    }

    /**
     * POST /api/bookings
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'unit_id'          => 'required|exists:units,id',
            'lead_id'          => 'nullable|exists:leads,id',
            'customer_name'    => 'required|string|max:255',
            'customer_phone'   => 'required|string|max:20',
            'customer_email'   => 'nullable|email|max:255',
            'assigned_to'      => 'nullable|exists:users,id',
            'booking_date'     => 'required|date',
            'booking_amount'   => 'required|numeric|min:0',
            'agreement_status' => 'nullable|string|in:' . implode(',', Booking::AGREEMENT_STATUSES),
            'notes'            => 'nullable|string',
        ]);

        $unit = Unit::findOrFail($validated['unit_id']);

        if (!in_array($unit->status, ['available', 'reserved', 'hold'])) {
            return response()->json([
                'success' => false,
                'message' => "Unit is currently '{$unit->status}' and cannot be booked.",
            ], 422);
        }

        $booking = Booking::create($validated);
        // Mark unit as booked
        $unit->update(['status' => 'booked']);

        $booking->load([
            'unit:id,unit_number,floor_number,bhk_type,status',
            'assignedTo:id,name',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Booking created successfully.',
            'data'    => $booking,
        ], 201);
    }

    /**
     * GET /api/bookings/{booking}
     */
    public function show(Booking $booking): JsonResponse
    {
        $booking->load([
            'unit.tower.project:id,name,code',
            'lead:id,lead_number,name,phone',
            'assignedTo:id,name',
            'payments',
        ]);

        return response()->json([
            'success' => true,
            'data'    => $booking,
        ]);
    }

    /**
     * PATCH /api/bookings/{booking}
     */
    public function update(Request $request, Booking $booking): JsonResponse
    {
        $validated = $request->validate([
            'customer_name'    => 'sometimes|required|string|max:255',
            'customer_phone'   => 'sometimes|required|string|max:20',
            'customer_email'   => 'nullable|email|max:255',
            'assigned_to'      => 'nullable|exists:users,id',
            'booking_date'     => 'sometimes|required|date',
            'booking_amount'   => 'sometimes|required|numeric|min:0',
            'agreement_status' => 'nullable|string|in:' . implode(',', Booking::AGREEMENT_STATUSES),
            'notes'            => 'nullable|string',
        ]);

        $booking->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Booking updated successfully.',
            'data'    => $booking,
        ]);
    }
}
