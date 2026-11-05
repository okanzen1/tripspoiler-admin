@php($disabled ??= false)

<div class="grid gap-3 sm:grid-cols-2">
    @foreach (\App\Enums\Role::cases() as $case)
        <label @class([
            'relative flex gap-3.5 rounded-2xl border-2 p-5 transition has-checked:border-brand-900 has-checked:bg-brand-50/60',
            'border-slate-200',
            'cursor-pointer hover:border-slate-300' => !$disabled,
            'cursor-not-allowed opacity-60' => $disabled,
        ])>
            <input type="radio" name="role" value="{{ $case->value }}" data-label="{{ $case->label() }}" required
                class="mt-0.5 size-4 accent-brand-900" @checked($selected === $case->value) @disabled($disabled) />
            <span>
                <span class="block text-sm font-semibold text-slate-900">{{ $case->label() }}</span>
                <span class="mt-1 block text-xs leading-relaxed text-slate-500">{{ $case->description() }}</span>
            </span>
        </label>
    @endforeach
</div>
@error('role')
    <p class="mt-2 text-xs font-medium text-red-600">{{ $message }}</p>
@enderror
