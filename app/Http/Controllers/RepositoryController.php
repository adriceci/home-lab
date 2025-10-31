<?php

namespace App\Http\Controllers;

use App\Models\Repository;
use Illuminate\Http\Request;

class RepositoryController extends Controller
{
    /**
     * Display a listing of repositories.
     */
    public function index(Request $request)
    {
        $query = Repository::query();

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filter by is_active
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Filter by is_verified
        if ($request->filled('is_verified')) {
            $query->where('is_verified', $request->boolean('is_verified'));
        }

        // Search by name
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // Order by order field if not specified
        $orderBy = $request->get('order_by', 'order');
        $orderDir = $request->get('order_dir', 'asc');
        $query->orderBy($orderBy, $orderDir);

        // Pagination
        $perPage = $request->get('per_page', 15);
        $repositories = $query->paginate($perPage);

        return response()->json($repositories);
    }

    /**
     * Store a newly created repository.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'status' => 'nullable|string|max:255',
            'type' => 'nullable|string|max:255',
            'icon' => 'nullable|string|max:255',
            'url' => 'nullable|url|max:2048',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'is_verified' => 'boolean',
        ]);

        $repository = Repository::create($validated);

        return response()->json($repository, 201);
    }

    /**
     * Display the specified repository.
     */
    public function show(Repository $repository)
    {
        return response()->json($repository);
    }

    /**
     * Update the specified repository.
     */
    public function update(Request $request, Repository $repository)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'status' => 'nullable|string|max:255',
            'type' => 'nullable|string|max:255',
            'icon' => 'nullable|string|max:255',
            'url' => 'nullable|url|max:2048',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'is_verified' => 'boolean',
        ]);

        $repository->update($validated);

        return response()->json($repository);
    }

    /**
     * Remove the specified repository.
     */
    public function destroy(Repository $repository)
    {
        $repository->delete();

        return response()->json(['message' => 'Repository deleted successfully']);
    }
}

