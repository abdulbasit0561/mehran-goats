<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    /**
     * Display paginated activity logs with filters.
     */
    public function index(Request $request)
    {
        $query = ActivityLog::with('user')->latest();

        if ($entityType = $request->get('entity_type')) {
            $query->where('entity_type', 'like', "%{$entityType}%");
        }

        if ($action = $request->get('action')) {
            $query->where('action', $action);
        }

        if ($userId = $request->get('user_id')) {
            $query->where('user_id', $userId);
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->get('from'));
        }

        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->get('to'));
        }

        if ($search = $request->get('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('user_name', 'like', "%{$search}%")
                  ->orWhere('entity_type', 'like', "%{$search}%");
            });
        }

        $logs = $query->paginate(25)->withQueryString();

        // For filter dropdowns
        $users     = User::orderBy('name')->pluck('name', 'id');
        $actions   = ['create' => 'Created', 'update' => 'Updated', 'delete' => 'Deleted'];
        $entities  = ActivityLog::select('entity_type')->distinct()->orderBy('entity_type')->pluck('entity_type')
                        ->mapWithKeys(fn ($type) => [class_basename($type) => $type]);

        return view('activity-logs.index', compact('logs', 'users', 'actions', 'entities'));
    }

    /**
     * Display a single activity log entry with full before/after diff.
     */
    public function show(ActivityLog $activity_log)
    {
        $activity_log->load('user');

        return view('activity-logs.show', ['log' => $activity_log]);
    }
}
