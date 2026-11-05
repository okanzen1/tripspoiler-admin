@extends('layouts.admin')

@php
    $editing = $announcement->exists;
    $steps = ['Duyuru', 'Yayın'];
    $stepFields = [['text', 'link'], ['active']];
    if (!$editing) {
        $steps[] = 'Onay';
    }
    $start = collect($stepFields)->search(fn ($fields) => $errors->hasAny($fields));
@endphp

@section('title', $editing ? 'Duyuruyu düzenle' : 'Yeni duyuru')

@section('header')
    <x-admin.page-header :title="$editing ? 'Duyuruyu düzenle' : 'Yeni duyuru'" :breadcrumb="['Duyurular' => route('announcements.index')]"
        :description="$editing ? \Illuminate\Support\Str::limit($announcement->text, 90) : 'Sitenin en üstündeki kayan şeride bir duyuru ekle.'">
        <x-slot:icon><path d="M3 11v2a1 1 0 0 0 1 1h2l5 4V6L6 10H4a1 1 0 0 0-1 1Z" /><path d="M15.5 8.5a5 5 0 0 1 0 7M18.5 5.5a9 9 0 0 1 0 13" /></x-slot:icon>
    </x-admin.page-header>
@endsection

@section('content')
    @if ($editing)
        <x-admin.translation-bar type="announcement" :model="$announcement" />
    @endif

    <x-admin.wizard :action="$editing ? route('announcements.update', $announcement) : route('announcements.store')" :method="$editing ? 'PUT' : null"
        :steps="$steps" :start="$start === false ? 0 : $start" :free="$editing"
        :submit-label="$editing ? 'Kaydet' : 'Duyuruyu ekle'" :cancel-url="route('announcements.index')">

        <section data-step class="space-y-5">
            <x-admin.step-heading :title="__('Duyuru')" :description="__('İngilizce yaz; kaydettikten sonra yukarıdaki Çeviriler\'den diğer dillere çevir. Kısa tut, tek cümle.')" />
            <x-admin.input name="text" :label="__('Duyuru metni')" :value="$announcement->text" required maxlength="160"
                placeholder="{{ __('Skip the lines at Hagia Sophia — tickets from €25') }}" autocomplete="off" />
            <x-admin.input name="link" :label="__('Bağlantı (isteğe bağlı)')" :value="$announcement->link" maxlength="500"
                placeholder="/activities" autocomplete="off"
                :hint="__('Site içi adres (/activities) ya da tam adres (https://…). Boş bırakılırsa yazı tıklanmaz.')" />
        </section>

        <section data-step hidden class="space-y-5">
            <x-admin.step-heading :title="__('Yayın')" :description="__('Duyuru kayan şeritte görünsün mü?')" />
            <x-admin.status-picker :active="$announcement->active" />
        </section>

        @unless ($editing)
            <section data-step hidden class="space-y-5">
                <x-admin.step-heading :title="__('Onay')" :description="__('Bilgileri kontrol et, doğruysa duyuruyu ekle.')" />
                <dl class="divide-y divide-slate-100 overflow-hidden rounded-2xl bg-white ring-1 ring-slate-200">
                    @foreach (['Metin' => 'text', 'Bağlantı' => 'link', 'Durum' => 'active'] as $label => $name)
                        <div class="grid grid-cols-3 gap-4 px-5 py-3.5 text-sm">
                            <dt class="text-slate-500">{{ __($label) }}</dt>
                            <dd class="col-span-2 whitespace-pre-line font-medium text-slate-900" data-summary-for="{{ $name }}">—</dd>
                        </div>
                    @endforeach
                </dl>
            </section>
        @endunless
    </x-admin.wizard>
@endsection
