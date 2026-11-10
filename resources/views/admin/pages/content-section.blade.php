@extends('layouts.admin')

@php
    [$name, $hint, $icon] = $def;
    [$headKey, $headFields, $headRecord] = $head;
    $field = 'mt-1.5 block w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm outline-none transition placeholder:text-slate-400 focus:border-brand-900 focus:ring-4 focus:ring-brand-900/10';
    $input = function ($key, $f, $label, $example, $max, $record, $rows = null) use ($field) {
        $value = old("$key.$f", $record->getTranslation($f, 'en', false));
        $control = $max > 100
            ? '<textarea name="' . e("{$key}[{$f}]") . '" rows="' . ($rows ?? ($max > 1000 ? 7 : 3)) . '" maxlength="' . $max . '" placeholder="' . e($example) . '" class="' . $field . ' resize-y leading-relaxed">' . e($value) . '</textarea>'
            : '<input type="text" name="' . e("{$key}[{$f}]") . '" maxlength="' . $max . '" placeholder="' . e($example) . '" value="' . e($value) . '" class="' . $field . '">';

        return '<label class="block"><span class="text-sm font-medium text-slate-700">' . e(__($label)) . '</span>' . $control . '</label>';
    };
@endphp

@section('title', $page->name . ' · ' . __($name))

@section('header')
    <x-admin.page-header :title="__($name)" :breadcrumb="['Sayfalar' => route('pages.index'), $page->name => route('pages.edit', $page), 'Sayfa içeriği' => route('pages.content', $page)]"
        :description="__($hint) . ' ' . __('İngilizce yaz ve kaydet; sonra “Tüm dillere çevir”. Boş bırakırsan varsayılan metin görünür.')">
        <x-slot:icon>{!! preg_replace('#^<svg[^>]*>|</svg>$#', '', \App\Support\FeatureIcons::svg($icon)) !!}</x-slot:icon>
        {{-- Çeviri bekleyen varsa sadece onlar; hepsi güncelse "Yeniden çevir" (all=1, onay sorar) --}}
        <form action="{{ route('pages.content.translate', [$page, $key]) }}" method="POST" data-translate="content:{{ $page->id }}:{{ $key }}" data-live="content-translate-{{ $key }}"
            @unless ($pending)
                data-confirm-title="{{ __('Tüm diller baştan çevrilsin mi?') }}"
                data-confirm="{{ __('Güncel olsalar da tüm dillerdeki metin İngilizceden Google Çeviri ile yeniden çevrilir. Dillerde elle yaptığın düzeltmeler gider.') }}"
                data-confirm-button="{{ __('Evet, yeniden çevir') }}"
            @endunless>
            @csrf
            @unless ($pending) <input type="hidden" name="all" value="1"> @endunless
            <button type="submit" class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold ring-1 transition {{ $pending ? 'bg-amber-50 text-amber-800 ring-amber-200 hover:bg-amber-100' : 'text-purple-700 ring-purple-200 hover:bg-purple-50' }}">
                @if ($pending)
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m5 8 6 6M4 14l6-6 2-3M2 5h12M7 2h1M22 22l-5-10-5 10M14 18h6" /></svg>
                    {{ __('Tüm dillere çevir') }} · {{ __('bekliyor') }}
                @else
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 12a9 9 0 0 1 15.5-6.2L21 8M21 3v5h-5M21 12a9 9 0 0 1-15.5 6.2L3 16M3 21v-5h5" /></svg>
                    {{ __('Yeniden çevir') }}
                @endif
            </button>
        </form>
    </x-admin.page-header>
@endsection

@section('footer-action')
    <button type="submit" form="content-section" class="inline-flex items-center gap-2 rounded-xl bg-brand-900 px-5 py-2.5 font-semibold text-white shadow-sm transition hover:bg-brand-800">
        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12.5l4.5 4.5L19 7.5" /></svg>
        {{ __('Kaydet') }}
    </button>
@endsection

@section('content')
    <form id="content-section" action="{{ route('pages.content.update', [$page, $key]) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        {{-- bölüm başlığı --}}
        <x-admin.card class="p-6 sm:p-8">
            <p class="text-sm font-semibold text-slate-900">{{ $cards->isNotEmpty() ? __('Bölüm başlığı') : __('Yazılar') }}</p>
            @if (filled($headRecord->title))
                <x-admin.language-pills type="section" :model="$headRecord" class="mt-3 border-b border-slate-100 pb-4" />
            @endif
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                @foreach ($headFields as $f => [$label, $example, $max])
                    <div @class(['sm:col-span-2' => $max > 100])>{!! $input($headKey, $f, $label, $example, $max, $headRecord) !!}</div>
                @endforeach
            </div>
        </x-admin.card>

        {{-- kartlar: yan yana --}}
        @if ($cards->isNotEmpty())
            <div>
                <p class="mb-3 text-sm font-semibold text-slate-900">{{ __('Kartlar') }} <span class="font-normal text-slate-500">· {{ __('sitedeki sırayla, soldan sağa') }}</span></p>
                <div @class(['grid gap-4', 'md:grid-cols-3' => $cards->count() === 3, 'md:grid-cols-2 xl:grid-cols-4' => $cards->count() === 4])>
                    @foreach ($cards as [$cardKey, $cardName, $cardFields, $cardRecord])
                        <x-admin.card class="space-y-4 p-5">
                            <div class="flex items-center gap-2">
                                <span class="grid size-7 place-items-center rounded-full bg-brand-900 text-xs font-bold text-white">{{ $loop->iteration }}</span>
                                <p class="text-sm font-semibold text-slate-900">{{ __($cardName) }}</p>
                            </div>
                            @if (filled($cardRecord->title))
                                <x-admin.language-pills type="section" :model="$cardRecord" class="border-b border-slate-100 pb-3" />
                            @endif
                            @foreach ($cardFields as $f => [$label, $example, $max])
                                {!! $input($cardKey, $f, $label, $example, $max, $cardRecord, 4) !!}
                            @endforeach
                        </x-admin.card>
                    @endforeach
                </div>
            </div>
        @endif
        @if ($errors->any())
            <p class="rounded-xl bg-red-50 px-4 py-3 text-sm font-medium text-red-700">{{ $errors->first() }}</p>
        @endif
    </form>
@endsection
