<?php

namespace App\Http\Middleware;

use App\Models\TmsUser;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class EnsureTmsAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $userId = $request->session()->get('tms_user_id');
        $user = $userId ? TmsUser::with(['businessUnits', 'defaultBusinessUnit'])->find($userId) : null;

        if (! $user || ! $user->is_active) {
            $request->session()->forget('tms_user_id');

            return redirect()->route('tms.login');
        }

        $request->attributes->set('tmsUser', $user);
        View::share('tmsUser', $user);

        return $next($request);
    }
}
