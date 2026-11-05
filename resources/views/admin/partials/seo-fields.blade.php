{{--
    SEO başlığı + açıklaması, karakter sayaçları ve Google önizlemesi (resources/js/activity-form.js).
    $metaTitle, $metaDescription: mevcut değerler · $base: adres öneki (ör. "activities", boşsa ana alan)
    $path: slug alanı olmayan sayfalar için sabit adres · $fallbackTitle: başlık boşken önizlemede görünen
--}}
@php
    $field = 'mt-1.5 block w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm outline-none transition placeholder:text-slate-400 focus:border-brand-900 focus:ring-4 focus:ring-brand-900/10';
    $crumbs = trim(str_replace('/', ' › ', trim(($base ?? '') . '/' . ($path ?? ''), '/')));
@endphp

<div class="grid gap-6 xl:grid-cols-2">
    <div class="space-y-5">
        <div>
            <div class="flex items-center justify-between"><label for="meta_title" class="text-sm font-medium text-slate-700">{{ __('SEO başlığı') }}</label><span class="text-xs text-slate-400" data-counter-for="meta_title" data-limit="60"></span></div>
            <input id="meta_title" name="meta_title" value="{{ old('meta_title', $metaTitle) }}" maxlength="255" class="{{ $field }}" placeholder="{{ $titlePlaceholder ?? __('Sayfa başlığı') . ' | TripSpoiler' }}" />
            @error('meta_title') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @else <p class="mt-1.5 text-xs text-slate-500">{{ __('Önerilen: 50–60 karakter.') }}</p> @enderror
        </div>
        <div>
            <div class="flex items-center justify-between"><label for="meta_description" class="text-sm font-medium text-slate-700">{{ __('SEO açıklaması') }}</label><span class="text-xs text-slate-400" data-counter-for="meta_description" data-limit="160"></span></div>
            <textarea id="meta_description" name="meta_description" rows="4" maxlength="500" class="{{ $field }} resize-y" placeholder="{{ $descriptionPlaceholder ?? __('Google sonuçlarında başlığın altında görünen kısa açıklama.') }}">{{ old('meta_description', $metaDescription) }}</textarea>
            @error('meta_description') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @else <p class="mt-1.5 text-xs text-slate-500">{{ __('Önerilen: 120–160 karakter.') }}</p> @enderror
        </div>
    </div>
    <div>
        <span class="text-sm font-medium text-slate-700">{{ __('Google önizlemesi') }}</span>
        <div class="mt-1.5 rounded-2xl bg-white p-5 ring-1 ring-slate-200" data-seo-preview data-seo-fallback="{{ $fallbackTitle ?? __('Sayfa başlığı') }}">
            <p class="text-xs text-slate-500">tripspoiler.com{{ $crumbs ? ' › ' . $crumbs : '' }}@isset($base) › <span data-seo-slug></span>@endisset</p>
            <p class="mt-1 line-clamp-1 text-lg text-[#1a0dab]" data-seo-title></p>
            <p class="mt-1 line-clamp-2 text-sm text-slate-600" data-seo-description></p>
        </div>
    </div>
</div>
