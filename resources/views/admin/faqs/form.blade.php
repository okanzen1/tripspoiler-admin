@extends('layouts.admin')

@php
    $editing = $faq->exists;
    $steps = ['Soru ve cevap', 'Nerede görünecek', 'Yayın'];
    $stepFields = [['question', 'answer'], ['target', 'target_kind'], ['active']];
    if (!$editing) {
        $steps[] = 'Onay';
    }
    $start = collect($stepFields)->search(fn ($fields) => $errors->hasAny($fields));
    $field = 'mt-1.5 block w-full rounded-xl border bg-white px-3.5 py-2.5 text-sm outline-none transition placeholder:text-slate-400 focus:border-brand-900 focus:ring-4 focus:ring-brand-900/10';
    $selectedTarget = old('target', $target);
@endphp

@section('title', $editing ? 'Soruyu düzenle' : 'Yeni soru')

@section('header')
    <x-admin.page-header :title="$editing ? 'Soruyu düzenle' : 'Yeni soru'" :breadcrumb="['Sıkça sorulan sorular' => route('faqs.index')]"
        :description="$editing ? \Illuminate\Support\Str::limit($faq->question, 90) : 'Ziyaretçilerin sık sorduğu bir soruyu cevabıyla ekle.'">
        <x-slot:icon><circle cx="12" cy="12" r="9" /><path d="M9.5 9a2.5 2.5 0 1 1 3.5 2.3c-.6.3-1 .9-1 1.6V14M12 17h.01" /></x-slot:icon>
    </x-admin.page-header>
@endsection

