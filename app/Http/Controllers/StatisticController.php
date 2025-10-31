<?php

namespace App\Http\Controllers;

use App\Models\Statistic;
use Illuminate\Http\Request;

class StatisticController extends Controller
{
    /**
     * Display a listing of statistics.
     */
    public function index(Request $request)
    {
        $query = Statistic::query();

        // Filter by model_type
        if ($request->filled('model_type')) {
            $query->where('model_type', $request->model_type);
        }

        // Filter by model_id
        if ($request->filled('model_id')) {
            $query->where('model_id', $request->model_id);
        }

        // Filter by metric
        if ($request->filled('metric')) {
            $query->where('metric', $request->metric);
        }

        // Filter by date range (occurred_at)
        if ($request->filled('from_date')) {
            $query->where('occurred_at', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->where('occurred_at', '<=', $request->to_date);
        }

        // Eager load the polymorphic relationship
        $query->with('model');

        // Pagination
        $perPage = $request->get('per_page', 15);
        $statistics = $query->orderBy('occurred_at', 'desc')->paginate($perPage);

        return response()->json($statistics);
    }

    /**
     * Store a newly created statistic.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'model_type' => 'nullable|string|max:255',
            'model_id' => 'nullable|string|max:255',
            'metric' => 'required|string|max:255',
            'value' => 'required|integer',
            'context' => 'nullable|array',
            'occurred_at' => 'nullable|date',
        ]);

        $statistic = Statistic::create($validated);

        return response()->json($statistic, 201);
    }

    /**
     * Display the specified statistic.
     */
    public function show(Statistic $statistic)
    {
        $statistic->load('model');

        return response()->json($statistic);
    }

    /**
     * Update the specified statistic.
     */
    public function update(Request $request, Statistic $statistic)
    {
        $validated = $request->validate([
            'model_type' => 'nullable|string|max:255',
            'model_id' => 'nullable|string|max:255',
            'metric' => 'sometimes|required|string|max:255',
            'value' => 'sometimes|required|integer',
            'context' => 'nullable|array',
            'occurred_at' => 'nullable|date',
        ]);

        $statistic->update($validated);

        return response()->json($statistic);
    }

    /**
     * Remove the specified statistic.
     */
    public function destroy(Statistic $statistic)
    {
        $statistic->delete();

        return response()->json(['message' => 'Statistic deleted successfully']);
    }
}

