{{-- Hero listeleri: arama kutusu ve (birden çok şehir varsa) şehir düğmeleri --}}
<div class="flex flex-wrap items-center gap-2">
    <div class="relative min-w-[14rem] flex-1">
        <svg class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7" /><path d="M20 20l-3.5-3.5" /></svg>
        <input type="search" data-filter-search placeholder="{{ $placeholder }}" autocomplete="off"
            class="block w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-10 pr-3.5 text-sm outline-none transition placeholder:text-slate-400 focus:border-brand-900 focus:ring-4 focus:ring-brand-900/10">
    </div>
    @if ($cities->count() > 1)
        <div class="flex flex-wrap gap-1.5">
            @foreach ($cities->prepend('') as $city)
                <button type="button" data-filter-city="{{ $city }}"
                    class="rounded-full px-3 py-1.5 text-xs font-semibold ring-1 ring-slate-200 transition hover:bg-slate-50 data-[active]:bg-brand-900 data-[active]:text-white data-[active]:ring-brand-900"
                    @if ($city === '') data-active @endif>{{ $city === '' ? __('Tüm şehirler') : $city }}</button>
            @endforeach
        </div>
    @endif
</div>
