@extends('layouts.admin')

@section('title', 'Analitik')

@php
    $fmt = fn ($n) => number_format($n, 0, ',', '.');
    $metrics = [
        'visitors' => ['label' => 'Ziyaretçi', 'hint' => 'Tekil ziyaretçi', 'format' => $fmt],
        'views' => ['label' => 'Sayfa görüntüleme', 'hint' => 'Toplam görüntüleme', 'format' => $fmt],
        'clicks' => ['label' => 'Affiliate tıklama', 'hint' => 'Partner linklerine tıklama', 'format' => $fmt],
        'ctr' => ['label' => 'Tıklama oranı', 'hint' => 'Tıklayan ziyaretçi oranı', 'format' => fn ($n) => '%' . number_format($n, 1, ',', '.')],
    ];
    $typeNames = ['home' => 'Anasayfa', 'city' => 'Şehir', 'activity' => 'Aktivite', 'blog' => 'Blog', 'other' => 'Diğer'];
@endphp

@section('header')
    <x-admin.page-header :title="__('Analitik')"
        description="{{ $analytics->start->locale(app()->getLocale())->translatedFormat('d F') }} – {{ $analytics->end->locale(app()->getLocale())->translatedFormat('d F Y') }} · {{ __('önceki :count günle karşılaştırmalı', ['count' => $analytics->days]) }}">
        <x-slot:icon><path d="M4 20V10M10 20V4M16 20v-7M22 20H2" /></x-slot:icon>

        <nav class="flex rounded-xl bg-slate-100 p-1 text-sm font-medium" aria-label="{{ __('Dönem') }}">
            @foreach (\App\Services\Analytics::RANGES as $days => $label)
                <a href="{{ route('analytics.index', ['range' => $days]) }}" @if ($analytics->days === $days) aria-current="page" @endif
                    @class([
                        'rounded-lg px-3.5 py-1.5 transition',
                        'bg-white text-brand-900 shadow-sm' => $analytics->days === $days,
                        'text-slate-500 hover:text-slate-800' => $analytics->days !== $days,
                    ])>{{ __($label) }}</a>
            @endforeach
        </nav>
    </x-admin.page-header>
@endsection

