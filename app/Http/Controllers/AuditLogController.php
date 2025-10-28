<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;

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

        // Filter by user
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

        $stats = [
            'total_logs' => $query->count(),
            'actions_count' => $query->selectRaw('action, COUNT(*) as count')
                ->groupBy('action')
                ->orderByRaw('COUNT(*) DESC')
                ->get(),
            'users_count' => $query->whereNotNull('user_id')
                ->distinct()
                ->count('user_id'),
            'failed_logins' => AuditLog::where('action', 'login_failed')->count(),
            'successful_logins' => AuditLog::where('action', 'login')->count(),
        ];

        return response()->json($stats);
    }
}
