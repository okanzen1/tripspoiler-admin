@extends('layouts.admin')

@php
    $editing = $activity->exists;
    $steps = ['Temel bilgiler', 'Partner', 'İçerik', 'SSS', 'Yorumlar', 'Görseller', 'SEO', 'Yayın'];
    $stepFields = [
        ['name', 'slug', 'type', 'city_id'],
        ['partner_id', 'affiliate_url', 'partner_product_id'],
        ['duration', 'audio_guide', 'description'],
        ['faqs', 'faqs.*'],
        ['reviews', 'reviews.*'],
        ['images', 'images.*', 'remove_images', 'cover_image_id'],
        ['meta_title', 'meta_description'],
        ['active', 'is_popular'],
    ];
    if (!$editing) {
        $steps[] = 'Onay';
    }
    $start = collect($stepFields)->search(fn ($fields) => $errors->hasAny($fields));
    $field = 'block w-full rounded-xl border bg-white px-3.5 py-2.5 text-sm outline-none transition placeholder:text-slate-400 focus:border-brand-900 focus:ring-4 focus:ring-brand-900/10';
    $selectedType = old('type', $activity->type?->value ?? 'product');
    $selectedPartner = (string) old('partner_id', $activity->partner_id);
    // SSS satırları: hata dönüşünde formdaki hali, yoksa kayıtlı olanlar
    $faqRows = old('faqs', $editing ? $activity->faqs->map(fn ($f) => ['id' => $f->id, 'question' => $f->question, 'answer' => $f->answer])->all() : []);
    $reviewRows = old('reviews', $editing ? $activity->reviews->map(fn ($r) => [
        'id' => $r->id, 'name' => $r->name, 'rating' => $r->rating, 'comment' => $r->comment, 'approved' => $r->approved,
        'date' => $r->created_at->locale(app()->getLocale())->translatedFormat('j M Y'),
    ])->all() : []);
    $pendingReviews = collect($reviewRows)->where('approved', false)->count();
@endphp

@section('title', $editing ? $activity->name : 'Yeni aktivite')

@section('header')
    <x-admin.page-header :title="$editing ? $activity->name : 'Yeni aktivite'" :breadcrumb="['Aktiviteler' => route('activities.index')]"
        :description="$editing ? $activity->city->country->flag . ' ' . $activity->city->name . ' · ' . $activity->type->label() : 'Adım adım yeni bir aktivite ekle.'">
        <x-slot:icon><path d="M4 7h16v4a2 2 0 0 0 0 4v4H4v-4a2 2 0 0 0 0-4Z" /><path d="M14 7v12" stroke-dasharray="2 2" /></x-slot:icon>
        @if ($editing)
            <span @class([
                'inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-sm font-semibold',
                'bg-emerald-50 text-emerald-700' => $activity->active,
                'bg-slate-100 text-slate-600' => !$activity->active,
            ])><span @class(['size-2 rounded-full', 'bg-emerald-500' => $activity->active, 'bg-slate-400' => !$activity->active])></span>{{ __($activity->active ? 'Yayında' : 'Yayında değil') }}</span>
        @endif
    </x-admin.page-header>
@endsection

