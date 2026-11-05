<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Panel dilini seçer: giriş yapmış kullanıcının tercihi, yoksa oturumdaki seçim (giriş sayfası).
 */
class SetPanelLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->user()?->locale ?? $request->session()->get('locale');

        if (array_key_exists((string) $locale, config('app.panel_locales'))) {
            app()->setLocale($locale);
        }

        return $next($request);
    }
}
