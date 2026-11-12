@extends('layouts.admin')

@section('title', __('Şehir rehberi') . ' · ' . $city->name)

@php
    $field = 'mt-1.5 block w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm outline-none transition placeholder:text-slate-400 focus:border-brand-900 focus:ring-4 focus:ring-brand-900/10';
    $switch = fn ($active, $label) => '<button type="button" role="switch" data-section-switch aria-checked="' . ($active ? 'true' : 'false') . '" aria-label="' . e($label) . '"
        class="group relative inline-flex h-6 w-11 shrink-0 items-center rounded-full bg-slate-200 transition-colors aria-checked:bg-emerald-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-500">
        <span class="inline-block size-5 translate-x-0.5 rounded-full bg-white shadow-sm ring-1 ring-black/5 transition-transform group-aria-checked:translate-x-[22px]"></span></button>';
@endphp

@section('header')
    <x-admin.page-header :title="$city->name" :breadcrumb="['Şehir rehberi' => route('city-guides.index')]"
        :description="__('Önce genel girişi yaz, sonra alanları doldur. Sağdaki anahtarla bu şehirde aç/kapa; değişiklik hemen kaydedilir.')">
        <x-slot:icon><path d="M9 4 3.5 6v14L9 18l6 2 5.5-2V4L15 6z" /><path d="M9 4v14M15 6v14" /></x-slot:icon>
        <a href="{{ rtrim(config('site.url'), '/') . '/en/cities/' . $city->slug }}" target="_blank" rel="noopener"
            class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-700 ring-1 ring-slate-200 transition hover:bg-white">{{ __('Şehir sayfasını aç') }} ↗</a>
    </x-admin.page-header>
@endsection

