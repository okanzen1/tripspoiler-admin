@props(['rating'])

<span {{ $attributes->class('inline-flex items-center gap-0.5') }} aria-label="{{ $rating }} / 5" title="{{ $rating }} / 5">
    @for ($i = 1; $i <= 5; $i++)
        <svg @class(['size-4', 'fill-amber-400 text-amber-400' => $i <= $rating, 'fill-slate-200 text-slate-200' => $i > $rating]) viewBox="0 0 24 24" aria-hidden="true">
            <path d="m12 3 2.8 5.7 6.2.9-4.5 4.4 1.1 6.2L12 17.3l-5.6 2.9 1.1-6.2L3 9.6l6.2-.9Z" />
        </svg>
    @endfor
</span>
