@extends('layouts.admin')

@section('title', 'Search Console')

@php
    $fmt = fn ($n) => number_format((float) $n, 0, ',', '.');
    $pct = fn ($n) => number_format((float) $n * 100, 1, ',', '.') . '%';
    $pos = fn ($n) => number_format((float) $n, 1, ',', '.');
    $short = fn ($url) => \Illuminate\Support\Str::after((string) $url, '://');
    $field = 'mt-1.5 block w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm outline-none transition placeholder:text-slate-400 focus:border-brand-900 focus:ring-4 focus:ring-brand-900/10';
    $btn = 'inline-flex items-center justify-center gap-2 rounded-xl bg-brand-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-800';
    $hints = [
        'firsatlar' => __('Google\'ın ilk 10 sonucunda görünüyor ama tıklanma oranı %:ctr\'nin altında (en az :min gösterim). Bu sayfanın SEO başlığını ve açıklamasını, aranan kelimeyi içerecek ve tıklatacak şekilde yeniden yaz.', ['ctr' => $lowCtr * 100, 'min' => $minImpressions]),
        'yakin' => __('Ortalama 8–20. sırada: ilk sayfanın sonunda ya da ikinci sayfada. İçeriği zenginleştir (SSS, bilgi, iç bağlantı); biraz emekle ilk sayfaya çıkabilir.'),
        'sayfalar' => __('Sitedeki sayfalar, Google\'dan gelen tıklamaya göre.'),
        'aramalar' => __('İnsanların Google\'da yazıp senin sitende karşılaştığı aramalar.'),
    ];
@endphp

@section('header')
    <x-admin.page-header :title="__('Search Console')"
        :description="__('Google aramasındaki görünürlüğün (son 28 gün): hangi sayfa hangi aramada kaçıncı, nerede görünüp tıklanmıyor. Her sabah kendiliğinden güncellenir; Google verisi 2–3 gün geriden gelir.')">
        <x-slot:icon><circle cx="10.5" cy="10.5" r="6.5" /><path d="m20 20-4.8-4.8M8 12.5v-2M10.5 12.5V8.5M13 12.5v-3" /></x-slot:icon>
        @if ($connected && $site)
            <form method="POST" action="{{ route('search-console.sync') }}">
                @csrf
                <button type="submit" class="{{ $btn }}">{{ __('Şimdi güncelle') }}</button>
            </form>
        @endif
    </x-admin.page-header>
@endsection

