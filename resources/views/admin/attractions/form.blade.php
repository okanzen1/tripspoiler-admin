@extends('layouts.admin')

@php
    $editing = $attraction->exists;
    $field = 'mt-1.5 block w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm outline-none transition placeholder:text-slate-400 focus:border-brand-900 focus:ring-4 focus:ring-brand-900/10';
    $faqRows = old('faqs', $editing ? $attraction->faqs->map(fn ($f) => ['id' => $f->id, 'question' => $f->question, 'answer' => $f->answer])->all() : []);
    $linked = collect(old('activity_ids', $editing ? $attraction->activity_ids : []))->map(fn ($id) => (int) $id)->all();
    $steps = ['Yer', 'Anlatım', 'Ziyaret bilgileri', 'Biletler', 'SSS', 'SEO ve yayın'];
@endphp

@section('title', $editing ? 'Yeri düzenle' : 'Yeni yer')

@section('header')
    <x-admin.page-header :title="$editing ? $attraction->getTranslation('name', 'en', false) : __('Yeni yer')" :breadcrumb="['Yerler' => route('attractions.index')]"
        :description="__('İngilizce yaz; kaydettikten sonra yukarıdaki Çeviriler\'den 12 dile çevir. Sadece emin olduğun bilgileri yaz.')">
        <x-slot:icon><path d="M3 21h18M5 21V10l7-5 7 5v11M9 21v-6h6v6" /></x-slot:icon>
        @if ($editing)
            <a href="{{ config('site.url') }}/en/attractions/{{ $attraction->slug }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-600 ring-1 ring-slate-200 transition hover:bg-slate-50">{{ __('Sitede aç') }} ↗</a>
        @endif
    </x-admin.page-header>
@endsection

