@props(['route', 'label', 'match' => null, 'badge' => 0, 'badgeTitle' => null, 'badgeTone' => 'alert', 'todo' => 0, 'seo' => 0])

@php
    $active = request()->routeIs(...($match ?? [$route]));
    $color = \App\Support\SectionColor::forRoute($route);
@endphp

<a href="{{ route($route) }}" title="{{ __($label) }}{{ $badge ? " ({$badge}" . ($badgeTitle ? " {$badgeTitle}" : '') . ')' : '' }}{{ $todo ? ' · ' . __(':count kaydın çevirisi eksik', ['count' => $todo]) : '' }}" @if ($active) aria-current="page" @endif
    @class([
        'relative flex items-center gap-3 rounded-xl px-2 py-1.5 font-medium transition lg:collapsed:justify-center lg:collapsed:px-0',
        'bg-white text-brand-900 shadow-sm' => $active,
        'text-white/80 hover:bg-white/10 hover:text-white' => !$active,
    ])>
    {{-- Bölüm rengindeki ikon kutusu (App\Support\SectionColor) --}}
    <span class="grid size-8 shrink-0 place-items-center rounded-lg {{ \App\Support\SectionColor::classes($color, $active ? 'solid' : 'nav') }}">
        <svg class="size-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            {{ $slot }}
        </svg>
    </span>
    <span class="flex-1 truncate lg:collapsed:hidden">{{ __($label) }}</span>
    {{-- mor: çevirisi eksik kayıt sayısı (App\Support\TranslationTodo) --}}
    @if ($todo > 0)
        <span title="{{ __(':count kaydın çevirisi eksik', ['count' => $todo]) }}" @class([
            'inline-flex items-center gap-0.5 rounded-full bg-purple-500 px-1.5 py-0.5 text-[11px] font-semibold leading-none tabular-nums text-white',
            'lg:collapsed:absolute lg:collapsed:-left-1 lg:collapsed:-top-1' => true,
        ])><svg class="size-2.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 8 6 6M4 14l6-6 2-3M2 5h12M22 22l-5-10-5 10M14 18h6" /></svg>{{ number_format($todo, 0, ',', '.') }}</span>
    @endif
    {{-- turuncu: SEO'su / içeriği eksik kayıt sayısı (App\Support\ContentTodo) --}}
    @if ($seo > 0)
        <span title="{{ __(':count kaydın SEO\'su ya da içeriği eksik', ['count' => $seo]) }}" @class([
            'rounded-full bg-amber-500 px-1.5 py-0.5 text-[11px] font-semibold leading-none tabular-nums text-white',
            'lg:collapsed:absolute lg:collapsed:-bottom-1 lg:collapsed:-right-1' => true,
        ])>{{ number_format($seo, 0, ',', '.') }}</span>
    @endif
    @if ($badge > 0)
        <span @if ($badgeTitle) title="{{ $badge }} {{ $badgeTitle }}" @endif @class([
            'rounded-full px-1.5 py-0.5 text-[11px] font-semibold leading-none tabular-nums',
            'lg:collapsed:absolute lg:collapsed:-right-1 lg:collapsed:-top-1',
            // alert: dikkat isteyen (kırmızı); count: sadece adet (sade)
            'bg-rose-500 text-white' => $badgeTone === 'alert',
            'bg-white/15 text-white ring-1 ring-white/20' => $badgeTone === 'count' && !$active,
            'bg-slate-100 text-slate-700' => $badgeTone === 'count' && $active,
        ])>{{ number_format($badge, 0, ',', '.') }}</span>
    @endif
</a>
