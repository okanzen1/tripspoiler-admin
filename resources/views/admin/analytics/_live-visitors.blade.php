{{-- Ziyaretçi tablosu: aynı IP tek satır (filtreye göre; 10 sn'de bir ve filtre değişince yenilenir) --}}
@php
    use App\Services\Analytics;
    $countryName = fn (?string $code) => $code ? (\Locale::getDisplayRegion("-{$code}", app()->getLocale()) ?: $code) : __('Bilinmiyor');
    $deviceName = ['mobile' => __('Telefon'), 'tablet' => __('Tablet'), 'desktop' => __('Bilgisayar')];
    // cihaz rozetleri: telefon mor, bilgisayar mavi, tablet turuncu
    $deviceTone = ['mobile' => 'bg-violet-50 text-violet-700 ring-violet-200', 'desktop' => 'bg-sky-50 text-sky-700 ring-sky-200', 'tablet' => 'bg-amber-50 text-amber-700 ring-amber-200'];
@endphp

    <div class="overflow-x-auto">
        <table class="w-full min-w-[1000px] text-left text-sm">
            <thead class="border-y border-sky-100 bg-gradient-to-r from-sky-50 via-indigo-50 to-violet-50 text-xs font-semibold text-indigo-900/70">
                <tr>
                    <th class="px-6 py-2.5 font-medium">{{ __('Son görülme') }}</th>
                    <th class="px-3 py-2.5 font-medium">{{ __('Konum') }}</th>
                    <th class="px-3 py-2.5 font-medium">{{ __('IP') }}</th>
                    <th class="px-3 py-2.5 font-medium">{{ __('Gezinti') }}</th>
                    <th class="px-3 py-2.5 font-medium">{{ __('Süre') }}</th>
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
                    <tr class="cursor-pointer align-top transition odd:bg-white even:bg-slate-50/50 hover:bg-sky-50/70" onclick="if (!event.target.closest('a')) location.href = this.dataset.href" data-href="{{ $url }}">
                        <td class="whitespace-nowrap px-6 py-2.5">
                            <span class="flex items-center gap-1.5 font-medium tabular-nums text-slate-800" title="{{ $at->translatedFormat('j F Y H:i:s') }}">
                                <span @class(['size-2 rounded-full', 'bg-emerald-500 ring-4 ring-emerald-100' => $online, 'bg-sky-300' => !$online && $at->isToday(), 'bg-slate-300' => !$online && !$at->isToday()]) @if ($online) title="{{ __('Şu an sitede') }}" @endif></span>
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
                            <a href="{{ $url }}" class="rounded-lg bg-indigo-50 px-2 py-1 font-mono text-xs font-semibold text-indigo-700 ring-1 ring-indigo-100 transition hover:bg-indigo-100">{{ $last->ip ?? \Illuminate\Support\Str::limit($visitor->who, 12) }}</a>
                        </td>
                        <td class="max-w-[18rem] px-3 py-2.5">
                            <span class="flex min-w-0 items-center gap-1.5">
                                <span class="min-w-0 truncate text-slate-800" title="{{ __('Son sayfa') }}: {{ $last->path }}">{{ $last->path }}</span>
                                <x-admin.site-link :path="$last->path" />
                                <a href="{{ route('analytics.heatmap', ['page' => $last->path, 'device' => $last->device === 'mobile' ? 'mobile' : 'desktop']) }}" title="{{ __('Bu sayfanın sıcaklık haritası') }}" aria-label="{{ __('Sıcaklık haritası') }}"
                                    class="inline-grid size-7 shrink-0 place-items-center rounded-lg bg-gradient-to-br from-amber-400 via-orange-500 to-rose-500 text-white shadow-[0_6px_14px_-6px_rgba(244,63,94,0.85)] ring-1 ring-white/40 transition hover:-translate-y-0.5 hover:from-amber-500 hover:to-rose-600 hover:shadow-[0_8px_18px_-6px_rgba(244,63,94,0.95)]">
                                    <svg class="size-4" viewBox="0 0 24 24" fill="currentColor" fill-opacity=".25" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 2c1 3 4 5 4 9a4 4 0 0 1-8 0c0-1.5.5-2.5 1.5-3.5C9 10 10 11 11 11c0-3-1-6 1-9Z" /><path d="M6 15a6 6 0 0 0 12 0" /></svg>
                                </a>
                            </span>
                            <span class="mt-1 flex gap-1 text-[10px] font-semibold">
                                <span class="rounded-full bg-violet-50 px-1.5 py-0.5 text-violet-700">{{ trans_choice(':count sayfa|:count sayfa', $visitor->views, ['count' => $visitor->views]) }}</span>
                                <span class="rounded-full bg-sky-50 px-1.5 py-0.5 text-sky-700">{{ trans_choice(':count oturum|:count oturum', $visitor->sessions, ['count' => $visitor->sessions]) }}</span>
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-3 py-2.5">
                            @if ($visitor->duration !== null && !$visitor->estimated)
                                <span class="inline-flex items-center gap-1 rounded-full bg-fuchsia-50 px-2 py-0.5 text-xs font-bold tabular-nums text-fuchsia-700 ring-1 ring-fuchsia-100" title="{{ __('Sitede geçirdiği süre (sekme öndeyken ölçüldü)') }}">
                                    <svg class="size-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="13" r="8" /><path d="M12 9v4l2.5 2.5" /></svg>{{ \App\Services\Engagement::duration($visitor->duration) }}
                                </span>
                            @elseif ($visitor->duration !== null)
                                <span class="text-xs font-semibold tabular-nums text-slate-600" title="{{ __('Tahmini: süre ölçümünden önceki ziyaret, ilk ve son sayfa arasındaki zaman (son sayfada kalınan süre dahil değil)') }}">~{{ \App\Services\Engagement::duration($visitor->duration) }}</span>
                                <span class="block text-[10px] text-slate-400">{{ __('yaklaşık') }}</span>
                            @else
                                <span class="text-xs text-slate-400" title="{{ __('Bu ziyaret süre ölçümü başlamadan önceydi ve tek sayfa gezildi: süre bilinmiyor') }}">{{ __('bilinmiyor') }}</span>
                            @endif
                        </td>
                        <td class="max-w-[12rem] px-3 py-2.5">
                            <x-admin.referrer :visit="$first" />
                        </td>
                        <td class="whitespace-nowrap px-3 py-2.5 text-slate-700">
                            <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold ring-1 {{ $deviceTone[$last->device] ?? 'bg-slate-50 text-slate-500 ring-slate-200' }}">{{ $deviceName[$last->device] ?? '—' }}</span>
                            <span class="block text-[11px] text-slate-400">{{ collect([$last->browser, $last->os])->filter()->implode(' · ') }}</span>
                        </td>
                        <td class="px-6 py-2.5 text-right">
                            @if ($visitor->clicks)
                                <span class="rounded-full bg-gradient-to-r from-amber-400 to-orange-500 px-2.5 py-0.5 text-xs font-bold text-white shadow-sm">{{ $visitor->clicks }}</span>
                            @else
                                <span class="text-slate-300">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-6 py-14 text-center text-sm text-slate-400">{{ __('Bu filtreye uyan ziyaretçi yok.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
<span class="hidden" data-live-count>{{ $live['visitors']->count() >= 50 ? __('son 50') : $live['visitors']->count() }}</span>
<span class="hidden" data-live-active-count>{{ $live['active'] }}</span>
