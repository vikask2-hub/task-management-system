<?php

namespace App\Http\Controllers\Tms;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTmsUserRequest;
use App\Models\TmsAuditLog;
use App\Models\TmsBusinessUnit;
use App\Models\TmsNotification;
use App\Models\TmsTaskCategory;
use App\Models\TmsUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ManagementController extends Controller
{
    public function team(Request $request): View
    {
        /** @var TmsUser $actor */
        $actor = $request->attributes->get('tmsUser');
        $users = TmsUser::with(['manager', 'defaultBusinessUnit'])
            ->when($actor->isRole('AM'), fn ($query) => $query->where('direct_manager_id', $actor->id))
            ->when($request->filled('role'), fn ($query) => $query->where('role', $request->string('role')->toString()))
            ->orderByRaw("CASE role WHEN 'GM' THEN 1 WHEN 'AM' THEN 2 ELSE 3 END")
            ->orderBy('name')
            ->paginate(24)
            ->withQueryString();

        return view('tms.management.team', ['users' => $users, 'managers' => TmsUser::where('role', 'AM')->where('is_active', true)->orderBy('name')->get(), 'businessUnits' => TmsBusinessUnit::orderBy('name')->get()]);
    }

    public function storeUser(StoreTmsUserRequest $request): RedirectResponse
    {
        $user = TmsUser::create($request->validated());
        if ($user->default_business_unit_id) {
            $user->businessUnits()->sync([$user->default_business_unit_id => ['is_primary' => true]]);
        }

        return back()->with('success', 'Team member created.');
    }

    public function updateUser(StoreTmsUserRequest $request, TmsUser $tmsUser): RedirectResponse
    {
        $data = $request->validated();
        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }
        $tmsUser->update($data);
        if ($tmsUser->default_business_unit_id) {
            $tmsUser->businessUnits()->sync([$tmsUser->default_business_unit_id => ['is_primary' => true]]);
        }

        return back()->with('success', 'Team member updated.');
    }

    public function masterData(): View
    {
        return view('tms.management.master-data', [
            'businessUnits' => TmsBusinessUnit::withCount(['users', 'tasks'])->orderBy('name')->get(),
            'categories' => TmsTaskCategory::withCount('tasks')->orderBy('sort_order')->get(),
        ]);
    }

    public function storeBusinessUnit(Request $request): RedirectResponse
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:20', 'unique:tms_business_units,code'], 'name' => ['required', 'string', 'max:255', 'unique:tms_business_units,name'], 'city' => ['required', 'string', 'max:100'], 'state' => ['nullable', 'string', 'max:100'], 'region' => ['nullable', 'string', 'max:100']]);
        TmsBusinessUnit::create($data);

        return back()->with('success', 'Business unit created.');
    }

    public function updateBusinessUnit(Request $request, TmsBusinessUnit $businessUnit): RedirectResponse
    {
        $businessUnit->update($request->validate(['name' => ['required', 'string', 'max:255'], 'city' => ['required', 'string', 'max:100'], 'state' => ['nullable', 'string', 'max:100'], 'region' => ['nullable', 'string', 'max:100'], 'is_active' => ['required', 'boolean']]));

        return back()->with('success', 'Business unit updated.');
    }

    public function storeCategory(Request $request): RedirectResponse
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:40', 'unique:tms_task_categories,code'], 'name' => ['required', 'string', 'max:255', 'unique:tms_task_categories,name'], 'description' => ['nullable', 'string', 'max:500'], 'icon_key' => ['nullable', 'string', 'max:50']]);
        $data['sort_order'] = TmsTaskCategory::max('sort_order') + 1;
        TmsTaskCategory::create($data);

        return back()->with('success', 'Task category created.');
    }

    public function updateCategory(Request $request, TmsTaskCategory $category): RedirectResponse
    {
        $category->update($request->validate(['name' => ['required', 'string', 'max:255'], 'description' => ['nullable', 'string', 'max:500'], 'icon_key' => ['nullable', 'string', 'max:50'], 'is_active' => ['required', 'boolean']]));

        return back()->with('success', 'Task category updated.');
    }

    public function notifications(Request $request): View
    {
        /** @var TmsUser $actor */
        $actor = $request->attributes->get('tmsUser');

        return view('tms.notifications', ['notifications' => TmsNotification::where('user_id', $actor->id)->latest()->paginate(25)]);
    }

    public function markNotificationRead(Request $request, TmsNotification $notification): RedirectResponse
    {
        /** @var TmsUser $actor */
        $actor = $request->attributes->get('tmsUser');
        abort_unless($notification->user_id === $actor->id, 404);
        $notification->update(['is_read' => true, 'read_at' => now()]);

        return back();
    }

    public function audit(Request $request): View
    {
        /** @var TmsUser $actor */
        $actor = $request->attributes->get('tmsUser');
        $logs = TmsAuditLog::with('actor')
            ->when($actor->isRole('AM'), fn ($query) => $query->whereIn('business_unit_id', $actor->businessUnits->pluck('id')))
            ->latest()
            ->paginate(30);

        return view('tms.audit', compact('logs'));
    }
}
