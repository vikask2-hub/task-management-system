<?php

namespace App\Http\Controllers\Tms;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTmsTaskRequest;
use App\Http\Requests\UpdateTmsTaskRequest;
use App\Models\TmsAuditLog;
use App\Models\TmsBusinessUnit;
use App\Models\TmsHospital;
use App\Models\TmsTask;
use App\Models\TmsTaskCategory;
use App\Models\TmsTaskComment;
use App\Models\TmsUser;
use App\Tms\TaskScopeService;
use App\Tms\TaskWorkflowService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TaskController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, TaskScopeService $scope): View
    {
        /** @var TmsUser $actor */
        $actor = $request->attributes->get('tmsUser');
        $query = $scope->tasksFor($actor)->with(['assignee', 'category', 'businessUnit', 'hospital']);

        $query->when($request->filled('search'), function ($query) use ($request): void {
            $search = $request->string('search')->toString();
            $query->where(function ($query) use ($search): void {
                $query->where('task_number', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhere('hospital_name_snapshot', 'like', "%{$search}%")
                    ->orWhereHas('hospital', fn ($hospitals) => $hospitals->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('assignee', fn ($users) => $users->where('name', 'like', "%{$search}%"));
            });
        });
        $query->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()));
        $query->when($request->filled('priority'), fn ($query) => $query->where('priority', $request->string('priority')->toString()));
        $query->when($request->filled('business_unit_id'), fn ($query) => $query->where('business_unit_id', $request->integer('business_unit_id')));
        $query->when($request->boolean('overdue'), fn ($query) => $query->where('due_at', '<', now())->whereNotIn('status', ['COMPLETED', 'CANCELLED']));

        $tasks = $query->orderByRaw("CASE priority WHEN 'URGENT' THEN 1 WHEN 'HIGH' THEN 2 WHEN 'MEDIUM' THEN 3 ELSE 4 END")
            ->orderBy('due_at')
            ->paginate(20)
            ->withQueryString();

        return view('tms.tasks.index', [
            'tasks' => $tasks,
            'businessUnits' => $this->businessUnitsFor($actor),
        ]);
    }

    public function verification(Request $request, TaskScopeService $scope): View
    {
        $request->merge(['status' => 'SUBMITTED']);

        return $this->index($request, $scope);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request): View
    {
        /** @var TmsUser $actor */
        $actor = $request->attributes->get('tmsUser');
        abort_unless($actor->isRole('GM', 'AM'), 403);

        return view('tms.tasks.form', $this->formData($actor));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTmsTaskRequest $request, TaskWorkflowService $workflow): RedirectResponse
    {
        /** @var TmsUser $actor */
        $actor = $request->attributes->get('tmsUser');
        $data = $request->validated();
        $workflow->create($actor, $data);

        return redirect()->route('tms.tasks.index')->with('success', 'Task created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, TmsTask $task, TaskScopeService $scope): View
    {
        /** @var TmsUser $actor */
        $actor = $request->attributes->get('tmsUser');
        abort_unless($scope->canViewTask($actor, $task), 404);

        return view('tms.tasks.show', ['task' => $task->load(['assignee.manager', 'creator', 'category', 'businessUnit', 'hospital', 'comments.author', 'histories.changedBy'])]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, TmsTask $task, TaskScopeService $scope): View
    {
        /** @var TmsUser $actor */
        $actor = $request->attributes->get('tmsUser');
        abort_unless($scope->canManageTask($actor, $task) && $task->status === 'TODO', 403);

        return view('tms.tasks.form', $this->formData($actor) + ['task' => $task]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTmsTaskRequest $request, TmsTask $task, TaskScopeService $scope): RedirectResponse
    {
        /** @var TmsUser $actor */
        $actor = $request->attributes->get('tmsUser');
        abort_unless($scope->canManageTask($actor, $task) && $task->status === 'TODO', 403);
        $assignee = TmsUser::findOrFail($request->integer('assignee_id'));
        if (! $scope->canCreateTaskFor($actor, $assignee)) {
            throw new AuthorizationException('You cannot assign this task to that user.');
        }
        $data = $request->validated();
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
        } else {
            $data['hospital_name_snapshot'] = null;
            $data['address_snapshot'] = null;
        }

        $task->update(Arr::only($data, ['title', 'description', 'category_id', 'priority', 'business_unit_id', 'hospital_id', 'hospital_name_snapshot', 'address_snapshot', 'assignee_id', 'planned_at', 'due_at', 'verification_required', 'expected_outcome']) + ['assignee_role_snapshot' => $assignee->role]);
        TmsAuditLog::create(['actor_user_id' => $actor->id, 'action' => 'task.updated', 'entity_type' => 'task', 'entity_id' => $task->id, 'business_unit_id' => $task->business_unit_id, 'metadata' => ['fields' => array_keys($request->validated())]]);

        return redirect()->route('tms.tasks.show', $task)->with('success', 'Task updated.');
    }

    public function transition(Request $request, TmsTask $task, string $transition, TaskWorkflowService $workflow): RedirectResponse
    {
        /** @var TmsUser $actor */
        $actor = $request->attributes->get('tmsUser');
        $data = $request->validate([
            'outcome' => ['nullable', 'string', 'max:3000'],
            'completion_notes' => ['nullable', 'string', 'max:5000'],
            'person_met' => ['nullable', 'string', 'max:255'],
            'follow_up_at' => ['nullable', 'date'],
            'blocker_reason' => ['nullable', 'string', 'max:2000'],
            'reason' => ['nullable', 'string', 'max:2000'],
        ]);

        if (mb_strtoupper($transition) === 'REWORK' && blank($data['reason'] ?? null)) {
            throw ValidationException::withMessages(['reason' => 'Explain what the assignee needs to change.']);
        }

        $workflow->transition($actor, $task->load('assignee.manager'), mb_strtoupper($transition), $data);

        return back()->with('success', 'Task status updated.');
    }

    public function comment(Request $request, TmsTask $task, TaskScopeService $scope): RedirectResponse
    {
        /** @var TmsUser $actor */
        $actor = $request->attributes->get('tmsUser');
        abort_unless($scope->canViewTask($actor, $task), 404);
        $data = $request->validate(['body' => ['required', 'string', 'min:2', 'max:3000']]);
        TmsTaskComment::create(['task_id' => $task->id, 'author_id' => $actor->id, 'body' => $data['body']]);

        return back()->with('success', 'Comment added.');
    }

    private function formData(TmsUser $actor): array
    {
        $assignees = $actor->isRole('GM')
            ? TmsUser::with('defaultBusinessUnit')->whereIn('role', ['AM', 'BDE'])->where('is_active', true)->orderBy('name')->get()
            : TmsUser::with('defaultBusinessUnit')->where('role', 'BDE')->where('direct_manager_id', $actor->id)->where('is_active', true)->orderBy('name')->get();

        $businessUnits = $this->businessUnitsFor($actor);

        return [
            'assignees' => $assignees,
            'businessUnits' => $businessUnits,
            'categories' => TmsTaskCategory::where('is_active', true)->orderBy('sort_order')->get(),
            'hospitals' => TmsHospital::where('is_active', true)->whereIn('business_unit_id', $businessUnits->pluck('id'))->orderBy('name')->get(),
        ];
    }

    private function businessUnitsFor(TmsUser $actor): Collection
    {
        return $actor->isRole('GM') ? TmsBusinessUnit::where('is_active', true)->orderBy('name')->get() : $actor->businessUnits()->where('is_active', true)->orderBy('name')->get();
    }
}
