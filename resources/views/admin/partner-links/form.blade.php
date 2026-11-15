@extends('layouts.admin')

@php
    $editing = $link->exists;
    $field = 'mt-1.5 block w-full rounded-xl border bg-white px-3.5 py-2.5 text-sm outline-none transition placeholder:text-slate-400 focus:border-brand-900 focus:ring-4 focus:ring-brand-900/10';
    $err = fn ($name) => $errors->has($name) ? 'border-red-300' : 'border-slate-200';
    // kart resmi için grafikere / yapay zekâya verilecek tarif
    $brief = <<<'TXT'
Merhaba, TripSpoiler sitesindeki bir kart için arka plan görseli lazım.

Ölçü: 1200 × 1500 px, dikey (4:5). JPG ya da WebP, 5 MB altı.

Görselin üstüne sitede yazı ve düğme geliyor, o yüzden:
- Görselde hiç yazı, logo, filigran olmasın (yazılar sitede 12 dilde ayrıca çıkıyor).
- Ana konu (şehrin simgesi: ör. İstanbul için Ayasofya / Sultanahmet silueti, Boğaz, vapurlar) üst ve orta kısımda olsun.
- Alt %40'lık kısım sakin ve görece koyu olsun (su, gölgeli sokak, gökyüzü); üstüne beyaz yazı ve beyaz bir düğme gelecek.
- Sol üst köşeye partner logosu (beyaz kutu içinde), sağ üst köşeye küçük bir ok ikonu geliyor; bu iki köşe sade kalsın.
- Kart telefonda daha dar görünüyor; yanlardan biraz kırpılabilir, önemli bir şey en kenarlarda olmasın.
- Sıcak, canlı, gerçekçi fotoğraf havası; gün batımı / altın saat ışığı tercihimiz.
- Yakından yüzü görünen kişi olmasın (telif ve izin sorunu olmasın).

Teslim: 1200 × 1500 px tek görsel, istersen aynı görselin biraz farklı kadrajlı ikinci bir versiyonu.
TXT;
    $prompt = <<<'TXT'
Vertical travel photo, 4:5 aspect ratio, 1200x1500 px. A wide, vibrant view of Istanbul at golden hour: Hagia Sophia and the Blue Mosque skyline with the Bosphorus and ferries, warm sunset light. Main subject in the upper and middle part of the frame; the lower 40% is calmer and darker (water or shadowed streets) so white text can sit on top. Leave the top-left corner simple. No text, no logos, no watermarks, no people's faces in close-up. Photorealistic, high detail, rich colors.
TXT;
@endphp

@section('title', $editing ? 'Bağlantıyı düzenle' : 'Yeni bağlantı')

@section('header')
    <x-admin.page-header :title="$editing ? __('Bağlantıyı düzenle') : __('Yeni bağlantı')" :breadcrumb="['Aktiviteler' => route('activities.index'), 'Partner bağlantıları' => route('partner-links.index')]"
        :description="__('Partneri ve şehri seç, partnerin o şehirdeki bütün aktivitelerinin sayfasını yapıştır. Kartın yazıları sitede her dilde otomatik çıkar.')">
        <x-slot:icon><path d="M14 4h6v6M20 4l-9 9M18 14v4a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4" /></x-slot:icon>
    </x-admin.page-header>
@endsection

@section('footer-action')
    <button type="submit" form="partner-link-form"
        class="inline-flex items-center gap-2 rounded-xl bg-brand-900 px-5 py-2.5 font-semibold text-white shadow-sm transition hover:bg-brand-800">
        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12.5l4.5 4.5L19 7.5" /></svg>
        {{ $editing ? __('Kaydet') : __('Bağlantıyı ekle') }}
    </button>
@endsection

