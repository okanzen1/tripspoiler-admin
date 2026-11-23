@props(['path'])

{{-- Ziyaret edilen sayfayı sitede yeni sekmede açan küçük düğme (analitik listeleri) --}}
@if (filled($path))
    <a href="{{ rtrim(config('site.url'), '/') . '/' . ltrim($path, '/') }}" target="_blank" rel="noopener noreferrer"
        title="{{ __('Sayfayı sitede aç') }}" aria-label="{{ __('Sayfayı sitede aç') }}"
        {{ $attributes->class('inline-grid size-7 shrink-0 place-items-center rounded-lg bg-gradient-to-br from-sky-400 to-indigo-500 text-white shadow-[0_6px_14px_-6px_rgba(79,70,229,0.8)] ring-1 ring-white/40 transition hover:-translate-y-0.5 hover:from-sky-500 hover:to-indigo-600 hover:shadow-[0_8px_18px_-6px_rgba(79,70,229,0.9)]') }}>
        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 17 17 7M8 7h9v9" /></svg>
    </a>
@endif
