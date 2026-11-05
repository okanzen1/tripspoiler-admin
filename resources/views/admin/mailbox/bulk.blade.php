@extends('layouts.admin')

@php
    $stepFields = [['subscriber_ids', 'extra_emails'], ['subject', 'body'], ['confirm']];
    // İlk açılışta tüm aboneler seçili; hata dönüşünde son seçim korunur
    $selectedIds = array_map('intval', (array) old('subscriber_ids', session()->hasOldInput() ? [] : $subscribers->pluck('id')->all()));
    $start = collect($stepFields)->search(fn ($fields) => $errors->hasAny($fields));
    $field = 'mt-1.5 block w-full rounded-xl border bg-white px-3.5 py-2.5 text-sm outline-none transition placeholder:text-slate-400 focus:border-brand-900 focus:ring-4 focus:ring-brand-900/10';
@endphp

@section('title', 'Toplu e-posta')

@section('header')
    <x-admin.page-header :title="__('Toplu e-posta')" :breadcrumb="['E-posta' => route('mailbox.index')]"
        description="Her alıcıya ayrı e-posta gider; alıcılar birbirini görmez. Gönderen: {{ config('mailbox.username') }}">
        <x-slot:icon><circle cx="9" cy="8" r="3.5" /><path d="M2.5 20c.8-3.4 3.4-5.5 6.5-5.5s5.7 2.1 6.5 5.5M16 4.6a3.5 3.5 0 0 1 0 6.8M18 14.8c1.9.8 3.1 2.6 3.5 5.2" /></x-slot:icon>
    </x-admin.page-header>
@endsection

