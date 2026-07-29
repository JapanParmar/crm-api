<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    /**
     * GET /api/projects
     */
    public function index(Request $request): JsonResponse
    {
        $query = Project::with('manager:id,name,email', 'createdBy:id,name');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%")
                  ->orWhere('developer', 'like', "%{$search}%");
            });
        }

        if ($type = $request->input('type')) {
            $types = is_array($type) ? $type : explode(',', $type);
            $query->whereIn('type', $types);
        }

        if ($status = $request->input('status')) {
            $statuses = is_array($status) ? $status : explode(',', $status);
            $query->whereIn('status', $statuses);
        }

        if ($city = $request->input('city')) {
            $query->where('city', $city);
        }

        $sortBy  = in_array($request->input('sort_by'), ['created_at', 'name', 'code', 'budget', 'total_units'])
            ? $request->input('sort_by')
            : 'created_at';
        $sortDir = $request->input('sort_dir', 'desc') === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortBy, $sortDir);

        $limit = min((int) $request->input('limit', 25), 100);
        $projects = $query->paginate($limit);

        return response()->json([
            'success' => true,
            'message' => 'Projects retrieved successfully.',
            'data'    => $projects->items(),
            'meta'    => [
                'page'        => $projects->currentPage(),
                'limit'       => $projects->perPage(),
                'total'       => $projects->total(),
                'total_pages' => $projects->lastPage(),
            ],
        ]);
    }

    /**
     * GET /api/projects/counts
     */
    public function counts(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => [
                'all'                => Project::count(),
                'active'             => Project::where('status', 'active')->count(),
                'under_construction' => Project::where('status', 'under_construction')->count(),
                'completed'          => Project::where('status', 'completed')->count(),
                'total_units'        => (int) Project::sum('total_units'),
                'available_units'    => (int) Project::sum('available_units'),
                'sold_units'         => (int) Project::sum('sold_units'),
            ],
        ]);
    }

    /**
     * POST /api/projects
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'            => 'required|string|max:255',
            'code'            => 'required|string|max:50|unique:projects,code',
            'type'            => 'required|string|in:' . implode(',', Project::TYPES),
            'status'          => 'required|string|in:' . implode(',', Project::STATUSES),
            'location'        => 'nullable|string|max:255',
            'city'            => 'nullable|string|max:255',
            'developer'       => 'nullable|string|max:255',
            'budget'          => 'nullable|numeric|min:0',
            'total_units'     => 'nullable|integer|min:0',
            'available_units' => 'nullable|integer|min:0',
            'sold_units'      => 'nullable|integer|min:0',
            'price_min'       => 'nullable|numeric|min:0',
            'price_max'       => 'nullable|numeric|min:0',
            'launch_date'     => 'nullable|date',
            'possession_date' => 'nullable|date',
            'description'     => 'nullable|string',
            'amenities'       => 'nullable|array',
            'manager_id'      => 'nullable|exists:users,id',
        ]);

        $validated['created_by'] = auth('api')->id();

        $project = Project::create($validated);
        $project->load('manager:id,name,email', 'createdBy:id,name');

        return response()->json([
            'success' => true,
            'message' => 'Project created successfully.',
            'data'    => $project,
        ], 201);
    }

    /**
     * GET /api/projects/{project}
     */
    public function show(Project $project): JsonResponse
    {
        $project->load([
            'manager:id,name,email',
            'createdBy:id,name',
            'leads' => function ($q) {
                $q->select('id', 'project_id', 'lead_number', 'name', 'phone', 'email', 'status', 'priority', 'assigned_to', 'created_at')
                  ->with('assignedTo:id,name')
                  ->orderBy('created_at', 'desc')
                  ->limit(20);
            },
            'siteVisits' => function ($q) {
                $q->with('lead:id,name,phone')
                  ->orderBy('scheduled_at', 'desc')
                  ->limit(20);
            },
        ]);

        return response()->json([
            'success' => true,
            'data'    => $project,
        ]);
    }

    /**
     * PATCH /api/projects/{project}
     */
    public function update(Request $request, Project $project): JsonResponse
    {
        $validated = $request->validate([
            'name'            => 'sometimes|required|string|max:255',
            'code'            => 'sometimes|required|string|max:50|unique:projects,code,' . $project->id,
            'type'            => 'sometimes|required|string|in:' . implode(',', Project::TYPES),
            'status'          => 'sometimes|required|string|in:' . implode(',', Project::STATUSES),
            'location'        => 'nullable|string|max:255',
            'city'            => 'nullable|string|max:255',
            'developer'       => 'nullable|string|max:255',
            'budget'          => 'nullable|numeric|min:0',
            'total_units'     => 'nullable|integer|min:0',
            'available_units' => 'nullable|integer|min:0',
            'sold_units'      => 'nullable|integer|min:0',
            'price_min'       => 'nullable|numeric|min:0',
            'price_max'       => 'nullable|numeric|min:0',
            'launch_date'     => 'nullable|date',
            'possession_date' => 'nullable|date',
            'description'     => 'nullable|string',
            'amenities'       => 'nullable|array',
            'manager_id'      => 'nullable|exists:users,id',
        ]);

        $project->update($validated);
        $project->load('manager:id,name,email', 'createdBy:id,name');

        return response()->json([
            'success' => true,
            'message' => 'Project updated successfully.',
            'data'    => $project,
        ]);
    }

    /**
     * DELETE /api/projects/{project}
     */
    public function destroy(Project $project): JsonResponse
    {
        $project->delete();

        return response()->json([
            'success' => true,
            'message' => 'Project deleted successfully.',
        ]);
    }

    /**
     * POST /api/projects/{project}/upload-image
     */
    public function uploadImage(Request $request, Project $project): JsonResponse
    {
        $request->validate([
            'image' => 'nullable|file|mimes:jpeg,png,jpg,gif,webp,pdf|max:25600', // max 25MB
            'file' => 'nullable|file|mimes:jpeg,png,jpg,gif,webp,pdf|max:25600',
            'type' => 'nullable|string|in:image,pdf,pdf_page',
            'pdf_name' => 'nullable|string',
            'pdf_url' => 'nullable|string',
            'page_number' => 'nullable|integer',
            'name' => 'nullable|string',
        ]);

        $uploadedFile = $request->file('image') ?? $request->file('file');

        if ($uploadedFile) {
            $path = $uploadedFile->store("projects/{$project->id}", 'public');
            $url = asset('storage/' . $path);

            $isPdf = $uploadedFile->getClientOriginalExtension() === 'pdf';
            $defaultType = $isPdf ? 'pdf' : 'image';

            $item = [
                'url' => $url,
                'type' => $request->input('type', $defaultType),
                'name' => $request->input('name', $uploadedFile->getClientOriginalName()),
            ];

            if ($request->filled('pdf_url')) {
                $item['pdf_url'] = $request->input('pdf_url');
            }
            if ($request->filled('pdf_name')) {
                $item['pdf_name'] = $request->input('pdf_name');
            }
            if ($request->filled('page_number')) {
                $item['page_number'] = (int) $request->input('page_number');
            }

            $images = $project->images ?? [];
            $images[] = $item;
            $project->images = $images;
            $project->save();

            return response()->json([
                'success' => true,
                'message' => 'File uploaded successfully.',
                'url' => $url,
                'item' => $item,
                'data' => $project,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'No file provided.',
        ], 400);
    }

    /**
     * POST /api/projects/{project}/delete-image
     */
    public function deleteImage(Request $request, Project $project): JsonResponse
    {
        $request->validate([
            'url' => 'required|string',
        ]);

        $url = $request->input('url');
        $images = $project->images ?? [];

        $found = false;
        foreach ($images as $key => $item) {
            $itemUrl = is_array($item) ? ($item['url'] ?? '') : $item;
            if ($itemUrl === $url) {
                unset($images[$key]);
                $found = true;
                break;
            }
        }

        if ($found) {
            $project->images = array_values($images);
            $project->save();

            $storagePrefix = asset('storage/');
            if (str_starts_with($url, $storagePrefix)) {
                $relativePath = str_replace($storagePrefix . '/', '', $url);
                \Illuminate\Support\Facades\Storage::disk('public')->delete($relativePath);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'File deleted successfully.',
            'data' => $project,
        ]);
    }

    /**
     * GET /api/media-proxy
     */
    public function proxyMedia(Request $request)
    {
        $url = $request->query('url');
        if (!$url) {
            return response()->json(['error' => 'URL is required'], 400);
        }

        $storageUrl = asset('storage/');
        if (!str_starts_with($url, $storageUrl)) {
            return response()->json(['error' => 'Unauthorized resource'], 403);
        }

        $relativePath = str_replace($storageUrl . '/', '', $url);
        
        if (!\Illuminate\Support\Facades\Storage::disk('public')->exists($relativePath)) {
            return response()->json(['error' => 'File not found'], 404);
        }

        $fileContent = \Illuminate\Support\Facades\Storage::disk('public')->get($relativePath);
        $mimeType = \Illuminate\Support\Facades\Storage::disk('public')->mimeType($relativePath);

        return response($fileContent)
            ->header('Content-Type', $mimeType)
            ->header('Access-Control-Allow-Origin', '*');
    }
}
