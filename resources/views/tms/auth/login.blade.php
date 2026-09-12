<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sign in · TMS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])<script src="https://unpkg.com/lucide@0.468.0/dist/umd/lucide.min.js"></script><style>body,h1,h2{font-family:Inter,ui-sans-serif,system-ui,sans-serif!important}</style>
</head>
<body class="min-h-screen bg-[#f5f7fb] pt-16 text-slate-950">
    <header class="fixed inset-x-0 top-0 z-50 border-b border-slate-200/80 bg-white/90 backdrop-blur-xl"><div class="mx-auto flex h-16 max-w-[1800px] items-center justify-between px-4 sm:px-8"><x-portfolio-back-button /><span class="text-right"><strong class="block text-[11px] font-extrabold text-slate-900">TMS</strong><small class="block text-[8px] font-bold tracking-[.1em] text-blue-600 uppercase">Task management</small></span></div></header>
    <main class="grid min-h-[calc(100vh-4rem)] lg:grid-cols-[1.05fr_.95fr]">
        <section class="relative hidden overflow-hidden bg-[#0a1020] p-12 text-white lg:flex lg:flex-col">
            <div class="absolute inset-0 opacity-70" style="background:radial-gradient(circle at 80% 10%,rgba(37,99,235,.55),transparent 35%),radial-gradient(circle at 30% 90%,rgba(79,70,229,.35),transparent 40%)"></div>
            <div class="relative my-auto max-w-xl"><p class="text-[10px] font-bold tracking-[.18em] text-blue-300 uppercase">Direction into daily action</p><h1 class="mt-5 text-5xl leading-[1.02] font-extrabold tracking-[-.065em]">Every task visible.<br><span class="text-slate-400">Every outcome clear.</span></h1><p class="mt-6 max-w-lg text-sm leading-7 text-slate-400">A Pan India operations command centre for General Managers, Assistant Managers, and field Business Development Executives.</p></div>
            <div class="relative grid grid-cols-3 gap-3">@foreach([['15','Business units'],['50','Field executives'],['500+','Demo tasks']] as [$value,$label])<div class="rounded-2xl border border-white/10 bg-white/[.06] p-4"><p class="text-2xl font-extrabold">{{ $value }}</p><p class="mt-1 text-[9px] text-slate-400">{{ $label }}</p></div>@endforeach</div>
        </section>
        <section class="flex items-center justify-center p-5 sm:p-10">
            <div class="w-full max-w-md">
                <div class="mb-8"><p class="text-[10px] font-bold tracking-[.16em] text-blue-600 uppercase">Welcome back</p><h2 class="mt-2 text-3xl font-extrabold tracking-[-.05em]">Sign in to TMS</h2><p class="mt-2 text-sm text-slate-500">Access your execution workspace.</p></div>
                @if($errors->any())<div class="mb-5 rounded-xl border border-rose-200 bg-rose-50 p-3 text-xs font-semibold text-rose-700">{{ $errors->first() }}</div>@endif
                <form method="POST" action="{{ route('tms.login.store') }}" class="grid gap-4">@csrf<label class="grid gap-1.5 text-xs font-bold text-slate-700">Email<input type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email" class="h-12 rounded-xl border-slate-300 bg-white text-sm" placeholder="you@company.com"></label><label class="grid gap-1.5 text-xs font-bold text-slate-700">Password<input type="password" name="password" required autocomplete="current-password" class="h-12 rounded-xl border-slate-300 bg-white text-sm" placeholder="••••••••"></label><button class="mt-1 h-12 rounded-xl bg-blue-600 text-xs font-bold text-white shadow-lg shadow-blue-600/20 transition hover:bg-blue-700">Sign in</button></form>
                @if(config('tms.demo_mode'))<div class="my-7 flex items-center gap-3 text-[9px] font-bold tracking-[.12em] text-slate-400 uppercase"><span class="h-px flex-1 bg-slate-200"></span>Explore a role<span class="h-px flex-1 bg-slate-200"></span></div><div class="grid grid-cols-3 gap-2">@foreach(['GM'=>'Tasks · Verify · Report','AM'=>'Tasks · Verify · Report','BDE'=>'Tasks only'] as $role=>$label)<form method="POST" action="{{ route('tms.demo', $role) }}">@csrf<button class="flex min-h-[74px] w-full flex-col items-center justify-center rounded-xl border border-slate-200 bg-white px-2 text-center transition hover:-translate-y-0.5 hover:border-blue-300 hover:shadow-md"><span class="text-sm font-extrabold text-slate-950">{{ $role }}</span><span class="mt-1 text-[8px] leading-3 text-slate-400">{{ $label }}</span></button></form>@endforeach</div>@endif
            </div>
        </section>
    </main>
    <script>document.addEventListener('DOMContentLoaded',()=>window.lucide?.createIcons());</script>
</body>
</html>
