<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Tower;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TowerController extends Controller
{
    /**
     * GET /api/projects/{project}/towers
     */
    public function index(Project $project): JsonResponse
    {
        $towers = $project->towers()
            ->withCount(['units', 'units as available_units_count' => fn ($q) => $q->where('status', 'available')])
            ->orderBy('tower_name')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $towers,
        ]);
    }

    /**
     * POST /api/projects/{project}/towers
     */
    public function store(Request $request, Project $project): JsonResponse
    {
        $validated = $request->validate([
            'tower_name'      => 'required|string|max:255',
            'total_floors'    => 'required|integer|min:1',
            'units_per_floor' => 'required|integer|min:1',
            'has_lift'        => 'nullable|boolean',
            'parking_details' => 'nullable|string|max:255',
        ]);

        $tower = $project->towers()->create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Tower created successfully.',
            'data'    => $tower,
        ], 201);
    }

    /**
     * GET /api/towers/{tower}
     */
    public function show(Tower $tower): JsonResponse
    {
        $tower->load('project:id,name,code');
        $tower->loadCount(['units', 'units as available_units_count' => fn ($q) => $q->where('status', 'available')]);

        return response()->json([
            'success' => true,
            'data'    => $tower,
        ]);
    }

    /**
     * PATCH /api/towers/{tower}
     */
    public function update(Request $request, Tower $tower): JsonResponse
    {
        $validated = $request->validate([
            'tower_name'      => 'sometimes|required|string|max:255',
            'total_floors'    => 'sometimes|required|integer|min:1',
            'units_per_floor' => 'sometimes|required|integer|min:1',
            'has_lift'        => 'nullable|boolean',
            'parking_details' => 'nullable|string|max:255',
        ]);

        $tower->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Tower updated successfully.',
            'data'    => $tower,
        ]);
    }

    /**
     * DELETE /api/towers/{tower}
     */
    public function destroy(Tower $tower): JsonResponse
    {
        if ($tower->units()->whereIn('status', ['booked', 'sold'])->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete tower with booked or sold units.',
            ], 422);
        }

        $tower->delete();

        return response()->json([
            'success' => true,
            'message' => 'Tower deleted successfully.',
        ]);
    }
}
