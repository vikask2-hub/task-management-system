<?php

namespace App\Tms;

use App\Models\TmsAuditLog;
use App\Models\TmsHospital;
use App\Models\TmsTask;
use App\Models\TmsTaskHistory;
use App\Models\TmsUser;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TaskWorkflowService
{
    public function __construct(private TaskScopeService $scope) {}

    public function create(TmsUser $actor, array $data): TmsTask
    {
        $assignee = TmsUser::findOrFail($data['assignee_id']);
        if (! $this->scope->canCreateTaskFor($actor, $assignee)) {
            throw new AuthorizationException('You cannot assign a task to this user.');
        }
        $isAssignedToBusinessUnit = (int) $assignee->default_business_unit_id === (int) $data['business_unit_id']
            || $assignee->businessUnits()->whereKey($data['business_unit_id'])->exists();
        if (! $isAssignedToBusinessUnit) {
            throw ValidationException::withMessages(['business_unit_id' => 'Choose the assignee’s business unit.']);
        }

        if (filled($data['hospital_id'] ?? null)) {
            $hospital = TmsHospital::query()->where('is_active', true)->findOrFail($data['hospital_id']);
            if ((int) $hospital->business_unit_id !== (int) $data['business_unit_id']) {
                throw ValidationException::withMessages(['hospital_id' => 'Choose a hospital from the selected business unit.']);
            }
            $data['hospital_name_snapshot'] = $hospital->name;
            $data['address_snapshot'] = $hospital->address;
        }

        return DB::transaction(function () use ($actor, $assignee, $data): TmsTask {
            $task = TmsTask::create(Arr::only($data, [
                'title', 'description', 'category_id', 'priority', 'business_unit_id', 'hospital_id',
                'hospital_name_snapshot', 'address_snapshot', 'planned_at', 'due_at', 'verification_required',
                'expected_outcome',
            ]) + [
                'task_number' => 'PENDING-'.str()->uuid(),
                'status' => 'TODO',
                'assignee_id' => $assignee->id,
                'assignee_role_snapshot' => $assignee->role,
                'created_by_id' => $actor->id,
                'source' => 'MANUAL',
            ]);

            $task->update(['task_number' => 'TMS-'.$task->created_at->format('Ymd').'-'.str_pad((string) $task->id, 6, '0', STR_PAD_LEFT)]);
            TmsTaskHistory::create(['task_id' => $task->id, 'from_status' => null, 'to_status' => 'TODO', 'changed_by_id' => $actor->id, 'reason' => 'Task created']);
            $this->audit($actor, 'task.created', $task, ['assignee_id' => $assignee->id]);

            return $task->fresh(['assignee', 'category', 'businessUnit']);
        });
    }

    public function transition(TmsUser $actor, TmsTask $task, string $transition, array $data = []): TmsTask
    {
        $target = $this->targetStatus($task, $transition);
        $this->authorizeTransition($actor, $task, $transition, $target);

        if (in_array($target, ['SUBMITTED', 'COMPLETED'], true) && blank($data['outcome'] ?? $task->outcome)) {
            throw ValidationException::withMessages(['outcome' => 'Add the outcome before submitting or completing this task.']);
        }
        if ($target === 'BLOCKED' && blank($data['blocker_reason'] ?? null)) {
            throw ValidationException::withMessages(['blocker_reason' => 'Explain what is blocking this task.']);
        }

        return DB::transaction(function () use ($actor, $task, $transition, $target, $data): TmsTask {
            $fromStatus = $task->status;
            $attributes = Arr::only($data, ['outcome', 'completion_notes', 'person_met', 'follow_up_at', 'blocker_reason']);
            $attributes['status'] = $target;

            if ($target === 'IN_PROGRESS') {
                $attributes['started_at'] = $task->started_at ?? now();
                $attributes['blocker_reason'] = null;
                if ($fromStatus === 'COMPLETED') {
                    $attributes['reopened_count'] = $task->reopened_count + 1;
                    $attributes['completed_at'] = null;
                }
            }
            if ($target === 'SUBMITTED') {
                $attributes['submitted_at'] = now();
            }
            if ($target === 'COMPLETED') {
                $attributes['completed_at'] = now();
            }
            if ($target === 'CANCELLED') {
                $attributes['cancelled_at'] = now();
            }

            $task->update($attributes);
            TmsTaskHistory::create(['task_id' => $task->id, 'from_status' => $fromStatus, 'to_status' => $target, 'changed_by_id' => $actor->id, 'reason' => $data['reason'] ?? null]);
            $this->audit($actor, 'task.'.str()->lower($transition), $task, ['from' => $fromStatus, 'to' => $target]);

            return $task->fresh(['assignee', 'category', 'businessUnit', 'histories.changedBy']);
        });
    }

    private function targetStatus(TmsTask $task, string $transition): string
    {
        return match ($transition) {
            'START', 'UNBLOCK', 'REWORK', 'REOPEN' => 'IN_PROGRESS',
            'BLOCK' => 'BLOCKED',
            'SUBMIT' => $task->verification_required ? 'SUBMITTED' : 'COMPLETED',
            'VERIFY' => 'COMPLETED',
            'CANCEL' => 'CANCELLED',
            default => throw ValidationException::withMessages(['status' => 'Unknown task action.']),
        };
    }

    private function authorizeTransition(TmsUser $actor, TmsTask $task, string $transition, string $target): void
    {
        $allowed = [
            'TODO' => ['IN_PROGRESS', 'BLOCKED', 'CANCELLED'],
            'IN_PROGRESS' => ['SUBMITTED', 'COMPLETED', 'BLOCKED', 'CANCELLED'],
            'BLOCKED' => ['IN_PROGRESS', 'CANCELLED'],
            'SUBMITTED' => ['COMPLETED', 'IN_PROGRESS', 'CANCELLED'],
            'COMPLETED' => ['IN_PROGRESS'],
        ];

        if (! in_array($target, $allowed[$task->status] ?? [], true)) {
            throw ValidationException::withMessages(['status' => "This task cannot move from {$task->status} to {$target}."]);
        }

        $isAssigneeAction = in_array($transition, ['START', 'BLOCK', 'UNBLOCK', 'SUBMIT'], true) && $task->assignee_id === $actor->id;
        $isVerificationAction = in_array($transition, ['VERIFY', 'REWORK'], true) && $this->scope->canVerifyTask($actor, $task);
        $isManagerAction = in_array($transition, ['CANCEL', 'REOPEN'], true) && $this->scope->canManageTask($actor, $task);

        if (! $isAssigneeAction && ! $isVerificationAction && ! $isManagerAction) {
            throw new AuthorizationException('You cannot perform this task action.');
        }
    }

    private function audit(TmsUser $actor, string $action, TmsTask $task, array $metadata): void
    {
        TmsAuditLog::create(['actor_user_id' => $actor->id, 'action' => $action, 'entity_type' => 'task', 'entity_id' => $task->id, 'business_unit_id' => $task->business_unit_id, 'metadata' => $metadata]);
    }
}
