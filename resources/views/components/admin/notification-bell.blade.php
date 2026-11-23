{{--
    Sağ üstteki zil: okunmamış bildirim sayısı ve son bildirimler. Sayfa açılınca sunucudan gelir, sonra
    resources/js/notifications.js 30 sn'de bir günceller (sekme görünürken). Okundu bilgisi kişiye özel.
--}}
@php
    $user = auth()->user();
    $unread = $user->unreadNotifications()->count();
    $items = $user->notifications()->limit(8)->get()->map(fn ($n) => \App\Support\Notify::present($n));
@endphp

<details data-dropdown data-notifications data-summary-url="{{ route('notifications.summary') }}" data-read-all-url="{{ route('notifications.read-all') }}"
    data-unread="{{ $unread }}" data-latest="{{ $items->first()['id'] ?? '' }}" {{ $attributes->class('group relative') }}>
    <summary class="relative grid size-10 cursor-pointer list-none place-items-center rounded-xl bg-white text-slate-600 shadow-sm ring-1 ring-slate-200 transition hover:text-slate-900 hover:ring-brand-200 group-open:ring-2 group-open:ring-brand-900/20 [&::-webkit-details-marker]:hidden"
        aria-label="{{ __('Bildirimler') }}" title="{{ __('Bildirimler') }}">
        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 8a6 6 0 1 1 12 0c0 7 3 9 3 9H3s3-2 3-9M10.3 21a1.94 1.94 0 0 0 3.4 0" /></svg>
        <span data-notifications-badge @class(['absolute -right-1.5 -top-1.5 min-w-5 rounded-full bg-red-600 px-1.5 text-center text-[11px] font-bold leading-5 text-white ring-2 ring-white tabular-nums', 'hidden' => !$unread])>{{ $unread > 99 ? '99+' : $unread }}</span>
        <span data-notifications-running class="absolute -bottom-1 -right-1 hidden size-3.5 place-items-center rounded-full bg-white ring-1 ring-purple-200" title="{{ __('Çeviri sürüyor') }}">
            <svg class="size-3 animate-spin text-purple-600" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-20"/><path d="M22 12a10 10 0 0 0-10-10" stroke="currentColor" stroke-width="4" stroke-linecap="round"/></svg>
        </span>
    </summary>

    <div class="absolute right-0 z-50 mt-2 w-[min(24rem,calc(100vw-2rem))] overflow-hidden rounded-2xl bg-white shadow-xl ring-1 ring-slate-200">
        <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-4 py-3">
            <p class="text-sm font-semibold text-slate-900">{{ __('Bildirimler') }}</p>
            <button type="button" data-notifications-read-all class="text-xs font-semibold text-brand-700 hover:underline">{{ __('Tümünü okundu yap') }}</button>
        </div>
        <div data-notifications-status class="hidden border-b border-slate-100 bg-purple-50/60 px-4 py-2.5 text-xs font-medium text-purple-800"></div>
        <ul data-notifications-list class="max-h-[60vh] divide-y divide-slate-100 overflow-y-auto">
            @forelse ($items as $item)
                @include('admin.notifications._item', ['item' => $item, 'compact' => true])
            @empty
                <li class="px-4 py-8 text-center text-sm text-slate-400">{{ __('Henüz bildirim yok.') }}</li>
            @endforelse
        </ul>
        <a href="{{ route('notifications.index') }}" class="block border-t border-slate-100 px-4 py-2.5 text-center text-sm font-semibold text-slate-700 transition hover:bg-slate-50">{{ __('Tüm bildirimleri gör') }}</a>
    </div>
</details>
