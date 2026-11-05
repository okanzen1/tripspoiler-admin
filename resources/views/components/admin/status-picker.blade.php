@props(['active' => false, 'name' => 'active'])

{{-- Yayın durumu seçimi: Aktif / Pasif kartları --}}
<div class="grid gap-3 sm:grid-cols-2">
    @foreach ([1 => ['Aktif', 'Sitede görünür'], 0 => ['Pasif', 'Kaydedilir ama sitede görünmez']] as $value => [$label, $hint])
        <label class="relative flex cursor-pointer gap-3.5 rounded-2xl border-2 border-slate-200 p-5 transition hover:border-slate-300 has-checked:border-brand-900 has-checked:bg-brand-50/60">
            <input type="radio" name="{{ $name }}" value="{{ $value }}" data-label="{{ __($label) }}" required
                class="mt-0.5 size-4 accent-brand-900" @checked((bool) old($name, $active) === (bool) $value) />
            <span>
                <span class="block text-sm font-semibold text-slate-900">{{ __($label) }}</span>
                <span class="mt-1 block text-xs text-slate-500">{{ __($hint) }}</span>
            </span>
        </label>
    @endforeach
</div>
