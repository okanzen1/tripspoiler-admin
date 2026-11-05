@props(['title', 'description' => null, 'breadcrumb' => []])

{{--
    Sayfa başlığı: layout'taki beyaz bandın içinde (@section('header')) kullanılır.
    Slotlar: $icon (svg path'leri), $leading (ikon yerine özel öğe, ör. avatar), varsayılan slot = sağdaki aksiyonlar.
--}}
<div class="flex flex-wrap items-center justify-between gap-x-6 gap-y-4">
    <div class="flex min-w-0 items-center gap-4">
        @isset($leading)
            {{ $leading }}
        @elseif (isset($icon))
            <span class="grid size-12 shrink-0 place-items-center rounded-2xl shadow-sm {{ \App\Support\SectionColor::classes(\App\Support\SectionColor::forRoute(request()->route()?->getName()), 'solid') }}">
                <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{{ $icon }}</svg>
            </span>
        @endisset

        <div class="min-w-0">
            {{-- Konum (her sayfada): Ana sayfa › …verilen ara sayfalar… › bu sayfa --}}
            @unless (request()->routeIs('analytics.index'))
                @php($trail = ['Ana sayfa' => route('analytics.index')] + $breadcrumb)
                <nav aria-label="{{ __('Konum') }}" class="mb-1 flex flex-wrap items-center gap-1.5 text-sm text-slate-500">
                    @foreach ($trail as $label => $url)
                        <a href="{{ $url }}" class="transition hover:text-brand-900">{{ __($label) }}</a>
                        <svg class="size-4 text-slate-300 rtl:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6" /></svg>
                    @endforeach
                    <span class="font-medium text-brand-900" aria-current="page">{{ __($title) }}</span>
                </nav>
            @endunless
            <h1 class="truncate text-2xl font-semibold tracking-tight text-slate-900">{{ __($title) }}</h1>
            @if ($description)
                <p class="mt-0.5 text-sm text-slate-500">{{ __($description) }}</p>
            @endif
        </div>
    </div>

    @if (trim($slot))
        <div class="flex flex-wrap items-center gap-3">{{ $slot }}</div>
    @endif
</div>
