@extends('layouts.admin')

@section('title', $page->name)

@php
    $seoDone = filled($page->meta_title) && filled($page->meta_description);
    $seoPending = count(array_filter(app(\App\Services\Translation\ModelTranslator::class)->status($page), fn ($s) => $s !== 'ok'));
    $hero = $page->key === 'home' ? \App\Support\HeroSettings::all() : null;
    $handle = '<span data-drag-handle draggable="true" title="' . e(__('Sürükleyerek sırala')) . '" class="absolute left-3 top-3 z-10 grid h-9 w-7 cursor-grab place-items-center rounded-lg bg-white/90 text-slate-400 shadow-sm ring-1 ring-slate-200 backdrop-blur transition hover:text-slate-700 active:cursor-grabbing"><svg class="size-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="9" cy="6" r="1.5"/><circle cx="15" cy="6" r="1.5"/><circle cx="9" cy="12" r="1.5"/><circle cx="15" cy="12" r="1.5"/><circle cx="9" cy="18" r="1.5"/><circle cx="15" cy="18" r="1.5"/></svg></span>';
@endphp

@section('header')
    <x-admin.page-header :title="$page->name" :description="__('tripspoiler.com:path · Düzenlemek istediğin bölüme tıkla.', ['path' => $page->path])" :breadcrumb="['Yönetim' => route('management.index'), 'Sayfalar' => route('pages.index')]">
        <x-slot:icon><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8Z" /><path d="M14 3v5h5" /></x-slot:icon>
        <a href="{{ $page->siteUrl() }}" target="_blank" rel="noopener"
            class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-700 ring-1 ring-slate-200 transition hover:bg-white">{{ __('Sayfayı aç') }} ↗</a>
    </x-admin.page-header>
@endsection

