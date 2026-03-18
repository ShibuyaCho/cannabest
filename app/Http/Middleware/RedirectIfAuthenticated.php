<?php

namespace App\Http\Middleware;

use App\Providers\RouteServiceProvider;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RedirectIfAuthenticated
{
    public function handle(Request $request, Closure $next, ...$guards)
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                $user = Auth::guard($guard)->user();
                
                if ($user->isSuperAdmin()) {
                    return redirect()->route('superadmin.dashboard');
                } elseif ($user->isOrganizationAdmin()) {
                    return redirect()->route('admin.dashboard');
                } elseif ($user->isStandardUser()) {
                    return redirect()->route('user.dashboard');
                } else {
                    return redirect(RouteServiceProvider::HOME);
                }
            }
        }

        return $next($request);
    }
}
