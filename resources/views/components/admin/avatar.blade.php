@props(['user'])

@if ($url = $user->avatarUrl())
    <img src="{{ $url }}" alt="{{ $user->full_name }}" {{ $attributes->class('shrink-0 rounded-full object-cover') }} />
@else
    <span {{ $attributes->class('grid shrink-0 place-items-center rounded-full bg-brand-100 text-sm font-semibold text-brand-900') }}>{{ $user->initials }}</span>
@endif
