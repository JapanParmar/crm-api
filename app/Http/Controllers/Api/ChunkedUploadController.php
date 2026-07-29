<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ChunkedUploadController extends Controller
{
    /**
     * POST /api/uploads/init
     *
     * Initialize a chunked upload session. Returns a unique upload_id
     * and the directory where chunks will be staged.
     */
    public function init(Request $request): JsonResponse
    {
        $request->validate([
            'filename'     => 'required|string|max:255',
            'total_size'   => 'required|integer|min:1',
            'total_chunks' => 'required|integer|min:1|max:5000',
            'mime_type'    => 'required|string|in:application/pdf,image/jpeg,image/png,image/gif,image/webp',
            'project_id'   => 'required|integer|exists:projects,id',
        ]);

        $uploadId = Str::uuid()->toString();
        $chunkDir = "chunks/{$uploadId}";

        Storage::disk('local')->makeDirectory($chunkDir);

        // Store session metadata as JSON
        Storage::disk('local')->put("{$chunkDir}/_meta.json", json_encode([
            'upload_id'    => $uploadId,
            'filename'     => $request->input('filename'),
            'total_size'   => (int) $request->input('total_size'),
            'total_chunks' => (int) $request->input('total_chunks'),
            'mime_type'    => $request->input('mime_type'),
            'project_id'   => (int) $request->input('project_id'),
            'user_id'      => auth('api')->id(),
            'created_at'   => now()->toIso8601String(),
            'received'     => [],
        ]));

        return response()->json([
            'success'   => true,
            'upload_id' => $uploadId,
            'message'   => 'Upload session initialized.',
        ]);
    }

    /**
     * POST /api/uploads/{uploadId}/chunk
     *
     * Receive a single chunk. Each chunk is validated and stored.
     * Chunk size should be ~2MB to stay well below PHP post limits.
     */
    public function chunk(Request $request, string $uploadId): JsonResponse
    {
        $request->validate([
            'chunk'       => 'required|file|max:5120', // max 5MB per chunk
            'chunk_index' => 'required|integer|min:0',
        ]);

        $chunkDir = "chunks/{$uploadId}";
        $metaPath = "{$chunkDir}/_meta.json";

        if (!Storage::disk('local')->exists($metaPath)) {
            return response()->json([
                'success' => false,
                'message' => 'Upload session not found or expired.',
            ], 404);
        }

        $meta = json_decode(Storage::disk('local')->get($metaPath), true);

        // Security: verify the upload belongs to the current user
        if ($meta['user_id'] !== auth('api')->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        $chunkIndex = (int) $request->input('chunk_index');

        if ($chunkIndex >= $meta['total_chunks']) {
            return response()->json([
                'success' => false,
                'message' => 'Chunk index exceeds total chunks.',
            ], 422);
        }

        // Store the chunk
        $chunkFile = $request->file('chunk');
        $chunkFile->storeAs($chunkDir, "chunk_{$chunkIndex}", 'local');

        // Update received list
        if (!in_array($chunkIndex, $meta['received'])) {
            $meta['received'][] = $chunkIndex;
            sort($meta['received']);
            Storage::disk('local')->put($metaPath, json_encode($meta));
        }

        $receivedCount = count($meta['received']);
        $totalChunks   = $meta['total_chunks'];

        return response()->json([
            'success'  => true,
            'message'  => "Chunk {$chunkIndex} received.",
            'received' => $receivedCount,
            'total'    => $totalChunks,
            'complete' => $receivedCount >= $totalChunks,
        ]);
    }

    /**
     * POST /api/uploads/{uploadId}/complete
     *
     * Merge all chunks into the final file and store it in public storage.
     * Optionally attach it to a project.
     */
    public function complete(Request $request, string $uploadId): JsonResponse
    {
        $request->validate([
            'type'        => 'nullable|string|in:image,pdf,pdf_page',
            'pdf_url'     => 'nullable|string',
            'pdf_name'    => 'nullable|string',
            'page_number' => 'nullable|integer',
            'name'        => 'nullable|string',
        ]);

        $chunkDir = "chunks/{$uploadId}";
        $metaPath = "{$chunkDir}/_meta.json";

        if (!Storage::disk('local')->exists($metaPath)) {
            return response()->json([
                'success' => false,
                'message' => 'Upload session not found.',
            ], 404);
        }

        $meta = json_decode(Storage::disk('local')->get($metaPath), true);

        if ($meta['user_id'] !== auth('api')->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        // Verify all chunks received
        if (count($meta['received']) < $meta['total_chunks']) {
            $missing = array_diff(range(0, $meta['total_chunks'] - 1), $meta['received']);
            return response()->json([
                'success' => false,
                'message' => 'Not all chunks received. Missing: ' . implode(', ', $missing),
            ], 422);
        }

        // Merge chunks into a temporary file
        $extension = pathinfo($meta['filename'], PATHINFO_EXTENSION) ?: 'pdf';
        $uniqueName = Str::uuid() . '.' . $extension;
        $projectId  = $meta['project_id'];
        $finalDir   = "projects/{$projectId}";
        $finalPath  = "{$finalDir}/{$uniqueName}";

        // Ensure directory exists
        Storage::disk('public')->makeDirectory($finalDir);

        // Build the merged file by reading chunks in order
        $localBasePath = Storage::disk('local')->path($chunkDir);
        $publicBasePath = Storage::disk('public')->path($finalPath);

        $outputStream = fopen($publicBasePath, 'wb');
        if (!$outputStream) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create output file.',
            ], 500);
        }

        for ($i = 0; $i < $meta['total_chunks']; $i++) {
            $chunkPath = "{$localBasePath}/chunk_{$i}";
            if (!file_exists($chunkPath)) {
                fclose($outputStream);
                return response()->json([
                    'success' => false,
                    'message' => "Chunk {$i} file is missing from disk.",
                ], 500);
            }
            $chunkStream = fopen($chunkPath, 'rb');
            stream_copy_to_stream($chunkStream, $outputStream);
            fclose($chunkStream);
        }
        fclose($outputStream);

        // Verify merged file size
        $mergedSize = filesize($publicBasePath);
        if (abs($mergedSize - $meta['total_size']) > 1024) {
            // Allow 1KB tolerance for encoding differences
            @unlink($publicBasePath);
            return response()->json([
                'success' => false,
                'message' => "File size mismatch. Expected {$meta['total_size']} bytes, got {$mergedSize} bytes.",
            ], 422);
        }

        // Validate the final file mime type
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $detectedMime = $finfo->file($publicBasePath);
        $allowedMimes = ['application/pdf', 'image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($detectedMime, $allowedMimes)) {
            @unlink($publicBasePath);
            return response()->json([
                'success' => false,
                'message' => "Invalid file type detected: {$detectedMime}",
            ], 422);
        }

        $url = asset('storage/' . $finalPath);

        // Attach to project
        $project = Project::findOrFail($projectId);
        $isPdf = $detectedMime === 'application/pdf';

        $item = [
            'url'  => $url,
            'type' => $request->input('type', $isPdf ? 'pdf' : 'image'),
            'name' => $request->input('name', $meta['filename']),
            'size' => $mergedSize,
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

        // Cleanup chunks
        Storage::disk('local')->deleteDirectory($chunkDir);

        return response()->json([
            'success' => true,
            'message' => 'File uploaded and assembled successfully.',
            'url'     => $url,
            'item'    => $item,
            'data'    => $project,
        ]);
    }

    /**
     * DELETE /api/uploads/{uploadId}
     *
     * Abort an in-progress upload, cleaning up partial chunks.
     */
    public function abort(string $uploadId): JsonResponse
    {
        $chunkDir = "chunks/{$uploadId}";

        if (Storage::disk('local')->exists($chunkDir)) {
            $metaPath = "{$chunkDir}/_meta.json";
            if (Storage::disk('local')->exists($metaPath)) {
                $meta = json_decode(Storage::disk('local')->get($metaPath), true);
                if ($meta['user_id'] !== auth('api')->id()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unauthorized.',
                    ], 403);
                }
            }
            Storage::disk('local')->deleteDirectory($chunkDir);
        }

        return response()->json([
            'success' => true,
            'message' => 'Upload aborted and cleaned up.',
        ]);
    }
}
