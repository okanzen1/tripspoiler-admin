@props([
    'search' => null,           // arama kutusunun değeri; null ise kutu gösterilmez (searchable=false)
    'searchable' => true,
    'placeholder' => 'Ara…',
    'filters' => [],            // [['name' => 'city', 'label' => 'Şehir', 'options' => [değer => etiket], 'value' => seçili, 'icon' => 'pin']]
    'tabs' => null,             // ['name' => 'status', 'value' => seçili, 'options' => [değer => [etiket, sayı|null]]]
])

{{--
    Liste sayfalarının ortak filtre çubuğu: arama + durum sekmeleri + seçmeli filtreler.
    Seçim değişince form kendini gönderir; dolu filtreler renklenir, tek tek ya da topluca temizlenir.
--}}
@php
    // Filtre ikonları (görünüm dosyalarında adıyla verilir: 'icon' => 'pin')
    $icons = [
        'pin' => '<path d="M12 21s-7-6.1-7-11a7 7 0 1 1 14 0c0 4.9-7 11-7 11Z" /><circle cx="12" cy="10" r="2.5" />',
        'link' => '<path d="M10 13a5 5 0 0 0 7.5.5l3-3a5 5 0 0 0-7-7l-1.7 1.7" /><path d="M14 11a5 5 0 0 0-7.5-.5l-3 3a5 5 0 0 0 7 7l1.7-1.7" />',
        'ticket' => '<path d="M4 7h16v4a2 2 0 0 0 0 4v4H4v-4a2 2 0 0 0 0-4Z" />',
        'star' => '<path d="m12 3 2.8 5.7 6.2.9-4.5 4.4 1.1 6.2L12 17.3l-5.6 2.9 1.1-6.2L3 9.6l6.2-.9Z" />',
        'pen' => '<path d="M12 20h9" /><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z" />',
        'image' => '<rect x="3" y="4" width="18" height="16" rx="2" /><circle cx="9" cy="10" r="2" /><path d="m21 16-5-5L5 20" />',
        'eye' => '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z" /><circle cx="12" cy="12" r="3" />',
        'globe' => '<circle cx="12" cy="12" r="9" /><path d="M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18" />',
    ];
    $tabValue = (string) ($tabs['value'] ?? '');
    $activeFilters = collect($filters)->filter(fn ($f) => filled($f['value'] ?? null));
    $dirty = filled($search) || $activeFilters->isNotEmpty() || $tabValue !== '';
    $resetKeys = collect($filters)->pluck('name')->push('search', 'page', $tabs['name'] ?? 'status')->all();
@endphp

