<?php

namespace App\Http\Controllers\LoanManager;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Read-only viewer for the audit trail populated by ActivityLogObserver.
 * Tenant-scoped like everything else — a manager only ever sees activity
 * recorded under their own loan_manager_id.
 */
class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $managerId = Auth::user()->loanManager->id;

        $query = ActivityLog::where('loan_manager_id', $managerId)->with('user');

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }
        if ($request->filled('action')) {
            $query->where('action', $request->input('action'));
        }
        if ($request->filled('subject_type')) {
            $query->where('subject_type', $request->input('subject_type'));
        }

        $logs = $query->orderByDesc('created_at')->paginate(30)->withQueryString();

        $subjectTypes = ActivityLog::where('loan_manager_id', $managerId)
            ->distinct()
            ->orderBy('subject_type')
            ->pluck('subject_type');

        return view('loan-manager.activity-log.index', [
            'logs' => $logs,
            'subjectTypes' => $subjectTypes,
            'filters' => $request->only(['date_from', 'date_to', 'action', 'subject_type']),
        ]);
    }
}
