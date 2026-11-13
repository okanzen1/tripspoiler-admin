@extends('layouts.admin')

@php
    $editing = $blog->exists;
    $steps = ['Temel bilgiler', 'İçerik', 'Kapak görseli', 'Etiketler ve aktiviteler', 'SSS', 'SEO', 'Yayın'];
    $stepFields = [
        ['title', 'slug', 'city_id', 'excerpt'],
        ['content'],
        ['cover', 'remove_cover'],
        ['tags', 'activity_ids', 'activity_ids.*'],
        ['faqs', 'faqs.*'],
        ['meta_title', 'meta_description'],
        ['active', 'published_at'],
    ];
    if (!$editing) {
        $steps[] = 'Onay';
    }
    $start = collect($stepFields)->search(fn ($fields) => $errors->hasAny($fields));
    $field = 'block w-full rounded-xl border bg-white px-3.5 py-2.5 text-sm outline-none transition placeholder:text-slate-400 focus:border-brand-900 focus:ring-4 focus:ring-brand-900/10';
    $selectedActivities = array_map('intval', (array) old('activity_ids', $blog->activities->pluck('id')->all()));
    $faqRows = old('faqs', $editing ? $blog->faqs->map(fn ($f) => ['id' => $f->id, 'question' => $f->question, 'answer' => $f->answer])->all() : []);
@endphp

@section('title', $editing ? $blog->title : 'Yeni yazı')

@section('header')
    <x-admin.page-header :title="$editing ? $blog->title : 'Yeni yazı'" :breadcrumb="['Blog' => route('blogs.index')]"
        :description="$editing ? __(':count dk okuma', ['count' => $blog->readingMinutes()]) . ($blog->published_at ? ' · ' . $blog->published_at->locale(app()->getLocale())->translatedFormat('j F Y') : '') : 'Adım adım yeni bir blog yazısı ekle.'">
        <x-slot:icon><path d="M12 20h9" /><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z" /></x-slot:icon>
        @if ($editing)
            <span @class([
                'inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-sm font-semibold',
                'bg-emerald-50 text-emerald-700' => $blog->active,
                'bg-slate-100 text-slate-600' => !$blog->active,
            ])><span @class(['size-2 rounded-full', 'bg-emerald-500' => $blog->active, 'bg-slate-400' => !$blog->active])></span>{{ __($blog->active ? 'Yayında' : 'Taslak') }}</span>
        @endif
    </x-admin.page-header>
@endsection

