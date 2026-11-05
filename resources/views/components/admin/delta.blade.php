@props(['change'])

{{-- Önceki döneme göre değişim; renk tek başına anlam taşımasın diye ok + işaretli sayı --}}
@if ($change === null)
    <span class="text-xs font-medium text-slate-400">{{ __('Önceki dönem verisi yok') }}</span>
@else
    <span @class([
        'inline-flex items-center gap-1 rounded-md px-1.5 py-0.5 text-xs font-semibold tabular-nums',
        'bg-emerald-50 text-emerald-700' => $change > 0,
        'bg-red-50 text-red-700' => $change < 0,
        'bg-slate-100 text-slate-600' => $change == 0,
    ])>
        @if ($change > 0)
            <svg class="size-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 19V5M5 12l7-7 7 7" /></svg>
        @elseif ($change < 0)
            <svg class="size-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14M19 12l-7 7-7-7" /></svg>
        @endif
        {{ $change > 0 ? '+' : '' }}{{ number_format($change, 1, ',', '.') }}%
    </span>
@endif