@section('content')
    <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3" data-sortable-grid data-order-key="page_cards_order" data-order-url="{{ route('cards.order') }}"
        data-initial-order="{{ \App\Models\Setting::get('page_cards_order') }}">

        {{-- SEO (aktivite detayında her aktivitenin kendi formunda) --}}
        @if (!$page->hasOwnSeo())
            @php $isPost = $page->key === 'post'; @endphp
            <a href="{{ $isPost ? route('blogs.index') : route('activities.index') }}" class="group relative block focus-visible:outline-none" data-card="seo">
                <x-admin.card class="flex h-full flex-col overflow-hidden transition group-hover:-translate-y-1 group-hover:shadow-xl">
                    {!! $handle !!}
                    <div class="flex aspect-[16/8] flex-col justify-center bg-slate-100 px-8 pl-12">
                        <div class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200/70">
                            <p class="text-[10px] text-slate-500">tripspoiler.com/{{ $isPost ? 'blog' : 'activities' }}/…</p>
                            <p class="mt-0.5 truncate text-sm font-medium text-blue-700">{{ $isPost ? __('Her yazının kendi başlığı') : __('Her aktivitenin kendi başlığı') }}</p>
                            <p class="mt-0.5 text-[11px] text-slate-500">{{ $isPost ? __('Blog formundaki SEO adımı') : __('Aktivite formundaki SEO adımı') }}</p>
                        </div>
                    </div>
                    <div class="flex flex-1 flex-col p-6">
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-lg font-semibold text-slate-900">{{ __('SEO ayarları') }}</p>
                            <span class="text-sm font-semibold text-brand-900 transition group-hover:translate-x-1">{{ $isPost ? __('Blog yazılarına git') : __('Aktivitelere git') }} →</span>
                        </div>
                        <p class="mt-1.5 flex-1 text-sm leading-relaxed text-slate-600">{{ $isPost ? __('Her yazının Google başlığı ve açıklaması kendi düzenleme formunda girilir; burada ortak ayar yok.') : __('Her aktivitenin Google başlığı ve açıklaması kendi düzenleme formunda girilir; burada ortak ayar yok.') }}</p>
                    </div>
                </x-admin.card>
            </a>
        @else
        <a href="{{ route('pages.seo', $page) }}" class="group relative block focus-visible:outline-none" data-card="seo">
            <x-admin.card class="flex h-full flex-col overflow-hidden transition group-hover:-translate-y-1 group-hover:shadow-xl">
                {!! $handle !!}
                <div class="flex aspect-[16/8] flex-col justify-center bg-slate-100 px-8 pl-12">
                    <div class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200/70">
                        <p class="text-[10px] text-slate-500">tripspoiler.com{{ $page->path }}</p>
                        <p class="mt-0.5 truncate text-sm font-medium text-blue-700">{{ $page->meta_title ?: $page->name . ' | TripSpoiler' }}</p>
                        <p class="mt-0.5 line-clamp-2 text-[11px] text-slate-500">{{ $page->meta_description ?: __('Açıklama…') }}</p>
                    </div>
                </div>
                <div class="flex flex-1 flex-col p-6">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-lg font-semibold text-slate-900">{{ __('SEO ayarları') }}</p>
                        <span class="text-sm font-semibold text-brand-900 transition group-hover:translate-x-1">{{ __('Düzenle') }} →</span>
                    </div>
                    <p class="mt-1.5 flex-1 text-sm leading-relaxed text-slate-600">{{ __('Google sonuçlarında bu sayfanın başlığı ve altındaki açıklama; 12 dile çevrilir.') }}</p>
                    <p class="mt-3 text-xs font-medium {{ $seoDone ? ($seoPending ? 'text-amber-700' : 'text-emerald-700') : 'text-amber-700' }}">
                        {{ !$seoDone ? __('Eksik: başlık ya da açıklama girilmemiş') : ($seoPending ? __(':count dil çeviri bekliyor', ['count' => $seoPending]) : __('✓ Tamam, tüm diller güncel')) }}
                    </p>
                </div>
            </x-admin.card>
        </a>

        @endif

        {{-- HERO (şimdilik anasayfada) --}}
        @if ($hero)
            <a href="{{ route('site-settings.hero') }}" class="group relative block focus-visible:outline-none" data-card="hero">
                <x-admin.card class="flex h-full flex-col overflow-hidden transition group-hover:-translate-y-1 group-hover:shadow-xl">
                    {!! $handle !!}
                    <div class="relative aspect-[16/8] overflow-hidden bg-slate-900">
                        @if ($hero['image_url'])
                            <img src="{{ $hero['image_url'] }}" alt="" class="absolute inset-0 size-full object-cover transition duration-700 group-hover:scale-105" style="object-position: {{ $hero['focus'] }}% center">
                        @endif
                        <div class="absolute inset-0 bg-gradient-to-r from-slate-950/80 via-slate-950/30 to-transparent"></div>
                        <div class="absolute left-12 top-1/2 w-1/2 -translate-y-1/2 space-y-1.5">
                            <div class="h-2.5 w-full rounded bg-white/90"></div>
                            <div class="h-2.5 w-4/5 rounded bg-white/90"></div>
                            <div class="h-2.5 w-3/5 rounded bg-red-500"></div>
                        </div>
                    </div>
                    <div class="flex flex-1 flex-col p-6">
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-lg font-semibold text-slate-900">{{ __('Hero alanı') }}</p>
                            <span class="text-sm font-semibold text-brand-900 transition group-hover:translate-x-1">{{ __('Düzenle') }} →</span>
                        </div>
                        <p class="mt-1.5 flex-1 text-sm leading-relaxed text-slate-600">{{ __('Sayfayı açınca ilk görülen büyük alan: arka plan resmi, başlık yazıları ve kartlar.') }}</p>
                        <p class="mt-3 text-xs font-medium {{ $hero['image'] ? 'text-emerald-700' : 'text-slate-500' }}">
                            {{ $hero['image'] ? __('✓ Arka plan resmi yüklü') : __('Varsayılan resim kullanılıyor') }}
                        </p>
                    </div>
                </x-admin.card>
            </a>
        @endif

        {{-- ANASAYFA BÖLÜMLERİ: Yönetim > Bölümler'de düzenlenir (sıra, aç/kapa, yazılar) --}}
        @if ($page->key === 'home')
            @php
                $homeLabels = [
                    'steps' => 'Nasıl çalışır', 'blog' => 'Blog bölümü', 'features' => 'Özellik kartları', 'announcements' => 'Duyuru şeridi',
                    'intro' => 'TripSpoiler nedir', 'social' => 'Sosyal medya', 'reviews' => 'Yorumlar', 'city_entry' => 'Şehir girişi', 'partners' => 'Destekçiler', 'rates' => 'Döviz kurları',
                ];
                $homeOn = array_values(array_filter(\App\Support\SiteSections::KEYS, fn ($key) => \App\Support\SiteSections::enabled($key)));
            @endphp
            <a href="{{ route('site-settings.index') }}" class="group relative block focus-visible:outline-none" data-card="sections">
                <x-admin.card class="flex h-full flex-col overflow-hidden transition group-hover:-translate-y-1 group-hover:shadow-xl">
                    {!! $handle !!}
                    <div class="flex aspect-[16/8] flex-col justify-center gap-1.5 bg-slate-100 px-8 pl-12">
                        @forelse (array_slice($homeOn, 0, 4) as $key)
                            <div class="flex items-center gap-2 rounded-lg bg-white px-3 py-2 text-xs font-medium text-slate-700 shadow-sm ring-1 ring-slate-200/70">
                                <span class="text-slate-300">⋮⋮</span>{{ __($homeLabels[$key] ?? $key) }}
                                <span class="ml-auto h-3 w-6 rounded-full bg-emerald-500"></span>
                            </div>
                        @empty
                            <p class="text-center text-sm text-slate-500">{{ __('Açık bölüm yok') }}</p>
                        @endforelse
                        @if (count($homeOn) > 4)
                            <p class="px-1 text-[11px] font-medium text-slate-500">{{ __('+ :n bölüm daha', ['n' => count($homeOn) - 4]) }}</p>
                        @endif
                    </div>
                    <div class="flex flex-1 flex-col p-6">
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-lg font-semibold text-slate-900">{{ __('Bölümler') }}</p>
                            <span class="text-sm font-semibold text-brand-900 transition group-hover:translate-x-1">{{ __('Düzenle') }} →</span>
                        </div>
                        <p class="mt-1.5 flex-1 text-sm leading-relaxed text-slate-600">{{ __('Hero\'nun altındaki bölümler: yazılarını düzenle, sürükleyerek sırala, istemediğini kapat.') }}</p>
                        <p class="mt-3 text-xs font-medium text-slate-500">{{ __(':n / :total bölüm açık', ['n' => count($homeOn), 'total' => count(\App\Support\SiteSections::KEYS)]) }}</p>
                    </div>
                </x-admin.card>
            </a>
        @endif

        {{-- BÖLÜMLER (anasayfa hariç): sıra + aç/kapa --}}
        @if (\App\Support\PageSections::supports($page->key))
            @php
                $sectionConfig = \App\Support\PageSections::config($page->key);
                $activeSections = array_values(array_filter($sectionConfig['order'], fn ($key) => $key !== 'main' && in_array($key, $sectionConfig['on'], true)));
            @endphp
            <a href="{{ route('pages.sections', $page) }}" class="group relative block focus-visible:outline-none" data-card="sections">
                <x-admin.card class="flex h-full flex-col overflow-hidden transition group-hover:-translate-y-1 group-hover:shadow-xl">
                    {!! $handle !!}
                    <div class="flex aspect-[16/8] flex-col justify-center gap-1.5 bg-slate-100 px-8 pl-12">
                        @forelse (array_slice($activeSections, 0, 4) as $key)
                            <div class="flex items-center gap-2 rounded-lg bg-white px-3 py-2 text-xs font-medium text-slate-700 shadow-sm ring-1 ring-slate-200/70">
                                <span class="text-slate-300">⋮⋮</span>{{ __($key === 'main' ? '' : \App\Support\PageSections::labels($page->key)[$key][0]) }}
                                <span class="ml-auto h-3 w-6 rounded-full bg-emerald-500"></span>
                            </div>
                        @empty
                            <p class="text-center text-sm text-slate-500">{{ __('Açık bölüm yok') }}</p>
                        @endforelse
                    </div>
                    <div class="flex flex-1 flex-col p-6">
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-lg font-semibold text-slate-900">{{ __('Bölümler') }}</p>
                            <span class="text-sm font-semibold text-brand-900 transition group-hover:translate-x-1">{{ __('Düzenle') }} →</span>
                        </div>
                        <p class="mt-1.5 flex-1 text-sm leading-relaxed text-slate-600">{{ __('Sayfanın altındaki bölümler: sürükleyerek sırala, istemediğini kapat.') }}</p>
                        <p class="mt-3 text-xs font-medium text-slate-500">{{ __(':n bölüm açık', ['n' => count($activeSections)]) }}</p>
                    </div>
                </x-admin.card>
            </a>
        @endif

        {{-- SAYFA İÇERİĞİ (hakkımızda) --}}
        @if (isset(\App\Http\Controllers\PageContentController::BLOCKS[$page->key]))
            @php
                $contentBlocks = \App\Http\Controllers\PageContentController::BLOCKS[$page->key];
                $written = \App\Models\SectionText::whereIn('key', array_keys($contentBlocks))->get()->filter(fn ($r) => filled($r->title))->count();
            @endphp
            <a href="{{ route('pages.content', $page) }}" class="group relative block focus-visible:outline-none" data-card="content">
                <x-admin.card class="flex h-full flex-col overflow-hidden transition group-hover:-translate-y-1 group-hover:shadow-xl">
                    {!! $handle !!}
                    <div class="flex aspect-[16/8] flex-col justify-center gap-2 bg-violet-50 px-8 pl-12">
                        <div class="h-3 w-1/2 rounded bg-violet-300"></div>
                        <div class="h-2 w-full rounded bg-violet-200"></div>
                        <div class="h-2 w-5/6 rounded bg-violet-200"></div>
                        <div class="mt-2 grid grid-cols-3 gap-2"><div class="h-8 rounded-lg bg-white"></div><div class="h-8 rounded-lg bg-white"></div><div class="h-8 rounded-lg bg-white"></div></div>
                    </div>
                    <div class="flex flex-1 flex-col p-6">
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-lg font-semibold text-slate-900">{{ __('Sayfa içeriği') }}</p>
                            <span class="text-sm font-semibold text-brand-900 transition group-hover:translate-x-1">{{ __('Düzenle') }} →</span>
                        </div>
                        <p class="mt-1.5 flex-1 text-sm leading-relaxed text-slate-600">{{ $page->key === 'rates' ? __('“Kurlar anlık değildir” uyarı kutusu (12 dil).') : ($page->key === 'privacy' ? __('Gizlilik metninin sekiz bölümü (12 dil).') : ($page->key === 'reviews' ? __('Liste başlığı ve “Yorum yaz” kutusunun yazıları (12 dil).') : __('Sayfadaki bütün yazılar: hikâye, adımlar, ilkeler ve kapanış (12 dil).'))) }}</p>
                        <p class="mt-3 text-xs font-medium text-slate-500">{{ __(':n / :t blok yazıldı', ['n' => $written, 't' => count($contentBlocks)]) }}</p>
                    </div>
                </x-admin.card>
            </a>
        @endif

        {{-- HERO YAZILARI (aktiviteler, şehirler, blog) --}}
        @if (isset(\App\Http\Controllers\PageHeroTextController::FIELDS[$page->key]))
            @php
                $heroText = \App\Models\SectionText::for('hero_' . $page->key);
                $heroPending = count(array_filter(app(\App\Services\Translation\ModelTranslator::class)->status($heroText), fn ($s) => $s !== 'ok'));
            @endphp
            <a href="{{ route('pages.hero-text', $page) }}" class="group relative block focus-visible:outline-none" data-card="hero-text">
                <x-admin.card class="flex h-full flex-col overflow-hidden transition group-hover:-translate-y-1 group-hover:shadow-xl">
                    {!! $handle !!}
                    @php
                        $heroImage = isset(\App\Http\Controllers\PageHeroController::SPECS[$page->key])
                            ? (\App\Models\PageBanner::where('page_key', $page->key)->whereNull('city_id')->value('image') ?: \App\Models\Setting::get('hero_image'))
                            : null;
                    @endphp
                    <div class="relative flex aspect-[16/8] flex-col justify-center gap-2 overflow-hidden bg-[#0F1420] px-8 pl-12">
                        @if ($heroImage)
                            <img src="{{ \App\Services\ImageUploader::url($heroImage, 'md') }}" alt="" class="absolute inset-0 size-full object-cover opacity-50 transition duration-700 group-hover:scale-105">
                            <div class="absolute inset-0 bg-gradient-to-r from-[#0F1420] via-[#0F1420]/60 to-transparent"></div>
                        @endif
                        <div class="relative h-2 w-16 rounded bg-red-400"></div>
                        <div class="relative h-4 w-3/4 rounded bg-white/90"></div>
                        <div class="relative h-2.5 w-1/2 rounded bg-white/40"></div>
                        <div class="relative mt-2 flex gap-2"><div class="h-5 w-16 rounded-full bg-red-500"></div><div class="h-5 w-16 rounded-full ring-1 ring-white/30"></div></div>
                    </div>
                    <div class="flex flex-1 flex-col p-6">
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-lg font-semibold text-slate-900">{{ __('Hero alanı') }}</p>
                            <span class="text-sm font-semibold text-brand-900 transition group-hover:translate-x-1">{{ __('Düzenle') }} →</span>
                        </div>
                        <p class="mt-1.5 flex-1 text-sm leading-relaxed text-slate-600">{{ match ($page->key) { 'blog' => __('Sayfanın üstü: yazılar (12 dil) ve her şehir için kemerlerdeki yazılar.'), 'cities' => __('Sayfanın üstü: yazılar (12 dil), resimler (şehir şehir, telefon dahil), görünüm ve fotoğraf üstü kartlar.'), 'contact', 'privacy', 'rates', 'campaigns' => __('Sayfanın üstündeki başlık ve açıklama (12 dil).'), 'reviews' => __('Sayfanın üstü: fotoğraf (telefon dahil), başlık, açıklama (12 dil), görünüm ve yorum kartları.'), 'about' => __('Sayfanın üstü: fotoğraf (telefon dahil), başlık ve açıklama (12 dil), görünüm.'), default => __('Sayfanın üstü: yazılar (12 dil), resimler (şehir şehir, telefon dahil) ve görünüm.') } }}</p>
                        <p class="mt-3 text-xs font-medium {{ filled($heroText->title) ? ($heroPending ? 'text-amber-700' : 'text-emerald-700') : 'text-slate-500' }}">
                            {{ !filled($heroText->title) ? __('Varsayılan yazılar kullanılıyor') : ($heroPending ? __(':count dil çeviri bekliyor', ['count' => $heroPending]) : __('✓ Tamam, tüm diller güncel')) }}
                        </p>
                    </div>
                </x-admin.card>
            </a>
        @endif
    </div>
@endsection
