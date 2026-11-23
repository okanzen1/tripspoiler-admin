@extends('layouts.admin')

@section('title', 'Sıcaklık haritası')

@php
    use App\Services\Engagement;

    $fmt = fn ($n) => number_format((float) $n, 0, ',', '.');
    $link = fn (array $over = []) => route('analytics.heatmap', array_filter([...['page' => $path, 'device' => $device, 'range' => $days], ...$over], fn ($v) => $v !== null && $v !== ''));
    $siteUrl = rtrim(config('site.url'), '/');
    $tones = [
        'red' => ['bg-red-50 ring-red-200', 'bg-red-500', 'text-red-900'],
        'amber' => ['bg-amber-50 ring-amber-200', 'bg-amber-500', 'text-amber-900'],
        'emerald' => ['bg-emerald-50 ring-emerald-200', 'bg-emerald-500', 'text-emerald-900'],
        'slate' => ['bg-slate-50 ring-slate-200', 'bg-slate-400', 'text-slate-800'],
    ];
    $flagTones = ['red' => 'bg-red-100 text-red-700', 'amber' => 'bg-amber-100 text-amber-800', 'emerald' => 'bg-emerald-100 text-emerald-700'];
    $short = fn (?string $p) => \Illuminate\Support\Str::limit((string) $p, 70);
@endphp

@section('header')
    <x-admin.page-header :title="__('Sıcaklık haritası')" :breadcrumb="['Analitik' => route('analytics.index')]"
        :description="__('Ziyaretçiler sayfada ne kadar kalıyor, nereye kadar iniyor, nereye tıklıyor ve ne yapmalı.')">
        <x-slot:icon><path d="M12 2c1 3 4 5 4 9a4 4 0 0 1-8 0c0-1.5.5-2.5 1.5-3.5C9 10 10 11 11 11c0-3-1-6 1-9Z" /><path d="M6 15a6 6 0 0 0 12 0" /></x-slot:icon>
    </x-admin.page-header>
@endsection

