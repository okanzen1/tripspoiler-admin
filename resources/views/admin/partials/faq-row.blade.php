{{-- Aktivite SSS satırı. Alan adları JS ile numaralanır (resources/js/repeater.js). $faq: ['id' => ?, 'question' => ?, 'answer' => ?] --}}
@php($input = 'block w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm outline-none transition placeholder:text-slate-400 focus:border-brand-900 focus:ring-4 focus:ring-brand-900/10')

<div data-repeater-row class="rounded-2xl bg-white p-4 ring-1 ring-slate-200">
    <input type="hidden" data-field="id" value="{{ $faq['id'] ?? '' }}" />
    <div class="flex items-start gap-3">
        <span class="mt-1 grid size-7 shrink-0 place-items-center rounded-full bg-brand-50 text-xs font-semibold text-brand-900" data-repeater-number>{{ $number ?? '' }}</span>
        <div class="min-w-0 flex-1 space-y-3">
            <input type="text" data-field="question" value="{{ $faq['question'] ?? '' }}" maxlength="500" placeholder="{{ __('Soru (ör. Is the ticket refundable?)') }}" aria-label="{{ __('Soru') }}"
                class="{{ $input }} font-medium" />
            <textarea data-field="answer" rows="3" maxlength="5000" placeholder="{{ __('Cevap') }}" aria-label="{{ __('Cevap') }}" class="{{ $input }} resize-y leading-relaxed">{{ $faq['answer'] ?? '' }}</textarea>
        </div>
        <div class="flex shrink-0 flex-col gap-1">
            <button type="button" data-repeater-up title="{{ __('Yukarı taşı') }}" aria-label="{{ __('Yukarı taşı') }}" class="grid size-8 place-items-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 disabled:opacity-30">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m18 15-6-6-6 6" /></svg>
            </button>
            <button type="button" data-repeater-down title="{{ __('Aşağı taşı') }}" aria-label="{{ __('Aşağı taşı') }}" class="grid size-8 place-items-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 disabled:opacity-30">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6" /></svg>
            </button>
            <button type="button" data-repeater-remove title="{{ __('Sil') }}" aria-label="{{ __('Sil') }}" class="grid size-8 place-items-center rounded-lg text-slate-400 transition hover:bg-red-50 hover:text-red-600">
                <x-admin.icon name="trash" />
            </button>
        </div>
    </div>
</div>
