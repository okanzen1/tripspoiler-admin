@props(['name', 'label', 'type' => 'text', 'value' => null, 'hint' => null, 'prefix' => null, 'suffix' => null])

<div>
    <label for="{{ $name }}" class="block text-sm font-medium text-slate-700">{{ __($label) }}</label>
    <div @class([
        'mt-1.5 flex items-center rounded-xl border bg-white transition focus-within:ring-4',
        'border-slate-200 focus-within:border-brand-900 focus-within:ring-brand-900/10' => !$errors->has($name),
        'border-red-300 bg-red-50/40 focus-within:border-red-500 focus-within:ring-red-500/15' => $errors->has($name),
    ])>
        @if ($prefix)
            <span class="select-none pl-3.5 text-sm text-slate-400">{{ $prefix }}</span>
        @endif
        <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}"
            @if ($type !== 'password') value="{{ old($name, $value) }}" @endif
            {{ $attributes->class([
                'block w-full min-w-0 rounded-xl bg-transparent py-2.5 text-sm outline-none placeholder:text-slate-400 read-only:text-slate-500',
                'pl-3.5' => !$prefix,
                'pl-1' => $prefix,
                'pr-3.5' => !$suffix,
                'pr-1' => $suffix,
            ]) }} />
        @if ($suffix)
            <span class="select-none pr-3.5 text-sm text-slate-400">{{ $suffix }}</span>
        @endif
    </div>
    @error($name)
        <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>
    @elseif ($hint)
        <p class="mt-1.5 text-xs text-slate-500">{{ __($hint) }}</p>
    @enderror
</div>
