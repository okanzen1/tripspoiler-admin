@extends('layouts.admin')

@section('title', 'E-posta')

@section('header')
    <x-admin.page-header :title="__('E-posta')">
        <x-slot:icon><path d="M22 12h-6l-2 3h-4l-2-3H2" /><path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11Z" /></x-slot:icon>
    </x-admin.page-header>
@endsection

@section('content')
    <x-admin.card class="px-6 py-16 text-center">
        <div class="mx-auto grid size-14 place-items-center rounded-2xl bg-amber-50 text-amber-600"><x-admin.icon name="alert" class="size-7" /></div>
        <h2 class="mt-5 text-lg font-semibold text-slate-900">{{ __('E-posta sunucusuna bağlanılamadı') }}</h2>
        <p class="mx-auto mt-2 max-w-xl text-sm text-slate-600">
            @if ($configured)
                {{ __('Sunucu şu anda yanıt vermiyor ya da hesap bilgileri değişmiş olabilir. Biraz sonra tekrar dene; sorun sürerse .env dosyasındaki MAILBOX_ ayarlarını ve e-posta şifresini kontrol et.') }}
            @else
                {{ __('E-posta hesabı henüz ayarlanmamış. .env dosyasına MAILBOX_USERNAME ve MAILBOX_PASSWORD bilgilerini ekle.') }}
            @endif
        </p>
        <a href="{{ route('mailbox.index') }}" class="mt-6 inline-flex rounded-xl bg-brand-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-800">{{ __('Tekrar dene') }}</a>
    </x-admin.card>
@endsection
