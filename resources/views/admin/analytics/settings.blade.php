@extends('layouts.admin')

@section('title', 'Analitik ayarları')

@php
    $fmt = fn ($n) => number_format($n, 0, ',', '.');
    $total = array_sum($counts);
    $chip = function (string $ip) use ($counts, $myIp, $fmt) {
        return [$ip === $myIp, $counts[$ip] ?? 0];
    };
@endphp

@section('header')
    <x-admin.page-header :title="__('Analitik ayarları')" :breadcrumb="['Yönetim' => route('management.index')]"
        :description="__('Hangi IP\'lerin analitikte sayılmayacağını belirle; bu IP\'lerin daha önce yazılmış kayıtlarını tek tuşla sil.')">
        <x-slot:icon><path d="M4 20V10M10 20V4M16 20v-7M22 20H2" /></x-slot:icon>
        <a href="{{ route('analytics.index') }}" class="inline-flex items-center gap-1.5 rounded-xl bg-white px-3.5 py-2 text-sm font-semibold text-slate-700 ring-1 ring-slate-200 transition hover:bg-slate-50">
            {{ __('Analitiğe dön') }} <span aria-hidden="true">→</span>
        </a>
    </x-admin.page-header>
@endsection

@section('content')
    {{-- ÖZET + TOPLU SİLME --}}
    <div class="relative isolate overflow-hidden rounded-3xl bg-gradient-to-br from-sky-600 via-sky-500 to-indigo-500 p-6 text-white shadow-[0_24px_50px_-30px_rgba(14,116,144,0.9)] sm:p-8">
        <div class="pointer-events-none absolute -right-16 -top-20 -z-10 size-72 rounded-full bg-white/10 blur-2xl" aria-hidden="true"></div>
        <div class="flex flex-wrap items-center justify-between gap-6">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-white/70">{{ __('Analitikte sayılmayan') }}</p>
                <p class="mt-2 text-4xl font-extrabold tracking-tight tabular-nums">{{ count($auto) + count(array_diff($blocked, $auto)) }} <span class="text-lg font-semibold text-white/80">IP</span></p>
                <p class="mt-1 text-sm text-white/80">
                    @if ($total)
                        {{ __('Bu IP\'lerin analitikte hâlâ :count kaydı var.', ['count' => $fmt($total)]) }}
                    @else
                        {{ __('Bu IP\'lerin analitikte kaydı yok, her şey temiz.') }}
                    @endif
                </p>
            </div>
            @if ($total)
                <form method="POST" action="{{ route('analytics-settings.purge') }}" data-no-ajax
                    data-confirm="{{ __('Listelerdeki bütün IP\'lerin :count kaydı analitikten kalıcı olarak silinsin mi?', ['count' => $fmt($total)]) }}">
                    @csrf @method('DELETE')
                    <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-white px-5 py-3 text-sm font-bold text-sky-700 shadow-lg transition hover:bg-sky-50">
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13" /></svg>
                        {{ __('Eski kayıtları analitikten sil') }}
                    </button>
                </form>
            @endif
        </div>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        {{-- SAYILMAYAN (otomatik) --}}
        <x-admin.card class="p-6">
            <div class="flex items-start gap-3">
                <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-emerald-50 text-emerald-600">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3 4 6v6c0 4.5 3.4 8.3 8 9 4.6-.7 8-4.5 8-9V6l-8-3Z" /><path d="m9 12 2 2 4-4" /></svg>
                </span>
                <div>
                    <h2 class="text-sm font-semibold text-slate-900">{{ __('Sayılmayan IP\'ler') }}</h2>
                    <p class="text-xs text-slate-500">{{ __('Panele girdiğin her cihaz ve IP otomatik eklenir (son 20).') }}</p>
                </div>
            </div>
            <ul class="mt-5 space-y-2">
                @forelse ($auto as $ip)
                    @php [$mine, $count] = $chip($ip); @endphp
                    <li class="flex flex-wrap items-center gap-2 rounded-xl bg-slate-50 px-3 py-2 ring-1 ring-slate-100">
                        <span class="min-w-0 flex-1 truncate font-mono text-xs text-slate-700">{{ $ip }}
                            @if ($mine)<span class="ml-1 rounded-full bg-emerald-100 px-1.5 py-0.5 font-sans text-[10px] font-bold text-emerald-700">{{ __('sen') }}</span>@endif
                        </span>
                        <span @class(['rounded-full px-2 py-0.5 text-[11px] font-semibold tabular-nums', 'bg-amber-100 text-amber-800' => $count, 'bg-slate-100 text-slate-400' => !$count])>{{ __(':count kayıt', ['count' => $fmt($count)]) }}</span>
                        @if ($count)
                            <form method="POST" action="{{ route('analytics-settings.purge') }}" data-no-ajax data-confirm="{{ __(':ip için :count kayıt silinsin mi?', ['ip' => $ip, 'count' => $fmt($count)]) }}">
                                @csrf @method('DELETE') <input type="hidden" name="ip" value="{{ $ip }}">
                                <button type="submit" class="rounded-lg px-2 py-1 text-[11px] font-semibold text-sky-700 transition hover:bg-sky-50">{{ __('Kayıtları sil') }}</button>
                            </form>
                        @endif
                        <form method="POST" action="{{ route('analytics.ignore-ip') }}" data-no-ajax>
                            @csrf <input type="hidden" name="ip" value="{{ $ip }}"><input type="hidden" name="remove" value="1">
                            <button type="submit" title="{{ __('Listeden çıkar (yeniden sayılır)') }}" class="grid size-6 place-items-center rounded-full text-slate-400 transition hover:bg-red-50 hover:text-red-600">×</button>
                        </form>
                    </li>
                @empty
                    <li class="rounded-xl bg-slate-50 px-3 py-4 text-center text-sm text-slate-500">{{ __('Yok — senin ziyaretlerin de sayılıyor.') }}</li>
                @endforelse
            </ul>
            @unless (in_array($myIp, $auto, true))
                <form method="POST" action="{{ route('analytics.ignore-ip') }}" data-no-ajax class="mt-4">
                    @csrf <input type="hidden" name="ip" value="{{ $myIp }}">
                    <button type="submit" class="inline-flex items-center gap-1.5 rounded-xl bg-brand-900 px-3.5 py-2 text-xs font-semibold text-white transition hover:bg-brand-800">
                        {{ __('Beni sayma') }} <span class="font-mono opacity-70">({{ $myIp }})</span>
                    </button>
                </form>
            @endunless
        </x-admin.card>

        {{-- İSTENMEYEN (elle) --}}
        <x-admin.card class="p-6">
            <div class="flex items-start gap-3">
                <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-rose-50 text-rose-600">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9" /><path d="m5.7 5.7 12.6 12.6" /></svg>
                </span>
                <div>
                    <h2 class="text-sm font-semibold text-slate-900">{{ __('İstenmeyen IP\'ler') }}</h2>
                    <p class="text-xs text-slate-500">{{ __('Analitikte görmek istemediğin IP\'leri yaz: her satıra bir IP (virgülle de ayırabilirsin).') }}</p>
                </div>
            </div>
            <form method="POST" action="{{ route('analytics-settings.blocked') }}" data-no-ajax class="mt-5">
                @csrf @method('PUT')
                <textarea name="ips" rows="7" spellcheck="false" placeholder="81.214.164.227&#10;2a00:1d34:2ddc:fd01:e0b2:a22b:1836:63f3"
                    @class(['block w-full resize-y rounded-xl border bg-white px-3.5 py-3 font-mono text-xs leading-relaxed text-slate-800 outline-none transition placeholder:text-slate-300 focus:border-brand-900 focus:ring-4 focus:ring-brand-900/10', 'border-red-300' => $errors->has('ips'), 'border-slate-200' => !$errors->has('ips')])>{{ old('ips', implode("\n", $blocked)) }}</textarea>
                @error('ips') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
                    <p class="text-xs text-slate-500">{{ __('Kaydettikten sonra bu IP\'lerden gelen yeni ziyaretler sayılmaz. Eski kayıtları silmek için yukarıdaki düğmeyi kullan.') }}</p>
                    <button type="submit" class="inline-flex items-center gap-1.5 rounded-xl bg-rose-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-rose-700">{{ __('Kaydet') }}</button>
                </div>
            </form>
            @if ($blocked)
                <ul class="mt-5 flex flex-wrap gap-2">
                    @foreach ($blocked as $ip)
                        @php [, $count] = $chip($ip); @endphp
                        <li class="inline-flex items-center gap-1.5 rounded-full bg-rose-50 py-1 pl-3 pr-1.5 font-mono text-xs text-rose-800 ring-1 ring-rose-100">
                            {{ $ip }}
                            <span @class(['rounded-full px-1.5 py-0.5 font-sans text-[10px] font-semibold tabular-nums', 'bg-amber-100 text-amber-800' => $count, 'bg-white text-rose-300' => !$count])>{{ $fmt($count) }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-admin.card>
    </div>
@endsection
