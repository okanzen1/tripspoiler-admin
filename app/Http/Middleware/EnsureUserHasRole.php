<?php

namespace App\Http\Middleware;

use App\Enums\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Kullanım: ->middleware('role:admin') veya ->middleware('role:admin,editor')
 */
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $allowed = array_map(fn (string $role) => Role::from($role), $roles);

        if ($request->user()?->hasRole(...$allowed)) {
            return $next($request);
        }

        // Yetkisiz sayfa linki açıldıysa 403 yerine ana sayfaya (analitik) gönder
        if ($request->isMethod('GET') && ! $request->expectsJson() && $request->user()) {
            return redirect()->route('analytics.index')
                ->with('error', __('Bu sayfayı görme yetkiniz yok.'));
        }

        abort(403);
    }
}
