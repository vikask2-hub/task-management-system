<?php

namespace App\Tms;

use App\Models\TmsUser;

class DashboardMetricsService
{
    public function __construct(private TaskScopeService $scope) {}

    public function for(TmsUser $actor): array
    {
        $tasks = $this->scope->tasksFor($actor);
        $today = now()->toDateString();
        $assignedToday = (clone $tasks)->whereDate('planned_at', $today)->where('status', '!=', 'CANCELLED')->count();
        $completedToday = (clone $tasks)->whereDate('completed_at', $today)->count();
        $eligibleToday = max(1, (clone $tasks)->whereDate('due_at', $today)->where('status', '!=', 'CANCELLED')->count());

        return [
            ['label' => $actor->isRole('BDE') ? 'My tasks today' : 'Assigned today', 'value' => $assignedToday, 'detail' => 'Planned for today', 'tone' => 'blue', 'icon' => 'clipboard-list'],
            ['label' => 'Completed today', 'value' => $completedToday, 'detail' => round($completedToday / $eligibleToday * 100).'% execution rate', 'tone' => 'emerald', 'icon' => 'circle-check-big'],
            ['label' => 'Open overdue', 'value' => (clone $tasks)->where('due_at', '<', now())->whereNotIn('status', ['COMPLETED', 'CANCELLED'])->count(), 'detail' => 'Needs intervention', 'tone' => 'rose', 'icon' => 'alarm-clock'],
            ['label' => 'Blocked', 'value' => (clone $tasks)->where('status', 'BLOCKED')->count(), 'detail' => 'Awaiting support', 'tone' => 'amber', 'icon' => 'octagon-alert'],
        ];
    }

    public function statusDistribution(TmsUser $actor): array
    {
        $counts = $this->scope->tasksFor($actor)->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');

        return collect(['TODO', 'IN_PROGRESS', 'SUBMITTED', 'BLOCKED', 'COMPLETED'])
            ->map(fn (string $status): array => ['status' => $status, 'count' => (int) ($counts[$status] ?? 0)])
            ->all();
    }
}
