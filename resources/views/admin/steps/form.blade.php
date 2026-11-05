@extends('layouts.admin')

@php
    $editing = $step->exists;
    $links = \App\Http\Controllers\StepController::LINKS;
    $linkChoice = old('link_choice', array_key_exists((string) $step->link, $links) ? $step->link : 'custom');
    $selectedIcon = old('icon', $step->icon);
    $field = 'mt-1.5 block w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm outline-none transition placeholder:text-slate-400 focus:border-brand-900 focus:ring-4 focus:ring-brand-900/10';
@endphp

@section('title', $editing ? 'Adımı düzenle' : 'Yeni adım')

@section('header')
    <x-admin.page-header :title="$editing ? 'Adımı düzenle' : 'Yeni adım'" :breadcrumb="['Bölümler' => route('site-settings.index'), 'Nasıl çalışır' => route('steps.index')]"
        :description="__('İkon, başlık, kısa yazı ve kartın altındaki bağlantı. Sağda anasayfadaki halini görürsün.')">
        <x-slot:icon><circle cx="5" cy="12" r="2" /><circle cx="12" cy="12" r="2" /><circle cx="19" cy="12" r="2" /><path d="M7 12h3M14 12h3" /></x-slot:icon>
    </x-admin.page-header>
@endsection

@section('footer-action')
    <button type="submit" form="step-form" class="inline-flex items-center gap-2 rounded-xl bg-brand-900 px-5 py-2.5 font-semibold text-white shadow-sm transition hover:bg-brand-800">
        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12.5l4.5 4.5L19 7.5" /></svg>
        {{ $editing ? __('Kaydet') : __('Adımı ekle') }}
    </button>
@endsection

