@props(['type', 'id'])

{{-- Listede satırın yanında: bu kaydın (ya da SSS'lerinin) bir dilde çevirisi eksik (App\Support\TranslationTodo) --}}
@if (\App\Support\TranslationTodo::has($type, $id))
    <span {{ $attributes->class('inline-flex shrink-0 items-center gap-1 rounded-full bg-purple-50 px-2 py-0.5 text-[11px] font-semibold text-purple-700 ring-1 ring-purple-200') }}>
        <svg class="size-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 8 6 6M4 14l6-6 2-3M2 5h12M22 22l-5-10-5 10M14 18h6" /></svg>{{ __('Çeviri eksik') }}
    </span>
@endif
