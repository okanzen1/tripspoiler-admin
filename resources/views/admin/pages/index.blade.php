@extends('layouts.admin')

@php
    $missing = $pages->reject->isSeoComplete()->count();
@endphp

@section('title', 'Sayfalar')

@section('header')
    <x-admin.page-header :title="__('Sayfalar')" :breadcrumb="['Yönetim' => route('management.index')]"
        :description="__('Sitenin sayfaları: her birinde hero alanı, alttaki bölümler ve Google ayarları (SEO). Düzenlemek için sayfaya tıkla.')">
        <x-slot:icon><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8Z" /><path d="M14 3v5h5" /></x-slot:icon>
        @if ($missing)
            <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-3 py-1 text-sm font-semibold text-amber-700">{{ __(':count sayfanın SEO bilgisi eksik', ['count' => $missing]) }}</span>
        @endif
    </x-admin.page-header>
@endsection

@section('content')
    <x-admin.todo-list :under="[route('pages.index'), route('site-settings.hero')]" />
    @php
        // Sayfa ikonu ve banner önizlemesi
        // her sayfanın kendi rengi (ikon kutusu)
        $colors = [
            'home' => 'bg-rose-100 text-rose-600',
            'activities' => 'bg-red-100 text-red-600',
            'activity' => 'bg-orange-100 text-orange-600',
            'cities' => 'bg-sky-100 text-sky-600',
            'blog' => 'bg-violet-100 text-violet-600',
            'post' => 'bg-fuchsia-100 text-fuchsia-600',
            'reviews' => 'bg-amber-100 text-amber-600',
            'about' => 'bg-teal-100 text-teal-600',
            'contact' => 'bg-emerald-100 text-emerald-600',
            'privacy' => 'bg-slate-200 text-slate-600',
            'rates' => 'bg-lime-100 text-lime-700',
            'campaigns' => 'bg-orange-100 text-orange-600',
            'weather' => 'bg-sky-100 text-sky-600',
            'time' => 'bg-indigo-100 text-indigo-600',
        ];
        $icons = [
            'home' => '<path d="M4 11.5 12 5l8 6.5V19a1 1 0 0 1-1 1h-4.5v-5h-5v5H5a1 1 0 0 1-1-1z"/>',
            'activities' => '<path d="M4 8a2 2 0 0 0 0 4v4a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-4a2 2 0 0 1 0-4V5a1 1 0 0 0-1-1H5a1 1 0 0 0-1 1z" transform="translate(0 1.5)"/>',
            'activity' => '<rect x="3" y="5" width="18" height="14" rx="2.5"/><path d="M3 10h18"/>',
            'cities' => '<path d="M9 4 3.5 6v14L9 18l6 2 5.5-2V4L15 6z"/><path d="M9 4v14M15 6v14"/>',
            'blog' => '<path d="M4 20h4L19 9a2.8 2.8 0 0 0-4-4L4 16z"/><path d="m13.5 6.5 4 4"/>',
            'post' => '<path d="M6 3h9l4 4v14H6z"/><path d="M15 3v4h4M9 12h7M9 16h5"/>',
            'reviews' => '<path d="m12 3 2.8 5.7 6.2.9-4.5 4.4 1.1 6.2L12 17.3l-5.6 2.9 1.1-6.2L3 9.6l6.2-.9Z"/>',
            'about' => '<circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/>',
            'contact' => '<path d="M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.6A8 8 0 1 1 21 12z"/>',
            'privacy' => '<path d="M12 3 4 6v6c0 4.5 3.4 8.3 8 9 4.6-.7 8-4.5 8-9V6z"/>',
            'rates' => '<path d="M7 7h11l-3-3M17 17H6l3 3"/>',
            'time' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
            'weather' => '<path d="M7 18a4.5 4.5 0 1 1 .9-8.9A6 6 0 0 1 19 11a3.5 3.5 0 0 1-.5 7z"/>',
            'campaigns' => '<path d="M20 12v8a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-8M2.5 7.5h19v4.5h-19zM12 21V7.5M12 7.5H8a2.5 2.5 0 1 1 0-5c2.5 0 4 5 4 5zM12 7.5h4a2.5 2.5 0 1 0 0-5c-2.5 0-4 5-4 5z"/>',
        ];
    @endphp

    <x-admin.card class="overflow-hidden">
        <div class="hidden grid-cols-12 gap-4 border-b border-slate-100 bg-slate-50 px-6 py-3 text-[11px] font-semibold uppercase tracking-wide text-slate-500 md:grid">
            <span class="col-span-5">{{ __('Sayfa') }}</span>
            <span class="col-span-3">{{ __('Durum') }}</span>
            <span class="col-span-4 text-right">{{ __('Düzenle') }}</span>
        </div>
        <ul class="divide-y divide-slate-100">
            @foreach ($pages as $page)
                @php
                    $hasHero = isset(\App\Http\Controllers\PageHeroTextController::FIELDS[$page->key]);
                    $heroDone = $page->key === 'home' || filled(\App\Models\SectionText::where('key', 'hero_' . $page->key)->value('title'));
                    $hasSections = \App\Support\PageSections::supports($page->key);
                    $config = $hasSections ? \App\Support\PageSections::config($page->key) : null;
                    $sectionCount = $config ? count(array_filter($config['order'], fn ($k) => $k !== 'main' && in_array($k, $config['on'], true))) : null;
                    $links = array_values(array_filter([
                        $page->key === 'home' ? [__('Hero alanı'), route('site-settings.hero')] : ($hasHero ? [__('Hero alanı'), route('pages.hero-text', $page)] : null),
                        $page->key === 'home' ? [__('Bölümler'), route('site-settings.index')] : ($hasSections ? [__('Bölümler'), route('pages.sections', $page)] : null),
                        isset(\App\Http\Controllers\PageContentController::BLOCKS[$page->key]) ? [__('İçerik'), route('pages.content', $page)] : null,
                        $page->hasOwnSeo() ? [__('SEO'), route('pages.seo', $page)] : null,
                    ]));
                @endphp
                <li class="grid items-center gap-4 px-6 py-4 transition hover:bg-slate-200/50 hover:shadow-sm hover:ring-1 hover:ring-slate-300/70 md:grid-cols-12 cursor-pointer">
                    <a href="{{ route('pages.edit', $page) }}" class="flex min-w-0 items-center gap-4 md:col-span-5">
                        <span class="grid size-11 shrink-0 place-items-center rounded-xl {{ $colors[$page->key] ?? 'bg-brand-50 text-brand-900' }}">
                            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">{!! $icons[$page->key] ?? $icons['about'] !!}</svg>
                        </span>
                        <span class="min-w-0">
                            <span class="block truncate font-semibold text-slate-900">{{ $page->name }}</span>
                            <code class="block truncate font-mono text-xs text-slate-400">tripspoiler.com{{ $page->path === '/' ? '' : $page->path }}</code>
                        </span>
                    </a>
                    <div class="flex flex-wrap gap-1.5 md:col-span-3">
                        @if ($page->hasOwnSeo())
                            <span @class(['inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-[11px] font-semibold', 'bg-emerald-50 text-emerald-700' => $page->isSeoComplete(), 'bg-amber-50 text-amber-700' => !$page->isSeoComplete()])>
                                <span @class(['size-1.5 rounded-full', 'bg-emerald-500' => $page->isSeoComplete(), 'bg-amber-500' => !$page->isSeoComplete()])></span>{{ $page->isSeoComplete() ? __('SEO tamam') : __('SEO eksik') }}
                            </span>
                        @endif
                        @if ($hasHero || $page->key === 'home')
                            <span @class(['inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-[11px] font-semibold', 'bg-emerald-50 text-emerald-700' => $heroDone, 'bg-slate-100 text-slate-500' => !$heroDone])>
                                <span @class(['size-1.5 rounded-full', 'bg-emerald-500' => $heroDone, 'bg-slate-400' => !$heroDone])></span>{{ $heroDone ? __('Hero') : __('Hero varsayılan') }}
                            </span>
                        @endif
                        @if ($todo = \App\Support\TranslationTodo::under(...array_column($links, 1)))
                            <span class="inline-flex items-center gap-1 rounded-full bg-purple-50 px-2.5 py-1 text-[11px] font-semibold text-purple-700 ring-1 ring-purple-200"><svg class="size-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 8 6 6M4 14l6-6 2-3M2 5h12M22 22l-5-10-5 10M14 18h6" /></svg>{{ __(':count çeviri eksik', ['count' => $todo]) }}</span>
                        @endif
                        @if ($sectionCount !== null)
                            <span class="inline-flex items-center rounded-full bg-sky-50 px-2.5 py-1 text-[11px] font-semibold text-sky-700">{{ __(':n bölüm açık', ['n' => $sectionCount]) }}</span>
                        @endif
                    </div>
                    <div class="flex flex-wrap gap-1.5 md:col-span-4 md:justify-end">
                        @foreach ($links as [$label, $href])
                            <a href="{{ $href }}" class="rounded-lg px-3 py-1.5 text-xs font-semibold text-slate-700 ring-1 ring-slate-200 transition hover:bg-brand-900 hover:text-white hover:ring-brand-900">{{ $label }}</a>
                        @endforeach
                    </div>
                </li>
            @endforeach
        </ul>
    </x-admin.card>
@endsection
