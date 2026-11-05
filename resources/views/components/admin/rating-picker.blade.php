@props(['name' => 'rating', 'value' => 5])

{{-- 1–5 yıldız seçimi (radyo düğmeleri, klavyeyle de seçilir) --}}
<div class="flex flex-wrap gap-2" role="radiogroup" aria-label="{{ __('Puan') }}">
    @for ($i = 5; $i >= 1; $i--)
        <label class="flex cursor-pointer items-center gap-2 rounded-xl border-2 border-slate-200 bg-white px-3.5 py-2.5 transition hover:border-slate-300 has-checked:border-amber-400 has-checked:bg-amber-50">
            <input type="radio" name="{{ $name }}" value="{{ $i }}" data-label="{{ str_repeat('★', $i) }}" required class="sr-only" @checked((int) old($name, $value) === $i) />
            <x-admin.stars :rating="$i" />
            <span class="text-sm font-semibold tabular-nums text-slate-700">{{ $i }}</span>
        </label>
    @endfor
</div>
