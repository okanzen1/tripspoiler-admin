@extends('layouts.admin')

@section('title', 'Bölümler')

@section('header')
    <x-admin.page-header :title="__('Bölümler')" :breadcrumb="['Yönetim' => route('management.index')]"
        :description="__('Anasayfada görünen bölümler: başlıkları, içerikleri ve kartları. Düzenlemek istediğin bölüme tıkla.')">
        <x-slot:icon><rect x="3" y="4" width="18" height="16" rx="2" /><path d="M3 9h18M8 4v5" /></x-slot:icon>
    </x-admin.page-header>
@endsection

@section('content')
    <x-admin.todo-list :under="[route('site-settings.index'), route('section-texts.index'), route('steps.index'), route('features.index'), route('blog-section.index')]" />
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <div class="relative w-full max-w-md">
            <svg class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7" /><path d="M20 20l-3.5-3.5" /></svg>
            <input type="search" data-card-search="site_settings_order" placeholder="{{ __('Bölümlerde ara… (ör. blog, yorumlar, duyuru)') }}" autocomplete="off"
                class="block w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-10 pr-3.5 text-sm outline-none transition placeholder:text-slate-400 focus:border-brand-900 focus:ring-4 focus:ring-brand-900/10">
        </div>
        <p class="inline-flex items-center gap-1.5 text-xs text-slate-500">
            <svg class="size-4 text-slate-400" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="9" cy="6" r="1.5"/><circle cx="15" cy="6" r="1.5"/><circle cx="9" cy="12" r="1.5"/><circle cx="15" cy="12" r="1.5"/><circle cx="9" cy="18" r="1.5"/><circle cx="15" cy="18" r="1.5"/></svg>
            {{ __('Soldaki tutamaçtan sürükle: anasayfadaki bölümler de bu sırayla dizilir. Duyuru şeridi en üstte, partner logoları en altta sabit.') }}
            <span class="hidden font-semibold text-emerald-700" data-order-saved="site_settings_order">✓ {{ __('Sıra kaydedildi') }}</span>
        </p>
    </div>

    @php
        // Satırlar: anahtar => [kart kimliği, bağlantı, ad, açıklama, anasayfadaki başlık, durum, durum iyi mi, ikon rengi, ikon]
        $translator = app(\App\Services\Translation\ModelTranslator::class);
        $rows = [
            'steps' => ['steps', route('steps.index'), 'Nasıl çalışır', 'Numaralı adımlar (01, 02, 03): başlık, adım yazıları, ikonlar.',
                $steps->pluck('title')->implode(' · '),
                $steps->count() ? __('✓ :count adım yayında', ['count' => $steps->count()]) : __('Yayında adım yok, bölüm gizli'), $steps->count() > 0,
                'bg-rose-500', '<path d="M4 6h2M4 12h2M4 18h2M9 6h11M9 12h11M9 18h11" />'],
        ];
        foreach (\App\Http\Controllers\SectionTextController::SECTIONS as $sectionKey => [$sectionName, $sectionHint]) {
            if ($sectionKey === 'popular') continue; // aşağıdaki "Popüler aktiviteler" kartı bu sayfayı açar
            $sectionModel = \App\Models\SectionText::for($sectionKey);
            $pending = count(array_filter($translator->status($sectionModel), fn ($state) => $state !== 'ok'));
            $rows[$sectionKey] = [str_replace('_', '-', $sectionKey) . '-texts', route('section-texts.edit', $sectionKey), $sectionName, $sectionHint,
                trim($sectionModel->getTranslation('title', 'en', false) . ' ' . $sectionModel->getTranslation('title_highlight', 'en', false)),
                $pending ? __(':count dil çeviri bekliyor', ['count' => $pending]) : __('✓ Tüm diller güncel'), !$pending,
                'bg-violet-500', '<path d="M4 7V5h16v2M9 19h6M12 5v14" />'];
        }
        $rows['blog'] = ['blog', route('blog-section.index'), 'Blog bölümü', 'Anasayfadaki blog alanı: başlık, öne çıkan yazılar ve bülten kutusu.',
            $homeBlogs->pluck('title')->implode(' · '),
            $homeBlogs->count() ? __('✓ :count yazı seçili', ['count' => $homeBlogs->count()]) : __('Otomatik: en yeni yazılar'), true,
            'bg-sky-500', '<path d="M4 5a2 2 0 0 1 2-2h12v16H6a2 2 0 0 0-2 2zM8 7h6M8 11h4" />'];
        $rows['features'] = ['features', route('features.index'), 'Özellik kartları', 'Telefonlu bölümün altında yan yana kayan küçük kartlar.',
            $features->pluck('title')->implode(' · '),
            $featureCount ? __('✓ :count kart yayında', ['count' => $featureCount]) : __('Yayında kart yok, bölüm gizli'), $featureCount > 0,
            'bg-amber-500', '<rect x="3" y="5" width="7" height="14" rx="1.5" /><rect x="14" y="5" width="7" height="14" rx="1.5" />'];
        $rows['home_popular'] = ['popular', route('section-texts.edit', 'popular'), 'Popüler aktiviteler', 'Anasayfadaki aktivite kartları: “Popüler” işaretli aktiviteler (aktivite formunda).',
            '',
            $popularCount ? __('✓ :count popüler aktivite', ['count' => $popularCount]) : __('Popüler işaretli aktivite yok, bölüm gizli'), $popularCount > 0,
            'bg-red-500', '<path d="M4 8a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v1.5a2.5 2.5 0 0 0 0 5V16a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-1.5a2.5 2.5 0 0 0 0-5z" />'];
        $rows['home_faq'] = ['faq', route('faqs.texts'), 'Sıkça sorulan sorular', 'Başlık, “Aklında başka bir soru mu var?” kutusu ve anasayfa soruları.',
            $homeFaqs->pluck('question')->implode(' · '),
            $homeFaqs->count() ? __('✓ :count soru yayında', ['count' => $homeFaqs->count()]) : __('Anasayfa sorusu yok, bölüm gizli'), $homeFaqs->count() > 0,
            'bg-teal-500', '<circle cx="12" cy="12" r="9" /><path d="M9.5 9a2.5 2.5 0 1 1 3.5 2.3c-.6.3-1 .9-1 1.6V14M12 17h.01" />'];
        $rows['announcements'] = ['announcements', route('announcements.index'), 'Duyuru şeridi', 'Sitenin en üstünde kayan kısa yazılar (kampanya, yeni rehber…).',
            '',
            $announcementCount ? __('✓ :count duyuru yayında', ['count' => $announcementCount]) : __('Yayında duyuru yok, şerit gizli'), $announcementCount > 0,
            'bg-emerald-500', '<path d="M3 11v2a1 1 0 0 0 1 1h2l5 4V6L6 10H4a1 1 0 0 0-1 1zM16 9a4 4 0 0 1 0 6" />'];
        $rateCurrencies = \App\Models\Country::active()->whereHas('cities', fn ($q) => $q->where('active', true))->whereNotNull('currency')->distinct()->pluck('currency');
        $rows['rates'] = ['rates', route('locations.index'), 'Döviz kurları', 'Duyuru şeridinin altında: ziyaretçinin parası ile sitedeki ülkelerin parası (günde bir güncellenir, ücretsiz).',
            '',
            $rateCurrencies->count() ? __('✓ :list', ['list' => $rateCurrencies->implode(', ')]) : __('Aktif şehri olan ülke yok'), $rateCurrencies->count() > 0,
            'bg-lime-600', '<circle cx="12" cy="12" r="9" /><path d="M15 9.5c-.5-1-1.6-1.5-3-1.5-1.7 0-3 .9-3 2s1.3 1.7 3 2 3 .9 3 2-1.3 2-3 2c-1.4 0-2.5-.6-3-1.5M12 6v2M12 16v2" />'];
        // Hava durumu ve iklim: şehre özel bölümler; hangi sayfada görüneceği Sayfalar › sayfa › Bölümler'den
        $weatherPages = \App\Models\Page::whereIn('key', array_keys(\App\Support\PageSections::DEFAULTS))->get()
            ->filter(fn ($page) => in_array('weather', \App\Support\PageSections::config($page->key)['on'], true))->pluck('name');
        $citiesPage = \App\Models\Page::where('key', 'cities')->first();
        $timePages = \App\Models\Page::whereIn('key', array_keys(\App\Support\PageSections::DEFAULTS))->get()
            ->filter(fn ($page) => in_array('time', \App\Support\PageSections::config($page->key)['on'], true))->pluck('name');
        $rows['time'] = ['time', $citiesPage ? route('pages.sections', $citiesPage) : route('pages.index'), 'Yerel saat', 'Şehrin canlı saati, ziyaretçiyle saat farkı, gün doğumu/batımı ve aramak için uygun saatler. Dış servis yok, ücretsiz.',
            '',
            $timePages->isNotEmpty() ? __('✓ :pages', ['pages' => $timePages->implode(', ')]) : __('Hiçbir sayfada açık değil'), $timePages->isNotEmpty(),
            'bg-indigo-500', '<circle cx="12" cy="12" r="9" /><path d="M12 7v5l3 2" />'];
        $rows['weather'] = ['weather', $citiesPage ? route('pages.sections', $citiesPage) : route('pages.index'), 'Hava durumu', 'Şehrin bugünkü havası, 7 günlük tahmin, ne giyilir ve havaya göre aktiviteler. Ücretsiz, saatte bir güncellenir.',
            '',
            $weatherPages->isNotEmpty() ? __('✓ :pages', ['pages' => $weatherPages->implode(', ')]) : __('Hiçbir sayfada açık değil'), $weatherPages->isNotEmpty(),
            'bg-sky-500', '<path d="M7 18a4.5 4.5 0 1 1 .9-8.9A6 6 0 0 1 19 11a3.5 3.5 0 0 1-.5 7z" />'];
    @endphp

    <x-admin.card class="overflow-hidden">
        <ul class="divide-y divide-slate-100" data-sortable-grid data-sortable-list data-order-key="site_settings_order"
            data-order-url="{{ route('cards.order') }}" data-initial-order="{{ \App\Models\Setting::get('site_settings_order') }}">
            @foreach ($rows as $section => [$cardKey, $url, $name, $hint, $preview, $status, $good, $color, $icon])
                @php $on = \App\Support\SiteSections::enabled($section); @endphp
                <li class="group relative flex items-center gap-3 bg-surface px-3 py-3.5 transition hover:bg-white sm:gap-4 sm:px-4" data-card="{{ $cardKey }}">
                    {{-- sürükleme tutamacı --}}
                    <span data-drag-handle draggable="true" title="{{ __('Sürükleyerek sırala') }}" aria-label="{{ __('Sürükleyerek sırala') }}"
                        class="grid h-9 w-6 shrink-0 cursor-grab place-items-center rounded-lg text-slate-300 transition hover:bg-slate-100 hover:text-slate-600 active:cursor-grabbing">
                        <svg class="size-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="9" cy="6" r="1.5"/><circle cx="15" cy="6" r="1.5"/><circle cx="9" cy="12" r="1.5"/><circle cx="15" cy="12" r="1.5"/><circle cx="9" cy="18" r="1.5"/><circle cx="15" cy="18" r="1.5"/></svg>
                    </span>

                    {{-- bölüm: ad, açıklama, anasayfadaki başlığı --}}
                    <a href="{{ $url }}" @class(['flex min-w-0 flex-1 items-center gap-3.5 transition focus-visible:outline-none', 'opacity-50 grayscale' => !$on]) data-card-link>
                        {{-- küçük önizleme: bölüm anasayfada nasıl görünüyor --}}
                        <span class="relative hidden aspect-[16/8] w-36 shrink-0 overflow-hidden rounded-xl bg-slate-100 ring-1 ring-slate-200/70 sm:block">
                            @switch($section)
                                @case('steps')
                                    <span class="absolute inset-0 flex items-center justify-center gap-1.5 px-2">
                                        <span class="absolute left-[16%] right-[16%] top-1/2 border-t border-dashed border-slate-300"></span>
                                        @foreach ($steps->take(3) as $step)
                                            <span class="relative grid w-9 place-items-center rounded-lg bg-white py-1.5 shadow-sm ring-1 ring-slate-200/70">
                                                <span class="grid size-4 place-items-center rounded-full bg-gradient-to-br from-rose-500 to-red-700 text-[6px] font-extrabold text-white">{{ sprintf('%02d', $loop->iteration) }}</span>
                                            </span>
                                        @endforeach
                                    </span>
                                    @break
                                @case('blog')
                                    <span class="absolute inset-0 grid grid-cols-5 gap-1 p-2">
                                        <span class="col-span-3 rounded-md bg-slate-300"></span>
                                        <span class="col-span-2 grid gap-1"><span class="rounded bg-white ring-1 ring-slate-200"></span><span class="rounded bg-white ring-1 ring-slate-200"></span><span class="rounded bg-slate-800"></span></span>
                                    </span>
                                    @break
                                @case('features')
                                    <span class="absolute inset-0 flex items-center gap-1.5 overflow-hidden px-2">
                                        @foreach ($features->take(3) as $feature)
                                            <span class="w-14 shrink-0 rounded-lg bg-white p-1.5 shadow-sm ring-1 ring-slate-200/70">
                                                <span class="grid size-4 place-items-center rounded-md bg-gradient-to-br from-rose-500 to-red-700 text-white">{!! $feature->iconHtml('size-2.5') !!}</span>
                                                <span class="mt-1 block truncate text-[7px] font-bold text-slate-900">{{ $feature->title }}</span>
                                            </span>
                                        @endforeach
                                    </span>
                                    @break
                                @case('home_popular')
                                    <span class="absolute inset-0 flex items-center gap-1.5 overflow-hidden px-2">
                                        @foreach (range(1, 3) as $n)
                                            <span class="h-14 w-12 shrink-0 overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-slate-200/70"><span class="block h-7 bg-slate-300"></span><span class="mx-1 mt-1.5 block h-1 rounded bg-slate-300"></span><span class="mx-1 mt-1 block h-1 w-6 rounded bg-red-400"></span></span>
                                        @endforeach
                                    </span>
                                    @break
                                @case('home_faq')
                                    <span class="absolute inset-0 flex flex-col justify-center gap-1 px-2.5">
                                        @foreach ($homeFaqs->take(3) as $faq)
                                            <span class="flex items-center justify-between gap-1 rounded-md bg-white px-1.5 py-1 shadow-sm ring-1 ring-slate-200/70"><span class="truncate text-[7px] font-semibold text-slate-800">{{ $faq->question }}</span><span class="text-[8px] text-slate-400">+</span></span>
                                        @endforeach
                                    </span>
                                    @break
                                @case('announcements')
                                    <span class="absolute inset-x-0 top-3 flex h-4 items-center gap-3 overflow-hidden whitespace-nowrap bg-slate-900 px-2 text-[7px] font-semibold text-white/90">
                                        <span>● {{ __('Kayan yazı') }}</span><span>● {{ __('Kayan yazı') }}</span><span>● {{ __('Kayan yazı') }}</span>
                                    </span>
                                    <span class="absolute inset-x-0 top-7 h-4 border-b border-slate-200 bg-white"></span>
                                    @break
                                @case('time')
                                    <span class="absolute inset-0 flex items-center justify-center gap-2 bg-slate-950 px-2">
                                        <span class="font-mono text-lg font-extrabold tracking-tight text-white">14:32<span class="text-[10px] text-white/50">:08</span></span>
                                        <span class="rounded-md bg-amber-400 px-1.5 py-0.5 text-[8px] font-extrabold text-slate-950">+2 sa</span>
                                    </span>
                                    @break
                                @case('weather')
                                    <span class="absolute inset-0 flex items-center gap-1.5 bg-gradient-to-br from-sky-500 to-indigo-600 px-2">
                                        <span class="rounded-md bg-white/20 px-1.5 py-1 text-[11px] font-extrabold text-white">17°</span>
                                        @foreach (['☀️', '⛅', '🌧️', '☁️'] as $emoji)
                                            <span class="grid w-6 place-items-center rounded-md bg-white py-1 text-[9px] leading-none">{{ $emoji }}</span>
                                        @endforeach
                                    </span>
                                    @break
                                @case('rates')
                                    <span class="absolute inset-x-0 top-3 h-3 bg-slate-900"></span>
                                    <span class="absolute inset-x-0 top-6 flex h-4 items-center gap-2 overflow-hidden whitespace-nowrap border-b border-slate-200 bg-slate-50 px-2 text-[7px] font-semibold text-slate-700">
                                        <span>1 € = 38,5 ₺</span><span>1 $ = 34,1 ₺</span><span>1 £ = 44,9 ₺</span>
                                    </span>
                                    @break
                                @default
                                    @php $sectionModel = \App\Models\SectionText::for($section); @endphp
                                    <span class="absolute inset-0 flex flex-col justify-center px-2.5">
                                        <span class="truncate text-[6px] font-bold uppercase tracking-[0.18em] text-red-600">{{ $sectionModel->getTranslation('eyebrow', 'en', false) }}</span>
                                        <span class="mt-0.5 line-clamp-2 text-[10px] font-extrabold leading-tight text-slate-900">
                                            {{ $sectionModel->getTranslation('title', 'en', false) }} <span class="text-red-600">{{ $sectionModel->getTranslation('title_highlight', 'en', false) }}</span>
                                        </span>
                                    </span>
                            @endswitch
                        </span>
                        {{-- telefonda önizleme yerine renkli ikon --}}
                        <span class="grid size-10 shrink-0 place-items-center rounded-xl {{ $color }} text-white shadow-sm sm:hidden">
                            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $icon !!}</svg>
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="flex flex-wrap items-baseline gap-x-2">
                                <span class="font-semibold text-slate-900 group-hover:text-brand-900">{{ __($name) }}</span>
                                @if ($section !== 'home_faq' && ($todo = \App\Support\TranslationTodo::under($url)))
                                    <span class="inline-flex items-center gap-1 rounded-full bg-purple-50 px-2 py-0.5 text-[11px] font-semibold text-purple-700 ring-1 ring-purple-200"><svg class="size-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 8 6 6M4 14l6-6 2-3M2 5h12M22 22l-5-10-5 10M14 18h6" /></svg>{{ __(':count çeviri eksik', ['count' => $todo]) }}</span>
                                @endif
                                @if ($preview && in_array($section, ['steps', 'blog', 'features', 'home_faq'], true))
                                    <span class="truncate text-xs text-slate-400">“{{ \Illuminate\Support\Str::limit($preview, 70) }}”</span>
                                @endif
                            </span>
                            <span class="mt-0.5 block truncate text-sm text-slate-500">{{ __($hint) }}</span>
                        </span>
                        <span class="hidden shrink-0 text-xs font-medium md:block {{ $good ? 'text-emerald-700' : 'text-amber-700' }}">{{ $status }}</span>
                    </a>

                    {{-- aç / kapa --}}
                    <x-admin.section-toggle :section="$section" class="shrink-0 !py-1 !pl-3 !pr-1 [&_[data-section-state]]:hidden [&_[data-section-state]]:text-xs sm:[&_[data-section-state]]:inline" />

                    <a href="{{ $url }}" class="hidden shrink-0 text-sm font-semibold text-brand-900 transition group-hover:translate-x-0.5 sm:inline" tabindex="-1">{{ __('Düzenle') }} →</a>
                </li>
            @endforeach
        </ul>
    </x-admin.card>
    <p class="hidden py-12 text-center text-sm text-slate-500" data-card-empty="site_settings_order">{{ __('Aramana uyan bölüm yok.') }}</p>
@endsection

@push('scripts')
    <script>
        // Anahtar değişince kartı soluklaştır / yazıyı güncelle
        document.addEventListener('toggled', (e) => {
            const card = e.target.closest('[data-card]');
            if (!card) return;
            card.querySelector('[data-card-link]').classList.toggle('opacity-50', !e.detail.active);
            card.querySelector('[data-card-link]').classList.toggle('grayscale', !e.detail.active);
        });
    </script>
@endpush
