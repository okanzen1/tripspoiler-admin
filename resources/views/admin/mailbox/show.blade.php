@extends('layouts.admin')

@php
    $address = fn (?array $a) => $a ? ($a['name'] ? $a['name'] . ' <' . $a['email'] . '>' : $a['email']) : '—';
    $list = fn (array $items) => collect($items)->map($address)->implode(', ');
    $roleLabels = ['inbox' => 'Gelen kutusu', 'sent' => 'Gönderilenler', 'drafts' => 'Taslaklar', 'junk' => 'Gereksiz', 'trash' => 'Çöp kutusu', 'archive' => 'Arşiv'];
    $isTrash = ($current['role'] ?? null) === 'trash';
    $trashPath = collect($folders)->firstWhere('role', 'trash')['path'] ?? null;
    $params = ['folder' => $folder, 'uid' => $message['uid']];
    $button = 'inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-sm font-medium text-slate-700 ring-1 ring-slate-200 transition hover:bg-slate-50';
@endphp

@section('title', $message['subject'] ?: 'E-posta')

@section('header')
    <x-admin.page-header :title="$message['subject'] ?: '(konu yok)'" :breadcrumb="['E-posta' => route('mailbox.index'), ($roleLabels[$current['role'] ?? ''] ?? ($current['name'] ?? $folder)) => route('mailbox.index', ['folder' => $folder])]">
        <x-slot:icon><rect x="3" y="5" width="18" height="14" rx="2" /><path d="m4 7 8 6 8-6" /></x-slot:icon>
    </x-admin.page-header>
@endsection

