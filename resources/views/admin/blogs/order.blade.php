@extends('layouts.admin')

@section('title', __('Blog sıralaması'))

@php
    $flag = fn ($code) => $code ? implode('', array_map(fn ($c) => mb_chr(0x1F1E6 + ord($c) - 65), str_split(strtoupper($code)))) : '';
@endphp

@section('header')
    <x-admin.page-header :title="__('Blog sıralaması')" :breadcrumb="['Blog' => route('blogs.index')]"
        :description="__('Blog sayfasında yazıların sırası, şehir şehir. Soldaki tutamaçtan sürükle (klavyede ↑ ↓); kemerlerde elle seçim yoksa ilk üç yazı sayfanın başındaki kemerlerde görünür (Sayfalar › Blog › Hero ayarları). Hemen kaydedilir. Yeni yazılar en üste gelir.')">
        <x-slot:icon><rect x="4" y="4" width="16" height="5" rx="1.5" /><rect x="4" y="11" width="16" height="4" rx="1.5" /><rect x="4" y="17" width="16" height="3" rx="1.5" /></x-slot:icon>
    </x-admin.page-header>
@endsection

@section('content')
    {{-- ilk üç yazı blog banner'ındaki kemerlerde: 1. orta (büyük), 2. sol, 3. sağ (kapak resmi olanlar arasından) --}}
    <style>
        [data-page-sections] > li:nth-child(-n+3) [data-hero-badge] { display: inline-block; }
        [data-page-sections] > li:nth-child(1) [data-hero-badge]::after { content: '{{ __('Banner · orta (büyük)') }}'; }
        [data-page-sections] > li:nth-child(2) [data-hero-badge]::after { content: '{{ __('Banner · sol') }}'; }
        [data-page-sections] > li:nth-child(3) [data-hero-badge]::after { content: '{{ __('Banner · sağ') }}'; }
    </style>
    @if ($cities->isEmpty())
        <x-admin.card class="px-6 py-16 text-center text-sm text-slate-500">{{ __('Henüz şehre bağlı yazı yok.') }}</x-admin.card>
    @else
        {{-- şehir sekmeleri --}}
        <div class="mb-5 flex flex-wrap gap-2">
            @foreach ($cities as $c)
                <a href="{{ route('blogs.order', ['city' => $c->id]) }}"
                    @class(['inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold ring-1 transition', 'bg-brand-900 text-white ring-brand-900' => $c->is($city), 'bg-white text-slate-700 ring-slate-200 hover:bg-slate-50' => !$c->is($city)])>
                    {{ $flag($c->country?->code) }} {{ $c->name }}
                    <span @class(['rounded-full px-2 py-0.5 text-xs', 'bg-white/20' => $c->is($city), 'bg-slate-100 text-slate-500' => !$c->is($city)])>{{ $c->blogs_count }}</span>
                </a>
            @endforeach
        </div>

        <x-admin.card class="overflow-hidden" data-page-sections-root>
            <ul class="divide-y divide-slate-100" data-page-sections data-url="{{ route('blogs.order.save') }}">
                @foreach ($blogs as $blog)
                    <li data-section="{{ $blog->id }}" @class(['flex items-center gap-4 bg-white px-4 py-3 transition sm:px-6', 'opacity-60' => !$blog->active])>
                        <span data-drag-handle draggable="true" tabindex="0" title="{{ __('Sürükleyerek sırala (klavyede ↑ ↓)') }}"
                            class="grid h-10 w-8 shrink-0 cursor-grab place-items-center rounded-lg text-slate-400 ring-1 ring-slate-200 transition hover:bg-slate-50 hover:text-slate-700 focus-visible:outline-2 focus-visible:outline-brand-900 active:cursor-grabbing">
                            <svg class="size-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="9" cy="6" r="1.5"/><circle cx="15" cy="6" r="1.5"/><circle cx="9" cy="12" r="1.5"/><circle cx="15" cy="12" r="1.5"/><circle cx="9" cy="18" r="1.5"/><circle cx="15" cy="18" r="1.5"/></svg>
                        </span>
                        <span data-position class="w-5 shrink-0 text-center text-sm font-semibold text-slate-400">{{ $loop->iteration }}</span>
                        <span class="h-12 w-16 shrink-0 overflow-hidden rounded-lg bg-slate-100">
                            @if ($blog->cover_path)<img src="{{ \App\Services\ImageUploader::url($blog->cover_path, 'sm') }}" alt="" class="size-full object-cover" style="object-position: {{ $blog->cover_focus ?? 50 }}% center">@endif
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="flex items-center gap-2"><span class="truncate font-semibold text-slate-900">{{ $blog->title }}</span><span data-hero-badge class="hidden shrink-0 rounded-md bg-red-50 px-1.5 py-0.5 text-[11px] font-semibold text-red-700 ring-1 ring-red-200"></span></p>
                            <p class="text-xs text-slate-500">
                                {{ $blog->published_at?->format('d.m.Y') }}
                                @unless ($blog->active) · <span class="font-semibold text-amber-700">{{ __('Taslak') }}</span> @endunless
                            </p>
                        </div>
                        <a href="{{ route('blogs.edit', $blog) }}" title="{{ __('Düzenle') }}" class="grid size-9 shrink-0 place-items-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-brand-900">
                            <x-admin.icon name="pencil" class="size-[18px]" />
                        </a>
                    </li>
                @endforeach
            </ul>
        </x-admin.card>
    @endif
@endsection
