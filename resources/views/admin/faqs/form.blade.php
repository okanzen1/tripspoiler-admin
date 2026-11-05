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

        <section data-step hidden class="space-y-5" data-faq-target>
            <x-admin.step-heading :title="__('Nerede görünecek')" :description="__('Sorunun sitede çıkacağı yeri seç. Her seçeneğin altında tam olarak nerede görüneceği yazıyor.')" />
            @php
                $kinds = [
                    'general' => ['Anasayfa', 'Anasayfanın en altındaki "Sık sorulan sorular" bölümünde.', '<path d="M4 11.5 12 5l8 6.5V19a1 1 0 0 1-1 1h-4.5v-5h-5v5H5a1 1 0 0 1-1-1z"/>'],
                    'page' => ['Bir sayfa', 'Seçtiğin sayfanın SSS bölümünde (ör. Aktiviteler), hangi şehir seçili olursa olsun.', '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8Z"/><path d="M14 3v5h5M9 13h6M9 17h4"/>'],
                    'city' => ['Bir şehir', 'Ziyaretçi o şehri seçtiyse SSS bölümü olan her sayfada (Aktiviteler, Şehirler, Blog…).', '<path d="M12 21s-7-6.2-7-11.5A7 7 0 0 1 19 9.5C19 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/>'],
                    'activity' => ['Bir aktivitenin sayfası', 'Sadece o aktivitenin detay sayfasında, açıklamanın altında.', '<path d="M4 8a2 2 0 0 0 0 4v4a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-4a2 2 0 0 1 0-4V5a1 1 0 0 0-1-1H5a1 1 0 0 0-1 1z" transform="translate(0 1.5)"/><path d="M14 5.5v3M14 11v2.5M14 16v2.5"/>'],
                    'blog' => ['Bir blog yazısı', 'Sadece o yazının en altında.', '<path d="M4 20h4L19 9a2.8 2.8 0 0 0-4-4L4 16z"/><path d="m13.5 6.5 4 4"/>'],
                ];
                [$selectedKind, $selectedId] = array_pad(explode(':', (string) $selectedTarget, 2), 2, null);
                if (!isset($kinds[$selectedKind])) { $selectedKind = 'general'; }
                if ($blogs->isEmpty()) { unset($kinds['blog']); }
            @endphp

            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($kinds as $kind => [$label, $description, $icon])
                    <label class="relative flex cursor-pointer gap-3 rounded-2xl bg-white p-4 ring-1 ring-slate-200 transition hover:bg-slate-50 has-[:checked]:bg-brand-50 has-[:checked]:ring-2 has-[:checked]:ring-brand-900">
                        <input type="radio" name="target_kind" value="{{ $kind }}" data-label="{{ __($label) }}" class="sr-only" @checked($selectedKind === $kind)>
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

            {{-- Seçime göre ikinci adım: hangi sayfa / şehir / aktivite / yazı. Sadece açık olan gönderilir. --}}
            <input type="hidden" name="target" value="general" data-target-for="general" data-label="{{ __('Anasayfa') }}">

            <div data-target-for="page" class="space-y-1.5">
                <label for="target-page" class="text-sm font-medium text-slate-700">{{ __('Hangi sayfa?') }}</label>
                <select id="target-page" name="target" required @class([$field, 'border-slate-200'])>
                    <option value="">{{ __('Sayfa seç…') }}</option>
                    @foreach ($pages as $page)
                        <option value="page:{{ $page->key }}" @selected($selectedTarget === "page:{$page->key}")>{{ $page->name }}</option>
                    @endforeach
                </select>
                <p class="text-xs text-slate-500">{{ __('O sayfanın Bölümler kartında "Sık sorulan sorular" açık olmalı.') }}</p>
            </div>

            <div data-target-for="city" class="space-y-1.5">
                <label for="target-city" class="text-sm font-medium text-slate-700">{{ __('Hangi şehir?') }}</label>
                <select id="target-city" name="target" required @class([$field, 'border-slate-200'])>
                    <option value="">{{ __('Şehir seç…') }}</option>
                    @foreach ($cities as $city)
                        <option value="city:{{ $city->id }}" @selected($selectedTarget === "city:{$city->id}")>{{ $city->name }}{{ $city->country ? ' · ' . $city->country->name : '' }} ({{ __(':n aktivite', ['n' => $city->activities_count]) }})</option>
                    @endforeach
                </select>
                <p class="text-xs text-slate-500">{{ __('Sayfaların Bölümler kartında "Sık sorulan sorular" açık olan yerlerde çıkar.') }}</p>
            </div>

            <div data-target-for="activity" class="space-y-1.5">
                <label for="target-activity" class="text-sm font-medium text-slate-700">{{ __('Hangi aktivite?') }}</label>
                <select id="target-activity" name="target" required @class([$field, 'border-slate-200'])>
                    <option value="">{{ __('Aktivite seç…') }}</option>
                    @foreach ($activities as $cityName => $cityActivities)
                        <optgroup label="{{ $cityName }}">
                            @foreach ($cityActivities as $activity)
                                <option value="activity:{{ $activity->id }}" @selected($selectedTarget === "activity:{$activity->id}")>{{ $activity->name }}</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
            </div>

            @if ($blogs->isNotEmpty())
                <div data-target-for="blog" class="space-y-1.5">
                    <label for="target-blog" class="text-sm font-medium text-slate-700">{{ __('Hangi yazı?') }}</label>
                    <select id="target-blog" name="target" required @class([$field, 'border-slate-200'])>
                        <option value="">{{ __('Yazı seç…') }}</option>
                        @foreach ($blogs as $blog)
                            <option value="blog:{{ $blog->id }}" @selected($selectedTarget === "blog:{$blog->id}")>{{ $blog->title }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            @error('target') <p class="text-xs font-medium text-red-600">{{ $message }}</p> @enderror

            <script>
                // Seçilen türün alanı görünür ve gönderilir; diğerleri kapalı (gönderilmez, zorunlu kontrolüne takılmaz)
                (() => {
                    const box = document.currentScript.closest('[data-faq-target]');
                    const sync = () => {
                        const kind = box.querySelector('input[name=target_kind]:checked')?.value ?? 'general';
                        box.querySelectorAll('[data-target-for]').forEach((el) => {
                            const on = el.dataset.targetFor === kind;
                            if (el.tagName !== 'INPUT') el.hidden = !on;
                            el.querySelectorAll('select').forEach((select) => (select.disabled = !on));
                            if (el.tagName === 'INPUT') el.disabled = !on;
                        });
                    };
                    box.addEventListener('change', (event) => event.target.name === 'target_kind' && sync());
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
