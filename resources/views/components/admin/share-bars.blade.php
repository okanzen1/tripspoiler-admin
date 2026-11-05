@props(['title', 'items', 'unit' => 'ziyaretçi', 'empty' => 'Bu dönemde veri yok.'])

{{-- Tek renkli yatay bar listesi: payı gösteren dağılımlar için --}}
<x-admin.card class="p-6">
    <h2 class="text-sm font-semibold text-slate-900">{{ __($title) }}</h2>

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
                        <div class="h-2 rounded-full bg-data" style="width: {{ max($item->percent, 1) }}%"></div>
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
</x-admin.card>