@section('content')
    @if ($editing)
        <x-admin.translation-bar type="activity" :model="$activity" />
    @endif

    <x-admin.wizard :action="$editing ? route('activities.update', $activity) : route('activities.store')" :method="$editing ? 'PUT' : null"
        :steps="$steps" :start="$start === false ? 0 : $start" :free="$editing"
        :submit-label="$editing ? 'Kaydet' : 'Aktiviteyi oluştur'" :cancel-url="route('activities.index')">

        {{-- 1. Temel bilgiler --}}
        <section data-step class="space-y-5">
            <x-admin.step-heading :title="__('Temel bilgiler')" :description="__('Sitede görünecek ad (İngilizce), şehir ve tür.')" />
            <x-admin.input name="name" :label="__('Aktivite adı')" :value="$activity->name" required maxlength="255" placeholder="{{ __('Hagia Sophia Skip-the-Line Ticket with Audio Guide') }}" autocomplete="off" />
            <x-admin.input name="slug" :label="__('URL adı')" :value="$activity->slug" data-slug-from="name" prefix="/activities/" autocomplete="off"
                :hint="$editing ? 'Değiştirirsen eski link çalışmaz; yayındaki aktivitede dikkatli ol.' : 'Boş bırakırsan addan otomatik üretilir.'" />

            <div>
                <label for="city_id" class="block text-sm font-medium text-slate-700">{{ __('Şehir') }}</label>
                <select id="city_id" name="city_id" required @class([$field, 'mt-1.5', 'border-slate-200' => !$errors->has('city_id'), 'border-red-300' => $errors->has('city_id')])>
                    <option value="">{{ __('Şehir seç…') }}</option>
                    @foreach ($cities as $country => $countryCities)
                        <optgroup label="{{ $country }}">
                            @foreach ($countryCities as $city)
                                <option value="{{ $city->id }}" @selected((string) old('city_id', $activity->city_id) === (string) $city->id)>{{ $city->name }}{{ $city->active ? '' : ' (' . __('pasif') . ')' }}</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
                @error('city_id')
                    <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>
                @else
                    <p class="mt-1.5 text-xs text-slate-500">{!! __('Sadece aktif ülkelerin şehirleri listelenir. Şehir yoksa :link sayfasından aç.', ['link' => '<a href="' . route('locations.index') . '" class="font-medium text-brand-900 underline">' . e(__('Ülkeler ve şehirler')) . '</a>']) !!}</p>
                @enderror
            </div>

            <div>
                <span class="block text-sm font-medium text-slate-700">{{ __('Tür') }}</span>
                <div class="mt-1.5 grid gap-3 md:grid-cols-3">
                    @foreach (\App\Enums\ActivityType::cases() as $type)
                        <label class="flex cursor-pointer gap-3 rounded-2xl border-2 border-slate-200 p-4 transition hover:border-slate-300 has-checked:border-brand-900 has-checked:bg-brand-50/60">
                            <input type="radio" name="type" value="{{ $type->value }}" data-label="{{ $type->label() }}" required class="mt-0.5 size-4 accent-brand-900" @checked($selectedType === $type->value) />
                            <span>
                                <span class="block text-sm font-semibold text-slate-900">{{ $type->label() }}</span>
                                <span class="mt-0.5 block text-xs text-slate-500">{{ $type->description() }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- 2. Partner --}}
        <section data-step hidden class="space-y-5">
            <x-admin.step-heading :title="__('Partner')" :description="__('Ziyaretçi rezervasyon için hangi partnerin sitesine gidecek?')" />
            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($partners as $partner)
                    <label class="flex cursor-pointer items-center gap-3 rounded-2xl border-2 border-slate-200 p-4 transition hover:border-slate-300 has-checked:border-brand-900 has-checked:bg-brand-50/60">
                        <input type="radio" name="partner_id" value="{{ $partner->id }}" data-label="{{ $partner->name }}" class="size-4 accent-brand-900" @checked($selectedPartner === (string) $partner->id) />
                        <x-admin.partner-logo :partner="$partner" class="size-10" />
                        <span class="min-w-0">
                            <span class="block truncate text-sm font-semibold text-slate-900">{{ $partner->name }}</span>
                            <span class="block truncate text-xs text-slate-500">{{ $partner->websiteHost() ?? $partner->slug }}</span>
                        </span>
                    </label>
                @endforeach
                <label class="flex cursor-pointer items-center gap-3 rounded-2xl border-2 border-dashed border-slate-200 p-4 transition hover:border-slate-300 has-checked:border-brand-900 has-checked:bg-brand-50/60">
                    <input type="radio" name="partner_id" value="" data-label="{{ __('Henüz yok') }}" class="size-4 accent-brand-900" @checked($selectedPartner === '') />
                    <span>
                        <span class="block text-sm font-semibold text-slate-900">{{ __('Henüz yok') }}</span>
                        <span class="block text-xs text-slate-500">{{ __('Sonra bağlarsın') }}</span>
                    </span>
                </label>
            </div>
            <x-admin.input name="affiliate_url" :label="__('Partner linki')" type="url" :value="$activity->affiliate_url" placeholder="{{ __('https://www.partner.com/istanbul/hagia-sophia-tour') }}"
                :hint="__('Partnerin sitesindeki ürün sayfası. Ziyaretçi \'Rezervasyon yap\'a basınca buraya gider ve tıklama sayılır.')" />
            <x-admin.input name="partner_product_id" :label="__('Partner ürün kodu (isteğe bağlı)')" :value="$activity->partner_product_id" placeholder="123456"
                :hint="__('Partner panelindeki ürün numarası; raporları eşleştirmek için.')" />
        </section>

        {{-- 3. İçerik --}}
        <section data-step hidden class="space-y-5">
            <x-admin.step-heading :title="__('İçerik')" :description="__('Aktivite sayfasında görünecek bilgiler (İngilizce).')" />
            <div class="grid gap-5 md:grid-cols-2">
                <x-admin.input name="duration" :label="__('Süre')" :value="$activity->duration" maxlength="50" placeholder="{{ __('3 hours') }}" />
                <label class="mt-6 flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 bg-white px-4 py-2.5 has-checked:border-brand-900 has-checked:bg-brand-50/60">
                    <input type="checkbox" name="audio_guide" value="1" class="size-4 accent-brand-900" @checked(old('audio_guide', $activity->audio_guide)) />
                    <span class="text-sm font-medium text-slate-800">{{ __('Sesli rehber var') }}</span>
                </label>
            </div>
            <div>
                <span class="block text-sm font-medium text-slate-700">{{ __('Açıklama') }}</span>
                <div class="mt-1.5 overflow-hidden rounded-xl border border-slate-200 bg-white" data-rich-editor="description" data-upload-url="{{ route('editor-images.store') }}" data-media-url="{{ \App\Services\ImageUploader::baseUrl() }}" data-placeholder="{{ __('Aktiviteyi anlat: ne yapılır, neler dahil, buluşma noktası…') }}">
                    <div class="min-h-80 text-[15px]"></div>
                </div>
                <input type="hidden" name="description" value="{{ old('description', $activity->description) }}" />
                @error('description') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
            </div>
        </section>

        {{-- 4. SSS --}}
        @include('admin.partials.faq-step', [
            'rows' => $faqRows,
            'max' => \App\Http\Requests\ActivityRequest::MAX_FAQS,
            'description' => 'Bu aktivitenin sayfasında görünecek soru-cevaplar (İngilizce). Sıra burada nasılsa sitede de öyle. İsteğe bağlı.',
        ])

        {{-- 5. Yorumlar --}}
        <section data-step hidden class="space-y-5">
            <x-admin.step-heading :title="__('Yorumlar')" :description="__('Ziyaretçilerin bu aktivite için yazdığı yorumlar. Onaylı olanlar sitede görünür; elle de yorum ekleyebilirsin.')" />
            @if ($pendingReviews)
                <p class="rounded-xl bg-amber-50 px-4 py-2.5 text-sm text-amber-900 ring-1 ring-amber-200">{{ $pendingReviews }} yorum onay bekliyor: kontrol edip "Onaylı" kutusunu işaretle ve kaydet.</p>
            @endif

            <div data-repeater="reviews" data-max="200" data-remove-confirm="{{ __('Bu yorum silinsin mi? Kaydettiğinde kalıcı olarak silinir.') }}" class="space-y-3">
                <div data-repeater-list class="space-y-3">
                    @foreach ($reviewRows as $row)
                        @include('admin.partials.review-row', ['review' => $row])
                    @endforeach
                </div>
                <p data-repeater-empty class="hidden rounded-2xl border-2 border-dashed border-slate-200 px-6 py-8 text-center text-sm text-slate-500">{{ __('Bu aktivite için henüz yorum yok.') }}</p>
                <template data-repeater-template>
                    @include('admin.partials.review-row', ['review' => []])
                </template>
                <button type="button" data-repeater-add
                    class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-brand-900 ring-1 ring-brand-200 transition hover:bg-brand-50 disabled:opacity-40">
                    <x-admin.icon name="plus" /> {{ __('Yorum ekle') }}
                </button>
            </div>
            @foreach ($errors->get('reviews*') as $messages)
                <p class="text-xs font-medium text-red-600">{{ $messages[0] }}</p>
            @endforeach
        </section>

        {{-- 6. Görseller --}}
        <section data-step hidden class="space-y-5">
            <x-admin.step-heading :title="__('Görseller')" :description="__('İlk görsel kapak olur. En fazla :count görsel; JPG, PNG ya da WebP, her biri en fazla 5 MB.', ['count' => \App\Http\Requests\ActivityRequest::MAX_IMAGES])" />

            @if ($editing && $activity->images->isNotEmpty())
                <div class="grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-5">
                    @foreach ($activity->images as $image)
                        <div class="group overflow-hidden rounded-2xl bg-white ring-1 ring-slate-200 has-[[data-remove]:checked]:opacity-40">
                            <img src="{{ $image->url('sm') }}" alt="" loading="lazy" class="aspect-[4/3] w-full object-cover" />
                            <div class="flex items-center justify-between gap-2 px-3 py-2 text-xs">
                                <label class="flex cursor-pointer items-center gap-1.5 font-medium text-slate-700">
                                    <input type="radio" name="cover_image_id" value="{{ $image->id }}" class="size-3.5 accent-brand-900" @checked($loop->first) /> {{ __('Kapak') }}
                                </label>
                                <label class="flex cursor-pointer items-center gap-1.5 font-medium text-red-600">
                                    <input type="checkbox" name="remove_images[]" value="{{ $image->id }}" data-remove class="size-3.5 accent-red-600" /> {{ __('Sil') }}
                                </label>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            <label class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-2xl border-2 border-dashed border-slate-300 bg-white px-6 py-10 text-center transition hover:border-brand-900 hover:bg-brand-50/40" data-drop-zone>
                <span class="grid size-12 place-items-center rounded-2xl bg-brand-50 text-brand-900">
                    <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 16V4M7 9l5-5 5 5M4 20h16" /></svg>
                </span>
                <span class="text-sm font-semibold text-slate-900">{{ __('Görsel seç ya da buraya sürükle') }}</span>
                <span class="text-xs text-slate-500">{{ __('Birden fazla seçebilirsin') }}</span>
                <input type="file" name="images[]" multiple accept="image/jpeg,image/png,image/webp" class="sr-only" data-image-input />
            </label>
            <div class="grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-5" data-image-previews></div>
            @foreach (['images', 'images.*'] as $key)
                @error($key) <p class="text-xs font-medium text-red-600">{{ $message }}</p> @enderror
            @endforeach
        </section>

        {{-- 7. SEO --}}
        <section data-step hidden class="space-y-5">
            <x-admin.step-heading :title="__('SEO')" :description="__('Google\'da nasıl görüneceği. Boş bırakırsan aktivite adı ve açıklamanın başı kullanılır.')" />
            @include('admin.partials.seo-fields', [
                'metaTitle' => $activity->meta_title,
                'metaDescription' => $activity->meta_description,
                'base' => 'activities',
                'fallbackTitle' => 'Aktivite adı',
                'titlePlaceholder' => 'Hagia Sophia Tickets & Audio Guide | TripSpoiler',
                'descriptionPlaceholder' => 'Skip the line at Hagia Sophia with an audio guide…',
            ])
        </section>

        {{-- 8. Yayın --}}
        <section data-step hidden class="space-y-5">
            <x-admin.step-heading :title="__('Yayın')" :description="__('Aktivite sitede görünsün mü, öne çıkarılsın mı?')" />
            <x-admin.status-picker :active="$activity->active" />
            <label class="flex cursor-pointer gap-3.5 rounded-2xl border-2 border-slate-200 p-5 transition hover:border-slate-300 has-checked:border-amber-500 has-checked:bg-amber-50/60">
                <input type="checkbox" name="is_popular" value="1" data-label="{{ __('Evet') }}" class="mt-0.5 size-4 accent-amber-500" @checked(old('is_popular', $activity->is_popular)) />
                <span>
                    <span class="flex items-center gap-1.5 text-sm font-semibold text-slate-900"><x-admin.icon name="star" class="size-4 fill-amber-400 text-amber-500" /> {{ __('Popüler') }}</span>
                    <span class="mt-1 block text-xs text-slate-500">{{ __('Anasayfada ve şehir sayfasında "En popüler" bölümünde gösterilir.') }}</span>
                </span>
            </label>
        </section>

        {{-- 9. Onay (sadece eklerken) --}}
        @unless ($editing)
            <section data-step hidden class="space-y-5">
                <x-admin.step-heading :title="__('Onay')" :description="__('Bilgileri kontrol et, doğruysa aktiviteyi oluştur.')" />
                <dl class="divide-y divide-slate-100 overflow-hidden rounded-2xl bg-white ring-1 ring-slate-200">
                    @foreach (['Aktivite adı' => 'name', 'URL adı' => 'slug', 'Şehir' => 'city_id', 'Tür' => 'type', 'Partner' => 'partner_id', 'Partner linki' => 'affiliate_url', 'Süre' => 'duration', 'Görseller' => 'images[]', 'Durum' => 'active', 'Popüler' => 'is_popular'] as $label => $name)
                        <div class="grid grid-cols-3 gap-4 px-5 py-3.5 text-sm">
                            <dt class="text-slate-500">{{ __($label) }}</dt>
                            <dd class="col-span-2 break-all font-medium text-slate-900" data-summary-for="{{ $name }}">—</dd>
                        </div>
                    @endforeach
                </dl>
            </section>
        @endunless
    </x-admin.wizard>
@endsection
