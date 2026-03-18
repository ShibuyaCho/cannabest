<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleBasedRedirection
{
    public function handle(Request $request, Closure $next)
    {
        if (auth()->check()) {
            $role_id = auth()->user()->role_id;
                $currentPath = $request->path();
    
                switch ($role_id) {
                    case 4:  // Customer
                        if (!str_starts_with($currentPath, 'thcfg') && !$this->isCommonRoute($currentPath)) {
                            return redirect('/thcfg');
                        }
                        break;
                    case 5:  // Wholesale
                        if (!str_starts_with($currentPath, 'wholesale') && !$this->isCommonRoute($currentPath)) {
                            return redirect()->route('wholesale.profile');
                        }
                        break;
                    case 3:  // Budtender / POS user
                        if (!str_starts_with($currentPath, 'sales') && !$this->isCommonRoute($currentPath)) {
                            return redirect('/sales/create');
                        }
                        break;
                    default: // Admin, etc.
                        if (!str_starts_with($currentPath, 'admin') && !$this->isCommonRoute($currentPath)) {
                            return redirect('/admin');
                        }
                        break;
                }
            }
    
            return $next($request);
        }
    
        private function isCommonRoute($path)
        {
            $commonRoutes = ['logout', 'profile', 'password/reset'];
            return in_array($path, $commonRoutes) || str_starts_with($path, 'api/');
    }
}