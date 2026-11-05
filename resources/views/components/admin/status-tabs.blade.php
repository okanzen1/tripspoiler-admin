@props(['current' => null])

{{-- Tümü / Aktif / Pasif filtresi; diğer sorgu parametrelerini korur --}}
<div class="flex rounded-xl bg-slate-100 p-1 text-sm font-medium">
    @foreach (['' => 'Tümü', 'active' => 'Aktif', 'passive' => 'Pasif'] as $value => $label)
        <a href="{{ request()->fullUrlWithQuery(['status' => $value ?: null, 'page' => null]) }}"
            @class([
                'rounded-lg px-3.5 py-1.5 transition',
                'bg-white text-brand-900 shadow-sm' => ($current ?? '') === $value,
                'text-slate-500 hover:text-slate-800' => ($current ?? '') !== $value,
            ])>{{ __($label) }}</a>
    @endforeach
</div>
