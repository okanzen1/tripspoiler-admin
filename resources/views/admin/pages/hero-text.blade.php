@extends('layouts.admin')

@section('title', $page->name . ' · ' . __('Hero alanı'))

{{--
    Sayfa hero'su tek ekranda: 1 yazılar (canlı önizleme), 2 genel resim, 3 görünüm, 4 şehirler listesi
    (şehre özel resim + şehirler sayfasında kartlar / blogda kemerler). Tek "Kaydet".
--}}
@php
    $field = 'mt-1.5 block w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm outline-none transition placeholder:text-slate-400 focus:border-brand-900 focus:ring-4 focus:ring-brand-900/10';
    $select = 'block w-full rounded-lg border border-slate-200 bg-white px-2.5 py-2 text-xs outline-none transition focus:border-brand-900 focus:ring-4 focus:ring-brand-900/10';
    $url = fn ($path, $size = null) => \App\Services\ImageUploader::url($path, $size);
    $overlays = [
        'soft' => ['Az', 'Fotoğraf daha canlı.', 'from-slate-950/50 via-slate-950/20'],
        'medium' => ['Orta (önerilen)', 'Hem fotoğraf hem yazı net.', 'from-slate-950 via-slate-950/70'],
        'strong' => ['Çok', 'Parlak fotoğrafta yazıyı öne çıkarır.', 'from-slate-950 via-slate-950/90'],
    ];
    $hasImages = (bool) $spec;
    $generalDesktop = $url($general?->image, 'md') ?: $preview;
    // Adımlar: kısa yollar
    $steps = array_values(array_filter([
        ['yazilar', __('Yazılar')],
        $hasImages ? ['resim', __('Genel resim')] : null,
        $visual ? ['gorunum', __('Görünüm')] : null,
        ($rowCities->isNotEmpty() || $archCities->isNotEmpty()) ? ['sehirler', __('Şehirler')] : null,
        $reviewCards->isNotEmpty() ? ['kartlar', __('Yorum kartları')] : null,
    ]));
    $badge = fn ($n) => '<span class="grid size-8 shrink-0 place-items-center rounded-full bg-brand-900 text-sm font-bold text-white">' . $n . '</span>';
    $thumbBtn = 'inline-flex cursor-pointer items-center gap-1 rounded-lg px-2 py-1 text-xs font-semibold text-slate-700 ring-1 ring-slate-200 transition hover:bg-slate-50';
@endphp

@section('header')
    <x-admin.page-header :title="__('Hero alanı')" :breadcrumb="['Yönetim' => route('management.index'), 'Sayfalar' => route('pages.index'), $page->name => route('pages.edit', $page)]"
        :description="__(':page sayfasını açınca ilk görülen alan. Adımları sırayla değiştir; istemediğini boş bırak. Yazılarda :city yazarsan seçili şehrin adı olur.', ['page' => $page->name])">
        <x-slot:icon><rect x="3" y="4" width="18" height="16" rx="2" /><path d="m3 16 5-5 4 4 3-3 6 6" /><circle cx="15.5" cy="8.5" r="1.5" /></x-slot:icon>
        <a href="{{ $page->siteUrl() }}" target="_blank" rel="noopener"
            class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-700 ring-1 ring-slate-200 transition hover:bg-white">{{ __('Sayfayı aç') }} ↗</a>
    </x-admin.page-header>
@endsection

@section('footer-action')
    <button type="submit" form="hero-text-form" class="inline-flex items-center gap-2 rounded-xl bg-brand-900 px-5 py-2.5 font-semibold text-white shadow-sm transition hover:bg-brand-800">
        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12.5l4.5 4.5L19 7.5" /></svg>
        {{ __('Değişiklikleri kaydet') }}
    </button>
@endsection

