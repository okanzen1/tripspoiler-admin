@extends('layouts.admin')

@section('title', __('Şehir rehberi'))

@php
    $flag = fn ($code) => $code ? implode('', array_map(fn ($c) => mb_chr(0x1F1E6 + ord($c) - 65), str_split(strtoupper($code)))) : '';
@endphp

@section('header')
    <x-admin.page-header :title="__('Şehir rehberi')" :description="__('Şehir sayfalarındaki rehber yazıları: gezilecek yerler, yeme içme, ulaşım… Bir şehir seç, alanlarını yaz ve sırala.')">
        <x-slot:icon><path d="M9 4 3.5 6v14L9 18l6 2 5.5-2V4L15 6z" /><path d="M9 4v14M15 6v14" /></x-slot:icon>
        @if (auth()->user()->isAdmin())
            <a href="{{ \App\Http\Controllers\CityGuideTopicController::back() }}"
                class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-700 ring-1 ring-slate-200 transition hover:bg-white">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="4" width="16" height="5" rx="1.5" /><rect x="4" y="11" width="16" height="4" rx="1.5" /><rect x="4" y="17" width="16" height="3" rx="1.5" /></svg>
                {{ __('Alanların sırası') }}
            </a>
        @endif
        <a href="{{ route('city-guide-topics.create') }}"
            class="inline-flex items-center gap-2 rounded-xl bg-brand-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-800">
            <x-admin.icon name="plus" /> {{ __('Yeni alan') }}
        </a>
    </x-admin.page-header>
@endsection

@section('content')
    @if ($cities->isEmpty())
        <x-admin.card class="px-6 py-16 text-center">
            <p class="text-sm font-semibold text-slate-900">{{ __('Henüz yayında şehir yok') }}</p>
            <p class="mt-1 text-sm text-slate-500">{{ __('Yönetim › Şehirler\'den bir şehri yayına al.') }}</p>
        </x-admin.card>
    @else
        <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($cities as $city)
                @php
                    $percent = $topicCount ? min(100, round($city->live_count / $topicCount * 100)) : 0;
                @endphp
                <a href="{{ route('city-guides.show', $city) }}" class="group block focus-visible:outline-none">
                    <x-admin.card class="flex h-full flex-col p-6 transition group-hover:-translate-y-1 group-hover:shadow-xl">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <span class="grid size-12 place-items-center rounded-2xl bg-slate-100 text-2xl leading-none">{{ $flag($city->country?->code) ?: '📍' }}</span>
                                <div>
                                    <p class="text-lg font-semibold text-slate-900">{{ $city->name }}</p>
                                    <p class="text-sm text-slate-500">{{ $city->country?->name }} · {{ __(':n aktivite', ['n' => $city->activities_count]) }}</p>
                                    @if ($cityTodo = \App\Support\TranslationTodo::under(route('city-guides.show', $city)))
                                        <span class="mt-1 inline-flex items-center gap-1 rounded-full bg-purple-50 px-2 py-0.5 text-[11px] font-semibold text-purple-700 ring-1 ring-purple-200">{{ __(':count çeviri eksik', ['count' => $cityTodo]) }}</span>
                                    @endif
                                </div>
                            </div>
                            <span class="text-sm font-semibold text-brand-900 transition group-hover:translate-x-1">{{ __('Aç') }} →</span>
                        </div>
                        <div class="mt-6">
                            <div class="flex items-center justify-between text-xs font-medium">
                                <span class="text-slate-500">{{ __('Yayındaki alanlar') }}</span>
                                <span @class(['font-semibold', 'text-emerald-700' => $city->live_count, 'text-slate-400' => !$city->live_count])>{{ $city->live_count }} / {{ $topicCount }}</span>
                            </div>
                            <div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full rounded-full bg-emerald-500 transition-all" style="width: {{ $percent }}%"></div>
                            </div>
                        </div>
                    </x-admin.card>
                </a>
            @endforeach
        </div>
    @endif
@endsection
