@extends('layouts.admin')

@section('title', __('Hazır yazı yapıştır'))

@php
    $field = 'mt-1.5 block w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm outline-none transition placeholder:text-slate-400 focus:border-brand-900 focus:ring-4 focus:ring-brand-900/10';
@endphp

@section('header')
    <x-admin.page-header :title="__('Hazır yazı yapıştır')" :breadcrumb="['Blog' => route('blogs.index')]"
        :description="__('Talimatı kopyala, Claude\'a ya da ChatGPT\'ye ver, gelen kodu aşağıya yapıştır. Bir ya da birden fazla yazı taslak olarak eklenir; kapak resimlerini sen eklersin.')">
        <x-slot:icon><path d="M12 20h9" /><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z" /></x-slot:icon>
    </x-admin.page-header>
@endsection

@section('content')
    <div class="space-y-6">
        @include('admin.partials.import-prompt', [
            'prompt' => $prompt,
            'description' => __('KAÇ YAZI, ŞEHİR ve (istersen) KONULAR satırlarını doldur, sonra hepsini Claude\'a ya da ChatGPT\'ye yapıştır. Sitede zaten olan yazılar talimatta yazılı; aynılarını yazmaz.'),
        ])

        <x-admin.card class="p-6 sm:p-8">
            <form action="{{ route('blogs.import.store') }}" method="POST" class="space-y-5">
                @csrf
                <x-admin.step-heading :title="__('2. Gelen kodu yapıştır')" :description="__('Gelen kodun tamamını yapıştır; 15 yazı da olsa tek seferde eklenir. Cevap yarıda kesildiyse “devam” deyip geleni de yapıştırırsın. Aynı yazı iki kez eklenmez.')" />
                <label class="block">
                    <span class="text-sm font-medium text-slate-700">{{ __('Şehir') }}</span>
                    <select name="city_id" class="{{ $field }}">
                        <option value="">{{ __('Genel (tüm şehirler)') }}</option>
                        @foreach ($cities as $city)
                            <option value="{{ $city->id }}" @selected((string) old('city_id') === (string) $city->id)>{{ $city->country->flag }} {{ $city->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block">
                    <span class="text-sm font-medium text-slate-700">{{ __('Kod') }}</span>
                    <textarea name="html" rows="16" required placeholder="<post>&#10;<title>…</title>&#10;<meta name=&quot;excerpt&quot; content=&quot;…&quot;>&#10;<article>…</article>&#10;</post>"
                        class="{{ $field }} resize-y font-mono text-xs leading-relaxed">{{ old('html') }}</textarea>
                    @error('html')
                        <span class="mt-1.5 block text-sm font-medium text-red-600">{{ $message }}</span>
                    @enderror
                </label>
                <div class="flex justify-end">
                    <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-brand-900 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-800">
                        <x-admin.icon name="plus" /> {{ __('Yazıları ekle') }}
                    </button>
                </div>
            </form>
        </x-admin.card>
    </div>
@endsection
