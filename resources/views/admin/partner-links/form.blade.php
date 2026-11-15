@extends('layouts.admin')

@php
    $editing = $link->exists;
    $field = 'mt-1.5 block w-full rounded-xl border bg-white px-3.5 py-2.5 text-sm outline-none transition placeholder:text-slate-400 focus:border-brand-900 focus:ring-4 focus:ring-brand-900/10';
    $err = fn ($name) => $errors->has($name) ? 'border-red-300' : 'border-slate-200';
@endphp

@section('title', $editing ? 'Bağlantıyı düzenle' : 'Yeni bağlantı')

@section('header')
    <x-admin.page-header :title="$editing ? __('Bağlantıyı düzenle') : __('Yeni bağlantı')" :breadcrumb="['Aktiviteler' => route('activities.index'), 'Partner bağlantıları' => route('partner-links.index')]"
        :description="__('Partneri ve şehri seç, partnerin o şehirdeki bütün aktivitelerinin sayfasını yapıştır. Kartın yazıları sitede her dilde otomatik çıkar.')">
        <x-slot:icon><path d="M14 4h6v6M20 4l-9 9M18 14v4a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4" /></x-slot:icon>
    </x-admin.page-header>
@endsection

@section('footer-action')
    <button type="submit" form="partner-link-form"
        class="inline-flex items-center gap-2 rounded-xl bg-brand-900 px-5 py-2.5 font-semibold text-white shadow-sm transition hover:bg-brand-800">
        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12.5l4.5 4.5L19 7.5" /></svg>
        {{ $editing ? __('Kaydet') : __('Bağlantıyı ekle') }}
    </button>
@endsection

