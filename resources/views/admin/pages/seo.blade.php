@extends('layouts.admin')

@section('title', $page->name . ' · SEO')

@section('header')
    <x-admin.page-header :title="__('SEO ayarları')" :description="'tripspoiler.com' . $page->path" :breadcrumb="['Yönetim' => route('management.index'), 'Sayfalar' => route('pages.index'), $page->name => route('pages.edit', $page)]">
        <x-slot:icon><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8Z" /><path d="M14 3v5h5" /></x-slot:icon>
    </x-admin.page-header>
@endsection

@section('content')
    <x-admin.translation-bar type="page" :model="$page" />


    <x-admin.card class="overflow-hidden">
        <form action="{{ route('pages.update', $page) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="p-6 sm:p-8">
                <x-admin.step-heading :title="__('SEO')" :description="__('Google sonuçlarında bu sayfanın başlığı ve altındaki açıklama (İngilizce).')" />
                <div class="mt-5">
                    @include('admin.partials.seo-fields', [
                        'metaTitle' => $page->meta_title,
                        'metaDescription' => $page->meta_description,
                        'path' => $page->path,
                        'fallbackTitle' => $page->name . ' | TripSpoiler',
                    ])
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 border-t border-slate-100 bg-slate-50/60 px-6 py-4 sm:px-8">
                <a href="{{ route('pages.edit', $page) }}" class="rounded-xl px-4 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-100">{{ __('Vazgeç') }}</a>
                <button type="submit" class="rounded-xl bg-brand-900 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-800">{{ __('Kaydet') }}</button>
            </div>
        </form>
    </x-admin.card>
@endsection
