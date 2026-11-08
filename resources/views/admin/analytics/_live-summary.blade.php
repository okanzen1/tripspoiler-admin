{{-- Canlı özet: şu an sitede, şu an bakılan sayfalar (10 sn'de bir yenilenir) --}}
@php
    use App\Services\Analytics;
    $countryName = fn (?string $code) => $code ? (\Locale::getDisplayRegion("-{$code}", app()->getLocale()) ?: $code) : __('Bilinmiyor');
    $deviceName = ['mobile' => __('Telefon'), 'tablet' => __('Tablet'), 'desktop' => __('Bilgisayar')];
@endphp

<div class="grid gap-5 lg:grid-cols-3">
    {{-- Şu an sitede --}}
    <x-admin.card class="p-6">
        <p class="flex items-center gap-2 text-sm font-medium text-slate-500">
            <span class="relative flex size-2.5">
                @if ($live['active'])
                    <span class="absolute inline-flex size-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                @endif
                <span class="relative inline-flex size-2.5 rounded-full {{ $live['active'] ? 'bg-emerald-500' : 'bg-slate-300' }}"></span>
            </span>
            {{ __('Şu an sitede') }}
        </p>
        <p class="mt-2 text-4xl font-semibold tracking-tight tabular-nums text-slate-900">{{ $live['active'] }}</p>
        <p class="mt-1 text-xs text-slate-500">{{ __('son 5 dakikada · bugün :count tekil ziyaretçi', ['count' => $live['today']]) }}</p>
        <p class="mt-2 text-[11px] text-slate-400">
            {{ __('Son ziyaret') }}:
            @if ($live['last_at'])
                <span title="{{ $live['last_at']->translatedFormat('j F Y H:i:s') }}">{{ $live['last_at']->format('H:i') }} · {{ $live['last_at']->locale(app()->getLocale())->diffForHumans($live['now']) }}</span>
            @else
                {{ __('henüz yok') }}
            @endif
        </p>
    </x-admin.card>

    {{-- Şu an açık sayfalar --}}
    <x-admin.card class="p-6 lg:col-span-2">
        <p class="text-sm font-medium text-slate-500">{{ __('Şu an bakılan sayfalar') }}</p>
        @if ($live['pages']->isEmpty())
            <p class="mt-4 text-sm text-slate-400">{{ __('Son 5 dakikada ziyaret yok.') }}</p>
        @else
            <ul class="mt-3 space-y-2">
                @foreach ($live['pages'] as $page)
                    <li class="flex items-center justify-between gap-3 text-sm">
                        <span class="truncate font-medium text-slate-800">{{ $page->path }}</span>
                        <span class="shrink-0 rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-semibold tabular-nums text-emerald-700">{{ trans_choice(':count kişi|:count kişi', $page->total, ['count' => $page->total]) }}</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-admin.card>
</div>