@section('content')
    <form id="partner-link-form" action="{{ $editing ? route('partner-links.update', $link) : route('partner-links.store') }}" method="POST" enctype="multipart/form-data" class="grid gap-6 xl:grid-cols-5">
        @csrf
        @if ($editing) @method('PUT') @endif

        <x-admin.card class="space-y-5 p-6 sm:p-8 xl:col-span-3">
            <label class="block">
                <span class="text-sm font-medium text-slate-700">{{ __('Partner') }}</span>
                <select name="partner_id" required data-preview="partner" @class([$field, $err('partner_id')])>
                    <option value="">{{ __('Seç…') }}</option>
                    @foreach ($partners as $partner)
                        <option value="{{ $partner->id }}" @selected((string) old('partner_id', $link->partner_id) === (string) $partner->id)>{{ $partner->name }}{{ $partner->active ? '' : ' (' . __('pasif') . ')' }}</option>
                    @endforeach
                </select>
                @error('partner_id') <span class="mt-1.5 block text-xs font-medium text-red-600">{{ $message }}</span> @enderror
            </label>

            <label class="block">
                <span class="text-sm font-medium text-slate-700">{{ __('Şehir') }}</span>
                <select name="city_id" data-preview="city" @class([$field, $err('city_id')])>
                    <option value="">{{ __('Bütün şehirler') }}</option>
                    @foreach ($cities as $city)
                        <option value="{{ $city->id }}" @selected((string) old('city_id', $link->city_id) === (string) $city->id)>{{ $city->country?->flag }} {{ $city->name }}</option>
                    @endforeach
                </select>
                <span class="mt-1 block text-xs text-slate-500">{{ __('Kart bu şehrin aktivite listesinin sonunda çıkar. “Bütün şehirler” seçersen her şehirde çıkar.') }}</span>
            </label>

            {{-- sekme: aktiviteler sayfasındaki Tümü / Şehir kartları / Deneyimler --}}
            <fieldset>
                <legend class="text-sm font-medium text-slate-700">{{ __('Hangi sekmede?') }}</legend>
                @php $typeValue = (string) old('type', $link->type); @endphp
                <div class="mt-1.5 flex flex-wrap gap-2">
                    @foreach (['' => __('Hepsi'), ...array_map('__', \App\Models\PartnerLink::TYPES)] as $value => $label)
                        <label class="cursor-pointer">
                            <input type="radio" name="type" value="{{ $value }}" class="peer sr-only" @checked($typeValue === (string) $value)>
                            <span class="inline-flex rounded-full border border-slate-200 bg-white px-3.5 py-2 text-sm font-medium text-slate-600 transition hover:border-slate-300 peer-checked:border-emerald-600 peer-checked:bg-emerald-600 peer-checked:text-white">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
                <span class="mt-1 block text-xs text-slate-500">{{ __('“Hepsi”: her sekmede çıkar. Bir sekme seçersen yalnızca o sekmede çıkar ve o sekmede aynı partnerin genel bağlantısının yerine geçer (ör. Şehir kartları sekmesinde partnerin şehir kartı sayfası).') }}</span>
            </fieldset>

            <label class="block">
                <span class="text-sm font-medium text-slate-700">{{ __('Bağlantı') }} <span class="font-normal text-slate-400">· {{ __('partnerin o şehirdeki bütün aktiviteleri sayfası (takip kodunla)') }}</span></span>
                <input type="url" name="url" required maxlength="1000" placeholder="https://www.megapass.com/istanbul?ref=…" value="{{ old('url', $link->url) }}" autocomplete="off" @class([$field, $err('url')])>
                @error('url') <span class="mt-1.5 block text-xs font-medium text-red-600">{{ $message }}</span> @enderror
            </label>

            <label class="block">
                <span class="text-sm font-medium text-slate-700">{{ __('Aktivite sayısı') }} <span class="font-normal text-slate-400">· {{ __('isteğe bağlı, ör. 200+') }}</span></span>
                <input type="text" name="count_label" maxlength="20" placeholder="200+" value="{{ old('count_label', $link->count_label) }}" data-preview="count" autocomplete="off" @class([$field, $err('count_label'), 'max-w-[10rem]'])>
                <span class="mt-1 block text-xs text-slate-500">{{ __('Yazarsan kartta “200+ aktivite daha” diye görünür; boş bırakırsan sayı yazılmaz.') }}</span>
            </label>

            <x-admin.image-upload name="image" :label="__('Kart resmi (isteğe bağlı)')" :current="$link->imageUrl('md')" :focus="$link->image_focus ?? 50" placeholder="{{ __('Resim yok') }}"
                :hint="__('Dikey resim: 1200 × 1500 px (4:5). Yazılar resmin alt yarısına gelir, ana konu üst-orta kısımda olsun. Kaydırıcı: kart darken resmin hangi tarafı görünsün. JPG, PNG ya da WebP · en fazla 5 MB')" />
            @error('image') <span class="block text-xs font-medium text-red-600">{{ $message }}</span> @enderror

            <label class="flex items-center gap-3">
                <input type="hidden" name="active" value="0">
                <input type="checkbox" name="active" value="1" @checked(old('active', $link->active)) class="size-5 rounded accent-brand-900">
                <span class="text-sm font-medium text-slate-700">{{ __('Yayında') }}</span>
            </label>
        </x-admin.card>

        {{-- sitede nasıl görünür (Türkçe örnek) --}}
        <div class="xl:col-span-2">
            <p class="mb-2 text-xs font-semibold uppercase tracking-wider text-slate-400">{{ __('Sitede böyle görünür') }}</p>
            <div class="relative isolate flex aspect-[4/5] max-w-xs flex-col justify-between overflow-hidden rounded-3xl bg-gradient-to-br from-slate-900 via-[#1f2a4d] to-[#C62E2E] p-6 text-white shadow-xl">
                <div class="pointer-events-none absolute -right-10 -top-10 -z-10 size-40 rounded-full bg-white/10 blur-2xl"></div>
                <span class="grid size-12 place-items-center rounded-2xl bg-white text-slate-900 shadow">
                    <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7" /><path d="m20 20-3.5-3.5" /></svg>
                </span>
                <div>
                    <p class="text-xl font-extrabold leading-tight">Aradığını bulamadın mı?</p>
                    <p class="mt-2 text-sm text-white/75"><span data-preview-text="partner">Partner</span>'da <span data-preview-text="city">şehir</span> için <span data-preview-text="count"></span> daha fazla aktivite</p>
                </div>
                <span class="inline-flex items-center justify-center gap-2 rounded-xl bg-white px-4 py-3 text-sm font-bold text-slate-900"><span data-preview-text="partner">Partner</span>'da gör ↗</span>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        // önizleme: seçilen partner, şehir ve sayı
        (() => {
            const form = document.getElementById('partner-link-form');
            const sync = () => {
                const partner = form.querySelector('[data-preview="partner"]');
                const city = form.querySelector('[data-preview="city"]');
                const count = form.querySelector('[data-preview="count"]');
                const set = (key, text) => document.querySelectorAll(`[data-preview-text="${key}"]`).forEach((el) => el.textContent = text);
                set('partner', partner.value ? partner.selectedOptions[0].textContent.replace(/\s*\(.*\)$/, '') : 'Partner');
                set('city', city.value ? city.selectedOptions[0].textContent.trim().replace(/^\S+\s/, '') : 'her şehir');
                set('count', count.value.trim());
            };
            form.addEventListener('input', sync);
            sync();
        })();
    </script>
@endpush
