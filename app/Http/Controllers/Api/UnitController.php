<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tower;
use App\Models\Unit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    /**
     * GET /api/towers/{tower}/units
     */
    public function index(Request $request, Tower $tower): JsonResponse
    {
        $query = $tower->units()->with('tower:id,tower_name');

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($bhk = $request->input('bhk_type')) {
            $query->where('bhk_type', $bhk);
        }

        $units = $query->orderBy('floor_number')->orderBy('unit_number')->get();

        return response()->json([
            'success' => true,
            'data'    => $units,
        ]);
    }

    /**
     * POST /api/towers/{tower}/units
     */
    public function store(Request $request, Tower $tower): JsonResponse
    {
        $validated = $request->validate([
            'unit_number'         => 'required|string|max:50',
            'floor_number'        => 'required|integer|min:0',
            'bhk_type'            => 'required|string|in:' . implode(',', Unit::BHK_TYPES),
            'carpet_area'         => 'nullable|numeric|min:0',
            'built_up_area'       => 'nullable|numeric|min:0',
            'super_built_up_area' => 'nullable|numeric|min:0',
            'facing'              => 'nullable|string|in:' . implode(',', Unit::FACINGS),
            'base_price'          => 'nullable|numeric|min:0',
            'price_per_sqft'      => 'nullable|numeric|min:0',
            'floor_rise_charges'  => 'nullable|numeric|min:0',
            'plc_charges'         => 'nullable|numeric|min:0',
            'parking_charges'     => 'nullable|numeric|min:0',
            'club_house_charges'  => 'nullable|numeric|min:0',
            'gst_amount'          => 'nullable|numeric|min:0',
            'total_price'         => 'nullable|numeric|min:0',
            'status'              => 'nullable|string|in:' . implode(',', Unit::STATUSES),
        ]);

        $validated['project_id'] = $tower->project_id;

        $unit = $tower->units()->create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Unit created successfully.',
            'data'    => $unit,
        ], 201);
    }

    /**
     * GET /api/units/{unit}
     */
    public function show(Unit $unit): JsonResponse
    {
        $unit->load([
            'tower:id,tower_name,project_id',
            'project:id,name,code',
            'bookings' => fn ($q) => $q->with('assignedTo:id,name')->orderBy('created_at', 'desc')->limit(5),
        ]);

        return response()->json([
            'success' => true,
            'data'    => $unit,
        ]);
    }

    /**
     * PATCH /api/units/{unit}
     */
    public function update(Request $request, Unit $unit): JsonResponse
    {
        $validated = $request->validate([
            'unit_number'         => 'sometimes|required|string|max:50',
            'floor_number'        => 'sometimes|required|integer|min:0',
            'bhk_type'            => 'sometimes|required|string|in:' . implode(',', Unit::BHK_TYPES),
            'carpet_area'         => 'nullable|numeric|min:0',
            'built_up_area'       => 'nullable|numeric|min:0',
            'super_built_up_area' => 'nullable|numeric|min:0',
            'facing'              => 'nullable|string|in:' . implode(',', Unit::FACINGS),
            'base_price'          => 'nullable|numeric|min:0',
            'price_per_sqft'      => 'nullable|numeric|min:0',
            'floor_rise_charges'  => 'nullable|numeric|min:0',
            'plc_charges'         => 'nullable|numeric|min:0',
            'parking_charges'     => 'nullable|numeric|min:0',
            'club_house_charges'  => 'nullable|numeric|min:0',
            'gst_amount'          => 'nullable|numeric|min:0',
            'total_price'         => 'nullable|numeric|min:0',
            'status'              => 'nullable|string|in:' . implode(',', Unit::STATUSES),
        ]);

        $unit->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Unit updated successfully.',
            'data'    => $unit,
        ]);
    }

    /**
     * PATCH /api/units/{unit}/status
     */
    public function changeStatus(Request $request, Unit $unit): JsonResponse
    {
        $request->validate([
            'status' => 'required|string|in:' . implode(',', Unit::STATUSES),
        ]);

        $unit->update(['status' => $request->input('status')]);

        return response()->json([
            'success' => true,
            'message' => 'Unit status updated.',
            'data'    => $unit,
        ]);
    }

    /**
     * DELETE /api/units/{unit}
     */
    public function destroy(Unit $unit): JsonResponse
    {
        if (in_array($unit->status, ['booked', 'sold'])) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete a booked or sold unit.',
            ], 422);
        }

        $unit->delete();

        return response()->json([
            'success' => true,
            'message' => 'Unit deleted successfully.',
        ]);
    }
}
