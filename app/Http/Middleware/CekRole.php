<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class CekRole
{
    /**
     * Handle an incoming request.
     */
    public function handle($request, Closure $next, ...$roles)
    {
        if (!Auth::check()) {
            return redirect('/login');
        }

        $user = Auth::user();

        // Jika user role ada di parameter, allow. Jika tidak, tolak.
        if (in_array($user->role, $roles)) {
            return $next($request);
        }

        // Redirect ke halaman utama atau error
        return abort(403, 'Akses ditolak');
    }
}

