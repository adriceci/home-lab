<?php

namespace App\Http\Controllers;

use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FileController extends Controller
{
    /**
     * Display a listing of files.
     * Files in quarantine are excluded by default for security reasons.
     */
    public function index(Request $request)
    {
        $query = File::query();

        // Exclude files in quarantine by default (security measure)
        // Only show quarantine files if explicitly requested with include_quarantine=true
        if (!$request->boolean('include_quarantine')) {
            $query->where('storage_disk', '!=', 'quarantine');
        }

        // Filter by storage_disk
        if ($request->filled('storage_disk')) {
            $query->where('storage_disk', $request->storage_disk);
        }

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filter by extension
        if ($request->filled('extension')) {
            $query->where('extension', $request->extension);
        }

        // Filter by virustotal_status
        if ($request->filled('virustotal_status')) {
            $query->where('virustotal_status', $request->virustotal_status);
        }

        // Search by name
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // Pagination
        $perPage = $request->get('per_page', 15);
        $files = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json($files);
    }

    /**
     * Store a newly created file.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'path' => 'nullable|string|max:2048',
            'size' => 'nullable|integer|min:0',
            'type' => 'nullable|string|max:255',
            'mime_type' => 'nullable|string|max:255',
            'extension' => 'nullable|string|max:50',
            'virustotal_scan_id' => 'nullable|string|max:255',
            'virustotal_status' => 'nullable|string|max:255',
            'virustotal_results' => 'nullable|array',
            'virustotal_scanned_at' => 'nullable|date',
        ]);

        $file = File::create($validated);

        return response()->json($file, 201);
    }

    /**
     * Display the specified file.
     * Files in quarantine cannot be accessed through this endpoint for security reasons.
     */
    public function show(File $file)
    {
        // Prevent access to files in quarantine
        if ($file->storage_disk === 'quarantine') {
            return response()->json([
                'error' => 'Files in quarantine cannot be accessed directly for security reasons.'
            ], 403);
        }

        return response()->json($file);
    }

    /**
     * Update the specified file.
     * Files in quarantine cannot be updated through this endpoint.
     */
    public function update(Request $request, File $file)
    {
        // Prevent modification of files in quarantine
        if ($file->storage_disk === 'quarantine') {
            return response()->json([
                'error' => 'Files in quarantine cannot be modified for security reasons.'
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'path' => 'nullable|string|max:2048',
            'size' => 'nullable|integer|min:0',
            'type' => 'nullable|string|max:255',
            'mime_type' => 'nullable|string|max:255',
            'extension' => 'nullable|string|max:50',
            'virustotal_scan_id' => 'nullable|string|max:255',
            'virustotal_status' => 'nullable|string|max:255',
            'virustotal_results' => 'nullable|array',
            'virustotal_scanned_at' => 'nullable|date',
        ]);

        $file->update($validated);

        return response()->json($file);
    }

    /**
     * Remove the specified file.
     * Files in quarantine cannot be deleted through this endpoint - use QuarantineService instead.
     */
    public function destroy(File $file)
    {
        // Prevent deletion of files in quarantine through this endpoint
        // Quarantine files should be managed through QuarantineService
        if ($file->storage_disk === 'quarantine') {
            return response()->json([
                'error' => 'Files in quarantine cannot be deleted through this endpoint. Use QuarantineService for quarantine management.'
            ], 403);
        }

        // Delete the physical file using the correct disk
        if ($file->path && $file->storage_disk) {
            if (Storage::disk($file->storage_disk)->exists($file->path)) {
                Storage::disk($file->storage_disk)->delete($file->path);
            }
        }

        $file->delete();

        return response()->json(['message' => 'File deleted successfully']);
    }
}

