@extends('layouts.admin')

@php
    $editing = $attraction->exists;
    $field = 'mt-1.5 block w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm outline-none transition placeholder:text-slate-400 focus:border-brand-900 focus:ring-4 focus:ring-brand-900/10';
    $faqRows = old('faqs', $editing ? $attraction->faqs->map(fn ($f) => ['id' => $f->id, 'question' => $f->question, 'answer' => $f->answer])->all() : []);
    $linked = collect(old('activity_ids', $editing ? $attraction->activity_ids : []))->map(fn ($id) => (int) $id)->all();
    $steps = ['Yer', 'Anlatım', 'Ziyaret bilgileri', 'Aktiviteler', 'SSS', 'SEO ve yayın'];
@endphp

@section('title', $editing ? 'Yeri düzenle' : 'Yeni yer')

@section('header')
    <x-admin.page-header :title="$editing ? $attraction->getTranslation('name', 'en', false) : __('Yeni yer')" :breadcrumb="['Yerler' => route('attractions.index')]"
        :description="__('İngilizce yaz; kaydettikten sonra yukarıdaki Çeviriler\'den 12 dile çevir. Sadece emin olduğun bilgileri yaz.')">
        <x-slot:icon><path d="M3 21h18M5 21V10l7-5 7 5v11M9 21v-6h6v6" /></x-slot:icon>
        @if ($editing)
            <a href="{{ config('site.url') }}/en/attractions/{{ $attraction->slug }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-600 ring-1 ring-slate-200 transition hover:bg-slate-50">{{ __('Sitede aç') }} ↗</a>
        @endif
    </x-admin.page-header>
@endsection

