{{-- $partner: düzenlenen partner, yeni kayıtta null --}}
<x-admin.image-upload name="logo" accept=".svg,image/svg+xml,image/png,image/webp,image/jpeg" :label="__('Logo')" :current="$partner?->logoUrl()" :placeholder="$partner?->initials() ?? 'LOGO'"
    :hint="__('SVG, şeffaf PNG ya da WebP en iyisi · en fazla 2 MB')" />
<div class="grid gap-5 sm:grid-cols-2">
    <x-admin.input name="name" :label="__('Partner adı')" :value="$partner?->name" required placeholder="{{ __('GetYourGuide') }}" autocomplete="off" />
    @if ($partner)
        <x-admin.input name="slug" :label="__('Kod')" :value="$partner->slug" readonly
            :hint="__('Tıklama geçmişi bu koda bağlı olduğu için değiştirilemez.')" />
    @else
        <x-admin.input name="slug" :label="__('Kod')" data-slug-from="name" data-slug-separator="" autocomplete="off"
            :hint="__('Tıklamalar bu kodla kaydedilir. Boş bırakırsan addan üretilir (ör. getyourguide).')" />
    @endif
</div>
<x-admin.input name="website" :label="__('Web sitesi')" type="url" :value="$partner?->website" placeholder="{{ __('https://www.getyourguide.com') }}" />

{{-- Footer'ın üstündeki partnerler şeridi --}}
<label class="flex cursor-pointer items-start gap-3 rounded-2xl bg-slate-50 p-4 ring-1 ring-slate-200 transition hover:bg-white has-[:checked]:bg-emerald-50 has-[:checked]:ring-emerald-300">
    <input type="hidden" name="show_on_site" value="0">
    <input type="checkbox" name="show_on_site" value="1" @checked(old('show_on_site', $partner?->show_on_site)) class="mt-0.5 size-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
    <span>
        <span class="block text-sm font-semibold text-slate-900">{{ __('“Destekçilerimiz ve partnerlerimiz” alanında göster') }}</span>
        <span class="block text-xs text-slate-500">{{ __('Sitenin her sayfasında, footer’ın hemen üstünde kayan logolar arasında yer alır. Logo yüklü olmalı.') }}</span>
    </span>
</label>
