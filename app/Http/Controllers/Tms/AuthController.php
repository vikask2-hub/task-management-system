<?php

namespace App\Http\Controllers\Tms;

use App\Http\Controllers\Controller;
use App\Models\TmsUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function create(): View|RedirectResponse
    {
        if (session()->has('tms_user_id')) {
            return redirect()->route('tms.tasks.index');
        }

        return view('tms.auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'email' => mb_strtolower(trim((string) $request->input('email'))),
        ]);

        $credentials = $request->validate(['email' => ['required', 'email', 'max:255'], 'password' => ['required', 'string']]);
        $user = TmsUser::where('email', $credentials['email'])->first();

        if (! $user || ! $user->is_active || ! Hash::check($credentials['password'], $user->password)) {
            return back()->withErrors(['email' => 'The email or password is incorrect.'])->onlyInput('email');
        }

        $request->session()->regenerate();
        $request->session()->put('tms_user_id', $user->id);

        return redirect()->route('tms.tasks.index');
    }

    public function demo(Request $request, string $role): RedirectResponse
    {
        abort_unless(config('tms.demo_mode'), 404);
        abort_unless(in_array($role, ['GM', 'AM', 'BDE'], true), 404);

        $user = TmsUser::where('role', $role)->where('is_active', true)->oldest('id')->firstOrFail();
        $request->session()->regenerate();
        $request->session()->put('tms_user_id', $user->id);

        return redirect()->route('tms.tasks.index');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->session()->forget('tms_user_id');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('tms.login');
    }
}
