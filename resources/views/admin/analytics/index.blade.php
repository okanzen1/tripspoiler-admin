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
            <nav class="flex rounded-xl bg-slate-100 p-1 text-sm font-medium" aria-label="{{ __('Dönem') }}">
                @foreach (\App\Services\Analytics::RANGES as $days => $label)
                    @php $on = !$analytics->custom && $analytics->days === $days; @endphp
                    <a href="{{ route('analytics.index', ['range' => $days]) }}" @if ($on) aria-current="page" @endif
                        @class([
                            'rounded-lg px-3.5 py-1.5 transition',
                            'bg-white text-sky-700 shadow-sm' => $on,
                            'text-slate-500 hover:text-slate-800' => !$on,
                        ])>{{ __($label) }}</a>
                @endforeach
            </nav>
            {{-- takvim: başlangıç – bitiş --}}
            <form method="GET" action="{{ route('analytics.index') }}" data-no-ajax
                @class(['flex items-center gap-1.5 rounded-xl p-1 ring-1 transition', 'bg-sky-50 ring-sky-300' => $analytics->custom, 'bg-white ring-slate-200' => !$analytics->custom])>
                <svg class="ml-2 size-4 shrink-0 text-sky-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2" /><path d="M3 10h18M8 3v4M16 3v4" /></svg>
                <input type="date" name="from" value="{{ $analytics->start->toDateString() }}" max="{{ now()->toDateString() }}" aria-label="{{ __('Başlangıç') }}"
                    class="h-8 rounded-lg border-0 bg-transparent px-1.5 text-xs font-semibold text-slate-700 outline-none focus:ring-2 focus:ring-sky-300">
                <span class="text-slate-300">–</span>
                <input type="date" name="to" value="{{ $analytics->end->toDateString() }}" max="{{ now()->toDateString() }}" aria-label="{{ __('Bitiş') }}"
                    class="h-8 rounded-lg border-0 bg-transparent px-1.5 text-xs font-semibold text-slate-700 outline-none focus:ring-2 focus:ring-sky-300">
                <button type="submit" class="h-8 rounded-lg bg-sky-600 px-3 text-xs font-semibold text-white transition hover:bg-sky-700">{{ __('Göster') }}</button>
            </form>
            <a href="{{ route('analytics-settings.index') }}" title="{{ __('Analitik ayarları') }}"
                class="inline-flex h-10 items-center gap-1.5 rounded-xl bg-white px-3 text-xs font-semibold text-slate-600 ring-1 ring-slate-200 transition hover:bg-slate-50 hover:text-slate-900">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 21v-7M4 10V3M12 21v-9M12 8V3M20 21v-5M20 12V3M1 14h6M9 8h6M17 16h6" /></svg>
                <span class="hidden sm:inline">{{ __('Ayarlar') }}</span>
            </a>
        </div>
    </x-admin.page-header>
@endsection

