@props(['types' => null, 'under' => null])

{{--
    "Çevirisi eksik olanlar": hangi kaydın çevirisinin unutulduğunu tek tek adıyla gösterir, tıklayınca o kaydın sayfası açılır.
    types: kayıt türleri (ör. ['faq']); verilmezse hepsi. under: yalnızca bu adreslerin altındakiler (ör. bir şehrin rehberi).
--}}
@php
    $items = \App\Support\TranslationTodo::items()
        // aktivite / blog listesinde onların SSS'leri de
        ->when($types, fn ($c) => $c->filter(fn ($i) => in_array($i['type'], $types, true) || ($i['parent'] ?? null) && in_array(strtok($i['parent'], ':'), $types, true)))
        ->when($under, fn ($c) => $c->filter(fn ($i) => \Illuminate\Support\Str::startsWith(rtrim(strtok($i['url'], '#?'), '/') . '/', collect((array) $under)->map(fn ($u) => rtrim($u, '/') . '/')->all())))
        ->values();
    $kinds = ['activity' => 'Aktivite', 'blog' => 'Blog yazısı', 'faq' => 'SSS', 'page' => 'Sayfa SEO', 'announcement' => 'Duyuru', 'campaign' => 'Kampanya',
        'hero' => 'Hero', 'feature' => 'Özellik kartı', 'section' => 'Bölüm yazısı', 'step' => 'Adım', 'city-guide' => 'Şehir rehberi girişi',
        'city-guide-section' => 'Rehber yazısı', 'city-guide-topic' => 'Rehber alanının adı'];
@endphp

@if ($items->isNotEmpty())
    @php
        $row = fn ($item) => '<li><a href="' . e($item['url']) . '" class="flex items-center gap-2 rounded-xl bg-white px-3 py-2 text-sm ring-1 ring-purple-100 transition hover:ring-purple-400">'
            . '<span class="shrink-0 rounded-md bg-purple-100 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-purple-700">' . e(__($kinds[$item['type']] ?? $item['type'])) . '</span>'
            . '<span class="min-w-0 flex-1 truncate font-medium text-slate-800">' . e($item['title']) . '</span>'
            . '<span class="shrink-0 text-xs font-semibold text-purple-600">' . e(__(':count dil', ['count' => $item['locales']])) . ' →</span></a></li>';
    @endphp
    <div {{ $attributes->class('mb-6 rounded-2xl bg-purple-50 p-4 ring-1 ring-purple-200 sm:p-5') }}>
        @php
            // sırada bekleyen çeviriler (cron her dakika işler)
            $queued = rescue(fn () => \Illuminate\Support\Facades\DB::table('jobs')->where('payload', 'like', '%TranslateModel%')->count(), 0, false);
        @endphp
        <div class="flex flex-wrap items-start justify-between gap-3">
        <p class="flex flex-wrap items-center gap-2 text-sm font-semibold text-purple-900">
            <span class="grid size-7 place-items-center rounded-lg bg-purple-500 text-white">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 8 6 6M4 14l6-6 2-3M2 5h12M22 22l-5-10-5 10M14 18h6" /></svg>
            </span>
            {{ __('Çevirisi eksik olanlar (:count)', ['count' => $items->count()]) }}
            <span class="font-normal text-purple-700">· {{ __('Tıkla, açılan sayfada üstteki Çeviriler kutusundan çevir.') }}</span>
        </p>
        {{-- hepsini çeviri sırasına al: zamanlayıcı tek tek çevirir, bitenler listeden düşer --}}
        <form method="POST" action="{{ route('translations.list') }}" class="flex shrink-0 items-center gap-2">
            @csrf
            @foreach ($items as $item)
                <input type="hidden" name="items[]" value="{{ $item['type'] }}:{{ $item['id'] }}">
            @endforeach
            @if ($queued)
                <span class="inline-flex items-center gap-1.5 rounded-full bg-white px-3 py-1.5 text-xs font-semibold text-purple-700 ring-1 ring-purple-200">
                    <svg class="size-3.5 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-20"/><path d="M22 12a10 10 0 0 0-10-10" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
                    {{ __(':count kayıt sırada', ['count' => $queued]) }}
                </span>
            @endif
            <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-purple-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-purple-700">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 8 6 6M4 14l6-6 2-3M2 5h12M22 22l-5-10-5 10M14 18h6" /></svg>
                {{ __('Tümünü sırayla çevir') }}
            </button>
        </form>
        </div>
        <ul class="mt-3 grid gap-1.5 sm:grid-cols-2 xl:grid-cols-3">{!! $items->take(9)->map($row)->implode('') !!}</ul>
        @if ($items->count() > 9)
            <details class="group mt-1.5">
                <summary class="cursor-pointer list-none text-xs font-semibold text-purple-700 hover:underline group-open:hidden">{{ __('Tümünü göster (:count)', ['count' => $items->count()]) }}</summary>
                <ul class="grid gap-1.5 sm:grid-cols-2 xl:grid-cols-3">{!! $items->slice(9)->map($row)->implode('') !!}</ul>
            </details>
        @endif
    </div>
@endif
