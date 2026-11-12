<?php

namespace App\Support;

/**
 * Panel bölümlerinin renkleri: menü ikonu, sayfa başlığı ikonu ve Yönetim kartları aynı rengi kullanır.
 * Tailwind sınıfları burada tam yazılır ki derlemede bulunsun (resources/css/app.css @source).
 */
class SectionColor
{
    /** Rota adı öneki → renk */
    private const ROUTES = [
        'analytics' => 'sky',
        'activities' => 'emerald',
        'blogs' => 'violet',
        'faqs' => 'amber',
        'campaigns' => 'orange',
        'reviews' => 'rose',
        'mailbox' => 'blue',
        'mail-templates' => 'blue',
        'bulk-email' => 'blue',
        'subscribers' => 'teal',
        'management' => 'orange',
        'locations' => 'cyan',
        'countries' => 'cyan',
        'cities' => 'cyan',
        'pages' => 'fuchsia',
        'partners' => 'lime',
        'users' => 'indigo',
        'roles' => 'pink',
        'translations' => 'purple',
        'languages' => 'purple',
        'redirects' => 'yellow',
        'site-settings' => 'rose',
        'activity-logs' => 'teal',
        'media-trash' => 'rose',
        'queue' => 'blue',
    ];

    private const CLASSES = [
        'sky' => ['solid' => 'bg-sky-500 text-white', 'soft' => 'bg-sky-50 text-sky-700', 'nav' => 'bg-sky-400/15 text-sky-300', 'text' => 'text-sky-600'],
        'emerald' => ['solid' => 'bg-emerald-500 text-white', 'soft' => 'bg-emerald-50 text-emerald-700', 'nav' => 'bg-emerald-400/15 text-emerald-300', 'text' => 'text-emerald-600'],
        'violet' => ['solid' => 'bg-violet-500 text-white', 'soft' => 'bg-violet-50 text-violet-700', 'nav' => 'bg-violet-400/15 text-violet-300', 'text' => 'text-violet-600'],
        'amber' => ['solid' => 'bg-amber-500 text-white', 'soft' => 'bg-amber-50 text-amber-700', 'nav' => 'bg-amber-400/15 text-amber-300', 'text' => 'text-amber-600'],
        'rose' => ['solid' => 'bg-rose-500 text-white', 'soft' => 'bg-rose-50 text-rose-700', 'nav' => 'bg-rose-400/15 text-rose-300', 'text' => 'text-rose-600'],
        'blue' => ['solid' => 'bg-blue-500 text-white', 'soft' => 'bg-blue-50 text-blue-700', 'nav' => 'bg-blue-400/15 text-blue-300', 'text' => 'text-blue-600'],
        'teal' => ['solid' => 'bg-teal-500 text-white', 'soft' => 'bg-teal-50 text-teal-700', 'nav' => 'bg-teal-400/15 text-teal-300', 'text' => 'text-teal-600'],
        'orange' => ['solid' => 'bg-orange-500 text-white', 'soft' => 'bg-orange-50 text-orange-700', 'nav' => 'bg-orange-400/15 text-orange-300', 'text' => 'text-orange-600'],
        'cyan' => ['solid' => 'bg-cyan-500 text-white', 'soft' => 'bg-cyan-50 text-cyan-700', 'nav' => 'bg-cyan-400/15 text-cyan-300', 'text' => 'text-cyan-600'],
        'fuchsia' => ['solid' => 'bg-fuchsia-500 text-white', 'soft' => 'bg-fuchsia-50 text-fuchsia-700', 'nav' => 'bg-fuchsia-400/15 text-fuchsia-300', 'text' => 'text-fuchsia-600'],
        'lime' => ['solid' => 'bg-lime-600 text-white', 'soft' => 'bg-lime-50 text-lime-700', 'nav' => 'bg-lime-400/15 text-lime-300', 'text' => 'text-lime-700'],
        'indigo' => ['solid' => 'bg-indigo-500 text-white', 'soft' => 'bg-indigo-50 text-indigo-700', 'nav' => 'bg-indigo-400/15 text-indigo-300', 'text' => 'text-indigo-600'],
        'pink' => ['solid' => 'bg-pink-500 text-white', 'soft' => 'bg-pink-50 text-pink-700', 'nav' => 'bg-pink-400/15 text-pink-300', 'text' => 'text-pink-600'],
        'purple' => ['solid' => 'bg-purple-500 text-white', 'soft' => 'bg-purple-50 text-purple-700', 'nav' => 'bg-purple-400/15 text-purple-300', 'text' => 'text-purple-600'],
        'yellow' => ['solid' => 'bg-yellow-500 text-white', 'soft' => 'bg-yellow-50 text-yellow-700', 'nav' => 'bg-yellow-400/15 text-yellow-300', 'text' => 'text-yellow-700'],
        'brand' => ['solid' => 'bg-brand-900 text-white', 'soft' => 'bg-brand-50 text-brand-900', 'nav' => 'bg-white/10 text-white/70', 'text' => 'text-brand-700'],
    ];

    /** Rota adına göre renk (ör. "activities.edit" → emerald) */
    public static function forRoute(?string $route): string
    {
        return self::ROUTES[strtok((string) $route, '.')] ?? 'brand';
    }

    /** @param  'solid'|'soft'|'nav'|'text'  $variant */
    public static function classes(string $color, string $variant): string
    {
        return (self::CLASSES[$color] ?? self::CLASSES['brand'])[$variant];
    }
}