@section('content')
    @if ($connected && $site)
        <div data-live="gsc-page">
            {{-- ÖZET --}}
            <div class="relative isolate overflow-hidden rounded-3xl bg-gradient-to-br from-emerald-600 via-teal-500 to-sky-500 p-6 text-white shadow-[0_24px_50px_-30px_rgba(5,150,105,0.9)] sm:p-8">
                <div class="pointer-events-none absolute -right-16 -top-20 -z-10 size-72 rounded-full bg-white/10 blur-2xl" aria-hidden="true"></div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-white/70">
                    {{ $totals ? __('Google · :start – :end', ['start' => \Illuminate\Support\Carbon::parse($totals['start'])->format('d.m'), 'end' => \Illuminate\Support\Carbon::parse($totals['end'])->format('d.m.Y')]) : __('Google') }}
                    · <span class="normal-case tracking-normal">{{ $site }}</span>
                </p>
                <dl class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
                    @foreach ([
                        [__('Tıklama'), $fmt($totals['clicks'] ?? 0), __('Google\'dan siteye gelen')],
                        [__('Gösterim'), $fmt($totals['impressions'] ?? 0), __('aramada görünme')],
                        [__('Tıklanma oranı'), $pct($totals['ctr'] ?? 0), __('gösterim başına tıklama')],
                        [__('Ortalama sıra'), $pos($totals['position'] ?? 0), __('küçük olan iyi')],
                    ] as [$label, $value, $hint])
                        <div class="rounded-2xl bg-white/12 px-4 py-3 ring-1 ring-white/20">
                            <dt class="text-xs font-semibold text-white/75">{{ $label }}</dt>
                            <dd class="mt-0.5 text-2xl font-extrabold tabular-nums">{{ $value }}</dd>
                            <dd class="text-[11px] text-white/70">{{ $hint }}</dd>
                        </div>
                    @endforeach
                </dl>
                <p class="mt-4 text-xs text-white/75">{{ $syncedAt ? __('Son güncelleme: :date', ['date' => \Illuminate\Support\Carbon::createFromTimestamp($syncedAt, config('app.timezone'))->format('d.m.Y H:i')]) : __('Henüz güncellenmedi.') }}</p>
            </div>

            @if (\App\Services\SearchConsole::reminderDue())
                <div class="mt-6 flex items-start gap-3 rounded-2xl bg-amber-50 px-5 py-4 text-sm text-amber-900 ring-1 ring-amber-200">
                    <span class="grid size-8 shrink-0 place-items-center rounded-lg bg-amber-500 text-base text-white">⏰</span>
                    <div>
                        <p class="font-semibold">{{ __('Hatırlatma: Search Console aracını kurma zamanı geldi') }}</p>
                        <p class="mt-1">{{ __('Google\'da 28 günde :count gösterime ulaştın; artık düzeltilecek fırsatlar oluşuyor. Claude\'a "Search Console aracını kur" de: verileri kendisi okuyup az tıklanan sayfaların başlık ve açıklamalarını düzeltsin.', ['count' => number_format(\App\Services\SearchConsole::totals()['impressions'] ?? 0, 0, ',', '.')]) }}</p>
                    </div>
                </div>
            @endif

            @if ($error)
                <div class="mt-6 rounded-2xl bg-red-50 px-5 py-4 text-sm text-red-800 ring-1 ring-red-200">
                    <p class="font-semibold">{{ __('Son güncellemede hata') }}</p>
                    <p class="mt-1">{{ $error }}</p>
                </div>
            @endif

            {{-- SEKMELER --}}
            <x-admin.card class="mt-6 overflow-hidden">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-4 py-3 sm:px-6">
                    <nav class="flex flex-wrap gap-1.5">
                        @foreach ($tabs as $key => $label)
                            <a href="{{ route('search-console.index', ['tab' => $key]) }}" @class([
                                'inline-flex items-center gap-1.5 rounded-xl px-3.5 py-2 text-sm font-semibold transition',
                                'bg-brand-900 text-white' => $tab === $key,
                                'text-slate-600 hover:bg-slate-100' => $tab !== $key,
                            ])>
                                {{ __($label) }}
                                @if (isset($counts[$key]) && $counts[$key])
                                    <span @class(['rounded-full px-1.5 text-[11px] tabular-nums', 'bg-white/20' => $tab === $key, 'bg-amber-100 text-amber-800' => $tab !== $key])>{{ $fmt($counts[$key]) }}</span>
                                @endif
                            </a>
                        @endforeach
                    </nav>
                    <form method="GET" action="{{ route('search-console.index') }}" class="flex items-center gap-2">
                        <input type="hidden" name="tab" value="{{ $tab }}">
                        <input type="search" name="search" value="{{ $search }}" placeholder="{{ $tab === 'aramalar' ? __('Aramada ara…') : __('Sayfa ya da arama…') }}"
                            class="w-52 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-sm outline-none focus:border-brand-900 focus:ring-4 focus:ring-brand-900/10" />
                        <button type="submit" class="rounded-xl bg-slate-100 px-3.5 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-200">{{ __('Ara') }}</button>
                    </form>
                </div>
                <p class="border-b border-slate-100 bg-slate-50/60 px-4 py-2.5 text-xs text-slate-600 sm:px-6">{{ $hints[$tab] }}</p>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="text-left text-xs uppercase tracking-wide text-slate-500">
                            <tr class="border-b border-slate-100">
                                <th class="px-4 py-2.5 font-medium sm:px-6">{{ $tab === 'aramalar' ? __('Arama') : ($tab === 'firsatlar' ? __('Arama · sayfa') : __('Sayfa')) }}</th>
                                <th class="px-3 py-2.5 text-right font-medium">{{ __('Tıklama') }}</th>
                                <th class="px-3 py-2.5 text-right font-medium">{{ __('Gösterim') }}</th>
                                <th class="px-3 py-2.5 text-right font-medium">{{ __('Oran') }}</th>
                                <th class="px-3 py-2.5 text-right font-medium">{{ __('Sıra') }}</th>
                                <th class="px-4 py-2.5 sm:px-6"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($rows as $row)
                                <tr class="align-top">
                                    <td class="max-w-xl px-4 py-2.5 sm:px-6">
                                        @if ($row->query)
                                            <p class="font-medium text-slate-900">{{ $row->query }}</p>
                                        @endif
                                        @if ($row->page)
                                            <a href="{{ $row->page }}" target="_blank" rel="noopener" @class(['block truncate hover:underline', 'text-xs text-slate-500' => $row->query, 'text-slate-800' => !$row->query])>{{ $short($row->page) }}</a>
                                            @isset($moved[$row->page])
                                                <span class="mt-0.5 inline-block rounded-md bg-slate-100 px-1.5 py-0.5 text-[10px] font-semibold text-slate-600" title="{{ __('Bu adres değişti; ziyaretçi yeni adrese yönlendiriliyor. Google birkaç hafta içinde yeni adresi öğrenir.') }}">{{ __('eski adres → yönlendiriliyor') }}</span>
                                            @endisset
                                        @elseif ($tab === 'aramalar')
                                            <p class="text-xs text-slate-500">{{ __(':count sayfada', ['count' => $row->pages]) }}</p>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2.5 text-right tabular-nums font-semibold text-slate-900">{{ $fmt($row->clicks) }}</td>
                                    <td class="px-3 py-2.5 text-right tabular-nums text-slate-600">{{ $fmt($row->impressions) }}</td>
                                    <td @class(['px-3 py-2.5 text-right tabular-nums', 'text-red-600 font-semibold' => $row->ctr < $lowCtr && $row->position <= 10, 'text-slate-600' => !($row->ctr < $lowCtr && $row->position <= 10)])>{{ $pct($row->ctr) }}</td>
                                    <td class="px-3 py-2.5 text-right tabular-nums text-slate-600">{{ $pos($row->position) }}</td>
                                    <td class="px-4 py-2.5 text-right sm:px-6">
                                        @if ($row->page && isset($edit[$row->page]))
                                            <a href="{{ $edit[$row->page] }}" class="text-xs font-semibold text-brand-900 hover:underline">{{ __('Düzenle') }}</a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-6 py-10 text-center text-sm text-slate-500">
                                    {{ $search ? __('Aramana uyan kayıt yok.') : ($tab === 'firsatlar' ? __('Şimdilik fırsat yok: ilk 10\'da görünen sayfaların tıklanma oranı iyi ya da henüz yeterli gösterim yok.') : __('Henüz veri yok.')) }}
                                </td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 px-6 py-3.5 text-sm text-slate-500">
                    <span>{{ __('Toplam') }} <strong class="font-semibold text-slate-700">{{ $fmt($rows->total()) }}</strong></span>
                    @if ($rows->hasPages()) {{ $rows->links() }} @endif
                </div>
            </x-admin.card>
        </div>
    @endif

    {{-- BAĞLANTI --}}
    <h2 @class(['text-sm font-bold uppercase tracking-wide text-slate-500', 'mt-10' => $connected && $site])>{{ __('Bağlantı') }}</h2>
    <div class="mt-3 grid gap-6 lg:grid-cols-2">
        <x-admin.card class="p-6">
            <p class="text-xs font-bold text-slate-400">1</p>
            <h3 class="text-sm font-semibold text-slate-900">{{ __('Google hizmet hesabı anahtarı') }}</h3>
            @if ($connected)
                <p class="mt-3 inline-flex items-center gap-2 rounded-xl bg-emerald-50 px-3 py-2 text-sm font-semibold text-emerald-700">
                    <span class="size-2 rounded-full bg-emerald-500"></span>{{ __('Kayıtlı') }}
                </p>
                <p class="mt-3 text-xs text-slate-500">{{ __('Hizmet hesabının e-postası (Search Console\'da kullanıcı olarak ekli olmalı):') }}</p>
                <div class="mt-1 flex items-center gap-2 rounded-xl bg-slate-50 px-3 py-2 ring-1 ring-slate-200">
                    <code class="min-w-0 flex-1 truncate text-xs text-slate-700">{{ $serviceEmail }}</code>
                    <button type="button" class="shrink-0 text-xs font-semibold text-brand-900 hover:underline"
                        onclick="navigator.clipboard.writeText(this.previousElementSibling.textContent); this.textContent = @js(__('Kopyalandı ✓'))">{{ __('Kopyala') }}</button>
                </div>
                <form method="POST" action="{{ route('search-console.disconnect') }}" class="mt-4" data-confirm="{{ __('Search Console bağlantısı ve çekilen veriler silinsin mi?') }}">
                    @csrf @method('DELETE')
                    <button type="submit" class="text-xs font-semibold text-red-600 hover:underline">{{ __('Bağlantıyı kaldır') }}</button>
                </form>
            @else
                <ol class="mt-2 list-decimal space-y-1.5 pl-5 text-xs leading-relaxed text-slate-600">
                    <li>{!! __('<strong>console.cloud.google.com</strong>\'da bir proje aç (ör. "TripSpoiler").') !!}</li>
                    <li>{!! __('"API\'ler ve Hizmetler › Kitaplık"tan <strong>Google Search Console API</strong>\'yi bul ve <strong>Etkinleştir</strong>.') !!}</li>
                    <li>{!! __('"IAM ve Yönetici › Hizmet hesapları" › <strong>Hizmet hesabı oluştur</strong> (ad: tripspoiler-panel; rol vermene gerek yok).') !!}</li>
                    <li>{!! __('Hesaba tıkla › <strong>Anahtarlar</strong> › Anahtar ekle › Yeni anahtar › <strong>JSON</strong>. Bir .json dosyası iner.') !!}</li>
                    <li>{!! __('Dosyayı metin düzenleyiciyle aç, <strong>tamamını</strong> aşağıya yapıştır, kaydet.') !!}</li>
                </ol>
                <form method="POST" action="{{ route('search-console.credentials') }}" class="mt-4 space-y-3">
                    @csrf @method('PUT')
                    <textarea name="credentials" rows="5" required spellcheck="false" autocomplete="off" placeholder='{"type": "service_account", ...}'
                        class="{{ $field }} font-mono text-xs"></textarea>
                    @error('credentials') <p class="text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    <p class="text-[11px] text-slate-500">{{ __('Şifreli saklanır, bir daha gösterilmez. Sadece okuma izni kullanılır.') }}</p>
                    <button type="submit" class="{{ $btn }}">{{ __('Kaydet') }}</button>
                </form>
            @endif
        </x-admin.card>

        <x-admin.card class="p-6">
            <p class="text-xs font-bold text-slate-400">2</p>
            <h3 class="text-sm font-semibold text-slate-900">{{ __('Search Console mülkü') }}</h3>
            @if (!$connected)
                <p class="mt-1 text-xs text-slate-500">{{ __('Önce 1. adımdaki anahtarı kaydet.') }}</p>
            @else
                <ol class="mt-2 list-decimal space-y-1.5 pl-5 text-xs leading-relaxed text-slate-600">
                    <li>{!! __('<strong>search.google.com/search-console</strong> › sitenin mülkü › <strong>Ayarlar › Kullanıcılar ve izinler</strong>.') !!}</li>
                    <li>{!! __('<strong>Kullanıcı ekle</strong>: soldaki kutudaki hizmet hesabı e-postasını yapıştır, izin: <strong>Kısıtlanmış</strong> (sadece okuma yeter).') !!}</li>
                    <li>{{ __('Bu sayfayı yenile, mülkü seç, kaydet.') }}</li>
                </ol>
                @if ($sitesError)
                    <p class="mt-3 text-xs font-medium text-red-600">{{ $sitesError }}</p>
                @elseif (!$sites)
                    <p class="mt-3 rounded-xl bg-amber-50 px-3 py-2 text-xs font-medium text-amber-800 ring-1 ring-amber-200">{{ __('Hizmet hesabı henüz hiçbir mülke eklenmemiş. Yukarıdaki adımları yapıp bu sayfayı yenile.') }}</p>
                @else
                    <form method="POST" action="{{ route('search-console.site') }}" class="mt-4 space-y-3">
                        @csrf @method('PUT')
                        <select name="site" required class="{{ $field }}">
                            @foreach ($sites as $option)
                                <option value="{{ $option }}" @selected($site === $option)>{{ $option }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="{{ $btn }}">{{ $site ? __('Kaydet ve güncelle') : __('Kaydet ve verileri çek') }}</button>
                    </form>
                @endif
            @endif
        </x-admin.card>
    </div>
@endsection
