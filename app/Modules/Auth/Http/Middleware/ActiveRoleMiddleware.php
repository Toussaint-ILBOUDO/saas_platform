<?php

namespace App\Modules\Auth\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ActiveRoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $activeRole = session('active_role');

        if (!$activeRole) {
            return redirect()->route('role.select');
        }

        $activeRole = strtolower($activeRole);
        $roles = array_map('strtolower', $roles);

        if (!in_array($activeRole, $roles)) {
            return redirect()->route('dashboard');
        }

        return $next($request);
    }
}