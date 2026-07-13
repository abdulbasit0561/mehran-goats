<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

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
     * Server-side DataTables AJAX endpoint for Activity Logs.
     */
    public function data(Request $request)
    {
        $query = ActivityLog::with('user')->select('activity_logs.*');

        return DataTables::of($query)
            ->filter(function ($query) use ($request) {
                if ($search = $request->get('search')['value'] ?? null) {
                    $query->where(function ($q) use ($search) {
                        $q->where('description', 'like', "%{$search}%")
                          ->orWhere('user_name', 'like', "%{$search}%")
                          ->orWhere('entity_type', 'like', "%{$search}%");
                    });
                }
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
            })
            ->editColumn('created_at', fn (ActivityLog $log) => $log->created_at->format('M d, Y g:i A'))
            ->editColumn('user_name', fn (ActivityLog $log) => '<span class="text-gray-700 font-medium">' . e($log->user_name ?? 'System') . '</span>')
            ->addColumn('action_badge', function (ActivityLog $log) {
                $badge = $log->actionBadge;
                return '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium ' . $badge['class'] . '">' . $badge['label'] . '</span>';
            })
            ->addColumn('entity_label', fn (ActivityLog $log) => e($log->entity_label))
            ->addColumn('description', fn (ActivityLog $log) => '<span class="text-gray-500">' . e($log->description ?? '—') . '</span>')
            ->addColumn('ip_address', fn (ActivityLog $log) => '<span class="text-xs text-gray-400">' . e($log->ip_address ?? '—') . '</span>')
            ->addColumn('actions', function (ActivityLog $log) {
                return '<a href="' . route('activity-logs.show', $log) . '" class="text-indigo-600 hover:underline text-xs">Details</a>';
            })
            ->rawColumns(['user_name', 'action_badge', 'description', 'ip_address', 'actions'])
            ->make(true);
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
