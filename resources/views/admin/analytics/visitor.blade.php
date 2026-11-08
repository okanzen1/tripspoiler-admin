@extends('layouts.admin')

@php
    use App\Services\Analytics;

    $last = $visits->first() ?? $clicks->first();
    $first = $visits->last() ?? $clicks->last();
    $countryName = fn (?string $code) => $code ? (\Locale::getDisplayRegion("-{$code}", app()->getLocale()) ?: $code) : __('Bilinmiyor');
    $deviceName = ['mobile' => __('Telefon'), 'tablet' => __('Tablet'), 'desktop' => __('Bilgisayar')];
    $online = \Carbon\Carbon::parse($last->created_at)->gte(now()->subMinutes(5));
    $browsers = $visits->pluck('visitor_id')->unique()->count();
    $languages = $visits->pluck('locale')->filter()->unique()->map(fn ($l) => strtoupper($l))->implode(', ');
    $title = $last->ip ?? __('Ziyaretçi');
@endphp

@section('title', $title)

@section('header')
    <x-admin.page-header :title="$title" :breadcrumb="['Analitik' => route('analytics.index')]"
        :description="Analytics::flag($last->country) . ' ' . collect([$last->city, $countryName($last->country)])->filter()->implode(', ')">
        <x-slot:icon><circle cx="12" cy="8" r="4" /><path d="M4 21c1-4 4-6 8-6s7 2 8 6" /></x-slot:icon>
    </x-admin.page-header>
@endsection

@section('content')
    {{-- Özet --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-admin.card class="p-5">
            <p class="text-sm font-medium text-slate-500">{{ __('Durum') }}</p>
            <p class="mt-2 flex items-center gap-2 text-lg font-semibold text-slate-900">
                <span class="size-2.5 rounded-full {{ $online ? 'bg-emerald-500' : 'bg-slate-300' }}"></span>
                {{ $online ? __('Şu an sitede') : __('Çevrimdışı') }}
            </p>
            <p class="mt-1 text-xs text-slate-500">{{ __('Son görülme') }}: {{ \Carbon\Carbon::parse($last->created_at)->translatedFormat('j F H:i') }}</p>
        </x-admin.card>
        <x-admin.card class="p-5">
            <p class="text-sm font-medium text-slate-500">{{ __('Gezinti') }}</p>
            <p class="mt-2 text-lg font-semibold tabular-nums text-slate-900">
                {{ trans_choice(':count sayfa|:count sayfa', $visits->count(), ['count' => $visits->count()]) }} ·
                {{ trans_choice(':count oturum|:count oturum', $sessions->count(), ['count' => $sessions->count()]) }}
            </p>
            <p class="mt-1 text-xs text-slate-500">{{ __('İlk geliş') }}: {{ \Carbon\Carbon::parse($first->created_at)->translatedFormat('j F Y H:i') }}</p>
        </x-admin.card>
        <x-admin.card class="p-5">
            <p class="text-sm font-medium text-slate-500">{{ __('Partner tıklaması') }}</p>
            <p class="mt-2 text-lg font-semibold tabular-nums {{ $clicks->count() ? 'text-emerald-700' : 'text-slate-900' }}">{{ $clicks->count() }}</p>
            <p class="mt-1 truncate text-xs text-slate-500">{{ $clicks->pluck('partner')->unique()->implode(', ') ?: __('Henüz tıklamadı') }}</p>
        </x-admin.card>
        <x-admin.card class="p-5">
            <p class="text-sm font-medium text-slate-500">{{ __('Cihaz') }}</p>
            <p class="mt-2 text-lg font-semibold text-slate-900">{{ $deviceName[$last->device] ?? '—' }}</p>
            <p class="mt-1 truncate text-xs text-slate-500">
                {{ collect([$last->browser, $last->os])->filter()->implode(' · ') }}
                @if ($browsers > 1) · {{ __(':count farklı tarayıcı', ['count' => $browsers]) }} @endif
                @if ($languages) · {{ $languages }} @endif
            </p>
        </x-admin.card>
    </div>

    {{-- Oturumlar: en yenisi üstte --}}
    @foreach ($sessions as $items)
        @php
            $start = \Carbon\Carbon::parse($items->first()->created_at);
            $end = \Carbon\Carbon::parse($items->last()->created_at);
            $entry = $items->first(fn ($i) => empty($i->is_click));
            $minutes = max(1, (int) round($start->diffInMinutes($end)));
        @endphp
        <h2 class="mb-2 mt-7 flex flex-wrap items-baseline gap-x-2 px-1 text-xs font-bold uppercase tracking-[0.14em] text-slate-400">
            {{ $start->isToday() ? __('Bugün') : ($start->isYesterday() ? __('Dün') : $start->translatedFormat('j F Y')) }}
            <span class="font-medium normal-case tracking-normal text-slate-500">
                {{ $start->format('H:i') }}–{{ $end->format('H:i') }} · {{ __(':count dk', ['count' => $minutes]) }}
                @if ($entry)
                    · {{ __('Kaynak') }}: {{ $entry->utm_source ?: ($entry->referrer_host ?: __('Doğrudan')) }}
                @endif
            </span>
        </h2>
        <x-admin.card class="overflow-hidden">
            <ol class="divide-y divide-slate-100">
                @foreach ($items as $item)
                    <li class="flex items-center gap-3 px-5 py-2.5 text-sm">
                        <span class="w-16 shrink-0 tabular-nums text-slate-500">{{ \Carbon\Carbon::parse($item->created_at)->format('H:i:s') }}</span>
                        @if (!empty($item->is_click))
                            <span class="grid size-7 shrink-0 place-items-center rounded-lg bg-emerald-500 text-white" title="{{ __('Partner tıklaması') }}">
                                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 17 17 7M8 7h9v9" /></svg>
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="font-semibold text-emerald-700">{{ __(':partner sitesine gitti', ['partner' => \Illuminate\Support\Str::headline($item->partner)]) }}</span>
                                <span class="block truncate text-xs text-slate-400" title="{{ $item->target_url }}">{{ $item->target_url }}</span>
                            </span>
                        @else
                            <span class="grid size-7 shrink-0 place-items-center rounded-lg bg-slate-100 text-slate-500">
                                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8Z" /><path d="M14 3v5h5" /></svg>
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate font-medium text-slate-800">{{ $item->path }}</span>
                                @if ($item->utm_source || $item->referrer_host)
                                    <span class="block truncate text-xs text-slate-400">{{ __('Geldiği yer') }}: {{ $item->utm_source ?: $item->referrer_host }}</span>
                                @endif
                            </span>
                            <span class="hidden shrink-0 text-xs uppercase text-slate-400 sm:block">{{ $item->locale }}</span>
                        @endif
                    </li>
                @endforeach
            </ol>
        </x-admin.card>
    @endforeach
@endsection
