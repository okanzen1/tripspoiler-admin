@props(['action', 'active', 'label', 'variant' => 'switch'])

{{-- Aktif/pasif anahtarı (ya da yıldız). JS varsa sayfa yenilenmeden çalışır (resources/js/toggle.js), yoksa normal form gönderir. --}}
<form action="{{ $action }}" method="POST" data-toggle class="inline-flex">
    @csrf
    @method('PATCH')
    @if ($variant === 'star')
        <button type="submit" role="switch" aria-checked="{{ $active ? 'true' : 'false' }}" aria-label="{{ __($label) }}" title="{{ __($label) }}"
            class="group grid size-9 place-items-center rounded-xl text-slate-300 ring-1 ring-slate-200 transition hover:text-amber-400 hover:ring-amber-200 aria-checked:bg-amber-50 aria-checked:text-amber-500 aria-checked:ring-amber-200 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-500 disabled:opacity-60">
            <svg class="size-5 transition group-aria-checked:fill-current" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" aria-hidden="true"><path d="m12 3 2.8 5.7 6.2.9-4.5 4.4 1.1 6.2L12 17.3l-5.6 2.9 1.1-6.2L3 9.6l6.2-.9Z" /></svg>
        </button>
    @else
        <button type="submit" role="switch" aria-checked="{{ $active ? 'true' : 'false' }}" aria-label="{{ __($label) }}"
            class="group relative inline-flex h-6 w-11 shrink-0 items-center rounded-full bg-slate-200 transition-colors aria-checked:bg-emerald-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-500 disabled:opacity-60">
            <span class="inline-block size-5 translate-x-0.5 rounded-full bg-white shadow-sm ring-1 ring-black/5 transition-transform group-aria-checked:translate-x-[22px]"></span>
        </button>
    @endif
</form>
