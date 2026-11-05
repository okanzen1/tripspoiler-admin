@extends('layouts.admin')

@section('title', $page->name . ' · ' . __('Sayfa içeriği'))

@section('header')
    <x-admin.page-header :title="__('Sayfa içeriği')" :breadcrumb="['Yönetim' => route('management.index'), 'Sayfalar' => route('pages.index'), $page->name => route('pages.edit', $page)]"
        :description="__(':page sayfasındaki bölümler. Yazılarını düzenlemek için bölüme tıkla.', ['page' => $page->name])">
        <x-slot:icon><path d="M4 6h16M4 12h10M4 18h7" /></x-slot:icon>
        <a href="{{ route('pages.sections', $page) }}" class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-700 ring-1 ring-slate-200 transition hover:bg-white">
            <svg class="size-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="9" cy="6" r="1.5"/><circle cx="15" cy="6" r="1.5"/><circle cx="9" cy="12" r="1.5"/><circle cx="15" cy="12" r="1.5"/><circle cx="9" cy="18" r="1.5"/><circle cx="15" cy="18" r="1.5"/></svg>
            {{ __('Sıra ve aç/kapa') }}
        </a>
        <a href="{{ $page->siteUrl() }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-700 ring-1 ring-slate-200 transition hover:bg-white">{{ __('Sayfayı aç') }} ↗</a>
    </x-admin.page-header>
@endsection

@section('content')
    <x-admin.card class="overflow-hidden">
        <ul class="divide-y divide-slate-100">
            @foreach ($rows as $row)
                <li>
                    <a href="{{ route('pages.content.section', [$page, $row['key']]) }}" @class(['flex items-center gap-4 px-6 py-5 transition hover:bg-slate-50/70', 'opacity-60' => !$row['on']])>
                        <span class="w-5 shrink-0 text-center text-sm font-semibold text-slate-400">{{ $loop->iteration }}</span>
                        <span class="grid size-11 shrink-0 place-items-center rounded-xl bg-violet-100 text-violet-600">{!! \App\Support\FeatureIcons::svg($row['icon'], 'size-5') !!}</span>
                        <span class="min-w-0 flex-1">
                            <span class="flex flex-wrap items-center gap-2">
                                <span class="font-semibold text-slate-900">{{ __($row['name']) }}</span>
                                @unless ($row['on'])
                                    <span class="rounded-md bg-slate-100 px-1.5 py-0.5 text-[11px] font-semibold text-slate-500">{{ __('Sitede kapalı') }}</span>
                                @endunless
                            </span>
                            <span class="mt-0.5 block truncate text-sm text-slate-500">{{ $row['title'] ?: __($row['hint']) }}</span>
                        </span>
                        <span class="hidden shrink-0 sm:block">
                            @if (!$row['written'])
                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-500">{{ __('Varsayılan metin') }}</span>
                            @elseif ($row['pending'])
                                <span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">{{ __('Çeviri bekliyor') }}</span>
                            @else
                                <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">{{ __('✓ 12 dil güncel') }}</span>
                            @endif
                        </span>
                        <span class="shrink-0 text-sm font-semibold text-brand-900">{{ __('Düzenle') }} →</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </x-admin.card>
    <p class="mt-4 text-sm text-slate-500">{{ __('Bölümlerin sırasını ve sitede görünüp görünmeyeceğini “Sıra ve aç/kapa” ekranından ayarlarsın.') }}</p>
@endsection
