@extends('layouts.admin')

@section('title', 'Arka plan işleri')

@php
    $status = [
        'running' => ['Sürüyor', 'bg-sky-50 text-sky-700 ring-sky-200'],
        'done' => ['Bitti', 'bg-emerald-50 text-emerald-700 ring-emerald-200'],
        'failed' => ['Başarısız', 'bg-red-50 text-red-700 ring-red-200'],
    ];
    $busy = collect($translations)->contains('status', 'running') || $pendingTotal > 0;
@endphp

@section('header')
    <x-admin.page-header :title="__('Arka plan işleri')" :breadcrumb="['Yönetim' => route('management.index')]"
        :description="__('Arka planda yapılan işler (çeviriler vb.): ne sürüyor, ne sırada bekliyor, ne başarısız oldu. Sayfa açıkken kendini yeniler.')">
        <x-slot:icon><path d="M4 6h16M4 12h10M4 18h7" /><circle cx="18" cy="16" r="3" /><path d="M18 14.5V16l1 1" /></x-slot:icon>
    </x-admin.page-header>
@endsection

@section('content')
    <div class="space-y-6" data-live="queue-page" data-busy="{{ $busy ? '1' : '0' }}">

        {{-- Durum özeti --}}
        <div class="grid gap-4 sm:grid-cols-3">
            <x-admin.card class="p-5">
                <p class="text-sm font-medium text-slate-500">{{ __('Zamanlayıcı (cron)') }}</p>
                <p class="mt-2 flex items-center gap-2 text-lg font-semibold {{ $schedulerOk ? 'text-emerald-700' : 'text-red-700' }}">
                    <span class="size-2.5 rounded-full {{ $schedulerOk ? 'bg-emerald-500' : 'bg-red-500' }}"></span>
                    {{ $schedulerOk ? __('Çalışıyor') : __('Çalışmıyor') }}
                </p>
                <p class="mt-1 text-xs text-slate-500">
                    {{ $lastRun ? __('Son çalışma: :time', ['time' => $lastRun->diffForHumans()]) : __('Hiç çalışmamış.') }}
                </p>
            </x-admin.card>
            <x-admin.card class="p-5">
                <p class="text-sm font-medium text-slate-500">{{ __('Sırada bekleyen') }}</p>
                <p class="mt-2 text-3xl font-semibold tabular-nums text-slate-900">{{ $pendingTotal }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ __('Her dakika otomatik işlenir.') }}</p>
            </x-admin.card>
            <x-admin.card class="p-5">
                <p class="text-sm font-medium text-slate-500">{{ __('Başarısız') }}</p>
                <p class="mt-2 text-3xl font-semibold tabular-nums {{ $failedTotal ? 'text-red-700' : 'text-slate-900' }}">{{ $failedTotal }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ $failedTotal ? __('Aşağıda nedenleriyle; tekrar deneyebilirsin.') : __('Sorun yok.') }}</p>
            </x-admin.card>
        </div>

        @unless ($schedulerOk)
            <div class="rounded-2xl bg-red-50 px-5 py-4 text-sm text-red-800 ring-1 ring-red-200">
                <p class="font-semibold">{{ __('Zamanlayıcı çalışmıyor: sıradaki işler kendiliğinden işlenmez.') }}</p>
                <p class="mt-1">{{ __('Hosting panelinde Cron Jobs bölümüne her dakika çalışan şu komutu ekle:') }}</p>
                <code class="mt-2 block overflow-x-auto rounded-lg bg-white px-3 py-2 font-mono text-xs text-slate-800 ring-1 ring-red-200">cd {{ base_path() }} && php artisan schedule:run</code>
                <p class="mt-2 text-xs">{{ __('O zamana kadar sıradaki işleri aşağıdaki "Şimdi işle" butonuyla elle işleyebilirsin.') }}</p>
            </div>
        @endunless

        {{-- Çeviriler (son 1 saat) --}}
        <x-admin.card class="overflow-hidden">
            <div class="px-6 pb-3 pt-6">
                <h2 class="text-sm font-semibold text-slate-900">{{ __('Çeviriler') }}</h2>
                <p class="text-xs text-slate-500">{{ __('Panelden başlatılan çeviriler (son 1 saat).') }}</p>
            </div>
            <ul class="divide-y divide-slate-100 border-t border-slate-100">
                @forelse ($translations as $job)
                    @php [$label, $badge] = $status[$job['status']] ?? [$job['status'], 'bg-slate-100 text-slate-600 ring-slate-200']; @endphp
                    <li class="flex items-center gap-4 px-6 py-3">
                        <span class="shrink-0 rounded-md px-2 py-0.5 text-xs font-semibold ring-1 {{ $badge }}">{{ __($label) }}</span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium text-slate-900">{{ $job['title'] ?: $job['key'] }}</p>
                            <p @class(['truncate text-xs', 'text-red-700' => $job['status'] === 'failed', 'text-slate-500' => $job['status'] !== 'failed'])>
                                {{ $job['status'] === 'running' ? __(':done / :total dil', ['done' => $job['done'], 'total' => $job['total']]) : $job['message'] }}
                            </p>
                        </div>
                        @if ($job['status'] === 'running' && $job['total'])
                            <div class="hidden h-1.5 w-32 overflow-hidden rounded-full bg-sky-100 sm:block">
                                <div class="h-full rounded-full bg-sky-500" style="width: {{ max(4, round($job['done'] / $job['total'] * 100)) }}%"></div>
                            </div>
                        @endif
                        <span class="shrink-0 text-xs tabular-nums text-slate-400">{{ now()->setTimestamp($job['started_at'])->format('H:i') }}</span>
                        @if ($job['url'])
                            <a href="{{ $job['url'] }}" class="shrink-0 text-xs font-semibold text-brand-900 hover:underline">{{ __('Aç') }}</a>
                        @endif
                    </li>
                @empty
                    <li class="px-6 py-8 text-center text-sm text-slate-400">{{ __('Son bir saatte çeviri yapılmadı.') }}</li>
                @endforelse
            </ul>
        </x-admin.card>

        {{-- Sırada bekleyen --}}
        <x-admin.card class="overflow-hidden">
            <div class="flex flex-wrap items-center justify-between gap-3 px-6 pb-3 pt-6">
                <div>
                    <h2 class="text-sm font-semibold text-slate-900">{{ __('Sırada bekleyen işler') }}</h2>
                    <p class="text-xs text-slate-500">{{ __('Ör. Diller ve çeviri › toplu çeviri. Zamanlayıcı her dakika işler.') }}</p>
                </div>
                @if ($pendingTotal)
                    <form method="POST" action="{{ route('queue.run') }}">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-brand-900 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-800">
                            <svg class="size-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5.5v13l11-6.5z" /></svg>
                            {{ __('Şimdi işle') }}
                        </button>
                    </form>
                @endif
            </div>
            <ul class="divide-y divide-slate-100 border-t border-slate-100">
                @forelse ($pending as $job)
                    <li class="flex items-center gap-4 px-6 py-3">
                        <span @class(['shrink-0 rounded-md px-2 py-0.5 text-xs font-semibold ring-1', 'bg-sky-50 text-sky-700 ring-sky-200' => $job['running'], 'bg-amber-50 text-amber-800 ring-amber-200' => !$job['running']])>
                            {{ $job['running'] ? __('İşleniyor') : __('Bekliyor') }}
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium text-slate-900">{{ $job['name'] }}@if ($job['subject']) <span class="font-normal text-slate-500">· {{ $job['subject'] }}</span>@endif</p>
                            <p class="truncate text-xs text-slate-500">
                                {{ __(':time sıraya girdi', ['time' => $job['created_at']->diffForHumans()]) }}
                                @if ($job['detail']) · {{ $job['detail'] }} @endif
                                @if ($job['attempts']) · {{ __(':count. deneme', ['count' => $job['attempts'] + 1]) }} @endif
                                @if ($job['available_at']->isFuture()) · {{ __(':time tekrar denenecek', ['time' => $job['available_at']->diffForHumans()]) }} @endif
                            </p>
                        </div>
                        @unless ($job['running'])
                            <form method="POST" action="{{ route('queue.destroy', $job['id']) }}" data-confirm="{{ __('Bu iş sıradan çıkarılsın mı? Yapılmayacak.') }}" data-confirm-title="{{ __('Sıradan çıkarılsın mı?') }}" data-confirm-button="{{ __('Evet, çıkar') }}">
                                @csrf @method('DELETE')
                                <button type="submit" class="rounded-lg p-1.5 text-slate-400 transition hover:bg-red-50 hover:text-red-600" title="{{ __('Sıradan çıkar') }}">
                                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12" /></svg>
                                </button>
                            </form>
                        @endunless
                    </li>
                @empty
                    <li class="px-6 py-8 text-center text-sm text-slate-400">{{ __('Sırada iş yok.') }}</li>
                @endforelse
            </ul>
        </x-admin.card>

        {{-- Başarısız --}}
        <x-admin.card class="overflow-hidden">
            <div class="flex flex-wrap items-center justify-between gap-3 px-6 pb-3 pt-6">
                <div>
                    <h2 class="text-sm font-semibold text-slate-900">{{ __('Başarısız işler') }}</h2>
                    <p class="text-xs text-slate-500">{{ __('Birkaç denemeye rağmen yapılamayanlar. Nedenine bak; tekrar dene ya da sil.') }}</p>
                </div>
                @if ($failedTotal)
                    <div class="flex gap-2">
                        <form method="POST" action="{{ route('queue.retry-all') }}">
                            @csrf
                            <button type="submit" class="rounded-xl bg-brand-900 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-800">{{ __('Hepsini tekrar dene') }}</button>
                        </form>
                        <form method="POST" action="{{ route('queue.flush') }}" data-confirm="{{ __('Başarısız işlerin hepsi silinsin mi? Tekrar denenemezler.') }}" data-confirm-button="{{ __('Evet, hepsini sil') }}">
                            @csrf @method('DELETE')
                            <button type="submit" class="rounded-xl px-4 py-2 text-sm font-semibold text-red-600 ring-1 ring-red-200 transition hover:bg-red-50">{{ __('Hepsini sil') }}</button>
                        </form>
                    </div>
                @endif
            </div>
            <ul class="divide-y divide-slate-100 border-t border-slate-100">
                @forelse ($failed as $job)
                    <li>
                        <details class="group/f">
                            <summary class="flex cursor-pointer list-none items-center gap-4 px-6 py-3 hover:bg-slate-50">
                                <span class="shrink-0 rounded-md bg-red-50 px-2 py-0.5 text-xs font-semibold text-red-700 ring-1 ring-red-200">{{ __('Başarısız') }}</span>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-medium text-slate-900">{{ $job['name'] }}@if ($job['subject']) <span class="font-normal text-slate-500">· {{ $job['subject'] }}</span>@endif</p>
                                    <p class="truncate text-xs text-red-700">{{ $job['error'] }}</p>
                                </div>
                                <span class="shrink-0 text-xs text-slate-400" title="{{ $job['failed_at']->translatedFormat('j F Y H:i') }}">{{ $job['failed_at']->diffForHumans() }}</span>
                                <form method="POST" action="{{ route('queue.retry', $job['uuid']) }}" onclick="event.stopPropagation()">
                                    @csrf
                                    <button type="submit" class="rounded-lg px-3 py-1.5 text-xs font-semibold text-brand-900 ring-1 ring-slate-200 transition hover:bg-white">{{ __('Tekrar dene') }}</button>
                                </form>
                                <form method="POST" action="{{ route('queue.forget', $job['uuid']) }}" onclick="event.stopPropagation()" data-confirm="{{ __('Bu başarısız iş silinsin mi?') }}">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="rounded-lg p-1.5 text-slate-400 transition hover:bg-red-50 hover:text-red-600" title="{{ __('Sil') }}">
                                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 7h16M10 11v6M14 11v6M6 7l1 12a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-12M9 7V4h6v3" /></svg>
                                    </button>
                                </form>
                            </summary>
                            <div class="border-t border-slate-100 bg-slate-50/70 px-6 py-3">
                                <p class="mb-1 text-[11px] font-semibold uppercase tracking-wider text-slate-400">{{ __('Teknik ayrıntı (geliştirici için)') }}</p>
                                <pre class="max-h-64 overflow-auto whitespace-pre-wrap break-words font-mono text-[11px] leading-relaxed text-slate-600">{{ $job['error_full'] }}</pre>
                            </div>
                        </details>
                    </li>
                @empty
                    <li class="px-6 py-8 text-center text-sm text-slate-400">{{ __('Başarısız iş yok.') }}</li>
                @endforelse
            </ul>
        </x-admin.card>
    </div>
@endsection

@push('scripts')
    <script>
        // Bir şey sürerken ya da sırada iş varken sayfa 5 sn'de bir kendini yeniler (sayfa yenilenmez)
        setInterval(() => {
            if (!document.hidden && document.querySelector('[data-live="queue-page"]')?.dataset.busy === '1') window.liveRefresh?.();
        }, 5000);
    </script>
@endpush