@section('content')
    @if ($editing)
        <x-admin.translation-bar type="faq" :model="$faq" />
    @endif

    <x-admin.wizard :action="$editing ? route('faqs.update', $faq) : route('faqs.store')" :method="$editing ? 'PUT' : null"
        :steps="$steps" :start="$start === false ? 0 : $start" :free="$editing"
        :submit-label="$editing ? 'Kaydet' : 'Soruyu ekle'" :cancel-url="route('faqs.index')">

        <section data-step class="space-y-5">
            <x-admin.step-heading :title="__('Soru ve cevap')" :description="__('İngilizce yaz; kaydettikten sonra yukarıdaki Çeviriler\'den diğer dillere çevir.')" />
            <x-admin.input name="question" :label="__('Soru')" :value="$faq->question" required maxlength="500" placeholder="{{ __('Do I need to print my ticket?') }}" autocomplete="off" />
            <div>
                <label for="answer" class="text-sm font-medium text-slate-700">{{ __('Cevap') }}</label>
                <textarea id="answer" name="answer" rows="6" required maxlength="5000" placeholder="{{ __('No, you can show your ticket on your phone.') }}"
                    @class([$field, 'resize-y leading-relaxed', 'border-slate-200' => !$errors->has('answer'), 'border-red-300' => $errors->has('answer')])>{{ old('answer', $faq->answer) }}</textarea>
                @error('answer') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
            </div>
        </section>

        <section data-step hidden class="space-y-6" data-faq-target>
            <x-admin.step-heading :title="__('Nerede görünecek')" :description="__('Önce sayfa türünü seç, sonra gerekiyorsa şehri, aktiviteyi ya da yazıyı.')" />
            @php
                // Sayfalar 3 gruba ayrılır: şehre göre değişenler (Aktiviteler, Şehirler, Blog), detay sayfaları, diğer sayfalar
                $cityPageKeys = ['activities', 'cities', 'blog'];
                $cityPages = $pages->whereIn('key', $cityPageKeys)->sortBy(fn ($p) => array_search($p->key, $cityPageKeys))->values();
                $otherPages = $pages->whereNotIn('key', [...$cityPageKeys, 'activity', 'post'])->values();
                // kayıtlı yer → seçimler
                [$tType, $tId] = array_pad(explode(':', (string) $selectedTarget, 2), 2, null);
                [$tPage, $tCity] = $tType === 'page' ? array_pad(explode('@', (string) $tId, 2), 2, null) : [null, null];
                $init = match (true) {
                    $tType === 'activity' => ['kind' => 'activity', 'id' => $tId],
                    $tType === 'blog' => ['kind' => 'blog', 'id' => $tId],
                    $tType === 'city' => ['kind' => 'city', 'page' => 'all', 'city' => $tId],
                    $tType === 'page' && in_array($tPage, $cityPageKeys, true) => ['kind' => 'city', 'page' => $tPage, 'city' => $tCity ?? ''],
                    $tType === 'page' => ['kind' => 'other', 'page' => $tPage],
                    default => ['kind' => 'general'],
                };
                $kinds = [
                    'general' => ['Anasayfa', 'Anasayfanın en altındaki soru bölümü.', '<path d="M4 11.5 12 5l8 6.5V19a1 1 0 0 1-1 1h-4.5v-5h-5v5H5a1 1 0 0 1-1-1z"/>'],
                    'city' => ['Aktiviteler · Şehirler · Blog', 'Ziyaretçinin seçtiği şehre göre değişen sayfalar.', '<path d="M12 21s-7-6.2-7-11.5A7 7 0 0 1 19 9.5C19 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/>'],
                    'activity' => ['Bir aktivitenin sayfası', 'Sadece seçtiğin aktivitenin detay sayfasında.', '<path d="M4 9.5a2 2 0 0 0 0 4v4a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-4a2 2 0 0 1 0-4v-4a1 1 0 0 0-1-1H5a1 1 0 0 0-1 1z"/>'],
                    'blog' => ['Bir blog yazısının sayfası', 'Sadece seçtiğin yazının en altında.', '<path d="M4 20h4L19 9a2.8 2.8 0 0 0-4-4L4 16z"/><path d="m13.5 6.5 4 4"/>'],
                    'other' => ['Diğer sayfalar', 'Fırsatlar, Hava durumu, Yerel saat, Hakkımızda…', '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8Z"/><path d="M14 3v5h5M9 13h6M9 17h4"/>'],
                ];
                if ($blogs->isEmpty()) { unset($kinds['blog']); }
                $chip = 'cursor-pointer rounded-xl bg-white px-3.5 py-2 text-sm font-semibold text-slate-700 ring-1 ring-slate-200 transition hover:bg-slate-50 has-[:checked]:bg-brand-900 has-[:checked]:text-white has-[:checked]:ring-brand-900';
            @endphp

            {{-- gönderilen değer (seçimlere göre doldurulur) --}}
            <input type="hidden" name="target" value="{{ $selectedTarget }}" data-target-value data-label="">

            {{-- 1. sayfa türü --}}
            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($kinds as $kind => [$label, $description, $icon])
                    <label class="relative flex cursor-pointer gap-3 rounded-2xl bg-white p-4 ring-1 ring-slate-200 transition hover:bg-slate-50 has-[:checked]:bg-brand-50 has-[:checked]:ring-2 has-[:checked]:ring-brand-900">
                        <input type="radio" name="target_kind" value="{{ $kind }}" class="sr-only" @checked($init['kind'] === $kind)>
                        <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-slate-100 text-slate-600">
                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">{!! $icon !!}</svg>
                        </span>
                        <span>
                            <span class="block text-sm font-semibold text-slate-900">{{ __($label) }}</span>
                            <span class="mt-0.5 block text-xs leading-relaxed text-slate-500">{{ __($description) }}</span>
                        </span>
                    </label>
                @endforeach
            </div>

            {{-- 2a. şehre göre değişen sayfalar: hangi sayfa + hangi şehir --}}
            <div data-panel="city" class="space-y-4 rounded-2xl bg-slate-50 p-5 ring-1 ring-slate-200">
                <div>
                    <p class="text-sm font-semibold text-slate-800">{{ __('Hangi sayfada?') }}</p>
                    <div class="mt-2 flex flex-wrap gap-2">
                        @foreach ($cityPages as $page)
                            <label class="{{ $chip }}"><input type="radio" name="city_page" value="{{ $page->key }}" class="sr-only" @checked(($init['page'] ?? null) === $page->key)>{{ $page->name }}</label>
                        @endforeach
                        <label class="{{ $chip }}"><input type="radio" name="city_page" value="all" class="sr-only" @checked(($init['page'] ?? null) === 'all')>{{ __('Üçünde de') }}</label>
                    </div>
                </div>
                <div>
                    <label for="faq-city" class="text-sm font-semibold text-slate-800">{{ __('Hangi şehirde?') }}</label>
                    <select id="faq-city" data-city @class([$field, 'mt-2 border-slate-200'])>
                        <option value="" data-all>{{ __('Bütün şehirlerde') }}</option>
                        @foreach ($cities as $city)
                            <option value="{{ $city->id }}" @selected((string) ($init['city'] ?? '') === (string) $city->id)>{{ $city->name }}{{ $city->country ? ' · ' . $city->country->name : '' }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1.5 text-xs text-slate-500" data-city-hint></p>
                </div>
            </div>

            {{-- 2b. aktivite --}}
            <div data-panel="activity" class="space-y-1.5">
                <label for="faq-activity" class="text-sm font-semibold text-slate-800">{{ __('Hangi aktivite?') }}</label>
                <select id="faq-activity" data-pick="activity" @class([$field, 'border-slate-200'])>
                    <option value="">{{ __('Aktivite seç…') }}</option>
                    @foreach ($activities as $cityName => $cityActivities)
                        <optgroup label="{{ $cityName }}">
                            @foreach ($cityActivities as $activity)
                                <option value="{{ $activity->id }}" @selected(($init['kind'] === 'activity') && (string) $init['id'] === (string) $activity->id)>{{ $activity->name }}</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
            </div>

            {{-- 2c. blog yazısı --}}
            @if ($blogs->isNotEmpty())
                <div data-panel="blog" class="space-y-1.5">
                    <label for="faq-blog" class="text-sm font-semibold text-slate-800">{{ __('Hangi yazı?') }}</label>
                    <select id="faq-blog" data-pick="blog" @class([$field, 'border-slate-200'])>
                        <option value="">{{ __('Yazı seç…') }}</option>
                        @foreach ($blogs as $blog)
                            <option value="{{ $blog->id }}" @selected(($init['kind'] === 'blog') && (string) $init['id'] === (string) $blog->id)>{{ $blog->title }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            {{-- 2d. diğer sayfalar --}}
            <div data-panel="other" class="rounded-2xl bg-slate-50 p-5 ring-1 ring-slate-200">
                <p class="text-sm font-semibold text-slate-800">{{ __('Hangi sayfada?') }}</p>
                <div class="mt-2 flex flex-wrap gap-2">
                    @foreach ($otherPages as $page)
                        <label class="{{ $chip }}"><input type="radio" name="other_page" value="{{ $page->key }}" class="sr-only" @checked(($init['kind'] === 'other') && ($init['page'] ?? null) === $page->key)>{{ $page->name }}</label>
                    @endforeach
                </div>
                <p class="mt-2 text-xs text-slate-500">{{ __('O sayfanın Bölümler kartında "Sık sorulan sorular" açık olmalı.') }}</p>
            </div>

            <p class="rounded-xl bg-white px-4 py-3 text-sm text-slate-700 ring-1 ring-slate-200"><span class="text-slate-400">{{ __('Görüneceği yer:') }}</span> <strong data-target-summary class="font-semibold"></strong></p>
            @error('target') <p class="text-xs font-medium text-red-600">{{ $message }}</p> @enderror

            <script>
                // Seçimlere göre "target" değeri: general · page:KEY · page:KEY@CITY · city:ID · activity:ID · blog:ID
                (() => {
                    const box = document.currentScript.closest('[data-faq-target]');
                    const out = box.querySelector('[data-target-value]');
                    const summary = box.querySelector('[data-target-summary]');
                    const city = box.querySelector('[data-city]');
                    const hint = box.querySelector('[data-city-hint]');
                    const val = (name) => box.querySelector(`input[name=${name}]:checked`)?.value ?? '';
                    const text = (el) => el?.closest('label')?.textContent.trim() ?? '';
                    const t = @js(['home' => __('Anasayfa'), 'all' => __('Aktiviteler, Şehirler ve Blog'), 'allCities' => __('bütün şehirlerde'), 'only' => __('sadece :city seçiliyken'), 'need' => __('Üç sayfa birden seçtiysen bir şehir seç.'), 'pick' => __('Seçim yapılmadı')]);
                    const sync = () => {
                        const kind = val('target_kind') || 'general';
                        box.querySelectorAll('[data-panel]').forEach((p) => (p.hidden = p.dataset.panel !== kind));
                        let target = '', label = '';
                        if (kind === 'general') { target = 'general'; label = t.home; }
                        if (kind === 'city') {
                            const page = val('city_page'), c = city.value, cityName = c ? city.selectedOptions[0].textContent.split(' · ')[0] : '';
                            city.querySelector('[data-all]').disabled = page === 'all';
                            if (page === 'all' && !c) { hint.textContent = t.need; }
                            else { hint.textContent = ''; }
                            if (page && page !== 'all') target = 'page:' + page + (c ? '@' + c : '');
                            if (page === 'all' && c) target = 'city:' + c;
                            const pageName = page === 'all' ? t.all : text(box.querySelector(`input[name=city_page][value="${page}"]`));
                            label = page ? pageName + ' · ' + (c ? t.only.replace(':city', cityName) : t.allCities) : '';
                        }
                        if (kind === 'activity' || kind === 'blog') {
                            const sel = box.querySelector(`[data-pick=${kind}]`);
                            if (sel?.value) { target = kind + ':' + sel.value; label = sel.selectedOptions[0].textContent.trim(); }
                        }
                        if (kind === 'other') { const page = val('other_page'); if (page) { target = 'page:' + page; label = text(box.querySelector(`input[name=other_page][value="${page}"]`)); } }
                        out.value = target;
                        out.dataset.label = label;
                        summary.textContent = label || t.pick;
                        summary.className = label ? 'font-semibold text-slate-900' : 'font-semibold text-red-600';
                    };
                    box.addEventListener('change', sync);
                    sync();
                })();
            </script>
        </section>

        <section data-step hidden class="space-y-5">
            <x-admin.step-heading :title="__('Yayın')" :description="__('Soru sitede görünsün mü?')" />
            <x-admin.status-picker :active="$faq->active" />
        </section>

        @unless ($editing)
            <section data-step hidden class="space-y-5">
                <x-admin.step-heading :title="__('Onay')" :description="__('Bilgileri kontrol et, doğruysa soruyu ekle.')" />
                <dl class="divide-y divide-slate-100 overflow-hidden rounded-2xl bg-white ring-1 ring-slate-200">
                    @foreach (['Soru' => 'question', 'Cevap' => 'answer', 'Yer' => 'target', 'Durum' => 'active'] as $label => $name)
                        <div class="grid grid-cols-3 gap-4 px-5 py-3.5 text-sm">
                            <dt class="text-slate-500">{{ __($label) }}</dt>
                            <dd class="col-span-2 whitespace-pre-line font-medium text-slate-900" data-summary-for="{{ $name }}">—</dd>
                        </div>
                    @endforeach
                </dl>
            </section>
        @endunless
    </x-admin.wizard>
@endsection
