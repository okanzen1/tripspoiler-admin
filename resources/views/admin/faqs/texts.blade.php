@extends('layouts.admin')

@php
    $field = 'mt-1.5 block w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm outline-none transition placeholder:text-slate-400 focus:border-brand-900 focus:ring-4 focus:ring-brand-900/10';
    $defs = \App\Http\Controllers\FaqTextController::FIELDS;
    $input = function (string $key, string $f) use ($defs, $records, $field) {
        [$label, $example, $max] = $defs[$key][$f];
        $value = old("$key.$f", $records[$key]->getTranslation($f, 'en', false));
        $attrs = 'name="' . e("{$key}[{$f}]") . '" maxlength="' . $max . '" placeholder="' . e($example) . '" data-preview-input="' . e("$key.$f") . '"';
        $control = $max > 100
            ? '<textarea ' . $attrs . ' rows="2" class="' . $field . ' resize-none leading-relaxed">' . e($value) . '</textarea>'
            : '<input type="text" ' . $attrs . ' autocomplete="off" value="' . e($value) . '" class="' . $field . '">';

        return '<label class="block"><span class="text-sm font-medium text-slate-700">' . e(__($label)) . '</span>' . $control . '</label>';
    };
@endphp

@section('title', __('SSS bölümünün yazıları'))

@section('header')
    <x-admin.page-header :title="__('SSS bölümünün yazıları')" :breadcrumb="['Sıkça sorulan sorular' => route('faqs.index')]"
        :description="__('Soruların solundaki başlık ve altındaki “Aklında başka bir soru mu var?” kutusu. Sitedeki bütün SSS bölümlerinde aynıdır. Boş bıraktığın alanda soluk yazan varsayılan metin görünür.')">
        <x-slot:icon><circle cx="12" cy="12" r="9" /><path d="M9.5 9a2.5 2.5 0 1 1 3.5 2.3c-.6.3-1 .9-1 1.6V14M12 17h.01" /></x-slot:icon>
        <a href="{{ route('faqs.index') }}" class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-700 ring-1 ring-slate-200 transition hover:bg-white">{{ __('Sorular') }} →</a>
    </x-admin.page-header>
@endsection

@section('footer-action')
    <button type="submit" form="faq-texts-form" class="inline-flex items-center gap-2 rounded-xl bg-brand-900 px-5 py-2.5 font-semibold text-white shadow-sm transition hover:bg-brand-800">
        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12.5l4.5 4.5L19 7.5" /></svg>
        {{ __('Kaydet') }}
    </button>
@endsection

@section('content')
    <form id="faq-texts-form" action="{{ route('faqs.texts.update') }}" method="POST" class="grid gap-6 xl:grid-cols-5">
        @csrf
        @method('PUT')

        <div class="space-y-6 xl:col-span-3">
            @if ($records['faq']->exists && filled($records['faq']->title))
                <x-admin.translation-bar type="section" :model="$records['faq']" class="!mb-0" />
            @endif
            <x-admin.card class="space-y-5 p-6 sm:p-8">
                <x-admin.step-heading :title="__('Başlık')" :description="__('Soruların solundaki başlık ve kısa açıklama. İngilizce yaz ve kaydet; çeviri bu kartın üstündeki kutudan.')" />
                @foreach (array_keys($defs['faq']) as $f) {!! $input('faq', $f) !!} @endforeach
            </x-admin.card>

            @if ($records['faq_contact']->exists && filled($records['faq_contact']->title))
                <x-admin.translation-bar type="section" :model="$records['faq_contact']" class="!mb-0" />
            @endif
            <x-admin.card class="space-y-5 p-6 sm:p-8">
                <x-admin.step-heading :title="__('“Aklında başka bir soru mu var?” kutusu')" :description="__('Başlığın altındaki koyu kutu ve kırmızı düğme. Düğme İletişim sayfasına gider. Çeviri bu kartın üstündeki kutudan.')" />
                @foreach (array_keys($defs['faq_contact']) as $f) {!! $input('faq_contact', $f) !!} @endforeach
            </x-admin.card>
        </div>

        <div class="xl:col-span-2">
            <x-admin.card class="p-6 xl:sticky xl:top-20">
                <p class="text-sm font-semibold text-slate-900">{{ __('Sitedeki hali') }}</p>
                <div class="mt-4 rounded-2xl bg-white p-6 ring-1 ring-slate-200">
                    <p class="flex items-center gap-2 text-[10px] font-bold uppercase tracking-[0.2em] text-red-600"><span class="h-px w-5 bg-red-600"></span><span data-preview="faq.eyebrow"></span></p>
                    <p class="mt-2 text-2xl font-extrabold leading-tight text-slate-900"><span data-preview="faq.title"></span> <span class="text-red-600" data-preview="faq.title_highlight"></span></p>
                    <p class="mt-2 text-sm leading-relaxed text-slate-600" data-preview="faq.description"></p>
                    <div class="mt-5 rounded-2xl bg-slate-900 p-4 text-white">
                        <p class="text-sm font-bold" data-preview="faq_contact.title"></p>
                        <p class="mt-0.5 text-xs text-white/70" data-preview="faq_contact.description"></p>
                    </div>
                    <p class="mt-2 rounded-xl bg-red-700 px-4 py-2.5 text-center text-sm font-semibold text-white"><span data-preview="faq_contact.eyebrow"></span> →</p>
                </div>
            </x-admin.card>
        </div>
    </form>

@endsection

@push('scripts')
    <script>
        document.querySelectorAll('[data-preview-input]').forEach(input => {
            const target = document.querySelector(`[data-preview="${input.dataset.previewInput}"]`);
            const update = () => target.textContent = input.value || input.placeholder;
            input.addEventListener('input', update);
            update();
        });
    </script>
@endpush
