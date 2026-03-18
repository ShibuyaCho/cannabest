<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|array  $roles
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        if (!$request->user()) {
            \Log::info('User not authenticated');
            return redirect('login');
        }

        $userRoleId = $request->user()->role_id;
        \Log::info('User role_id: ' . $userRoleId);
        \Log::info('Required roles: ' . implode(', ', $roles));

        // Check if the user's role_id is 2 (admin) or if it's in the required roles
        if ($userRoleId == 2 || in_array($userRoleId, $roles) || in_array('admin', $roles)) {
            \Log::info('Access granted. User role matches required roles or is admin.');
            return $next($request);
        }

        \Log::info('Access denied. User role not in required roles and not admin.');
        abort(403, 'Unauthorized action.');
    }
}
