{{--
    Sihirbazdaki SSS adımı (aktivite, blog). $rows: [['id' => ?, 'question' => ?, 'answer' => ?], …] · $description
    "Toplu yapıştır": birçok soru-cevabı tek seferde yapıştır, satırlara dağıtılır (resources/js/faq-bulk.js).
--}}
<section data-step hidden class="space-y-5">
    <x-admin.step-heading :title="__('Sıkça sorulan sorular')" :description="$description" />

    <div data-repeater="faqs" data-max="{{ $max ?? 30 }}" data-remove-confirm="{{ __('Bu soru silinsin mi? Kaydettiğinde kalıcı olarak silinir.') }}" class="space-y-3">
        <div data-repeater-list class="space-y-3">
            @foreach ($rows as $row)
                @include('admin.partials.faq-row', ['faq' => $row, 'number' => $loop->iteration])
            @endforeach
        </div>
        <p data-repeater-empty class="hidden rounded-2xl border-2 border-dashed border-slate-200 px-6 py-8 text-center text-sm text-slate-500">{{ __('Henüz soru yok.') }}</p>
        <template data-repeater-template>
            @include('admin.partials.faq-row', ['faq' => []])
        </template>
        {{-- toplu yapıştır --}}
        <details data-faq-bulk class="group rounded-2xl bg-brand-50/60 ring-1 ring-brand-100 open:bg-white open:ring-brand-200">
            <summary class="flex cursor-pointer list-none items-center gap-3 px-4 py-3 text-sm font-semibold text-brand-900 [&::-webkit-details-marker]:hidden">
                <span class="grid size-8 place-items-center rounded-lg bg-white ring-1 ring-brand-100"><x-admin.icon name="plus" /></span>
                <span class="flex-1">{{ __('Toplu yapıştır: birçok soruyu tek seferde ekle') }}</span>
                <svg class="size-4 transition group-open:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6" /></svg>
            </summary>
            <div class="space-y-3 px-4 pb-4">
                <p class="text-xs leading-relaxed text-slate-500">{{ __('Soruları ve cevapları buraya yapıştır, "Soruları ekle"ye bas; her biri ayrı satıra yerleşir. Şu biçimlerin hepsi olur:') }}</p>
                <div class="grid gap-2 text-xs sm:grid-cols-2">
                    <pre class="whitespace-pre-wrap rounded-xl bg-slate-50 p-3 font-mono text-[11px] leading-relaxed text-slate-600 ring-1 ring-slate-200">1. Is the ticket refundable?
Yes, up to 24 hours before.

2. Is a guide included?
Yes, a one-hour tour.</pre>
                    <pre class="whitespace-pre-wrap rounded-xl bg-slate-50 p-3 font-mono text-[11px] leading-relaxed text-slate-600 ring-1 ring-slate-200">Q: Is the ticket refundable?
A: Yes, up to 24 hours before.

Q: Is a guide included?
A: Yes, a one-hour tour.</pre>
                </div>
                <textarea data-faq-bulk-text rows="8" placeholder="{{ __('Soruları buraya yapıştır…') }}" aria-label="{{ __('Toplu yapıştır') }}"
                    class="block w-full resize-y rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm leading-relaxed outline-none transition placeholder:text-slate-400 focus:border-brand-900 focus:ring-4 focus:ring-brand-900/10"></textarea>
                <div class="flex flex-wrap items-center gap-3">
                    <button type="button" data-faq-bulk-add
                        class="inline-flex items-center gap-2 rounded-xl bg-brand-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-800">
                        <x-admin.icon name="plus" /> {{ __('Soruları ekle') }}
                    </button>
                    <p data-faq-bulk-status class="text-sm font-medium" role="status"></p>
                </div>
            </div>
        </details>

        <button type="button" data-repeater-add
            class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-brand-900 ring-1 ring-brand-200 transition hover:bg-brand-50 disabled:opacity-40">
            <x-admin.icon name="plus" /> {{ __('Soru ekle') }}
        </button>
    </div>
    @foreach ($errors->get('faqs*') as $messages)
        <p class="text-xs font-medium text-red-600">{{ $messages[0] }}</p>
    @endforeach
</section>
