<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Panele giren ekip (admin / editör) analitikte otomatik sayılmaz:
 * 1) Bu tarayıcıya site alan adı için "ts_staff" çerezi yazılır (IP değişse de, telefonda da çalışır)
 * 2) O anki IP "sayılmayan IP'ler" listesine eklenir (Setting analytics_ignore_ips; başka tarayıcıdan girse de)
 * Site tarafı: App\Support\Tracker::isIgnoredIp.
 */
class MarkStaffDevice
{
    public const COOKIE = 'ts_staff';

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        if (!$request->user()) {
            return $response;
        }

        $ip = $request->ip();
        if ($ip && !Cache::has("staff-ip:{$ip}")) {
            $list = array_values(array_filter(array_map('trim', explode(',', (string) Setting::get('analytics_ignore_ips')))));
            if (!in_array($ip, $list, true)) {
                $list[] = $ip;
                Setting::put('analytics_ignore_ips', implode(',', array_slice($list, -20)));
            }
            Cache::put("staff-ip:{$ip}", true, now()->addDay());
        }

        if ($request->cookie(self::COOKIE) !== '1') {
            $response->headers->setCookie(cookie(self::COOKIE, '1', 60 * 24 * 365, '/', self::domain(), $request->isSecure(), true, false, 'lax'));
        }

        return $response;
    }

    /** admin.tripspoiler.com → ".tripspoiler.com" (site de okur); yerelde (localhost) alan adı verilmez */
    private static function domain(): ?string
    {
        $site = (string) parse_url(config('site.url'), PHP_URL_HOST);
        $panel = (string) request()->getHost();

        return $site !== '' && str_ends_with($panel, '.' . $site) ? '.' . $site : null;
    }
}
