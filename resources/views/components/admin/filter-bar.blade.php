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

    <div class="flex flex-wrap items-center gap-3">
        @if ($searchable)
            <div class="relative min-w-56 flex-1">
                <x-admin.icon name="search" class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" />
                <input type="search" name="search" value="{{ $search }}" placeholder="{{ __($placeholder) }}"
                    class="block h-11 w-full rounded-xl border-0 bg-slate-100/80 pl-10 pr-3 text-sm outline-none ring-1 ring-transparent transition placeholder:text-slate-400 focus:bg-white focus:ring-2 focus:ring-brand-900/30" />
            </div>
        @endif

        @if ($tabs)
            <div class="flex h-11 items-center rounded-xl bg-slate-100 p-1 text-sm font-medium">
                @foreach ($tabs['options'] as $value => [$label, $count])
                    @php($current = $tabValue === (string) $value)
                    <a href="{{ request()->fullUrlWithQuery([$tabs['name'] => $value === '' ? null : $value, 'page' => null]) }}"
                        @class(['flex h-full items-center gap-1.5 rounded-lg px-3.5 transition', 'bg-white text-brand-900 shadow-sm' => $current, 'text-slate-500 hover:text-slate-800' => !$current])>
                        {{ __($label) }}
                        @if ($count !== null)
                            <span @class(['rounded-full px-1.5 text-xs tabular-nums', 'bg-brand-50 text-brand-900' => $current, 'text-slate-400' => !$current])>{{ $count }}</span>
                        @endif
                    </a>
                @endforeach
            </div>
        @endif
    </div>

    @if ($filters || $dirty)
        <div class="flex flex-wrap items-center gap-2">
            @foreach ($filters as $filter)
                @php($on = filled($filter['value'] ?? null))
                <label @class([
                    'relative inline-flex h-10 cursor-pointer items-center gap-2 rounded-xl pl-3 pr-2 text-sm ring-1 transition',
                    'bg-brand-50 text-brand-900 ring-brand-200' => $on,
                    'bg-white text-slate-600 ring-slate-200 hover:ring-slate-300' => !$on,
                ])>
                    @isset($filter['icon'])
                        <svg @class(['size-4 shrink-0', 'text-brand-700' => $on, 'text-slate-400' => !$on]) viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $icons[$filter['icon']] ?? '' !!}</svg>
                    @endisset
                    <span @class(['text-xs', 'text-brand-700' => $on, 'text-slate-400' => !$on])>{{ __($filter['label']) }}</span>
                    <select name="{{ $filter['name'] }}" onchange="this.form.submit()" aria-label="{{ __($filter['label']) }}"
                        class="max-w-52 cursor-pointer appearance-none truncate border-0 bg-transparent py-0 pl-0 pr-5 text-sm font-semibold outline-none focus:ring-0">
                        <option value="">{{ __('Tümü') }}</option>
                        @foreach ($filter['options'] as $value => $label)
                            <option value="{{ $value }}" @selected((string) ($filter['value'] ?? '') === (string) $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <svg class="pointer-events-none absolute right-2 size-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6" /></svg>
                </label>
                @if ($on)
                    <a href="{{ request()->fullUrlWithQuery([$filter['name'] => null, 'page' => null]) }}" title="{{ __('Bu filtreyi kaldır') }}" aria-label="{{ __('Bu filtreyi kaldır') }}"
                        class="-ml-1 grid size-7 place-items-center rounded-lg text-brand-700 transition hover:bg-brand-100">
                        <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18" /></svg>
                    </a>
                @endif
            @endforeach

            @if ($dirty)
                <a href="{{ request()->fullUrlWithQuery(array_fill_keys($resetKeys, null)) }}"
                    class="ml-auto inline-flex h-10 items-center gap-1.5 rounded-xl px-3 text-sm font-medium text-slate-500 transition hover:bg-slate-100 hover:text-slate-800">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 12a9 9 0 1 0 3-6.7L3 8M3 3v5h5" /></svg>
                    {{ __('Filtreleri temizle') }}
                </a>
            @endif
        </div>
    @endif
</form>
