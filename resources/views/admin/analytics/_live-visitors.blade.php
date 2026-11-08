{{-- Ziyaretçi tablosu: aynı IP tek satır (filtreye göre; 10 sn'de bir ve filtre değişince yenilenir) --}}
@php
    use App\Services\Analytics;
    $countryName = fn (?string $code) => $code ? (\Locale::getDisplayRegion("-{$code}", app()->getLocale()) ?: $code) : __('Bilinmiyor');
    $deviceName = ['mobile' => __('Telefon'), 'tablet' => __('Tablet'), 'desktop' => __('Bilgisayar')];
@endphp

    <div class="overflow-x-auto">
        <table class="w-full min-w-[900px] text-left text-sm">
            <thead class="border-y border-slate-100 bg-slate-50/60 text-xs text-slate-500">
                <tr>
                    <th class="px-6 py-2.5 font-medium">{{ __('Son görülme') }}</th>
                    <th class="px-3 py-2.5 font-medium">{{ __('Konum') }}</th>
                    <th class="px-3 py-2.5 font-medium">{{ __('IP') }}</th>
                    <th class="px-3 py-2.5 font-medium">{{ __('Gezinti') }}</th>
                    <th class="px-3 py-2.5 font-medium">{{ __('İlk geliş') }}</th>
                    <th class="px-3 py-2.5 font-medium">{{ __('Cihaz') }}</th>
                    <th class="px-6 py-2.5 text-right font-medium">{{ __('Partner') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($live['visitors'] as $visitor)
                    @php
                        $last = $visitor->last;
                        $first = $visitor->first;
                        $at = \Carbon\Carbon::parse($last->created_at);
                        $online = $at->gte(now()->subMinutes(5));
                        $url = route('analytics.visitor', rawurlencode($visitor->who));
                    @endphp
                    <tr class="cursor-pointer align-top transition hover:bg-slate-50" onclick="if (!event.target.closest('a')) location.href = this.dataset.href" data-href="{{ $url }}">
                        <td class="whitespace-nowrap px-6 py-2.5">
                            <span class="flex items-center gap-1.5 font-medium tabular-nums text-slate-800" title="{{ $at->translatedFormat('j F Y H:i:s') }}">
                                @if ($online) <span class="size-2 rounded-full bg-emerald-500" title="{{ __('Şu an sitede') }}"></span> @endif
                                {{ $at->format('H:i:s') }}
                            </span>
                            <span class="block text-[11px] text-slate-400">{{ $at->isToday() ? $at->diffForHumans() : $at->translatedFormat('j M') }}</span>
                        </td>
                        <td class="px-3 py-2.5">
                            <span class="flex items-center gap-2">
                                <span class="text-lg leading-none">{{ Analytics::flag($last->country) }}</span>
                                <span class="min-w-0">
                                    <span class="block truncate text-slate-800">{{ $countryName($last->country) }}</span>
                                    @if ($last->city) <span class="block truncate text-[11px] text-slate-400">{{ $last->city }}</span> @endif
                                </span>
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-3 py-2.5">
                            <a href="{{ $url }}" class="font-mono text-xs text-brand-900 underline decoration-brand-200 underline-offset-2 hover:decoration-brand-900">{{ $last->ip ?? \Illuminate\Support\Str::limit($visitor->who, 12) }}</a>
                        </td>
                        <td class="max-w-[18rem] px-3 py-2.5">
                            <span class="block truncate text-slate-800" title="{{ __('Son sayfa') }}: {{ $last->path }}">{{ $last->path }}</span>
                            <span class="block text-[11px] text-slate-400">
                                {{ trans_choice(':count sayfa|:count sayfa', $visitor->views, ['count' => $visitor->views]) }} ·
                                {{ trans_choice(':count oturum|:count oturum', $visitor->sessions, ['count' => $visitor->sessions]) }}
                            </span>
                        </td>
                        <td class="max-w-[12rem] px-3 py-2.5">
                            <x-admin.referrer :visit="$first" />
                        </td>
                        <td class="whitespace-nowrap px-3 py-2.5 text-slate-700">
                            {{ $deviceName[$last->device] ?? '—' }}
                            <span class="block text-[11px] text-slate-400">{{ collect([$last->browser, $last->os])->filter()->implode(' · ') }}</span>
                        </td>
                        <td class="px-6 py-2.5 text-right">
                            @if ($visitor->clicks)
                                <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700">{{ $visitor->clicks }}</span>
                            @else
                                <span class="text-slate-300">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-6 py-14 text-center text-sm text-slate-400">{{ __('Bu filtreye uyan ziyaretçi yok.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
<span class="hidden" data-live-count>{{ $live['visitors']->count() }}</span>
