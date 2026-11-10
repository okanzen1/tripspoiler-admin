@extends('layouts.admin')

@section('title', 'İşlem kaydı')

@php
    use App\Http\Controllers\ActivityLogController;
    use App\Support\ActivityLogger;

    $actions = ActivityLogController::ACTIONS;
    $badge = [
        'emerald' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'sky' => 'bg-sky-50 text-sky-700 ring-sky-200',
        'rose' => 'bg-rose-50 text-rose-700 ring-rose-200',
        'purple' => 'bg-purple-50 text-purple-700 ring-purple-200',
        'slate' => 'bg-slate-100 text-slate-600 ring-slate-200',
        'amber' => 'bg-amber-50 text-amber-800 ring-amber-200',
        'indigo' => 'bg-indigo-50 text-indigo-700 ring-indigo-200',
    ];
    $device = fn (?string $agent) => match (true) {
        !$agent => null,
        (bool) preg_match('/ipad|tablet/i', $agent) => __('Tablet'),
        (bool) preg_match('/mobi|iphone|android/i', $agent) => __('Telefon'),
        default => __('Bilgisayar'),
    };
    $field = 'block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none transition focus:border-brand-900 focus:ring-4 focus:ring-brand-900/10';
    $filtered = collect($filters)->filter()->isNotEmpty();
@endphp

@section('header')
    <x-admin.page-header :title="__('İşlem kaydı')" :breadcrumb="['Yönetim' => route('management.index')]"
        :description="__('Panelde kim, ne zaman, neyi değiştirdi: ekleme, güncelleme, silme, çeviri ve girişler. 1 yıl saklanır.')">
        <x-slot:icon><path d="M12 8v4l3 2" /><circle cx="12" cy="12" r="9" /></x-slot:icon>
    </x-admin.page-header>
@endsection

