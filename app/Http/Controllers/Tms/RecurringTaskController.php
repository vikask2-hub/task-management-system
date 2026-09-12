<?php

namespace App\Http\Controllers\Tms;

use App\Http\Controllers\Controller;
use App\Models\TmsBusinessUnit;
use App\Models\TmsRecurringTemplate;
use App\Models\TmsTaskCategory;
use App\Models\TmsUser;
use App\Tms\TaskScopeService;
use App\Tms\TaskWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RecurringTaskController extends Controller
{
    public function index(Request $request): View
    {
        /** @var TmsUser $actor */
        $actor = $request->attributes->get('tmsUser');
        $templates = TmsRecurringTemplate::with(['assignee', 'category'])
            ->when($actor->isRole('AM'), fn ($query) => $query->where('created_by_id', $actor->id))
            ->latest()
            ->paginate(20);

        $assignees = $actor->isRole('GM')
            ? TmsUser::whereIn('role', ['AM', 'BDE'])->where('is_active', true)->orderBy('name')->get()
            : TmsUser::where('direct_manager_id', $actor->id)->where('is_active', true)->orderBy('name')->get();

        return view('tms.recurring', [
            'templates' => $templates,
            'assignees' => $assignees,
            'categories' => TmsTaskCategory::where('is_active', true)->orderBy('sort_order')->get(),
            'businessUnits' => $actor->isRole('GM') ? TmsBusinessUnit::where('is_active', true)->orderBy('name')->get() : $actor->businessUnits,
        ]);
    }

    public function store(Request $request, TaskScopeService $scope): RedirectResponse
    {
        /** @var TmsUser $actor */
        $actor = $request->attributes->get('tmsUser');
        $data = $this->validateTemplate($request);
        $assignee = TmsUser::findOrFail($data['assignee_id']);
        abort_unless($scope->canCreateTaskFor($actor, $assignee), 403);
        TmsRecurringTemplate::create($data + ['created_by_id' => $actor->id]);

        return back()->with('success', 'Recurring task created.');
    }

    public function update(Request $request, TmsRecurringTemplate $recurringTemplate): RedirectResponse
    {
        /** @var TmsUser $actor */
        $actor = $request->attributes->get('tmsUser');
        abort_unless($actor->isRole('GM') || $recurringTemplate->created_by_id === $actor->id, 403);
        $recurringTemplate->update($this->validateTemplate($request));

        return back()->with('success', 'Recurring task updated.');
    }

    public function destroy(Request $request, TmsRecurringTemplate $recurringTemplate): RedirectResponse
    {
        /** @var TmsUser $actor */
        $actor = $request->attributes->get('tmsUser');
        abort_unless($actor->isRole('GM') || $recurringTemplate->created_by_id === $actor->id, 403);
        $recurringTemplate->update(['is_active' => ! $recurringTemplate->is_active]);

        return back()->with('success', $recurringTemplate->is_active ? 'Recurring task resumed.' : 'Recurring task paused.');
    }

    public function generate(Request $request, TmsRecurringTemplate $recurringTemplate, TaskWorkflowService $workflow): RedirectResponse
    {
        /** @var TmsUser $actor */
        $actor = $request->attributes->get('tmsUser');
        abort_unless($recurringTemplate->is_active && ($actor->isRole('GM') || $recurringTemplate->created_by_id === $actor->id), 403);
        $date = now()->toDateString();
        $occurrenceKey = $recurringTemplate->id.'-'.$recurringTemplate->assignee_id.'-'.$date;

        if ($recurringTemplate->tasks()->where('occurrence_key', $occurrenceKey)->exists()) {
            return back()->with('success', 'Today’s occurrence already exists.');
        }

        $task = $workflow->create($actor, [
            'title' => $recurringTemplate->title,
            'description' => $recurringTemplate->description,
            'category_id' => $recurringTemplate->category_id,
            'priority' => $recurringTemplate->priority,
            'business_unit_id' => $recurringTemplate->business_unit_id,
            'assignee_id' => $recurringTemplate->assignee_id,
            'planned_at' => now()->startOfDay(),
            'due_at' => now()->setTimeFromTimeString($recurringTemplate->preferred_due_time),
            'verification_required' => $recurringTemplate->verification_required,
            'attachment_required' => $recurringTemplate->attachment_required,
            'geo_requested' => $recurringTemplate->geo_requested,
            'source' => 'RECURRING',
        ]);
        $task->update(['recurring_template_id' => $recurringTemplate->id, 'occurrence_key' => $occurrenceKey]);
        $recurringTemplate->update(['last_generated_for_date' => $date]);

        return back()->with('success', 'Today’s task occurrence generated.');
    }

    private function validateTemplate(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'title' => ['required', 'string', 'min:3', 'max:150'],
            'description' => ['nullable', 'string', 'max:5000'],
            'category_id' => ['required', 'exists:tms_task_categories,id'],
            'priority' => ['required', Rule::in(['LOW', 'MEDIUM', 'HIGH', 'URGENT'])],
            'business_unit_id' => ['required', 'exists:tms_business_units,id'],
            'assignee_id' => ['required', 'exists:tms_users,id'],
            'verification_required' => ['nullable', 'boolean'],
            'attachment_required' => ['nullable', 'boolean'],
            'geo_requested' => ['nullable', 'boolean'],
            'schedule_type' => ['required', Rule::in(['DAILY', 'WEEKDAYS', 'WEEKLY', 'MONTHLY'])],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'preferred_due_time' => ['required', 'date_format:H:i'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }
}
