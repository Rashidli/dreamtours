<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Activity history (§22): read-only, filterable by who, module, record, action and date. */
class ActivityLogController extends Controller
{
    /** Times are stored in UTC and shown in DreamTour's local time. */
    public const DISPLAY_TIMEZONE = 'America/Toronto';

    public function __construct() { $this->middleware('permission:list-activity-log'); }

    public function index(Request $r): View
    {
        $d = $r->validate([
            'actor' => ['nullable', 'string', 'max:20'],
            'module' => ['nullable', Rule::in(array_column(ActivityLog::MODULES, 0))],
            'record' => ['nullable', 'integer', 'min:1'],
            'action' => ['nullable', 'string', 'max:64'],
            'q' => ['nullable', 'string', 'max:100'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);
        $tz = self::DISPLAY_TIMEZONE;

        $logs = ActivityLog::query()
            ->when($d['actor'] ?? null, fn ($q, $actor) => in_array($actor, ['system', 'visitor'], true) ? $q->where('actor_type', $actor) : $q->where('actor_type', 'user')->where('actor_id', (int) $actor))
            // One module label can cover several record types (album + media = Gallery).
            ->when($d['module'] ?? null, fn ($q, $module) => $q->whereIn('subject_type', array_keys(array_filter(ActivityLog::MODULES, fn ($m) => $m[0] === $module))))
            ->when($d['record'] ?? null, fn ($q, $id) => $q->where('subject_id', $id))
            ->when($d['action'] ?? null, fn ($q, $action) => $q->where('action', $action))
            ->when($d['q'] ?? null, fn ($q, $term) => $q->where(fn ($x) => $x->where('subject_label', 'like', "%{$term}%")->orWhere('actor_name', 'like', "%{$term}%")->orWhere('reason', 'like', "%{$term}%")))
            ->when($d['from'] ?? null, fn ($q, $from) => $q->where('created_at', '>=', Carbon::parse($from, $tz)->startOfDay()->utc()))
            ->when($d['to'] ?? null, fn ($q, $to) => $q->where('created_at', '<=', Carbon::parse($to, $tz)->endOfDay()->utc()))
            ->latest('id')
            ->paginate(50)
            ->withQueryString();

        return view('admin.activity_log.index', [
            'logs' => $logs,
            'users' => User::orderBy('name')->get(['id', 'name']),
            'modules' => collect(array_column(ActivityLog::MODULES, 0))->unique()->sort()->values(),
            'actions' => ActivityLog::query()->distinct()->orderBy('action')->pluck('action'),
            'timezone' => $tz,
        ]);
    }
}
