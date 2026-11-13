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
    {{-- Sayılmayan IP'ler: site sahibinin kendi ziyaretleri analitiği şişirmesin --}}
    @php
        $ignored = array_values(array_filter(array_map('trim', explode(',', (string) \App\Models\Setting::get('analytics_ignore_ips')))));
        $myIp = request()->ip();
    @endphp
    <div class="mb-6 flex flex-wrap items-center gap-3 rounded-2xl bg-white p-4 text-sm ring-1 ring-slate-200">
        <span class="grid size-9 place-items-center rounded-xl bg-slate-100 text-slate-500">
            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 3l18 18M10.6 5.1A10 10 0 0 1 22 12s-1.2 2.4-3.6 4.4M6.4 6.4C3.6 8.3 2 12 2 12s3.5 7 10 7c1.7 0 3.2-.5 4.5-1.2"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>
        </span>
        <span class="font-semibold text-slate-900">{{ __('Sayılmayan IP\'ler') }}</span>
        <span class="text-xs text-slate-500">{{ __('Panele girdiğin her cihaz ve IP otomatik eklenir.') }}</span>
        @forelse ($ignored as $ip)
            <form method="POST" action="{{ route('analytics.ignore-ip') }}" data-no-ajax class="inline-flex items-center gap-1 rounded-full bg-slate-100 py-1 pl-3 pr-1 font-mono text-xs text-slate-700">
                @csrf <input type="hidden" name="ip" value="{{ $ip }}"><input type="hidden" name="remove" value="1">
                {{ $ip }}@if ($ip === $myIp) <span class="font-sans font-semibold text-emerald-700">· {{ __('sen') }}</span>@endif
                <button type="submit" title="{{ __('Kaldır') }}" class="grid size-5 place-items-center rounded-full text-slate-400 hover:bg-red-50 hover:text-red-600">×</button>
            </form>
        @empty
            <span class="text-slate-500">{{ __('Yok — senin ziyaretlerin de sayılıyor.') }}</span>
        @endforelse
        @unless (in_array($myIp, $ignored, true))
            <form method="POST" action="{{ route('analytics.ignore-ip') }}" data-no-ajax class="ml-auto">
                @csrf <input type="hidden" name="ip" value="{{ $myIp }}">
                <button type="submit" class="inline-flex items-center gap-1.5 rounded-xl bg-brand-900 px-3.5 py-2 text-xs font-semibold text-white transition hover:bg-brand-800">
                    {{ __('Beni sayma') }} <span class="font-mono opacity-70">({{ $myIp }})</span>
                </button>
            </form>
        @endunless
    </div>

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
            <div class="border-b border-slate-100 px-6 pb-4 pt-6">
                <div class="flex flex-wrap items-end justify-between gap-2">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-900">{{ __('Ziyaretçiler') }}</h2>
                        <p class="text-xs text-slate-500">{{ __('Aynı IP tek satırda · satıra tıkla, o ziyaretçinin gezdiği her şeyi gör · botlar sayılmaz') }}</p>
                    </div>
                    <p class="flex items-center gap-1.5 text-[11px] text-slate-400">
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
                                <span class="block rounded-lg px-3 py-1 text-xs font-semibold text-slate-500 transition hover:text-slate-800 peer-checked:bg-white peer-checked:text-slate-900 peer-checked:shadow-sm peer-focus-visible:ring-2 peer-focus-visible:ring-brand-500">{{ __(str_replace('Son ', '', $label)) }}</span>
                            </label>
                        @endforeach
                    </div>

                    {{-- cihaz: tek tıkla, ikonlu --}}
                    <div class="inline-flex h-9 items-center rounded-xl bg-slate-100 p-1" role="radiogroup" aria-label="{{ __('Cihaz') }}">
                        @foreach (['' => ['Hepsi', null], 'desktop' => ['Bilgisayar', '<rect x="2.5" y="4.5" width="19" height="12" rx="2" /><path d="M8 20h8M12 16.5V20" />'], 'mobile' => ['Telefon', '<rect x="7" y="2.5" width="10" height="19" rx="2" /><path d="M11 18.5h2" />'], 'tablet' => ['Tablet', '<rect x="4.5" y="2.5" width="15" height="19" rx="2" /><path d="M11 18.5h2" />']] as $value => [$label, $icon])
                            <label class="cursor-pointer" title="{{ __($label) }}">
                                <input type="radio" name="device" value="{{ $value }}" class="peer sr-only" @checked(($liveFilters['device'] ?? '') === $value)>
                                <span class="flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-xs font-semibold text-slate-500 transition hover:text-slate-800 peer-checked:bg-white peer-checked:text-slate-900 peer-checked:shadow-sm peer-focus-visible:ring-2 peer-focus-visible:ring-brand-500">
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
                ['q', 'when', 'country', 'device'].forEach((key) => p.has(key) ? url.searchParams.set(key, p.get(key)) : url.searchParams.delete(key));
                history.replaceState(null, '', url);
                reset.classList.toggle('hidden', ![...p.keys()].length);
                // seçili ülke: bayrak + vurgulu çerçeve
                const option = country.selectedOptions[0];
                form.querySelector('[data-country-flag]').textContent = option?.dataset.flag || '🌍';
                country.classList.toggle('border-brand-900', !!country.value);
                country.classList.toggle('text-brand-900', !!country.value);
                country.classList.toggle('border-slate-200', !country.value);
                country.classList.toggle('text-slate-600', !country.value);
                refresh({ dim: true });
            };
            let typing;
            form.addEventListener('input', (e) => { clearTimeout(typing); typing = setTimeout(apply, e.target.type === 'search' ? 300 : 0); });
            reset.addEventListener('click', () => {
                form.querySelector('[name="q"]').value = '';
                form.querySelector('[name="when"][value="0"]').checked = true;
                form.querySelector('[name="device"][value=""]').checked = true;
                country.value = '';
                apply();
            });
            setInterval(refresh, 10000);
            document.addEventListener('visibilitychange', () => !document.hidden && refresh());
        })();
    </script>
@endpush
