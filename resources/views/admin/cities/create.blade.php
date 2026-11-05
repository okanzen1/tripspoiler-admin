@extends('layouts.admin')

@section('title', 'Yeni şehir')

@php
    // Sunucu hata döndürürse ilk hatalı adımdan başla
    $stepFields = [['country_id'], ['name', 'slug'], ['active']];
    $start = collect($stepFields)->search(fn ($fields) => $errors->hasAny($fields));
@endphp

@section('header')
    <x-admin.page-header :title="__('Yeni şehir')" :description="__('Sitede aktivite ve blog yazılarının bağlanacağı yeni bir şehir ekle.')"
        :breadcrumb="['Yönetim' => route('management.index'), 'Ülkeler ve şehirler' => route('locations.index', ['country' => $preselected?->id])]">
        <x-slot:icon><path d="M12 21s-7-6.1-7-11.5A7 7 0 0 1 19 9.5C19 14.9 12 21 12 21Z" /><path d="M12 7v5M9.5 9.5h5" /></x-slot:icon>
    </x-admin.page-header>
@endsection

@section('content')

    <div>
        <x-admin.wizard :action="route('cities.store')" :steps="['Ülke', 'Şehir', 'Yayın', 'Onay']"
            :start="$start === false ? 0 : $start" :submit-label="__('Şehri oluştur')" :cancel-url="route('locations.index', ['country' => $preselected?->id])">

            <section data-step class="space-y-5">
                <x-admin.step-heading :title="__('Ülke')" :description="__('Şehir hangi ülkede?')" />
                <x-admin.country-select :countries="$countries" :selected="$preselected?->id" required />
            </section>

            <section data-step hidden class="space-y-5">
                <x-admin.step-heading :title="__('Şehir')" :description="__('Sitede gösterilecek ad (İngilizce).')" />
                <x-admin.input name="name" :label="__('Şehir adı')" required autocomplete="off" :placeholder="__('Rome')" />
                <x-admin.input name="slug" :label="__('URL adı')" autocomplete="off" data-slug-from="name"
                    :hint="__('Boş bırakırsan addan otomatik üretilir. Örn: tripspoiler.com/cities/rome')" />
            </section>

            <section data-step hidden class="space-y-5">
                <x-admin.step-heading :title="__('Yayın')" :description="__('Şehir eklendiği anda sitede görünsün mü?')" />
                <x-admin.status-picker :active="false" />
            </section>

            <section data-step hidden class="space-y-5">
                <x-admin.step-heading :title="__('Onay')" :description="__('Bilgileri kontrol et, doğruysa şehri oluştur.')" />
                <dl class="divide-y divide-slate-100 overflow-hidden rounded-2xl ring-1 ring-slate-200">
                    @foreach (['Ülke' => 'country_id', 'Şehir adı' => 'name', 'URL adı' => 'slug', 'Durum' => 'active'] as $label => $field)
                        <div class="grid grid-cols-3 gap-4 px-5 py-3.5 text-sm">
                            <dt class="text-slate-500">{{ __($label) }}</dt>
                            <dd class="col-span-2 font-medium text-slate-900" data-summary-for="{{ $field }}">—</dd>
                        </div>
                    @endforeach
                </dl>
            </section>
        </x-admin.wizard>
    </div>
@endsection
