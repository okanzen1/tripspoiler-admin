@extends('layouts.admin')

@php($field = 'mt-1.5 block w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm outline-none transition placeholder:text-slate-400 focus:border-brand-900 focus:ring-4 focus:ring-brand-900/10')

@section('title', 'Hazır yanıtlar ve imza')

@section('header')
    <x-admin.page-header :title="__('Hazır yanıtlar ve imza')" :breadcrumb="['E-posta' => route('mailbox.index')]"
        :description="__('Sık sorulanlara hazır cevap yaz; e-posta yazarken tek tıkla eklenir. İmza her yeni e-postanın ve yanıtın altına otomatik eklenir.')">
        <x-slot:icon><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8Z" /><path d="M14 3v5h5M9 13h6M9 17h4" /></x-slot:icon>
    </x-admin.page-header>
@endsection

@section('content')
    <div class="grid items-start gap-5 lg:grid-cols-3">
        <div class="space-y-5 lg:col-span-2">
            <x-admin.card class="overflow-hidden">
                <h2 class="border-b border-slate-100 px-6 py-4 text-sm font-semibold text-slate-900">{{ __('Hazır yanıtlar') }}</h2>
                @if ($templates->isEmpty())
                    <div class="px-6 py-12 text-center">
                        <p class="text-sm font-semibold text-slate-900">{{ __('Henüz hazır yanıt yok') }}</p>
                        <p class="mt-1 text-sm text-slate-500">{{ __('Örneğin "Rezervasyon nasıl yapılır", "İptal ve iade" gibi sık sorulanlara cevap ekle.') }}</p>
                    </div>
                @else
                    <ul class="divide-y divide-slate-100">
                        @foreach ($templates as $template)
                            <li class="px-6 py-4" data-editable>
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <span class="font-semibold text-slate-900">{{ $template->title }}</span>
                                    <span class="flex items-center gap-1">
                                        <button type="button" data-edit-toggle class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-sm font-medium text-slate-600 hover:bg-slate-100"><x-admin.icon name="pencil" /> {{ __('Düzenle') }}</button>
                                        <form method="POST" action="{{ route('mailbox.templates.destroy', $template) }}" data-confirm="{{ __('Bu hazır yanıt silinsin mi?') }}">
                                            @csrf @method('DELETE')
                                            <button class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-sm font-medium text-red-600 hover:bg-red-50"><x-admin.icon name="trash" /> {{ __('Sil') }}</button>
                                        </form>
                                    </span>
                                </div>
                                <p class="mt-1 line-clamp-3 whitespace-pre-line text-sm text-slate-600" data-view>{{ $template->body }}</p>
                                <form method="POST" action="{{ route('mailbox.templates.update', $template) }}" class="mt-3 hidden space-y-3" data-edit>
                                    @csrf @method('PUT')
                                    <div><label class="text-sm font-medium text-slate-700">{{ __('Başlık') }}</label><input name="title" value="{{ $template->title }}" required maxlength="120" class="{{ $field }}" /></div>
                                    <div><label class="text-sm font-medium text-slate-700">{{ __('Metin') }}</label><textarea name="body" rows="6" required class="{{ $field }}">{{ $template->body }}</textarea></div>
                                    <div class="max-w-32"><label class="text-sm font-medium text-slate-700">{{ __('Sıra') }}</label><input type="number" name="sort_order" value="{{ $template->sort_order }}" min="0" max="1000" class="{{ $field }}" /></div>
                                    <div class="flex gap-2">
                                        <button class="rounded-xl bg-brand-900 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-800">{{ __('Kaydet') }}</button>
                                        <button type="button" data-edit-toggle class="rounded-xl px-4 py-2 text-sm font-medium text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50">{{ __('Vazgeç') }}</button>
                                    </div>
                                </form>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-admin.card>

            <x-admin.card class="p-6">
                <h2 class="text-sm font-semibold text-slate-900">{{ __('Yeni hazır yanıt') }}</h2>
                <form method="POST" action="{{ route('mailbox.templates.store') }}" class="mt-4 space-y-4">
                    @csrf
                    <div><label class="text-sm font-medium text-slate-700">{{ __('Başlık') }}</label><input name="title" required maxlength="120" placeholder="{{ __('Ör. Rezervasyon nasıl yapılır') }}" class="{{ $field }}" /></div>
                    <div><label class="text-sm font-medium text-slate-700">{{ __('Metin') }}</label><textarea name="body" rows="6" required placeholder="{{ __('Merhaba, rezervasyonlarımız partner sitesi üzerinden…') }}" class="{{ $field }}"></textarea></div>
                    <button class="inline-flex items-center gap-2 rounded-xl bg-brand-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-800"><x-admin.icon name="plus" /> {{ __('Ekle') }}</button>
                </form>
            </x-admin.card>
        </div>

        <x-admin.card class="p-6 lg:sticky lg:top-6">
            <h2 class="text-sm font-semibold text-slate-900">{{ __('İmza') }}</h2>
            <form method="POST" action="{{ route('mailbox.templates.signature') }}" class="mt-4 space-y-4">
                @csrf @method('PUT')
                <textarea name="signature" rows="6" maxlength="2000" placeholder="{{ __('TripSpoiler
info@tripspoiler.com
www.tripspoiler.com') }}" class="{{ $field }}">{{ $signature }}</textarea>
                <p class="text-xs text-slate-500">{{ __('Boş bırakırsan imza eklenmez.') }}</p>
                <button class="w-full rounded-xl bg-brand-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-800">{{ __('İmzayı kaydet') }}</button>
            </form>
        </x-admin.card>
    </div>
@endsection
