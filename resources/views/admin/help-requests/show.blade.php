@extends('layouts.admin')

@section('title', 'Yardım talebi')

@php
    use App\Models\HelpRequest as H;
    $a = (array) $item->answers;
    $tones = ['new' => 'bg-pink-50 text-pink-700 ring-pink-200', 'answered' => 'bg-emerald-50 text-emerald-700 ring-emerald-200', 'closed' => 'bg-slate-100 text-slate-600 ring-slate-200'];
    $dates = match (true) {
        !empty($a['start_date']) => trim(($a['start_date'] ?? '') . (!empty($a['end_date']) ? ' → ' . $a['end_date'] : '')),
        !empty($a['month']) => $a['month'],
        default => null,
    };
    $sections = [
        __('Yolculuk') => [
            __('Şehir') => implode(', ', (array) ($a['city_names'] ?? [])) ?: null,
            __('Tarih') => $dates ?? __('Henüz belli değil'),
            __('Kaç gün') => !empty($a['days']) ? __(':n gün', ['n' => $a['days']]) : null,
            __('Daha önce') => H::label('first_time', $a['first_time'] ?? null),
        ],
        __('Kim gidiyor') => [
            __('Grup') => H::label('group', $a['group'] ?? null),
            __('Yetişkin') => $a['adults'] ?? null,
            __('Çocuk') => !empty($a['children']) ? $a['children'] . (!empty($a['child_ages']) ? ' (' . __('yaşlar: :ages', ['ages' => $a['child_ages']]) . ')' : '') : null,
            __('Özel ihtiyaç') => H::label('access', (array) ($a['access'] ?? [])),
        ],
        __('Neye yardım lazım') => [
            __('İhtiyaçlar') => H::label('needs', (array) ($a['needs'] ?? [])),
            __('Konaklama: semt') => H::label('stay_vibe', $a['stay_vibe'] ?? null),
            __('Konaklama: bütçe') => H::label('stay_budget', $a['stay_budget'] ?? null),
            __('Konaklama: istekler') => H::label('stay_extras', (array) ($a['stay_extras'] ?? [])),
            __('Yemek') => H::label('food', (array) ($a['food'] ?? [])),
            __('Özel gün') => H::label('occasion', $a['occasion'] ?? null),
        ],
        __('Tarzı') => [
            __('Tempo') => H::label('pace', $a['pace'] ?? null),
            __('İlgi alanları') => H::label('interests', (array) ($a['interests'] ?? [])),
            __('Bütçe anlayışı') => H::label('budget', $a['budget'] ?? null),
        ],
    ];
@endphp

@section('header')
    <x-admin.page-header :title="$item->name" :description="__('Gönderildi: :date · Site dili: :locale', ['date' => $item->created_at->format('d.m.Y H:i'), 'locale' => strtoupper($item->locale)])"
        :breadcrumb="['Yardım talepleri' => route('help-requests.index')]">
        <x-slot:icon><path d="M9 11a3 3 0 1 0 6 0 3 3 0 0 0-6 0Z" /><path d="M17.8 17.8A8.5 8.5 0 1 1 20.5 12c0 1.5-.4 3-1.1 4.2L21 21l-4.8-1.6" /></x-slot:icon>
        <a href="{{ route('mailbox.compose', ['to' => $item->email]) }}"
            class="inline-flex items-center gap-2 rounded-xl bg-brand-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-800">
            <x-admin.icon name="mail" /> {{ __('Cevapla') }}
        </a>
    </x-admin.page-header>
@endsection

