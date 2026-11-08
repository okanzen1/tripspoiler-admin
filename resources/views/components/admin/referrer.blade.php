{{-- Ziyaretçinin geldiği yer: site adresi tıklanabilir (yeni sekme), UTM etiketi düz yazı, ikisi de yoksa "Doğrudan" --}}
@props(['visit'])
@if ($visit->utm_source)
    <span {{ $attributes->class('rounded-md bg-violet-50 px-1.5 py-0.5 font-medium text-violet-700') }} title="{{ __('UTM etiketi') }}">{{ $visit->utm_source }}</span>
@elseif ($visit->referrer_host)
    <a href="https://{{ $visit->referrer_host }}" target="_blank" rel="noopener noreferrer nofollow" onclick="event.stopPropagation()"
        {{ $attributes->class('inline-flex max-w-full items-center gap-1 text-brand-900 underline decoration-brand-200 underline-offset-2 hover:decoration-brand-900') }}
        title="{{ __(':site sitesini yeni sekmede aç', ['site' => $visit->referrer_host]) }}">
        <span class="truncate">{{ $visit->referrer_host }}</span>
        <svg class="size-3 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 17 17 7M8 7h9v9" /></svg>
    </a>
@else
    <span {{ $attributes->class('text-slate-400') }}>{{ __('Doğrudan') }}</span>
@endif
