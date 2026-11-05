@props(['action', 'steps', 'start' => 0, 'submitLabel' => 'Kaydet', 'cancelUrl', 'free' => false, 'method' => null])

{{--
    Adım adım form. İçine her adım için bir <section data-step> koy.
    "İleri" o adımdaki alanları doğrular; son adımda [data-summary-for="alan"] öğeleri doldurulur.
    free: düzenleme modu; adımlar serbestçe gezilir, "Kaydet" her adımda görünür.
    Adımlar geniş ekranda solda dikey, dar ekranda üstte yatay listelenir.
--}}
<x-admin.card class="overflow-hidden">
    <form action="{{ $action }}" method="POST" enctype="multipart/form-data" data-wizard data-start="{{ $start }}" data-free="{{ $free ? 1 : 0 }}" novalidate
        class="lg:grid lg:grid-cols-[15rem_1fr]">
        @csrf
        @if ($method)
            @method($method)
        @endif

        {{-- Adım göstergesi --}}
        <ol class="flex gap-4 overflow-x-auto border-b border-slate-100 bg-slate-50/60 px-6 py-4 lg:flex-col lg:gap-0 lg:overflow-visible lg:border-b-0 lg:border-r lg:px-5 lg:py-7">
            @foreach ($steps as $i => $title)
                <li data-step-indicator data-state="upcoming" class="group relative shrink-0 lg:pb-7 lg:last:pb-0">
                    @unless ($loop->last)
                        <span class="absolute left-[17px] top-11 bottom-2 hidden w-0.5 rounded-full bg-slate-200 transition group-data-[state=done]:bg-brand-300 lg:block" aria-hidden="true"></span>
                    @endunless
                    {{-- Tıklanabilir adımlar: düzenlemede hepsi, eklemede tamamlananlar --}}
                    <button type="button"
                        @class([
                            'flex w-full items-center gap-3 rounded-xl p-1.5 text-left transition -m-1.5 group-data-[state=current]:cursor-default',
                            'cursor-pointer hover:bg-brand-50 focus-visible:outline-2 focus-visible:outline-brand-900',
                            'group-data-[state=upcoming]:cursor-not-allowed group-data-[state=upcoming]:opacity-70 group-data-[state=upcoming]:hover:bg-transparent' => !$free,
                        ])
                        title="{{ $free ? __('Bu adıma git') : '' }}">
                        <span class="grid size-9 shrink-0 place-items-center rounded-full text-sm font-semibold ring-2 transition
                            group-data-[state=upcoming]:bg-white group-data-[state=upcoming]:text-slate-400 group-data-[state=upcoming]:ring-slate-200
                            group-data-[state=current]:bg-brand-900 group-data-[state=current]:text-white group-data-[state=current]:ring-brand-900
                            group-data-[state=done]:bg-brand-50 group-data-[state=done]:text-brand-700 group-data-[state=done]:ring-brand-200">
                            <span class="group-data-[state=done]:hidden">{{ $i + 1 }}</span>
                            <svg class="hidden size-4 group-data-[state=done]:block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12 5 5 9-9" /></svg>
                        </span>
                        <span class="min-w-0">
                            <span class="block text-[11px] font-medium uppercase tracking-wider text-slate-400">{{ __('Adım :number', ['number' => $i + 1]) }}</span>
                            <span class="flex items-center gap-1 whitespace-nowrap text-sm font-medium group-data-[state=current]:font-semibold group-data-[state=current]:text-slate-900 group-data-[state=done]:text-slate-700 group-data-[state=done]:underline group-data-[state=done]:decoration-slate-300 group-data-[state=done]:underline-offset-4 @if ($free) group-data-[state=upcoming]:text-slate-600 group-data-[state=upcoming]:underline group-data-[state=upcoming]:decoration-slate-300 group-data-[state=upcoming]:underline-offset-4 @else group-data-[state=upcoming]:text-slate-400 @endif">
                                {{ __($title) }}
                                <svg class="size-3.5 opacity-0 transition group-hover:opacity-100 group-data-[state=current]:hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" /></svg>
                            </span>
                        </span>
                    </button>
                </li>
            @endforeach
        </ol>

        <div class="flex min-w-0 flex-col">
            <div class="flex-1 p-6 sm:p-8">
                {{ $slot }}
            </div>

            <div class="flex items-center justify-between gap-3 border-t border-slate-100 bg-slate-50/60 px-6 py-4 sm:px-8">
                <a href="{{ $cancelUrl }}" class="rounded-xl px-4 py-2.5 text-sm font-medium text-slate-500 transition hover:bg-slate-100 hover:text-slate-700">{{ __('Vazgeç') }}</a>

                <div class="flex items-center gap-3">
                    @php($last = count($steps) - 1)
                    <button type="button" data-wizard-prev
                        @class(['inline-flex items-center gap-1.5 rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-700 ring-1 ring-slate-200 transition hover:bg-white', 'invisible' => $start === 0])>
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 18l-6-6 6-6" /></svg>
                        {{ __('Geri') }}
                    </button>
                    <button type="button" data-wizard-next
                        @if ($start === $last) hidden @endif
                        @class([
                            'inline-flex items-center gap-1.5 rounded-xl px-5 py-2.5 text-sm font-semibold transition',
                            'bg-brand-900 text-white shadow-sm hover:bg-brand-800' => !$free,
                            'text-slate-700 ring-1 ring-slate-200 hover:bg-white' => $free,
                        ])>
                        {{ __('İleri') }}
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6" /></svg>
                    </button>
                    <button type="submit" data-wizard-submit
                        @if (!$free && $start !== $last) hidden @endif
                        class="inline-flex items-center gap-1.5 rounded-xl bg-brand-900 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-800">
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12 5 5 9-9" /></svg>
                        {{ __($submitLabel) }}
                    </button>
                </div>
            </div>
        </div>
    </form>
</x-admin.card>
