@extends('layouts.admin')

@section('title', __('Hazır rehber yapıştır'))

@section('header')
    <x-admin.page-header :title="__('Hazır rehber yapıştır')" :breadcrumb="$city ? ['Şehir rehberi' => route('city-guides.index'), $city->name => route('city-guides.show', $city)] : ['Şehir rehberi' => route('city-guides.index')]"
        :description="__('Talimatı kopyala, Claude\'a ya da ChatGPT\'ye ver, gelen kodu aşağıya yapıştır. Bir ya da birkaç şehrin genel girişi ve bütün alanları bir seferde dolar; kapak resimlerini sen eklersin.')">
        <x-slot:icon><path d="M9 4 3.5 6v14L9 18l6 2 5.5-2V4L15 6z" /><path d="M9 4v14M15 6v14" /></x-slot:icon>
    </x-admin.page-header>
@endsection

@section('content')
    <div class="space-y-6">
        @include('admin.partials.import-prompt', [
            'prompt' => $prompt,
            'description' => $city
                ? __(':city için hazır. Başka şehir de istersen ŞEHİRLER satırına ekle (ör. :city, Antalya). Sonra hepsini Claude\'a ya da ChatGPT\'ye yapıştır.', ['city' => $city->name])
                : __('ŞEHİRLER satırına istediğin şehirleri yaz (ör. Istanbul, Antalya, Cappadocia), sonra hepsini Claude\'a ya da ChatGPT\'ye yapıştır. Şehirler panelde ekli olmalı.'),
        ])

        <x-admin.card class="p-6 sm:p-8">
            <form action="{{ route('city-guides.import.store') }}" method="POST" class="space-y-5">
                @csrf
                @if ($city)
                    <input type="hidden" name="city_id" value="{{ $city->id }}">
                @endif
                <x-admin.step-heading :title="__('2. Gelen kodu yapıştır')" :description="__('Gelen kodun tamamını yapıştır; birkaç şehir de olsa tek seferde eklenir. Cevap yarıda kesildiyse devamını da yapıştırırsın.')" />
                <p class="rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-800 ring-1 ring-amber-200">
                    {{ __('Dikkat: Bir şehrin daha önce yazılmış alanı koddaysa yazısı yenisiyle değişir (yazının içine eklediğin resimler de gider). Kapak resimleri ve kodda olmayan alanlar olduğu gibi kalır.') }}
                </p>
                <label class="block">
                    <span class="text-sm font-medium text-slate-700">{{ __('Kod') }}</span>
                    <textarea name="html" rows="16" required placeholder="<guide data-city=&quot;Istanbul&quot;>&#10;<header>…</header>&#10;<section data-topic=&quot;sights&quot;>…</section>&#10;</guide>"
                        class="mt-1.5 block w-full resize-y rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 font-mono text-xs leading-relaxed outline-none transition placeholder:text-slate-400 focus:border-brand-900 focus:ring-4 focus:ring-brand-900/10">{{ old('html') }}</textarea>
                    @error('html')
                        <span class="mt-1.5 block text-sm font-medium text-red-600">{{ $message }}</span>
                    @enderror
                </label>
                <div class="flex justify-end">
                    <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-brand-900 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-800">
                        <x-admin.icon name="plus" /> {{ __('Rehberi ekle') }}
                    </button>
                </div>
            </form>
        </x-admin.card>
    </div>
@endsection
