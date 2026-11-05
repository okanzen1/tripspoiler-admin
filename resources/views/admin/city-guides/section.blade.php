@extends('layouts.admin')

@php
    $topic = $section->topic;
    $topicName = $topic->getTranslation('name', 'tr', false) ?: $topic->name;
    $field = 'mt-1.5 block w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm outline-none transition placeholder:text-slate-400 focus:border-brand-900 focus:ring-4 focus:ring-brand-900/10';
    $imageUrl = \App\Services\ImageUploader::url($section->image_path, 'sm');
@endphp

@section('title', $city->name . ' · ' . $topicName)

@section('header')
    <x-admin.page-header :title="$topicName" :breadcrumb="['Şehir rehberi' => route('city-guides.index'), $city->name => route('city-guides.show', $city)]"
        :description="__(':city şehrinin “:topic” alanı. Başlığı ve yazıyı İngilizce yaz; kaydettikten sonra Çeviriler\'den 12 dile çevir.', ['city' => $city->name, 'topic' => $topicName])">
        <x-slot:icon>{!! preg_replace('#^<svg[^>]*>|</svg>$#', '', \App\Support\FeatureIcons::svg($topic->icon)) !!}</x-slot:icon>
    </x-admin.page-header>
@endsection

@section('footer-action')
    <button type="submit" form="guide-section"
        class="inline-flex items-center gap-2 rounded-xl bg-brand-900 px-5 py-2.5 font-semibold text-white shadow-sm transition hover:bg-brand-800 focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-brand-900/25">
        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12.5l4.5 4.5L19 7.5" /></svg>
        {{ __('Kaydet') }}
    </button>
@endsection

@section('content')
    @if ($section->isWritten())
        <x-admin.translation-bar type="city-guide-section" :model="$section" />
    @endif

    <form id="guide-section" action="{{ route('city-guides.sections.update', [$city, $section]) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

        <x-admin.card class="space-y-5 p-6 sm:p-8">
            <x-admin.step-heading :title="__('Başlık')" :description="__('Sitede bu alanın büyük başlığı. Soru gibi yazmak iyi gider (Google\'da aranan cümle).')" />
            <label class="block">
                <span class="sr-only">{{ __('Başlık') }}</span>
                <input type="text" name="title" required maxlength="160" autocomplete="off" value="{{ old('title', $section->getTranslation('title', 'en', false)) }}"
                    placeholder="{{ $topic->example ?: __('Başlık') }}" class="{{ $field }} text-base font-semibold">
                @error('title') <span class="mt-1 block text-xs font-medium text-red-600">{{ $message }}</span> @enderror
            </label>
            <label class="flex items-center gap-3 text-sm text-slate-700">
                <input type="hidden" name="active" value="0">
                <input type="checkbox" name="active" value="1" class="size-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500" @checked(old('active', $section->exists ? $section->active : true) || !$section->isWritten())>
                {{ __('Sitede göster') }} <span class="text-slate-400">· {{ __('kapalıysa yazı saklanır ama görünmez') }}</span>
            </label>
        </x-admin.card>

        {{-- YAZI --}}
        <x-admin.card class="space-y-4 p-6 sm:p-8">
            <x-admin.step-heading :title="__('Yazı')" :description="__('Word\'de hazırladığını kopyalayıp direkt yapıştırabilirsin: başlıklar, kalın yazılar, listeler ve resimler gelir.')" />
            <ul class="grid gap-2 rounded-2xl bg-sky-50 p-4 text-sm text-sky-950 ring-1 ring-sky-200 sm:grid-cols-2">
                <li class="flex gap-2"><span aria-hidden="true">📋</span>{{ __('Word\'den yapıştır: fazla biçimler temizlenir, başlık ve listeler kalır.') }}</li>
                <li class="flex gap-2"><span aria-hidden="true">🖼️</span>{{ __('Resimler otomatik küçültülür ve WebP\'ye çevrilir (genişlik en fazla 1200 px).') }}</li>
                <li class="flex gap-2"><span aria-hidden="true">↔️</span>{{ __('Resme tıkla: Küçük / Orta / Tam boyut seç, sayfada fazla yer kaplamasın.') }}</li>
                <li class="flex gap-2"><span aria-hidden="true">✋</span>{{ __('Resmi sürükleyip bırakabilir ya da araç çubuğundaki resim düğmesini kullanabilirsin.') }}</li>
            </ul>
            <input type="hidden" name="content" value="{{ old('content', $section->getTranslation('content', 'en', false)) }}">
            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white" data-rich-editor="content" data-word-paste data-image-sizes
                data-upload-url="{{ route('editor-images.store') }}" data-media-url="{{ \App\Services\ImageUploader::baseUrl() }}"
                data-placeholder="{{ __('Yaz ya da Word\'den yapıştır…') }}">
                <div class="min-h-[420px] text-[15px]"></div>
            </div>
            @error('content') <p class="text-xs font-medium text-red-600">{{ $message }}</p> @enderror
        </x-admin.card>

        {{-- KAPAK --}}
        <x-admin.card class="p-6 sm:p-8">
            <x-admin.step-heading :title="__('Kapak resmi (isteğe bağlı)')" :description="__('Alanın başında büyük resim. Yatay bir fotoğraf: önerilen 1600 × 900 piksel. Otomatik küçültülür ve WebP\'ye çevrilir.')" />
            <div class="mt-5 flex flex-col gap-4 sm:flex-row sm:items-end" data-cover>
                <div class="relative aspect-[16/9] w-full shrink-0 overflow-hidden rounded-xl bg-slate-100 ring-1 ring-slate-200 sm:w-80">
                    <img src="{{ $imageUrl }}" alt="" data-cover-preview @class(['absolute inset-0 size-full object-cover', 'hidden' => !$imageUrl])>
                    <span data-cover-empty @class(['absolute inset-0 grid place-items-center text-sm text-slate-400', 'hidden' => $imageUrl])>{{ __('Resim yok') }}</span>
                </div>
                <div class="space-y-2">
                    <div class="flex flex-wrap items-center gap-2">
                        <label class="inline-flex cursor-pointer items-center gap-2 rounded-xl bg-brand-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-800">
                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 16V4M7 9l5-5 5 5M4 20h16" /></svg>
                            {{ $section->image_path ? __('Resmi değiştir') : __('Resim seç') }}
                            <input type="file" name="image" accept="image/jpeg,image/png,image/webp" class="sr-only" data-cover-input>
                        </label>
                        @if ($section->image_path)
                            <label class="inline-flex cursor-pointer items-center gap-2 rounded-xl px-3 py-2 text-sm font-medium text-slate-500 hover:bg-red-50 hover:text-red-600">
                                <input type="checkbox" name="remove_image" value="1" class="rounded border-slate-300 text-red-600 focus:ring-red-500">
                                {{ __('Kaldır') }}
                            </label>
                        @endif
                    </div>
                    <p class="text-xs text-slate-500">{{ __('JPG, PNG ya da WebP · en fazla :mb MB', ['mb' => $maxMb]) }}</p>
                    @error('image') <p class="text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </x-admin.card>
    </form>

    <script>
        // Kapak önizlemesi (kaydetmeden)
        document.querySelector('[data-cover-input]')?.addEventListener('change', (event) => {
            const file = event.target.files?.[0];
            if (!file) return;
            const box = event.target.closest('[data-cover]');
            const img = box.querySelector('[data-cover-preview]');
            img.src = URL.createObjectURL(file);
            img.classList.remove('hidden');
            box.querySelector('[data-cover-empty]')?.classList.add('hidden');
        });
    </script>
@endsection
