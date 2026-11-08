@extends('layouts.admin')

@php
    $labels = [
        'name' => 'Ad', 'title' => 'Başlık', 'question' => 'Soru', 'answer' => 'Cevap', 'excerpt' => 'Özet',
        'description' => 'Açıklama', 'content' => 'İçerik', 'tags' => 'Etiketler',
        'meta_title' => 'SEO başlığı', 'meta_description' => 'SEO açıklaması',
    ];
    $long = ['answer', 'excerpt', 'meta_description'];
    $rtl = in_array($language->code, ['ar', 'he', 'fa', 'ur'], true);
    $field = 'block w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm outline-none transition placeholder:text-slate-400 focus:border-brand-900 focus:ring-4 focus:ring-brand-900/10';
    $prose = '[&_a]:text-brand-700 [&_a]:underline [&_blockquote]:border-l-4 [&_blockquote]:pl-3 [&_h2]:mb-2 [&_h2]:mt-4 [&_h2]:text-base [&_h2]:font-semibold [&_h3]:mb-1.5 [&_h3]:mt-3 [&_h3]:font-semibold [&_img]:my-2 [&_img]:rounded-lg [&_li]:mb-1 [&_ol]:list-decimal [&_ol]:pl-5 [&_p]:mb-2 [&_ul]:list-disc [&_ul]:pl-5';
    $state = $status[$language->code] ?? 'missing';
@endphp

@section('title', $language->native_name . ' · ' . $owner['title'])

@section('header')
    <x-admin.page-header :title="$language->flag() . ' ' . __(':language çevirisi', ['language' => $language->native_name])" :description="$owner['title']" :breadcrumb="[$owner['title'] => $owner['url']]">
        <x-slot:icon><path d="m5 8 6 6M4 14l6-6 2-3M2 5h12M7 2h1M22 22l-5-10-5 10M14 18h6" /></x-slot:icon>
        <form action="{{ route('translations.translate', [$type, $model->getKey()]) }}" method="POST" data-busy="{{ __(':language çevriliyor…', ['language' => $language->native_name]) }}" data-busy-hint="{{ __('Birkaç saniye sürer. Bitince sayfa kendiliğinden yenilenir.') }}"
            data-confirm-title="{{ __(':language baştan çevrilsin mi?', ['language' => $language->native_name]) }}"
            data-confirm="{{ __('Bu dildeki tüm alanlar İngilizceden Google Çeviri ile yeniden çevrilir. Elle yaptığın düzeltmeler gider.') }}"
            data-confirm-button="{{ __('Evet, yeniden çevir') }}"
            onsubmit="setTimeout(() => this.querySelector('button').disabled = true); this.querySelector('span').textContent = @js(__('Çevriliyor…'));">
            @csrf
            <input type="hidden" name="locale" value="{{ $language->code }}" />
            <button type="submit" class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-purple-700 ring-1 ring-purple-200 transition hover:bg-purple-50 disabled:opacity-60">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 8 6 6M4 14l6-6 2-3M2 5h12M7 2h1M22 22l-5-10-5 10M14 18h6" /></svg>
                <span>{{ $language->flag() }} {{ __($state === 'missing' ? ':language: Google ile çevir' : ':language: Google ile yeniden çevir', ['language' => $language->native_name]) }}</span>
            </button>
        </form>
    </x-admin.page-header>
@endsection

@section('content')
    <div class="grid items-start gap-6 lg:grid-cols-[15rem_minmax(0,1fr)]">
        @include('admin.translations.partials.language-list')

        <div class="min-w-0">

    @if ($state === 'stale')
        <div class="mb-5 rounded-2xl bg-amber-50 px-4 py-3 text-sm text-amber-800 ring-1 ring-amber-200">
            {{ __('İngilizce metin bu çeviriden sonra değişti. Farkları düzeltip kaydedebilir ya da Google ile yeniden çevirebilirsin.') }}
        </div>
    @endif

    <x-admin.card class="overflow-hidden">
        <form action="{{ route('translations.update', [$type, $model->getKey(), $language->code]) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="hidden grid-cols-2 gap-6 border-b border-slate-100 bg-slate-50/60 px-6 py-3 text-xs font-semibold text-slate-500 xl:grid">
                <span>English ({{ __('kaynak') }})</span>
                <span>{{ $language->native_name }}</span>
            </div>

            <div class="divide-y divide-slate-100">
                @foreach ($fields as $name => $item)
                    @php($value = old("fields.{$name}", is_array($item['value']) ? implode(', ', $item['value']) : $item['value']))
                    <div class="grid gap-3 px-6 py-5 xl:grid-cols-2 xl:gap-6">
                        <div class="min-w-0">
                            <p class="mb-1.5 text-sm font-medium text-slate-700">{{ __($labels[$name] ?? $name) }} <span class="text-xs font-normal text-slate-400 xl:hidden">· English</span></p>
                            @if ($item['html'])
                                <div class="max-h-[32rem] overflow-y-auto rounded-xl bg-slate-50 px-4 py-3 text-sm leading-relaxed text-slate-700 ring-1 ring-slate-200 {{ $prose }}">{!! $item['source'] !!}</div>
                            @else
                                <p class="whitespace-pre-line rounded-xl bg-slate-50 px-3.5 py-2.5 text-sm text-slate-700 ring-1 ring-slate-200">{{ is_array($item['source']) ? implode(', ', $item['source']) : $item['source'] }}</p>
                            @endif
                        </div>

                        <div class="min-w-0">
                            <label for="field-{{ $name }}" class="mb-1.5 block text-sm font-medium text-slate-700">{{ __($labels[$name] ?? $name) }} <span class="text-xs font-normal text-slate-400">· {{ $language->native_name }}</span></label>
                            @if ($item['html'])
                                <div class="overflow-hidden rounded-xl border border-slate-200 bg-white" data-rich-editor="fields[{{ $name }}]"
                                    data-upload-url="{{ route('editor-images.store') }}" data-media-url="{{ \App\Services\ImageUploader::baseUrl() }}" @if ($rtl) dir="rtl" @endif>
                                    <div class="max-h-[32rem] min-h-60 overflow-y-auto text-[15px]"></div>
                                </div>
                                <input type="hidden" name="fields[{{ $name }}]" value="{{ $value }}" />
                            @elseif (in_array($name, $long, true))
                                <textarea id="field-{{ $name }}" name="fields[{{ $name }}]" rows="4" dir="{{ $rtl ? 'rtl' : 'auto' }}" class="{{ $field }} resize-y leading-relaxed">{{ $value }}</textarea>
                            @else
                                <input id="field-{{ $name }}" name="fields[{{ $name }}]" value="{{ $value }}" dir="{{ $rtl ? 'rtl' : 'auto' }}" class="{{ $field }}" />
                            @endif
                            @if ($name === 'tags')<p class="mt-1.5 text-xs text-slate-500">{{ __('Virgülle ayır.') }}</p>@endif
                            @error("fields.{$name}") <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="flex items-center justify-end gap-3 border-t border-slate-100 bg-slate-50/60 px-6 py-4">
                <a href="{{ $owner['url'] }}" class="rounded-xl px-4 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-100">{{ __('Vazgeç') }}</a>
                <button type="submit" class="rounded-xl bg-brand-900 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-800">
                    {{ __(':language çevirisini kaydet', ['language' => $language->native_name]) }}
                </button>
            </div>
        </form>
    </x-admin.card>
        </div>
    </div>
@endsection
