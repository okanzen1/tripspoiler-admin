@extends('layouts.admin')

@section('title', 'Partner bağlantıları')

@section('header')
    <x-admin.page-header :title="__('Partner bağlantıları')" :breadcrumb="['Aktiviteler' => route('activities.index')]"
        :description="__('Sitede aktivite listesinin sonunda çıkan “Aradığını bulamadın mı?” kartları. Detay sayfası yoktur; tıklayan ziyaretçi doğrudan partnerin o şehirdeki bütün aktivitelerine gider.')">
        <x-slot:icon><path d="M14 4h6v6M20 4l-9 9M18 14v4a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4" /></x-slot:icon>
        <a href="{{ route('partner-links.create') }}"
            class="inline-flex items-center gap-2 rounded-xl bg-brand-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-800">
            <x-admin.icon name="plus" /> {{ __('Yeni bağlantı') }}
        </a>
    </x-admin.page-header>
@endsection

@section('content')
    <x-admin.card class="overflow-hidden">
        @if ($links->isEmpty())
            <div class="px-6 py-16 text-center">
                <div class="mx-auto grid size-14 place-items-center rounded-2xl bg-emerald-50 text-emerald-600">
                    <svg class="size-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 4h6v6M20 4l-9 9M18 14v4a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4" /></svg>
                </div>
                <p class="mt-4 text-sm font-semibold text-slate-900">{{ __('Henüz bağlantı yok') }}</p>
                <p class="mx-auto mt-1 max-w-md text-sm text-slate-500">{{ __('Örnek: Partner Megapass, şehir Istanbul, bağlantı Megapass\'in İstanbul aktiviteleri sayfası. Sitede aktivitelerin sonunda “Megapass\'te daha fazlası” kartı çıkar.') }}</p>
            </div>
        @else
            <ul class="divide-y divide-slate-100">
                @foreach ($links as $link)
                    <li @class(['flex items-center gap-4 px-6 py-4 transition hover:bg-slate-50/60', 'opacity-60' => !$link->active])>
                        <div class="flex shrink-0 flex-col gap-1">
                            @foreach ([-1 => 'M6 15l6-6 6 6', 1 => 'M6 9l6 6 6-6'] as $step => $path)
                                <form action="{{ route('partner-links.move', $link) }}" method="POST">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="step" value="{{ $step }}">
                                    <button type="submit" @disabled(($step < 0 && $loop->parent->first) || ($step > 0 && $loop->parent->last))
                                        title="{{ $step < 0 ? __('Yukarı al') : __('Aşağı al') }}"
                                        class="grid size-7 place-items-center rounded-lg text-slate-400 ring-1 ring-slate-200 transition hover:bg-white hover:text-brand-900 disabled:opacity-30">
                                        <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $path }}" /></svg>
                                    </button>
                                </form>
                            @endforeach
                        </div>

                        @if ($thumb = $link->imageUrl('sm'))
                            <img src="{{ $thumb }}" alt="" loading="lazy" class="h-12 w-16 shrink-0 rounded-xl object-cover ring-1 ring-slate-200">
                        @else
                            <x-admin.partner-logo :partner="$link->partner" class="size-12" />
                        @endif

                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="font-semibold text-slate-900">{{ $link->partner->name }}</p>
                                <span @class(['rounded-md px-1.5 py-0.5 text-xs font-semibold', 'bg-cyan-50 text-cyan-700' => $link->city, 'bg-violet-50 text-violet-700' => !$link->city])>
                                    {{ $link->city ? $link->city->country?->flag . ' ' . $link->city->name : __('Bütün şehirler') }}
                                </span>
                                <span @class(['rounded-md px-1.5 py-0.5 text-xs font-semibold', 'bg-emerald-50 text-emerald-700' => $link->type, 'bg-slate-100 text-slate-600' => !$link->type])>
                                    {{ $link->type ? __(\App\Models\PartnerLink::TYPES[$link->type] ?? $link->type) : __('Bütün sekmeler') }}
                                </span>
                                @if ($link->count_label)
                                    <span class="rounded-md bg-amber-50 px-1.5 py-0.5 text-xs font-semibold text-amber-700">{{ $link->count_label }} {{ __('aktivite') }}</span>
                                @endif
                            </div>
                            <a href="{{ $link->url }}" target="_blank" rel="noopener" class="mt-0.5 block truncate text-sm text-slate-500 hover:text-brand-900 hover:underline">{{ $link->url }}</a>
                        </div>

                        <div class="flex shrink-0 items-center gap-1.5">
                            <x-admin.toggle :action="route('partner-links.toggle', $link)" :active="$link->active" :label="__('Yayında')" />
                            <a href="{{ route('partner-links.edit', $link) }}" title="{{ __('Düzenle') }}" aria-label="{{ __('Düzenle') }}"
                                class="ml-2 grid size-9 place-items-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-brand-900">
                                <x-admin.icon name="pencil" class="size-[18px]" />
                            </a>
                            <form action="{{ route('partner-links.destroy', $link) }}" method="POST" data-confirm="{{ __('Bu bağlantı silinsin mi?') }}">
                                @csrf @method('DELETE')
                                <button type="submit" title="{{ __('Sil') }}" aria-label="{{ __('Sil') }}" class="grid size-9 place-items-center rounded-lg text-slate-400 transition hover:bg-red-50 hover:text-red-600">
                                    <x-admin.icon name="trash" class="size-[18px]" />
                                </button>
                            </form>
                        </div>
                    </li>
                @endforeach
            </ul>
            <div class="border-t border-slate-100 px-6 py-3.5 text-sm text-slate-500">
                {{ __('Bir şehirde birden fazla bağlantı varsa bu sırayla görünür. “Bütün şehirler” bağlantısı her şehrin listesinde çıkar.') }}
            </div>
        @endif
    </x-admin.card>
@endsection
