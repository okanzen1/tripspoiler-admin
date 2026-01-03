<?php

namespace App\Http\Middleware;

use Illuminate\Support\Facades\Auth;

class AdminMiddleware
{
    public function handle($request, \Closure $next)
    {
        if (!Auth::check()) {
            return redirect()->route('admin.login');
        }

        if (!in_array(Auth::user()->role, ['admin', 'superadmin'])) {
            abort(403, 'Yetkin yok');
        }

        return $next($request);
    }
}