@section('content')
    <div class="grid items-start gap-5 xl:grid-cols-[1fr_20rem]">
        <div data-bulk-form>
            <x-admin.wizard :action="route('mailbox.bulk.store')" :steps="['Alıcılar', 'Mesaj', 'Onay']"
                :start="$start === false ? 0 : $start" :submit-label="__('Gönder')" :cancel-url="route('mailbox.index')">

                <section data-step class="space-y-5">
                    <x-admin.step-heading :title="__('Alıcılar')" :description="__('Kimlere gönderilecek?')" />

                    {{-- Blog aboneleri: hepsi seçili gelir, istemediğinin işaretini kaldır --}}
                    <div class="overflow-hidden rounded-2xl ring-1 ring-slate-200" data-subscriber-picker>
                        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 bg-slate-50/60 px-4 py-3">
                            <label class="flex items-center gap-3">
                                <input type="checkbox" data-select-all-subscribers class="size-4 accent-brand-900" aria-label="{{ __('Tüm aboneleri seç') }}"
                                    @disabled($subscribers->isEmpty()) />
                                <span>
                                    <span class="block text-sm font-semibold text-slate-900">{{ __('Blog aboneleri') }}</span>
                                    <span class="block text-xs text-slate-500"><span data-selected-subscribers>{{ count($selectedIds) }}</span> / {{ $subscribers->count() }} seçili · e-postanın altına "abonelikten çık" bağlantısı eklenir</span>
                                </span>
                            </label>
                            @if ($subscribers->count() > 8)
                                <div class="relative w-full sm:w-64">
                                    <x-admin.icon name="search" class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                                    <input type="search" data-subscriber-search placeholder="{{ __('Abone ara…') }}" autocomplete="off"
                                        class="block w-full rounded-lg border border-slate-200 bg-white py-1.5 pl-9 pr-3 text-sm outline-none focus:border-brand-900 focus:ring-2 focus:ring-brand-900/15" />
                                </div>
                            @endif
                        </div>

                        @if ($subscribers->isEmpty())
                            <p class="px-4 py-6 text-sm text-slate-500">{{ __('Henüz aktif blog abonesi yok. Aşağıya adres yazarak gönderebilirsin.') }}</p>
                        @else
                            <ul class="max-h-80 divide-y divide-slate-100 overflow-y-auto">
                                @foreach ($subscribers as $subscriber)
                                    <li data-email="{{ mb_strtolower($subscriber->email) }}">
                                        <label class="flex cursor-pointer items-center gap-3 px-4 py-2.5 transition hover:bg-slate-50 has-[:checked]:bg-white [&:not(:has(:checked))]:opacity-60">
                                            <input type="checkbox" name="subscriber_ids[]" value="{{ $subscriber->id }}" class="size-4 accent-brand-900"
                                                @checked(in_array($subscriber->id, $selectedIds, true)) />
                                            <span class="min-w-0 flex-1 truncate text-sm text-slate-800">{{ $subscriber->email }}</span>
                                            <span class="shrink-0 text-xs text-slate-400">{{ $subscriber->created_at->locale(app()->getLocale())->translatedFormat('j M Y') }}</span>
                                        </label>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>

                    <div>
                        <label for="extra_emails" class="text-sm font-medium text-slate-700">{{ __('Ek adresler') }} <span class="font-normal text-slate-400">{{ __('(isteğe bağlı)') }}</span></label>
                        <textarea id="extra_emails" name="extra_emails" rows="4" placeholder="{{ __('ornek@alanadi.com, ikinci@alanadi.com') }}"
                            @class([$field, 'border-slate-200' => !$errors->has('extra_emails'), 'border-red-300' => $errors->has('extra_emails')])>{{ old('extra_emails') }}</textarea>
                        @error('extra_emails')
                            <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>
                        @else
                            <p class="mt-1.5 text-xs text-slate-500">Virgül, boşluk ya da satırla ayır. En fazla {{ config('mailbox.extra_limit') }} adres. Zaten abone olanlar ikinci kez eklenmez.</p>
                        @enderror
                    </div>
                </section>

                <section data-step hidden class="space-y-5">
                    <x-admin.step-heading :title="__('Mesaj')" :description="__('Konu ve metin. Metin düz yazı olarak gider.')" />
                    <x-admin.input name="subject" :label="__('Konu')" required maxlength="255" :placeholder="__('Ör. Bu ayın en çok tıklanan turları')" />
                    <div>
                        <div class="flex items-center justify-between gap-3">
                            <label for="body" class="text-sm font-medium text-slate-700">{{ __('Mesaj') }}</label>
                            @include('admin.mailbox.partials.template-picker', ['target' => 'body'])
                        </div>
                        <textarea id="body" name="body" rows="14" required maxlength="50000" data-autofocus-start
                            @class([$field, 'resize-y leading-relaxed', 'border-slate-200' => !$errors->has('body'), 'border-red-300' => $errors->has('body')])>{{ old('body', $body) }}</textarea>
                        @error('body') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                </section>

                <section data-step hidden class="space-y-5">
                    <x-admin.step-heading :title="__('Onay')" :description="__('Gönderdikten sonra geri alınamaz; kontrol et.')" />
                    <dl class="divide-y divide-slate-100 overflow-hidden rounded-2xl ring-1 ring-slate-200">
                        <div class="grid grid-cols-3 gap-4 px-5 py-3.5 text-sm">
                            <dt class="text-slate-500">{{ __('Alıcı sayısı') }}</dt>
                            <dd class="col-span-2 font-semibold text-slate-900"><span data-recipient-count>0</span> {{ __('kişi') }}</dd>
                        </div>
                        <div class="grid grid-cols-3 gap-4 px-5 py-3.5 text-sm">
                            <dt class="text-slate-500">{{ __('Konu') }}</dt>
                            <dd class="col-span-2 font-medium text-slate-900" data-summary-for="subject">—</dd>
                        </div>
                    </dl>
                    <label class="flex items-start gap-3 rounded-2xl bg-amber-50 p-4 text-sm text-amber-900 ring-1 ring-amber-200">
                        <input type="checkbox" name="confirm" value="1" required class="mt-0.5 size-4 accent-brand-900" />
                        <span>{!! __(':count kişiye e-posta gönderilecek, onaylıyorum.', ['count' => '<strong class="font-semibold"><span data-recipient-count>0</span></strong>']) !!}</span>
                    </label>
                    @error('confirm') <p class="text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </section>
            </x-admin.wizard>
        </div>

        <x-admin.card class="overflow-hidden xl:sticky xl:top-6">
            <h2 class="border-b border-slate-100 px-5 py-4 text-sm font-semibold text-slate-900">{{ __('Son gönderimler') }}</h2>
            @forelse ($history as $item)
                <div class="border-b border-slate-100 px-5 py-3 last:border-0">
                    <p class="truncate text-sm font-medium text-slate-900">{{ $item->subject }}</p>
                    <p class="text-xs text-slate-500">
                        {{ $item->recipient_count }} kişi · {{ $item->created_at->locale(app()->getLocale())->translatedFormat('j M Y H:i') }}
                        @if ($item->sender) · {{ $item->sender->full_name }} @endif
                    </p>
                </div>
            @empty
                <p class="px-5 py-6 text-sm text-slate-500">{{ __('Henüz toplu e-posta gönderilmedi.') }}</p>
            @endforelse
        </x-admin.card>
    </div>
@endsection
