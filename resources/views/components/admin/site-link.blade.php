@props(['path'])

{{-- Ziyaret edilen sayfayı sitede yeni sekmede açan küçük düğme (analitik listeleri) --}}
@if (filled($path))
    <a href="{{ rtrim(config('site.url'), '/') . '/' . ltrim($path, '/') }}" target="_blank" rel="noopener noreferrer"
        title="{{ __('Sayfayı sitede aç') }}" aria-label="{{ __('Sayfayı sitede aç') }}"
        {{ $attributes->class('inline-grid size-6 shrink-0 place-items-center rounded-md text-slate-400 ring-1 ring-slate-200 transition hover:bg-sky-50 hover:text-sky-700 hover:ring-sky-200') }}>
        <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 17 17 7M8 7h9v9" /></svg>
    </a>
@endif
