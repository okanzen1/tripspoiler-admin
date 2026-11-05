{{-- Panel dili seçimi (kullanıcı formu); kullanıcı menüdeki TR | EN ile de değiştirebilir --}}
<div>
    <label for="locale" class="block text-sm font-medium text-slate-700">{{ __('Panel dili') }}</label>
    <select id="locale" name="locale" class="mt-1.5 block w-full max-w-xs rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm outline-none transition focus:border-brand-900 focus:ring-4 focus:ring-brand-900/10">
        @foreach (config('app.panel_locales') as $code => $name)
            <option value="{{ $code }}" @selected(old('locale', $selected) === $code)>{{ $name }}</option>
        @endforeach
    </select>
    @error('locale') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
</div>
