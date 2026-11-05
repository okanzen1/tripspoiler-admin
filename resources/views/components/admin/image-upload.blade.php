@props(['name', 'label', 'current' => null, 'placeholder' => '', 'shape' => 'square', 'accept' => 'image/jpeg,image/png,image/webp', 'hint' => 'JPG, PNG ya da WebP · büyükse otomatik küçültülür'])

{{-- Resim yükleme: seçilen dosya anında önizlenir (resources/js/image-upload.js). "Kaldır" → remove_{name}=1 --}}
<div data-image-upload>
    <span class="block text-sm font-medium text-slate-700">{{ __($label) }}</span>
    <div class="mt-2 flex items-center gap-5">
        <div @class([
            'relative grid size-20 shrink-0 place-items-center overflow-hidden bg-slate-100 text-lg font-semibold text-slate-500 ring-1 ring-slate-200',
            'rounded-full' => $shape === 'circle',
            'rounded-2xl' => $shape !== 'circle',
        ])>
            <img data-preview src="{{ $current }}" alt="" @class(['size-full', 'object-cover' => $shape === 'circle', 'object-contain p-2' => $shape !== 'circle', 'hidden' => !$current]) />
            <span data-placeholder @class(['hidden' => $current])>{{ $placeholder }}</span>
        </div>

        <div class="space-y-2">
            <div class="flex flex-wrap items-center gap-2">
                <label class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl px-3.5 py-2 text-sm font-medium text-slate-700 ring-1 ring-slate-200 transition hover:bg-slate-50 focus-within:ring-2 focus-within:ring-brand-900/40">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 16V4M7 9l5-5 5 5M4 20h16" /></svg>
                    <span data-choose-label>{{ __($current ? 'Değiştir' : 'Resim seç') }}</span>
                    <input type="file" name="{{ $name }}" accept="{{ $accept ?? 'image/jpeg,image/png,image/webp' }}" class="sr-only" />
                </label>
                <button type="button" data-remove @class([
                    'rounded-xl px-3 py-2 text-sm font-medium text-slate-500 transition hover:bg-red-50 hover:text-red-600',
                    'hidden' => !$current,
                ])>{{ __('Kaldır') }}</button>
                <input type="hidden" name="remove_{{ $name }}" value="0" data-remove-flag />
            </div>
            @error($name)
                <p class="text-xs font-medium text-red-600">{{ $message }}</p>
            @else
                <p class="text-xs text-slate-500">{{ __($hint) }}</p>
            @enderror
        </div>
    </div>
</div>
