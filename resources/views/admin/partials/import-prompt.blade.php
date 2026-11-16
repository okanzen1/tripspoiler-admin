{{-- "Hazır yazı yapıştır" sayfalarındaki talimat: kopyalanıp Claude'a / ChatGPT'ye verilir --}}
<x-admin.card class="p-6 sm:p-8">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <x-admin.step-heading :title="__('1. Talimatı kopyala')" :description="$description" />
        <button type="button" data-copy-prompt
            class="inline-flex shrink-0 items-center gap-2 rounded-xl bg-brand-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-800">
            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="9" width="12" height="12" rx="2" /><path d="M5 15H4a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v1" /></svg>
            <span data-copy-label>{{ __('Talimatı kopyala') }}</span>
        </button>
    </div>
    <textarea readonly rows="10" data-prompt-text
        class="mt-4 block w-full resize-y rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-3 font-mono text-xs leading-relaxed text-slate-700 outline-none">{{ $prompt }}</textarea>
</x-admin.card>

<script>
    document.querySelector('[data-copy-prompt]')?.addEventListener('click', async (event) => {
        const btn = event.currentTarget;
        const box = document.querySelector('[data-prompt-text]');
        const label = btn.querySelector('[data-copy-label]');
        try {
            await navigator.clipboard.writeText(box.value);
        } catch {
            box.select();
            document.execCommand('copy');
        }
        const text = label.textContent;
        label.textContent = @json(__('Kopyalandı ✓'));
        setTimeout(() => (label.textContent = text), 1800);
    });
</script>
