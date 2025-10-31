<?php

namespace App\Http\Controllers;

use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FileController extends Controller
{
    /**
     * Display a listing of files.
     */
    public function index(Request $request)
    {
        $query = File::query();

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
     */
    public function show(File $file)
    {
        return response()->json($file);
    }

    /**
     * Update the specified file.
     */
    public function update(Request $request, File $file)
    {
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
     */
    public function destroy(File $file)
    {
        // Optionally delete the physical file
        if ($file->path && Storage::exists($file->path)) {
            Storage::delete($file->path);
        }

        $file->delete();

        return response()->json(['message' => 'File deleted successfully']);
    }
}

