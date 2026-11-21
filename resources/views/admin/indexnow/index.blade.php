@extends('layouts.admin')

@section('title', 'IndexNow')

@php
    $fmt = fn ($n) => number_format($n, 0, ',', '.');
    $lastAt = $last ? \Illuminate\Support\Carbon::createFromTimestamp($last['at']) : null;
@endphp

@section('header')
    <x-admin.page-header :title="__('IndexNow')" :breadcrumb="['Yönetim' => route('management.index')]"
        :description="__('Panel her gün siteyi tarar; yeni ya da değişen sayfaları (aktiviteler, şehirler, yerler, bloglar, her dilde) Bing, Yandex, Seznam ve Naver\'e kendiliğinden bildirir. Değişmeyen sayfa tekrar gönderilmez. Google IndexNow kullanmaz.')">
        <x-slot:icon><path d="M13 2 4 14h7l-1 8 9-12h-7Z" /></x-slot:icon>
    </x-admin.page-header>
@endsection

@section('content')
    <div data-live="indexnow-page">
        <div class="relative isolate overflow-hidden rounded-3xl bg-gradient-to-br from-sky-600 via-sky-500 to-indigo-500 p-6 text-white shadow-[0_24px_50px_-30px_rgba(14,116,144,0.9)] sm:p-8">
            <div class="pointer-events-none absolute -right-16 -top-20 -z-10 size-72 rounded-full bg-white/10 blur-2xl" aria-hidden="true"></div>
            <div class="flex flex-wrap items-start justify-between gap-6">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-white/70">{{ __('Bildirilen adres') }}</p>
                    <p class="mt-2 text-4xl font-extrabold tracking-tight tabular-nums">{{ $fmt($known) }}</p>
                    <p class="mt-1 text-sm text-white/85">
                        @if ($lastAt)
                            {{ __('Son gönderim: :date · :count adres', ['date' => $lastAt->format('d.m.Y H:i'), 'count' => $fmt($last['sent'])]) }}
                        @else
                            {{ __('Henüz gönderim yapılmadı; ilk çalışmada sitedeki bütün sayfalar bildirilir (her gün 06:20 ya da hemen, yandaki düğmeyle).') }}
                        @endif
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <form method="POST" action="{{ route('indexnow.sync') }}">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-white px-5 py-3 text-sm font-bold text-sky-700 shadow-lg transition hover:bg-sky-50">{{ __('Şimdi kontrol et ve gönder') }}</button>
                    </form>
                    <form method="POST" action="{{ route('indexnow.resend') }}" data-confirm="{{ __('Sitedeki bütün sayfalar yeniden bildirilsin mi? Sadece gerektiğinde kullan (ör. site adresleri topluca değiştiyse).') }}">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-white/15 px-4 py-3 text-sm font-semibold text-white ring-1 ring-white/30 transition hover:bg-white/25">{{ __('Hepsini yeniden gönder') }}</button>
                    </form>
                </div>
            </div>
            <dl class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-4">
                @foreach ([
                    [__('Bugün bildirilen'), $fmt($today)],
                    [__('Otomatik kontrol'), __('Her gün 06:20')],
                    [__('Son kontrol'), $lastRun ? \Illuminate\Support\Carbon::createFromTimestamp($lastRun)->diffForHumans() : '—'],
                    [__('Anahtar dosyası'), $keyOk ? __('Doğru ✓') : __('Bulunamadı')],
                ] as [$label, $value])
                    <div class="rounded-2xl bg-white/12 px-4 py-3 ring-1 ring-white/20">
                        <dt class="text-xs font-semibold text-white/75">{{ $label }}</dt>
                        <dd class="mt-0.5 text-lg font-extrabold">{{ $value }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>

        @if ($last && $last['error'])
            <div class="mt-6 rounded-2xl bg-red-50 px-5 py-4 text-sm text-red-800 ring-1 ring-red-200">
                <p class="font-semibold">{{ __('Son çalışmada hata: :date', ['date' => $lastAt->format('d.m.Y H:i')]) }}</p>
                <p class="mt-1">{{ $last['error'] }}</p>
                <p class="mt-1 text-xs text-red-700">{{ __('Ertesi gün kendiliğinden yeniden denenir; istersen yukarıdan hemen gönder.') }}</p>
            </div>
        @endif

        @unless ($keyOk)
            <div class="mt-6 rounded-2xl bg-amber-50 px-5 py-4 text-sm text-amber-900 ring-1 ring-amber-200">
                <p class="font-semibold">{{ __('Anahtar dosyası sitede bulunamadı') }}</p>
                <p class="mt-1">{{ __('Arama motorları sitenin sahibini bu dosyayla doğrular. Sitenin son hali yayına çıkınca (birkaç dakika) düzelir:') }}
                    <a href="{{ $keyUrl }}" target="_blank" rel="noopener" class="font-semibold underline">{{ $keyUrl }}</a></p>
            </div>
        @endunless

        <x-admin.card class="mt-6 overflow-hidden">
            <div class="border-b border-slate-100 px-6 py-4">
                <h2 class="text-sm font-semibold text-slate-900">{{ __('Son bildirilen sayfalar') }}</h2>
                <p class="text-xs text-slate-500">{{ __('Yeni ya da güncellenen sayfa ertesi sabahki kontrolde gönderilir ve burada görünür.') }}</p>
            </div>
            <ul class="divide-y divide-slate-100">
                @forelse ($recent as $row)
                    <li class="flex items-center justify-between gap-3 px-6 py-2.5 text-sm">
                        <a href="{{ $row->url }}" target="_blank" rel="noopener" class="min-w-0 truncate text-slate-700 hover:text-brand-900 hover:underline">{{ \Illuminate\Support\Str::after($row->url, '://') }}</a>
                        <span class="shrink-0 text-xs text-slate-400">{{ $row->submitted_at ? \Illuminate\Support\Carbon::parse($row->submitted_at)->format('d.m H:i') : '' }}</span>
                    </li>
                @empty
                    <li class="px-6 py-8 text-center text-sm text-slate-500">{{ __('Henüz bildirim yok.') }}</li>
                @endforelse
            </ul>
        </x-admin.card>
    </div>
@endsection
