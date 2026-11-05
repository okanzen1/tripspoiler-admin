{{-- Klasörler (webmail ile aynı): özel klasörler önce, okunmamış sayılarıyla --}}
@php
    $roles = [
        'inbox' => ['Gelen kutusu', 'inbox'],
        'sent' => ['Gönderilenler', 'send'],
        'drafts' => ['Taslaklar', 'drafts'],
        'junk' => ['Gereksiz', 'alert'],
        'trash' => ['Çöp kutusu', 'trash'],
        'archive' => ['Arşiv', 'archive'],
    ];
    $order = array_keys($roles);
    $ordered = collect($folders)->sortBy(fn ($f) => array_search($f['role'], $order, true) === false ? 99 : array_search($f['role'], $order, true));
@endphp

<x-admin.card class="p-3">
    <a href="{{ route('mailbox.compose') }}" class="mb-2 flex w-full items-center justify-center gap-2 rounded-xl bg-brand-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-800">
        <x-admin.icon name="pencil" /> {{ __('Yeni e-posta') }}
    </a>
    <a href="{{ route('mailbox.bulk.create') }}" class="mb-3 flex w-full items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-brand-900 ring-1 ring-brand-200 transition hover:bg-brand-50">
        <x-admin.icon name="users" /> {{ __('Toplu e-posta') }}
    </a>

    <nav aria-label="{{ __('E-posta klasörleri') }}">
        <ul class="space-y-0.5">
            @foreach ($ordered as $item)
                @php([$label, $icon] = $roles[$item['role']] ?? [$item['name'], 'folder'])
                @php($active = $item['path'] === $folder)
                <li>
                    <a href="{{ route('mailbox.index', ['folder' => $item['path']]) }}" @if ($active) aria-current="page" @endif
                        @class(['flex items-center justify-between gap-2 rounded-lg px-3 py-2 text-sm transition', 'bg-brand-50 font-semibold text-brand-900' => $active, 'text-slate-700 hover:bg-slate-50' => !$active])>
                        <span class="flex min-w-0 items-center gap-2.5">
                            <x-admin.icon :name="$icon" @class(['text-brand-900' => $active, 'text-slate-400' => !$active]) />
                            <span class="truncate">{{ __($label) }}</span>
                        </span>
                        @if ($item['unseen'] > 0)
                            <span class="rounded-full bg-brand-900 px-1.5 py-0.5 text-[11px] font-semibold leading-none text-white">{{ $item['unseen'] }}</span>
                        @endif
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>

    <a href="{{ route('mailbox.templates.index') }}" class="mt-2 flex items-center gap-2.5 border-t border-slate-100 px-3 pb-2 pt-3 text-sm text-slate-700 transition hover:text-brand-900">
        <x-admin.icon name="file" class="text-slate-400" /> {{ __('Hazır yanıtlar ve imza') }}
    </a>
    <p class="truncate border-t border-slate-100 px-3 pt-3 text-xs text-slate-500" title="{{ config('mailbox.username') }}">{{ config('mailbox.username') }}</p>
</x-admin.card>