@section('content')
    <form id="partner-link-form" action="{{ $editing ? route('partner-links.update', $link) : route('partner-links.store') }}" method="POST" enctype="multipart/form-data" class="grid gap-6 xl:grid-cols-5">
        @csrf
        @if ($editing) @method('PUT') @endif

        <x-admin.card class="space-y-5 p-6 sm:p-8 xl:col-span-3">
            <label class="block">
                <span class="text-sm font-medium text-slate-700">{{ __('Partner') }}</span>
                <select name="partner_id" required data-preview="partner" @class([$field, $err('partner_id')])>
                    <option value="">{{ __('Seç…') }}</option>
                    @foreach ($partners as $partner)
                        <option value="{{ $partner->id }}" @selected((string) old('partner_id', $link->partner_id) === (string) $partner->id)>{{ $partner->name }}{{ $partner->active ? '' : ' (' . __('pasif') . ')' }}</option>
                    @endforeach
                </select>
                @error('partner_id') <span class="mt-1.5 block text-xs font-medium text-red-600">{{ $message }}</span> @enderror
            </label>

            <label class="block">
                <span class="text-sm font-medium text-slate-700">{{ __('Şehir') }}</span>
                <select name="city_id" data-preview="city" @class([$field, $err('city_id')])>
                    <option value="">{{ __('Bütün şehirler') }}</option>
                    @foreach ($cities as $city)
                        <option value="{{ $city->id }}" @selected((string) old('city_id', $link->city_id) === (string) $city->id)>{{ $city->country?->flag }} {{ $city->name }}</option>
                    @endforeach
                </select>
                <span class="mt-1 block text-xs text-slate-500">{{ __('Kart bu şehrin aktivite listesinin sonunda çıkar. “Bütün şehirler” seçersen her şehirde çıkar.') }}</span>
            </label>

            {{-- sekme: aktiviteler sayfasındaki Tümü / Şehir kartları / Deneyimler --}}
            <fieldset>
                <legend class="text-sm font-medium text-slate-700">{{ __('Hangi sekmede?') }}</legend>
                @php $typeValue = (string) old('type', $link->type); @endphp
                <div class="mt-1.5 flex flex-wrap gap-2">
                    @foreach (['' => __('Hepsi'), ...array_map('__', \App\Models\PartnerLink::TYPES)] as $value => $label)
                        <label class="cursor-pointer">
                            <input type="radio" name="type" value="{{ $value }}" class="peer sr-only" @checked($typeValue === (string) $value)>
                            <span class="inline-flex rounded-full border border-slate-200 bg-white px-3.5 py-2 text-sm font-medium text-slate-600 transition hover:border-slate-300 peer-checked:border-emerald-600 peer-checked:bg-emerald-600 peer-checked:text-white">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
                <span class="mt-1 block text-xs text-slate-500">{{ __('“Hepsi”: her sekmede çıkar. Bir sekme seçersen yalnızca o sekmede çıkar ve o sekmede aynı partnerin genel bağlantısının yerine geçer (ör. Şehir kartları sekmesinde partnerin şehir kartı sayfası).') }}</span>
            </fieldset>

            <label class="block">
                <span class="text-sm font-medium text-slate-700">{{ __('Bağlantı') }} <span class="font-normal text-slate-400">· {{ __('isteğe bağlı') }}</span></span>
                <input type="url" name="url" maxlength="1000" placeholder="https://www.megapass.com/istanbul?ref=…" value="{{ old('url', $link->url) }}" autocomplete="off" @class([$field, $err('url')])>
                <span class="mt-1 block text-xs text-slate-500">{{ __('Partnerin o şehirdeki aktiviteler sayfası (takip kodunla). Boş bırakırsan partnerin kendi sitesine gider (Partnerler\'de kayıtlı adres).') }}</span>
                @error('url') <span class="mt-1.5 block text-xs font-medium text-red-600">{{ $message }}</span> @enderror
            </label>

            <label class="block">
                <span class="text-sm font-medium text-slate-700">{{ __('Aktivite sayısı') }} <span class="font-normal text-slate-400">· {{ __('isteğe bağlı, ör. 200+') }}</span></span>
                <input type="text" name="count_label" maxlength="20" placeholder="200+" value="{{ old('count_label', $link->count_label) }}" data-preview="count" autocomplete="off" @class([$field, $err('count_label'), 'max-w-[10rem]'])>
                <span class="mt-1 block text-xs text-slate-500">{{ __('Yazarsan kartta “200+ aktivite daha” diye görünür; boş bırakırsan sayı yazılmaz.') }}</span>
            </label>

            <x-admin.image-upload name="image" :label="__('Kart resmi (isteğe bağlı)')" :current="$link->imageUrl('md')" :focus="$link->image_focus ?? 50" placeholder="{{ __('Resim yok') }}"
                :hint="__('Dikey resim: 1200 × 1500 px (4:5). Yazılar resmin alt yarısına gelir, ana konu üst-orta kısımda olsun. Kaydırıcı: kart darken resmin hangi tarafı görünsün. JPG, PNG ya da WebP · en fazla 5 MB')" />
            @error('image') <span class="block text-xs font-medium text-red-600">{{ $message }}</span> @enderror
            {{-- grafikere / yapay zekâya verilecek tarif (kopyalanabilir) --}}
            <details class="group rounded-xl bg-slate-50 ring-1 ring-slate-200">
                <summary class="flex cursor-pointer list-none items-center justify-between gap-2 px-4 py-3 text-sm font-semibold text-slate-700">
                    <span class="inline-flex items-center gap-2">
                        <svg class="size-4 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z" /></svg>
                        {{ __('Grafikere / yapay zekâya ne söyleyeyim?') }}
                    </span>
                    <svg class="size-4 text-slate-400 transition group-open:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="m6 9 6 6 6-6" /></svg>
                </summary>
                <div class="space-y-4 border-t border-slate-200 px-4 py-4">
                    @foreach ([[__('Grafiker için (Türkçe)'), 'brief'], [__('Yapay zekâ resim aracı için (İngilizce komut)'), 'prompt']] as [$title, $key])
                        <div>
                            <div class="flex items-center justify-between gap-2">
                                <p class="text-xs font-bold uppercase tracking-wider text-slate-500">{{ $title }}</p>
                                <button type="button" data-copy-brief="{{ $key }}" class="rounded-lg bg-white px-2.5 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-200 transition hover:bg-emerald-50">{{ __('Kopyala') }}</button>
                            </div>
                            <pre data-brief="{{ $key }}" class="mt-1.5 max-h-56 overflow-auto whitespace-pre-wrap rounded-lg bg-white p-3 font-sans text-xs leading-relaxed text-slate-700 ring-1 ring-slate-200">{{ $key === 'brief' ? $brief : $prompt }}</pre>
                        </div>
                    @endforeach
                    <p class="text-xs text-slate-500">{{ __('Başka şehir için İstanbul ve simgelerini değiştir (ör. Roma: Kolezyum ve Roma çatıları).') }}</p>
                </div>
            </details>

            <label class="flex items-center gap-3">
                <input type="hidden" name="active" value="0">
                <input type="checkbox" name="active" value="1" @checked(old('active', $link->active)) class="size-5 rounded accent-brand-900">
                <span class="text-sm font-medium text-slate-700">{{ __('Yayında') }}</span>
            </label>
        </x-admin.card>

        {{-- sitede nasıl görünür (Türkçe örnek) --}}
        <div class="xl:col-span-2">
            <p class="mb-2 text-xs font-semibold uppercase tracking-wider text-slate-400">{{ __('Sitede böyle görünür') }}</p>
            <div class="relative isolate flex aspect-[4/5] max-w-xs flex-col justify-between overflow-hidden rounded-3xl bg-gradient-to-br from-slate-900 via-[#1f2a4d] to-[#C62E2E] p-6 text-white shadow-xl">
                <div class="pointer-events-none absolute -right-10 -top-10 -z-10 size-40 rounded-full bg-white/10 blur-2xl"></div>
                <span class="grid size-12 place-items-center rounded-2xl bg-white text-slate-900 shadow">
                    <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7" /><path d="m20 20-3.5-3.5" /></svg>
                </span>
                <div>
                    <p class="text-xl font-extrabold leading-tight">Aradığını bulamadın mı?</p>
                    <p class="mt-2 text-sm text-white/75"><span data-preview-text="partner">Partner</span>'da <span data-preview-text="city">şehir</span> için <span data-preview-text="count"></span> daha fazla aktivite</p>
                </div>
                <span class="inline-flex items-center justify-center gap-2 rounded-xl bg-white px-4 py-3 text-sm font-bold text-slate-900"><span data-preview-text="partner">Partner</span>'da gör ↗</span>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        // önizleme: seçilen partner, şehir ve sayı
        (() => {
            const form = document.getElementById('partner-link-form');
            const sync = () => {
                const partner = form.querySelector('[data-preview="partner"]');
                const city = form.querySelector('[data-preview="city"]');
                const count = form.querySelector('[data-preview="count"]');
                const set = (key, text) => document.querySelectorAll(`[data-preview-text="${key}"]`).forEach((el) => el.textContent = text);
                set('partner', partner.value ? partner.selectedOptions[0].textContent.replace(/\s*\(.*\)$/, '') : 'Partner');
                set('city', city.value ? city.selectedOptions[0].textContent.trim().replace(/^\S+\s/, '') : 'her şehir');
                set('count', count.value.trim());
            };
            form.addEventListener('input', sync);
            sync();
            document.querySelectorAll('[data-copy-brief]').forEach((btn) => btn.addEventListener('click', async () => {
                const text = document.querySelector(`[data-brief="${btn.dataset.copyBrief}"]`).textContent.trim();
                try { await navigator.clipboard.writeText(text); const t = btn.textContent; btn.textContent = '✓'; setTimeout(() => btn.textContent = t, 1500); } catch {}
            }));
        })();
    </script>
@endpush
