<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Tasks') · TMS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://unpkg.com/lucide@0.468.0/dist/umd/lucide.min.js"></script>
    <style>body, h1, h2, h3 { font-family: Inter, ui-sans-serif, system-ui, sans-serif !important; }</style>
</head>
@php
    $icons = ['tasks' => 'square-check-big', 'verification' => 'badge-check', 'reports' => 'chart-no-axes-combined'];
    $navigation = config('tms.navigation.'.$tmsUser->role);
    $navigationUrl = fn (string $key) => match ($key) {
        'tasks' => route('tms.tasks.index'),
        'verification' => route('tms.verification'),
        'reports' => route('tms.reports'),
    };
    $activeKey = request()->routeIs('tms.verification') ? 'verification' : (request()->routeIs('tms.reports*') ? 'reports' : 'tasks');
@endphp
<body x-data="{ sidebar: false }" class="min-h-screen bg-[#f6f7f9] text-[#111827] selection:bg-blue-600 selection:text-white">
    <div x-show="sidebar" x-cloak x-transition.opacity class="fixed inset-0 z-40 bg-slate-950/40 lg:hidden" @click="sidebar=false"></div>
    <aside :class="sidebar ? 'translate-x-0' : '-translate-x-full'" class="fixed inset-y-0 left-0 z-50 flex w-[248px] flex-col border-r border-slate-200 bg-white transition-transform duration-200 lg:translate-x-0">
        <div class="flex h-[72px] items-center gap-3 border-b border-slate-100 px-5">
            <x-portfolio-back-button class="min-w-0 flex-1" />
            <button @click="sidebar=false" class="ml-auto grid size-10 place-items-center rounded-xl text-slate-500 hover:bg-slate-100 lg:hidden" aria-label="Close navigation"><i data-lucide="x" class="size-5"></i></button>
        </div>

        <div class="px-4 pt-5">
            <div class="rounded-xl bg-slate-50 p-3">
                <div class="flex items-center gap-2"><span class="size-2 rounded-full bg-emerald-500"></span><p class="text-[9px] font-bold tracking-[.12em] text-slate-500 uppercase">{{ $tmsUser->role }} workspace</p></div>
                <p class="mt-1.5 truncate text-xs font-bold">{{ $tmsUser->defaultBusinessUnit?->name ?? 'Pan India' }}</p>
            </div>
        </div>

        <nav class="flex-1 px-3 py-4" aria-label="TMS navigation">
            <p class="px-3 pb-2 text-[9px] font-bold tracking-[.15em] text-slate-400 uppercase">Workspace</p>
            <div class="grid gap-1">
                @foreach($navigation as $key => $label)
                    <a href="{{ $navigationUrl($key) }}" @class(['flex min-h-11 items-center gap-3 rounded-xl px-3 text-[12px] font-semibold transition', 'bg-blue-50 text-blue-700' => $activeKey === $key, 'text-slate-600 hover:bg-slate-50 hover:text-slate-950' => $activeKey !== $key])><i data-lucide="{{ $icons[$key] }}" class="size-[17px]"></i><span>{{ $label }}</span></a>
                @endforeach
            </div>
        </nav>

        <div class="border-t border-slate-100 p-3">
            <div class="flex items-center gap-3 rounded-xl p-2">
                <div class="grid size-9 shrink-0 place-items-center rounded-xl text-[11px] font-extrabold text-white" style="background:{{ $tmsUser->avatar_color }}">{{ collect(explode(' ', $tmsUser->name))->map(fn ($name) => mb_substr($name, 0, 1))->take(2)->join('') }}</div>
                <div class="min-w-0"><p class="truncate text-xs font-bold">{{ $tmsUser->name }}</p><p class="mt-0.5 text-[9px] text-slate-400">{{ $tmsUser->employee_code }}</p></div>
            </div>
            <form method="POST" action="{{ route('tms.logout') }}" class="mt-1">@csrf<button class="flex min-h-10 w-full items-center justify-center gap-2 rounded-xl text-[10px] font-bold text-slate-500 transition hover:bg-rose-50 hover:text-rose-600"><i data-lucide="log-out" class="size-4"></i>Logout</button></form>
        </div>
    </aside>

    <div class="min-h-screen pt-[72px] lg:pl-[248px]">
        <header class="fixed inset-x-0 top-0 z-30 flex h-[72px] items-center gap-3 border-b border-slate-200 bg-white/95 px-4 backdrop-blur sm:px-6 lg:left-[248px] lg:px-8">
            <button @click="sidebar=true" class="grid size-10 place-items-center rounded-xl hover:bg-slate-100 lg:hidden" aria-label="Open navigation"><i data-lucide="menu" class="size-5"></i></button>
            <div><p class="text-[9px] font-bold tracking-[.14em] text-slate-400 uppercase">Task Management System</p><p class="mt-0.5 text-xs font-extrabold">{{ $navigation[$activeKey] }}</p></div>
            <div class="ml-auto flex items-center gap-2">
                <form method="POST" action="{{ route('tms.logout') }}">@csrf<button class="inline-flex min-h-10 items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 text-[10px] font-bold text-slate-600 transition hover:border-rose-200 hover:bg-rose-50 hover:text-rose-600"><i data-lucide="log-out" class="size-4"></i><span class="hidden sm:inline">Logout</span></button></form>
            </div>
        </header>

        <main class="mx-auto max-w-[1540px] p-4 sm:p-6 lg:p-8">
            @if(session('success'))<div class="mb-5 flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs font-semibold text-emerald-800"><i data-lucide="circle-check" class="size-4"></i>{{ session('success') }}</div>@endif
            @if($errors->any())<div class="mb-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-xs text-rose-800"><p class="font-bold">Please correct the highlighted details.</p><ul class="mt-1 list-inside list-disc">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
            @yield('content')
        </main>
    </div>

    <script>document.addEventListener('DOMContentLoaded', () => window.lucide?.createIcons());</script>
</body>
</html>
