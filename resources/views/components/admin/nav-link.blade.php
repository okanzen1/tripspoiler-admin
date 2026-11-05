@props(['route', 'label', 'match' => null, 'badge' => 0])

@php
    $active = request()->routeIs(...($match ?? [$route]));
    $color = \App\Support\SectionColor::forRoute($route);
@endphp

<a href="{{ route($route) }}" title="{{ __($label) }}{{ $badge ? " ({$badge})" : '' }}" @if ($active) aria-current="page" @endif
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
    @if ($badge > 0)
        <span @class([
            'rounded-full px-1.5 py-0.5 text-[11px] font-semibold leading-none tabular-nums',
            'lg:collapsed:absolute lg:collapsed:-right-1 lg:collapsed:-top-1',
            'bg-rose-500 text-white',
        ])>{{ $badge > 99 ? '99+' : $badge }}</span>
    @endif
</a>