@section('content')
    @if ($editing)
        <x-admin.translation-bar type="attraction" :model="$attraction" />
    @endif

    <x-admin.wizard :action="$editing ? route('attractions.update', $attraction) : route('attractions.store')" :method="$editing ? 'PUT' : null"
        :steps="$steps" :start="0" :free="$editing"
        :submit-label="$editing ? 'Kaydet' : 'Yeri ekle'" :cancel-url="route('attractions.index')">

        <section data-step class="space-y-5">
            <x-admin.step-heading :title="__('Yer')" :description="__('Hangi şehirde, adı ne, bir iki cümlelik kısa tanıtım ve kapak resmi.')" />
            <label class="block">
                <span class="text-sm font-medium text-slate-700">{{ __('Şehir') }}</span>
                <select name="city_id" required class="{{ $field }}">
                    @foreach ($cities as $city)
                        <option value="{{ $city->id }}" @selected((int) old('city_id', $attraction->city_id) === $city->id)>{{ $city->name }}</option>
                    @endforeach
                </select>
            </label>
            <x-admin.input name="name" :label="__('Ad')" :value="$attraction->getTranslation('name', 'en', false)" required maxlength="120" placeholder="Topkapi Palace" />
            <x-admin.input name="slug" :label="__('Adres (slug)')" :value="$attraction->slug" maxlength="120" placeholder="topkapi-palace" :hint="__('Boş bırakırsan addan üretilir. Sitede /attractions/topkapi-palace')" />
            <label class="block">
                <span class="text-sm font-medium text-slate-700">{{ __('Kısa tanıtım') }}</span>
                <textarea name="summary" rows="3" maxlength="400" class="{{ $field }} resize-y leading-relaxed" placeholder="The palace of the Ottoman sultans for almost 400 years…">{{ old('summary', $attraction->getTranslation('summary', 'en', false)) }}</textarea>
            </label>
            <x-admin.image-upload name="image" :label="__('Kapak resmi')" :current="$attraction->imageUrl()" shape="wide" />
        </section>

        <section data-step hidden class="space-y-5">
            <x-admin.step-heading :title="__('Anlatım')" :description="__('Yerin tarihi, öne çıkanları, nasıl gezilir. Başlıklar (H2) ve tablo kullanabilirsin.')" />
            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white" data-rich-editor="content" data-upload-url="{{ route('editor-images.store') }}" data-media-url="{{ \App\Services\ImageUploader::baseUrl() }}" data-placeholder="{{ __('Yeri anlat…') }}">
                <div class="min-h-80 text-[15px]"></div>
            </div>
            <input type="hidden" name="content" value="{{ old('content', $attraction->getTranslation('content', 'en', false)) }}" />
        </section>

        <section data-step hidden class="space-y-5">
            <x-admin.step-heading :title="__('Ziyaret bilgileri')" :description="__('Sitede bilgi tablosu olarak çıkar. Saatleri resmi siteden kontrol et; emin olmadığını boş bırak.')" />
            <div class="grid gap-4 md:grid-cols-2">
                @foreach (\App\Models\Attraction::VISIT_FIELDS as $visit => [$visitLabel, $visitHint, $visitList])
                    <label @class(['block', 'md:col-span-2' => $visitList])>
                        <span class="text-sm font-medium text-slate-700">{{ __($visitLabel) }}</span>
                        <textarea name="{{ $visit }}" rows="{{ $visitList ? 5 : 2 }}" placeholder="{{ $visitHint }}" class="{{ $field }} resize-y leading-relaxed">{{ old($visit, $attraction->getTranslation($visit, 'en', false)) }}</textarea>
                    </label>
                @endforeach
            </div>
            <x-admin.input name="official_url" :label="__('Resmi site (isteğe bağlı)')" :value="$attraction->official_url" maxlength="500" placeholder="https://muze.gov.tr" :hint="__('Sadece gerçek resmi site. Google bunu güven işareti sayar.')" />
        </section>

        <section data-step hidden class="space-y-5">
            <x-admin.step-heading :title="__('Biletler')" :description="__('Bu yere giriş içeren aktiviteleri işaretle. Kombolar birden fazla yere bağlanabilir; passler en üstte gösterilir.')" />
            @foreach ($activities->groupBy(fn ($a) => $a->city?->name) as $cityName => $group)
                <fieldset class="rounded-2xl bg-slate-50 p-4 ring-1 ring-slate-200">
                    <legend class="px-1 text-xs font-bold uppercase tracking-wide text-slate-500">{{ $cityName }}</legend>
                    <div class="mt-1 grid gap-2 sm:grid-cols-2">
                        @foreach ($group as $activity)
                            <label class="flex cursor-pointer items-start gap-3 rounded-xl bg-white px-3 py-2.5 text-sm ring-1 ring-slate-200 transition has-checked:ring-2 has-checked:ring-brand-900">
                                <input type="checkbox" name="activity_ids[]" value="{{ $activity->id }}" class="mt-0.5 size-4 accent-brand-900" @checked(in_array($activity->id, $linked, true))>
                                <span class="min-w-0">
                                    <span class="block font-medium text-slate-900">{{ $activity->getTranslation('name', 'en', false) }}</span>
                                    <span class="text-xs text-slate-500">{{ $activity->type }}{{ $activity->active ? '' : ' · ' . __('kapalı') }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>
            @endforeach
        </section>

        @include('admin.partials.faq-step', [
            'rows' => $faqRows,
            'description' => 'Bu yerin sayfasında görünecek soru-cevaplar (İngilizce). Google bunları SSS olarak okur.',
        ])

        <section data-step hidden class="space-y-5">
            <x-admin.step-heading :title="__('SEO ve yayın')" :description="__('Boş bırakırsan sitede otomatik başlık kullanılır: “Topkapi Palace Tickets & Visitor Guide 2026”.')" />
            <x-admin.input name="meta_title" :label="__('SEO başlığı')" :value="$attraction->getTranslation('meta_title', 'en', false)" maxlength="70" />
            <label class="block">
                <span class="text-sm font-medium text-slate-700">{{ __('SEO açıklaması') }}</span>
                <textarea name="meta_description" rows="2" maxlength="170" class="{{ $field }} resize-y">{{ old('meta_description', $attraction->getTranslation('meta_description', 'en', false)) }}</textarea>
            </label>
            <x-admin.status-picker :active="$attraction->active" />
            @if ($editing)
                <p class="text-xs text-slate-500">{{ __('Silmek için:') }}
                    <button type="submit" form="delete-attraction" class="font-semibold text-rose-600 hover:underline" data-confirm="{{ __('Bu yer silinsin mi?') }}">{{ __('Yeri sil') }}</button></p>
            @endif
        </section>
    </x-admin.wizard>
    @if ($editing)
        <form id="delete-attraction" method="POST" action="{{ route('attractions.destroy', $attraction) }}" class="hidden">@csrf @method('DELETE')</form>
    @endif
@endsection
