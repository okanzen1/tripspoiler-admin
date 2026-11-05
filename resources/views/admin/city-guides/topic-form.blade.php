@extends('layouts.admin')

@php
    $editing = $topic->exists;
    $icons = \App\Support\FeatureIcons::ICONS;
    $selectedIcon = old('icon', $topic->icon);
    $field = 'mt-1.5 block w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm outline-none transition placeholder:text-slate-400 focus:border-brand-900 focus:ring-4 focus:ring-brand-900/10';
    $title = $editing ? __('Alanı düzenle') : __('Yeni alan');
@endphp

@section('title', $title)

@section('header')
    <x-admin.page-header :title="$title" :breadcrumb="['Şehir rehberi' => route('city-guides.index'), 'Şehirler › Bölümler' => \App\Http\Controllers\CityGuideTopicController::back()]"
        :description="__('Alan bütün şehirlerin rehberine eklenir; her şehir kendi başlığını ve yazısını yazar. Sırası Sayfalar › Şehirler › Bölümler listesinde.')">
        <x-slot:icon><rect x="4" y="4" width="16" height="5" rx="1.5" /><rect x="4" y="11" width="16" height="4" rx="1.5" /><rect x="4" y="17" width="16" height="3" rx="1.5" /></x-slot:icon>
        @if ($editing)
            <form action="{{ route('city-guide-topics.destroy', $topic) }}" method="POST" data-confirm="{{ __('Bu alan silinsin mi? Bütün şehirlerdeki bu alana ait yazılar da silinir.') }}">
                @csrf
                @method('DELETE')
                <button type="submit" class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-red-600 ring-1 ring-red-200 transition hover:bg-red-50">
                    <x-admin.icon name="trash" class="size-4" /> {{ __('Alanı sil') }}
                </button>
            </form>
        @endif
    </x-admin.page-header>
@endsection

@section('footer-action')
    <button type="submit" form="topic-form" class="inline-flex items-center gap-2 rounded-xl bg-brand-900 px-5 py-2.5 font-semibold text-white shadow-sm transition hover:bg-brand-800">
        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12.5l4.5 4.5L19 7.5" /></svg>
        {{ $editing ? __('Kaydet') : __('Alanı ekle') }}
    </button>
@endsection

@section('content')
    @if ($editing)
        <x-admin.translation-bar type="city-guide-topic" :model="$topic" />
    @endif

    <form id="topic-form" action="{{ $editing ? route('city-guide-topics.update', $topic) : route('city-guide-topics.store') }}" method="POST" class="space-y-6">
        @csrf
        @if ($editing) @method('PUT') @endif

        <x-admin.card class="space-y-5 p-6 sm:p-8">
            <x-admin.step-heading :title="__('Ad')" :description="__('Sitede alanın kısa adı (menüde ve etikette). İngilizce yaz: “Nightlife”, “With kids”…')" />
            <label class="block">
                <span class="text-sm font-medium text-slate-700">{{ __('Alan adı (İngilizce)') }}</span>
                <input type="text" name="name" required maxlength="40" autocomplete="off" value="{{ old('name', $topic->getTranslation('name', 'en', false)) }}" placeholder="Nightlife" class="{{ $field }}">
                @error('name') <span class="mt-1 block text-xs font-medium text-red-600">{{ $message }}</span> @enderror
            </label>
            <label class="block">
                <span class="text-sm font-medium text-slate-700">{{ __('Örnek başlık') }} <span class="font-normal text-slate-400">· {{ __('sadece panelde, yazarken fikir versin diye') }}</span></span>
                <input type="text" name="example" maxlength="160" autocomplete="off" value="{{ old('example', $topic->example) }}" placeholder="{{ __('Örn: İstanbul\'da gece nereye gidilir?') }}" class="{{ $field }}">
            </label>
        </x-admin.card>

        <x-admin.card class="p-6 sm:p-8">
            <x-admin.step-heading :title="__('İkon')" :description="__('Sitede alanın yanında görünür.')" />
            <div class="mt-5 grid grid-cols-4 gap-2 sm:grid-cols-6 lg:grid-cols-9">
                @foreach ($icons as $key => [$label])
                    <label class="flex cursor-pointer flex-col items-center gap-1.5 rounded-2xl p-3 text-center ring-1 ring-slate-200 transition hover:bg-slate-50 has-[:checked]:bg-brand-50 has-[:checked]:ring-2 has-[:checked]:ring-brand-900" title="{{ __($label) }}">
                        <input type="radio" name="icon" value="{{ $key }}" class="sr-only" @checked($selectedIcon === $key)>
                        <span class="text-slate-700">{!! \App\Support\FeatureIcons::svg($key, 'size-6') !!}</span>
                        <span class="line-clamp-1 text-[11px] text-slate-500">{{ __($label) }}</span>
                    </label>
                @endforeach
            </div>
            @error('icon') <p class="mt-2 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
        </x-admin.card>
    </form>
@endsection
