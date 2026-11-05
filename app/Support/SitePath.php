<?php

namespace App\Support;

/**
 * Front'taki sayfa adresi (config/site.php kalıplarından).
 */
class SitePath
{
    public static function for(string $type, string $locale, string $slug): string
    {
        return strtr(config("site.paths.{$type}"), ['{locale}' => $locale, '{slug}' => $slug]);
    }
}
