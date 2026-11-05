@extends('layouts.admin')

@section('title', 'Yeni partner')

@php
    // Sunucu hata döndürürse ilk hatalı adımdan başla
    $stepFields = [['logo', 'name', 'slug', 'website'], ['active']];
    $start = collect($stepFields)->search(fn ($fields) => $errors->hasAny($fields));
    $partner = null;
@endphp

@section('header')
    <x-admin.page-header :title="__('Yeni partner')" :description="__('GetYourGuide, Viator gibi bir affiliate partneri ekle.')"
        :breadcrumb="['Yönetim' => route('management.index'), 'Partnerler' => route('partners.index')]">
        <x-slot:icon><path d="M10 13a5 5 0 0 0 7.5.5l3-3a5 5 0 0 0-7-7l-1.7 1.7" /><path d="M14 11a5 5 0 0 0-7.5-.5l-3 3a5 5 0 0 0 7 7l1.7-1.7" /></x-slot:icon>
    </x-admin.page-header>
@endsection

@section('content')
    <x-admin.wizard :action="route('partners.store')" :steps="['Partner', 'Yayın', 'Onay']"
        :start="$start === false ? 0 : $start" :submit-label="__('Partneri oluştur')" :cancel-url="route('partners.index')">

        <section data-step class="space-y-5">
            <x-admin.step-heading :title="__('Partner')" :description="__('Logo, ad ve web sitesi.')" />
            @include('admin.partners._basics')
        </section>

        <section data-step hidden class="space-y-5">
            <x-admin.step-heading :title="__('Yayın')" :description="__('Partner aktif olunca aktivitelere bağlanabilir ve linkleri sitede görünür.')" />
            <x-admin.status-picker :active="true" />
        </section>

        <section data-step hidden class="space-y-5">
            <x-admin.step-heading :title="__('Onay')" :description="__('Bilgileri kontrol et, doğruysa partneri oluştur.')" />
            <dl class="divide-y divide-slate-100 overflow-hidden rounded-2xl ring-1 ring-slate-200">
                @foreach (['Logo' => 'logo', 'Partner adı' => 'name', 'Kod' => 'slug', 'Web sitesi' => 'website', 'Durum' => 'active'] as $label => $field)
                    <div class="grid grid-cols-3 gap-4 px-5 py-3.5 text-sm">
                        <dt class="text-slate-500">{{ __($label) }}</dt>
                        <dd class="col-span-2 break-all font-medium text-slate-900" data-summary-for="{{ $field }}">—</dd>
                    </div>
                @endforeach
            </dl>
        </section>
    </x-admin.wizard>
@endsection
