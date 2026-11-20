@props(['types' => null, 'under' => null])

{{--
    "SEO'su / içeriği eksik olanlar": hangi kaydın neyinin (SEO başlığı, SEO açıklaması, yazı…) boş olduğunu adıyla gösterir,
    tıklayınca o kaydın düzenleme sayfası açılır (App\Support\ContentTodo).
    types: kayıt türleri (ör. ['blog']); verilmezse hepsi. under: yalnızca bu adreslerin altındakiler (ör. bir şehrin rehberi).
--}}
@php
    $items = \App\Support\ContentTodo::items()
        ->when($types, fn ($c) => $c->whereIn('type', $types))
        ->when($under, fn ($c) => $c->filter(fn ($i) => \Illuminate\Support\Str::startsWith(rtrim(strtok($i['url'], '#?'), '/') . '/', collect((array) $under)->map(fn ($u) => rtrim($u, '/') . '/')->all())))
        ->values();
    // liste filtrelenmişse (arama, şehir, durum…) kutu gizlenir; menüdeki turuncu sayı yine görünür
    if (array_diff_key(request()->query(), ['page' => true])) {
        $items = collect();
    }
    $kinds = ['activity' => 'Aktivite', 'blog' => 'Blog yazısı', 'attraction' => 'Yer', 'page' => 'Sayfa SEO',
        'city-guide' => 'Şehir rehberi', 'city-guide-section' => 'Rehber yazısı'];
@endphp

@if ($items->isNotEmpty())
    @php
        $row = fn ($item) => '<li><a href="' . e($item['url']) . '" class="flex h-full flex-col gap-1 rounded-xl bg-white px-3 py-2 text-sm ring-1 ring-amber-100 transition hover:ring-amber-400">'
            . '<span class="flex items-center gap-2"><span class="shrink-0 rounded-md bg-amber-100 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-amber-800">' . e(__($kinds[$item['type']] ?? $item['type'])) . '</span>'
            . '<span class="min-w-0 flex-1 truncate font-medium text-slate-800">' . e($item['title']) . '</span><span class="shrink-0 text-xs font-semibold text-amber-700">→</span></span>'
            . '<span class="text-xs text-amber-800">' . e(__('Eksik: :list', ['list' => implode(', ', $item['missing'])])) . '</span></a></li>';
    @endphp
    <div {{ $attributes->class('mb-6 rounded-2xl bg-amber-50 p-4 ring-1 ring-amber-200 sm:p-5') }}>
        <p class="flex flex-wrap items-center gap-2 text-sm font-semibold text-amber-900">
            <span class="grid size-7 place-items-center rounded-lg bg-amber-500 text-white">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 9v4M12 17h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z" /></svg>
            </span>
            {{ __('SEO\'su ya da içeriği eksik olanlar (:count)', ['count' => $items->count()]) }}
            <span class="font-normal text-amber-800">· {{ __('Google\'da eksik görünür. Tıkla, boş alanları doldur.') }}</span>
        </p>
        <ul class="mt-3 grid gap-1.5 sm:grid-cols-2 xl:grid-cols-3">{!! $items->take(9)->map($row)->implode('') !!}</ul>
        @if ($items->count() > 9)
            <details class="group mt-1.5">
                <summary class="cursor-pointer list-none text-xs font-semibold text-amber-800 hover:underline group-open:hidden">{{ __('Tümünü göster (:count)', ['count' => $items->count()]) }}</summary>
                <ul class="grid gap-1.5 sm:grid-cols-2 xl:grid-cols-3">{!! $items->slice(9)->map($row)->implode('') !!}</ul>
            </details>
        @endif
    </div>
@endif