@section('content')
    @if ($editing)
        <x-admin.translation-bar type="blog" :model="$blog" />
    @endif

    <x-admin.wizard :action="$editing ? route('blogs.update', $blog) : route('blogs.store')" :method="$editing ? 'PUT' : null"
        :steps="$steps" :start="$start === false ? 0 : $start" :free="$editing"
        :submit-label="$editing ? 'Kaydet' : 'Yazıyı oluştur'" :cancel-url="route('blogs.index')">

        {{-- 1. Temel bilgiler --}}
        <section data-step class="space-y-5">
            <x-admin.step-heading :title="__('Temel bilgiler')" :description="__('Başlık ve özet sitede İngilizce görünür.')" />
            <x-admin.input name="title" :label="__('Başlık')" :value="$blog->title" required maxlength="255" placeholder="{{ __('Hagia Sophia: History, Visiting Tips and How to Skip the Lines') }}" autocomplete="off" />
            <x-admin.input name="slug" :label="__('URL adı')" :value="$blog->slug" data-slug-from="title" prefix="/blog/" autocomplete="off"
                :hint="$editing ? 'Değiştirirsen eski link çalışmaz; yayındaki yazıda dikkatli ol.' : 'Boş bırakırsan başlıktan otomatik üretilir.'" />
            <div>
                <label for="city_id" class="block text-sm font-medium text-slate-700">{{ __('Şehir') }} <span class="font-normal text-slate-400">{{ __('(isteğe bağlı)') }}</span></label>
                <select id="city_id" name="city_id" @class([$field, 'mt-1.5 border-slate-200'])>
                    <option value="">{{ __('Şehre bağlı değil (genel yazı)') }}</option>
                    @foreach ($cities as $city)
                        <option value="{{ $city->id }}" @selected((string) old('city_id', $blog->city_id) === (string) $city->id)>{{ $city->country->flag }} {{ $city->name }}</option>
                    @endforeach
                </select>
                <p class="mt-1.5 text-xs text-slate-500">{{ __('Seçersen yazı o şehrin sayfasında da listelenir.') }}</p>
            </div>
            <div>
                <div class="flex items-center justify-between"><label for="excerpt" class="text-sm font-medium text-slate-700">{{ __('Özet') }}</label><span class="text-xs text-slate-400" data-counter-for="excerpt" data-limit="200"></span></div>
                <textarea id="excerpt" name="excerpt" rows="3" maxlength="500" placeholder="{{ __('Blog listesinde başlığın altında görünen 1-2 cümle.') }}"
                    @class([$field, 'mt-1.5 resize-y', 'border-slate-200' => !$errors->has('excerpt'), 'border-red-300' => $errors->has('excerpt')])>{{ old('excerpt', $blog->excerpt) }}</textarea>
                @error('excerpt') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
            </div>
        </section>

        {{-- 2. İçerik --}}
        <section data-step hidden class="space-y-5">
            <x-admin.step-heading :title="__('İçerik')" :description="__('Yazının metni (İngilizce). Başlıklar, kalın yazı, liste, link ve görsel kullanabilirsin.')" />
            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white" data-rich-editor="content" data-upload-url="{{ route('editor-images.store') }}" data-media-url="{{ \App\Services\ImageUploader::baseUrl() }}" data-placeholder="{{ __('Yazını buraya yaz…') }}">
                <div></div>
            </div>
            <input type="hidden" name="content" value="{{ old('content', $blog->content) }}" />
            @error('content') <p class="text-xs font-medium text-red-600">{{ $message }}</p> @enderror
        </section>

        {{-- 3. Kapak görseli --}}
        <section data-step hidden class="space-y-5">
            <x-admin.step-heading :title="__('Kapak görseli')" :description="__('Blog listesinde ve yazının en üstünde görünür. Yatay (ör. 1600x900) bir görsel en iyisi.')" />
            <x-admin.image-upload name="cover" shape="wide" :label="__('Kapak')" :current="$blog->coverUrl()" :focus="$blog->cover_focus ?? 50" placeholder="{{ __('Görsel yok') }}" :hint="__('JPG, PNG ya da WebP · en fazla 5 MB · 1600 px\'e küçültülür')" />
        </section>

        {{-- 4. Etiketler ve aktiviteler --}}
        <section data-step hidden class="space-y-5">
            <x-admin.step-heading :title="__('Etiketler ve aktiviteler')" :description="__('Etiketler yazıyı gruplar; seçtiğin aktiviteler yazının altında \'ilgili turlar\' olarak çıkar (tıklama getiren kısım).')" />
            <x-admin.input name="tags" :label="__('Etiketler')" :value="implode(', ', (array) $blog->tags)" placeholder="{{ __('Istanbul, History, Museums') }}" autocomplete="off"
                :hint="__('Virgülle ayır.')" />

            <div class="overflow-hidden rounded-2xl bg-white ring-1 ring-slate-200" data-activity-picker>
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 bg-gradient-to-r from-brand-50/70 to-white px-5 py-4">
                    <span class="flex items-center gap-3">
                        <span class="grid size-10 place-items-center rounded-xl bg-white text-brand-900 shadow-sm ring-1 ring-brand-100">
                            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16v4a2 2 0 0 0 0 4v4H4v-4a2 2 0 0 0 0-4Z" /><path d="M14 7v12" stroke-dasharray="2 2" /></svg>
                        </span>
                        <span>
                            <span class="block text-sm font-semibold text-slate-900">{{ __('İlgili aktiviteler') }}</span>
                            <span class="block text-xs text-slate-500"><span data-picked-count class="font-bold text-brand-900">{{ count($selectedActivities) }}</span> {{ __('seçili') }} · {{ __('yazının altında "ilgili turlar" olarak çıkar') }}</span>
                        </span>
                    </span>
                    <div class="relative w-full sm:w-80">
                        <x-admin.icon name="search" class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                        <input type="search" data-picker-search placeholder="{{ __('Ad, ID, partner ya da ürün ID ile ara…') }}" autocomplete="off"
                            class="block w-full rounded-xl border border-slate-200 bg-white py-2 pl-9 pr-3 text-sm outline-none focus:border-brand-900 focus:ring-2 focus:ring-brand-900/15" />
                    </div>
                </div>
                <ul class="max-h-[28rem] divide-y divide-slate-100 overflow-y-auto">
                    @forelse ($activities as $activity)
                        <li data-search="{{ mb_strtolower($activity->name . ' ' . $activity->city->name . ' #' . $activity->id . ' ' . $activity->id . ' ' . ($activity->partner?->name ?? '') . ' ' . ($activity->partner_product_id ?? '')) }}">
                            <label class="flex cursor-pointer items-center gap-4 px-5 py-3 transition hover:bg-slate-50 has-[:checked]:bg-brand-50/70">
                                <input type="checkbox" name="activity_ids[]" value="{{ $activity->id }}" data-label="{{ $activity->name }}" class="size-[18px] shrink-0 rounded accent-brand-900"
                                    @checked(in_array($activity->id, $selectedActivities, true)) />
                                @if ($thumb = $activity->cover?->url('sm'))
                                    <img src="{{ $thumb }}" alt="" loading="lazy" class="h-11 w-16 shrink-0 rounded-lg object-cover ring-1 ring-slate-200">
                                @else
                                    <span class="h-11 w-16 shrink-0 rounded-lg bg-slate-100 ring-1 ring-slate-200"></span>
                                @endif
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-sm font-semibold text-slate-900">{{ $activity->name }}</span>
                                    <span class="mt-1 flex flex-wrap items-center gap-1.5 text-[11px]">
                                        <span class="rounded-md bg-slate-100 px-1.5 py-0.5 font-bold tabular-nums text-slate-600" title="{{ __('Bizdeki ID') }}">#{{ $activity->id }}</span>
                                        @if ($activity->partner)
                                            <span class="inline-flex items-center gap-1 rounded-md bg-white py-0.5 pl-0.5 pr-1.5 font-semibold text-slate-700 ring-1 ring-slate-200">
                                                <x-admin.partner-logo :partner="$activity->partner" class="size-4" /> {{ $activity->partner->name }}
                                            </span>
                                        @else
                                            <span class="rounded-md bg-amber-50 px-1.5 py-0.5 font-semibold text-amber-700">{{ __('Partner yok') }}</span>
                                        @endif
                                        @if ($activity->partner_product_id)
                                            <span class="rounded-md bg-violet-50 px-1.5 py-0.5 font-semibold tabular-nums text-violet-700" title="{{ __('Partnerdeki ürün ID') }}">{{ __('Ürün ID') }}: {{ $activity->partner_product_id }}</span>
                                        @endif
                                        @unless ($activity->active)
                                            <span class="rounded-md bg-slate-100 px-1.5 py-0.5 font-semibold text-slate-500">{{ __('Pasif') }}</span>
                                        @endunless
                                    </span>
                                </span>
                                <span class="hidden shrink-0 items-center gap-1 text-xs text-slate-500 sm:inline-flex">{{ $activity->city->country?->flag }} {{ $activity->city->name }}</span>
                            </label>
                        </li>
                    @empty
                        <li class="px-4 py-6 text-sm text-slate-500">{{ __('Henüz aktivite yok.') }}</li>
                    @endforelse
                </ul>
            </div>
        </section>

        {{-- 5. SSS --}}
        @include('admin.partials.faq-step', [
            'rows' => $faqRows,
            'description' => 'Yazının sonunda görünecek soru-cevaplar (İngilizce). Google bu soruları arama sonuçlarında da gösterebilir. İsteğe bağlı.',
        ])

        {{-- 6. SEO --}}
        <section data-step hidden class="space-y-5">
            <x-admin.step-heading :title="__('SEO')" :description="__('Google\'da nasıl görüneceği. Boş bırakırsan başlık ve özet kullanılır.')" />
            @include('admin.partials.seo-fields', [
                'metaTitle' => $blog->meta_title,
                'metaDescription' => $blog->meta_description,
                'base' => 'blog',
                'fallbackTitle' => 'Yazı başlığı',
                'titlePlaceholder' => 'Hagia Sophia Guide: History & Tips | TripSpoiler',
            ])
        </section>

        {{-- 7. Yayın --}}
        <section data-step hidden class="space-y-5">
            <x-admin.step-heading :title="__('Yayın')" :description="__('Yazı sitede görünsün mü?')" />
            <x-admin.status-picker :active="$blog->active" />
            <div class="max-w-xs">
                <x-admin.input name="published_at" :label="__('Yayın tarihi')" type="date" :value="$blog->published_at?->format('Y-m-d')"
                    :hint="__('Yazıda ve listede görünen tarih. Boşsa yayına alındığı gün.')" />
            </div>
        </section>

        {{-- 8. Onay (sadece eklerken) --}}
        @unless ($editing)
            <section data-step hidden class="space-y-5">
                <x-admin.step-heading :title="__('Onay')" :description="__('Bilgileri kontrol et, doğruysa yazıyı oluştur.')" />
                <dl class="divide-y divide-slate-100 overflow-hidden rounded-2xl bg-white ring-1 ring-slate-200">
                    @foreach (['Başlık' => 'title', 'URL adı' => 'slug', 'Şehir' => 'city_id', 'Etiketler' => 'tags', 'Kapak' => 'cover', 'Durum' => 'active', 'Yayın tarihi' => 'published_at'] as $label => $name)
                        <div class="grid grid-cols-3 gap-4 px-5 py-3.5 text-sm">
                            <dt class="text-slate-500">{{ __($label) }}</dt>
                            <dd class="col-span-2 break-all font-medium text-slate-900" data-summary-for="{{ $name }}">—</dd>
                        </div>
                    @endforeach
                    <div class="grid grid-cols-3 gap-4 px-5 py-3.5 text-sm">
                        <dt class="text-slate-500">{{ __('İlgili aktiviteler') }}</dt>
                        <dd class="col-span-2 font-medium text-slate-900"><span data-picked-count>{{ count($selectedActivities) }}</span> {{ __('seçili') }}</dd>
                    </div>
                </dl>
            </section>
        @endunless
    </x-admin.wizard>
@endsection