@section('content')
    <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_20rem]">
        <div class="space-y-5">
            @if (filled($a['note'] ?? null))
                <x-admin.card class="p-6">
                    <p class="text-sm font-semibold text-slate-900">{{ __('Misafirin notu') }}</p>
                    <p class="mt-2 whitespace-pre-line text-[15px] leading-relaxed text-slate-700">{{ $a['note'] }}</p>
                </x-admin.card>
            @endif

            @foreach ($sections as $title => $rows)
                @php $rows = array_filter($rows, fn ($v) => filled($v) && $v !== []); @endphp
                @continue(empty($rows))
                <x-admin.card class="overflow-hidden">
                    <p class="border-b border-slate-100 px-6 py-3.5 text-sm font-semibold text-slate-900">{{ $title }}</p>
                    <dl class="divide-y divide-slate-100">
                        @foreach ($rows as $label => $value)
                            <div class="grid gap-1 px-6 py-3 sm:grid-cols-[12rem_minmax(0,1fr)]">
                                <dt class="text-sm text-slate-500">{{ $label }}</dt>
                                <dd class="text-sm text-slate-900">
                                    @if (is_array($value))
                                        <span class="flex flex-wrap gap-1.5">@foreach ($value as $v)<span class="rounded-full bg-slate-50 px-2.5 py-0.5 ring-1 ring-slate-200">{{ $v }}</span>@endforeach</span>
                                    @else
                                        {{ $value }}
                                    @endif
                                </dd>
                            </div>
                        @endforeach
                    </dl>
                </x-admin.card>
            @endforeach
        </div>

        <aside class="space-y-5">
            <x-admin.card class="p-5">
                <p class="text-sm font-semibold text-slate-900">{{ __('İletişim') }}</p>
                <p class="mt-2 break-all text-sm text-slate-700">{{ $item->email }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ $item->newsletter ? __('Bültene katılmak istedi') : __('Bülten istemedi') }}</p>
            </x-admin.card>

            <x-admin.card class="p-5">
                <p class="text-sm font-semibold text-slate-900">{{ __('Durum') }}</p>
                <span class="mt-2 inline-block rounded-md px-2 py-0.5 text-xs font-semibold ring-1 {{ $tones[$item->status] ?? $tones['closed'] }}">{{ __(H::STATUSES[$item->status] ?? $item->status) }}</span>
                @if ($item->answered_at)<p class="mt-1 text-xs text-slate-500">{{ __('Cevaplandı: :date', ['date' => $item->answered_at->format('d.m.Y H:i')]) }}</p>@endif
                <div class="mt-4 grid gap-2">
                    @foreach (H::STATUSES as $key => $label)
                        @continue($key === $item->status)
                        <form method="POST" action="{{ route('help-requests.update', $item) }}">
                            @csrf @method('PATCH')
                            <input type="hidden" name="status" value="{{ $key }}">
                            <button class="w-full rounded-xl px-3 py-2 text-sm font-medium text-slate-700 ring-1 ring-slate-200 transition hover:bg-slate-50">{{ __(':status olarak işaretle', ['status' => __($label)]) }}</button>
                        </form>
                    @endforeach
                </div>
            </x-admin.card>

            @if ($others->isNotEmpty())
                <x-admin.card class="p-5">
                    <p class="text-sm font-semibold text-slate-900">{{ __('Aynı kişinin diğer talepleri') }}</p>
                    <ul class="mt-2 space-y-1.5">
                        @foreach ($others as $other)
                            <li><a href="{{ route('help-requests.show', $other) }}" class="text-sm text-brand-900 hover:underline">{{ $other->created_at->format('d.m.Y') }} · {{ implode(', ', (array) ($other->answers['city_names'] ?? [])) }}</a></li>
                        @endforeach
                    </ul>
                </x-admin.card>
            @endif

            <form method="POST" action="{{ route('help-requests.destroy', $item) }}" data-confirm="{{ __('Bu talep kalıcı olarak silinsin mi? Geri alınamaz.') }}">
                @csrf @method('DELETE')
                <button class="w-full rounded-xl px-3 py-2.5 text-sm font-semibold text-red-600 ring-1 ring-red-200 transition hover:bg-red-50">{{ __('Talebi sil') }}</button>
            </form>
        </aside>
    </div>
@endsection
