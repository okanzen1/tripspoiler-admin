@extends('layouts.admin')

@section('title', $page->name . ' · ' . __('Bölümler'))

@php
    $content = \App\Support\PageSections::labels($page->key);
    $guideTopics = $page->key === 'cities' ? \App\Models\CityGuideTopic::all()->keyBy('id') : collect();
    $fixed = \App\Support\PageSections::FIXED;
    $on = $config['on'];
    // Bölümler sayfasında tüm sitede kapatılanlar (burada açık olsa da görünmez)
    $globalOff = fn ($key) => in_array($key, \App\Support\SiteSections::KEYS, true) && !\App\Support\SiteSections::enabled($key);
    $switch = fn ($active, $label) => '<button type="button" role="switch" data-section-switch aria-checked="' . ($active ? 'true' : 'false') . '" aria-label="' . e($label) . '"
        class="group relative inline-flex h-6 w-11 shrink-0 items-center rounded-full bg-slate-200 transition-colors aria-checked:bg-emerald-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-500">
        <span class="inline-block size-5 translate-x-0.5 rounded-full bg-white shadow-sm ring-1 ring-black/5 transition-transform group-aria-checked:translate-x-[22px]"></span></button>';
@endphp

@section('header')
    <x-admin.page-header :title="__('Bölümler')" :description="__(':name sayfasının altındaki bölümler. Soldaki tutamaçtan sürükleyerek sırala, sağdaki anahtarla aç/kapa. Değişiklik hemen kaydedilir.', ['name' => $page->name])"
        :breadcrumb="['Yönetim' => route('management.index'), 'Sayfalar' => route('pages.index'), $page->name => route('pages.edit', $page)]">
        <x-slot:icon><rect x="4" y="4" width="16" height="5" rx="1.5" /><rect x="4" y="11" width="16" height="4" rx="1.5" /><rect x="4" y="17" width="16" height="3" rx="1.5" /></x-slot:icon>
        <a href="{{ $page->siteUrl() }}" target="_blank" rel="noopener"
            class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-700 ring-1 ring-slate-200 transition hover:bg-white">{{ __('Sayfayı aç') }} ↗</a>
    </x-admin.page-header>
@endsection

