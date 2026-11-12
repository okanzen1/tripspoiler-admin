@extends('layouts.admin')

@php
    [$name, $hint] = $meta;
    $field = 'mt-1.5 block w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm outline-none transition focus:border-brand-900 focus:ring-4 focus:ring-brand-900/10';
@endphp

@section('title', $name)

@section('header')
    <x-admin.page-header :title="$name" :breadcrumb="['Yönetim' => route('management.index'), 'Bölümler' => route('site-settings.index')]" :description="$hint">
        <x-slot:icon><path d="M4 6h16M4 12h10M4 18h7" /></x-slot:icon>
        <x-admin.section-toggle :section="$key === 'popular' ? 'home_popular' : $key" />
    </x-admin.page-header>
@endsection

@section('footer-action')
    <button type="submit" form="section-text-form" class="inline-flex items-center gap-2 rounded-xl bg-brand-900 px-5 py-2.5 font-semibold text-white shadow-sm transition hover:bg-brand-800">
        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12.5l4.5 4.5L19 7.5" /></svg>
        {{ __('Kaydet') }}
    </button>
@endsection

@section('content')
    @if ($fields)
        <x-admin.translation-bar type="section" :model="$model" />
    @endif

    <form id="section-text-form" action="{{ route('section-texts.update', $key) }}" method="POST" enctype="multipart/form-data" class="grid gap-6 xl:grid-cols-5">
        @csrf
        @method('PUT')

        <div class="space-y-6 xl:col-span-3">
        @foreach ($images as $image)
            <x-admin.card class="p-6 sm:p-8">
                <x-admin.step-heading :title="__($image['label'])" :description="__($image['hint'])" />
                <div class="mt-5 grid gap-5 sm:grid-cols-5 sm:items-center">
                    <div class="relative aspect-[16/9] overflow-hidden rounded-2xl bg-slate-900 sm:col-span-3">
                        <img src="{{ $image['url'] ?: $image['default'] }}" alt="" data-image-preview="{{ $image['field'] }}" class="absolute inset-0 size-full object-cover">
                        <div class="absolute inset-0 bg-gradient-to-r from-slate-950/80 to-transparent"></div>
                    </div>
                    <div class="space-y-2 sm:col-span-2">
                        <label class="flex cursor-pointer items-center justify-center gap-2 rounded-2xl bg-brand-900 px-5 py-3.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-800">
                            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 16V4M7 9l5-5 5 5M4 20h16" /></svg>
                            <span data-image-label="{{ $image['field'] }}">{{ $image['url'] ? __('Resmi değiştir') : __('Resim seç') }}</span>
                            <input type="file" name="{{ $image['field'] }}" accept="image/jpeg,image/png,image/webp" class="sr-only" data-image-input="{{ $image['field'] }}">
                        </label>
                        <p class="text-xs text-slate-500" data-image-status="{{ $image['field'] }}">{{ $image['url'] ? __('Panelden yüklenen resim kullanılıyor.') : __('Şu an varsayılan resim kullanılıyor.') }}</p>
                        @error($image['field']) <p class="text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </x-admin.card>
        @endforeach
        @if ($overlayKey)
            {{-- resimlerin karartması (sayfa banner'larındaki gibi): yazılar okunsun diye fotoğraf ne kadar koyulaşsın --}}
            <x-admin.card class="p-6 sm:p-8">
                <x-admin.step-heading :title="__(count($images) > 1 ? 'Resimlerin karartması' : 'Resmin karartması')" :description="__(count($images) > 1 ? 'Kartlardaki yazılar okunabilsin diye fotoğraflar ne kadar koyulaşsın? İki karta da uygulanır.' : 'Karttaki yazı okunabilsin diye fotoğraf ne kadar koyulaşsın?')" />
                <div class="mt-5 grid gap-2 sm:grid-cols-3">
                    @foreach (['soft' => ['Az', 'Fotoğraf daha canlı.'], 'medium' => ['Orta (önerilen)', 'Hem fotoğraf hem yazı net.'], 'strong' => ['Çok', 'Parlak fotoğrafta yazıyı öne çıkarır.']] as $value => [$label, $hint])
                        <label class="flex cursor-pointer flex-col rounded-2xl bg-white p-3 ring-1 ring-slate-200 transition hover:bg-slate-50 has-[:checked]:bg-brand-50 has-[:checked]:ring-2 has-[:checked]:ring-brand-900">
                            <input type="radio" name="overlay" value="{{ $value }}" class="sr-only" @checked(old('overlay', $overlay) === $value)>
                            <span class="text-sm font-semibold text-slate-900">{{ __($label) }}</span>
                            <span class="mt-0.5 text-xs text-slate-500">{{ __($hint) }}</span>
                        </label>
                    @endforeach
                </div>
            </x-admin.card>
        @endif
        @if ($key === 'popular')
            <a href="{{ route('activities.index') }}" class="flex items-center justify-between gap-3 rounded-2xl bg-amber-50 px-4 py-3 text-sm text-amber-900 ring-1 ring-amber-200 transition hover:bg-amber-100">
                <span>{{ __('Kartlarda hangi aktivitelerin görüneceğini aktivite formundaki “Popüler” anahtarıyla seçersin.') }}</span>
                <span class="shrink-0 font-semibold">{{ __('Aktiviteler') }} →</span>
            </a>
        @endif
        @if ($fields)
        <x-admin.card class="space-y-5 p-6 sm:p-8">
 @if ($key === 'partners')
                <a href="{{ route('partners.index') }}" class="flex items-center justify-between gap-3 rounded-2xl bg-lime-50 px-4 py-3 text-sm text-lime-900 ring-1 ring-lime-200 transition hover:bg-lime-100">
                    <span>{{ __('Şeritte hangi logoların döneceğini Partnerler sayfasındaki “Partnerler şeridinde” anahtarıyla seçersin.') }}</span>
                    <span class="shrink-0 font-semibold">{{ __('Partnerler') }} →</span>
                </a>
            @endif
            <x-admin.step-heading :title="__('Yazılar')" :description="__('İngilizce yaz ve kaydet. Diğer dillere yukarıdaki Çeviriler kutusundan çevir.')" />
            @foreach ([
                'eyebrow' => ['Üstteki küçük yazı', 40],
                'title' => ['Başlık (siyah kısım)', 60],
                'title_highlight' => ['Başlık (kırmızı kısım)', 60],
                'description' => ['Açıklama', 260],
            ] as $f => [$label, $limit])
                @continue(!in_array($f, $fields, true))
                <label class="block">
                    <span class="text-sm font-medium text-slate-700">{{ __($label) }}</span>
                    @if ($f === 'description')
                        <textarea name="{{ $f }}" rows="3" maxlength="{{ $limit }}" data-preview-input="{{ $f }}" class="{{ $field }} resize-none leading-relaxed">{{ old($f, $model->getTranslation($f, 'en', false)) }}</textarea>
                    @else
                        <input type="text" name="{{ $f }}" maxlength="{{ $limit }}" autocomplete="off" data-preview-input="{{ $f }}"
                            value="{{ old($f, $model->getTranslation($f, 'en', false)) }}" class="{{ $field }}">
                    @endif
                    @error($f) <span class="mt-1 block text-xs font-medium text-red-600">{{ $message }}</span> @enderror
                </label>
            @endforeach
        </x-admin.card>
        @endif
        </div>

        @if ($fields)
        <div class="xl:col-span-2">
            <x-admin.card class="p-6 xl:sticky xl:top-20">
                <p class="text-sm font-semibold text-slate-900">{{ __('Anasayfadaki hali') }}</p>
                <div class="mt-4 rounded-2xl bg-slate-50 p-6 ring-1 ring-slate-200">
                    <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-red-600" data-preview="eyebrow"></p>
                    <p class="mt-2 text-2xl font-extrabold leading-tight text-slate-900"><span data-preview="title"></span> <span class="text-red-600" data-preview="title_highlight"></span></p>
                    <p class="mt-2 text-sm leading-relaxed text-slate-600" data-preview="description"></p>
                </div>
            </x-admin.card>
        </div>
        @endif
    </form>
@endsection

@push('scripts')
    <script>
        // Seçilen resim kaydetmeden önce önizlenir
        document.querySelectorAll('[data-image-input]').forEach((input) => input.addEventListener('change', () => {
            const file = input.files[0];
            const name = input.dataset.imageInput;
            if (!file) return;
            document.querySelector(`[data-image-preview="${name}"]`).src = URL.createObjectURL(file);
            document.querySelector(`[data-image-status="${name}"]`).textContent = @js(__('Yeni resim seçildi; kaydetmeyi unutma.'));
        }));
        document.querySelectorAll('[data-preview-input]').forEach(input => {
            const target = document.querySelector(`[data-preview="${input.dataset.previewInput}"]`);
            const update = () => target.textContent = input.value;
            input.addEventListener('input', update);
            update();
        });
    </script>
@endpush
