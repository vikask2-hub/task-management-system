@extends('layouts.tms')
@php
    $isVerification = request()->routeIs('tms.verification');
@endphp
@section('title', $isVerification ? 'Verification' : 'Tasks')
@section('content')
@php
    $statusStyles = ['TODO'=>'bg-slate-100 text-slate-600','IN_PROGRESS'=>'bg-blue-50 text-blue-700','SUBMITTED'=>'bg-violet-50 text-violet-700','COMPLETED'=>'bg-emerald-50 text-emerald-700','BLOCKED'=>'bg-rose-50 text-rose-700','CANCELLED'=>'bg-slate-100 text-slate-400'];
    $priorityStyles = ['LOW'=>'bg-slate-100 text-slate-600','MEDIUM'=>'bg-blue-50 text-blue-700','HIGH'=>'bg-amber-50 text-amber-700','URGENT'=>'bg-rose-50 text-rose-700'];
    $resetRoute = $isVerification ? route('tms.verification') : route('tms.tasks.index');
@endphp

<div x-data="{ createBde: @js($errors->any()) }">
<div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <p class="text-[10px] font-bold tracking-[.15em] text-blue-600 uppercase">{{ $isVerification ? 'Ready for review' : 'Simple execution' }}</p>
        <h1 class="mt-1 text-2xl font-extrabold tracking-[-.045em] sm:text-3xl">{{ $isVerification ? 'Verification' : ($tmsUser->isRole('BDE') ? 'My tasks' : 'Tasks') }}</h1>
        <p class="mt-1 text-xs text-slate-500">{{ $isVerification ? 'Review submitted work and complete or return it.' : ($tmsUser->isRole('BDE') ? 'Start work, record outcomes, and keep progress clear.' : 'Assign work and keep execution moving.') }}</p>
    </div>
    @if(! $isVerification && $tmsUser->isRole('GM', 'AM'))
        <div class="flex flex-wrap gap-2">
            @if($tmsUser->isRole('AM'))
                <button type="button" @click="createBde = true" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-xs font-bold text-slate-700 shadow-sm transition hover:border-blue-200 hover:text-blue-700"><i data-lucide="user-plus" class="size-4"></i>Add BDE</button>
            @endif
            <a href="{{ route('tms.tasks.create') }}" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 text-xs font-bold text-white shadow-sm transition hover:bg-blue-700"><i data-lucide="plus" class="size-4"></i>Create task</a>
        </div>
    @endif
</div>

