@extends('layouts.admin')

@php
    $roleLabels = ['inbox' => 'Gelen kutusu', 'sent' => 'Gönderilenler', 'drafts' => 'Taslaklar', 'junk' => 'Gereksiz', 'trash' => 'Çöp kutusu', 'archive' => 'Arşiv'];
    $folderLabel = $roleLabels[$current['role'] ?? ''] ?? ($current['name'] ?? $folder);
    $isSentLike = in_array($current['role'] ?? null, ['sent', 'drafts'], true);
    $isTrash = ($current['role'] ?? null) === 'trash';
    $trashPath = collect($folders)->firstWhere('role', 'trash')['path'] ?? null;
    $inboxPath = collect($folders)->firstWhere('role', 'inbox')['path'] ?? 'INBOX';
@endphp

@section('title', 'E-posta')

@section('header')
    <x-admin.page-header :title="__('E-posta')" :description="__('Burada yaptığın her işlem (okundu, taşıma, silme, gönderme) doğrudan hesapta yapılır ve webmail\'de de görünür.')">
        <x-slot:icon><path d="M22 12h-6l-2 3h-4l-2-3H2" /><path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11Z" /></x-slot:icon>
    </x-admin.page-header>
@endsection

@section('content')
    <div class="grid items-start gap-5 lg:grid-cols-[16rem_1fr]">
        <aside class="lg:sticky lg:top-6">
            @include('admin.mailbox.partials.folders')
        </aside>

        <section class="min-w-0 space-y-4">
            <form method="GET" action="{{ route('mailbox.index') }}">
                <input type="hidden" name="folder" value="{{ $folder }}" />
                <div class="relative">
                    <x-admin.icon name="search" class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" />
                    <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="{{ $folderLabel }} içinde ara: gönderen, konu veya metin…"
                        class="block w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-10 pr-3 text-sm outline-none transition placeholder:text-slate-400 focus:border-brand-900 focus:ring-4 focus:ring-brand-900/10" />
                </div>
            </form>

            <x-admin.card class="overflow-hidden">
                <form method="POST" action="{{ route('mailbox.seen') }}" data-mail-list>
                    @csrf
                    <input type="hidden" name="_method" value="PATCH" data-method />
                    <input type="hidden" name="folder" value="{{ $folder }}" />

                    <header class="flex min-h-14 flex-wrap items-center justify-between gap-2 border-b border-slate-100 px-4 py-2.5">
                        <label class="flex items-center gap-3">
                            <input type="checkbox" data-select-all class="size-4 accent-brand-900" aria-label="{{ __('Tümünü seç') }}" />
                            <span class="font-semibold text-slate-900">{{ $folderLabel }}</span>
                            <span class="text-sm text-slate-500">{{ number_format($messages->total(), 0, ',', '.') }} e-posta</span>
                        </label>
                        <div class="hidden flex-wrap gap-1.5" data-selection-bar>
                            <button class="rounded-lg px-3 py-1.5 text-sm font-medium text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50" name="seen" value="1" formaction="{{ route('mailbox.seen') }}" data-method-value="PATCH">{{ __('Okundu') }}</button>
                            <button class="rounded-lg px-3 py-1.5 text-sm font-medium text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50" name="seen" value="0" formaction="{{ route('mailbox.seen') }}" data-method-value="PATCH">{{ __('Okunmadı') }}</button>
                            @if ($isTrash)
                                <button class="rounded-lg px-3 py-1.5 text-sm font-medium text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50" name="target" value="{{ $inboxPath }}" formaction="{{ route('mailbox.move') }}" data-method-value="POST">{{ __('Gelen kutusuna taşı') }}</button>
                                <button class="rounded-lg px-3 py-1.5 text-sm font-medium text-red-600 ring-1 ring-red-200 hover:bg-red-50" formaction="{{ route('mailbox.destroy') }}" data-method-value="DELETE" data-confirm-click="{{ __('Seçili e-postalar kalıcı olarak silinsin mi? Bu işlem geri alınamaz.') }}">{{ __('Kalıcı sil') }}</button>
                            @elseif ($trashPath)
                                <button class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-sm font-medium text-red-600 ring-1 ring-red-200 hover:bg-red-50" name="target" value="{{ $trashPath }}" formaction="{{ route('mailbox.move') }}" data-method-value="POST"><x-admin.icon name="trash" /> {{ __('Çöp kutusuna taşı') }}</button>
                            @endif
                        </div>
                    </header>

                    @if ($messages->isEmpty())
                        <div class="px-6 py-16 text-center">
                            <div class="mx-auto grid size-12 place-items-center rounded-2xl bg-brand-50 text-brand-900"><x-admin.icon name="inbox" class="size-6" /></div>
                            <p class="mt-4 text-sm font-semibold text-slate-900">{{ __(filled($filters['q'] ?? null) ? 'Aramaya uyan e-posta yok' : 'Bu klasör boş') }}</p>
                        </div>
                    @else
                        <ul class="divide-y divide-slate-100">
                            @foreach ($messages as $mail)
                                @php
                                    $who = $isSentLike
                                        ? __('Kime') . ': ' . collect($mail['to'])->map(fn ($a) => $a['name'] ?: $a['email'])->implode(', ')
                                        : (($mail['from']['name'] ?? null) ?: ($mail['from']['email'] ?? __('Bilinmeyen gönderen')));
                                @endphp
                                <li @class(['flex items-center gap-3 px-4 py-3 transition hover:bg-slate-50', 'bg-brand-50/50' => !$mail['seen']])>
                                    <input type="checkbox" name="uids[]" value="{{ $mail['uid'] }}" class="size-4 shrink-0 accent-brand-900" aria-label="{{ __('Seç') }}" />
                                    <span @class(['shrink-0', 'text-amber-500' => $mail['flagged'], 'text-slate-300' => !$mail['flagged']]) title="{{ $mail['flagged'] ? __('Yıldızlı') : '' }}">
                                        <x-admin.icon name="star" @class(['fill-current' => $mail['flagged']]) />
                                    </span>
                                    {{-- Okunmamış noktası; okunmuşlarda boş yer tutar ki hizalar kaymasın --}}
                                    <span @class(['size-2 shrink-0 rounded-full', 'bg-brand-900' => !$mail['seen']]) @if (!$mail['seen']) title="{{ __('Okunmadı') }}" @endif></span>
                                    <a href="{{ route('mailbox.show', ['folder' => $folder, 'uid' => $mail['uid']]) }}" class="grid min-w-0 flex-1 gap-0.5 sm:grid-cols-[15rem_1fr_auto] sm:items-center sm:gap-4">
                                        <span class="flex min-w-0 items-center gap-1.5">
                                            <span @class(['truncate text-sm', 'font-semibold text-slate-900' => !$mail['seen'], 'text-slate-700' => $mail['seen']])>{{ $who }}</span>
                                            @if (!$isSentLike && in_array(mb_strtolower($mail['from']['email'] ?? ''), $subscriberEmails, true))
                                                <span class="shrink-0 rounded-md bg-brand-50 px-1.5 text-[11px] font-medium text-brand-900" title="{{ __('Bu adres bir blog abonesine ait') }}">{{ __('Abone') }}</span>
                                            @endif
                                        </span>
                                        <span class="flex min-w-0 items-center gap-2">
                                            @if ($mail['answered'])<x-admin.icon name="reply" class="size-3.5 text-slate-400" />@endif
                                            <span @class(['truncate text-sm', 'font-semibold text-slate-900' => !$mail['seen'], 'text-slate-600' => $mail['seen']])>{{ $mail['subject'] ?: __('(konu yok)') }}</span>
                                            @if ($mail['has_attachments'])<x-admin.icon name="paperclip" class="size-3.5 text-slate-400" />@endif
                                        </span>
                                        <span class="whitespace-nowrap text-xs text-slate-500" title="{{ $mail['date']?->format('d.m.Y H:i') }}">
                                            {{ $mail['date']?->isToday() ? $mail['date']->format('H:i') : $mail['date']?->locale(app()->getLocale())->translatedFormat('j M') }}
                                        </span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </form>
            </x-admin.card>

            @if ($messages->hasPages())
                {{ $messages->links() }}
            @endif
        </section>
    </div>
@endsection
