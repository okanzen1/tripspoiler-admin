@props(['partner'])

@if ($url = $partner->logoUrl())
    <span {{ $attributes->class('grid shrink-0 place-items-center overflow-hidden rounded-xl bg-white ring-1 ring-slate-200') }}>
        <img src="{{ $url }}" alt="{{ $partner->name }}" class="size-full object-contain p-1.5" />
    </span>
@else
    <span {{ $attributes->class('grid shrink-0 place-items-center rounded-xl bg-brand-900 text-xs font-bold tracking-wide text-white') }}>{{ $partner->initials() }}</span>
@endif