@section('content')
    {{-- ADIMLAR --}}
    <nav class="mb-6 flex flex-wrap gap-2">
        @foreach ($steps as $i => [$anchor, $label])
            <a href="#{{ $anchor }}" class="inline-flex items-center gap-2 rounded-full bg-white py-1.5 pl-1.5 pr-4 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-slate-200 transition hover:ring-brand-900">
                <span class="grid size-6 place-items-center rounded-full bg-brand-900 text-xs font-bold text-white">{{ $i + 1 }}</span>{{ $label }}
            </a>
        @endforeach
    </nav>

    <x-admin.translation-bar type="section" :model="$model" />

    <form id="hero-text-form" action="{{ route('pages.hero-text.update', $page) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

        {{-- 1 YAZILAR + canlı önizleme --}}
        <x-admin.card class="scroll-mt-20 p-6 sm:p-8" id="yazilar">
            <div class="flex items-start gap-3">
                {!! $badge(1) !!}
                <x-admin.step-heading :title="__('Yazılar')" :description="__('İngilizce yaz ve kaydet; diğer dillere yukarıdaki Çeviriler kutusundan çevir. Boş bırakırsan varsayılan metin görünür.')" />
            </div>
            <div class="mt-6 grid gap-6 lg:grid-cols-5">
                <div class="space-y-5 lg:col-span-3">
                    @foreach ($fields as $name => [$label, $hint, $example, $max])
                        <label class="block">
                            <span class="text-sm font-medium text-slate-700">{{ __($label) }} <span class="font-normal text-slate-400">· {{ __($hint) }}</span></span>
                            @if ($max > 120)
                                <textarea name="{{ $name }}" rows="3" maxlength="{{ $max }}" placeholder="{{ $example }}" data-live="{{ $name }}" class="{{ $field }} resize-y leading-relaxed">{{ old($name, $model->getTranslation($name, 'en', false)) }}</textarea>
                            @else
                                <input type="text" name="{{ $name }}" maxlength="{{ $max }}" placeholder="{{ $example }}" data-live="{{ $name }}" value="{{ old($name, $model->getTranslation($name, 'en', false)) }}" class="{{ $field }}">
                            @endif
                            @error($name) <span class="mt-1 block text-xs font-medium text-red-600">{{ $message }}</span> @enderror
                        </label>
                    @endforeach
                </div>
                {{-- önizleme --}}
                <div class="lg:col-span-2">
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Önizleme') }}</p>
                    <div class="relative overflow-hidden rounded-2xl bg-[#0F1420] p-6 text-white">
                        @if ($hasImages && $generalDesktop)
                            <img src="{{ $generalDesktop }}" alt="" data-live-img class="absolute inset-0 size-full object-cover opacity-40" style="object-position: {{ $focus }}% center">
                            <div class="absolute inset-0 bg-gradient-to-r from-[#0F1420] via-[#0F1420]/80 to-transparent"></div>
                        @endif
                        <div class="relative">
                            <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-red-400" data-live-out="eyebrow" data-example="{{ $fields['eyebrow'][2] }}"></p>
                            <p class="mt-2 text-2xl font-extrabold leading-tight tracking-tight">
                                @if ($page->key === 'blog')
                                    Istanbul<span class="mt-1 block text-base font-medium text-white/60" data-live-out="title" data-example="{{ $fields['title'][2] }}"></span>
                                @elseif ($page->key === 'cities')
                                    Istanbul<span class="mt-1 block text-base font-bold text-white/90" data-live-out="title" data-example="{{ $fields['title'][2] }}"></span>
                                @else
                                    <span data-live-out="title" data-example="{{ $fields['title'][2] }}"></span><span class="block text-red-400">Istanbul</span>
                                @endif
                            </p>
                            <p class="mt-3 line-clamp-3 text-xs leading-relaxed text-white/65" data-live-out="description" data-example="{{ $fields['description'][2] }}"></p>
                            <div class="mt-4 flex gap-2"><span class="h-6 w-20 rounded-full bg-red-600"></span><span class="h-6 w-20 rounded-full ring-1 ring-white/30"></span></div>
                        </div>
                    </div>
                    <p class="mt-2 text-xs text-slate-500">{{ __('Önizlemede şehir olarak İstanbul gösterilir; :city yerine seçili şehir gelir.') }}</p>
                </div>
            </div>
        </x-admin.card>

        {{-- 2 GENEL RESİM --}}
        @if ($hasImages)
            <x-admin.card class="scroll-mt-20 p-6 sm:p-8" id="resim">
                <div class="flex items-start gap-3">
                    {!! $badge(2) !!}
                    <x-admin.step-heading :title="__('Genel resim')" :description="__('Kendi resmi olmayan her şehirde bu görünür; boşsa anasayfa hero resmi. Masaüstü önerilen :dw × :dh, telefon :mw × :mh piksel. Otomatik küçültülür ve WebP olur.', ['dw' => $spec['desktop'][0][0], 'dh' => $spec['desktop'][0][1], 'mw' => $spec['mobile'][0][0], 'mh' => $spec['mobile'][0][1]])" />
                </div>
                @include('admin.pages._banner-slot', [
                    'spec' => $spec,
                    'desktopName' => 'general', 'mobileName' => 'general_mobile',
                    'removeDesktop' => 'remove[general]', 'removeMobile' => 'remove[general_mobile]',
                    'desktop' => $general?->image, 'mobile' => $general?->image_mobile,
                    'desktopPreview' => $generalDesktop, 'mobilePreview' => $url($general?->image_mobile) ?: $generalDesktop,
                    'note' => $general?->image ? null : __('Şu an anasayfa resmi kullanılıyor'),
                    'focus' => $general?->focus ?? 50,
                ])
                @error('general') <p class="mt-2 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                @error('general_mobile') <p class="mt-2 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                <p class="mt-4 text-xs text-slate-500">{{ __($spec['layout']) }}</p>
            </x-admin.card>
        @endif

        {{-- 3 GÖRÜNÜM --}}
        @if ($visual)
            <x-admin.card class="scroll-mt-20 p-6 sm:p-8" id="gorunum">
                <div class="flex items-start gap-3">
                    {!! $badge(3) !!}
                    <x-admin.step-heading :title="__('Görünüm')" :description="__('Resmin nasıl duracağı; önizlemede hemen görürsün.')" />
                </div>
                <div class="mt-5 grid gap-6 lg:grid-cols-2">
                    <div class="relative aspect-[16/8] overflow-hidden rounded-2xl bg-slate-900">
                        @if ($generalDesktop)
                            <img src="{{ $generalDesktop }}" alt="" data-hero-img class="absolute inset-0 size-full object-cover" style="object-position: {{ $focus }}% center">
                        @endif
                        <div data-hero-overlay class="absolute inset-0 bg-gradient-to-r {{ $overlays[$overlay][2] }} to-transparent"></div>
                        <div class="absolute left-6 top-1/2 w-2/5 -translate-y-1/2 space-y-1.5">
                            <div class="h-2.5 rounded bg-white/90"></div>
                            <div class="h-2.5 w-3/4 rounded bg-red-400"></div>
                            <div class="h-2 w-2/3 rounded bg-white/50"></div>
                        </div>
                    </div>
                    <div class="space-y-6">
                        <div>
                            <p class="text-sm font-semibold text-slate-900">{{ __('Kadraj: resmin hangi tarafı görünsün?') }}</p>
                            <div class="mt-3 flex items-center gap-4">
                                <span class="text-xs font-medium text-slate-500">{{ __('Sol') }}</span>
                                <input type="range" name="focus" min="0" max="100" step="5" value="{{ old('focus', $focus) }}" class="w-full accent-brand-900" data-hero-focus>
                                <span class="text-xs font-medium text-slate-500">{{ __('Sağ') }}</span>
                            </div>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-slate-900">{{ __('Karartma: yazılar okunabilsin diye resim ne kadar koyulaşsın?') }}</p>
                            <div class="mt-3 grid gap-2 sm:grid-cols-3">
                                @foreach ($overlays as $value => [$label, $description, $classes])
                                    <label class="flex cursor-pointer flex-col rounded-2xl bg-white p-3 ring-1 ring-slate-200 transition hover:bg-slate-50 has-[:checked]:bg-brand-50 has-[:checked]:ring-2 has-[:checked]:ring-brand-900">
                                        <input type="radio" name="overlay" value="{{ $value }}" data-classes="{{ $classes }}" class="sr-only" @checked(old('overlay', $overlay) === $value)>
                                        <span class="text-sm font-semibold text-slate-900">{{ __($label) }}</span>
                                        <span class="mt-0.5 text-xs text-slate-500">{{ __($description) }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </x-admin.card>
        @endif

        {{-- YORUMLAR: banner'daki üç yorum kartı --}}
        @if ($reviewCards->isNotEmpty())
            @php
                $rcMode = $reviewCardsValue === '0' ? 'hide' : ($reviewCardsValue !== '' ? 'pick' : 'auto');
                $rcPicked = array_pad(array_filter(explode(',', $reviewCardsValue), fn ($v) => $v !== '0'), 3, '');
            @endphp
            <x-admin.card class="scroll-mt-20 p-6 sm:p-8" id="kartlar" x-data="{ mode: @js($rcMode) }">
                <div class="flex items-start gap-3">
                    {!! $badge(count($steps)) !!}
                    <x-admin.step-heading :title="__('Banner\'daki yorum kartları')" :description="__('Başlığın altında duran üç cam yorum kartı (telefonda kayan şerit).')" />
                </div>
                <div class="mt-5 grid gap-2 sm:grid-cols-3">
                    @foreach (['auto' => ['Otomatik', 'En yeni 4–5 yıldızlı üç yorum.'], 'pick' => ['Ben seçeyim', 'Hangi yorumların çıkacağını seç.'], 'hide' => ['Gösterme', 'Banner\'da yorum kartı olmasın.']] as $value => [$label, $desc])
                        <label class="flex cursor-pointer flex-col rounded-2xl bg-white p-4 ring-1 ring-slate-200 transition hover:bg-slate-50 has-[:checked]:bg-brand-50 has-[:checked]:ring-2 has-[:checked]:ring-brand-900">
                            <input type="radio" name="review_cards_mode" value="{{ $value }}" x-model="mode" class="sr-only" @checked($rcMode === $value)>
                            <span class="text-sm font-semibold text-slate-900">{{ __($label) }}</span>
                            <span class="mt-0.5 text-xs text-slate-500">{{ __($desc) }}</span>
                        </label>
                    @endforeach
                </div>
                <div x-show="mode === 'pick'" x-cloak class="mt-5 grid gap-3 sm:grid-cols-3">
                    @foreach ([0, 1, 2] as $i)
                        <label class="block">
                            <span class="text-sm font-medium text-slate-700">{{ __(':n. kart', ['n' => $i + 1]) }}</span>
                            <select name="review_cards[{{ $i }}]" class="{{ $field }}">
                                <option value="">{{ __('Boş') }}</option>
                                @foreach ($reviewCards as $rc)
                                    <option value="{{ $rc->id }}" @selected((string) $rcPicked[$i] === (string) $rc->id)>{{ str_repeat('★', $rc->rating) }} {{ $rc->name }} — {{ \Illuminate\Support\Str::limit($rc->comment, 40) }}</option>
                                @endforeach
                            </select>
                        </label>
                    @endforeach
                </div>
            </x-admin.card>
        @endif

        {{-- 4 ŞEHİRLER: liste --}}
        @php
            $rows = $page->key === 'blog' ? $archCities->map(fn ($r) => $r['city']) : $rowCities;
        @endphp
        @if ($rows->isNotEmpty())
            <x-admin.card class="scroll-mt-20 overflow-hidden" id="sehirler">
                <div class="flex items-start gap-3 p-6 sm:p-8">
                    {!! $badge(count($steps)) !!}
                    <x-admin.step-heading :title="__('Şehirler')"
                        :description="match ($page->key) {
                            'blog' => __('Her şehir için sağdaki üç kemerde hangi yazı dursun. Otomatik: Blog › Sıralama\'daki ilk üç yazı. Kapağı olmayan yazı seçilemez.'),
                            'cities' => __('Şehre özel resim (yoksa genel resim) ve fotoğrafın üstündeki iki kart. Kartlar: Otomatik = kapak resmi olan ilk konular.'),
                            default => __('Ziyaretçi o şehri seçince banner şehrin resmine döner; resmi yoksa genel resim görünür.'),
                        }" />
                </div>

                <ul class="divide-y divide-slate-100 border-t border-slate-100">
                    @foreach ($rows as $rowCity)
                        @php
                            $banner = $cityBanners->get($rowCity->id);
                            $desk = $url($banner?->image, 'sm');
                            $mob = $url($banner?->image_mobile);
                            $cityFocus = $banner?->focus ?? $focus; // şehre özel kadraj yoksa sayfa kadrajı
                            $cardValues = array_pad(explode(',', (string) $rowCity->guide?->hero_cards), 2, '');
                            $guideOptions = $page->key === 'cities' ? $rowCity->guideSections->filter->isWritten() : collect();
                            $arch = $page->key === 'blog' ? $archCities->first(fn ($r) => $r['city']->is($rowCity)) : null;
                            $hasError = $errors->has("city.{$rowCity->id}") || $errors->has("city_mobile.{$rowCity->id}");
                            $optionName = fn ($id) => ($o = $guideOptions->firstWhere('id', (int) $id)) ? ($o->topic->getTranslation('name', 'tr', false) ?: $o->topic->name) : null;
                            $cardsSummary = collect($cardValues)->map(fn ($v) => $v === '' ? __('Otomatik') : ($v === '0' ? __('Gösterme') : ($optionName($v) ?? __('Otomatik'))))->implode(' · ');
                        @endphp
                        {{-- satır: kayıttan sonra sunucudan yerinde yenilenir (data-live) --}}
                        <li data-live="hero-city-{{ $rowCity->id }}" data-city-row>
                            <div class="flex items-center gap-4 px-6 py-3.5 transition hover:bg-slate-50">
                                {{-- küçük önizleme --}}
                                @if ($hasImages)
                                    <span class="relative aspect-[16/9] w-24 shrink-0 overflow-hidden rounded-lg bg-slate-100 ring-1 ring-slate-200">
                                        <img src="{{ $desk ?: $generalDesktop }}" alt="" data-preview @class(['absolute inset-0 size-full object-cover', 'opacity-50 grayscale' => !$desk]) style="object-position: {{ $cityFocus }}% center">
                                    </span>
                                @endif

                                {{-- şehir --}}
                                <div class="min-w-0 flex-1">
                                    <p class="flex items-center gap-2 font-semibold text-slate-900">
                                        <span class="text-lg leading-none">{{ $rowCity->country?->flag }}</span>
                                        <span class="truncate">{{ $rowCity->name }}</span>
                                    </p>
                                    <p class="mt-0.5 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-slate-500">
                                        <span>{{ $rowCity->country?->name }} · {{ $arch ? __(':n yazı', ['n' => $arch['city']->blogs->count()]) : __(':n aktivite', ['n' => $rowCity->activities_count]) }}</span>
                                        @if ($hasImages)
                                            <span @class(['rounded-md px-1.5 py-0.5 font-medium', 'bg-emerald-50 text-emerald-700' => $desk, 'bg-slate-100 text-slate-500' => !$desk])>{{ $desk ? __('Özel resim') : __('Genel resim') }}</span>
                                            @if ($mob) <span class="rounded-md bg-sky-50 px-1.5 py-0.5 font-medium text-sky-700">{{ __('Telefon resmi') }}</span> @endif
                                        @endif
                                        @if ($page->key === 'cities')
                                            <span class="truncate">{{ $guideOptions->isEmpty() ? __('Rehber yazılmamış') : __('Kartlar') . ': ' . $cardsSummary }}</span>
                                        @endif
                                    </p>
                                    @if ($hasError)
                                        <p class="mt-1 text-xs font-medium text-red-600">{{ $errors->first("city.{$rowCity->id}") ?: $errors->first("city_mobile.{$rowCity->id}") }}</p>
                                    @endif
                                </div>

                                <button type="button" data-city-open class="inline-flex shrink-0 items-center gap-1.5 rounded-xl px-3.5 py-2 text-sm font-semibold text-brand-900 ring-1 ring-slate-200 transition hover:bg-white hover:ring-brand-200">
                                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z" /></svg>
                                    {{ __('Düzenle') }}
                                </button>
                            </div>

                            {{-- DÜZENLEME PANELİ (sağdan açılır; formun içinde: "Kaydet" bütün formu kaydeder) --}}
                            <div data-city-drawer @class(['fixed inset-0 z-[60]', 'hidden' => !$hasError]) role="dialog" aria-modal="true" aria-label="{{ $rowCity->name }}">
                                <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-[2px]" data-city-close></div>
                                <div class="absolute inset-y-0 right-0 flex w-full max-w-xl flex-col bg-white shadow-2xl">
                                    <div class="flex items-center gap-3 border-b border-slate-100 px-6 py-4">
                                        <span class="grid size-10 place-items-center rounded-xl bg-slate-100 text-xl leading-none">{{ $rowCity->country?->flag }}</span>
                                        <div class="min-w-0 flex-1">
                                            <p class="truncate text-lg font-semibold text-slate-900">{{ $rowCity->name }}</p>
                                            <p class="text-xs text-slate-500">{{ $page->name }} · {{ __('şehre özel banner') }}</p>
                                        </div>
                                        <button type="button" data-city-close class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700" aria-label="{{ __('Kapat') }}">
                                            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12" /></svg>
                                        </button>
                                    </div>

                                    <div class="flex-1 space-y-7 overflow-y-auto px-6 py-6">
                                        @if ($hasError)
                                            <p class="rounded-xl bg-red-50 px-4 py-3 text-sm font-medium text-red-700 ring-1 ring-red-200">{{ $errors->first("city.{$rowCity->id}") ?: $errors->first("city_mobile.{$rowCity->id}") }}</p>
                                        @endif

                                        @if ($hasImages)
                                            {{-- masaüstü --}}
                                            <section data-banner-box>
                                                <p class="mb-2 flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2.5" y="4.5" width="19" height="12" rx="2" /><path d="M8 20h8M12 16.5V20" /></svg>
                                                    {{ __('Masaüstü') }}
                                                </p>
                                                <div class="relative {{ $spec['ratio'] }} w-full overflow-hidden rounded-xl bg-slate-900">
                                                    <img src="{{ $desk ?: $generalDesktop }}" alt="" data-preview @class(['absolute inset-0 size-full object-cover', 'opacity-60' => !$desk]) style="object-position: {{ $cityFocus }}% center">
                                                    @if (!$desk)
                                                        <span data-note class="absolute bottom-2 right-2 rounded-md bg-slate-950/70 px-2 py-1 text-[11px] font-medium text-white/85">{{ __('Şu an genel resim kullanılıyor') }}</span>
                                                    @endif
                                                </div>
                                                <div class="mt-3 flex flex-wrap items-center gap-2">
                                                    <label class="inline-flex cursor-pointer items-center gap-2 rounded-xl bg-brand-900 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-800">
                                                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 16V4M7 9l5-5 5 5M4 20h16" /></svg>
                                                        {{ $desk ? __('Resmi değiştir') : __('Şehre özel resim seç') }}
                                                        <input type="file" name="city[{{ $rowCity->id }}]" accept="image/jpeg,image/png,image/webp" class="sr-only">
                                                    </label>
                                                    @if ($desk)
                                                        <label class="inline-flex cursor-pointer items-center gap-2 rounded-xl px-3 py-2 text-sm font-medium text-slate-500 hover:bg-red-50 hover:text-red-600">
                                                            <input type="checkbox" name="remove[city][{{ $rowCity->id }}]" value="1" class="rounded border-slate-300 text-red-600">{{ __('Kaldır, genel resmi kullan') }}
                                                        </label>
                                                    @endif
                                                    <span class="text-xs font-medium text-emerald-700" data-file-name></span>
                                                </div>
                                            </section>

                                            {{-- telefon --}}
                                            <section data-banner-box class="flex items-start gap-5">
                                                <div class="w-32 shrink-0 rounded-[1.4rem] border-4 border-slate-900 bg-slate-900 p-0.5">
                                                    <div class="relative {{ $spec['mobile_ratio'] }} overflow-hidden rounded-[1rem] bg-slate-800">
                                                        <img src="{{ $mob ?: ($desk ?: $generalDesktop) }}" alt="" data-preview @class(['absolute inset-0 size-full object-cover', 'opacity-60' => !$mob]) style="object-position: {{ $cityFocus }}% center">
                                                    </div>
                                                </div>
                                                <div class="min-w-0 flex-1">
                                                    <p class="flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="7" y="2.5" width="10" height="19" rx="2" /><path d="M11 18.5h2" /></svg>
                                                        {{ __('Telefon') }}
                                                    </p>
                                                    <p class="mt-1 text-sm text-slate-500">{{ $mob ? __('Telefonda bu resim görünür.') : __('Ayrı resim yoksa masaüstü resminden kesilir.') }}</p>
                                                    <div class="mt-3 flex flex-wrap items-center gap-2">
                                                        <label class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl px-3.5 py-2 text-sm font-semibold text-slate-700 ring-1 ring-slate-200 transition hover:bg-slate-50">
                                                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 16V4M7 9l5-5 5 5M4 20h16" /></svg>
                                                            {{ $mob ? __('Değiştir') : __('Telefon resmi seç') }}
                                                            <input type="file" name="city_mobile[{{ $rowCity->id }}]" accept="image/jpeg,image/png,image/webp" class="sr-only">
                                                        </label>
                                                        @if ($mob)
                                                            <label class="inline-flex cursor-pointer items-center gap-2 rounded-xl px-3 py-2 text-sm font-medium text-slate-500 hover:bg-red-50 hover:text-red-600">
                                                                <input type="checkbox" name="remove[city_mobile][{{ $rowCity->id }}]" value="1" class="rounded border-slate-300 text-red-600">{{ __('Kaldır') }}
                                                            </label>
                                                        @endif
                                                    </div>
                                                    <span class="mt-1 block text-xs font-medium text-emerald-700" data-file-name></span>
                                                </div>
                                            </section>

                                            {{-- kadraj: bu şehrin bütün önizlemeleri birlikte değişir --}}
                                            <section>
                                                <p class="text-sm font-semibold text-slate-900">{{ __('Kadraj: resmin hangi tarafı görünsün?') }}</p>
                                                <div class="mt-2 flex items-center gap-3">
                                                    <span class="text-xs font-medium text-slate-500">{{ __('Sol') }}</span>
                                                    <input type="range" name="city_focus[{{ $rowCity->id }}]" min="0" max="100" step="5" value="{{ old('city_focus.' . $rowCity->id, $cityFocus) }}" class="w-full accent-brand-900" data-banner-focus>
                                                    <span class="text-xs font-medium text-slate-500">{{ __('Sağ') }}</span>
                                                </div>
                                            </section>
                                        @endif

                                        {{-- fotoğraf üstü kartlar (şehirler) / kemerler (blog) --}}
                                        @if ($page->key === 'cities')
                                            <section>
                                                <p class="text-sm font-semibold text-slate-900">{{ __('Fotoğrafın üstündeki kartlar') }}</p>
                                                <p class="mt-0.5 text-xs text-slate-500">{{ __('Otomatik: kapak resmi olan ilk rehber konuları.') }}</p>
                                                @if ($guideOptions->isEmpty())
                                                    <p class="mt-3 rounded-xl bg-slate-50 px-4 py-3 text-sm text-slate-500 ring-1 ring-slate-200">{{ __('Bu şehrin rehberi henüz yazılmamış; kartlar rehber yazılınca seçilebilir.') }}</p>
                                                @else
                                                    <div class="mt-3 grid gap-3 sm:grid-cols-2">
                                                        @foreach ([__('Sol alt'), __('Sağ üst')] as $i => $cardLabel)
                                                            <label class="block">
                                                                <span class="mb-1 block text-xs font-semibold text-slate-600">{{ $cardLabel }}</span>
                                                                <select name="cards[{{ $rowCity->id }}][{{ $i }}]" class="{{ $field }}">
                                                                    <option value="" @selected($cardValues[$i] === '')>{{ __('Otomatik') }}</option>
                                                                    <option value="0" @selected($cardValues[$i] === '0')>{{ __('Gösterme') }}</option>
                                                                    @foreach ($guideOptions as $option)
                                                                        <option value="{{ $option->id }}" @selected($cardValues[$i] === (string) $option->id)>{{ $option->topic->getTranslation('name', 'tr', false) ?: $option->topic->name }}{{ $option->image_path ? '' : ' (' . __('resimsiz') . ')' }}</option>
                                                                    @endforeach
                                                                </select>
                                                            </label>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </section>
                                        @elseif ($arch)
                                            <section>
                                                <p class="text-sm font-semibold text-slate-900">{{ __('Kemerler') }}</p>
                                                <p class="mt-0.5 text-xs text-slate-500">{{ __('Sağdaki üç kemerde hangi yazı dursun. Kapağı olmayan yazı seçilemez.') }}</p>
                                                <div class="mt-3 grid gap-3">
                                                    @foreach ([__('Orta (büyük)'), __('Sol'), __('Sağ')] as $i => $archLabel)
                                                        <label class="block">
                                                            <span class="mb-1 flex items-center gap-1.5 text-xs font-semibold text-slate-600">
                                                                <span @class(['inline-block w-2.5 rounded-t-full', 'h-4 bg-red-400' => $i === 0, 'h-3 bg-slate-300' => $i > 0])></span>{{ $archLabel }}
                                                            </span>
                                                            <select name="arches[{{ $rowCity->id }}][{{ $i }}]" class="{{ $field }}">
                                                                <option value="">{{ __('Otomatik') }}</option>
                                                                @foreach ($arch['city']->blogs as $archBlog)
                                                                    <option value="{{ $archBlog->id }}" @selected($arch['values'][$i] === (string) $archBlog->id) @disabled(!$archBlog->cover_path)>
                                                                        {{ \Illuminate\Support\Str::limit($archBlog->getTranslation('title', 'en', false), 50) }}{{ $archBlog->cover_path ? '' : ' (' . __('kapak yok') . ')' }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </label>
                                                    @endforeach
                                                </div>
                                            </section>
                                        @endif
                                    </div>

                                    <div class="flex items-center justify-end gap-2 border-t border-slate-100 bg-slate-50/70 px-6 py-4">
                                        <button type="button" data-city-close class="rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-600 ring-1 ring-slate-200 transition hover:bg-white">{{ __('Kapat') }}</button>
                                        <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-brand-900 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-800">
                                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12l5 5L20 7" /></svg>
                                            {{ __('Kaydet') }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </x-admin.card>
        @endif
    </form>

    <script>
        (() => {
            const form = document.getElementById('hero-text-form');
            // canlı önizleme: yazılar
            const live = () => form.querySelectorAll('[data-live-out]').forEach((out) => {
                const input = form.querySelector(`[data-live="${out.dataset.liveOut}"]`);
                out.textContent = ((input?.value || out.dataset.example) ?? '').replaceAll(':city', 'Istanbul');
            });
            live();
            form.addEventListener('input', (event) => {
                if (event.target.matches('[data-live]')) live();
                // Sayfa kadrajı: "Görünüm" adımındaki ve genel resmin altındaki kaydırıcı aynı ayar; biri oynayınca diğeri de
                // (sitede genel resme uygulanır). Şehirlerin kendi kadrajı ayrı (Düzenle paneli).
                const pageFocus = (value) => {
                    form.querySelectorAll('[data-hero-focus], [data-banner-slot] [data-banner-focus]').forEach((el) => { if (el !== event.target) el.value = value; });
                    form.querySelectorAll('[data-hero-img], [data-live-img], [data-banner-slot] [data-preview]').forEach((img) => img.style.objectPosition = value + '% center');
                };
                if (event.target.matches('[data-hero-focus]') || event.target.closest('[data-banner-slot]')?.contains(event.target) && event.target.matches('[data-banner-focus]')) {
                    pageFocus(event.target.value);
                } else if (event.target.matches('[data-banner-focus]')) {
                    // Şehir kadrajı: o şehrin listedeki küçük resmi ve paneldeki masaüstü/telefon önizlemesi birlikte değişir
                    event.target.closest('li')?.querySelectorAll('[data-preview]').forEach((img) => img.style.objectPosition = event.target.value + '% center');
                }
                if (event.target.name === 'overlay') form.querySelector('[data-hero-overlay]').className = 'absolute inset-0 bg-gradient-to-r ' + event.target.dataset.classes + ' to-transparent';
            });
            // Şehir düzenleme paneli: aç / kapat (Esc), arkadaki sayfa kaymasın
            const drawers = () => [...form.querySelectorAll('[data-city-drawer]')];
            const closeDrawers = () => { drawers().forEach((d) => d.classList.add('hidden')); document.documentElement.classList.remove('overflow-hidden'); };
            form.addEventListener('click', (event) => {
                if (event.target.closest('[data-city-open]')) {
                    closeDrawers();
                    event.target.closest('[data-city-row]').querySelector('[data-city-drawer]').classList.remove('hidden');
                    document.documentElement.classList.add('overflow-hidden');
                } else if (event.target.closest('[data-city-close]')) {
                    closeDrawers();
                }
            });
            document.addEventListener('keydown', (event) => event.key === 'Escape' && closeDrawers());
            // Kaydedilince satırlar sunucudan yenilenir (yeni küçük resim, durum): açık panel de kapanır
            document.addEventListener('live:refreshed', () => { if (!drawers().some((d) => !d.classList.contains('hidden'))) document.documentElement.classList.remove('overflow-hidden'); });
            if (drawers().some((d) => !d.classList.contains('hidden'))) document.documentElement.classList.add('overflow-hidden');

            // seçilen resim hemen önizlenir (kaydetmeden)
            form.addEventListener('change', (event) => {
                const input = event.target;
                if (input.type !== 'file' || !input.files?.length) return;
                const box = input.closest('[data-banner-box]');
                const img = box.querySelector('[data-preview]');
                const src = URL.createObjectURL(input.files[0]);
                img.src = src;
                img.classList.remove('opacity-40', 'opacity-50', 'opacity-60', 'grayscale', 'hidden');
                // şehir satırı: masaüstü resmi seçilince listedeki küçük resim de değişsin
                const row = input.closest('[data-city-row]');
                if (row && input.name.startsWith('city[')) {
                    const thumb = row.querySelector(':scope > div [data-preview]');
                    if (thumb) { thumb.src = src; thumb.classList.remove('opacity-50', 'grayscale'); }
                }
                box.querySelector('[data-note]')?.remove();
                const name = box.querySelector('[data-file-name]');
                if (name) name.textContent = '✓ ' + input.files[0].name;
            });
        })();
    </script>
@endsection
