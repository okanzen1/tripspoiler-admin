{{-- Canlı özet: şu an sitede, şu an bakılan sayfalar (10 sn'de bir yenilenir) --}}
@php
    use App\Services\Analytics;
    $countryName = fn (?string $code) => $code ? (\Locale::getDisplayRegion("-{$code}", app()->getLocale()) ?: $code) : __('Bilinmiyor');
    $deviceName = ['mobile' => __('Telefon'), 'tablet' => __('Tablet'), 'desktop' => __('Bilgisayar')];
@endphp

<div class="grid gap-5 lg:grid-cols-3">
    {{-- Şu an sitede: yeşil degrade kart --}}
    <x-admin.card class="relative isolate overflow-hidden border-0 bg-gradient-to-br from-emerald-500 via-emerald-500 to-teal-600 p-6 text-white shadow-[0_20px_40px_-24px_rgba(5,150,105,0.9)]">
        <div class="pointer-events-none absolute -right-10 -top-12 -z-10 size-44 rounded-full bg-white/15 blur-2xl" aria-hidden="true"></div>
        <p class="flex items-center gap-2 text-sm font-semibold text-white/90">
            <span class="relative flex size-2.5">
                @if ($live['active'])
                    <span class="absolute inline-flex size-full animate-ping rounded-full bg-white opacity-75"></span>
                @endif
                <span class="relative inline-flex size-2.5 rounded-full {{ $live['active'] ? 'bg-white' : 'bg-white/40' }}"></span>
            </span>
            {{ __('Şu an sitede') }}
        </p>
        <p class="mt-2 text-5xl font-extrabold tracking-tight tabular-nums">{{ $live['active'] }}</p>
        <p class="mt-1 text-xs text-white/85">{{ __('son 5 dakikada · bugün :count tekil ziyaretçi', ['count' => $live['today']]) }}</p>
        <p class="mt-3 inline-flex rounded-full bg-white/15 px-2.5 py-1 text-[11px] font-medium text-white/90">
            {{ __('Son ziyaret') }}:&nbsp;
            @if ($live['last_at'])
                <span title="{{ $live['last_at']->translatedFormat('j F Y H:i:s') }}">{{ $live['last_at']->format('H:i') }} · {{ $live['last_at']->locale(app()->getLocale())->diffForHumans($live['now']) }}</span>
            @else
                {{ __('henüz yok') }}
            @endif
        </p>
    </x-admin.card>

    {{-- Şu an açık sayfalar: mor tonlu --}}
    <x-admin.card class="border-violet-200/70 bg-gradient-to-br from-violet-50 via-white to-fuchsia-50 p-6 lg:col-span-2">
        <p class="flex items-center gap-2.5 text-sm font-semibold text-violet-900">
            <span class="grid size-8 place-items-center rounded-lg bg-violet-500 text-white shadow-sm">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z" /><circle cx="12" cy="12" r="3" /></svg>
            </span>
            {{ __('Şu an bakılan sayfalar') }}
        </p>
        @if ($live['pages']->isEmpty())
            <p class="mt-4 rounded-xl bg-white/70 px-4 py-3 text-sm text-violet-400 ring-1 ring-violet-100">{{ __('Son 5 dakikada ziyaret yok.') }}</p>
        @else
            <ul class="mt-3 space-y-2">
                @foreach ($live['pages'] as $page)
                    <li class="flex items-center justify-between gap-3 rounded-xl bg-white/80 px-3 py-2 text-sm ring-1 ring-violet-100">
                        <span class="truncate font-medium text-slate-800">{{ $page->path }}</span>
                        <span class="shrink-0 rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-semibold tabular-nums text-emerald-700">{{ trans_choice(':count kişi|:count kişi', $page->total, ['count' => $page->total]) }}</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-admin.card>
</div>