@section('content')
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
                        <p class="text-xs text-slate-500">{{ __('Aynı IP tek satırda · satıra tıkla, o ziyaretçinin gezdiği her şeyi gör · botlar sayılmaz') }}</p>
                        </div>
                    </div>
                    <p class="flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-semibold text-emerald-700 ring-1 ring-emerald-100">
                        <span class="size-1.5 animate-pulse rounded-full bg-emerald-500"></span>
                        {{ __('Canlı') }} · <span data-live-shown>{{ $live['visitors']->count() }}</span> {{ __('ziyaretçi') }}
                    </p>
                </div>

                <form class="mt-4 flex flex-wrap items-center gap-2.5" data-live-filters onsubmit="return false">
                    {{-- ara --}}
                    <label class="relative min-w-[13rem] flex-1 lg:max-w-xs">
                        <svg class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7" /><path d="M20 20l-3.5-3.5" /></svg>
                        <input type="search" name="q" value="{{ $liveFilters['q'] }}" placeholder="{{ __('IP, sayfa, şehir, kaynak…') }}" autocomplete="off"
                            class="h-9 w-full rounded-xl border border-slate-200 bg-white pl-9 pr-3 text-sm outline-none transition placeholder:text-slate-400 focus:border-brand-900 focus:ring-4 focus:ring-brand-900/10">
                    </label>

                    {{-- zaman: tek tıkla --}}
                    <div class="inline-flex h-9 items-center rounded-xl bg-slate-100 p-1" role="radiogroup" aria-label="{{ __('Zaman') }}">
                        @foreach (\App\Services\Analytics::LIVE_RANGES as $value => $label)
                            <label class="cursor-pointer">
                                <input type="radio" name="when" value="{{ $value }}" class="peer sr-only" @checked($liveFilters['range'] === $value)>
                                <span class="block rounded-lg px-3 py-1 text-xs font-semibold text-slate-500 transition hover:text-slate-800 peer-checked:bg-sky-500 peer-checked:text-white peer-checked:shadow-sm peer-focus-visible:ring-2 peer-focus-visible:ring-brand-500">{{ __(str_replace('Son ', '', $label)) }}</span>
                            </label>
                        @endforeach
                    </div>

                    {{-- takvim: belirli bir gün (seçilince yukarıdaki zaman düğmeleri yerine o gün) --}}
                    <label @class(['inline-flex h-9 items-center gap-1.5 rounded-xl border bg-white pl-2.5 pr-1 transition', 'border-sky-400 bg-sky-50' => $liveFilters['date'], 'border-slate-200' => !$liveFilters['date']]) data-date-wrap title="{{ __('Gün seç') }}">
                        <svg class="size-3.5 text-sky-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2" /><path d="M3 10h18M8 3v4M16 3v4" /></svg>
                        <input type="date" name="date" value="{{ $liveFilters['date'] }}" max="{{ now()->toDateString() }}" aria-label="{{ __('Gün seç') }}"
                            class="h-7 border-0 bg-transparent p-0 text-xs font-semibold text-slate-600 outline-none focus:ring-0">
                    </label>

                    {{-- cihaz: tek tıkla, ikonlu --}}
                    <div class="inline-flex h-9 items-center rounded-xl bg-slate-100 p-1" role="radiogroup" aria-label="{{ __('Cihaz') }}">
                        @foreach (['' => ['Hepsi', null], 'desktop' => ['Bilgisayar', '<rect x="2.5" y="4.5" width="19" height="12" rx="2" /><path d="M8 20h8M12 16.5V20" />'], 'mobile' => ['Telefon', '<rect x="7" y="2.5" width="10" height="19" rx="2" /><path d="M11 18.5h2" />'], 'tablet' => ['Tablet', '<rect x="4.5" y="2.5" width="15" height="19" rx="2" /><path d="M11 18.5h2" />']] as $value => [$label, $icon])
                            <label class="cursor-pointer" title="{{ __($label) }}">
                                <input type="radio" name="device" value="{{ $value }}" class="peer sr-only" @checked(($liveFilters['device'] ?? '') === $value)>
                                <span class="flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-xs font-semibold text-slate-500 transition hover:text-slate-800 peer-checked:bg-violet-500 peer-checked:text-white peer-checked:shadow-sm peer-focus-visible:ring-2 peer-focus-visible:ring-brand-500">
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
                        <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm leading-none" data-country-flag>{{ $liveFilters['country'] ? \App\Services\Analytics::flag($liveFilters['country']) : '🌍' }}</span>
                        <select name="country" class="h-9 appearance-none rounded-xl border bg-white bg-[url('data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 24 24%22 fill=%22none%22 stroke=%22%2394a3b8%22 stroke-width=%222.5%22><path d=%22m6 9 6 6 6-6%22/></svg>')] bg-[length:14px] bg-[right_10px_center] bg-no-repeat pl-9 pr-8 text-xs font-semibold outline-none transition focus:ring-4 focus:ring-brand-900/10 {{ $liveFilters['country'] ? 'border-brand-900 text-brand-900' : 'border-slate-200 text-slate-600' }}" data-country-select>
                            <option value="">{{ __('Tüm ülkeler') }}</option>
                            @foreach ($live['countries'] as $code)
                                <option value="{{ $code }}" data-flag="{{ \App\Services\Analytics::flag($code) }}" @selected($liveFilters['country'] === $code)>{{ \Locale::getDisplayRegion("-{$code}", app()->getLocale()) ?: $code }}</option>
                            @endforeach
                        </select>
                    </label>

                    <button type="button" data-live-reset @class(['inline-flex h-9 items-center gap-1 rounded-xl px-3 text-xs font-semibold text-slate-500 transition hover:bg-slate-100 hover:text-slate-800', 'hidden' => !array_filter($liveFilters)])>
                        <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12" /></svg>
                        {{ __('Filtreleri temizle') }}
                    </button>
                </form>
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
                                        <span class="truncate font-medium text-slate-800" title="{{ $page->path }}">{{ $page->path }}</span>
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
        // Canlı bölüm: sekme görünürken 10 sn'de bir, filtre değişince hemen yenile (sayfa yenilenmez).
        // Sunucu iki parçayı birlikte döner (özet + ziyaretçi tablosu); filtreler yenilenen alanın dışında kalır.
        // Filtreler adrese yazılır: sayfa yenilense ya da link paylaşılsa da kalır.
        (() => {
            const root = document.querySelector('[data-live-analytics]');
            const form = root?.querySelector('[data-live-filters]');
            if (!root || !form) return;
            const reset = form.querySelector('[data-live-reset]');
            const table = root.querySelector('[data-live-part="visitors"]');
            const country = form.querySelector('[data-country-select]');
            const params = () => {
                const p = new URLSearchParams();
                new FormData(form).forEach((value, key) => { if (String(value).trim() !== '' && !(key === 'when' && value === '0')) p.set(key, value); });
                return p;
            };
            let ticket = 0;
            const refresh = async ({ dim = false } = {}) => {
                if (document.hidden) return;
                const mine = ++ticket;
                if (dim) table.classList.add('opacity-50');
                try {
                    const res = await fetch(root.dataset.url + '?' + params(), { headers: { Accept: 'text/html' }, credentials: 'same-origin' });
                    if (!res.ok || res.redirected || mine !== ticket) return;
                    const doc = new DOMParser().parseFromString(await res.text(), 'text/html');
                    doc.querySelectorAll('[data-live-part]').forEach((part) => {
                        const target = root.querySelector(`[data-live-part="${part.dataset.livePart}"]`);
                        if (target) target.innerHTML = part.innerHTML;
                    });
                    root.querySelector('[data-live-shown]').textContent = table.querySelector('[data-live-count]')?.textContent ?? '0';
                } catch { /* bir sonraki turda tekrar */ }
                finally { if (mine === ticket) table.classList.remove('opacity-50'); }
            };
            const apply = () => {
                const p = params();
                const url = new URL(location.href);
                ['q', 'when', 'country', 'device', 'date'].forEach((key) => p.has(key) ? url.searchParams.set(key, p.get(key)) : url.searchParams.delete(key));
                history.replaceState(null, '', url);
                reset.classList.toggle('hidden', ![...p.keys()].length);
                // seçili ülke: bayrak + vurgulu çerçeve
                const option = country.selectedOptions[0];
                form.querySelector('[data-country-flag]').textContent = option?.dataset.flag || '🌍';
                country.classList.toggle('border-brand-900', !!country.value);
                country.classList.toggle('text-brand-900', !!country.value);
                country.classList.toggle('border-slate-200', !country.value);
                country.classList.toggle('text-slate-600', !country.value);
                // gün seçildiyse takvim vurgulu, zaman düğmeleri soluk (o gün geçerli)
                const day = form.querySelector('[name="date"]').value;
                const wrap = form.querySelector('[data-date-wrap]');
                wrap.classList.toggle('border-sky-400', !!day); wrap.classList.toggle('bg-sky-50', !!day); wrap.classList.toggle('border-slate-200', !day);
                form.querySelector('[role="radiogroup"]').classList.toggle('opacity-40', !!day);
                refresh({ dim: true });
            };
            let typing;
            form.addEventListener('input', (e) => { if (e.target.name === 'when') form.querySelector('[name="date"]').value = ''; clearTimeout(typing); typing = setTimeout(apply, e.target.type === 'search' ? 300 : 0); });
            reset.addEventListener('click', () => {
                form.querySelector('[name="q"]').value = '';
                form.querySelector('[name="when"][value="0"]').checked = true;
                form.querySelector('[name="device"][value=""]').checked = true;
                country.value = '';
                form.querySelector('[name="date"]').value = '';
                apply();
            });
            setInterval(refresh, 10000);
            document.addEventListener('visibilitychange', () => !document.hidden && refresh());
        })();
    </script>
@endpush