@section('content')
    <div class="grid items-start gap-5 lg:grid-cols-[16rem_1fr]">
        <aside class="lg:sticky lg:top-6">
            @include('admin.mailbox.partials.folders')
        </aside>

        <section class="min-w-0 space-y-4">
            {{-- İşlemler --}}
            <x-admin.card class="flex flex-wrap items-center gap-2 p-3">
                <a href="{{ route('mailbox.compose', ['mode' => 'reply', ...$params]) }}" class="inline-flex items-center gap-1.5 rounded-lg bg-brand-900 px-3.5 py-2 text-sm font-semibold text-white transition hover:bg-brand-800"><x-admin.icon name="reply" /> {{ __('Yanıtla') }}</a>
                <a href="{{ route('mailbox.compose', ['mode' => 'reply_all', ...$params]) }}" class="{{ $button }}">{{ __('Tümünü yanıtla') }}</a>
                <a href="{{ route('mailbox.compose', ['mode' => 'forward', ...$params]) }}" class="{{ $button }}"><x-admin.icon name="forward" /> {{ __('İlet') }}</a>

                <form method="POST" action="{{ route('mailbox.seen') }}">
                    @csrf @method('PATCH')
                    <input type="hidden" name="folder" value="{{ $folder }}" /><input type="hidden" name="uids[]" value="{{ $message['uid'] }}" /><input type="hidden" name="seen" value="0" />
                    <button class="{{ $button }}">{{ __('Okunmadı yap') }}</button>
                </form>
                <form method="POST" action="{{ route('mailbox.flag') }}">
                    @csrf @method('PATCH')
                    <input type="hidden" name="folder" value="{{ $folder }}" /><input type="hidden" name="uid" value="{{ $message['uid'] }}" /><input type="hidden" name="flagged" value="{{ $message['flagged'] ? 0 : 1 }}" />
                    <button @class([$button, 'text-amber-600' => $message['flagged']])><x-admin.icon name="star" @class(['fill-current' => $message['flagged']]) /> {{ __($message['flagged'] ? 'Yıldızı kaldır' : 'Yıldızla') }}</button>
                </form>
                <form method="POST" action="{{ route('mailbox.move') }}">
                    @csrf
                    <input type="hidden" name="folder" value="{{ $folder }}" /><input type="hidden" name="uids[]" value="{{ $message['uid'] }}" />
                    <select name="target" aria-label="{{ __('Taşınacak klasör') }}" onchange="this.value && this.form.requestSubmit()"
                        class="rounded-lg border-0 py-2 pl-3 pr-8 text-sm text-slate-700 ring-1 ring-slate-200">
                        <option value="">{{ __('Taşı…') }}</option>
                        @foreach ($folders as $item)
                            @continue($item['path'] === $folder)
                            <option value="{{ $item['path'] }}">{{ $roleLabels[$item['role'] ?? ''] ?? $item['name'] }}</option>
                        @endforeach
                    </select>
                </form>
                <form method="POST" action="{{ route('mailbox.destroy') }}" class="ms-auto" @if ($isTrash || !$trashPath) data-confirm="{{ __('Bu e-posta kalıcı olarak silinsin mi? Bu işlem geri alınamaz.') }}" @endif>
                    @csrf @method('DELETE')
                    <input type="hidden" name="folder" value="{{ $folder }}" /><input type="hidden" name="uids[]" value="{{ $message['uid'] }}" />
                    <button class="inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-sm font-medium text-red-600 transition hover:bg-red-50"><x-admin.icon name="trash" /> {{ __($isTrash || !$trashPath ? 'Kalıcı sil' : 'Sil') }}</button>
                </form>
            </x-admin.card>

            {{-- Başlık bilgileri --}}
            <x-admin.card class="p-5">
                <dl class="grid gap-x-4 gap-y-1.5 text-sm sm:grid-cols-[6rem_1fr]">
                    <dt class="text-slate-500">{{ __('Gönderen') }}</dt>
                    <dd class="break-all font-medium text-slate-900">
                        {{ $address($message['from']) }}
                        @if ($isSubscriber)
                            <span class="ml-1 rounded-md bg-brand-50 px-1.5 text-[11px] font-medium text-brand-900">{{ __('Blog abonesi') }}</span>
                        @endif
                    </dd>
                    <dt class="text-slate-500">{{ __('Kime') }}</dt><dd class="break-all text-slate-700">{{ $list($message['to']) ?: '—' }}</dd>
                    @if ($message['cc'])<dt class="text-slate-500">{{ __('Bilgi') }}</dt><dd class="break-all text-slate-700">{{ $list($message['cc']) }}</dd>@endif
                    <dt class="text-slate-500">{{ __('Tarih') }}</dt><dd class="text-slate-700">{{ $message['date']?->locale(app()->getLocale())->translatedFormat('j F Y l, H:i') ?? '—' }}</dd>
                </dl>

                @if ($message['attachments'])
                    <ul class="mt-4 flex flex-wrap gap-2 border-t border-slate-100 pt-4">
                        @foreach ($message['attachments'] as $attachment)
                            @continue($attachment['inline'] && $attachment['content_id'])
                            <li>
                                <a href="{{ route('mailbox.attachment', [...$params, 'index' => $attachment['index']]) }}" class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-xs text-slate-700 transition hover:bg-slate-50">
                                    <x-admin.icon name="paperclip" class="text-slate-400" />
                                    <span class="max-w-56 truncate font-medium">{{ $attachment['name'] }}</span>
                                    <span class="text-slate-400">{{ \Illuminate\Support\Number::fileSize($attachment['size']) }}</span>
                                    <x-admin.icon name="download" class="text-slate-400" />
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-admin.card>

            {{-- Gövde: betik çalıştıramayan iframe; yalnızca yüksekliğini bildirir, uzak görseller varsayılan olarak engelli --}}
            <x-admin.card class="overflow-hidden" data-mail-body>
                @if ($hasRemoteImages)
                    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 bg-slate-50 px-4 py-2 text-xs text-slate-600" data-images-notice>
                        <span>{{ __('Gizliliğin için uzak görseller gösterilmiyor.') }}</span>
                        <button type="button" class="inline-flex items-center gap-1 font-medium text-brand-900 hover:underline" data-show-images="{{ route('mailbox.body', [...$params, 'images' => 1]) }}"><x-admin.icon name="eye" class="size-3.5" /> {{ __('Görselleri göster') }}</button>
                    </div>
                @endif
                <iframe title="{{ __('E-posta içeriği') }}" sandbox="allow-scripts allow-popups allow-popups-to-escape-sandbox" referrerpolicy="no-referrer"
                    src="{{ route('mailbox.body', $params) }}" class="block w-full bg-white" style="height: 160px"></iframe>
            </x-admin.card>
        </section>
    </div>
@endsection