@section('content')
    <div class="space-y-6" data-page-sections-root>
        @if (in_array($page->key, \App\Support\PageSections::NO_CITY, true))
            {{-- şehirle ilgisi olmayan sayfa: bilgi kutusu yok --}}
        @elseif ($page->key === 'activity')
            <div class="rounded-2xl bg-sky-50 px-5 py-4 text-sm text-sky-950 ring-1 ring-sky-200">
                {{ __('Bu düzen tüm aktivite sayfalarında aynıdır. Bölümler o aktiviteye göre dolar: kendi yorumları, kendi soruları, aynı şehirdeki benzerleri. İçeriği olmayan bölüm görünmez.') }}
            </div>
        @elseif ($page->key === 'post')
            <div class="rounded-2xl bg-sky-50 px-5 py-4 text-sm text-sky-950 ring-1 ring-sky-200">
                {{ __('Bu düzen tüm blog yazılarında aynıdır. Bölümler o yazıya göre dolar: kendi soruları, yazının şehrindeki aktiviteler. İçeriği olmayan bölüm görünmez. En alttaki “Diğer rehberler” her zaman yazının sonunda kalır.') }}
            </div>
        @else
        <div class="flex items-start gap-3 rounded-2xl bg-sky-50 px-5 py-4 text-sm text-sky-950 ring-1 ring-sky-200">
            <span class="mt-0.5 shrink-0 whitespace-nowrap rounded-md bg-sky-600 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-white">{{ __('Şehre özel') }}</span>
            <p>{{ __('Bu etiketli bölümler ziyaretçinin seçtiği şehre göre dolar (ör. Atina seçiliyse Atina yazıları). O şehirde içerik yoksa bölüm kendiliğinden gizlenir, boş görünmez.') }}</p>
        </div>
        @endif

        {{-- SIRALANABİLİR --}}
        <x-admin.card class="overflow-hidden">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-6 py-4">
                <p class="font-semibold text-slate-900">{{ isset(\App\Support\PageSections::MAIN[$page->key]) ? __('Sayfadaki sıra, yukarıdan aşağıya (banner\'ın altından başlar)') : __('Sayfa içeriğinin altında, yukarıdan aşağıya') }}</p>
                @if ($page->key === 'cities')
                    {{-- şehir rehberi alanları da bu listede; yenisi eklenince buraya gelir --}}
                    <a href="{{ route('city-guide-topics.create') }}" class="inline-flex items-center gap-1.5 rounded-xl px-3.5 py-2 text-sm font-semibold text-brand-900 ring-1 ring-slate-200 transition hover:bg-slate-50">
                        <x-admin.icon name="plus" class="size-4" /> {{ __('Yeni rehber alanı') }}
                    </a>
                @endif
            </div>
            <ul class="divide-y divide-slate-100" data-page-sections data-url="{{ route('pages.sections.update', $page) }}">
                @foreach ($config['order'] as $key)
                    @php
                        $isMain = $key === 'main';
                        $guideTopic = str_starts_with($key, 'guide:') ? ($guideTopics[(int) substr($key, 6)] ?? null) : null;
                        [$label, $description, $citySpecific] = $isMain ? [...\App\Support\PageSections::MAIN[$page->key], false] : $content[$key];
                    @endphp
                    <li data-section="{{ $key }}" @class(['flex items-center gap-4 px-4 py-4 transition sm:px-6', 'bg-brand-50/60' => $isMain, 'bg-white' => !$isMain, 'opacity-60' => !$isMain && !in_array($key, $on, true)])>
                        <span data-drag-handle draggable="true" tabindex="0" title="{{ __('Sürükleyerek sırala (klavyede ↑ ↓)') }}"
                            class="grid h-10 w-8 shrink-0 cursor-grab place-items-center rounded-lg text-slate-400 ring-1 ring-slate-200 transition hover:bg-slate-50 hover:text-slate-700 focus-visible:outline-2 focus-visible:outline-brand-900 active:cursor-grabbing">
                            <svg class="size-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="9" cy="6" r="1.5"/><circle cx="15" cy="6" r="1.5"/><circle cx="9" cy="12" r="1.5"/><circle cx="15" cy="12" r="1.5"/><circle cx="9" cy="18" r="1.5"/><circle cx="15" cy="18" r="1.5"/></svg>
                        </span>
                        <span data-position class="w-5 shrink-0 text-center text-sm font-semibold text-slate-400">{{ $loop->iteration }}</span>
                        @if ($guideTopic)
                            <span class="grid size-9 shrink-0 place-items-center rounded-xl bg-brand-50 text-brand-900">{!! \App\Support\FeatureIcons::svg($guideTopic->icon, 'size-[18px]') !!}</span>
                        @endif
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="font-semibold text-slate-900">{{ __($label) }}</p>
                                @if ($isMain)
                                    <span class="rounded-md bg-brand-900 px-1.5 py-0.5 text-[11px] font-semibold text-white">{{ __('Ana içerik') }}</span>
                                @endif
                                @if ($guideTopic)
                                    <span class="rounded-md bg-violet-50 px-1.5 py-0.5 text-[11px] font-semibold text-violet-700 ring-1 ring-violet-200">{{ __('Şehir rehberi') }}</span>
                                @endif
                                @if ($citySpecific)
                                    <span class="rounded-md bg-sky-50 px-1.5 py-0.5 text-[11px] font-semibold text-sky-700 ring-1 ring-sky-200">{{ __('Şehre özel') }}</span>
                                @endif
                                @if ($globalOff($key))
                                    <a href="{{ route('site-settings.index') }}" class="rounded-md bg-amber-50 px-1.5 py-0.5 text-[11px] font-semibold text-amber-800 ring-1 ring-amber-200 hover:bg-amber-100">{{ __('Tüm sitede kapalı') }} →</a>
                                @endif
                            </div>
                            <p class="mt-0.5 text-sm text-slate-500">{{ __($description) }}</p>
                        </div>
                        @if ($guideTopic)
                            <a href="{{ route('city-guide-topics.edit', $guideTopic) }}" title="{{ __('Alanın adı ve ikonu') }}" aria-label="{{ __('Düzenle') }}"
                                class="grid size-9 shrink-0 place-items-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-brand-900">
                                <x-admin.icon name="pencil" class="size-[18px]" />
                            </a>
                        @endif
                        @if ($isMain)
                            <span class="shrink-0 text-xs font-semibold text-slate-500">{{ __('Her zaman açık') }}</span>
                        @else
                            {!! $switch(in_array($key, $on, true), __($label)) !!}
                        @endif
                    </li>
                @endforeach
            </ul>
        </x-admin.card>

        {{-- SABİT YERLER --}}
        <x-admin.card class="overflow-hidden">
            <div class="border-b border-slate-100 px-6 py-4">
                <p class="font-semibold text-slate-900">{{ __('Sabit yerdekiler') }} <span class="font-normal text-slate-500">— {{ __('yerleri değişmez, sadece aç/kapa') }}</span></p>
            </div>
            <ul class="divide-y divide-slate-100">
                @foreach ($fixed as $key => [$label, $description])
                    <li data-section="{{ $key }}" @class(['flex items-center gap-4 px-4 py-4 transition sm:px-6', 'opacity-60' => !in_array($key, $on, true)])>
                        <div class="min-w-0 flex-1 sm:pl-[4.25rem]">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="font-semibold text-slate-900">{{ __($label) }}</p>
                                @if ($globalOff($key))
                                    <a href="{{ route('site-settings.index') }}" class="rounded-md bg-amber-50 px-1.5 py-0.5 text-[11px] font-semibold text-amber-800 ring-1 ring-amber-200 hover:bg-amber-100">{{ __('Tüm sitede kapalı') }} →</a>
                                @endif
                            </div>
                            <p class="mt-0.5 text-sm text-slate-500">{{ __($description) }}</p>
                        </div>
                        {!! $switch(in_array($key, $on, true), __($label)) !!}
                    </li>
                @endforeach
            </ul>
        </x-admin.card>
    </div>
@endsection
