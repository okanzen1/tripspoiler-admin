@extends('layouts.admin')

@section('title', 'Bölümler')

@section('header')
    <x-admin.page-header :title="__('Bölümler')" :breadcrumb="['Yönetim' => route('management.index')]"
        :description="__('Anasayfada görünen bölümler: başlıkları, içerikleri ve kartları. Düzenlemek istediğin bölüme tıkla.')">
        <x-slot:icon><rect x="3" y="4" width="18" height="16" rx="2" /><path d="M3 9h18M8 4v5" /></x-slot:icon>
    </x-admin.page-header>
@endsection

@section('content')
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <div class="relative w-full max-w-md">
            <svg class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7" /><path d="M20 20l-3.5-3.5" /></svg>
            <input type="search" data-card-search="site_settings_order" placeholder="{{ __('Bölümlerde ara… (ör. blog, yorumlar, duyuru)') }}" autocomplete="off"
                class="block w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-10 pr-3.5 text-sm outline-none transition placeholder:text-slate-400 focus:border-brand-900 focus:ring-4 focus:ring-brand-900/10">
        </div>
        <p class="inline-flex items-center gap-1.5 text-xs text-slate-500">
            <svg class="size-4 text-slate-400" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="9" cy="6" r="1.5"/><circle cx="15" cy="6" r="1.5"/><circle cx="9" cy="12" r="1.5"/><circle cx="15" cy="12" r="1.5"/><circle cx="9" cy="18" r="1.5"/><circle cx="15" cy="18" r="1.5"/></svg>
            {{ __('Sırayı değiştirmek için kartı sol üstteki tutamaçtan tutup sürükle.') }}
            <span class="hidden font-semibold text-emerald-700" data-order-saved="site_settings_order">✓ {{ __('Sıra kaydedildi') }}</span>
        </p>
    </div>

    <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3" data-sortable-grid data-order-key="site_settings_order"
        data-order-url="{{ route('cards.order') }}" data-initial-order="{{ \App\Models\Setting::get('site_settings_order') }}">


        {{-- NASIL ÇALIŞIR --}}
        @php $cardSection = 'steps'; $cardOn = \App\Support\SiteSections::enabled($cardSection); @endphp
        <div class="group relative" data-card="steps">
            {{-- aç / kapa: kartın sağ üstünde (bağlantının dışında) --}}
            <x-admin.section-toggle :section="$cardSection" class="absolute right-3 top-3 z-20 !py-1 !pl-3 !pr-1 [&_[data-section-state]]:text-xs" />
            <a href="{{ route('steps.index') }}" @class(['block h-full transition focus-visible:outline-none', 'opacity-50 grayscale' => !$cardOn]) data-card-link>
            <x-admin.card class="h-full overflow-hidden transition group-hover:-translate-y-1 group-hover:shadow-xl group-focus-visible:ring-2 group-focus-visible:ring-brand-500">
                {{-- sürükleme tutamacı --}}
                <span data-drag-handle draggable="true" title="{{ __('Sürükleyerek sırala') }}" aria-label="{{ __('Sürükleyerek sırala') }}"
                    class="absolute left-3 top-3 z-10 grid h-9 w-7 cursor-grab place-items-center rounded-lg bg-white/90 text-slate-400 shadow-sm ring-1 ring-slate-200 backdrop-blur transition hover:text-slate-700 active:cursor-grabbing">
                    <svg class="size-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="9" cy="6" r="1.5"/><circle cx="15" cy="6" r="1.5"/><circle cx="9" cy="12" r="1.5"/><circle cx="15" cy="12" r="1.5"/><circle cx="9" cy="18" r="1.5"/><circle cx="15" cy="18" r="1.5"/></svg>
                </span>
                <div class="relative flex aspect-[16/8] items-center justify-center gap-4 overflow-hidden bg-slate-100 px-5">
                    <div class="absolute left-[18%] right-[18%] top-1/2 border-t-2 border-dashed border-slate-300"></div>
                    @foreach ($steps as $step)
                        <div class="relative w-24 rounded-2xl bg-white p-3 text-center shadow-sm ring-1 ring-slate-200/70">
                            <span class="mx-auto grid size-7 place-items-center rounded-full bg-gradient-to-br from-rose-500 to-red-700 text-[10px] font-extrabold text-white">{{ sprintf('%02d', $loop->iteration) }}</span>
                            <p class="mt-1.5 truncate text-[10px] font-bold text-slate-900">{{ $step->title }}</p>
                        </div>
                    @endforeach
                </div>
                <div class="p-6">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-lg font-semibold text-slate-900">{{ __('Nasıl çalışır') }}</p>
                        <span class="text-sm font-semibold text-brand-900 transition group-hover:translate-x-1">{{ __('Düzenle') }} →</span>
                    </div>
                    <p class="mt-1.5 text-sm leading-relaxed text-slate-600">{{ __('Anasayfadaki numaralı adımlar (01, 02, 03): bölümün başlığı, her adımın yazısı, ikonu ve bağlantısı.') }}</p>
                    <p class="mt-3 text-xs font-medium {{ $steps->count() ? 'text-emerald-700' : 'text-slate-500' }}">
                        {{ $steps->count() ? __('✓ :count adım yayında', ['count' => $steps->count()]) : __('Yayında adım yok, bölüm gizli') }}
                    </p>
                </div>
            </x-admin.card>
        </a>
        </div>

        {{-- BÖLÜM YAZILARI: her bölüm ayrı kart (TripSpoiler nedir, Sosyal medya, Yorumlar) --}}
        @foreach (\App\Http\Controllers\SectionTextController::SECTIONS as $sectionKey => [$sectionName, $sectionHint])
            @php
                $sectionModel = \App\Models\SectionText::for($sectionKey);
                $sectionPending = count(array_filter(app(\App\Services\Translation\ModelTranslator::class)->status($sectionModel), fn ($s) => $s !== 'ok'));
            @endphp
            @php $cardSection = $sectionKey; $cardOn = \App\Support\SiteSections::enabled($cardSection); @endphp
            <div class="group relative" data-card="{{ str_replace('_', '-', $sectionKey) }}-texts">
                {{-- aç / kapa: kartın sağ üstünde (bağlantının dışında) --}}
                <x-admin.section-toggle :section="$cardSection" class="absolute right-3 top-3 z-20 !py-1 !pl-3 !pr-1 [&_[data-section-state]]:text-xs" />
                <a href="{{ route('section-texts.edit', $sectionKey) }}" @class(['block h-full transition focus-visible:outline-none', 'opacity-50 grayscale' => !$cardOn]) data-card-link>
                <x-admin.card class="flex h-full flex-col overflow-hidden transition group-hover:-translate-y-1 group-hover:shadow-xl group-focus-visible:ring-2 group-focus-visible:ring-brand-500">
                    <span data-drag-handle draggable="true" title="{{ __('Sürükleyerek sırala') }}" aria-label="{{ __('Sürükleyerek sırala') }}"
                        class="absolute left-3 top-3 z-10 grid h-9 w-7 cursor-grab place-items-center rounded-lg bg-white/90 text-slate-400 shadow-sm ring-1 ring-slate-200 backdrop-blur transition hover:text-slate-700 active:cursor-grabbing">
                        <svg class="size-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="9" cy="6" r="1.5"/><circle cx="15" cy="6" r="1.5"/><circle cx="9" cy="12" r="1.5"/><circle cx="15" cy="12" r="1.5"/><circle cx="9" cy="18" r="1.5"/><circle cx="15" cy="18" r="1.5"/></svg>
                    </span>
                    {{-- bölümün şu anki başlığı --}}
                    <div class="flex aspect-[16/8] flex-col justify-center bg-slate-100 px-8 pl-12">
                        <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-red-600">{{ $sectionModel->getTranslation('eyebrow', 'en', false) }}</p>
                        <p class="mt-2 text-xl font-extrabold leading-tight text-slate-900">
                            {{ $sectionModel->getTranslation('title', 'en', false) }} <span class="text-red-600">{{ $sectionModel->getTranslation('title_highlight', 'en', false) }}</span>
                        </p>
                        <p class="mt-2 line-clamp-2 text-xs text-slate-500">{{ $sectionModel->getTranslation('description', 'en', false) }}</p>
                    </div>
                    <div class="flex flex-1 flex-col p-6">
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-lg font-semibold text-slate-900">{{ __($sectionName) }}</p>
                            <span class="text-sm font-semibold text-brand-900 transition group-hover:translate-x-1">{{ __('Düzenle') }} →</span>
                        </div>
                        <p class="mt-1.5 flex-1 text-sm leading-relaxed text-slate-600">{{ __($sectionHint) }}</p>
                        <p class="mt-3 text-xs font-medium {{ $sectionPending ? 'text-amber-700' : 'text-emerald-700' }}">
                            {{ $sectionPending ? __(':count dil çeviri bekliyor', ['count' => $sectionPending]) : __('✓ Tüm diller güncel') }}
                        </p>
                    </div>
                </x-admin.card>
            </a>
            </div>
        @endforeach

        {{-- BLOG BÖLÜMÜ --}}
        @php $cardSection = 'blog'; $cardOn = \App\Support\SiteSections::enabled($cardSection); @endphp
        <div class="group relative" data-card="blog">
            {{-- aç / kapa: kartın sağ üstünde (bağlantının dışında) --}}
            <x-admin.section-toggle :section="$cardSection" class="absolute right-3 top-3 z-20 !py-1 !pl-3 !pr-1 [&_[data-section-state]]:text-xs" />
            <a href="{{ route('blog-section.index') }}" @class(['block h-full transition focus-visible:outline-none', 'opacity-50 grayscale' => !$cardOn]) data-card-link>
            <x-admin.card class="h-full overflow-hidden transition group-hover:-translate-y-1 group-hover:shadow-xl group-focus-visible:ring-2 group-focus-visible:ring-brand-500">
                {{-- sürükleme tutamacı --}}
                <span data-drag-handle draggable="true" title="{{ __('Sürükleyerek sırala') }}" aria-label="{{ __('Sürükleyerek sırala') }}"
                    class="absolute left-3 top-3 z-10 grid h-9 w-7 cursor-grab place-items-center rounded-lg bg-white/90 text-slate-400 shadow-sm ring-1 ring-slate-200 backdrop-blur transition hover:text-slate-700 active:cursor-grabbing">
                    <svg class="size-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="9" cy="6" r="1.5"/><circle cx="15" cy="6" r="1.5"/><circle cx="9" cy="12" r="1.5"/><circle cx="15" cy="12" r="1.5"/><circle cx="9" cy="18" r="1.5"/><circle cx="15" cy="18" r="1.5"/></svg>
                </span>
                <div class="relative grid aspect-[16/8] grid-cols-5 gap-2 overflow-hidden bg-slate-100 p-4">
                    <div class="col-span-3 rounded-xl bg-slate-300"></div>
                    <div class="col-span-2 grid gap-2"><div class="rounded-xl bg-white ring-1 ring-slate-200"></div><div class="rounded-xl bg-white ring-1 ring-slate-200"></div><div class="rounded-xl bg-slate-800"></div></div>
                </div>
                <div class="p-6">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-lg font-semibold text-slate-900">{{ __('Blog bölümü') }}</p>
                        <span class="text-sm font-semibold text-brand-900 transition group-hover:translate-x-1">{{ __('Düzenle') }} →</span>
                    </div>
                    <p class="mt-1.5 text-sm leading-relaxed text-slate-600">{{ __('Anasayfadaki blog yazıları alanı: başlık, öne çıkan yazılar ve bülten kutusu.') }}</p>
                    <p class="mt-3 text-xs font-medium {{ $homeBlogs->count() ? 'text-emerald-700' : 'text-slate-500' }}">
                        {{ $homeBlogs->count() ? __('✓ :count yazı seçili', ['count' => $homeBlogs->count()]) : __('Otomatik: en yeni yazılar') }}
                    </p>
                </div>
            </x-admin.card>
        </a>
        </div>

        {{-- ÖZELLİK KARTLARI --}}
        @php $cardSection = 'features'; $cardOn = \App\Support\SiteSections::enabled($cardSection); @endphp
        <div class="group relative" data-card="features">
            {{-- aç / kapa: kartın sağ üstünde (bağlantının dışında) --}}
            <x-admin.section-toggle :section="$cardSection" class="absolute right-3 top-3 z-20 !py-1 !pl-3 !pr-1 [&_[data-section-state]]:text-xs" />
            <a href="{{ route('features.index') }}" @class(['block h-full transition focus-visible:outline-none', 'opacity-50 grayscale' => !$cardOn]) data-card-link>
            <x-admin.card class="h-full overflow-hidden transition group-hover:-translate-y-1 group-hover:shadow-xl group-focus-visible:ring-2 group-focus-visible:ring-brand-500">
                {{-- sürükleme tutamacı --}}
                <span data-drag-handle draggable="true" title="{{ __('Sürükleyerek sırala') }}" aria-label="{{ __('Sürükleyerek sırala') }}"
                    class="absolute left-3 top-3 z-10 grid h-9 w-7 cursor-grab place-items-center rounded-lg bg-white/90 text-slate-400 shadow-sm ring-1 ring-slate-200 backdrop-blur transition hover:text-slate-700 active:cursor-grabbing">
                    <svg class="size-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="9" cy="6" r="1.5"/><circle cx="15" cy="6" r="1.5"/><circle cx="9" cy="12" r="1.5"/><circle cx="15" cy="12" r="1.5"/><circle cx="9" cy="18" r="1.5"/><circle cx="15" cy="18" r="1.5"/></svg>
                </span>
                <div class="relative flex aspect-[16/8] items-center gap-3 overflow-hidden bg-slate-100 px-5">
                    @foreach ($features as $feature)
                        <div class="w-36 shrink-0 rounded-2xl bg-white p-3 shadow-sm ring-1 ring-slate-200/70">
                            <span class="grid size-8 place-items-center rounded-xl bg-gradient-to-br from-rose-500 to-red-700 text-white">{!! $feature->iconHtml('size-4') !!}</span>
                            <p class="mt-2 truncate text-xs font-bold text-slate-900">{{ $feature->title }}</p>
                            <p class="mt-0.5 line-clamp-2 text-[10px] leading-snug text-slate-500">{{ $feature->text }}</p>
                        </div>
                    @endforeach
                </div>
                <div class="p-6">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-lg font-semibold text-slate-900">{{ __('Özellik kartları') }}</p>
                        <span class="text-sm font-semibold text-brand-900 transition group-hover:translate-x-1">{{ __('Düzenle') }} →</span>
                    </div>
                    <p class="mt-1.5 text-sm leading-relaxed text-slate-600">
                        {{ __('Anasayfada telefonlu bölümün altında yan yana kayan küçük kartlar: ikon, başlık ve bir cümle.') }}
                    </p>
                    <p class="mt-3 text-xs font-medium {{ $featureCount ? 'text-emerald-700' : 'text-slate-500' }}">
                        {{ $featureCount ? __('✓ :count kart yayında', ['count' => $featureCount]) : __('Yayında kart yok, bölüm gizli') }}
                    </p>
                </div>
            </x-admin.card>
        </a>
        </div>

        {{-- DUYURU ŞERİDİ --}}
        @php $cardSection = 'announcements'; $cardOn = \App\Support\SiteSections::enabled($cardSection); @endphp
        <div class="group relative" data-card="announcements">
            {{-- aç / kapa: kartın sağ üstünde (bağlantının dışında) --}}
            <x-admin.section-toggle :section="$cardSection" class="absolute right-3 top-3 z-20 !py-1 !pl-3 !pr-1 [&_[data-section-state]]:text-xs" />
            <a href="{{ route('announcements.index') }}" @class(['block h-full transition focus-visible:outline-none', 'opacity-50 grayscale' => !$cardOn]) data-card-link>
            <x-admin.card class="h-full overflow-hidden transition group-hover:-translate-y-1 group-hover:shadow-xl group-focus-visible:ring-2 group-focus-visible:ring-brand-500">
                {{-- sürükleme tutamacı --}}
                <span data-drag-handle draggable="true" title="{{ __('Sürükleyerek sırala') }}" aria-label="{{ __('Sürükleyerek sırala') }}"
                    class="absolute left-3 top-3 z-10 grid h-9 w-7 cursor-grab place-items-center rounded-lg bg-white/90 text-slate-400 shadow-sm ring-1 ring-slate-200 backdrop-blur transition hover:text-slate-700 active:cursor-grabbing">
                    <svg class="size-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="9" cy="6" r="1.5"/><circle cx="15" cy="6" r="1.5"/><circle cx="9" cy="12" r="1.5"/><circle cx="15" cy="12" r="1.5"/><circle cx="9" cy="18" r="1.5"/><circle cx="15" cy="18" r="1.5"/></svg>
                </span>
                <div class="relative aspect-[16/8] overflow-hidden bg-slate-100">
                    <div class="absolute inset-x-0 top-6 flex h-7 items-center gap-6 overflow-hidden bg-slate-900 px-4 text-[11px] font-semibold text-white/90 whitespace-nowrap">
                        <span>● {{ __('Kayan yazı burada görünür') }}</span><span>● {{ __('Kayan yazı burada görünür') }}</span><span>● {{ __('Kayan yazı burada görünür') }}</span>
                    </div>
                    <div class="absolute inset-x-0 top-[3.25rem] h-8 border-b border-slate-200 bg-white"></div>
                </div>
                <div class="p-6">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-lg font-semibold text-slate-900">{{ __('Duyuru şeridi') }}</p>
                        <span class="text-sm font-semibold text-brand-900 transition group-hover:translate-x-1">{{ __('Düzenle') }} →</span>
                    </div>
                    <p class="mt-1.5 text-sm leading-relaxed text-slate-600">
                        {{ __('Sitenin en üstünde soldan sağa kayan kısa yazılar (kampanya, yeni rehber…).') }}
                    </p>
                    <p class="mt-3 text-xs font-medium {{ $announcementCount ? 'text-emerald-700' : 'text-slate-500' }}">
                        {{ $announcementCount ? __('✓ :count duyuru yayında', ['count' => $announcementCount]) : __('Yayında duyuru yok, şerit gizli') }}
                    </p>
                </div>
            </x-admin.card>
        </a>
        </div>

    </div>
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