@section('content')
    {{-- Filtre: sayfa, cihaz, dönem --}}
    <form method="GET" action="{{ route('analytics.heatmap') }}" data-no-ajax
        class="sticky top-0 z-20 -mx-1 mb-6 rounded-2xl bg-gradient-to-r from-orange-500 via-rose-500 to-fuchsia-600 p-3 shadow-[0_18px_40px_-22px_rgba(225,29,72,0.9)] sm:p-4">
        <input type="hidden" name="device" value="{{ $device }}">
        <input type="hidden" name="range" value="{{ $days }}">
        <div class="flex flex-wrap items-center gap-2.5">
            <label class="relative min-w-[16rem] flex-1">
                <svg class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-rose-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8Z" /><path d="M14 3v5h5" /></svg>
                <select name="page" onchange="this.form.submit()" aria-label="{{ __('Sayfa') }}"
                    class="h-10 w-full appearance-none truncate rounded-xl border-0 bg-white pl-9 pr-9 text-sm font-semibold text-slate-800 outline-none ring-1 ring-white focus:ring-2 focus:ring-white/80">
                    @forelse ($options as $option)
                        <option value="{{ $option->path }}" @selected($option->path === $path)>{{ $short($option->path) }} · {{ $fmt($option->views) }} {{ __('görüntüleme') }} · {{ $fmt($option->clicks) }} {{ __('tıklama') }}</option>
                    @empty
                        <option value="">{{ __('Henüz ölçülen sayfa yok') }}</option>
                    @endforelse
                    @if ($path && !$options->contains('path', $path))
                        <option value="{{ $path }}" selected>{{ $short($path) }}</option>
                    @endif
                </select>
                <svg class="pointer-events-none absolute right-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="m6 9 6 6 6-6" /></svg>
            </label>

            <div class="inline-flex h-10 items-center rounded-xl bg-white/15 p-1 ring-1 ring-white/25">
                @foreach (['desktop' => ['Bilgisayar', '<rect x="2.5" y="4.5" width="19" height="12" rx="2" /><path d="M8 20h8M12 16.5V20" />'], 'mobile' => ['Telefon', '<rect x="7" y="2.5" width="10" height="19" rx="2" /><path d="M11 18.5h2" />']] as $value => [$label, $icon])
                    <a href="{{ $link(['device' => $value]) }}" @class(['flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold transition', 'bg-white text-rose-700 shadow-sm' => $device === $value, 'text-white/85 hover:text-white' => $device !== $value])>
                        <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $icon !!}</svg>{{ __($label) }}
                    </a>
                @endforeach
            </div>

            <div class="inline-flex h-10 items-center rounded-xl bg-white/15 p-1 ring-1 ring-white/25">
                @foreach ([7 => 'Son 7 gün', 30 => 'Son 30 gün', 90 => 'Son 90 gün'] as $value => $label)
                    <a href="{{ $link(['range' => $value]) }}" @class(['rounded-lg px-3 py-1.5 text-xs font-semibold transition', 'bg-white text-rose-700 shadow-sm' => $days === $value, 'text-white/85 hover:text-white' => $days !== $value])>{{ __($label) }}</a>
                @endforeach
            </div>
        </div>
    </form>

    @if (!$page || (!$page['views'] && !$page['clicks']))
        <x-admin.card class="mb-6 px-6 py-14 text-center">
            <span class="mx-auto grid size-14 place-items-center rounded-2xl bg-gradient-to-br from-orange-400 to-rose-500 text-white shadow-sm">
                <svg class="size-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 2c1 3 4 5 4 9a4 4 0 0 1-8 0c0-1.5.5-2.5 1.5-3.5C9 10 10 11 11 11c0-3-1-6 1-9Z" /></svg>
            </span>
            <p class="mt-4 text-base font-semibold text-slate-900">{{ __('Veri toplanıyor') }}</p>
            <p class="mx-auto mt-1 max-w-md text-sm text-slate-500">{{ __('Site, kalma süresini, kaydırmayı ve tıklanan yerleri yeni ölçmeye başladı. Ziyaretçiler geldikçe haritalar burada kendiliğinden oluşur.') }}</p>
        </x-admin.card>
    @else
        {{-- Özet --}}
        @php
            $kpis = [
                ['Görüntüleme', $fmt($page['views']), __(':count ziyaretçi', ['count' => $fmt($page['visitors'])]), 'from-sky-500 to-indigo-500', '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z" /><circle cx="12" cy="12" r="3" />'],
                ['Sayfada kalma', Engagement::duration($page['duration']), __('ortanca · ortalama :t', ['t' => Engagement::duration($page['duration_avg'])]), 'from-violet-500 to-fuchsia-500', '<circle cx="12" cy="13" r="8" /><path d="M12 9v4l2.5 2.5M9 2h6" />'],
                ['Nereye kadar iniyor', $page['scroll'] !== null ? '%' . $page['scroll'] : '—', __('sayfanın ortalama görülen kısmı'), 'from-cyan-500 to-sky-500', '<path d="M12 3v18M6 15l6 6 6-6" />'],
                ['Hemen çıkan', $page['quick'] !== null ? '%' . $page['quick'] : '—', __(':s saniyeden kısa kalan', ['s' => Engagement::QUICK_EXIT]), 'from-rose-500 to-red-500', '<path d="M10 17l5-5-5-5M15 12H3M21 3v18" />'],
                ['Partnere giden', $page['ctr'] !== null ? '%' . number_format($page['ctr'], 1, ',', '.') : '—', __(':count kişi tıkladı', ['count' => $fmt($page['partner_clickers'])]), 'from-amber-400 to-orange-500', '<path d="M7 17 17 7M8 7h9v9" />'],
                ['Sayfa içi tıklama', $fmt($page['clicks']), __(':r sinirli · :d boşa', ['r' => $fmt($page['rage']->sum('total')), 'd' => $fmt($page['dead']->sum('total'))]), 'from-emerald-500 to-teal-500', '<path d="m9 9 5 12 1.8-5.2L21 14Z" /><path d="M7.2 2.2 8 5.1M5.1 8l-2.9-.8M14 4.1 12 6M6 12l-1.9 2" />'],
            ];
        @endphp
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-6">
            @foreach ($kpis as [$label, $value, $hint, $gradient, $icon])
                <x-admin.card class="p-4">
                    <div class="flex items-center gap-2.5">
                        <span class="grid size-8 shrink-0 place-items-center rounded-lg bg-gradient-to-br {{ $gradient }} text-white shadow-sm">
                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $icon !!}</svg>
                        </span>
                        <p class="text-xs font-semibold text-slate-500">{{ __($label) }}</p>
                    </div>
                    <p class="mt-2.5 text-2xl font-bold tabular-nums tracking-tight text-slate-900">{{ $value }}</p>
                    <p class="mt-0.5 truncate text-[11px] text-slate-400">{{ $hint }}</p>
                </x-admin.card>
            @endforeach
        </div>

        {{-- Ne yapmalı --}}
        <x-admin.card class="mt-5 p-5">
            <h2 class="flex items-center gap-2 text-sm font-semibold text-slate-900">
                <span class="grid size-7 place-items-center rounded-lg bg-gradient-to-br from-amber-400 to-orange-500 text-white">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 18h6M10 22h4M12 2a7 7 0 0 0-4 12.7c.6.5 1 1.3 1 2.3h6c0-1 .4-1.8 1-2.3A7 7 0 0 0 12 2Z" /></svg>
                </span>
                {{ __('Ne yapmalı') }}
            </h2>
            <ul class="mt-3 grid gap-2.5 lg:grid-cols-2">
                @foreach ($page['insights'] as $tip)
                    @php([$box, $dot, $text] = $tones[$tip['tone']] ?? $tones['slate'])
                    <li class="flex gap-3 rounded-xl px-4 py-3 ring-1 {{ $box }}">
                        <span class="mt-1.5 size-2.5 shrink-0 rounded-full {{ $dot }}"></span>
                        <span>
                            <span class="block text-sm font-semibold {{ $text }}">{{ $tip['title'] }}</span>
                            <span class="block text-sm text-slate-600">{{ $tip['text'] }}</span>
                        </span>
                    </li>
                @endforeach
            </ul>
        </x-admin.card>

        {{-- Harita + listeler --}}
        <div class="mt-5 grid gap-5">
            <x-admin.card class="overflow-hidden">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-3.5">
                    <div class="inline-flex rounded-xl bg-slate-100 p-1" role="group" aria-label="{{ __('Görünüm') }}">
                        @foreach (['clicks' => 'Tıklamalar', 'scroll' => 'Kaydırma', 'problems' => 'Sorunlar'] as $mode => $label)
                            <button type="button" data-heatmap-mode="{{ $mode }}" form="none" aria-pressed="{{ $mode === 'clicks' ? 'true' : 'false' }}"
                                class="rounded-lg px-3.5 py-1.5 text-xs font-semibold text-slate-500 transition hover:text-slate-900 aria-pressed:bg-white aria-pressed:text-rose-700 aria-pressed:shadow-sm">{{ __($label) }}</button>
                        @endforeach
                    </div>
                    <div class="flex items-center gap-3 text-[11px] font-medium text-slate-500">
                        <span data-heatmap-legend="clicks" class="flex items-center gap-2">{{ __('az') }} <span class="h-2 w-28 rounded-full bg-gradient-to-r from-blue-600 via-green-500 via-60% to-red-500"></span> {{ __('çok tıklanan') }}</span>
                        <span data-heatmap-legend="scroll" class="flex hidden items-center gap-2">{{ __('az kişi') }} <span class="h-2 w-28 rounded-full bg-gradient-to-r from-blue-600 via-green-500 via-60% to-red-500"></span> {{ __('herkes gördü') }}</span>
                        <span data-heatmap-legend="problems" class="flex hidden items-center gap-3">
                            <span class="flex items-center gap-1.5"><span class="size-3 rounded-full bg-red-500 ring-2 ring-red-200"></span>{{ __('sinirli tıklama') }}</span>
                            <span class="flex items-center gap-1.5"><span class="size-2.5 rounded-full bg-amber-400 ring-2 ring-amber-200"></span>{{ __('boşa tıklama') }}</span>
                        </span>
                        <x-admin.site-link :path="$path" />
                    </div>
                </div>

                <div data-heatmap class="flex justify-center gap-4 bg-[radial-gradient(circle_at_1px_1px,rgb(148_163_184/0.35)_1px,transparent_0)] bg-[length:16px_16px] p-4"
                    data-fold-label="{{ __('Kaydırmadan görünen kısım burada biter') }}"
                    data-reach-label="{{ __('Ziyaretçilerin %:p kadarı buraya kadar indi') }}"
                    data-rage-label="{{ __('Sinirli tıklama') }}" data-dead-label="{{ __('Boşa tıklama') }}"
                    data-fail-label="{{ __('Sayfa açılamadı. Site güncellemesi henüz yayına çıkmamış olabilir.') }}">
                    <div data-heatmap-viewport class="relative shrink-0 overflow-hidden rounded-xl bg-white shadow-[0_20px_50px_-20px_rgba(15,23,42,0.45)] ring-1 ring-slate-300">
                        <div data-heatmap-scale class="absolute left-0 top-0 origin-top-left" style="width: {{ $page['width'] }}px; height: {{ $page['fold'] }}px">
                            <iframe data-heatmap-frame src="{{ $siteUrl . $path }}?ts-heatmap=1" title="{{ $path }}" width="{{ $page['width'] }}" height="{{ $page['fold'] }}"
                                sandbox="allow-scripts allow-same-origin" referrerpolicy="no-referrer" class="block border-0 bg-white"></iframe>
                            <div class="pointer-events-none absolute inset-0 overflow-hidden">
                                <div data-heatmap-layer class="absolute left-0 top-0 will-change-transform" style="width: {{ $page['width'] }}px">
                                    <canvas data-heatmap-canvas class="absolute inset-0 size-full"></canvas>
                                    <div data-heatmap-marks class="absolute inset-0"></div>
                                </div>
                            </div>
                        </div>
                        <div data-heatmap-loading class="absolute inset-0 grid place-items-center bg-white/90">
                            <p class="flex items-center gap-2 text-sm font-medium text-slate-500">
                                <svg class="size-4 animate-spin text-rose-500" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-20"/><path d="M22 12a10 10 0 0 0-10-10" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
                                <span data-heatmap-loading-text>{{ __('Sayfa açılıyor…') }}</span>
                            </p>
                        </div>
                    </div>
                    <div data-heatmap-minimap title="{{ __('Bütün sayfa: tıkla ya da sürükle') }}" class="relative w-12 shrink-0 cursor-pointer touch-none overflow-hidden rounded-lg bg-white ring-1 ring-slate-300">
                        <canvas class="absolute inset-0 size-full"></canvas>
                        <span data-heatmap-window class="pointer-events-none absolute inset-x-0 rounded border-2 border-slate-900/70 bg-slate-900/10"></span>
                    </div>
                </div>
                <p class="border-t border-slate-100 px-5 py-2.5 text-[11px] text-slate-400">
                    {{ __('Sayfa, ziyaretçinin ekranı boyunda açılır: içinde kaydır ya da sağdaki şeritten git. Harita son :count tıklamadan çizilir; sayfa o günden bu yana değiştiyse noktalar biraz kayabilir.', ['count' => $fmt(min(5000, $page['clicks']))]) }}
                </p>
            </x-admin.card>

            <div class="grid items-start gap-5 lg:grid-cols-3">
                @foreach ([
                    ['En çok tıklananlar', $page['elements'], 'Bu sayfada henüz tıklama yok.', 'from-rose-500 to-orange-400'],
                    ['Sinirli tıklamalar', $page['rage'], 'Sinirli tıklama yok, güzel.', 'from-red-500 to-red-400'],
                    ['Boşa tıklamalar', $page['dead'], 'Boşa tıklama yok.', 'from-amber-400 to-amber-300'],
                ] as [$title, $items, $empty, $bar])
                    @continue($title !== 'En çok tıklananlar' && $items->isEmpty())
                    <x-admin.card class="overflow-hidden">
                        <h3 class="border-b border-slate-100 px-5 py-3 text-sm font-semibold text-slate-900">{{ __($title) }}</h3>
                        <ol class="divide-y divide-slate-100">
                            @php($top = max(1, $items->max('total')))
                            @forelse ($items as $item)
                                <li>
                                    <button type="button" data-heatmap-jump="{{ $item->y }}" data-ph="{{ $item->ph ?? 0 }}" title="{{ __('Sayfada göster') }}"
                                        class="block w-full px-5 py-2.5 text-left transition hover:bg-rose-50/60">
                                        <span class="flex items-center gap-2">
                                            <span class="min-w-0 flex-1 truncate text-sm font-medium text-slate-800">{{ $item->label ?: __('(isimsiz)') }}</span>
                                            @if ($item->partner)
                                                <span class="shrink-0 rounded-full bg-emerald-100 px-1.5 py-0.5 text-[10px] font-bold text-emerald-700">{{ __('partner') }}</span>
                                            @endif
                                            <span class="shrink-0 text-xs font-bold tabular-nums text-slate-700">{{ $fmt($item->total) }}</span>
                                        </span>
                                        @if ($item->href)
                                            <span class="block truncate text-[11px] text-slate-400">{{ $item->href }}</span>
                                        @endif
                                        <span class="mt-1.5 block h-1.5 overflow-hidden rounded-full bg-slate-100"><span class="block h-full rounded-full bg-gradient-to-r {{ $bar }}" style="width: {{ round($item->total / $top * 100) }}%"></span></span>
                                    </button>
                                </li>
                            @empty
                                <li class="px-5 py-8 text-center text-sm text-slate-400">{{ __($empty) }}</li>
                            @endforelse
                        </ol>
                    </x-admin.card>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Sayfa karnesi --}}
    <x-admin.card class="mt-6 overflow-hidden">
        <div class="border-b border-slate-100 bg-gradient-to-r from-orange-50 via-white to-fuchsia-50 px-6 py-4">
            <h2 class="text-sm font-semibold text-slate-900">{{ __('Sayfa karnesi') }}</h2>
            <p class="text-xs text-slate-500">{{ __('En çok görüntülenen sayfalar: ne kadar kalıyorlar, ne kadar iniyorlar, partnere gidiyorlar mı. Satıra tıkla, haritasını aç.') }}</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[900px] text-left text-sm">
                <thead class="bg-slate-50 text-xs font-semibold text-slate-500">
                    <tr>
                        <th class="px-6 py-2.5">{{ __('Sayfa') }}</th>
                        <th class="px-3 py-2.5 text-right">{{ __('Görüntüleme') }}</th>
                        <th class="px-3 py-2.5 text-right">{{ __('Ort. kalma') }}</th>
                        <th class="px-3 py-2.5">{{ __('Kaydırma') }}</th>
                        <th class="px-3 py-2.5 text-right">{{ __('Hemen çıkan') }}</th>
                        <th class="px-3 py-2.5 text-right">{{ __('Partnere giden') }}</th>
                        <th class="px-6 py-2.5">{{ __('Durum') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($pages as $row)
                        <tr @class(['cursor-pointer transition hover:bg-rose-50/50', 'bg-rose-50/70' => $row->path === $path]) onclick="location.href = this.dataset.href" data-href="{{ $link(['page' => $row->path]) }}">
                            <td class="max-w-[22rem] px-6 py-2.5"><span class="block truncate font-medium text-slate-800" title="{{ $row->path }}">{{ $row->path }}</span></td>
                            <td class="px-3 py-2.5 text-right tabular-nums text-slate-600">{{ $fmt($row->views) }}</td>
                            <td class="px-3 py-2.5 text-right tabular-nums text-slate-700">{{ Engagement::duration($row->duration) }}</td>
                            <td class="px-3 py-2.5">
                                @if ($row->scroll !== null)
                                    <span class="flex items-center gap-2">
                                        <span class="h-1.5 w-20 overflow-hidden rounded-full bg-slate-100"><span @class(['block h-full rounded-full', 'bg-emerald-500' => $row->scroll >= 60, 'bg-amber-400' => $row->scroll >= 40 && $row->scroll < 60, 'bg-red-400' => $row->scroll < 40]) style="width: {{ $row->scroll }}%"></span></span>
                                        <span class="text-xs tabular-nums text-slate-600">%{{ $row->scroll }}</span>
                                    </span>
                                @else
                                    <span class="text-slate-300">—</span>
                                @endif
                            </td>
                            <td class="px-3 py-2.5 text-right tabular-nums text-slate-600">{{ $row->quick !== null ? '%' . $row->quick : '—' }}</td>
                            <td class="px-3 py-2.5 text-right tabular-nums font-semibold text-amber-700">{{ $row->ctr !== null ? '%' . number_format($row->ctr, 1, ',', '.') : '—' }}</td>
                            <td class="px-6 py-2.5">
                                <span class="flex flex-wrap gap-1">
                                    @forelse ($row->flags as [$flag, $tone])
                                        <span class="rounded-full px-2 py-0.5 text-[10px] font-bold {{ $flagTones[$tone] }}">{{ __($flag) }}</span>
                                    @empty
                                        <span class="text-xs text-slate-300">{{ $row->measured < 5 ? __('ölçülüyor') : '—' }}</span>
                                    @endforelse
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-6 py-10 text-center text-slate-400">{{ __('Bu dönemde veri yok.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-admin.card>

    @if ($page && ($page['views'] || $page['clicks']))
        @php($heatmapData = ['points' => $page['points'], 'reach' => (object) $page['reach'], 'fold' => $page['fold'], 'width' => $page['width'], 'device' => $device])
        <script type="application/json" id="heatmap-data">{!! json_encode($heatmapData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) !!}</script>
    @endif
@endsection
