@extends('layouts.admin')

@section('title', 'Silinen resimler')

@section('header')
    <x-admin.page-header :title="__('Silinen resimler')" :breadcrumb="['Yönetim' => route('management.index')]"
        :description="__('Değiştirdiğin ya da sildiğin resimler ve gece temizliğinin kullanılmıyor diye kaldırdıkları burada :days gün durur. Geri al dersen resim eski yerine döner; :days gün sonra kalıcı silinir.', ['days' => \App\Support\MediaTrash::DAYS])">
        <x-slot:icon><path d="M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13" /><path d="M10 11v6M14 11v6" /></x-slot:icon>
    </x-admin.page-header>
@endsection

@section('content')
    @php
        $folders = ['hero' => 'Banner', 'sections' => 'Bölüm / rehber resmi', 'activities' => 'Aktivite', 'blogs' => 'Blog kapağı', 'content' => 'Yazı içi resim', 'partners' => 'Partner logosu', 'avatars' => 'Profil', 'icons' => 'İkon'];
    @endphp

    <x-admin.card class="overflow-hidden">
        @if (!$items)
            <div class="px-6 py-16 text-center">
                <p class="text-sm font-semibold text-slate-900">{{ __('Çöp kutusu boş') }}</p>
                <p class="mt-1 text-sm text-slate-500">{{ __('Silinen ya da değiştirilen resimler burada :days gün bekler.', ['days' => \App\Support\MediaTrash::DAYS]) }}</p>
            </div>
        @else
            <ul class="grid gap-4 p-5 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($items as $item)
                    @php $folder = strtok($item['path'], '/'); @endphp
                    <li class="overflow-hidden rounded-2xl bg-white ring-1 ring-slate-200">
                        <a href="{{ $item['url'] }}" target="_blank" class="block aspect-[16/9] bg-slate-100">
                            <img src="{{ $item['url'] }}" alt="" loading="lazy" class="size-full object-cover">
                        </a>
                        <div class="space-y-3 p-4">
                            <div class="flex flex-wrap items-center gap-2 text-xs">
                                <span class="rounded-md bg-slate-100 px-2 py-0.5 font-semibold text-slate-700">{{ __($folders[$folder] ?? $folder) }}</span>
                                <span class="text-slate-500">{{ __('Silindi: :date', ['date' => \Illuminate\Support\Carbon::parse($item['day'])->translatedFormat('j M Y')]) }}</span>
                                <span class="text-amber-700">{{ __(':date tarihinde kalıcı silinir', ['date' => \Illuminate\Support\Carbon::parse($item['expires'])->translatedFormat('j M')]) }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <form method="POST" action="{{ route('media-trash.restore') }}" class="flex-1">
                                    @csrf
                                    <input type="hidden" name="file" value="{{ $item['trash'] }}">
                                    <button type="submit" class="w-full rounded-xl bg-brand-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-800">{{ __('Geri al') }}</button>
                                </form>
                                <a href="{{ $item['url'] }}" download class="rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-700 ring-1 ring-slate-200 transition hover:bg-slate-50">{{ __('İndir') }}</a>
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-admin.card>
    <p class="mt-4 text-xs text-slate-500">{{ __('Değiştirdiğin bir resmi geri alırsan dosya geri gelir ama sayfada yeni resim kalır; eskisini kullanmak için “İndir” deyip yeniden yükleyebilirsin.') }}</p>
@endsection
