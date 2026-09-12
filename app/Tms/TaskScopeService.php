<?php

namespace App\Tms;

use App\Models\TmsTask;
use App\Models\TmsUser;
use Illuminate\Database\Eloquent\Builder;

class TaskScopeService
{
    public function tasksFor(TmsUser $actor): Builder
    {
        $query = TmsTask::query()->whereNull('archived_at');

        if ($actor->isRole('GM')) {
            return $query;
        }

        if ($actor->isRole('BDE')) {
            return $query->where('assignee_id', $actor->id);
        }

        return $query->where(function (Builder $query) use ($actor): void {
            $query->where('assignee_id', $actor->id)
                ->orWhere('created_by_id', $actor->id)
                ->orWhereHas('assignee', fn (Builder $assignees): Builder => $assignees->where('direct_manager_id', $actor->id));
        });
    }

    public function canViewTask(TmsUser $actor, TmsTask $task): bool
    {
        return $this->tasksFor($actor)->whereKey($task->id)->exists();
    }

    public function canCreateTaskFor(TmsUser $actor, TmsUser $assignee): bool
    {
        if (! $assignee->is_active || $actor->isRole('BDE')) {
            return false;
        }

        if ($actor->isRole('GM')) {
            return $assignee->isRole('AM', 'BDE');
        }

        return $assignee->isRole('BDE') && $assignee->direct_manager_id === $actor->id;
    }

    public function canManageTask(TmsUser $actor, TmsTask $task): bool
    {
        return $actor->isRole('GM') || ($actor->isRole('AM') && $this->canViewTask($actor, $task));
    }

    public function canVerifyTask(TmsUser $actor, TmsTask $task): bool
    {
        return $actor->isRole('GM') || ($actor->isRole('AM') && $task->assignee?->direct_manager_id === $actor->id);
    }
}
