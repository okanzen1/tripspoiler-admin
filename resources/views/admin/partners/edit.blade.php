@extends('layouts.admin')

@section('title', $partner->name)

@section('header')
    <x-admin.page-header :title="$partner->name" :description="__(':count tıklama (tüm zamanlar)', ['count' => number_format($clickCount, 0, ',', '.')])"
        :breadcrumb="['Yönetim' => route('management.index'), 'Partnerler' => route('partners.index')]">
        <x-slot:leading><x-admin.partner-logo :partner="$partner" class="size-12" /></x-slot:leading>
    </x-admin.page-header>
@endsection

@section('content')
    <x-admin.card class="overflow-hidden">
        <form action="{{ route('partners.update', $partner) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="divide-y divide-slate-100">
                <section class="grid gap-6 p-6 sm:p-8 lg:grid-cols-3">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-900">{{ __('Partner') }}</h2>
                        <p class="mt-1 text-sm text-slate-500">{{ __('Logo, ad, kod ve web sitesi.') }}</p>
                    </div>
                    <div class="space-y-5 lg:col-span-2">
                        @include('admin.partners._basics')
                    </div>
                </section>

                <section class="grid gap-6 p-6 sm:p-8 lg:grid-cols-3">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-900">{{ __('Yayın') }}</h2>
                        <p class="mt-1 text-sm text-slate-500">{{ __('Pasif partnerin linkleri sitede kullanılmaz.') }}</p>
                    </div>
                    <div class="lg:col-span-2">
                        <x-admin.status-picker :active="$partner->active" />
                    </div>
                </section>
            </div>

            <div class="flex items-center justify-end gap-3 border-t border-slate-100 bg-slate-50/60 px-6 py-4 sm:px-8">
                <a href="{{ route('partners.index') }}" class="rounded-xl px-4 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-100">{{ __('Vazgeç') }}</a>
                <button type="submit" class="rounded-xl bg-brand-900 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-800">
                    {{ __('Değişiklikleri kaydet') }}
                </button>
            </div>
        </form>
    </x-admin.card>
@endsection
