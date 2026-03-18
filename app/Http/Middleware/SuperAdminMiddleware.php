<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SuperAdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (auth()->check() && auth()->user()->role_id === 1) { // Assuming 1 is the role_id for superadmin
            return $next($request);
        }

        return redirect('login')->with('error', 'You do not have superadmin access.');
    }
}