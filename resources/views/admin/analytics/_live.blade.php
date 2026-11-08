{{-- Canlı: son 5 dakikada sitede olanlar + son ziyaretler. Analitik sayfası bunu 10 sn'de bir yeniden çeker (analytics.live). --}}
@php
    use App\Services\Analytics;
    $countryName = fn (?string $code) => $code ? (\Locale::getDisplayRegion("-{$code}", app()->getLocale()) ?: $code) : __('Bilinmiyor');
    $deviceName = ['mobile' => __('Telefon'), 'tablet' => __('Tablet'), 'desktop' => __('Bilgisayar')];
@endphp

<div class="grid gap-5 lg:grid-cols-3">
    {{-- Şu an sitede --}}
    <x-admin.card class="p-6">
        <p class="flex items-center gap-2 text-sm font-medium text-slate-500">
            <span class="relative flex size-2.5">
                @if ($live['active'])
                    <span class="absolute inline-flex size-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                @endif
                <span class="relative inline-flex size-2.5 rounded-full {{ $live['active'] ? 'bg-emerald-500' : 'bg-slate-300' }}"></span>
            </span>
            {{ __('Şu an sitede') }}
        </p>
        <p class="mt-2 text-4xl font-semibold tracking-tight tabular-nums text-slate-900">{{ $live['active'] }}</p>
        <p class="mt-1 text-xs text-slate-500">{{ __('son 5 dakikada · bugün :count tekil ziyaretçi', ['count' => $live['today']]) }}</p>
    </x-admin.card>

    {{-- Şu an açık sayfalar --}}
    <x-admin.card class="p-6 lg:col-span-2">
        <p class="text-sm font-medium text-slate-500">{{ __('Şu an bakılan sayfalar') }}</p>
        @if ($live['pages']->isEmpty())
            <p class="mt-4 text-sm text-slate-400">{{ __('Son 5 dakikada ziyaret yok.') }}</p>
        @else
            <ul class="mt-3 space-y-2">
                @foreach ($live['pages'] as $page)
                    <li class="flex items-center justify-between gap-3 text-sm">
                        <span class="truncate font-medium text-slate-800">{{ $page->path }}</span>
                        <span class="shrink-0 rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-semibold tabular-nums text-emerald-700">{{ trans_choice(':count kişi|:count kişi', $page->total, ['count' => $page->total]) }}</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-admin.card>
</div>

{{-- Son ziyaretler --}}
<x-admin.card class="mt-5 overflow-hidden">
    <div class="flex items-center justify-between px-6 pt-6">
        <div>
            <h2 class="text-sm font-semibold text-slate-900">{{ __('Son ziyaretler') }}</h2>
            <p class="text-xs text-slate-500">{{ __('Botlar sayılmaz · kendiliğinden yenilenir') }}</p>
        </div>
        <span class="text-[11px] text-slate-400" data-live-updated>{{ now()->format('H:i:s') }}</span>
    </div>
    <div class="mt-4 overflow-x-auto">
        <table class="w-full min-w-[860px] text-left text-sm">
            <thead class="border-y border-slate-100 bg-slate-50/60 text-xs text-slate-500">
                <tr>
                    <th class="px-6 py-2.5 font-medium">{{ __('Saat') }}</th>
                    <th class="px-3 py-2.5 font-medium">{{ __('Konum') }}</th>
                    <th class="px-3 py-2.5 font-medium">{{ __('IP') }}</th>
                    <th class="px-3 py-2.5 font-medium">{{ __('Sayfa') }}</th>
                    <th class="px-3 py-2.5 font-medium">{{ __('Kaynak') }}</th>
                    <th class="px-6 py-2.5 font-medium">{{ __('Cihaz') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($live['recent'] as $visit)
                    @php $at = \Carbon\Carbon::parse($visit->created_at); @endphp
                    <tr class="align-top">
                        <td class="whitespace-nowrap px-6 py-2.5">
                            <span class="font-medium tabular-nums text-slate-800" title="{{ $at->translatedFormat('j F Y H:i:s') }}">{{ $at->format('H:i:s') }}</span>
                            <span class="block text-[11px] text-slate-400">{{ $at->isToday() ? $at->diffForHumans() : $at->translatedFormat('j M') }}</span>
                        </td>
                        <td class="px-3 py-2.5">
                            <span class="flex items-center gap-2">
                                <span class="text-lg leading-none">{{ Analytics::flag($visit->country) }}</span>
                                <span class="min-w-0">
                                    <span class="block truncate text-slate-800">{{ $countryName($visit->country) }}</span>
                                    @if ($visit->city)
                                        <span class="block truncate text-[11px] text-slate-400">{{ $visit->city }}</span>
                                    @endif
                                </span>
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-3 py-2.5 font-mono text-xs text-slate-600">{{ $visit->ip ?? '—' }}</td>
                        <td class="max-w-[16rem] px-3 py-2.5">
                            <span class="block truncate text-slate-800" title="{{ $visit->path }}">{{ $visit->path }}</span>
                            <span class="block text-[11px] uppercase text-slate-400">{{ $visit->locale }}</span>
                        </td>
                        <td class="max-w-[12rem] px-3 py-2.5">
                            @if ($visit->utm_source)
                                <span class="rounded-md bg-violet-50 px-1.5 py-0.5 text-xs font-medium text-violet-700">{{ $visit->utm_source }}</span>
                                @if ($visit->utm_campaign) <span class="block truncate text-[11px] text-slate-400">{{ $visit->utm_campaign }}</span> @endif
                            @elseif ($visit->referrer_host)
                                <span class="block truncate text-slate-700">{{ $visit->referrer_host }}</span>
                            @else
                                <span class="text-slate-400">{{ __('Doğrudan') }}</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-6 py-2.5 text-slate-700">
                            {{ $deviceName[$visit->device] ?? '—' }}
                            <span class="block text-[11px] text-slate-400">{{ collect([$visit->browser, $visit->os])->filter()->implode(' · ') }}</span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-6 py-10 text-center text-sm text-slate-400">{{ __('Henüz ziyaret yok.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-admin.card>