@section('content')
    @if ($editing)
        <x-admin.translation-bar type="attraction" :model="$attraction" />
    @endif

    <x-admin.wizard :action="$editing ? route('attractions.update', $attraction) : route('attractions.store')" :method="$editing ? 'PUT' : null"
        :steps="$steps" :start="0" :free="$editing"
        :submit-label="$editing ? 'Kaydet' : 'Yeri ekle'" :cancel-url="route('attractions.index')">

        <section data-step class="space-y-5">
            <x-admin.step-heading :title="__('Yer')" :description="__('Hangi şehirde, adı ne, bir iki cümlelik kısa tanıtım ve kapak resmi.')" />
            <label class="block">
                <span class="text-sm font-medium text-slate-700">{{ __('Şehir') }}</span>
                <select name="city_id" required class="{{ $field }}">
                    @foreach ($cities as $city)
                        <option value="{{ $city->id }}" @selected((int) old('city_id', $attraction->city_id) === $city->id)>{{ $city->name }}</option>
                    @endforeach
                </select>
            </label>
            <x-admin.input name="name" :label="__('Ad')" :value="$attraction->getTranslation('name', 'en', false)" required maxlength="120" placeholder="Topkapi Palace" />
            <x-admin.input name="slug" :label="__('Adres (slug)')" :value="$attraction->slug" maxlength="120" placeholder="topkapi-palace" :hint="__('Boş bırakırsan addan üretilir. Sitede /attractions/topkapi-palace')" />
            <label class="block">
                <span class="text-sm font-medium text-slate-700">{{ __('Kısa tanıtım') }}</span>
                <textarea name="summary" rows="3" maxlength="400" class="{{ $field }} resize-y leading-relaxed" placeholder="The palace of the Ottoman sultans for almost 400 years…">{{ old('summary', $attraction->getTranslation('summary', 'en', false)) }}</textarea>
            </label>
            <x-admin.image-upload name="image" :label="__('Kapak resmi')" :current="$attraction->imageUrl()" shape="wide"
                :note="__('Önerilen: yatay, en az 1600 × 900 piksel (16:9). Yerin kendisi ortada olsun; kenarlar telefonda kırpılabilir. JPG, PNG ya da WebP, en fazla 15 MB.')"
                :hint="__('Yatay resim · en az 1600 × 900 px · en fazla 15 MB')" />
        </section>

        <section data-step hidden class="space-y-5">
            <x-admin.step-heading :title="__('Anlatım')" :description="__('Yerin tarihi, öne çıkanları, nasıl gezilir. Başlıklar (H2) ve tablo kullanabilirsin.')" />
            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white" data-rich-editor="content" data-upload-url="{{ route('editor-images.store') }}" data-media-url="{{ \App\Services\ImageUploader::baseUrl() }}" data-placeholder="{{ __('Yeri anlat…') }}">
                <div class="min-h-80 text-[15px]"></div>
            </div>
            <input type="hidden" name="content" value="{{ old('content', $attraction->getTranslation('content', 'en', false)) }}" />
        </section>

        <section data-step hidden class="space-y-5">
            <x-admin.step-heading :title="__('Ziyaret bilgileri')" :description="__('Sitede bilgi tablosu olarak çıkar. Saatleri resmi siteden kontrol et; emin olmadığını boş bırak.')" />
            <div class="grid gap-4 md:grid-cols-2">
                @foreach (\App\Models\Attraction::VISIT_FIELDS as $visit => [$visitLabel, $visitHint, $visitList])
                    <label @class(['block', 'md:col-span-2' => $visitList])>
                        <span class="text-sm font-medium text-slate-700">{{ __($visitLabel) }}</span>
                        <textarea name="{{ $visit }}" rows="{{ $visitList ? 5 : 2 }}" placeholder="{{ $visitHint }}" class="{{ $field }} resize-y leading-relaxed">{{ old($visit, $attraction->getTranslation($visit, 'en', false)) }}</textarea>
                    </label>
                @endforeach
            </div>
            <x-admin.input name="official_url" :label="__('Resmi site (isteğe bağlı)')" :value="$attraction->official_url" maxlength="500" placeholder="https://muze.gov.tr" :hint="__('Sadece gerçek resmi site. Google bunu güven işareti sayar.')" />
        </section>

        <section data-step hidden class="space-y-5">
            <x-admin.step-heading :title="__('Aktiviteler')" :description="__('Bu yere giriş içeren aktiviteleri seç. Kombolar birden fazla yere bağlanabilir; passler en üstte gösterilir.')" />
            @php $byId = $activities->keyBy('id'); @endphp
            {{-- Aranabilir seçim: kutuya yaz, listeden seç; seçilenler aşağıda etiket olur (× ile çıkar) --}}
            <div data-activity-select class="space-y-3">
                <div class="relative">
                    <x-admin.icon name="search" class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                    <input type="search" data-select-search autocomplete="off" placeholder="{{ __('Aktivite ara ve seç (ad, şehir ya da ID)…') }}"
                        class="block w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-9 pr-3 text-sm outline-none focus:border-brand-900 focus:ring-2 focus:ring-brand-900/15" />
                    <ul data-select-list hidden class="mt-1 max-h-80 overflow-y-auto rounded-xl bg-white py-1 shadow-lg ring-1 ring-slate-200">
                        @foreach ($activities->groupBy(fn ($a) => $a->city?->name) as $cityName => $group)
                            <li data-group class="px-3 pb-1 pt-2 text-[11px] font-bold uppercase tracking-wide text-slate-400">{{ $cityName }}</li>
                            @foreach ($group as $activity)
                                <li data-option data-id="{{ $activity->id }}" data-search="{{ mb_strtolower($activity->getTranslation('name', 'en', false) . ' ' . $cityName . ' #' . $activity->id) }}"
                                    class="flex cursor-pointer items-center gap-2 px-3 py-2 text-sm hover:bg-brand-50">
                                    <span class="min-w-0 flex-1 truncate font-medium text-slate-900">{{ $activity->getTranslation('name', 'en', false) }}</span>
                                    <span class="shrink-0 text-xs text-slate-400">#{{ $activity->id }}{{ $activity->active ? '' : ' · ' . __('kapalı') }}</span>
                                </li>
                            @endforeach
                        @endforeach
                        <li data-select-empty hidden class="px-3 py-3 text-sm text-slate-500">{{ __('Bulunamadı.') }}</li>
                    </ul>
                </div>
                <ul data-select-chips class="flex flex-wrap gap-2">
                    @foreach ($linked as $id)
                        @if ($a = $byId->get($id))
                            <li data-chip data-id="{{ $id }}" class="inline-flex items-center gap-1.5 rounded-full bg-brand-50 py-1 pl-3 pr-1 text-sm font-medium text-brand-900 ring-1 ring-brand-100">
                                <input type="hidden" name="activity_ids[]" value="{{ $id }}">
                                <span>{{ $a->getTranslation('name', 'en', false) }} <span class="text-xs text-slate-400">· {{ $a->city?->name }}</span></span>
                                <button type="button" data-chip-remove class="grid size-6 place-items-center rounded-full text-slate-400 hover:bg-white hover:text-rose-600" aria-label="{{ __('Çıkar') }}">×</button>
                            </li>
                        @endif
                    @endforeach
                </ul>
                <p data-select-none @class(['text-sm text-slate-500', 'hidden' => count($linked) > 0])>{{ __('Henüz aktivite seçilmedi.') }}</p>
                <template data-chip-template>
                    <li data-chip class="inline-flex items-center gap-1.5 rounded-full bg-brand-50 py-1 pl-3 pr-1 text-sm font-medium text-brand-900 ring-1 ring-brand-100">
                        <input type="hidden" name="activity_ids[]">
                        <span data-chip-label></span>
                        <button type="button" data-chip-remove class="grid size-6 place-items-center rounded-full text-slate-400 hover:bg-white hover:text-rose-600" aria-label="{{ __('Çıkar') }}">×</button>
                    </li>
                </template>
            </div>
        </section>

        @include('admin.partials.faq-step', [
            'rows' => $faqRows,
            'description' => 'Bu yerin sayfasında görünecek soru-cevaplar (İngilizce). Google bunları SSS olarak okur.',
        ])

        <section data-step hidden class="space-y-5">
            <x-admin.step-heading :title="__('SEO ve yayın')" :description="__('Boş bırakırsan sitede otomatik başlık kullanılır: “:title”.', ['title' => ($attraction->getTranslation('name', 'en', false) ?: 'Topkapi Palace') . ' Visitor Guide & Things to Do ' . now()->year . ' | TripSpoiler'])" />
            <x-admin.input name="meta_title" :label="__('SEO başlığı')" :value="$attraction->getTranslation('meta_title', 'en', false)" maxlength="70" />
            <label class="block">
                <span class="text-sm font-medium text-slate-700">{{ __('SEO açıklaması') }}</span>
                <textarea name="meta_description" rows="2" maxlength="170" class="{{ $field }} resize-y">{{ old('meta_description', $attraction->getTranslation('meta_description', 'en', false)) }}</textarea>
            </label>
            <x-admin.status-picker :active="$attraction->active" />
            @if ($editing)
                <p class="text-xs text-slate-500">{{ __('Silmek için:') }}
                    <button type="submit" form="delete-attraction" class="font-semibold text-rose-600 hover:underline" data-confirm="{{ __('Bu yer silinsin mi?') }}">{{ __('Yeri sil') }}</button></p>
            @endif
        </section>
    </x-admin.wizard>
    @if ($editing)
        <form id="delete-attraction" method="POST" action="{{ route('attractions.destroy', $attraction) }}" class="hidden">@csrf @method('DELETE')</form>
    @endif
    @push('scripts')
        <script>
            // Aktivite seçimi: ara → seç → etiket; seçili olanlar listede gizlenir
            document.querySelectorAll('[data-activity-select]').forEach((box) => {
                const search = box.querySelector('[data-select-search]'), list = box.querySelector('[data-select-list]');
                const chips = box.querySelector('[data-select-chips]'), none = box.querySelector('[data-select-none]');
                const tpl = box.querySelector('[data-chip-template]'), empty = box.querySelector('[data-select-empty]');
                const picked = () => new Set([...chips.querySelectorAll('[data-chip]')].map((c) => c.dataset.id));

                const filter = () => {
                    const q = search.value.trim().toLocaleLowerCase('tr'), have = picked();
                    let shown = 0, group = null, groupHas = false;
                    const closeGroup = () => { if (group) group.hidden = !groupHas; };
                    list.querySelectorAll('[data-group], [data-option]').forEach((li) => {
                        if (li.hasAttribute('data-group')) { closeGroup(); group = li; groupHas = false; return; }
                        const ok = !have.has(li.dataset.id) && (!q || li.dataset.search.includes(q));
                        li.hidden = !ok;
                        if (ok) { shown++; groupHas = true; }
                    });
                    closeGroup();
                    empty.hidden = shown > 0;
                    none.classList.toggle('hidden', have.size > 0);
                };
                const open = () => { filter(); list.hidden = false; };

                // adıma geçince imleç kutuya kendiliğinden gelir; liste sadece tıklayınca ya da yazınca açılsın
                search.addEventListener('click', open);
                search.addEventListener('input', open);
                search.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape') list.hidden = true;
                    if (e.key === 'Enter') {
                        e.preventDefault(); // formu göndermesin; ilk sonucu seç
                        list.querySelector('[data-option]:not([hidden])')?.dispatchEvent(new MouseEvent('mousedown', { bubbles: true }));
                    }
                });
                search.addEventListener('blur', () => setTimeout(() => { list.hidden = true; }, 120));
                list.addEventListener('mousedown', (e) => {
                    const option = e.target.closest('[data-option]');
                    if (!option) return;
                    e.preventDefault();
                    const chip = tpl.content.firstElementChild.cloneNode(true);
                    chip.dataset.id = option.dataset.id;
                    chip.querySelector('input').value = option.dataset.id;
                    chip.querySelector('[data-chip-label]').textContent = option.querySelector('span').textContent;
                    chips.append(chip);
                    search.value = '';
                    filter();
                });
                chips.addEventListener('click', (e) => {
                    if (!e.target.closest('[data-chip-remove]')) return;
                    e.target.closest('[data-chip]').remove();
                    filter();
                });
            });
        </script>
    @endpush
@endsection