@section('content')
    @if ($editing)
        <x-admin.translation-bar type="step" :model="$step" />
    @endif

    <form id="step-form" action="{{ $editing ? route('steps.update', $step) : route('steps.store') }}" method="POST" class="grid gap-6 xl:grid-cols-5">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="space-y-6 xl:col-span-3">
            <x-admin.card class="space-y-5 p-6 sm:p-8">
                <x-admin.step-heading :title="__('Yazı')" :description="__('İngilizce yaz; kaydettikten sonra yukarıdaki Çeviriler\'den diğer dillere çevir.')" />
                <label class="block">
                    <span class="text-sm font-medium text-slate-700">{{ __('Başlık') }} <span class="font-normal text-slate-400">· {{ __('2-4 kelime, emir kipi iyi gider: “Rehberi oku”') }}</span></span>
                    <input type="text" name="title" required maxlength="50" autocomplete="off" placeholder="Read the guide" data-preview-input="title" value="{{ old('title', $step->title) }}" class="{{ $field }}">
                    @error('title') <span class="mt-1 block text-xs font-medium text-red-600">{{ $message }}</span> @enderror
                </label>
                <label class="block">
                    <span class="text-sm font-medium text-slate-700">{{ __('Kısa yazı') }} <span class="font-normal text-slate-400">· {{ __('tek cümle') }}</span></span>
                    <textarea name="text" rows="2" maxlength="180" placeholder="Learn what each sight is about…" data-preview-input="text" class="{{ $field }} resize-none leading-relaxed">{{ old('text', $step->text) }}</textarea>
                    @error('text') <span class="mt-1 block text-xs font-medium text-red-600">{{ $message }}</span> @enderror
                </label>
            </x-admin.card>

            <x-admin.card class="space-y-5 p-6 sm:p-8">
                <x-admin.step-heading :title="__('Bağlantı')" :description="__('Kartın altındaki kırmızı yazı ve tıklayınca gideceği yer. Yazıyı boş bırakırsan bağlantı gösterilmez.')" />
                <label class="block">
                    <span class="text-sm font-medium text-slate-700">{{ __('Bağlantı yazısı') }}</span>
                    <input type="text" name="cta" maxlength="40" autocomplete="off" placeholder="Browse guides" data-preview-input="cta" value="{{ old('cta', $step->cta) }}" class="{{ $field }}">
                </label>
                <label class="block">
                    <span class="text-sm font-medium text-slate-700">{{ __('Nereye gitsin?') }}</span>
                    <select name="link_choice" class="{{ $field }}" data-link-choice>
                        @foreach ($links as $path => $label)
                            <option value="{{ $path }}" @selected($linkChoice === $path)>{{ __($label) }}</option>
                        @endforeach
                        <option value="custom" @selected($linkChoice === 'custom')>{{ __('Başka bir adres…') }}</option>
                    </select>
                </label>
                <label @class(['block', 'hidden' => $linkChoice !== 'custom']) data-link-custom>
                    <span class="text-sm font-medium text-slate-700">{{ __('Adres') }}</span>
                    <input type="text" name="link_custom" maxlength="500" placeholder="/cities/istanbul" value="{{ old('link_custom', $linkChoice === 'custom' ? $step->link : '') }}" class="{{ $field }}">
                    <span class="mt-1 block text-xs text-slate-500">{{ __('Site içi adres / ile başlar (ör. /cities/istanbul); dil öneki otomatik eklenir. Tam adres https:// ile başlar.') }}</span>
                    @error('link_custom') <span class="mt-1 block text-xs font-medium text-red-600">{{ $message }}</span> @enderror
                </label>
            </x-admin.card>

            <x-admin.card class="p-6 sm:p-8">
                <x-admin.step-heading :title="__('İkon')" :description="__('Adımın konusuna uyan birine tıkla.')" />
                <div class="mt-5 grid grid-cols-4 gap-2 sm:grid-cols-6 lg:grid-cols-8">
                    @foreach (\App\Support\FeatureIcons::ICONS as $key => [$label])
                        <label class="group flex cursor-pointer flex-col items-center gap-1.5 rounded-2xl p-3 text-center ring-1 ring-slate-200 transition hover:bg-slate-50 has-[:checked]:bg-rose-50 has-[:checked]:ring-2 has-[:checked]:ring-rose-500" title="{{ __($label) }}">
                            <input type="radio" name="icon" value="{{ $key }}" class="sr-only" data-icon-option @checked($selectedIcon === $key)>
                            <span class="text-slate-500 group-has-[:checked]:text-rose-600">{!! \App\Support\FeatureIcons::svg($key) !!}</span>
                            <span class="w-full truncate text-[10px] font-medium text-slate-500">{{ __($label) }}</span>
                            <template data-icon-svg>{!! \App\Support\FeatureIcons::svg($key, 'size-6') !!}</template>
                        </label>
                    @endforeach
                </div>
            </x-admin.card>

            <x-admin.card class="p-6 sm:p-8">
                <x-admin.step-heading :title="__('Yayın')" :description="__('Adım anasayfada görünsün mü?')" />
                <div class="mt-4"><x-admin.status-picker :active="$step->active" /></div>
            </x-admin.card>
        </div>

        {{-- ÖNİZLEME --}}
        <div class="xl:col-span-2">
            <div class="xl:sticky xl:top-20">
                <x-admin.card class="p-6">
                    <p class="text-sm font-semibold text-slate-900">{{ __('Anasayfadaki hali') }}</p>
                    <div class="mt-4 rounded-2xl bg-slate-50 p-6">
                        <div class="mx-auto w-full max-w-xs rounded-3xl bg-white p-6 text-center ring-1 ring-slate-200/80 shadow-[0_10px_30px_-18px_rgba(15,23,42,0.35)]">
                            <span class="mx-auto grid size-12 place-items-center rounded-2xl bg-rose-50 text-rose-600" data-preview-icon>{!! \App\Support\FeatureIcons::svg($selectedIcon) !!}</span>
                            <p class="mt-4 text-[11px] font-bold uppercase tracking-[0.16em] text-slate-400">Step</p>
                            <p class="mt-1 text-lg font-bold text-slate-900" data-preview="title"></p>
                            <p class="mt-2 text-sm leading-relaxed text-slate-600" data-preview="text"></p>
                            <p class="mt-4 text-sm font-semibold text-red-600"><span data-preview="cta"></span> →</p>
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
            const form = document.getElementById('step-form');
            form.querySelectorAll('[data-preview-input]').forEach(input => {
                const target = form.querySelector(`[data-preview="${input.dataset.previewInput}"]`);
                const update = () => target.textContent = input.value || input.placeholder;
                input.addEventListener('input', update);
                update();
            });
            form.querySelectorAll('[data-icon-option]').forEach(radio => radio.addEventListener('change', () => {
                form.querySelector('[data-preview-icon]').innerHTML = radio.closest('label').querySelector('template[data-icon-svg]').innerHTML;
            }));
            const choice = form.querySelector('[data-link-choice]');
            choice.addEventListener('change', () => form.querySelector('[data-link-custom]').classList.toggle('hidden', choice.value !== 'custom'));
        })();
    </script>
@endpush
