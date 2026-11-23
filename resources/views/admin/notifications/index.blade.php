@extends('layouts.admin')

@section('title', 'Bildirimler')

@section('header')
    <x-admin.page-header :title="__('Bildirimler')" :description="__('Yeni e-posta, yardım talebi, yorum, abone, biten çeviriler ve sistem uyarıları. Okundu bilgisi sana özel: sen okuyunca diğer kullanıcılarda okunmamış kalır.')">
        <x-slot:icon><path d="M6 8a6 6 0 1 1 12 0c0 7 3 9 3 9H3s3-2 3-9M10.3 21a1.94 1.94 0 0 0 3.4 0" /></x-slot:icon>
        @if ($unread)
            <form method="POST" action="{{ route('notifications.read-all') }}">
                @csrf
                <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-brand-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-800">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 7 17l-5-5M22 10l-7.5 7.5L13 16" /></svg>
                    {{ __('Tümünü okundu yap (:count)', ['count' => $unread]) }}
                </button>
            </form>
        @endif
    </x-admin.page-header>
@endsection

@section('content')
    {{-- Arka planda süren çeviriler (eskiden sağ altta kutu olarak çıkıyordu) --}}
    @if ($running || $bulk || !$schedulerAlive)
        <x-admin.card class="mb-5 p-5">
            <p class="flex items-center gap-2 text-sm font-semibold text-slate-900">
                <span class="grid size-7 place-items-center rounded-lg bg-purple-100 text-purple-600">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 8 6 6M4 14l6-6 2-3M2 5h12M22 22l-5-10-5 10M14 18h6" /></svg>
                </span>
                {{ __('Arka planda süren çeviriler') }}
            </p>
            @unless ($schedulerAlive)
                <p class="mt-3 rounded-xl bg-red-50 px-3 py-2 text-sm font-medium text-red-700 ring-1 ring-red-200">{{ __('Zamanlayıcı (cron) son 3 dakikadır çalışmıyor: sıradaki işler başlamaz. Hostinger › Cron işlerini kontrol et.') }}</p>
            @endunless
            <ul class="mt-3 space-y-2">
                @foreach ($running as $job)
                    <li class="flex flex-wrap items-center gap-x-3 gap-y-1 rounded-xl bg-slate-50 px-3 py-2 text-sm">
                        <svg class="size-4 animate-spin text-purple-600" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-20"/><path d="M22 12a10 10 0 0 0-10-10" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
                        <a href="{{ $job['url'] }}" class="min-w-0 flex-1 truncate font-medium text-slate-800 hover:underline">{{ $job['title'] }}</a>
                        <span class="text-xs font-semibold tabular-nums text-purple-700">{{ __(':done / :total dil', ['done' => $job['done'], 'total' => $job['total']]) }}</span>
                        @if ($job['message'])
                            <span class="w-full text-xs text-amber-700">{{ $job['message'] }}</span>
                        @endif
                    </li>
                @endforeach
                @if ($bulk)
                    <li class="flex items-center gap-3 rounded-xl bg-slate-50 px-3 py-2 text-sm">
                        <svg class="size-4 animate-spin text-purple-600" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-20"/><path d="M22 12a10 10 0 0 0-10-10" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
                        <span class="flex-1 font-medium text-slate-800">{{ __('Toplu çeviri: :count kayıt sırada', ['count' => $bulk]) }}</span>
                        <a href="{{ route('translations.index') }}" class="text-xs font-semibold text-brand-700 hover:underline">{{ __('Diller ve çeviri') }} →</a>
                    </li>
                @endif
            </ul>
        </x-admin.card>
    @endif

    {{-- Süzgeç: hepsi / okunmamış, türe göre --}}
    <div class="mb-4 flex flex-wrap items-center gap-2">
        @foreach (['all' => __('Hepsi'), 'unread' => __('Okunmamış (:count)', ['count' => $unread])] as $key => $label)
            <a href="{{ route('notifications.index', array_filter(['filtre' => $key === 'unread' ? 'okunmamis' : null, 'tur' => $kind])) }}"
                @class(['rounded-full px-3.5 py-1.5 text-sm font-semibold ring-1 transition', 'bg-brand-900 text-white ring-brand-900' => $filter === $key, 'bg-white text-slate-600 ring-slate-200 hover:ring-slate-300' => $filter !== $key])>{{ $label }}</a>
        @endforeach
        <span class="mx-1 h-5 w-px bg-slate-300"></span>
        @foreach (\App\Support\Notify::KINDS as $key => [$label])
            <a href="{{ route('notifications.index', array_filter(['filtre' => $filter === 'unread' ? 'okunmamis' : null, 'tur' => $kind === $key ? null : $key])) }}"
                @class(['rounded-full px-3 py-1.5 text-xs font-semibold ring-1 transition', 'bg-slate-800 text-white ring-slate-800' => $kind === $key, 'bg-white text-slate-500 ring-slate-200 hover:ring-slate-300' => $kind !== $key])>{{ __($label) }}</a>
        @endforeach
    </div>

    <x-admin.card class="overflow-hidden">
        <ul class="divide-y divide-slate-100">
            @forelse ($notifications as $item)
                @include('admin.notifications._item', ['item' => $item])
            @empty
                <li class="px-5 py-12 text-center text-sm text-slate-400">{{ $filter === 'unread' ? __('Okunmamış bildirim yok.') : __('Henüz bildirim yok.') }}</li>
            @endforelse
        </ul>
        @if ($notifications->hasPages())
            <div class="border-t border-slate-100 px-6 py-3.5">{{ $notifications->links() }}</div>
        @endif
    </x-admin.card>

    @if ($notifications->total() > $unread)
        <form method="POST" action="{{ route('notifications.clear-read') }}" class="mt-4 text-right" data-confirm="{{ __('Okunmuş bildirimlerin silinsin mi? (Sadece senin listenden)') }}">
            @csrf
            @method('DELETE')
            <button type="submit" class="text-sm font-semibold text-slate-500 hover:text-red-600">{{ __('Okunmuşları sil') }}</button>
        </form>
    @endif
@endsection
