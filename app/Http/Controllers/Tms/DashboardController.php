<?php

namespace App\Http\Controllers\Tms;

use App\Http\Controllers\Controller;
use App\Models\TmsBusinessUnit;
use App\Models\TmsUser;
use App\Tms\DashboardMetricsService;
use App\Tms\TaskScopeService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, DashboardMetricsService $metrics, TaskScopeService $scope): View
    {
        /** @var TmsUser $actor */
        $actor = $request->attributes->get('tmsUser');
        $taskQuery = $scope->tasksFor($actor);
        $todayTasks = (clone $taskQuery)->with(['category', 'businessUnit', 'assignee.manager'])
            ->whereDate('planned_at', now()->toDateString())
            ->orderByRaw("CASE priority WHEN 'URGENT' THEN 1 WHEN 'HIGH' THEN 2 WHEN 'MEDIUM' THEN 3 ELSE 4 END")
            ->orderBy('due_at')
            ->limit($actor->isRole('BDE') ? 8 : 6)
            ->get();

        $exceptions = (clone $taskQuery)->with(['assignee', 'businessUnit'])
            ->where(function ($query): void {
                $query->where('status', 'BLOCKED')
                    ->orWhere(fn ($overdue) => $overdue->where('due_at', '<', now())->whereNotIn('status', ['COMPLETED', 'CANCELLED']));
            })
            ->orderBy('due_at')
            ->limit(5)
            ->get();

        $team = collect();
        if ($actor->isRole('GM')) {
            $team = TmsUser::with('defaultBusinessUnit')->where('role', 'AM')->withCount(['reports', 'assignedTasks'])->orderBy('name')->limit(8)->get();
        } elseif ($actor->isRole('AM')) {
            $team = TmsUser::with('defaultBusinessUnit')->where('direct_manager_id', $actor->id)->withCount(['assignedTasks as tasks_today_count' => fn ($query) => $query->whereDate('planned_at', now()->toDateString()), 'assignedTasks as completed_today_count' => fn ($query) => $query->whereDate('completed_at', now()->toDateString())])->orderBy('name')->get();
        }

        return view('tms.dashboard', [
            'stats' => $metrics->for($actor),
            'statusDistribution' => $metrics->statusDistribution($actor),
            'todayTasks' => $todayTasks,
            'exceptions' => $exceptions,
            'team' => $team,
            'businessUnits' => $actor->isRole('GM') ? TmsBusinessUnit::withCount('tasks')->orderByDesc('tasks_count')->limit(6)->get() : collect(),
            'pendingVerification' => (clone $taskQuery)->where('status', 'SUBMITTED')->count(),
        ]);
    }
}
