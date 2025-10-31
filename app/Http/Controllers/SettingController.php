<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SettingController extends Controller
{
    /**
     * Display a listing of settings.
     */
    public function index(Request $request)
    {
        $query = Setting::with('user');

        // Filter by user_id
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        } else {
            // If no user_id specified, show current user's settings or all if admin
            if (!Auth::user()?->is_admin) {
                $query->where('user_id', Auth::id());
            }
        }

        // Filter by group
        if ($request->filled('group')) {
            $query->where('group', $request->group);
        }

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filter by is_public
        if ($request->filled('is_public')) {
            $query->where('is_public', $request->boolean('is_public'));
        }

        // Search by key
        if ($request->filled('search')) {
            $query->where('key', 'like', '%' . $request->search . '%');
        }

        // Pagination
        $perPage = $request->get('per_page', 15);
        $settings = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json($settings);
    }

    /**
     * Store a newly created setting.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'nullable|string|exists:users,id',
            'key' => 'required|string|max:255',
            'value' => 'nullable|array',
            'type' => 'nullable|string|max:255',
            'group' => 'nullable|string|max:255',
            'is_public' => 'boolean',
            'description' => 'nullable|string|max:1000',
        ]);

        // Default user_id to current user if not provided
        if (!isset($validated['user_id'])) {
            $validated['user_id'] = Auth::id();
        }

        $setting = Setting::create($validated);

        return response()->json($setting, 201);
    }

    /**
     * Display the specified setting.
     */
    public function show(Setting $setting)
    {
        $setting->load('user');

        return response()->json($setting);
    }

    /**
     * Update the specified setting.
     */
    public function update(Request $request, Setting $setting)
    {
        $validated = $request->validate([
            'user_id' => 'nullable|string|exists:users,id',
            'key' => 'sometimes|required|string|max:255',
            'value' => 'nullable|array',
            'type' => 'nullable|string|max:255',
            'group' => 'nullable|string|max:255',
            'is_public' => 'boolean',
            'description' => 'nullable|string|max:1000',
        ]);

        $setting->update($validated);

        return response()->json($setting);
    }

    /**
     * Remove the specified setting.
     */
    public function destroy(Setting $setting)
    {
        $setting->delete();

        return response()->json(['message' => 'Setting deleted successfully']);
    }
}

