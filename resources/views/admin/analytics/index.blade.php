@extends('layouts.admin')

@section('title', 'Analitik')

@php
    $fmt = fn ($n) => number_format($n, 0, ',', '.');
    // her metriğin rengi (grafik de aynı rengi kullanır: resources/js/analytics-chart.js)
    $metrics = [
        'visitors' => ['label' => 'Ziyaretçi', 'hint' => 'Tekil ziyaretçi', 'format' => $fmt,
            'tone' => 'from-sky-50 aria-selected:border-sky-500 aria-selected:ring-sky-500', 'tile' => 'bg-sky-500', 'icon' => '<circle cx="9" cy="8" r="3.5" /><path d="M2.5 20c.8-3.4 3.4-5.5 6.5-5.5s5.7 2.1 6.5 5.5M16 4.6a3.5 3.5 0 0 1 0 6.8M18 14.8c1.9.8 3.1 2.6 3.5 5.2" />'],
        'views' => ['label' => 'Sayfa görüntüleme', 'hint' => 'Toplam görüntüleme', 'format' => $fmt,
            'tone' => 'from-violet-50 aria-selected:border-violet-500 aria-selected:ring-violet-500', 'tile' => 'bg-violet-500', 'icon' => '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z" /><circle cx="12" cy="12" r="3" />'],
        'clicks' => ['label' => 'Affiliate tıklama', 'hint' => 'Partner linklerine tıklama', 'format' => $fmt,
            'tone' => 'from-amber-50 aria-selected:border-amber-500 aria-selected:ring-amber-500', 'tile' => 'bg-gradient-to-br from-amber-400 to-orange-500', 'icon' => '<path d="m9 9 5 12 1.8-5.2L21 14Z" /><path d="M7.2 2.2 8 5.1M5.1 8l-2.9-.8M14 4.1 12 6M6 12l-1.9 2" />'],
        'ctr' => ['label' => 'Tıklama oranı', 'hint' => 'Tıklayan ziyaretçi oranı', 'format' => fn ($n) => '%' . number_format($n, 1, ',', '.'),
            'tone' => 'from-emerald-50 aria-selected:border-emerald-500 aria-selected:ring-emerald-500', 'tile' => 'bg-emerald-500', 'icon' => '<path d="M19 5 5 19" /><circle cx="6.5" cy="6.5" r="2.5" /><circle cx="17.5" cy="17.5" r="2.5" />'],
    ];
    $typeNames = ['home' => 'Anasayfa', 'city' => 'Şehir', 'activity' => 'Aktivite', 'blog' => 'Blog', 'other' => 'Diğer'];
    $typeTones = ['home' => 'bg-sky-100 text-sky-700', 'city' => 'bg-cyan-100 text-cyan-700', 'activity' => 'bg-emerald-100 text-emerald-700', 'blog' => 'bg-violet-100 text-violet-700', 'other' => 'bg-slate-100 text-slate-600'];
@endphp

@section('header')
    <x-admin.page-header :title="__('Analitik')"
        description="{{ $analytics->start->locale(app()->getLocale())->translatedFormat('d F') }} – {{ $analytics->end->locale(app()->getLocale())->translatedFormat('d F Y') }} · {{ $analytics->custom ? __(':count gün · önceki :count günle karşılaştırmalı', ['count' => $analytics->days]) : __('önceki :count günle karşılaştırmalı', ['count' => $analytics->days]) }}">
        <x-slot:icon><path d="M4 20V10M10 20V4M16 20v-7M22 20H2" /></x-slot:icon>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('analytics.heatmap') }}"
                class="inline-flex h-10 items-center gap-1.5 rounded-xl bg-gradient-to-r from-orange-500 to-rose-500 px-3.5 text-xs font-semibold text-white shadow-sm transition hover:from-orange-600 hover:to-rose-600">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 2c1 3 4 5 4 9a4 4 0 0 1-8 0c0-1.5.5-2.5 1.5-3.5C9 10 10 11 11 11c0-3-1-6 1-9Z" /><path d="M6 15a6 6 0 0 0 12 0" /></svg>
                {{ __('Sıcaklık haritası') }}
            </a>
            <a href="{{ route('analytics-settings.index') }}" title="{{ __('Analitik ayarları') }}"
                class="inline-flex h-10 items-center gap-1.5 rounded-xl bg-white px-3 text-xs font-semibold text-slate-600 ring-1 ring-slate-200 transition hover:bg-slate-50 hover:text-slate-900">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 21v-7M4 10V3M12 21v-9M12 8V3M20 21v-5M20 12V3M1 14h6M9 8h6M17 16h6" /></svg>
                <span class="hidden sm:inline">{{ __('Ayarlar') }}</span>
            </a>
        </div>
    </x-admin.page-header>
