@props(['size' => 128])

<img src="{{ asset("images/logo-{$size}.webp") }}" alt="{{ __('TripSpoiler') }}" width="{{ $size }}" height="{{ $size }}" {{ $attributes->class('object-contain') }} />
