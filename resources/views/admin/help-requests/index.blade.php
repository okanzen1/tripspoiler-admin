@extends('layouts.admin')

@section('title', 'Yardım talepleri')

{{-- Sitedeki "Plan your trip" formundan gelen talepler. Veriler şifreli saklanır, burada çözülerek gösterilir. --}}
@section('header')
    <x-admin.page-header :title="__('Yardım talepleri')" :description="__('Sitedeki “Plan your trip” formundan gelen talepler. Ad, e-posta ve cevaplar şifreli saklanır; 12 ay sonra kendiliğinden silinir.')">
        <x-slot:icon><path d="M9 11a3 3 0 1 0 6 0 3 3 0 0 0-6 0Z" /><path d="M17.8 17.8A8.5 8.5 0 1 1 20.5 12c0 1.5-.4 3-1.1 4.2L21 21l-4.8-1.6" /></x-slot:icon>
    </x-admin.page-header>
@endsection

@php
    $tones = ['new' => 'bg-pink-50 text-pink-700 ring-pink-200', 'answered' => 'bg-emerald-50 text-emerald-700 ring-emerald-200', 'closed' => 'bg-slate-100 text-slate-600 ring-slate-200'];
@endphp

@section('content')
    <div class="mb-5 grid gap-4 sm:grid-cols-3">
        @foreach (\App\Models\HelpRequest::STATUSES as $key => $label)
            <a href="{{ route('help-requests.index', ['status' => $key]) }}" class="block">
                <x-admin.card class="p-5 transition hover:ring-slate-300">
                    <p class="text-sm text-slate-500">{{ __($label) }}</p>
                    <p class="mt-1 text-2xl font-semibold tabular-nums text-slate-900">{{ number_format($counts[$key] ?? 0, 0, ',', '.') }}</p>
                </x-admin.card>
            </a>
        @endforeach
    </div>

    @if ($topCities->isNotEmpty() || $topNeeds->isNotEmpty())
        <x-admin.card class="mb-5 grid gap-5 p-5 md:grid-cols-2">
            @foreach ([__('En çok istenen şehirler (90 gün)') => $topCities, __('En çok istenen yardımlar (90 gün)') => $topNeeds] as $title => $items)
                <div>
                    <p class="text-sm font-semibold text-slate-900">{{ $title }}</p>
                    <div class="mt-2.5 flex flex-wrap gap-2">
                        @forelse ($items as $name => $n)
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-50 px-3 py-1 text-sm text-slate-700 ring-1 ring-slate-200">{{ $name }} <span class="font-semibold tabular-nums text-slate-900">{{ $n }}</span></span>
                        @empty
                            <span class="text-sm text-slate-400">—</span>
                        @endforelse
                    </div>
                </div>
            @endforeach
        </x-admin.card>
    @endif

    <x-admin.card class="overflow-hidden">
        <nav class="flex flex-wrap gap-1.5 border-b border-slate-100 px-4 py-3" aria-label="{{ __('Durum') }}">
            @foreach (['' => [__('Tümü'), $total]] + collect(\App\Models\HelpRequest::STATUSES)->map(fn ($l, $k) => [__($l), $counts[$k] ?? 0])->all() as $key => [$label, $n])
                <a href="{{ route('help-requests.index', array_filter(['status' => $key])) }}"
                    @class(['rounded-lg px-3 py-1.5 text-sm font-medium transition', 'bg-brand-900 text-white' => (string) $status === (string) $key, 'text-slate-600 hover:bg-slate-100' => (string) $status !== (string) $key])>
                    {{ $label }} <span class="ms-1 tabular-nums opacity-70">{{ $n }}</span>
                </a>
            @endforeach
        </nav>

        @if ($requests->isEmpty())
            <div class="px-6 py-16 text-center">
                <div class="mx-auto grid size-12 place-items-center rounded-2xl bg-pink-50 text-pink-700"><x-admin.icon name="mail" class="size-6" /></div>
                <p class="mt-4 text-sm font-semibold text-slate-900">{{ __('Henüz talep yok') }}</p>
                <p class="mt-1 text-sm text-slate-500">{{ __('Sitedeki “Plan your trip” sayfasından gelen talepler burada görünür.') }}</p>
            </div>
        @else
            <ul class="divide-y divide-slate-100">
                @foreach ($requests as $item)
                    @php $a = (array) $item->answers; @endphp
                    <li class="relative flex items-center gap-4 px-6 py-4 transition hover:bg-slate-50/60">
                        <span class="grid size-10 shrink-0 place-items-center rounded-full bg-pink-50 text-sm font-semibold uppercase text-pink-700">{{ mb_substr($item->name, 0, 1) }}</span>
                        <div class="min-w-0 flex-1">
                            <a href="{{ route('help-requests.show', $item) }}" class="block truncate font-semibold text-slate-900 after:absolute after:inset-0 hover:text-brand-900">{{ $item->name }}</a>
                            <p class="mt-0.5 truncate text-sm text-slate-500">
                                {{ implode(', ', (array) ($a['city_names'] ?? [])) ?: '—' }}
                                @if (!empty($a['days'])) · {{ __(':n gün', ['n' => $a['days']]) }} @endif
                                @if (!empty($a['group'])) · {{ \App\Models\HelpRequest::label('group', $a['group']) }} @endif
                                · {{ implode(', ', \App\Models\HelpRequest::label('needs', (array) ($a['needs'] ?? []))) }}
                            </p>
                        </div>
                        <span class="hidden shrink-0 text-xs text-slate-500 sm:block" title="{{ $item->created_at->format('d.m.Y H:i') }}">{{ $item->created_at->diffForHumans() }}</span>
                        <span class="shrink-0 rounded-md px-2 py-0.5 text-xs font-semibold ring-1 {{ $tones[$item->status] ?? $tones['closed'] }}">{{ __(\App\Models\HelpRequest::STATUSES[$item->status] ?? $item->status) }}</span>
                    </li>
                @endforeach
            </ul>
            @if ($requests->hasPages())
                <div class="border-t border-slate-100 px-6 py-3.5">{{ $requests->links() }}</div>
            @endif
        @endif
    </x-admin.card>
@endsection
