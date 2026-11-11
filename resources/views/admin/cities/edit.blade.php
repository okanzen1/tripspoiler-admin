@extends('layouts.admin')

@section('title', $city->name)

@section('header')
    <x-admin.page-header :title="$city->name" :description="$city->country->flag . ' ' . $city->country->name_tr"
        :breadcrumb="['Yönetim' => route('management.index'), 'Ülkeler ve şehirler' => route('locations.index', ['country' => $city->country_id])]">
        <x-slot:icon><path d="M12 21s-7-6.1-7-11.5A7 7 0 0 1 19 9.5C19 14.9 12 21 12 21Z" /><circle cx="12" cy="9.5" r="2.5" /></x-slot:icon>
    </x-admin.page-header>
@endsection

@section('content')

    @if ($city->isHiddenByCountry())
        <div class="mb-6 rounded-2xl bg-amber-50 px-4 py-3 text-sm text-amber-900 ring-1 ring-amber-200">
            {!! __('Bu şehir aktif ama :country pasif olduğu için sitede görünmüyor.', ['country' => '<strong class="font-semibold">' . e($city->country->name_tr) . '</strong>']) !!}
            <a href="{{ route('locations.index', ['country' => $city->country_id]) }}" class="font-semibold underline">{{ __('Ülkeyi aktif et') }}</a>
        </div>
    @endif

    <x-admin.card class="overflow-hidden">
        <form action="{{ route('cities.update', $city) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="divide-y divide-slate-100">
                <section class="grid gap-6 p-6 sm:p-8 md:grid-cols-3">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-900">{{ __('Şehir bilgileri') }}</h2>
                        <p class="mt-1 text-sm text-slate-500">{{ __('Ülke, sitede görünen ad ve URL.') }}</p>
                    </div>
                    <div class="space-y-5 md:col-span-2">
                        <x-admin.country-select :countries="$countries" :selected="$city->country_id" required />
                        <p class="-mt-3 text-xs text-slate-500">
                            {{ __('Para birimi ülkeden gelir:') }}
                            <strong class="font-semibold text-slate-700">{{ $city->country->currency ?: __('seçilmemiş') }}</strong>
                            · <a href="{{ route('locations.index', ['country' => $city->country_id]) }}" class="font-semibold text-brand-900 hover:underline">{{ __('Değiştir') }}</a>
                        </p>
                        <x-admin.input name="name" :label="__('Şehir adı')" :value="$city->name" required />
                        <x-admin.input name="slug" :label="__('URL adı')" :value="$city->slug" data-slug-from="name"
                            :hint="__('Değiştirirsen eski link çalışmaz; yayındaki şehirde dikkatli ol.')" />
                    </div>
                </section>

                <section class="grid gap-6 p-6 sm:p-8 md:grid-cols-3">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-900">{{ __('Yayın') }}</h2>
                        <p class="mt-1 text-sm text-slate-500">{{ __('Şehrin sitede görünüp görünmeyeceği.') }}</p>
                    </div>
                    <div class="md:col-span-2">
                        <x-admin.status-picker :active="$city->active" />
                    </div>
                </section>
            </div>

            <div class="flex items-center justify-end gap-3 border-t border-slate-100 bg-slate-50/60 px-6 py-4 sm:px-8">
                <a href="{{ route('locations.index', ['country' => $city->country_id]) }}" class="rounded-xl px-4 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-100">{{ __('Vazgeç') }}</a>
                <button type="submit" class="rounded-xl bg-brand-900 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-800">
                    {{ __('Değişiklikleri kaydet') }}
                </button>
            </div>
        </form>
    </x-admin.card>
@endsection
