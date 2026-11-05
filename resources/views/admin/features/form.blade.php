@extends('layouts.admin')

@php
    $editing = $feature->exists;
    $icons = \App\Support\FeatureIcons::ICONS;
    $selectedIcon = old('icon', $feature->icon_path ? 'custom' : $feature->icon);
    $customUrl = $feature->icon_path ? \App\Services\ImageUploader::url($feature->icon_path) : null;
    $field = 'mt-1.5 block w-full rounded-xl border bg-white px-3.5 py-2.5 text-sm outline-none transition placeholder:text-slate-400 focus:border-brand-900 focus:ring-4 focus:ring-brand-900/10';
@endphp

@section('title', $editing ? 'Kartı düzenle' : 'Yeni kart')

@section('header')
    <x-admin.page-header :title="$editing ? 'Kartı düzenle' : 'Yeni kart'" :breadcrumb="['Bölümler' => route('site-settings.index'), 'Özellik kartları' => route('features.index')]"
        :description="__('Bir ikon seç, kısa bir başlık ve bir cümle yaz. Sağda anasayfadaki halini görürsün.')">
        <x-slot:icon><rect x="3" y="5" width="7" height="14" rx="2" /><rect x="14" y="5" width="7" height="14" rx="2" /></x-slot:icon>
    </x-admin.page-header>
@endsection

@section('footer-action')
    <button type="submit" form="feature-form"
        class="inline-flex items-center gap-2 rounded-xl bg-brand-900 px-5 py-2.5 font-semibold text-white shadow-sm transition hover:bg-brand-800">
        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12.5l4.5 4.5L19 7.5" /></svg>
        {{ $editing ? __('Kaydet') : __('Kartı ekle') }}
    </button>
@endsection

