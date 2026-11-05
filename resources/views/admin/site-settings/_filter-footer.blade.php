{{-- Hero listeleri: kaç kayıt görünüyor + sitede görünmeyenleri aç/kapat --}}
<div class="mt-2 flex flex-wrap items-center justify-between gap-2 px-1 text-xs text-slate-500">
    <span data-filter-count data-unit="{{ $unit }}"></span>
    <label class="hidden cursor-pointer items-center gap-2" data-filter-hidden-toggle>
        <input type="checkbox" class="rounded border-slate-300 text-brand-900 focus:ring-brand-900/30">
        <span>{{ __('Sitede görünmeyenleri de göster') }} (<span data-filter-hidden-count></span>)</span>
    </label>
</div>