<section class="mt-5 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <form method="GET" action="{{ $isVerification ? route('tms.verification') : route('tms.tasks.index') }}" @class(['grid gap-2 border-b border-slate-100 bg-slate-50/60 p-3 sm:grid-cols-2', 'lg:grid-cols-[1.5fr_repeat(2,1fr)_auto]' => $isVerification, 'lg:grid-cols-[1.5fr_repeat(3,1fr)_auto]' => ! $isVerification])>
        <label class="relative"><span class="sr-only">Search</span><i data-lucide="search" class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400"></i><input name="search" value="{{ request('search') }}" class="h-10 w-full rounded-xl border-slate-200 bg-white pl-10 text-xs" placeholder="Search tasks…"></label>
        @if($isVerification)
            <input type="hidden" name="status" value="SUBMITTED">
        @else
            <select name="status" class="h-10 rounded-xl border-slate-200 bg-white text-xs"><option value="">All statuses</option>@foreach(['TODO','IN_PROGRESS','SUBMITTED','BLOCKED','COMPLETED','CANCELLED'] as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ str($status)->replace('_',' ')->title() }}</option>@endforeach</select>
        @endif
        <select name="priority" class="h-10 rounded-xl border-slate-200 bg-white text-xs"><option value="">All priorities</option>@foreach(['LOW','MEDIUM','HIGH','URGENT'] as $priority)<option value="{{ $priority }}" @selected(request('priority') === $priority)>{{ str($priority)->title() }}</option>@endforeach</select>
        <select name="business_unit_id" class="h-10 rounded-xl border-slate-200 bg-white text-xs"><option value="">All business units</option>@foreach($businessUnits as $unit)<option value="{{ $unit->id }}" @selected((int) request('business_unit_id') === $unit->id)>{{ $unit->name }}</option>@endforeach</select>
        <div class="flex gap-2"><button class="h-10 flex-1 rounded-xl bg-slate-950 px-4 text-[10px] font-bold text-white">Apply</button><a href="{{ $resetRoute }}" class="grid size-10 place-items-center rounded-xl border border-slate-200 bg-white text-slate-500" aria-label="Reset filters"><i data-lucide="rotate-ccw" class="size-4"></i></a></div>
    </form>

    <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3 sm:px-5">
        <p class="text-[10px] font-bold text-slate-600">{{ $tasks->total() }} {{ $isVerification ? 'submitted tasks' : 'tasks' }}</p>
        @if(request('overdue'))<span class="rounded-full bg-rose-50 px-3 py-1.5 text-[9px] font-bold text-rose-700">Overdue only</span>@endif
    </div>

    <div class="hidden grid-cols-[1.8fr_.8fr_.75fr_.7fr_.65fr_auto] gap-3 border-b border-slate-100 bg-white px-5 py-3 text-[8px] font-bold tracking-[.1em] text-slate-400 uppercase lg:grid"><span>Task</span><span>Assignee</span><span>Business unit</span><span>Due</span><span>Status</span><span></span></div>
    <div class="divide-y divide-slate-100">
        @forelse($tasks as $task)
            <a href="{{ route('tms.tasks.show', $task) }}" class="grid gap-3 px-4 py-4 transition hover:bg-slate-50 lg:grid-cols-[1.8fr_.8fr_.75fr_.7fr_.65fr_auto] lg:items-center lg:px-5">
                <div class="min-w-0"><div class="flex items-center gap-2"><span class="rounded-full px-2 py-0.5 text-[7px] font-bold {{ $priorityStyles[$task->priority] }}">{{ $task->priority }}</span>@if($task->isOverdue())<span class="rounded-full bg-rose-50 px-2 py-0.5 text-[7px] font-bold text-rose-700">OVERDUE</span>@endif</div><p class="mt-2 truncate text-[11px] font-bold">{{ $task->title }}</p><p class="mt-1 text-[8px] text-slate-400">{{ $task->task_number }} · {{ $task->category->name }}</p></div>
                <div class="flex items-center gap-2"><span class="grid size-7 place-items-center rounded-lg text-[8px] font-bold text-white" style="background:{{ $task->assignee->avatar_color }}">{{ collect(explode(' ', $task->assignee->name))->map(fn ($name) => mb_substr($name, 0, 1))->take(2)->join('') }}</span><span class="text-[9px] font-semibold">{{ $task->assignee->name }}</span></div>
                <p class="text-[9px] text-slate-500">{{ $task->businessUnit->name }}</p>
                <div><p class="text-[9px] font-semibold">{{ $task->due_at->format('d M Y') }}</p><p class="mt-0.5 text-[8px] text-slate-400">{{ $task->due_at->format('g:i A') }}</p></div>
                <span class="w-fit rounded-full px-2.5 py-1 text-[7px] font-bold {{ $statusStyles[$task->status] }}">{{ str($task->status)->replace('_', ' ') }}</span>
                <i data-lucide="chevron-right" class="hidden size-4 text-slate-300 lg:block"></i>
            </a>
        @empty
            <div class="py-16 text-center"><span class="mx-auto grid size-12 place-items-center rounded-2xl bg-slate-50 text-slate-400"><i data-lucide="circle-check-big" class="size-5"></i></span><p class="mt-3 text-xs font-bold">{{ $isVerification ? 'Nothing waiting for review' : 'No tasks found' }}</p><p class="mt-1 text-[10px] text-slate-400">{{ $isVerification ? 'All submitted work has been handled.' : 'Reset filters or create a new task.' }}</p></div>
        @endforelse
    </div>
</section>

<div class="mt-4">{{ $tasks->links() }}</div>

