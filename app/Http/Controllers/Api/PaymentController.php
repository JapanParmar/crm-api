<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PaymentController extends Controller
{
    /**
     * GET /api/bookings/{booking}/payments
     */
    public function index(Booking $booking): JsonResponse
    {
        $payments = $booking->payments()->orderBy('due_date')->get();

        $summary = [
            'total_due'  => $payments->sum('amount'),
            'total_paid' => $payments->where('payment_status', 'paid')->sum('amount'),
            'pending'    => $payments->where('payment_status', 'pending')->sum('amount'),
            'overdue'    => $payments->where('payment_status', 'overdue')->sum('amount'),
        ];

        return response()->json([
            'success' => true,
            'data'    => $payments,
            'summary' => $summary,
        ]);
    }

    /**
     * POST /api/bookings/{booking}/payments
     */
    public function store(Request $request, Booking $booking): JsonResponse
    {
        $validated = $request->validate([
            'payment_type'   => 'required|string|in:' . implode(',', Payment::PAYMENT_TYPES),
            'amount'         => 'required|numeric|min:0',
            'due_date'       => 'nullable|date',
            'paid_date'      => 'nullable|date',
            'payment_status' => 'nullable|string|in:' . implode(',', Payment::PAYMENT_STATUSES),
            'notes'          => 'nullable|string',
        ]);

        $validated['booking_id'] = $booking->id;

        $payment = Payment::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Payment record created successfully.',
            'data'    => $payment,
        ], 201);
    }

    /**
     * PATCH /api/payments/{payment}
     */
    public function update(Request $request, Payment $payment): JsonResponse
    {
        $validated = $request->validate([
            'payment_type'   => 'sometimes|required|string|in:' . implode(',', Payment::PAYMENT_TYPES),
            'amount'         => 'sometimes|required|numeric|min:0',
            'due_date'       => 'nullable|date',
            'paid_date'      => 'nullable|date',
            'payment_status' => 'nullable|string|in:' . implode(',', Payment::PAYMENT_STATUSES),
            'receipt'        => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:10240',
            'notes'          => 'nullable|string',
        ]);

        if ($request->hasFile('receipt')) {
            $path = $request->file('receipt')->store("receipts/{$payment->booking_id}", 'public');
            $validated['receipt_url'] = asset('storage/' . $path);
        }

        unset($validated['receipt']);
        $payment->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Payment updated successfully.',
            'data'    => $payment,
        ]);
    }
}
