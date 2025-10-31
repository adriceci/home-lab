<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use Illuminate\Http\Request;

class DomainController extends Controller
{
    /**
     * Display a listing of domains.
     */
    public function index(Request $request)
    {
        $query = Domain::query();

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

        // Pagination
        $perPage = $request->get('per_page', 15);
        $domains = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json($domains);
    }

    /**
     * Store a newly created domain.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:domains,name',
            'description' => 'nullable|string|max:1000',
            'status' => 'nullable|string|max:255',
            'type' => 'nullable|string|max:255',
            'url' => 'nullable|url|max:2048',
            'is_active' => 'boolean',
            'is_verified' => 'boolean',
        ]);

        $domain = Domain::create($validated);

        return response()->json($domain, 201);
    }

    /**
     * Display the specified domain.
     */
    public function show(Domain $domain)
    {
        return response()->json($domain);
    }

    /**
     * Update the specified domain.
     */
    public function update(Request $request, Domain $domain)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255|unique:domains,name,' . $domain->id,
            'description' => 'nullable|string|max:1000',
            'status' => 'nullable|string|max:255',
            'type' => 'nullable|string|max:255',
            'url' => 'nullable|url|max:2048',
            'is_active' => 'boolean',
            'is_verified' => 'boolean',
        ]);

        $domain->update($validated);

        return response()->json($domain);
    }

    /**
     * Remove the specified domain.
     */
    public function destroy(Domain $domain)
    {
        $domain->delete();

        return response()->json(['message' => 'Domain deleted successfully']);
    }
}