@if(! $isVerification && $tmsUser->isRole('AM'))
    <div x-show="createBde" x-cloak x-transition.opacity class="fixed inset-0 z-[70] flex items-end justify-center bg-slate-950/45 p-0 sm:items-center sm:p-5" @click.self="createBde = false">
        <form method="POST" action="{{ route('tms.bdes.store') }}" class="max-h-[92vh] w-full max-w-lg overflow-y-auto rounded-t-2xl bg-white p-5 shadow-2xl sm:rounded-2xl sm:p-6" role="dialog" aria-modal="true" aria-labelledby="create-bde-title">
            @csrf
            <div class="flex items-start justify-between gap-4">
                <div><p class="text-[9px] font-bold tracking-[.14em] text-blue-600 uppercase">Your field team</p><h2 id="create-bde-title" class="mt-1 text-lg font-extrabold">Add a BDE</h2><p class="mt-1 text-[10px] leading-4 text-slate-500">Create secure access for a Business Development Executive who reports to you.</p></div>
                <button type="button" @click="createBde = false" class="grid size-9 shrink-0 place-items-center rounded-lg bg-slate-100 text-slate-500" aria-label="Close dialog"><i data-lucide="x" class="size-4"></i></button>
            </div>
            <div class="mt-5 grid gap-4 sm:grid-cols-2">
                <label class="grid gap-1.5 text-[10px] font-bold text-slate-600 sm:col-span-2">Full name<input name="name" value="{{ old('name') }}" required maxlength="255" autocomplete="name" class="h-11 rounded-xl border-slate-200 text-xs" placeholder="e.g. Aarav Sharma">@error('name')<span class="font-medium text-rose-600">{{ $message }}</span>@enderror</label>
                <label class="grid gap-1.5 text-[10px] font-bold text-slate-600">Work email<input type="email" name="email" value="{{ old('email') }}" required maxlength="255" autocomplete="email" class="h-11 rounded-xl border-slate-200 text-xs" placeholder="aarav@company.com">@error('email')<span class="font-medium text-rose-600">{{ $message }}</span>@enderror</label>
                <label class="grid gap-1.5 text-[10px] font-bold text-slate-600">Phone <span class="font-normal text-slate-400">(optional)</span><input name="phone" value="{{ old('phone') }}" maxlength="30" autocomplete="tel" class="h-11 rounded-xl border-slate-200 text-xs" placeholder="+91 98765 43210">@error('phone')<span class="font-medium text-rose-600">{{ $message }}</span>@enderror</label>
                <label class="grid gap-1.5 text-[10px] font-bold text-slate-600 sm:col-span-2">Business unit<select name="business_unit_id" required class="h-11 rounded-xl border-slate-200 text-xs">@foreach($businessUnits as $unit)<option value="{{ $unit->id }}" @selected((int) old('business_unit_id', $tmsUser->default_business_unit_id) === $unit->id)>{{ $unit->name }}</option>@endforeach</select>@error('business_unit_id')<span class="font-medium text-rose-600">{{ $message }}</span>@enderror</label>
                <label class="grid gap-1.5 text-[10px] font-bold text-slate-600">Temporary password<input type="password" name="password" required minlength="8" maxlength="255" autocomplete="new-password" class="h-11 rounded-xl border-slate-200 text-xs" placeholder="Minimum 8 characters">@error('password')<span class="font-medium text-rose-600">{{ $message }}</span>@enderror</label>
                <label class="grid gap-1.5 text-[10px] font-bold text-slate-600">Confirm password<input type="password" name="password_confirmation" required minlength="8" maxlength="255" autocomplete="new-password" class="h-11 rounded-xl border-slate-200 text-xs" placeholder="Repeat password"></label>
            </div>
            <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <button type="button" @click="createBde = false" class="min-h-11 rounded-xl px-5 text-xs font-bold text-slate-500">Cancel</button>
                <button class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-blue-600 px-5 text-xs font-bold text-white shadow-lg shadow-blue-600/20"><i data-lucide="user-plus" class="size-4"></i>Create BDE</button>
            </div>
        </form>
    </div>
@endif
</div>
@endsection