@endsection

@section('content')
    {{-- TEK FİLTRE ÇUBUĞU: dönem, takvim, cihaz, ülke, arama — sayfadaki her şey (kartlar, grafik, tablolar, ziyaretçiler) buna göre --}}
    @php
        $periodLabel = $filters['custom']
            ? $analytics->start->locale(app()->getLocale())->translatedFormat('j M') . ' – ' . $analytics->end->locale(app()->getLocale())->translatedFormat('j M Y')
            : __(\App\Services\Analytics::RANGES[$filters['range']]);
        $active = $filters['q'] || $filters['device'] || $filters['country'] || $filters['custom'] || $filters['range'] !== 30;
    @endphp
    <form method="GET" action="{{ route('analytics.index') }}" data-analytics-filters data-no-ajax @if ($filters['custom']) data-custom @endif
        class="sticky top-0 z-20 -mx-1 mb-6 rounded-2xl bg-gradient-to-r from-sky-600 via-indigo-600 to-violet-600 p-3 shadow-[0_18px_40px_-22px_rgba(79,70,229,0.9)] sm:p-4">
        <div class="flex flex-wrap items-center gap-2.5">
            {{-- dönem --}}
            <div class="inline-flex h-10 items-center rounded-xl bg-white/15 p-1 ring-1 ring-white/20" role="radiogroup" aria-label="{{ __('Dönem') }}">
                @foreach (\App\Services\Analytics::RANGES as $days => $label)
                    <label class="cursor-pointer">
                        <input type="radio" name="range" value="{{ $days }}" class="peer sr-only" data-period @checked($filters['range'] === $days)>
                        <span class="block rounded-lg px-3 py-1.5 text-xs font-semibold text-white/80 transition hover:text-white peer-checked:bg-white peer-checked:text-indigo-700 peer-checked:shadow-sm">{{ __($label) }}</span>
                    </label>
                @endforeach
            </div>

            {{-- takvim: başlangıç – bitiş --}}
            <div @class(['inline-flex h-10 items-center gap-1 rounded-xl px-2 ring-1 transition', 'bg-white text-indigo-700 ring-white' => $filters['custom'], 'bg-white/15 text-white ring-white/20' => !$filters['custom']])>
                <svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2" /><path d="M3 10h18M8 3v4M16 3v4" /></svg>
                <input type="date" name="from" value="{{ $filters['from'] }}" max="{{ now()->toDateString() }}" aria-label="{{ __('Başlangıç') }}" data-date
                    class="h-8 w-[8.5rem] rounded-lg border-0 bg-transparent px-1 text-xs font-semibold text-current outline-none [color-scheme:light] focus:ring-2 focus:ring-white/60">
                <span class="opacity-50">–</span>
                <input type="date" name="to" value="{{ $filters['to'] }}" max="{{ now()->toDateString() }}" aria-label="{{ __('Bitiş') }}" data-date
                    class="h-8 w-[8.5rem] rounded-lg border-0 bg-transparent px-1 text-xs font-semibold text-current outline-none focus:ring-2 focus:ring-white/60">
            </div>

            {{-- cihaz --}}
            <div class="inline-flex h-10 items-center rounded-xl bg-white/15 p-1 ring-1 ring-white/20" role="radiogroup" aria-label="{{ __('Cihaz') }}">
                @foreach (['' => ['Hepsi', null], 'desktop' => ['Bilgisayar', '<rect x="2.5" y="4.5" width="19" height="12" rx="2" /><path d="M8 20h8M12 16.5V20" />'], 'mobile' => ['Telefon', '<rect x="7" y="2.5" width="10" height="19" rx="2" /><path d="M11 18.5h2" />'], 'tablet' => ['Tablet', '<rect x="4.5" y="2.5" width="15" height="19" rx="2" /><path d="M11 18.5h2" />']] as $value => [$label, $icon])
                    <label class="cursor-pointer" title="{{ __($label) }}">
                        <input type="radio" name="device" value="{{ $value }}" class="peer sr-only" @checked(($filters['device'] ?? '') === $value)>
                        <span class="flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-semibold text-white/80 transition hover:text-white peer-checked:bg-white peer-checked:text-violet-700 peer-checked:shadow-sm">
                            @if ($icon)
                                <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $icon !!}</svg>
                                <span class="hidden xl:inline">{{ __($label) }}</span>
                            @else
                                {{ __($label) }}
                            @endif
                        </span>
                    </label>
                @endforeach
            </div>

            {{-- ülke --}}
            <label class="relative">
                <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm leading-none">{{ $filters['country'] ? \App\Services\Analytics::flag($filters['country']) : '🌍' }}</span>
                <select name="country" @class(['h-10 appearance-none rounded-xl border-0 pl-9 pr-8 text-xs font-semibold outline-none ring-1 transition focus:ring-2', 'bg-white text-indigo-700 ring-white' => $filters['country'], 'bg-white/15 text-white ring-white/20 [&>option]:text-slate-900' => !$filters['country']])>
                    <option value="">{{ __('Tüm ülkeler') }}</option>
                    @foreach ($live['countries'] as $code)
                        <option value="{{ $code }}" @selected($filters['country'] === $code)>{{ \App\Services\Analytics::flag($code) }} {{ \Locale::getDisplayRegion("-{$code}", app()->getLocale()) ?: $code }}</option>
                    @endforeach
                </select>
                <svg class="pointer-events-none absolute right-2.5 top-1/2 size-3.5 -translate-y-1/2 opacity-70" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="m6 9 6 6 6-6" /></svg>
            </label>

            {{-- arama (Enter) --}}
            <label class="relative min-w-[12rem] flex-1">
                <svg class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-white/70" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7" /><path d="M20 20l-3.5-3.5" /></svg>
                <input type="search" name="q" value="{{ $filters['q'] }}" placeholder="{{ __('Ziyaretçide ara: IP, sayfa, şehir, kaynak… (Enter)') }}" autocomplete="off"
                    class="h-10 w-full rounded-xl border-0 bg-white/15 pl-9 pr-3 text-sm text-white outline-none ring-1 ring-white/20 transition placeholder:text-white/60 focus:bg-white focus:text-slate-900 focus:ring-2 focus:ring-white focus:placeholder:text-slate-400">
            </label>

            @if ($active)
                <a href="{{ route('analytics.index') }}" class="inline-flex h-10 items-center gap-1 rounded-xl px-3 text-xs font-semibold text-white/85 transition hover:bg-white/15 hover:text-white">
                    <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12" /></svg>
                    {{ __('Temizle') }}
                </a>
            @endif
        </div>
    </form>
    @if ($analytics->hasDemoData())
        <div class="mb-6 flex items-start gap-3 rounded-2xl bg-amber-50 px-4 py-3 text-sm text-amber-900 ring-1 ring-amber-200">
            <svg class="mt-0.5 size-5 shrink-0 text-amber-600" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M8.5 2.6a1.7 1.7 0 0 1 3 0l6.3 11.5a1.7 1.7 0 0 1-1.5 2.5H3.7a1.7 1.7 0 0 1-1.5-2.5L8.5 2.6ZM10 7a.75.75 0 0 1 .75.75v3.5a.75.75 0 0 1-1.5 0v-3.5A.75.75 0 0 1 10 7Zm0 8a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd" /></svg>
            <p><strong class="font-semibold">{{ __('Demo veri gösteriliyor.') }}</strong> {{ __('Bu sayılar gerçek değil, ekranı denemek için üretildi. Silmek için:') }} <code class="rounded bg-amber-100 px-1.5 py-0.5 font-mono text-xs">php artisan analytics:demo --clear</code></p>
        </div>
    @endif

    {{-- CANLI: özet + ziyaretçiler (sekme açıkken 10 sn'de bir, filtre değişince hemen yenilenir) --}}
    <div data-live-analytics data-url="{{ route('analytics.live') }}" class="mb-6">
        <div data-live-part="summary">@include('admin.analytics._live-summary')</div>

        <x-admin.card class="mt-5 overflow-hidden">
            {{-- başlık + filtreler (yenilenen tablonun dışında: seçimler kaybolmasın) --}}
            <div class="border-b border-sky-100 bg-gradient-to-r from-sky-50/80 via-white to-indigo-50/60 px-6 pb-4 pt-6">
                <div class="flex flex-wrap items-end justify-between gap-2">
                    <div class="flex items-center gap-3">
                        <span class="grid size-10 place-items-center rounded-xl bg-gradient-to-br from-sky-500 to-indigo-500 text-white shadow-sm">
                            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="8" r="3.5" /><path d="M2.5 20c.8-3.4 3.4-5.5 6.5-5.5s5.7 2.1 6.5 5.5M16 4.6a3.5 3.5 0 0 1 0 6.8M18 14.8c1.9.8 3.1 2.6 3.5 5.2" /></svg>
                        </span>
                        <div>
                        <h2 class="text-sm font-semibold text-slate-900">{{ __('Ziyaretçiler') }}</h2>
                        <p class="text-xs text-slate-500"><strong class="font-semibold text-indigo-700">{{ $periodLabel }}</strong> · {{ __('Aynı IP tek satırda · satıra tıkla, o ziyaretçinin gezdiği her şeyi gör · botlar sayılmaz') }}</p>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        {{-- listedeki (filtrelenen dönemdeki) ziyaretçi sayısı; liste en fazla 50 kişi gösterir --}}
                        <p class="rounded-full bg-indigo-50 px-2.5 py-1 text-[11px] font-semibold text-indigo-700 ring-1 ring-indigo-100" title="{{ __('Seçili dönemde listelenen ziyaretçi') }}">
                            <span data-live-shown>{{ $live['visitors']->count() >= 50 ? __('son 50') : $live['visitors']->count() }}</span> {{ __('ziyaretçi listede') }}
                        </p>
                        {{-- gerçekten canlı: son 5 dakikada sitede olanlar (filtreden bağımsız) --}}
                        <p class="flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-semibold text-emerald-700 ring-1 ring-emerald-100" title="{{ __('Son 5 dakikada sitede olanlar') }}">
                            <span class="size-1.5 animate-pulse rounded-full bg-emerald-500"></span>
                            {{ __('Şu an sitede') }}: <span data-live-active>{{ $live['active'] }}</span>
                        </p>
                    </div>
                </div>

            </div>

            <div data-live-part="visitors" class="transition-opacity duration-150">@include('admin.analytics._live-visitors')</div>
        </x-admin.card>
    </div>

    @if (!$analytics->hasAnyData())
        <x-admin.card class="px-6 py-20 text-center">
            <div class="mx-auto grid size-14 place-items-center rounded-2xl bg-brand-50 text-brand-900">
                <svg class="size-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M4 20V10M10 20V4M16 20v-7M22 20H2" /></svg>
            </div>
            <h2 class="mt-5 text-lg font-semibold text-slate-900">{{ __('Henüz veri yok') }}</h2>
            <p class="mx-auto mt-2 max-w-md text-sm text-slate-500">{{ __('Site yayına girip ziyaret ve affiliate tıklamaları gelmeye başlayınca burada görünecek.') }}</p>
        </x-admin.card>
    @else
        {{-- KPI'lar: tıklanınca grafik o metriğe geçer --}}
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" role="tablist" aria-label="{{ __('Metrik') }}">
            @foreach ($metrics as $key => $metric)
                <button type="button" role="tab" data-metric="{{ $key }}" aria-selected="{{ $key === 'clicks' ? 'true' : 'false' }}"
                    class="group rounded-2xl border border-slate-300/60 bg-gradient-to-br to-white p-5 text-left shadow-[0_1px_3px_rgba(15,23,42,0.06)] transition hover:-translate-y-0.5 hover:border-slate-300 hover:shadow-md aria-selected:ring-1 {{ $metric['tone'] }}">
                    <div class="flex items-center justify-between gap-2">
                        <p class="text-sm font-medium text-slate-600">{{ __($metric['label']) }}</p>
                        <span class="grid size-9 place-items-center rounded-xl text-white shadow-sm {{ $metric['tile'] }}">
                            <svg class="size-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $metric['icon'] !!}</svg>
                        </span>
                    </div>
                    <p class="mt-2 text-3xl font-semibold tracking-tight tabular-nums text-slate-900">{{ $metric['format']($kpis[$key]['value']) }}</p>
                    <div class="mt-3 flex items-center gap-2">
                        <x-admin.delta :change="$kpis[$key]['change']" />
                        @if ($kpis[$key]['change'] !== null)
                            <span class="text-xs text-slate-400">{{ __('önceki') }}: {{ $metric['format']($kpis[$key]['previous']) }}</span>
                        @endif
                    </div>
                </button>
            @endforeach
        </div>

        {{-- Günlük grafik --}}
        <x-admin.card class="mt-5 bg-gradient-to-br from-white via-white to-amber-50/60 p-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <span class="grid size-10 place-items-center rounded-xl bg-gradient-to-br from-amber-400 to-orange-500 text-white shadow-sm">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 17l6-6 4 4 8-8" /><path d="M15 7h6v6" /></svg>
                    </span>
                    <div>
                        <h2 class="text-sm font-semibold text-slate-900" data-chart-title>{{ __('Affiliate tıklama') }}</h2>
                        <p class="text-xs text-slate-500">{{ __('Günlük') }} · {{ __('Üstteki kartlara tıklayarak değiştir') }}</p>
                    </div>
                </div>
            </div>
            <div class="relative mt-4 h-72">
                <canvas id="analytics-chart" role="img" aria-label="{{ __('Günlük metrik grafiği') }}"></canvas>
            </div>
        </x-admin.card>

        <script type="application/json" id="analytics-data">@json(['daily' => $daily, 'labels' => collect($metrics)->map(fn ($m) => $m['label'])])</script>

        {{-- En çok tıklama getiren sayfalar --}}
        <x-admin.card class="mt-5 overflow-hidden">
            <div class="flex items-center justify-between bg-gradient-to-r from-rose-50/80 via-white to-amber-50/60 px-6 pb-4 pt-6">
                <div class="flex items-center gap-3">
                    <span class="grid size-10 place-items-center rounded-xl bg-gradient-to-br from-rose-500 to-orange-500 text-white shadow-sm">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8 21h8M12 17v4M7 4h10v4a5 5 0 0 1-10 0V4ZM17 6h2a2 2 0 0 1 0 4h-2M7 6H5a2 2 0 0 0 0 4h2" /></svg>
                    </span>
                    <div>
                        <h2 class="text-sm font-semibold text-slate-900">{{ __('En çok tıklama getiren sayfalar') }}</h2>
                        <p class="text-xs text-slate-500">{{ __('Hangi sayfa ziyaretçiyi partnere gönderiyor') }}</p>
                    </div>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-y border-rose-100 bg-rose-50/40 text-xs font-semibold text-rose-900/60">
                        <tr>
                            <th class="px-6 py-3 font-medium">{{ __('Sayfa') }}</th>
                            <th class="px-6 py-3 text-right font-medium">{{ __('Görüntüleme') }}</th>
                            <th class="px-6 py-3 text-right font-medium">{{ __('Tıklama') }}</th>
                            <th class="px-6 py-3 text-right font-medium">{{ __('Tıklama oranı') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($topPages as $page)
                            <tr class="transition hover:bg-amber-50/50">
                                <td class="max-w-md px-6 py-3.5">
                                    <div class="flex items-center gap-2.5">
                                        @if ($page->type)
                                            <span class="shrink-0 rounded-md px-1.5 py-0.5 text-[11px] font-semibold {{ $typeTones[$page->type] ?? 'bg-slate-100 text-slate-600' }}">{{ __($typeNames[$page->type] ?? $page->type) }}</span>
                                        @endif
                                        <a href="{{ route('analytics.heatmap', ['page' => $page->path]) }}" class="truncate font-medium text-slate-800 hover:text-rose-700 hover:underline" title="{{ __('Sıcaklık haritasını aç') }}: {{ $page->path }}">{{ $page->path }}</a>
                                    </div>
                                </td>
                                <td class="px-6 py-3.5 text-right tabular-nums text-slate-600">{{ $fmt($page->views) }}</td>
                                <td class="px-6 py-3.5 text-right"><span class="rounded-full bg-amber-100 px-2.5 py-0.5 font-bold tabular-nums text-amber-800">{{ $fmt($page->clicks) }}</span></td>
                                <td class="px-6 py-3.5 text-right tabular-nums text-slate-600">{{ $page->ctr === null ? '—' : '%' . number_format($page->ctr, 1, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-6 py-10 text-center text-slate-400">{{ __('Bu dönemde veri yok.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-admin.card>

        {{-- Dağılımlar --}}
        <div class="mt-5 grid gap-5 md:grid-cols-2">
            <x-admin.share-bars :title="__('Partnerler')" :items="$partners" unit="tıklama" color="amber" icon='<path d="M10 13a5 5 0 0 0 7.5.5l3-3a5 5 0 0 0-7-7l-1.7 1.7" /><path d="M14 11a5 5 0 0 0-7.5-.5l-3 3a5 5 0 0 0 7 7l1.7-1.7" />' />
            <x-admin.share-bars :title="__('Trafik kaynakları')" :items="$sources" unit="oturum" color="violet" icon='<path d="M4 12h10M10 6l6 6-6 6M20 4v16" />' />
            <x-admin.share-bars :title="__('Cihazlar')" :items="$devices" color="emerald" icon='<rect x="2.5" y="4.5" width="19" height="12" rx="2" /><path d="M8 20h8M12 16.5V20" />' />
            <x-admin.share-bars :title="__('Ülkeler')" :items="$countries" color="sky" icon='<circle cx="12" cy="12" r="9" /><path d="M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18" />' />
        </div>
    @endif
@endsection

@push('scripts')
    <script>
        // Filtre çubuğu: seçim değişince sayfa o filtreyle yenilenir (arama Enter ile).
        // Dönem düğmesine basınca takvim temizlenir; takvimden tarih seçilince dönem düğmesi kalkar.
        (() => {
            const form = document.querySelector('[data-analytics-filters]');
            if (!form) return;
            // takvim yalnızca seçildiyse (ya da zaten takvim aralığı açıksa) gönderilir; yoksa dönem düğmesi geçerli
            let useDates = form.hasAttribute('data-custom');
            const send = () => {
                form.querySelectorAll('[data-date]').forEach((d) => d.disabled = !useDates);
                form.querySelectorAll('[data-period]').forEach((r) => r.disabled = useDates);
                form.querySelectorAll('input, select').forEach((el) => { if (el.name && el.value === '') el.disabled = true; });
            };
            form.addEventListener('change', (e) => {
                if (e.target.name === 'q') return;
                if (e.target.matches('[data-period]')) useDates = false;
                if (e.target.matches('[data-date]')) useDates = true;
                send();
                form.submit();
            });
            form.addEventListener('submit', send);
        })();

        // Canlı bölüm: sekme görünürken 10 sn'de bir, sayfanın filtreleriyle yenilenir (sayfa yenilenmez).
        (() => {
            const root = document.querySelector('[data-live-analytics]');
            if (!root) return;
            const table = root.querySelector('[data-live-part="visitors"]');
            const refresh = async () => {
                if (document.hidden) return;
                try {
                    const res = await fetch(root.dataset.url + location.search, { headers: { Accept: 'text/html' }, credentials: 'same-origin' });
                    if (!res.ok || res.redirected) return;
                    const doc = new DOMParser().parseFromString(await res.text(), 'text/html');
                    doc.querySelectorAll('[data-live-part]').forEach((part) => {
                        const target = root.querySelector(`[data-live-part="${part.dataset.livePart}"]`);
                        if (target) target.innerHTML = part.innerHTML;
                    });
                    root.querySelector('[data-live-shown]').textContent = table.querySelector('[data-live-count]')?.textContent ?? '0';
                    root.querySelector('[data-live-active]').textContent = table.querySelector('[data-live-active-count]')?.textContent ?? '0';
                } catch { /* bir sonraki turda tekrar */ }
            };
            setInterval(refresh, 10000);
            document.addEventListener('visibilitychange', () => !document.hidden && refresh());
        })();
    </script>
@endpush
