<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="robots" content="noindex, nofollow" />
    <meta name="color-scheme" content="light" />
    <title>{!! __(trim($__env->yieldContent('title', 'Panel'))) !!} · TripSpoiler Admin</title>
    @include('partials.favicon')
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    {{-- Daraltılmış menü tercihi: sayfa çizilmeden uygula ki zıplamasın --}}
    <script>
        try {
            if (localStorage.getItem('sidebar') === 'collapsed') document.documentElement.classList.add('sidebar-collapsed');
        } catch {}
    </script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    {{-- Sunucunun yükleme sınırı: görseller bu sınırı aşarsa tarayıcıda küçültülür (resources/js/image-compress.js) --}}
    <meta name="upload-limit" content="{{ \App\Support\UploadLimit::fileBytes() }}" data-request="{{ \App\Support\UploadLimit::requestBytes() }}">
    {{-- JS'teki metinlerin çevirisi (resources/js/i18n.js) --}}
    <script>
        window.i18n = {!! json_encode(collect(['Yeni', 'Açıklama…', 'Değiştir', 'Resim seç', 'En az bir abone seç ya da bir adres yaz.', 'Bu satır silinsin mi? Kaydettiğinde kalıcı olarak silinir.', 'Yazmaya başla…', 'Kaydedilemedi, tekrar dene.', ':count yeni görsel', 'Evet', 'otomatik', 'Görsel yükleniyor…', 'Görsel yüklenemedi (en fazla 5 MB; JPG, PNG, WebP).', 'Silinsin mi?', 'Emin misin?', 'Vazgeç', 'Evet, sil', 'Evet, devam et', ':s sn', ':m dk :s sn', 'Çeviri sürüyor, bitince tekrar kullanılabilir.', 'Diğer dillere çevriliyor…', 'Çeviri yapılamadı', 'Çeviri tamamlandı', 'Sayfayı yenile', 'Aç', 'Kapat', 'Çeviri başlatılıyor…', ':done / :total dil', 'Başlatılıyor…', 'Bu arada başka işlerine devam edebilirsin.', 'Çeviri başlatılamadı, tekrar dene.', 'Çok sık çeviri isteği gönderildi, bir dakika sonra tekrar dene.', 'Oturum süresi doldu, sayfayı yenileyip tekrar dene.', 'Çeviri başlatılamadı (:code).', 'Küçült', 'Çeviri', 'Soru bulunamadı. Her soru kendi satırında, cevabı altında olmalı.', ':added soru eklendi; en fazla :max soru olabildiği için :skipped tanesi eklenemedi.', ':added soru eklendi. Kontrol edip kaydet.', ':count soru bulundu', ':count soru'])->mapWithKeys(fn ($key) => [$key => __($key)]), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!};
        {{-- Arka planda süren çeviriler: sağ üstteki bildirim (resources/js/translation-jobs.js) --}}
        @auth
            window.translationJobs = {!! json_encode(['statusUrl' => route('translations.jobs'), 'now' => time(), 'jobs' => \App\Support\TranslationJobs::recent()], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!};
        @endauth
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="h-full overflow-x-clip bg-[#e9edf4] font-sans text-slate-800 antialiased">

    @php($user = auth()->user())

    {{-- Mobil arka plan --}}
    <div id="sidebar-backdrop" class="fixed inset-0 z-30 hidden bg-slate-900/40 backdrop-blur-sm lg:hidden"></div>

    {{-- Sidebar --}}
    <aside id="sidebar"
        class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col bg-brand-900 text-white transition-[transform,width] duration-200 lg:translate-x-0 lg:collapsed:w-20">

        {{-- Daralt / genişlet --}}
        <button type="button" id="sidebar-collapse" aria-label="{{ __('Menüyü daralt') }}" title="{{ __('Menüyü daralt / genişlet') }}"
            class="absolute -right-3 top-9 z-10 hidden size-6 place-items-center rounded-full bg-white text-brand-900 shadow-md ring-1 ring-slate-200 transition hover:scale-110 lg:grid">
            <svg class="size-3.5 transition-transform lg:collapsed:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 18l-6-6 6-6" /></svg>
        </button>

        <a href="{{ route('analytics.index') }}" class="flex items-center gap-3 px-5 py-6 lg:collapsed:justify-center lg:collapsed:px-0">
            <x-admin.logo class="size-11 shrink-0 drop-shadow-[0_2px_6px_rgba(0,0,0,0.35)]" />
            <span class="leading-tight lg:collapsed:hidden">
                <span class="block text-[15px] font-semibold tracking-tight">{{ __('TripSpoiler') }}</span>
                <span class="block text-xs text-white/60">{{ __('Yönetim paneli') }}</span>
            </span>
        </a>

        {{-- Kullanıcı --}}
        <div class="mx-4 mb-5 flex items-center gap-3 rounded-2xl bg-white/[0.06] p-3 ring-1 ring-white/10 lg:collapsed:mx-3 lg:collapsed:justify-center lg:collapsed:bg-transparent lg:collapsed:p-0 lg:collapsed:ring-0">
            <x-admin.avatar :user="$user" class="size-10 ring-2 ring-white/25" title="{{ $user->full_name }}" />
            <div class="min-w-0 flex-1 lg:collapsed:hidden">
                <p class="truncate text-sm font-semibold">{{ $user->full_name }}</p>
                <p class="truncate text-xs text-white/60">{{ $user->role->label() }}</p>
            </div>
        </div>

        <nav class="flex-1 space-y-1 overflow-y-auto overflow-x-hidden px-4 pb-6 text-sm lg:collapsed:px-3">
            <x-admin.nav-link route="analytics.index" :label="__('Analitik')">
                <path d="M4 20V10M10 20V4M16 20v-7M22 20H2" />
            </x-admin.nav-link>

            <x-admin.nav-link route="activities.index" :todo="\App\Support\TranslationTodo::area('activities')" :match="['activities.*']" :label="__('Aktiviteler')">
                <path d="M4 7h16v4a2 2 0 0 0 0 4v4H4v-4a2 2 0 0 0 0-4Z" /><path d="M14 7v2M14 12v1M14 16v1" />
            </x-admin.nav-link>

            <x-admin.nav-link route="blogs.index" :todo="\App\Support\TranslationTodo::area('blogs')" :match="['blogs.*']" :label="__('Blog')">
                <path d="M12 20h9" /><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z" />
            </x-admin.nav-link>

            <x-admin.nav-link route="city-guides.index" :todo="\App\Support\TranslationTodo::area('city-guides')" :match="['city-guides.*', 'city-guide-topics.*']" :label="__('Şehir rehberi')">
                <path d="M9 4 3.5 6v14L9 18l6 2 5.5-2V4L15 6z" /><path d="M9 4v14M15 6v14" />
            </x-admin.nav-link>

            <x-admin.nav-link route="faqs.index" :todo="\App\Support\TranslationTodo::area('faqs')" :match="['faqs.*']" :label="__('SSS')">
                <circle cx="12" cy="12" r="9" /><path d="M9.5 9a2.5 2.5 0 1 1 3.5 2.3c-.6.3-1 .9-1 1.6V14M12 17h.01" />
            </x-admin.nav-link>

            <x-admin.nav-link route="campaigns.index" :todo="\App\Support\TranslationTodo::area('campaigns')" :match="['campaigns.*']" :label="__('Kampanyalar')" :badge="\App\Models\Campaign::visible()->count()">
                <path d="M20 12v8a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-8M2.5 7.5h19v4.5h-19zM12 21V7.5M12 7.5H8a2.5 2.5 0 1 1 0-5c2.5 0 4 5 4 5zM12 7.5h4a2.5 2.5 0 1 0 0-5c-2.5 0-4 5-4 5z" />
            </x-admin.nav-link>

            <x-admin.nav-link route="announcements.index" :todo="\App\Support\TranslationTodo::area('announcements')" :match="['announcements.*']" :label="__('Duyurular')">
                <path d="M3 11v2a1 1 0 0 0 1 1h2l5 4V6L6 10H4a1 1 0 0 0-1 1Z" /><path d="M15.5 8.5a5 5 0 0 1 0 7M18.5 5.5a9 9 0 0 1 0 13" />
            </x-admin.nav-link>

            <x-admin.nav-link route="reviews.index" :match="['reviews.*']" :label="__('Yorumlar')" :badge="\App\Models\Review::pending()->count()">
                <path d="M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.6A8 8 0 1 1 21 12Z" /><path d="M8.5 11h.01M12 11h.01M15.5 11h.01" />
            </x-admin.nav-link>

            <x-admin.nav-link route="mailbox.index" :match="['mailbox.*']" :label="__('E-posta')" :badge="\App\Services\Mailbox\InboxUnreadCount::get()">
                <path d="M22 12h-6l-2 3h-4l-2-3H2" /><path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11Z" />
            </x-admin.nav-link>

            <x-admin.nav-link route="subscribers.index" :match="['subscribers.*']" :label="__('Blog aboneleri')">
                <path d="M4 4h12a4 4 0 0 1 4 4v12H8a4 4 0 0 1-4-4Z" /><path d="M8 9h8M8 13h5" />
            </x-admin.nav-link>

            @can('admin')
                <x-admin.nav-link route="management.index" :todo="\App\Support\TranslationTodo::area('management')" :match="['management.*', 'users.*', 'roles.*', 'locations.*', 'countries.*', 'cities.*', 'partners.*', 'pages.*', 'translations.index', 'redirects.*', 'media-trash.*', 'site-settings.*', 'features.*', 'steps.*', 'blog-section.*', 'section-texts.*']" :label="__('Yönetim')">
                    <path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" />
                    <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.32 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1Z" />
                </x-admin.nav-link>
            @endcan
        </nav>

    </aside>

    <div class="flex min-h-full min-w-0 flex-col transition-[padding] duration-200 lg:pl-64 lg:collapsed:pl-20">

        {{-- Üst bar --}}
        <header class="sticky top-0 z-20 flex h-14 items-center gap-3 border-b border-slate-200 bg-surface/95 px-4 backdrop-blur sm:px-8 lg:px-10">
            <button type="button" id="sidebar-open" aria-label="{{ __('Menüyü aç') }}"
                class="-ml-2 grid size-10 place-items-center rounded-xl text-slate-600 hover:bg-slate-100 lg:hidden">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true">
                    <path d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
            <x-admin.logo class="size-8 lg:hidden" />
            {{-- Geri: bir önceki panel sayfasına; dışarıdan gelindiyse bir üst sayfaya (adresin son parçası atılır) --}}
            @unless (request()->routeIs('analytics.index'))
                <button type="button" data-back
                    class="inline-flex items-center gap-1.5 rounded-xl px-3 py-2 text-sm font-semibold text-slate-600 ring-1 ring-slate-200 transition hover:bg-white hover:text-slate-900">
                    <svg class="size-4 rtl:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 18l-6-6 6-6" /></svg>
                    <span class="hidden sm:inline">{{ __('Geri') }}</span>
                </button>
            @endunless
            {{-- Tarih: çubuğun ortasında --}}
            <span class="pointer-events-none absolute left-1/2 hidden -translate-x-1/2 whitespace-nowrap text-sm font-semibold text-slate-700 md:block">{{ now()->locale(app()->getLocale())->translatedFormat('j F Y, l') }}</span>
            <div class="ml-auto flex items-center gap-2.5">
                <x-admin.locale-switch />
            </div>
        </header>

        {{-- Sayfa başlığı bandı --}}
        @hasSection('header')
            <div class="border-b border-slate-200 bg-surface px-4 py-6 sm:px-8 lg:px-10">
                @yield('header')
            </div>
        @endif

        <main class="w-full min-w-0 flex-1 px-4 py-8 sm:px-8 lg:px-10">
            @if (session('success'))
                <div role="status" class="mb-6 flex items-center gap-3 rounded-2xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 ring-1 ring-emerald-200">
                    <svg class="size-5 shrink-0 text-emerald-600" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.86-9.86a.75.75 0 0 0-1.22-.88l-3.24 4.5-1.6-1.6a.75.75 0 1 0-1.06 1.06l2.22 2.22a.75.75 0 0 0 1.14-.09l3.76-5.2Z" clip-rule="evenodd" />
                    </svg>
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div role="alert" class="mb-6 rounded-2xl bg-red-50 px-4 py-3 text-sm font-medium text-red-700 ring-1 ring-red-200">
                    {{ session('error') }}
                </div>
            @endif

            @yield('content')
        </main>

        {{-- Alt bilgi --}}
        {{-- Sayfa @section('footer-action') verirse (ör. Kaydet): solda çıkış, ortada yazı, sağda o buton --}}
        @php($logout = '<form action="' . route('logout') . '" method="POST">' . csrf_field() . '<button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-red-600 px-4 py-2.5 font-semibold text-white shadow-sm transition hover:bg-red-700 focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-red-600/25"><svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 4h3a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-3M10 17l-5-5 5-5M5 12h11" /></svg>' . e(__('Çıkış yap')) . '</button></form>')
        <footer class="sticky bottom-0 z-20 border-t border-slate-200 bg-surface/95 px-4 py-3 backdrop-blur sm:px-8 lg:px-10">
            @hasSection('footer-action')
                <div class="grid grid-cols-[auto_1fr_auto] items-center gap-3 text-sm text-slate-500">
                    {!! $logout !!}
                    <div class="flex min-w-0 items-center justify-center gap-2.5">
                        <x-admin.logo class="hidden size-7 sm:block" />
                        <span class="hidden truncate sm:inline">© {{ date('Y') }} <span class="font-semibold text-slate-700">TripSpoiler</span> · {{ __('Yönetim paneli') }}</span>
                    </div>
                    <div class="flex items-center justify-end">@yield('footer-action')</div>
                </div>
            @else
                <div class="flex flex-wrap items-center justify-between gap-3 text-sm text-slate-500">
                    <div class="flex items-center gap-2.5">
                        <x-admin.logo class="size-7" />
                        <span>© {{ date('Y') }} <span class="font-semibold text-slate-700">TripSpoiler</span> · {{ __('Yönetim paneli') }}</span>
                    </div>
                    {!! $logout !!}
                </div>
            @endif
        </footer>
    </div>

    <script>
        // Geri/ileri ile dönülen sayfa tarayıcı hafızasındaki eski haliyle açılmasın (ör. kaydetmeden önceki boş form):
        // her zaman sunucudan güncel hali gelsin
        window.addEventListener('pageshow', (e) => {
            if (e.persisted || performance.getEntriesByType('navigation')[0]?.type === 'back_forward') location.reload();
        });
        // Geri: panelin içinden gelindiyse tarayıcı geçmişi, değilse adresin bir üst parçası
        document.querySelector('[data-back]')?.addEventListener('click', () => {
            const fromPanel = document.referrer && new URL(document.referrer).origin === location.origin && history.length > 1;
            if (fromPanel) return history.back();
            const parts = location.pathname.replace(/\/+$/, '').split('/');
            const last = parts.pop();
            // ".../aktiviteler/5/duzenle" → listeye (".../aktiviteler"), kaydın kendi sayfası yok
            if (last === 'duzenle' && /^\d+$/.test(parts.at(-1) ?? '')) parts.pop();
            location.href = parts.join('/') || '/';
        });
        const sidebar = document.getElementById('sidebar');
        const backdrop = document.getElementById('sidebar-backdrop');
        const setSidebar = (open) => {
            sidebar.classList.toggle('-translate-x-full', !open);
            backdrop.classList.toggle('hidden', !open);
        };
        document.getElementById('sidebar-open').addEventListener('click', () => setSidebar(true));
        backdrop.addEventListener('click', () => setSidebar(false));

        document.getElementById('sidebar-collapse').addEventListener('click', () => {
            const collapsed = document.documentElement.classList.toggle('sidebar-collapsed');
            try {
                localStorage.setItem('sidebar', collapsed ? 'collapsed' : 'expanded');
            } catch {}
        });
    </script>
    {{-- Başka formların içindeki butonlara bağlı ayrı formlar (ör. x-admin.retranslate) --}}
    <div data-live="forms-stack">@stack('forms')</div>
    @stack('scripts')

    {{-- Global AJAX form submit: sayfa yenilenmeden kaydet --}}
    <script>
        (() => {
            const toast = document.createElement('div');
            toast.className = 'hidden fixed right-4 top-4 z-[9999] max-w-[calc(100vw-2rem)] rounded-2xl px-5 py-3 text-sm font-semibold shadow-lg ring-1';
            document.body.appendChild(toast);
            let toastTimer;
            const showToast = (msg, ok = true) => {
                toast.textContent = msg;
                toast.className = 'fixed right-4 top-4 z-[9999] max-w-[calc(100vw-2rem)] rounded-2xl px-5 py-3 text-sm font-semibold shadow-lg ring-1 ' + (ok ? 'bg-emerald-50 text-emerald-800 ring-emerald-200' : 'bg-red-50 text-red-700 ring-red-200');
                clearTimeout(toastTimer);
                toastTimer = setTimeout(() => toast.classList.add('hidden'), 4000);
            };

            // Kayıt sırasında sayfayı kilitleyen katman: dönen simge, "Kaydediliyor…", resim yüklenirken yüzde.
            // Bu sürede hiçbir şeye tıklanamaz, klavyeyle de işlem yapılamaz (inert).
            let lock;
            const lockText = {
                saving: @json(__('Kaydediliyor…')),
                uploading: @json(__('Resimler yükleniyor')),
                processing: @json(__('Resimler işleniyor…')),
                leaving: @json(__('Açılıyor…')),
                deleting: @json(__('Siliniyor…')),
            };
            const setLock = (text, percent = null) => {
                if (!lock) {
                    lock = document.createElement('div');
                    lock.setAttribute('role', 'alertdialog');
                    lock.setAttribute('aria-busy', 'true');
                    lock.className = 'fixed inset-0 z-[9990] grid cursor-wait place-items-center bg-slate-900/25 opacity-0 backdrop-blur-[2px] transition-opacity duration-150';
                    lock.innerHTML = `
                        <div class="flex min-w-[15rem] items-center gap-3.5 rounded-2xl bg-white px-5 py-4 shadow-2xl ring-1 ring-slate-200">
                            <svg class="size-6 shrink-0 animate-spin text-brand-900" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-20"/><path d="M22 12a10 10 0 0 0-10-10" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
                            <div class="min-w-0 flex-1">
                                <p data-lock-text class="text-sm font-semibold text-slate-900"></p>
                                <div data-lock-bar class="mt-2 hidden h-1.5 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-brand-900 transition-[width] duration-200" style="width:0"></div></div>
                            </div>
                        </div>`;
                    // Arkadaki her şey tıklanamaz ve klavyeyle erişilemez
                    [...document.body.children].forEach((el) => { if (el !== lock && el.tagName !== 'SCRIPT') { el.dataset.wasInert = el.inert ? '1' : ''; el.inert = true; } });
                    document.activeElement?.blur?.();
                    document.body.appendChild(lock);
                    requestAnimationFrame(() => lock.classList.replace('opacity-0', 'opacity-100'));
                }
                lock.querySelector('[data-lock-text]').textContent = percent === null ? text : `${text} %${percent}`;
                const bar = lock.querySelector('[data-lock-bar]');
                bar.classList.toggle('hidden', percent === null);
                if (percent !== null) bar.firstElementChild.style.width = percent + '%';
            };
            const unlock = () => {
                if (!lock) return;
                [...document.body.children].forEach((el) => { if ('wasInert' in el.dataset) { el.inert = el.dataset.wasInert === '1'; delete el.dataset.wasInert; } });
                lock.remove();
                lock = null;
            };

            // XMLHttpRequest: fetch'ten farklı olarak yükleme ilerlemesini verir (resimler için yüzde)
            const send = (url, body, onUpload) => new Promise((resolve, reject) => {
                const xhr = new XMLHttpRequest();
                xhr.open('POST', url);
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                xhr.setRequestHeader('Accept', 'application/json');
                xhr.withCredentials = true;
                xhr.upload.onprogress = (ev) => ev.lengthComputable && onUpload(Math.round(ev.loaded / ev.total * 100));
                xhr.onload = () => {
                    let data = {};
                    try { data = JSON.parse(xhr.responseText); } catch {}
                    resolve({ ok: xhr.status >= 200 && xhr.status < 300, status: xhr.status, data });
                };
                xhr.onerror = () => reject(new Error('network'));
                xhr.send(body);
            });

            document.addEventListener('submit', async (e) => {
                const form = e.target;
                if (!(form instanceof HTMLFormElement)) return;
                if (form.dataset.noAjax !== undefined) return;
                // Aktif/pasif anahtarları ve yıldızlar kendi işini yapar (resources/js/toggle.js); burada ikinci kez gönderilirse
                // kayıt iki kez değişir (yıldız geri döner) ve sayfa yenilenip başa kayar
                if (form.matches('[data-toggle]')) return;
                const action = form.action || '';
                // Yerel/dil değiştirme ve çıkış formları normal çalışsın
                if (action.includes('/dil') || action.includes('/logout') || action.includes('/cevir')) return;
                // Sadece sunucuya giden POST formları (PUT/PATCH/DELETE de _method ile POST'tur).
                // GET ve onay penceresinin <form method="dialog">'u karışmasın: yoksa pencere kapanmaz, sayfaya POST gider (405)
                const method = (form.getAttribute('method') || 'GET').toUpperCase();
                if (method !== 'POST') return;
                const isDelete = method === 'DELETE' || form.querySelector('input[name="_method"][value="DELETE"]');
                // Zaten işleniyorsa tekrar çalıştırma
                if (form.dataset.ajaxBusy) return;
                // Önce onay penceresi (confirm-dialog.js) ve resim küçültme (image-compress.js) bitsin; onlar formu yeniden gönderir
                if (form.matches('[data-confirm]') && !form.dataset.confirmed) return;
                if (form.hasAttribute('data-compressing')) return;
                // Güncelleme (PUT/PATCH) ya da aynı sayfaya dönen işlem: sayfada kal, yerinde yenile
                const spoofed = form.querySelector('input[name="_method"]')?.value?.toUpperCase();

                e.preventDefault();

                // Seçilen dosyaların toplamı sunucu sınırını (post_max_size) aşarsa sunucu formu tamamen atar: baştan uyar
                const postMax = {{ \App\Support\UploadLimit::requestBytes() }};
                const fileBytes = [...form.querySelectorAll('input[type=file]')].flatMap(i => [...i.files]).reduce((sum, f) => sum + f.size, 0);
                if (fileBytes > postMax * 0.95) {
                    showToast(@json(__('Seçilen resimler birlikte çok büyük (sunucu sınırı :max MB). Daha küçük resim seç ya da resimleri ayrı ayrı kaydet.', ['max' => round(\App\Support\UploadLimit::requestBytes() / 1024 ** 2, 1)])), false);
                    return;
                }

                form.dataset.ajaxBusy = '1';
                // Formun içindeki ve dışından form="…" ile bağlı (ör. alttaki "Değişiklikleri kaydet") bütün butonlar pasif
                const buttons = [...form.querySelectorAll('button[type="submit"], button:not([type]), input[type="submit"]'),
                    ...(form.id ? document.querySelectorAll(`button[form="${CSS.escape(form.id)}"]`) : [])];
                buttons.forEach((b) => { b.disabled = true; });
                const hasFiles = fileBytes > 0;
                setLock(isDelete ? lockText.deleting : lockText.saving);
                let leaving = false;

                try {
                    const res = await send(form.action, new FormData(form, e.submitter?.form === form ? e.submitter : undefined), (percent) => {
                        if (!hasFiles) return;
                        percent < 100 ? setLock(lockText.uploading, percent) : setLock(lockText.processing);
                    });
                    const data = res.data;

                    if (res.ok && (data.success || data.redirect)) {
                        if (data.success) showToast(data.success);
                        const target = data.redirect ? new URL(data.redirect, location.href) : null;
                        const samePage = !target || target.pathname === location.pathname;
                        // Sayfa değişmez: güncellemede (sunucu listeye yönlendirse de) ya da aynı sayfaya dönen işlemde.
                        // Yeni kayıt oluşturma / silinen kaydın kendi sayfası: yeni adrese gidilir.
                        const stay = samePage || (spoofed === 'PUT' || spoofed === 'PATCH');
                        if (stay) {
                            if (isDelete) {
                                // Silinen satır listeden kalksın: sayfanın içeriğini yerinde yenile
                                await window.removeRow?.(form);
                            } else {
                                await window.liveRefresh?.(form);
                            }
                        } else {
                            leaving = true; // yeni sayfa açılana kadar kilit kalsın
                            setLock(lockText.leaving);
                            setTimeout(() => { window.location.href = target.href; }, data.success ? 500 : 0);
                        }
                    } else if (res.status === 422 && data.errors) {
                        const msgs = Object.values(data.errors).flat();
                        showToast(msgs.join(' '), false);
                        // Hata kutusu varsa güncelle
                        const errBox = form.querySelector('[data-form-errors]');
                        if (errBox) {
                            errBox.innerHTML = '<p class="font-semibold">' + @json(__('Kaydedilemedi. Lütfen hataları düzelt:')) + '</p><ul class="mt-2 list-disc space-y-1 pl-5">' + msgs.map(m => '<li>' + m + '</li>').join('') + '</ul>';
                            errBox.classList.remove('hidden');
                        }
                    } else if (data.error) {
                        showToast(data.error, false);
                    } else {
                        // Beklenmeyen yanıt: normal submit'e düş
                        leaving = true;
                        delete form.dataset.ajaxBusy;
                        form.submit();
                        return;
                    }
                } catch (err) {
                    // Ağ hatası: normal submit'e düş
                    leaving = true;
                    delete form.dataset.ajaxBusy;
                    form.submit();
                    return;
                } finally {
                    if (!leaving) {
                        buttons.forEach((b) => { b.disabled = false; });
                        unlock();
                    }
                    delete form.dataset.ajaxBusy;
                }
            }, true);
        })();
    </script>
</body>

</html>
