@extends('layouts.admin')

@section('title', 'Yeni kullanıcı')

@php
    // Sunucu hata döndürürse ilk hatalı adımdan başla
    $stepFields = [['avatar', 'first_name', 'last_name', 'email'], ['role', 'locale'], ['password']];
    $start = collect($stepFields)->search(fn ($fields) => $errors->hasAny($fields));
@endphp

@section('header')
    <x-admin.page-header :title="__('Yeni kullanıcı')" :description="__('Panele erişecek yeni bir admin ya da editör ekle.')"
        :breadcrumb="['Yönetim' => route('management.index'), 'Kullanıcılar' => route('users.index')]">
        <x-slot:icon><circle cx="10" cy="8" r="3.5" /><path d="M3.5 20c.8-3.4 3.4-5.5 6.5-5.5 1.3 0 2.5.4 3.5 1M19 15v6M16 18h6" /></x-slot:icon>
    </x-admin.page-header>
@endsection

@section('content')
    <div>
        <x-admin.wizard :action="route('users.store')" :steps="['Kişisel bilgiler', 'Rol ve dil', 'Şifre', 'Onay']"
            :start="$start === false ? 0 : $start" :submit-label="__('Kullanıcıyı oluştur')" :cancel-url="route('users.index')">

            <section data-step class="space-y-5">
                <x-admin.step-heading :title="__('Kişisel bilgiler')" :description="__('Panelde görünecek ad ve giriş için kullanılacak e-posta.')" />
                <x-admin.image-upload name="avatar" :label="__('Profil resmi (isteğe bağlı)')" shape="circle" placeholder="?" />
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-admin.input name="first_name" :label="__('Ad')" required autocomplete="off" />
                    <x-admin.input name="last_name" :label="__('Soyad')" required autocomplete="off" />
                </div>
                <x-admin.input name="email" :label="__('E-posta')" type="email" required autocomplete="off" :placeholder="__('ornek@tripspoiler.com')" />
            </section>

            <section data-step hidden class="space-y-5">
                <x-admin.step-heading :title="__('Rol ve dil')" :description="__('Kullanıcının panelde neleri yapabileceğini ve paneli hangi dilde göreceğini seç.')" />
                @include('admin.users._role-picker', ['selected' => old('role', \App\Enums\Role::Editor->value)])
                @include('admin.users._locale-picker', ['selected' => 'tr'])
            </section>

            <section data-step hidden class="space-y-5">
                <x-admin.step-heading :title="__('Şifre')" :description="__('En az 8 karakter. Kullanıcı ilk girişte bu şifreyi kullanacak.')" />
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-admin.input name="password" :label="__('Şifre')" type="password" required minlength="8" autocomplete="new-password" />
                    <x-admin.input name="password_confirmation" :label="__('Şifre (tekrar)')" type="password" required minlength="8" autocomplete="new-password" />
                </div>
            </section>

            <section data-step hidden class="space-y-5">
                <x-admin.step-heading :title="__('Onay')" :description="__('Bilgileri kontrol et, doğruysa kullanıcıyı oluştur.')" />
                <dl class="divide-y divide-slate-100 overflow-hidden rounded-2xl ring-1 ring-slate-200">
                    @foreach (['Profil resmi' => 'avatar', 'Ad' => 'first_name', 'Soyad' => 'last_name', 'E-posta' => 'email', 'Rol' => 'role', 'Panel dili' => 'locale', 'Şifre' => 'password'] as $label => $field)
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
