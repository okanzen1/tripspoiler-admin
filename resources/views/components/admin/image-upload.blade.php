@props(['name', 'label', 'current' => null, 'focus' => 50, 'placeholder' => '', 'shape' => 'square', 'accept' => 'image/jpeg,image/png,image/webp', 'hint' => 'JPG, PNG ya da WebP · büyükse otomatik küçültülür'])

{{-- Resim yükleme: seçilen dosya anında önizlenir (resources/js/image-upload.js). "Kaldır" → remove_{name}=1
     shape="wide": büyük yatay önizleme (kapak görselleri); resim üstüne sürüklenip bırakılabilir --}}
@if ($shape === 'wide')
<div data-image-upload>
    <span class="block text-sm font-medium text-slate-700">{{ __($label) }}</span>
    <div class="mt-2 grid gap-5 lg:grid-cols-[minmax(0,1fr)_17rem]">
        <label data-drop class="group relative block aspect-[16/9] cursor-pointer overflow-hidden rounded-2xl border-2 border-dashed border-slate-300 bg-slate-50 transition hover:border-brand-400 hover:bg-brand-50/40 data-[over]:border-brand-600 data-[over]:bg-brand-50">
            <img data-preview src="{{ $current }}" alt="" @class(['absolute inset-0 size-full object-cover', 'hidden' => !$current]) style="object-position: {{ $focus }}% center" />
            <span data-placeholder @class(['absolute inset-0 grid place-items-center p-6 text-center', 'hidden' => $current])>
                <span>
                    <span class="mx-auto grid size-14 place-items-center rounded-2xl bg-white text-brand-900 shadow-sm ring-1 ring-slate-200 transition group-hover:scale-105">
                        <svg class="size-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 16V4M7 9l5-5 5 5M4 20h16" /></svg>
                    </span>
                    <span class="mt-3 block text-sm font-semibold text-slate-800">{{ __('Resmi buraya sürükle ya da tıkla') }}</span>
                    <span class="mt-1 block text-xs text-slate-500">{{ __($hint) }}</span>
                </span>
            </span>
            {{-- resim varken: köşede "Değiştir" --}}
            <span @class(['absolute bottom-3 right-3 rounded-xl bg-white/95 px-3 py-2 text-sm font-semibold text-slate-800 shadow-lg ring-1 ring-black/5 transition group-hover:bg-white', 'hidden' => !$current]) data-change-chip>
                <span class="inline-flex items-center gap-1.5"><svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 16V4M7 9l5-5 5 5M4 20h16" /></svg>
                <span data-choose-label>{{ __($current ? 'Değiştir' : 'Resim seç') }}</span></span>
            </span>
            <input type="file" name="{{ $name }}" accept="{{ $accept ?? 'image/jpeg,image/png,image/webp' }}" class="sr-only" />
        </label>

        <div class="space-y-4">
            <div class="rounded-2xl bg-white p-4 ring-1 ring-slate-200" data-focus-box>
                <p class="text-sm font-semibold text-slate-800">{{ __('Görselin odağı') }}</p>
                <p class="mt-0.5 text-xs leading-relaxed text-slate-500">{{ __('Resim farklı ekranlarda kırpılınca hangi tarafı görünsün?') }}</p>
                <div class="mt-3 flex items-center gap-3">
                    <span class="text-[11px] font-semibold text-slate-500">{{ __('Sol') }}</span>
                    <input type="range" name="{{ $name }}_focus" min="0" max="100" step="5" value="{{ old($name . '_focus', $focus) }}" class="w-full accent-brand-900" data-image-focus>
                    <span class="text-[11px] font-semibold text-slate-500">{{ __('Sağ') }}</span>
                </div>
            </div>
            <button type="button" data-remove @class([
                'w-full rounded-xl px-3 py-2.5 text-sm font-semibold text-red-600 ring-1 ring-red-100 transition hover:bg-red-50',
                'hidden' => !$current,
            ])><span class="inline-flex items-center gap-1.5"><x-admin.icon name="trash" class="size-4" /> {{ __('Görseli kaldır') }}</span></button>
            <input type="hidden" name="remove_{{ $name }}" value="0" data-remove-flag />
            @error($name)
                <p class="text-xs font-medium text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>
</div>
@else
<div data-image-upload>
    <span class="block text-sm font-medium text-slate-700">{{ __($label) }}</span>
    <div class="mt-2 flex items-center gap-5">
        <div @class([
            'relative grid size-20 shrink-0 place-items-center overflow-hidden bg-slate-100 text-lg font-semibold text-slate-500 ring-1 ring-slate-200',
            'rounded-full' => $shape === 'circle',
            'rounded-2xl' => $shape !== 'circle',
        ])>
            <img data-preview src="{{ $current }}" alt="" @class(['size-full', 'object-cover' => $shape === 'circle', 'object-contain p-2' => $shape !== 'circle', 'hidden' => !$current]) style="object-position: {{ $focus }}% center" />
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
            <div class="mt-2 flex items-center gap-3" data-focus-box>
                <span class="text-[11px] font-medium text-slate-500">Sol</span>
                <input type="range" name="{{ $name }}_focus" min="0" max="100" step="5" value="{{ old($name . '_focus', $focus) }}" class="w-full accent-brand-900" data-image-focus>
                <span class="text-[11px] font-medium text-slate-500">Sağ</span>
            </div>
            @error($name)
                <p class="text-xs font-medium text-red-600">{{ $message }}</p>
            @else
                <p class="text-xs text-slate-500">{{ __($hint) }}</p>
            @enderror
        </div>
    </div>
</div>
@endif
