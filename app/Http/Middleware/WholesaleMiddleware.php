<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class WholesaleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check() || !in_array(auth()->user()->role_id, [2, 5])) {
            if (auth()->check()) {
                // User is logged in but not a wholesale user or admin
                return redirect('/'); // or wherever non-wholesale users should go
            }
            // User is not logged in
            return redirect()->route('login');
        }

        return $next($request);
    }
}
