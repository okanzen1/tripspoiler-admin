@extends('layouts.admin')

@php
    $titles = ['new' => 'Yeni e-posta', 'reply' => 'Yanıtla', 'reply_all' => 'Tümünü yanıtla', 'forward' => 'İlet'];
    $field = 'block w-full rounded-xl border bg-white px-3.5 py-2.5 text-sm outline-none transition placeholder:text-slate-400 focus:border-brand-900 focus:ring-4 focus:ring-brand-900/10';
    $showCc = filled(old('cc', $draft['cc'])) || filled(old('bcc'));
@endphp

@section('title', $titles[$mode])

@section('header')
    <x-admin.page-header :title="$titles[$mode]" :description="__('Gönderen') . ': ' . config('mailbox.from_name') . ' <' . config('mailbox.username') . '>'" :breadcrumb="['E-posta' => route('mailbox.index')]">
        <x-slot:icon><path d="m22 2-7 20-4-9-9-4Z" /><path d="M22 2 11 13" /></x-slot:icon>
    </x-admin.page-header>
@endsection

@section('content')
    <form method="POST" action="{{ route('mailbox.send') }}" enctype="multipart/form-data" class="grid items-start gap-5 lg:grid-cols-3" data-sending-form>
        @csrf
        <input type="hidden" name="mode" value="{{ $mode }}" />
        @if ($original)
            <input type="hidden" name="original_folder" value="{{ $folder }}" />
            <input type="hidden" name="original_uid" value="{{ $original['uid'] }}" />
        @endif

        <x-admin.card class="space-y-5 p-6 lg:col-span-2">
            <div>
                <div class="flex items-center justify-between">
                    <label for="to" class="text-sm font-medium text-slate-700">{{ __('Kime') }} <span class="text-red-500">*</span></label>
                    <button type="button" data-show-cc @class(['text-xs font-medium text-brand-900 hover:underline', 'hidden' => $showCc])>{{ __('Bilgi / Gizli bilgi ekle') }}</button>
                </div>
                <input id="to" name="to" required value="{{ old('to', $draft['to']) }}" placeholder="{{ __('ornek@alanadi.com, ikinci@alanadi.com') }}"
                    @class([$field, 'mt-1.5', 'border-slate-200' => !$errors->has('to'), 'border-red-300' => $errors->has('to')]) />
                @error('to') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @else <p class="mt-1.5 text-xs text-slate-500">{{ __('Birden fazla alıcıyı virgülle ayır.') }}</p> @enderror
            </div>

            <div @class(['grid gap-5 sm:grid-cols-2', 'hidden' => !$showCc]) data-cc-fields>
                <div>
                    <label for="cc" class="text-sm font-medium text-slate-700">{{ __('Bilgi (CC)') }}</label>
                    <input id="cc" name="cc" value="{{ old('cc', $draft['cc']) }}" @class([$field, 'mt-1.5', 'border-slate-200' => !$errors->has('cc'), 'border-red-300' => $errors->has('cc')]) />
                    @error('cc') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="bcc" class="text-sm font-medium text-slate-700">{{ __('Gizli bilgi (BCC)') }}</label>
                    <input id="bcc" name="bcc" value="{{ old('bcc') }}" @class([$field, 'mt-1.5', 'border-slate-200' => !$errors->has('bcc'), 'border-red-300' => $errors->has('bcc')]) />
                    @error('bcc') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <x-admin.input name="subject" :label="__('Konu')" :value="$draft['subject']" required maxlength="255" />

            <div>
                <div class="flex items-center justify-between gap-3">
                    <label for="body" class="text-sm font-medium text-slate-700">{{ __('Mesaj') }} <span class="text-red-500">*</span></label>
                    @include('admin.mailbox.partials.template-picker', ['target' => 'body'])
                </div>
                <textarea id="body" name="body" rows="16" required maxlength="50000" data-autofocus-start
                    @class([$field, 'mt-1.5 resize-y leading-relaxed', 'border-slate-200' => !$errors->has('body'), 'border-red-300' => $errors->has('body')])>{{ old('body', $draft['body']) }}</textarea>
                @error('body') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
            </div>
        </x-admin.card>

        <aside class="space-y-4 lg:sticky lg:top-6">
            <x-admin.card class="p-5">
                <h2 class="flex items-center gap-2 text-sm font-semibold text-slate-900"><x-admin.icon name="paperclip" class="text-slate-400" /> {{ __('Ekler') }}</h2>
                <input type="file" name="attachments[]" multiple
                    class="mt-3 block w-full text-sm text-slate-700 file:me-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-brand-900" />
                <p class="mt-1.5 text-xs text-slate-500">En fazla 10 dosya, dosya başına {{ \Illuminate\Support\Number::fileSize(config('mailbox.attachment_max_kb') * 1024) }}.</p>
                @foreach ($errors->get('attachments.*') as $messages)
                    <p class="mt-1.5 text-xs font-medium text-red-600">{{ $messages[0] }}</p>
                @endforeach

                @if ($mode === 'forward' && $original && $original['attachments'])
                    <label class="mt-4 flex items-start gap-3 border-t border-slate-100 pt-4 text-sm text-slate-700">
                        <input type="hidden" name="include_attachments" value="0" />
                        <input type="checkbox" name="include_attachments" value="1" checked class="mt-0.5 size-4 accent-brand-900" />
                        <span>{{ __('Orijinal ekleri de ilet') }} <span class="block text-xs text-slate-500">{{ collect($original['attachments'])->pluck('name')->implode(', ') }}</span></span>
                    </label>
                @endif
            </x-admin.card>

            <button class="flex w-full items-center justify-center gap-2 rounded-xl bg-brand-900 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-800 disabled:opacity-60" data-submit-label="{{ __('Gönderiliyor…') }}">
                <x-admin.icon name="send" /> <span>{{ __('Gönder') }}</span>
            </button>
            <a href="{{ $original ? route('mailbox.show', ['folder' => $folder, 'uid' => $original['uid']]) : route('mailbox.index') }}"
                class="flex w-full items-center justify-center rounded-xl px-4 py-2.5 text-sm font-medium text-slate-600 ring-1 ring-slate-200 transition hover:bg-white">{{ __('Vazgeç') }}</a>
            <p class="text-xs text-slate-500">{{ __('Gönderilen e-postanın kopyası hesabın "Gönderilenler" klasörüne kaydedilir.') }}</p>
        </aside>
    </form>
@endsection
