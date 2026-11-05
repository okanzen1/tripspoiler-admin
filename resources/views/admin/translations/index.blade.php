@extends('layouts.admin')

@section('title', 'Diller ve çeviri')

@php
    $typeNames = ['activity' => 'Aktiviteler', 'blog' => 'Blog yazıları', 'faq' => 'Sıkça sorulan sorular', 'page' => 'Sayfalar (SEO)', 'announcement' => 'Duyurular', 'hero' => 'Hero alanı yazıları', 'feature' => 'Özellik kartları', 'section' => 'Bölüm yazıları', 'step' => 'Nasıl çalışır adımları'];
    $totalPending = array_sum($pendingByLocale);
@endphp

@section('header')
    <x-admin.page-header :title="__('Diller ve çeviri')" :breadcrumb="['Yönetim' => route('management.index')]"
        :description="__('İçerik İngilizce yazılır, ücretsiz Google Çeviri ile diğer dillere çevrilir. Adresler (slug) çevrilmez, her dilde aynıdır.')">
        <x-slot:icon><path d="m5 8 6 6M4 14l6-6 2-3M2 5h12M7 2h1M22 22l-5-10-5 10M14 18h6" /></x-slot:icon>
        <form action="{{ route('translations.all') }}" method="POST" data-confirm="{{ __('Tüm dillerdeki eksik ve eskimiş çeviriler sıraya alınsın mı?') }}">
            @csrf
            <button type="submit" @disabled($totalPending === 0)
                class="inline-flex items-center gap-2 rounded-xl bg-purple-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-purple-700 disabled:cursor-not-allowed disabled:opacity-50">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 8 6 6M4 14l6-6 2-3M2 5h12M7 2h1M22 22l-5-10-5 10M14 18h6" /></svg>
                {{ __('Tüm dillere çevir') }}
            </button>
        </form>
    </x-admin.page-header>
@endsection

@section('content')
    @if ($queued)
        <div class="mb-6 flex items-center gap-3 rounded-2xl bg-purple-50 px-4 py-3 text-sm text-purple-800 ring-1 ring-purple-100">
            <svg class="size-4 shrink-0 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-opacity=".25" stroke-width="3" /><path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round" /></svg>
            {{ __(':count kayıt çeviri sırasında. Sıra arka planda işlenir (sunucuda php artisan queue:work çalışmalı).', ['count' => $queued]) }}
        </div>
    @endif

    <div class="grid gap-6 xl:grid-cols-3">
        {{-- Diller --}}
        <x-admin.card class="overflow-hidden xl:col-span-2">
            <div class="p-6">
                <h2 class="text-base font-semibold text-slate-900">{{ __('Sitenin dilleri') }}</h2>
                <p class="mt-1 text-sm text-slate-500">{{ __('İstediğin dili tek başına çevirebilirsin. Kapalı dil sitede görünmez ve çevrilmez; İngilizce kaynak dildir.') }}</p>
            </div>
            <ul class="divide-y divide-slate-100 border-t border-slate-100">
                @foreach ($languages as $language)
                    @php($pending = $pendingByLocale[$language->code] ?? 0)
                    <li class="flex flex-wrap items-center gap-x-4 gap-y-2 px-6 py-3.5">
                        <span class="grid size-9 shrink-0 place-items-center rounded-lg bg-slate-100 text-xs font-bold text-slate-600">{{ strtoupper($language->code) }}</span>
                        <div class="min-w-36 flex-1">
                            <p class="text-sm font-semibold text-slate-900">{{ $language->native_name }}</p>
                            <p class="text-xs text-slate-500">{{ $language->name }}</p>
                        </div>

                        @if ($language->isSource())
                            <span class="rounded-full bg-brand-50 px-2.5 py-1 text-xs font-semibold text-brand-900">{{ __('Kaynak dil') }}</span>
                        @elseif (!$language->active)
                            <span class="text-xs text-slate-400">{{ __('Kapalı') }}</span>
                        @elseif ($pending === 0)
                            <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-emerald-700"><span class="size-1.5 rounded-full bg-emerald-500"></span>{{ __('Hepsi güncel') }}</span>
                        @else
                            <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-amber-700"><span class="size-1.5 rounded-full bg-amber-500"></span>{{ __(':count kayıt bekliyor', ['count' => $pending]) }}</span>
                            <form action="{{ route('translations.all') }}" method="POST">
                                @csrf
                                <input type="hidden" name="locale" value="{{ $language->code }}" />
                                <button type="submit" class="rounded-lg px-3 py-1.5 text-sm font-semibold text-purple-700 ring-1 ring-purple-200 transition hover:bg-purple-50">
                                    {{ __(':language çevir', ['language' => $language->native_name]) }}
                                </button>
                            </form>
                        @endif

                        @unless ($language->isSource())
                            <x-admin.toggle :action="route('languages.toggle', $language)" :active="$language->active" :label="__(':name aktif', ['name' => $language->name])" />
                        @endunless
                    </li>
                @endforeach
            </ul>
        </x-admin.card>

        {{-- İçerik türleri --}}
        <div class="space-y-6">
            <x-admin.card class="overflow-hidden">
                <div class="p-6">
                    <h2 class="text-base font-semibold text-slate-900">{{ __('Çeviri durumu') }}</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ __('Tüm aktif dillerde güncel olan kayıtlar. İngilizcesi değişen kaydın çevirisi "eskimiş" sayılır.') }}</p>
                </div>
                <ul class="divide-y divide-slate-100 border-t border-slate-100">
                    @foreach ($types as $type => $row)
                        @php($percent = $row['total'] ? round($row['ok'] / $row['total'] * 100) : 100)
                        <li class="px-6 py-3.5">
                            <div class="flex items-baseline justify-between text-sm">
                                <span class="font-medium text-slate-900">{{ __($typeNames[$type]) }}</span>
                                <span class="tabular-nums text-slate-500">{{ $row['ok'] }} / {{ $row['total'] }}</span>
                            </div>
                            <div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-100">
                                <div @class(['h-full rounded-full', 'bg-emerald-500' => $percent === 100, 'bg-amber-500' => $percent < 100]) style="width: {{ $percent }}%"></div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </x-admin.card>

            <x-admin.card class="p-6 text-sm text-slate-600">
                <h2 class="text-base font-semibold text-slate-900">{{ __('Nasıl çalışır?') }}</h2>
                <ul class="mt-3 list-disc space-y-1.5 pl-5">
                    <li>{{ __('Çeviri ücretsiz Google Çeviri ile yapılır; anahtar ya da kota yok.') }}</li>
                    <li>{{ __('Paragraflar ayrı çevrilir; kalın yazı, link ve görseller bozulmaz.') }}</li>
                    <li>{{ __('Adres (slug) hiçbir dilde çevrilmez; eski çevrilmiş adresler Yönlendirmeler\'de yeni adrese gider.') }}</li>
                    <li>{{ __('Toplu çeviri arka planda yavaş yavaş yapılır (Google çok hızlı isteği engeller).') }}</li>
                </ul>
            </x-admin.card>
        </div>
    </div>
@endsection