@section('content')
    <div class="space-y-6">
        {{-- GENEL GİRİŞ --}}
        @if ($guide->exists)
            <x-admin.translation-bar type="city-guide" :model="$guide" />
        @endif
        <x-admin.card class="p-6 sm:p-8">
            <form action="{{ route('city-guides.intro', $city) }}" method="POST" class="space-y-5">
                @csrf
                @method('PUT')
                <x-admin.step-heading :title="__('Genel giriş')" :description="__('Şehir sayfasının en üstündeki başlık ve kısa açıklama. İngilizce yaz; kaydettikten sonra Çeviriler\'den 12 dile çevir.')" />
                <label class="block">
                    <span class="text-sm font-medium text-slate-700">{{ __('Başlık') }}</span>
                    <input type="text" name="title" maxlength="120" value="{{ old('title', $guide->getTranslation('title', 'en', false)) }}"
                        placeholder="{{ __('Örn: Istanbul travel guide: where east meets west') }}" class="{{ $field }}">
                </label>
                <label class="block">
                    <span class="text-sm font-medium text-slate-700">{{ __('Açıklama') }} <span class="font-normal text-slate-400">· {{ __('2-4 cümle; Google\'da da kullanılır') }}</span></span>
                    <textarea name="description" rows="4" maxlength="1500" placeholder="{{ __('Şehri birkaç cümleyle anlat: neden gidilir, kime göre, kaç gün yeter…') }}"
                        class="{{ $field }} resize-y leading-relaxed">{{ old('description', $guide->getTranslation('description', 'en', false)) }}</textarea>
                </label>
                {{-- BANNER KARTLARI: şehir sayfası fotoğrafının üstündeki iki kart --}}
                @php
                    $cardValues = array_pad(explode(',', (string) $guide->hero_cards), 2, '');
                    $written = $sections->filter->isWritten();
                @endphp
                <div class="rounded-2xl bg-slate-50 p-5 ring-1 ring-slate-200">
                    <p class="text-sm font-semibold text-slate-900">{{ __('Banner\'daki kartlar') }}</p>
                    <p class="mt-0.5 text-sm text-slate-500">{{ __('Şehir sayfasında fotoğrafın üstünde duran iki küçük kart; tıklayınca o konuya gider. Otomatik: kapak resmi olan ilk konular.') }}</p>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        @foreach ([__('Sol alttaki kart'), __('Sağ üstteki kart')] as $i => $cardLabel)
                            <label class="block">
                                <span class="text-sm font-medium text-slate-700">{{ $cardLabel }}</span>
                                <select name="cards[{{ $i }}]" class="{{ $field }}">
                                    <option value="" @selected($cardValues[$i] === '')>{{ __('Otomatik') }}</option>
                                    <option value="0" @selected($cardValues[$i] === '0')>{{ __('Gösterme') }}</option>
                                    @foreach ($written as $section)
                                        <option value="{{ $section->id }}" @selected($cardValues[$i] === (string) $section->id)>
                                            {{ $section->topic->getTranslation('name', 'tr', false) ?: $section->topic->name }} — {{ \Illuminate\Support\Str::limit($section->getTranslation('title', 'en', false), 50) }}{{ $section->image_path ? '' : ' (' . __('resimsiz') . ')' }}{{ $section->active ? '' : ' · ' . __('kapalı') }}
                                        </option>
                                    @endforeach
                                </select>
                            </label>
                        @endforeach
                    </div>
                    @if ($written->isEmpty())
                        <p class="mt-3 text-xs text-amber-700">{{ __('Önce aşağıdan bir alan yaz; sonra burada seçebilirsin.') }}</p>
                    @endif
                </div>
                <div class="flex justify-end">
                    <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-brand-900 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-800">
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.5l4.5 4.5L19 7.5" /></svg>
                        {{ __('Girişi kaydet') }}
                    </button>
                </div>
            </form>
        </x-admin.card>

        {{-- ALANLAR --}}
        <x-admin.card class="overflow-hidden" data-page-sections-root>
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-6 py-4">
                <div>
                    <p class="font-semibold text-slate-900">{{ __('Alanlar') }} <span class="font-normal text-slate-500">— {{ __('sayfadaki sırayla') }}</span></p>
                    <p class="mt-0.5 text-xs text-slate-500">
                        {{ __('Boş ya da kapalı alan sitede görünmez.') }}
                        @if (auth()->user()->isAdmin())
                            <a href="{{ \App\Http\Controllers\CityGuideTopicController::back() }}" class="font-semibold text-brand-900 hover:underline">{{ __('Sırayı değiştir: Sayfalar › Şehirler › Bölümler') }} →</a>
                        @endif
                    </p>
                </div>
                <a href="{{ route('city-guide-topics.create') }}" class="inline-flex items-center gap-1.5 rounded-xl px-3.5 py-2 text-sm font-semibold text-brand-900 ring-1 ring-slate-200 transition hover:bg-slate-50">
                    <x-admin.icon name="plus" class="size-4" /> {{ __('Yeni alan ekle') }}
                </a>
            </div>
            <ul class="divide-y divide-slate-100" data-page-sections data-url="{{ route('city-guides.order', $city) }}">
                @foreach ($sections as $section)
                    @php
                        $topic = $section->topic;
                        $written = $section->isWritten();
                        $title = $section->getTranslation('title', 'en', false);
                    @endphp
                    <li data-section="{{ $section->id }}" @class(['flex items-center gap-4 bg-white px-4 py-4 transition sm:px-6', 'opacity-60' => !$section->active])>
                        <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-brand-50 text-brand-900">{!! \App\Support\FeatureIcons::svg($topic->icon, 'size-5') !!}</span>
                        <a href="{{ route('city-guides.sections.edit', [$city, $section]) }}" class="group min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="font-semibold text-slate-900 group-hover:text-brand-900">{{ $topic->getTranslation('name', 'tr', false) ?: $topic->name }}</p>
                                <x-admin.todo-pill type="city-guide-section" :id="$section->id" />
                                @if (!$written)
                                    <span class="rounded-md bg-slate-100 px-1.5 py-0.5 text-[11px] font-semibold text-slate-500">{{ __('Henüz yazılmadı') }}</span>
                                @endif
                                @if ($section->image_path)
                                    <span class="rounded-md bg-sky-50 px-1.5 py-0.5 text-[11px] font-semibold text-sky-700 ring-1 ring-sky-200">{{ __('Kapak resmi var') }}</span>
                                @endif
                                @if (!\App\Support\PageSections::guideOn($topic->id))
                                    <span class="rounded-md bg-amber-50 px-1.5 py-0.5 text-[11px] font-semibold text-amber-800 ring-1 ring-amber-200" title="{{ __('Sayfalar › Şehirler › Bölümler') }}">{{ __('Şehirler sayfasında kapalı') }}</span>
                                @endif
                            </div>
                            <p class="mt-0.5 truncate text-sm text-slate-500">{{ $title ?: $topic->example }}</p>
                        </a>
                        <a href="{{ route('city-guides.sections.edit', [$city, $section]) }}" class="hidden shrink-0 rounded-lg px-3 py-1.5 text-sm font-semibold text-brand-900 transition hover:bg-brand-50 sm:block">
                            {{ $written ? __('Düzenle') : __('Yaz') }} →
                        </a>
                        {!! $switch($section->active, $topic->name) !!}
                    </li>
                @endforeach
            </ul>
        </x-admin.card>
    </div>
@endsection
