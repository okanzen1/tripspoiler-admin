// Panelin kendi onay penceresi (tarayıcının confirm() kutusu yerine).
// Formlarda: data-confirm="açıklama" [data-confirm-title] [data-confirm-button] [data-confirm-tone="danger|primary"]
// Silme (DELETE) formları kendiliğinden kırmızı "danger" olur.
import { t } from './i18n';

let dialog;

function build() {
    dialog = document.createElement('dialog');
    dialog.className = 'm-auto w-[calc(100%-2rem)] max-w-md rounded-2xl bg-white p-0 text-slate-800 shadow-2xl ring-1 ring-slate-200 backdrop:bg-slate-900/50 backdrop:backdrop-blur-[2px]';
    dialog.innerHTML = `
        <form method="dialog" class="p-6">
            <div class="flex items-start gap-4">
                <span data-icon class="grid size-11 shrink-0 place-items-center rounded-2xl"></span>
                <div class="min-w-0 pt-0.5">
                    <h2 data-title class="text-base font-semibold text-slate-900"></h2>
                    <p data-message class="mt-1.5 whitespace-pre-line text-sm leading-relaxed text-slate-600"></p>
                </div>
            </div>
            <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <button value="cancel" data-cancel class="rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-600 ring-1 ring-slate-200 transition hover:bg-slate-50"></button>
                <button value="ok" data-ok class="rounded-xl px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition"></button>
            </div>
        </form>`;
    document.body.append(dialog);
}

const icons = {
    danger: '<svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16M10 11v6M14 11v6M6 7l1 12a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-12M9 7V4h6v3"/></svg>',
    primary: '<svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 8v4M12 16h.01"/></svg>',
};

/**
 * @returns {Promise<boolean>}
 */
export function ask({ title, message = '', confirm, cancel, tone = 'primary' }) {
    if (!dialog) build();
    const danger = tone === 'danger';

    dialog.querySelector('[data-title]').textContent = title || (danger ? t('Silinsin mi?') : t('Emin misin?'));
    dialog.querySelector('[data-message]').textContent = message;
    dialog.querySelector('[data-cancel]').textContent = cancel || t('Vazgeç');
    const ok = dialog.querySelector('[data-ok]');
    ok.textContent = confirm || (danger ? t('Evet, sil') : t('Evet, devam et'));
    ok.className = `rounded-xl px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition ${danger ? 'bg-red-600 hover:bg-red-700' : 'bg-brand-900 hover:bg-brand-800'}`;
    const icon = dialog.querySelector('[data-icon]');
    icon.className = `grid size-11 shrink-0 place-items-center rounded-2xl ${danger ? 'bg-red-50 text-red-600' : 'bg-brand-50 text-brand-900'}`;
    icon.innerHTML = danger ? icons.danger : icons.primary;

    dialog.returnValue = '';
    dialog.showModal();
    ok.focus();

    return new Promise((resolve) => dialog.addEventListener('close', () => resolve(dialog.returnValue === 'ok'), { once: true }));
}

/** Formun data-confirm-* özelliklerinden pencere ayarları */
export function optionsFor(el, form = el) {
    const isDelete = form.querySelector?.('input[name="_method"]')?.value === 'DELETE';

    return {
        title: el.dataset.confirmTitle,
        message: el.dataset.confirm ?? el.dataset.confirmClick,
        confirm: el.dataset.confirmButton,
        tone: el.dataset.confirmTone || (isDelete ? 'danger' : 'primary'),
    };
}

// Onaylı formlar: önce sor, "evet" denirse aynı düğmeyle yeniden gönder
document.addEventListener('submit', async (e) => {
    const form = e.target;
    if (!form.matches('form[data-confirm]') || form.dataset.confirmed) return;

    e.preventDefault();
    e.stopPropagation();
    if (await ask(optionsFor(form))) {
        form.dataset.confirmed = '1';
        form.requestSubmit(e.submitter);
        delete form.dataset.confirmed;
    }
}, true);
