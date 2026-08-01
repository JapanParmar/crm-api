<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectConfiguration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectConfigurationController extends Controller
{
    /**
     * GET /api/projects/{project}/configurations
     */
    public function index(Project $project): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $project->configurations()->orderBy('bhk_type')->get(),
        ]);
    }

    /**
     * POST /api/projects/{project}/configurations
     */
    public function store(Request $request, Project $project): JsonResponse
    {
        $validated = $request->validate([
            'bhk_type'        => 'required|string|in:' . implode(',', ProjectConfiguration::BHK_TYPES),
            'carpet_area_min' => 'nullable|numeric|min:0',
            'carpet_area_max' => 'nullable|numeric|min:0',
            'price_from'      => 'nullable|numeric|min:0',
            'price_to'        => 'nullable|numeric|min:0',
        ]);

        $config = $project->configurations()->create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Configuration added.',
            'data'    => $config,
        ], 201);
    }

    /**
     * PATCH /api/projects/{project}/configurations/{configuration}
     */
    public function update(Request $request, Project $project, ProjectConfiguration $configuration): JsonResponse
    {
        $validated = $request->validate([
            'bhk_type'        => 'sometimes|required|string|in:' . implode(',', ProjectConfiguration::BHK_TYPES),
            'carpet_area_min' => 'nullable|numeric|min:0',
            'carpet_area_max' => 'nullable|numeric|min:0',
            'price_from'      => 'nullable|numeric|min:0',
            'price_to'        => 'nullable|numeric|min:0',
        ]);

        $configuration->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Configuration updated.',
            'data'    => $configuration,
        ]);
    }

    /**
     * DELETE /api/projects/{project}/configurations/{configuration}
     */
    public function destroy(Project $project, ProjectConfiguration $configuration): JsonResponse
    {
        $configuration->delete();

        return response()->json([
            'success' => true,
            'message' => 'Configuration deleted.',
        ]);
    }
}