@section('content')
    @if ($editing)
        <x-admin.translation-bar type="feature" :model="$feature" />
    @endif

    <form id="feature-form" action="{{ $editing ? route('features.update', $feature) : route('features.store') }}" method="POST" enctype="multipart/form-data" class="grid gap-6 xl:grid-cols-5">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="space-y-6 xl:col-span-3">
            {{-- YAZI --}}
            <x-admin.card class="space-y-5 p-6 sm:p-8">
                <x-admin.step-heading :title="__('Yazı')" :description="__('İngilizce yaz; kaydettikten sonra yukarıdaki Çeviriler\'den diğer dillere çevir.')" />
                <label class="block">
                    <span class="text-sm font-medium text-slate-700">{{ __('Başlık') }} <span class="font-normal text-slate-400">· {{ __('kısa tut, 2-5 kelime') }}</span></span>
                    <input type="text" name="title" required maxlength="60" autocomplete="off" data-preview-input="title" placeholder="Skip-the-line tickets"
                        value="{{ old('title', $feature->title) }}" @class([$field, 'border-slate-200' => !$errors->has('title'), 'border-red-300' => $errors->has('title')])>
                    @error('title') <span class="mt-1.5 block text-xs font-medium text-red-600">{{ $message }}</span> @enderror
                </label>
                <label class="block">
                    <span class="text-sm font-medium text-slate-700">{{ __('Kısa yazı') }} <span class="font-normal text-slate-400">· {{ __('tek cümle') }}</span></span>
                    <textarea name="text" rows="2" maxlength="160" data-preview-input="text" placeholder="Fast-track entry for Hagia Sophia and more."
                        @class([$field, 'resize-none leading-relaxed', 'border-slate-200' => !$errors->has('text'), 'border-red-300' => $errors->has('text')])>{{ old('text', $feature->text) }}</textarea>
                    <span class="mt-1 block text-right text-xs text-slate-400"><span data-count="text">0</span> / 160</span>
                    @error('text') <span class="block text-xs font-medium text-red-600">{{ $message }}</span> @enderror
                </label>
            </x-admin.card>

            {{-- İKON --}}
            <x-admin.card class="p-6 sm:p-8">
                <x-admin.step-heading :title="__('İkon')" :description="__('Kartın konusuna uyan birine tıkla. Aradığın yoksa en sondaki “Kendi ikonun” ile istediğin ikonu yükle.')" />
                <div class="mt-5 grid grid-cols-4 gap-2 sm:grid-cols-6 lg:grid-cols-8">
                    @foreach ($icons as $key => [$label])
                        <label class="group flex cursor-pointer flex-col items-center gap-1.5 rounded-2xl p-3 text-center ring-1 ring-slate-200 transition hover:bg-slate-50 has-[:checked]:bg-rose-50 has-[:checked]:ring-2 has-[:checked]:ring-rose-500" title="{{ __($label) }}">
                            <input type="radio" name="icon" value="{{ $key }}" class="sr-only" data-icon-option @checked($selectedIcon === $key)>
                            <span class="text-slate-500 group-has-[:checked]:text-rose-600">{!! \App\Support\FeatureIcons::svg($key) !!}</span>
                            <span class="w-full truncate text-[10px] font-medium text-slate-500">{{ __($label) }}</span>
                            <template data-icon-svg>{!! \App\Support\FeatureIcons::svg($key, 'size-6') !!}</template>
                        </label>
                    @endforeach

                    {{-- KENDİ İKONUN --}}
                    <label for="icon-upload" data-custom-tile class="group col-span-2 flex cursor-pointer items-center gap-3 rounded-2xl border-2 border-dashed border-slate-300 p-3 transition hover:border-rose-400 hover:bg-rose-50/40 has-[:checked]:border-solid has-[:checked]:border-rose-500 has-[:checked]:bg-rose-50">
                        <input type="radio" name="icon" value="custom" class="sr-only" data-icon-option data-custom-radio @checked($selectedIcon === 'custom')>
                        <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-white text-slate-500 ring-1 ring-slate-200 group-has-[:checked]:text-rose-600" data-custom-thumb>
                            @if ($customUrl)
                                <span class="inline-block size-6" style="background-color: currentColor; -webkit-mask: url('{{ $customUrl }}') center / contain no-repeat; mask: url('{{ $customUrl }}') center / contain no-repeat"></span>
                            @else
                                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 16V4M7 9l5-5 5 5M4 20h16" /></svg>
                            @endif
                        </span>
                        <span class="min-w-0 text-left">
                            <span class="block text-xs font-semibold text-slate-800">{{ __('Kendi ikonun') }}</span>
                            <span class="block text-[10px] leading-tight text-slate-500" data-custom-label>{{ $customUrl ? __('Yüklü · değiştirmek için tıkla') : __('SVG ya da PNG yükle') }}</span>
                        </span>
                        <input type="file" id="icon-upload" name="icon_upload" accept=".svg,image/svg+xml,image/png,image/webp" class="sr-only" data-no-compress data-custom-file>
                    </label>
                </div>

                <div class="mt-4 rounded-2xl bg-slate-50 p-4 text-xs leading-relaxed text-slate-600 ring-1 ring-slate-200">
                    <p class="font-semibold text-slate-800">{{ __('Kendi ikonunu nereden bulurum?') }}</p>
                    <p class="mt-1">
                        {!! __('Ücretsiz ikon siteleri: :lucide, :tabler, :phosphor. Beğendiğin ikonda "SVG indir"e bas, buraya yükle.', [
                            'lucide' => '<a href="https://lucide.dev/icons" target="_blank" rel="noopener" class="font-semibold text-brand-900 underline">lucide.dev</a>',
                            'tabler' => '<a href="https://tabler.io/icons" target="_blank" rel="noopener" class="font-semibold text-brand-900 underline">tabler.io/icons</a>',
                            'phosphor' => '<a href="https://phosphoricons.com" target="_blank" rel="noopener" class="font-semibold text-brand-900 underline">phosphoricons.com</a>',
                        ]) !!}
                    </p>
                    <p class="mt-1">{{ __('Rengi dert etme: sitede diğer ikonlar gibi otomatik beyaz olur. PNG ise arka planı şeffaf olmalı (yoksa kare görünür). En fazla 1 MB.') }}</p>
                </div>
                @error('icon') <p class="mt-2 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                @error('icon_upload') <p class="mt-2 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
            </x-admin.card>

            {{-- YAYIN --}}
            <x-admin.card class="p-6 sm:p-8">
                <x-admin.step-heading :title="__('Yayın')" :description="__('Kart anasayfada görünsün mü?')" />
                <div class="mt-4"><x-admin.status-picker :active="$feature->active" /></div>
            </x-admin.card>
        </div>

        {{-- ÖNİZLEME --}}
        <div class="xl:col-span-2">
            <div class="xl:sticky xl:top-20">
                <x-admin.card class="p-6">
                    <p class="text-sm font-semibold text-slate-900">{{ __('Anasayfadaki hali') }}</p>
                    <div class="mt-4 rounded-2xl bg-slate-50 p-5">
                        <div class="w-64 rounded-3xl bg-white p-5 ring-1 ring-slate-200/80 shadow-[0_10px_30px_-18px_rgba(15,23,42,0.35)]">
                            <span class="grid size-11 place-items-center rounded-2xl bg-gradient-to-br from-rose-500 to-red-700 text-white shadow-lg shadow-rose-500/25" data-preview-icon>
                                {!! $feature->exists ? $feature->iconHtml() : \App\Support\FeatureIcons::svg($selectedIcon) !!}
                            </span>
                            <p class="mt-4 text-[15px] font-bold leading-snug text-slate-900" data-preview="title"></p>
                            <p class="mt-1.5 text-sm leading-relaxed text-slate-500" data-preview="text"></p>
                        </div>
                    </div>
                </x-admin.card>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        (() => {
            const form = document.getElementById('feature-form');
            form.querySelectorAll('[data-preview-input]').forEach(input => {
                const key = input.dataset.previewInput;
                const target = form.querySelector(`[data-preview="${key}"]`);
                const count = form.querySelector(`[data-count="${key}"]`);
                const update = () => { target.textContent = input.value || input.placeholder; if (count) count.textContent = input.value.length; };
                input.addEventListener('input', update);
                update();
            });
            const icon = form.querySelector('[data-preview-icon]');
            const maskSpan = (url) => `<span class="inline-block size-6" style="background-color: currentColor; -webkit-mask: url('${url}') center / contain no-repeat; mask: url('${url}') center / contain no-repeat"></span>`;
            let customHtml = form.querySelector('[data-custom-thumb]').innerHTML;
            form.querySelectorAll('[data-icon-option]').forEach(radio => radio.addEventListener('change', () => {
                const template = radio.closest('label').querySelector('template[data-icon-svg]');
                icon.innerHTML = template ? template.innerHTML : customHtml;
            }));
            // Dosya seçilince: kutuda ve önizlemede göster, "Kendi ikonun" seçili olsun
            const file = form.querySelector('[data-custom-file]');
            // Kutuya tıklayınca hem "Kendi ikonun" seçilir hem dosya penceresi açılır (label for=icon-upload)
            form.querySelector('[data-custom-tile]').addEventListener('click', () => {
                const radio = form.querySelector('[data-custom-radio]');
                radio.checked = true;
                radio.dispatchEvent(new Event('change'));
            });
            file.addEventListener('change', () => {
                if (!file.files[0]) return;
                customHtml = maskSpan(URL.createObjectURL(file.files[0]));
                form.querySelector('[data-custom-thumb]').innerHTML = customHtml;
                form.querySelector('[data-custom-label]').textContent = file.files[0].name;
                const radio = form.querySelector('[data-custom-radio]');
                radio.checked = true;
                icon.innerHTML = customHtml;
            });
        })();
    </script>
@endpush
