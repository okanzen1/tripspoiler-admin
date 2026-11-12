@extends('layouts.admin')

@section('title', 'Yönetim')

@section('header')
    <x-admin.page-header :title="__('Yönetim')" :description="__('Sayfalar, bölümler, ülkeler, şehirler, partnerler, kullanıcılar ve yetkiler.')">
        <x-slot:icon>
            <path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" />
            <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.32 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1Z" />
        </x-slot:icon>
    </x-admin.page-header>
@endsection

@section('content')
    @php
        $cards = [
            [
                'key' => 'site-settings',
                'title' => 'Bölümler',
                'description' => 'Anasayfadaki bölümler: blog, yorumlar, nasıl çalışır, özellik kartları, duyuru şeridi.',
                'url' => route('site-settings.index'),
                'stat' => __(':count bölüm', ['count' => 8]),
                'color' => \App\Support\SectionColor::forRoute('site-settings'),
                'icon' => '<rect x="3" y="4" width="18" height="7" rx="1.5" /><rect x="3" y="14" width="8" height="6" rx="1.5" /><rect x="14" y="14" width="7" height="6" rx="1.5" />',
            ],
            [
                'key' => 'locations',
                'title' => 'Ülkeler ve şehirler',
                'description' => 'Sitede görünecek ülke ve şehirleri aç, kapat; yeni şehir ekle.',
                'url' => route('locations.index'),
                'stat' => __(':countries ülke · :cities şehir yayında', ['countries' => $activeCountryCount, 'cities' => $activeCityCount]),
                'color' => \App\Support\SectionColor::forRoute('locations'),
                'icon' => '<circle cx="12" cy="12" r="9" /><path d="M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18" />',
            ],
            [
                'key' => 'pages',
                'title' => 'Sayfalar',
                'description' => 'Sabit sayfaların SEO başlığı ve açıklaması.',
                'url' => route('pages.index'),
                'stat' => $pagesMissingSeo ? __(':count eksik', ['count' => $pagesMissingSeo]) : __('Tamam'),
                'color' => \App\Support\SectionColor::forRoute('pages'),
                'icon' => '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8Z" /><path d="M14 3v5h5" />',
            ],
            [
                'key' => 'partners',
                'title' => 'Partnerler',
                'description' => 'Affiliate partnerleri, takip kodları ve komisyonlar.',
                'url' => route('partners.index'),
                'stat' => __(':count aktif', ['count' => $activePartnerCount]),
                'color' => \App\Support\SectionColor::forRoute('partners'),
                'icon' => '<path d="M10 13a5 5 0 0 0 7.5.5l3-3a5 5 0 0 0-7-7l-1.7 1.7" /><path d="M14 11a5 5 0 0 0-7.5-.5l-3 3a5 5 0 0 0 7 7l1.7-1.7" />',
            ],
            [
                'key' => 'users',
                'title' => 'Kullanıcılar',
                'description' => 'Admin ve editör ekle, düzenle, sil.',
                'url' => route('users.index'),
                'stat' => __(':count kullanıcı', ['count' => $userCount]),
                'color' => \App\Support\SectionColor::forRoute('users'),
                'icon' => '<circle cx="9" cy="8" r="3.5" /><path d="M2.5 20c.8-3.4 3.4-5.5 6.5-5.5s5.7 2.1 6.5 5.5M16 4.6a3.5 3.5 0 0 1 0 6.8M18 14.8c1.9.8 3.1 2.6 3.5 5.2" />',
            ],
            [
                'key' => 'roles',
                'title' => 'Roller',
                'description' => 'Hangi rolün nelere erişebildiğini gör.',
                'url' => route('roles.index'),
                'stat' => __(':count rol', ['count' => $roleCount]),
                'color' => \App\Support\SectionColor::forRoute('roles'),
                'icon' => '<path d="M12 3 4 6v6c0 4.5 3.4 8.3 8 9 4.6-.7 8-4.5 8-9V6l-8-3Z" /><path d="m9 12 2 2 4-4" />',
            ],
            [
                'key' => 'translations',
                'title' => 'Diller ve çeviri',
                'description' => 'Sitenin dilleri ve tek tuşla ücretsiz çeviri.',
                'url' => route('translations.index'),
                'stat' => __(':count dil', ['count' => $activeLanguageCount]),
                'color' => \App\Support\SectionColor::forRoute('translations'),
                'icon' => '<path d="m5 8 6 6M4 14l6-6 2-3M2 5h12M7 2h1M22 22l-5-10-5 10M14 18h6" />',
            ],
            [
                'key' => 'redirects',
                'title' => 'Yönlendirmeler',
                'description' => 'Eski adresleri yenilerine yönlendir; Google 404 görmesin.',
                'url' => route('redirects.index'),
                'stat' => __(':count yönlendirme', ['count' => $redirectCount]),
                'color' => \App\Support\SectionColor::forRoute('redirects'),
                'icon' => '<path d="M4 7h11l-3-3M20 17H9l3 3" />',
            ],
            [
                'key' => 'activity-logs',
                'title' => 'İşlem kaydı',
                'description' => 'Panelde kim, ne zaman, neyi değiştirdi; girişler ve başarısız denemeler.',
                'url' => route('activity-logs.index'),
                'stat' => __('Bugün :count işlem', ['count' => \App\Models\ActivityLog::where('created_at', '>=', today())->count()]),
                'color' => \App\Support\SectionColor::forRoute('activity-logs'),
                'icon' => '<path d="M12 8v4l3 2" /><circle cx="12" cy="12" r="9" />',
            ],
            [
                'key' => 'media-trash',
                'title' => 'Silinen resimler',
                'description' => 'Değiştirilen ve silinen resimler 30 gün burada bekler; tek tıkla geri alınır.',
                'url' => route('media-trash.index'),
                'stat' => __(':count resim', ['count' => count(\App\Support\MediaTrash::items())]),
                'color' => \App\Support\SectionColor::forRoute('media-trash'),
                'icon' => '<path d="M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13" /><path d="M10 11v6M14 11v6" />',
            ],
            [
                'key' => 'queue',
                'title' => 'Arka plan işleri',
                'description' => 'Çeviriler ve sıradaki işler: ne sürüyor, ne bekliyor, ne başarısız oldu.',
                'url' => route('queue.index'),
                'stat' => ($failedJobs = \Illuminate\Support\Facades\DB::table('failed_jobs')->count()) ? __(':count başarısız', ['count' => $failedJobs]) : __(':count sırada', ['count' => \Illuminate\Support\Facades\DB::table('jobs')->count()]),
                'color' => \App\Support\SectionColor::forRoute('queue'),
                'icon' => '<path d="M4 6h16M4 12h10M4 18h7" /><circle cx="18" cy="16" r="3" /><path d="M18 14.5V16l1 1" />',
            ],
        ];
        $cards = \App\Http\Controllers\ManagementController::sorted($cards, 'management_order');
    @endphp

    {{-- ARAMA + İPUCU --}}
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <div class="relative w-full max-w-md">
            <svg class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7" /><path d="M20 20l-3.5-3.5" /></svg>
            <input type="search" data-card-search="management_order" placeholder="{{ __('Yönetimde ara… (ör. şehir, çeviri, kullanıcı)') }}" autocomplete="off"
                class="block w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-10 pr-3.5 text-sm outline-none transition placeholder:text-slate-400 focus:border-brand-900 focus:ring-4 focus:ring-brand-900/10">
        </div>
        <p class="inline-flex items-center gap-1.5 text-xs text-slate-500">
            <svg class="size-4 text-slate-400" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="9" cy="6" r="1.5"/><circle cx="15" cy="6" r="1.5"/><circle cx="9" cy="12" r="1.5"/><circle cx="15" cy="12" r="1.5"/><circle cx="9" cy="18" r="1.5"/><circle cx="15" cy="18" r="1.5"/></svg>
            {{ __('Sırayı değiştirmek için kartı soldaki tutamaçtan tutup sürükle.') }}
            <span class="hidden font-semibold text-emerald-700" data-order-saved="management_order">✓ {{ __('Sıra kaydedildi') }}</span>
        </p>
    </div>

    <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4" data-sortable-grid data-order-key="management_order" data-order-url="{{ route('cards.order') }}">
        @foreach ($cards as $card)
            <a href="{{ $card['url'] }}" class="group relative block focus-visible:outline-none" data-card="{{ $card['key'] }}"
                data-search="{{ mb_strtolower(__($card['title']) . ' ' . __($card['description'])) }}">
                <x-admin.card class="flex h-full flex-col p-6 transition group-hover:-translate-y-1 group-hover:shadow-xl group-hover:ring-brand-200 group-focus-visible:ring-2 group-focus-visible:ring-brand-500">
                    <div class="flex items-start justify-between">
                        <span class="flex items-center gap-2">
                        {{-- sürükleme tutamacı --}}
                        <span data-drag-handle draggable="true" title="{{ __('Sürükleyerek sırala') }}" aria-label="{{ __('Sürükleyerek sırala') }}"
                            class="-ml-2 grid h-12 w-6 cursor-grab place-items-center rounded-lg text-slate-300 transition hover:bg-slate-100 hover:text-slate-500 active:cursor-grabbing">
                            <svg class="size-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="9" cy="6" r="1.5"/><circle cx="15" cy="6" r="1.5"/><circle cx="9" cy="12" r="1.5"/><circle cx="15" cy="12" r="1.5"/><circle cx="9" cy="18" r="1.5"/><circle cx="15" cy="18" r="1.5"/></svg>
                        </span>
                        <span class="grid size-12 place-items-center rounded-2xl shadow-sm {{ \App\Support\SectionColor::classes($card['color'], 'solid') }}">
                            <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $card['icon'] !!}</svg>
                        </span>
                        </span>
                        <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ \App\Support\SectionColor::classes($card['color'], 'soft') }}">{{ $card['stat'] }}</span>
                    </div>
                    <h2 class="mt-5 text-lg font-semibold text-slate-900">{{ __($card['title']) }}</h2>
                    <p class="mt-1 flex-1 text-sm text-slate-500">{{ __($card['description']) }}</p>
                    <span class="mt-5 inline-flex items-center gap-1 text-sm font-semibold {{ \App\Support\SectionColor::classes($card['color'], 'text') }}">
                        {{ __('Aç') }}
                        <svg class="size-4 transition group-hover:translate-x-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" /></svg>
                    </span>
                </x-admin.card>
            </a>
        @endforeach
    </div>
    <p class="hidden py-12 text-center text-sm text-slate-500" data-card-empty="management_order">{{ __('Aramana uyan bölüm yok.') }}</p>
@endsection