@section('content')
    {{-- Özet --}}
    <div class="mb-5 flex flex-wrap items-center gap-2 text-sm">
        <span class="rounded-full bg-white px-3.5 py-1.5 font-medium text-slate-700 ring-1 ring-slate-200">{{ __('Bugün :count işlem', ['count' => $todayCount]) }}</span>
        <a href="{{ route('activity-logs.index', ['action' => 'login_failed']) }}"
            class="rounded-full px-3.5 py-1.5 font-medium ring-1 transition {{ $failedCount ? 'bg-amber-50 text-amber-800 ring-amber-200 hover:bg-amber-100' : 'bg-white text-slate-500 ring-slate-200' }}">
            {{ __('Son 7 günde :count başarısız giriş', ['count' => $failedCount]) }}
        </a>
    </div>

    {{-- Filtreler --}}
    <x-admin.card class="mb-5 p-4">
        <form method="GET" action="{{ route('activity-logs.index') }}" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-6">
            <div class="relative sm:col-span-2">
                <svg class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7" /><path d="M20 20l-3.5-3.5" /></svg>
                <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="{{ __('Ara: kayıt adı, kişi, IP, değişen yazı…') }}" class="{{ $field }} pl-9">
            </div>
            <select name="user" class="{{ $field }}">
                <option value="">{{ __('Herkes') }}</option>
                @foreach ($users as $user)
                    <option value="{{ $user->id }}" @selected(($filters['user'] ?? null) == $user->id)>{{ $user->full_name }}</option>
                @endforeach
            </select>
            <select name="action" class="{{ $field }}">
                <option value="">{{ __('Tüm işlemler') }}</option>
                @foreach ($actions as $key => [$verb])
                    <option value="{{ $key }}" @selected(($filters['action'] ?? null) === $key)>{{ \Illuminate\Support\Str::ucfirst(__($verb)) }}</option>
                @endforeach
            </select>
            <select name="type" class="{{ $field }}">
                <option value="">{{ __('Her yer') }}</option>
                @foreach (collect(ActivityLogger::TYPES)->sortBy(fn ($t) => $t[0]) as $key => [$label])
                    <option value="{{ $key }}" @selected(($filters['type'] ?? null) === $key)>{{ __($label) }}</option>
                @endforeach
            </select>
            <div class="flex gap-2">
                <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="{{ $field }} min-w-0" title="{{ __('Başlangıç') }}">
                <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="{{ $field }} min-w-0" title="{{ __('Bitiş') }}">
            </div>
            <div class="flex items-center gap-2 lg:col-span-6">
                <button type="submit" class="rounded-xl bg-brand-900 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-800">{{ __('Filtrele') }}</button>
                @if ($filtered)
                    <a href="{{ route('activity-logs.index') }}" class="rounded-xl px-3 py-2 text-sm font-medium text-slate-500 hover:bg-slate-100">{{ __('Temizle') }}</a>
                @endif
                <span class="ml-auto text-xs text-slate-500">{{ __(':count kayıt', ['count' => number_format($logs->total(), 0, ',', '.')]) }}</span>
            </div>
        </form>
    </x-admin.card>

    {{-- Kayıtlar (güne göre) --}}
    @forelse ($logs->groupBy(fn ($log) => $log->created_at->toDateString()) as $day => $dayLogs)
        @php $date = \Carbon\Carbon::parse($day); @endphp
        <h2 class="mb-2 mt-6 px-1 text-xs font-bold uppercase tracking-[0.14em] text-slate-400 first:mt-0">
            {{ $date->isToday() ? __('Bugün') : ($date->isYesterday() ? __('Dün') : $date->translatedFormat('j F Y, l')) }}
        </h2>
        <x-admin.card class="overflow-hidden">
            <ul class="divide-y divide-slate-100">
                @foreach ($dayLogs as $log)
                    @php
                        [$verb, $color] = $actions[$log->action] ?? [$log->action, 'slate'];
                        $typeLabel = ActivityLogger::TYPES[$log->subject_type][0] ?? null;
                        $url = $log->action !== 'deleted' ? ActivityLogger::url($log->subject_type, $log->subject_id) : null;
                        $changes = $log->changes ?? [];
                        $name = $log->user?->full_name ?? $log->user_name;
                    @endphp
                    <li>
                        <details class="group/log">
                            <summary @class(['flex cursor-default list-none items-start gap-3 px-4 py-3 sm:px-5', 'cursor-pointer hover:bg-slate-50' => $changes])>
                                {{-- kim --}}
                                @if ($log->user)
                                    <x-admin.avatar :user="$log->user" class="mt-0.5 size-9 text-xs" />
                                @else
                                    <span class="mt-0.5 grid size-9 shrink-0 place-items-center rounded-full bg-slate-100 text-slate-400">
                                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="8" r="4" /><path d="M4 21c1-4 4-6 8-6s7 2 8 6" /></svg>
                                    </span>
                                @endif

                                {{-- ne yaptı --}}
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm leading-relaxed text-slate-700">
                                        <span class="font-semibold text-slate-900">{{ $name ?: __('Bilinmeyen') }}</span>
                                        <span class="mx-1 inline-flex items-center rounded-md px-1.5 py-0.5 text-xs font-semibold ring-1 {{ $badge[$color] }}">{{ __($verb) }}</span>
                                        @if ($typeLabel)
                                            <span class="text-slate-500">{{ __($typeLabel) }}</span>
                                        @endif
                                        @if ($log->subject_label)
                                            @if ($url)
                                                <a href="{{ $url }}" class="font-medium text-brand-900 underline decoration-brand-200 underline-offset-2 hover:decoration-brand-900" onclick="event.stopPropagation()">“{{ $log->subject_label }}”</a>
                                            @else
                                                <span class="font-medium text-slate-900">“{{ $log->subject_label }}”</span>
                                            @endif
                                        @endif
                                        @if ($log->action === 'login_failed' && $log->subject_label)
                                            <span class="text-slate-500">{{ __('(girilen e-posta)') }}</span>
                                        @endif
                                    </p>
                                    <p class="mt-0.5 flex flex-wrap items-center gap-x-2 text-xs text-slate-400">
                                        @if ($changes)
                                            <span class="font-medium text-slate-500">
                                                {{ trans_choice(':count alan değişti|:count alan değişti', count($changes), ['count' => count($changes)]) }}:
                                                {{ \Illuminate\Support\Str::limit(collect(array_keys($changes))->map(fn ($f) => ActivityLogController::field($f))->implode(', '), 90) }}
                                            </span>
                                            <span class="text-brand-900 group-open/log:hidden">{{ __('Ayrıntı') }} ▾</span>
                                            <span class="hidden text-brand-900 group-open/log:inline">{{ __('Gizle') }} ▴</span>
                                        @endif
                                    </p>
                                </div>

                                {{-- ne zaman, nereden --}}
                                <div class="shrink-0 text-right">
                                    <p class="text-sm font-medium tabular-nums text-slate-700" title="{{ $log->created_at->translatedFormat('j F Y H:i:s') }}">{{ $log->created_at->format('H:i') }}</p>
                                    <p class="text-[11px] text-slate-400">{{ collect([$device($log->user_agent), $log->ip])->filter()->implode(' · ') }}</p>
                                </div>
                            </summary>

                            {{-- değişiklikler: eski → yeni --}}
                            @if ($changes)
                                <div class="border-t border-slate-100 bg-slate-50/70 px-4 py-3 sm:pl-[4.25rem] sm:pr-5">
                                    <table class="w-full table-fixed text-left text-xs">
                                        <thead>
                                            <tr class="text-[11px] uppercase tracking-wider text-slate-400">
                                                <th class="w-1/5 pb-1.5 font-semibold">{{ __('Alan') }}</th>
                                                <th class="w-2/5 pb-1.5 font-semibold">{{ __('Eski') }}</th>
                                                <th class="w-2/5 pb-1.5 font-semibold">{{ __('Yeni') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody class="align-top">
                                            @foreach ($changes as $fieldName => [$old, $new])
                                                <tr class="border-t border-slate-200/70">
                                                    <td class="py-1.5 pr-3 font-medium text-slate-700">{{ ActivityLogController::field($fieldName) }}</td>
                                                    <td class="break-words py-1.5 pr-3 text-rose-700"><span class="{{ $old !== null ? 'line-through decoration-rose-300' : 'text-slate-400' }}">{{ $old ?? '—' }}</span></td>
                                                    <td class="break-words py-1.5 text-emerald-700">{{ $new ?? '—' }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                    @if ($log->url)
                                        <p class="mt-2 truncate text-[11px] text-slate-400">{{ __('Adres') }}: {{ $log->url }}</p>
                                    @endif
                                </div>
                            @endif
                        </details>
                    </li>
                @endforeach
            </ul>
        </x-admin.card>
    @empty
        <x-admin.card class="p-12 text-center">
            <p class="text-sm font-semibold text-slate-900">{{ $filtered ? __('Bu filtreye uyan işlem yok.') : __('Henüz kayıtlı işlem yok.') }}</p>
            <p class="mt-1 text-sm text-slate-500">{{ __('Panelde yapılan her değişiklik burada görünecek.') }}</p>
        </x-admin.card>
    @endforelse

    @if ($logs->hasPages())
        <div class="mt-5">{{ $logs->links() }}</div>
    @endif
@endsection
