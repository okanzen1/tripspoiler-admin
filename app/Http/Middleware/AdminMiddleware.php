<?php

namespace App\Http\Middleware;

use Illuminate\Support\Facades\Auth;

class AdminMiddleware
{
    public function handle($request, \Closure $next)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        return $next($request);
    }
}
