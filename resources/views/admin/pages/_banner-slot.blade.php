{{-- Banner resim alanı: masaüstü (sayfadaki oranda) + telefon (telefon çerçevesinde) önizleme, seç / kaldır --}}
<div class="mt-5 flex flex-col gap-5 lg:flex-row lg:items-start" data-banner-slot>
    {{-- MASAÜSTÜ --}}
    <div class="min-w-0 flex-1 space-y-3" data-banner-box>
        <p class="flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-slate-500">
            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2.5" y="4.5" width="19" height="12" rx="2" /><path d="M8 20h8M12 16.5V20" /></svg>
            {{ __('Masaüstü') }}
        </p>
        <div class="relative {{ $spec['ratio'] }} {{ $spec['preview_width'] }} w-full overflow-hidden rounded-xl bg-slate-900">
            <img src="{{ $desktopPreview }}" alt="" data-preview @class(['absolute inset-0 size-full object-cover', 'opacity-50' => !$desktop, 'hidden' => !$desktopPreview]) style="object-position: {{ $focus ?? 50 }}% center">
            @if (!empty($note))
                <span data-note class="absolute bottom-2 right-2 rounded-md bg-slate-950/70 px-2 py-1 text-[11px] font-medium text-white/85">{{ $note }}</span>
            @endif
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <label class="inline-flex cursor-pointer items-center gap-2 rounded-xl bg-brand-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-800">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 16V4M7 9l5-5 5 5M4 20h16" /></svg>
                {{ $desktop ? __('Resmi değiştir') : __('Resim seç') }}
                <input type="file" name="{{ $desktopName }}" accept="image/jpeg,image/png,image/webp" class="sr-only">
            </label>
            @if ($desktop)
                <label class="inline-flex cursor-pointer items-center gap-2 rounded-xl px-3 py-2 text-sm font-medium text-slate-500 hover:bg-red-50 hover:text-red-600">
                    <input type="checkbox" name="{{ $removeDesktop }}" value="1" data-remove-image class="rounded border-slate-300 text-red-600 focus:ring-red-500">
                    {{ __('Kaldır') }}
                </label>
            @endif
            <span class="text-xs font-medium text-emerald-700" data-file-name></span>
        </div>
    </div>

    {{-- TELEFON --}}
    <div class="space-y-3 lg:w-48" data-banner-box>
        <p class="flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-slate-500">
            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="7" y="2.5" width="10" height="19" rx="2" /><path d="M11 18.5h2" /></svg>
            {{ __('Telefon') }}
        </p>
        <div class="w-36 rounded-[1.4rem] border-4 border-slate-900 bg-slate-900 p-0.5">
            <div class="relative {{ $spec['mobile_ratio'] }} overflow-hidden rounded-[1rem] bg-slate-800">
                <img src="{{ $mobilePreview }}" alt="" data-preview data-own-mobile="{{ $mobile ? '1' : '0' }}" @class(['absolute inset-0 size-full object-cover', 'opacity-50' => !$mobile, 'hidden' => !$mobilePreview]) style="object-position: {{ $focus ?? 50 }}% center">
                @if (!$mobile)
                    <span data-note class="absolute inset-x-1.5 bottom-1.5 rounded-md bg-slate-950/70 px-1.5 py-1 text-center text-[10px] font-medium leading-tight text-white/85">{{ __('Masaüstü resminden kesilir') }}</span>
                @endif
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <label class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl px-3 py-2 text-sm font-semibold text-slate-700 ring-1 ring-slate-200 transition hover:bg-slate-50">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 16V4M7 9l5-5 5 5M4 20h16" /></svg>
                {{ $mobile ? __('Değiştir') : __('Seç') }}
                <input type="file" name="{{ $mobileName }}" accept="image/jpeg,image/png,image/webp" class="sr-only">
            </label>
            @if ($mobile)
                <label class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl px-2.5 py-2 text-sm font-medium text-slate-500 hover:bg-red-50 hover:text-red-600">
                    <input type="checkbox" name="{{ $removeMobile }}" value="1" data-remove-image class="rounded border-slate-300 text-red-600 focus:ring-red-500">
                    {{ __('Kaldır') }}
                </label>
            @endif
        </div>
        <span class="block text-xs font-medium text-emerald-700" data-file-name></span>
        <div class="mt-2 flex items-center gap-2">
            <span class="text-[11px] font-medium text-slate-500">Sol</span>
            <input type="range" name="{{ $desktopName }}_focus" min="0" max="100" step="5" value="{{ old($desktopName . '_focus', $focus ?? 50) }}" class="w-full accent-brand-900" data-banner-focus>
            <span class="text-[11px] font-medium text-slate-500">Sağ</span>
        </div>
        {{-- resim çerçeveye tam oturuyorsa kaydırıcı onu değiştiremez: neden değişmediği yazsın (hero-text.blade.php betiği) --}}
        <p class="hidden text-[11px] leading-snug text-amber-700" data-focus-hint></p>
    </div>
</div>
