<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuditLogController extends Controller
{
    /**
     * Display a listing of audit logs.
     */
    public function index(Request $request)
    {
        $query = AuditLog::with('user');

        // Filter by action
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        // Filter by user - admins can see all logs or filter by specific user
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by date range
        if ($request->filled('from_date')) {
            $query->where('created_at', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->where('created_at', '<=', $request->to_date);
        }

        // Pagination
        $perPage = $request->get('per_page', 15);
        $auditLogs = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json($auditLogs);
    }

    /**
     * Display the specified audit log.
     */
    public function show(AuditLog $auditLog)
    {
        $auditLog->load('user');

        return response()->json($auditLog);
    }

    /**
     * Store a newly created audit log.
     * 
     * Note: Audit logs are typically created automatically by the system.
     * This method is provided for completeness but should be used sparingly.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'nullable|string|exists:users,id',
            'action' => 'required|string|max:255',
            'model_type' => 'nullable|string|max:255',
            'model_id' => 'nullable|string|max:255',
            'old_values' => 'nullable|array',
            'new_values' => 'nullable|array',
            'ip_address' => 'nullable|ip',
            'user_agent' => 'nullable|string|max:500',
            'url' => 'nullable|string|max:2048',
            'method' => 'nullable|string|max:10',
            'description' => 'nullable|string|max:1000',
        ]);

        $auditLog = AuditLog::create($validated);
        $auditLog->load('user');

        return response()->json($auditLog, 201);
    }

    /**
     * Update the specified audit log.
     * 
     * Note: Audit logs are typically immutable for historical integrity.
     * This method is provided for completeness but should be used sparingly.
     */
    public function update(Request $request, AuditLog $auditLog)
    {
        $validated = $request->validate([
            'user_id' => 'nullable|string|exists:users,id',
            'action' => 'sometimes|required|string|max:255',
            'model_type' => 'nullable|string|max:255',
            'model_id' => 'nullable|string|max:255',
            'old_values' => 'nullable|array',
            'new_values' => 'nullable|array',
            'ip_address' => 'nullable|ip',
            'user_agent' => 'nullable|string|max:500',
            'url' => 'nullable|string|max:2048',
            'method' => 'nullable|string|max:10',
            'description' => 'nullable|string|max:1000',
        ]);

        $auditLog->update($validated);
        $auditLog->load('user');

        return response()->json($auditLog);
    }

    /**
     * Remove the specified audit log.
     * 
     * Note: Audit logs are typically preserved for compliance and security.
     * This method uses soft delete to maintain data integrity.
     */
    public function destroy(AuditLog $auditLog)
    {
        $auditLog->delete();

        return response()->json(['message' => 'Audit log deleted successfully']);
    }

    /**
     * Get audit log statistics.
     */
    public function stats(Request $request)
    {
        $query = AuditLog::query();

        // Filter by date range
        if ($request->filled('from_date')) {
            $query->where('created_at', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->where('created_at', '<=', $request->to_date);
        }

        // Filter by user if specified
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $stats = [
            'total_logs' => $query->count(),
            'actions_count' => $query->selectRaw('action, COUNT(*) as count')
                ->groupBy('action')
                ->orderByRaw('COUNT(*) DESC')
                ->get(),
            'users_count' => $query->whereNotNull('user_id')
                ->distinct()
                ->count('user_id'),
            'failed_logins' => $query->where('action', 'login_failed')->count(),
            'successful_logins' => $query->where('action', 'login')->count(),
        ];

        return response()->json($stats);
    }
}
