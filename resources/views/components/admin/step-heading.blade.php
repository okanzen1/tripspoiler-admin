@props(['title', 'description' => null])

<div>
    <h2 class="text-lg font-semibold text-slate-900">{{ __($title) }}</h2>
    @if ($description)
        <p class="mt-1 text-sm text-slate-500">{{ __($description) }}</p>
    @endif
</div>
