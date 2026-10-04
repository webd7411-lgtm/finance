<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        if (!auth()->user()->isOwner() && !auth()->user()->isIncharge()) {
            abort(403, 'Only Owner or Incharge can access the Audit Trail.');
        }

        $query = ActivityLog::with('user')->latest();

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('module')) {
            $query->where('module', $request->module);
        }

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        if ($request->filled('search')) {
            $query->where('details', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $logs = $query->paginate(20)->withQueryString();

        $users = User::orderBy('name')->get();
        $modules = ActivityLog::select('module')->distinct()->orderBy('module')->pluck('module');
        $actions = ActivityLog::select('action')->distinct()->orderBy('action')->pluck('action');

        $totalLogsCount = ActivityLog::count();
        $todayLogsCount = ActivityLog::whereDate('created_at', Carbon::today())->count();

        return view('audit_logs.index', compact(
            'logs',
            'users',
            'modules',
            'actions',
            'totalLogsCount',
            'todayLogsCount'
        ));
    }
}