<form method="GET" {{ $attributes->class('space-y-3 p-5') }}>
    @if ($tabs && $tabValue !== '')
        <input type="hidden" name="{{ $tabs['name'] }}" value="{{ $tabValue }}" />
    @endif

    {{-- üst satır: sekmeler + arama --}}
    <div class="flex flex-col gap-3 lg:flex-row lg:items-center">
        @if ($tabs)
            <div class="-mx-1 shrink-0 overflow-x-auto px-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                <div class="inline-flex h-11 min-w-full items-center gap-0.5 rounded-xl bg-slate-100 p-1 text-sm font-medium sm:min-w-0">
                    @foreach ($tabs['options'] as $value => [$label, $count])
                        @php($current = $tabValue === (string) $value)
                        <a href="{{ request()->fullUrlWithQuery([$tabs['name'] => $value === '' ? null : $value, 'page' => null]) }}"
                            @class(['flex h-full shrink-0 items-center gap-1.5 whitespace-nowrap rounded-lg px-3.5 transition', 'bg-white text-brand-900 shadow-sm' => $current, 'text-slate-500 hover:text-slate-800' => !$current])>
                            {{ __($label) }}
                            @if ($count !== null)
                                <span @class(['rounded-full px-1.5 text-xs tabular-nums', 'bg-brand-50 text-brand-900' => $current, 'text-slate-400' => !$current])>{{ $count }}</span>
                            @endif
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
        @if ($searchable)
            <div class="relative min-w-0 flex-1">
                <x-admin.icon name="search" class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" />
                <input type="search" name="search" value="{{ $search }}" placeholder="{{ __($placeholder) }}" enterkeyhint="search"
                    class="block h-11 w-full rounded-xl border-0 bg-slate-100/80 pl-10 pr-20 text-sm outline-none ring-1 ring-transparent transition placeholder:text-slate-400 focus:bg-white focus:ring-2 focus:ring-brand-900/30" />
                <button type="submit" class="absolute right-1.5 top-1/2 -translate-y-1/2 rounded-lg bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 shadow-sm ring-1 ring-slate-200 transition hover:text-brand-900">{{ __('Ara') }}</button>
            </div>
        @endif
    </div>

    {{-- alt satır: filtreler hap şeklinde; seçili olan koyu renk, içinde × ile kaldırılır --}}
    @if ($filters || $dirty)
        <div class="flex flex-wrap items-center gap-2">
            @foreach ($filters as $filter)
                @php($on = filled($filter['value'] ?? null))
                <div @class(['inline-flex h-9 items-center rounded-full text-sm transition',
                    'bg-brand-900 text-white shadow-sm' => $on,
                    'bg-white text-slate-600 ring-1 ring-slate-200 hover:ring-slate-300 hover:text-slate-900' => !$on])>
                    <label class="relative inline-flex h-full cursor-pointer items-center gap-1.5 pl-3 pr-1">
                        @isset($filter['icon'])
                            <svg @class(['size-4 shrink-0', 'text-white/70' => $on, 'text-slate-400' => !$on]) viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $icons[$filter['icon']] ?? '' !!}</svg>
                        @endisset
                        @if ($on)
                            <span class="text-xs text-white/70">{{ __($filter['label']) }}:</span>
                        @endif
                        <select name="{{ $filter['name'] }}" onchange="this.form.submit()" aria-label="{{ __($filter['label']) }}"
                            @class(['max-w-52 cursor-pointer appearance-none truncate border-0 bg-transparent py-0 pl-0 pr-5 text-sm outline-none focus:ring-0', 'font-semibold' => $on, 'font-medium' => !$on])>
                            {{-- seçili değilken hapın üstünde filtrenin adı yazar ("Şehir"), listede "Tümü" --}}
                            <option value="" class="text-slate-900">{{ $on ? __('Tümü') : __($filter['label']) }}</option>
                            @foreach ($filter['options'] as $value => $label)
                                <option value="{{ $value }}" class="text-slate-900" @selected((string) ($filter['value'] ?? '') === (string) $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <svg @class(['pointer-events-none absolute right-1 size-3.5', 'text-white/70' => $on, 'text-slate-400' => !$on]) viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6" /></svg>
                    </label>
                    @if ($on)
                        <a href="{{ request()->fullUrlWithQuery([$filter['name'] => null, 'page' => null]) }}" title="{{ __('Bu filtreyi kaldır') }}" aria-label="{{ __('Bu filtreyi kaldır') }}"
                            class="mr-1 grid size-7 place-items-center rounded-full text-white/80 transition hover:bg-white/15 hover:text-white">
                            <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18" /></svg>
                        </a>
                    @else
                        <span class="w-2"></span>
                    @endif
                </div>
            @endforeach
            @if ($dirty)
                <a href="{{ request()->fullUrlWithQuery(array_fill_keys($resetKeys, null)) }}"
                    class="inline-flex h-9 items-center gap-1.5 rounded-full px-3 text-sm font-medium text-red-600 transition hover:bg-red-50">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 12a9 9 0 1 0 3-6.7L3 8M3 3v5h5" /></svg>
                    {{ __('Filtreleri temizle') }}
                </a>
            @endif
        </div>
    @endif
</form>
