@props(['title', 'items', 'unit' => 'ziyaretçi', 'empty' => 'Bu dönemde veri yok.', 'color' => 'brand', 'icon' => null])

{{-- Yatay bar listesi: payı gösteren dağılımlar için; color: kartın rengi (başlık ikonu + barlar) --}}
@php
    [$tile, $bar] = [
        'sky' => ['bg-sky-50 text-sky-600', 'bg-gradient-to-r from-sky-400 to-sky-600'],
        'violet' => ['bg-violet-50 text-violet-600', 'bg-gradient-to-r from-violet-400 to-violet-600'],
        'amber' => ['bg-amber-50 text-amber-600', 'bg-gradient-to-r from-amber-300 to-orange-500'],
        'emerald' => ['bg-emerald-50 text-emerald-600', 'bg-gradient-to-r from-emerald-400 to-emerald-600'],
        'rose' => ['bg-rose-50 text-rose-600', 'bg-gradient-to-r from-rose-400 to-rose-600'],
        'brand' => ['bg-brand-50 text-brand-900', 'bg-data'],
    ][$color] ?? ['bg-brand-50 text-brand-900', 'bg-data'];
@endphp
<x-admin.card class="p-6">
    <h2 class="flex items-center gap-2.5 text-sm font-semibold text-slate-900">
        @if ($icon)
            <span class="grid size-8 place-items-center rounded-lg {{ $tile }}">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $icon !!}</svg>
            </span>
        @endif
        {{ __($title) }}
    </h2>

    @if ($items->isEmpty())
        <p class="mt-6 text-sm text-slate-400">{{ __($empty) }}</p>
    @else
        <ul class="mt-5 space-y-4">
            @foreach ($items as $item)
                <li title="{{ __($item->label) }}: {{ number_format($item->total, 0, ',', '.') }} {{ __($unit) }} (%{{ number_format($item->percent, 1, ',', '.') }})">
                    <div class="flex items-baseline justify-between gap-3 text-sm">
                        <span class="truncate text-slate-700">{{ __($item->label) }}</span>
                        <span class="shrink-0 tabular-nums text-slate-500">
                            <span class="font-semibold text-slate-900">{{ number_format($item->total, 0, ',', '.') }}</span>
                            · %{{ number_format($item->percent, 1, ',', '.') }}
                        </span>
                    </div>
                    <div class="mt-1.5 h-2 rounded-full bg-slate-100">
                        <div class="h-2 rounded-full {{ $bar }}" style="width: {{ max($item->percent, 1) }}%"></div>
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
</x-admin.card>