@section('content')
    @if ($analytics->hasDemoData())
        <div class="mb-6 flex items-start gap-3 rounded-2xl bg-amber-50 px-4 py-3 text-sm text-amber-900 ring-1 ring-amber-200">
            <svg class="mt-0.5 size-5 shrink-0 text-amber-600" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M8.5 2.6a1.7 1.7 0 0 1 3 0l6.3 11.5a1.7 1.7 0 0 1-1.5 2.5H3.7a1.7 1.7 0 0 1-1.5-2.5L8.5 2.6ZM10 7a.75.75 0 0 1 .75.75v3.5a.75.75 0 0 1-1.5 0v-3.5A.75.75 0 0 1 10 7Zm0 8a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd" /></svg>
            <p><strong class="font-semibold">{{ __('Demo veri gösteriliyor.') }}</strong> {{ __('Bu sayılar gerçek değil, ekranı denemek için üretildi. Silmek için:') }} <code class="rounded bg-amber-100 px-1.5 py-0.5 font-mono text-xs">php artisan analytics:demo --clear</code></p>
        </div>
    @endif

    {{-- CANLI filtreleri (yenilenen alanın dışında: yazılanlar kaybolmasın) --}}
    @php $liveField = 'rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none transition focus:border-brand-900 focus:ring-4 focus:ring-brand-900/10'; @endphp
    <form class="mb-4 flex flex-wrap items-center gap-2" data-live-filters onsubmit="return false">
        <div class="relative min-w-[14rem] flex-1 sm:max-w-sm">
            <svg class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7" /><path d="M20 20l-3.5-3.5" /></svg>
            <input type="search" name="q" value="{{ $liveFilters['q'] }}" placeholder="{{ __('IP, sayfa, şehir ya da kaynak ara…') }}" autocomplete="off" class="{{ $liveField }} w-full pl-9">
        </div>
        <select name="when" class="{{ $liveField }}">
            @foreach (\App\Services\Analytics::LIVE_RANGES as $value => $label)
                <option value="{{ $value }}" @selected($liveFilters['range'] === $value)>{{ __($label) }}</option>
            @endforeach
        </select>
        <select name="country" class="{{ $liveField }}">
            <option value="">{{ __('Tüm ülkeler') }}</option>
            @foreach ($live['countries'] as $code)
                <option value="{{ $code }}" @selected($liveFilters['country'] === $code)>{{ \App\Services\Analytics::flag($code) }} {{ \Locale::getDisplayRegion("-{$code}", app()->getLocale()) ?: $code }}</option>
            @endforeach
        </select>
        <select name="device" class="{{ $liveField }}">
            <option value="">{{ __('Tüm cihazlar') }}</option>
            @foreach (['desktop' => 'Bilgisayar', 'mobile' => 'Telefon', 'tablet' => 'Tablet'] as $value => $label)
                <option value="{{ $value }}" @selected($liveFilters['device'] === $value)>{{ __($label) }}</option>
            @endforeach
        </select>
        <button type="button" data-live-reset @class(['rounded-xl px-3 py-2 text-sm font-medium text-slate-500 hover:bg-slate-100', 'hidden' => !array_filter($liveFilters)])>{{ __('Temizle') }}</button>
    </form>

    {{-- CANLI: 10 saniyede bir yenilenir (sekme açıkken) --}}
    <div class="mb-6" data-live-analytics data-url="{{ route('analytics.live') }}">
        @include('admin.analytics._live', ['live' => $live])
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
                    class="group rounded-2xl border border-slate-300/60 bg-surface p-5 text-left shadow-[0_1px_3px_rgba(15,23,42,0.06)] transition hover:border-slate-300 aria-selected:border-brand-900 aria-selected:ring-1 aria-selected:ring-brand-900">
                    <div class="flex items-center justify-between gap-2">
                        <p class="text-sm font-medium text-slate-500">{{ __($metric['label']) }}</p>
                        <span class="size-2 rounded-full bg-transparent group-aria-selected:bg-data" aria-hidden="true"></span>
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
        <x-admin.card class="mt-5 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-semibold text-slate-900" data-chart-title>{{ __('Affiliate tıklama') }}</h2>
                    <p class="text-xs text-slate-500">{{ __('Günlük') }}</p>
                </div>
            </div>
            <div class="relative mt-4 h-72">
                <canvas id="analytics-chart" role="img" aria-label="{{ __('Günlük metrik grafiği') }}"></canvas>
            </div>
        </x-admin.card>

        <script type="application/json" id="analytics-data">@json(['daily' => $daily, 'labels' => collect($metrics)->map(fn ($m) => $m['label'])])</script>

        {{-- En çok tıklama getiren sayfalar --}}
        <x-admin.card class="mt-5 overflow-hidden">
            <div class="flex items-center justify-between px-6 pt-6">
                <div>
                    <h2 class="text-sm font-semibold text-slate-900">{{ __('En çok tıklama getiren sayfalar') }}</h2>
                    <p class="text-xs text-slate-500">{{ __('Hangi sayfa ziyaretçiyi partnere gönderiyor') }}</p>
                </div>
            </div>
            <div class="mt-4 overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-y border-slate-100 bg-slate-50/60 text-xs text-slate-500">
                        <tr>
                            <th class="px-6 py-3 font-medium">{{ __('Sayfa') }}</th>
                            <th class="px-6 py-3 text-right font-medium">{{ __('Görüntüleme') }}</th>
                            <th class="px-6 py-3 text-right font-medium">{{ __('Tıklama') }}</th>
                            <th class="px-6 py-3 text-right font-medium">{{ __('Tıklama oranı') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($topPages as $page)
                            <tr class="hover:bg-slate-50/60">
                                <td class="max-w-md px-6 py-3.5">
                                    <div class="flex items-center gap-2.5">
                                        @if ($page->type)
                                            <span class="shrink-0 rounded-md bg-slate-100 px-1.5 py-0.5 text-[11px] font-medium text-slate-600">{{ __($typeNames[$page->type] ?? $page->type) }}</span>
                                        @endif
                                        <span class="truncate font-medium text-slate-800" title="{{ $page->path }}">{{ $page->path }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-3.5 text-right tabular-nums text-slate-600">{{ $fmt($page->views) }}</td>
                                <td class="px-6 py-3.5 text-right font-semibold tabular-nums text-slate-900">{{ $fmt($page->clicks) }}</td>
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
            <x-admin.share-bars :title="__('Partnerler')" :items="$partners" unit="tıklama" />
            <x-admin.share-bars :title="__('Trafik kaynakları')" :items="$sources" unit="oturum" />
            <x-admin.share-bars :title="__('Cihazlar')" :items="$devices" />
            <x-admin.share-bars :title="__('Ülkeler')" :items="$countries" />
        </div>
    @endif
@endsection

@push('scripts')
    <script>
        // Canlı bölüm: sekme görünürken 10 sn'de bir, filtre değişince hemen yenile (sayfa yenilenmez).
        // Filtreler adrese yazılır: sayfa yenilense ya da link paylaşılsa da kalır.
        (() => {
            const box = document.querySelector('[data-live-analytics]');
            const form = document.querySelector('[data-live-filters]');
            if (!box || !form) return;
            const reset = form.querySelector('[data-live-reset]');
            const params = () => {
                const p = new URLSearchParams();
                new FormData(form).forEach((value, key) => { if (String(value).trim() !== '' && !(key === 'when' && value === '0')) p.set(key, value); });
                return p;
            };
            let ticket = 0;
            const refresh = async () => {
                if (document.hidden) return;
                const mine = ++ticket;
                try {
                    const res = await fetch(box.dataset.url + '?' + params(), { headers: { Accept: 'text/html' }, credentials: 'same-origin' });
                    if (res.ok && !res.redirected && mine === ticket) box.innerHTML = await res.text();
                } catch { /* bir sonraki turda tekrar */ }
            };
            const apply = () => {
                const p = params();
                const url = new URL(location.href);
                ['q', 'when', 'country', 'device'].forEach((key) => p.has(key) ? url.searchParams.set(key, p.get(key)) : url.searchParams.delete(key));
                history.replaceState(null, '', url);
                reset.classList.toggle('hidden', ![...p.keys()].length);
                refresh();
            };
            let typing;
            form.addEventListener('input', (e) => { clearTimeout(typing); typing = setTimeout(apply, e.target.type === 'search' ? 300 : 0); });
            reset.addEventListener('click', () => { form.reset(); form.querySelectorAll('input, select').forEach((el) => { el.value = el.name === 'when' ? '0' : ''; }); apply(); });
            setInterval(refresh, 10000);
            document.addEventListener('visibilitychange', () => !document.hidden && refresh());
        })();
    </script>
@endpush
