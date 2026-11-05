@props(['countries', 'selected' => null, 'name' => 'country_id', 'label' => 'Ülke'])

{{-- $countries: ['active' => [...], 'passive' => [...]] (CityController::countryOptions) --}}
<div>
    <label for="{{ $name }}" class="block text-sm font-medium text-slate-700">{{ __($label) }}</label>
    <select id="{{ $name }}" name="{{ $name }}" {{ $attributes->class([
        'mt-1.5 block w-full rounded-xl border bg-white px-3.5 py-2.5 text-sm outline-none transition focus:ring-4',
        'border-slate-200 focus:border-brand-900 focus:ring-brand-900/10' => !$errors->has($name),
        'border-red-300 bg-red-50/40 focus:border-red-500 focus:ring-red-500/15' => $errors->has($name),
    ]) }}>
        <option value="">{{ __('Ülke seç…') }}</option>
        @foreach (['active' => 'Aktif ülkeler', 'passive' => 'Diğer ülkeler (pasif)'] as $group => $groupLabel)
            @if (isset($countries[$group]))
                <optgroup label="{{ __($groupLabel) }}">
                    @foreach ($countries[$group] as $country)
                        <option value="{{ $country->id }}" @selected((string) old($name, $selected) === (string) $country->id)>{{ $country->flag }} {{ $country->name_tr }}</option>
                    @endforeach
                </optgroup>
            @endif
        @endforeach
    </select>
    @error($name)
        <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>
    @else
        <p class="mt-1.5 text-xs text-slate-500">{{ __('Pasif bir ülke seçersen şehir, ülke aktif olana kadar sitede görünmez.') }}</p>
    @enderror
</div>
