// Aktif/pasif anahtarları ve yıldızlar (x-admin.toggle): sayfa yenilemeden, yerinden oynamadan günceller.
// Tıklanınca hemen değişir; sunucu farklı derse ya da hata olursa düzeltilir. Belgeye tek dinleyici (capture):
// panelin genel form göndericisinden önce çalışır, yenilenen listelerdeki anahtarlar da çalışır.
import { t } from './i18n';
import { toast } from './toast';

const setState = (action, active) => document.querySelectorAll('form[data-toggle]').forEach((other) => {
    if (other.action === action) other.querySelector('[role="switch"]').setAttribute('aria-checked', String(active));
});

document.addEventListener('submit', async (e) => {
    const form = e.target;
    if (!(form instanceof HTMLFormElement) || !form.matches('[data-toggle]')) return;
    e.preventDefault();
    e.stopImmediatePropagation();

    const button = form.querySelector('[role="switch"]');
    if (form.dataset.busy) return; // art arda tıklama: önceki bitsin
    form.dataset.busy = '1';
    const before = button.getAttribute('aria-checked') === 'true';
    setState(form.action, !before);

    try {
        const response = await fetch(form.action, {
            method: 'POST',
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: new FormData(form),
        });
        if (!response.ok) throw new Error(response.statusText);

        const data = await response.json();
        setState(form.action, data.active);
        form.dispatchEvent(new CustomEvent('toggled', { bubbles: true, detail: data }));
        // Bölüm anahtarı (x-admin.section-toggle): "Sitede yayında / gizli" yazısı
        const state = form.closest('[data-section-toggle]')?.querySelector('[data-section-state]');
        if (state) {
            state.textContent = data.active ? state.dataset.on : state.dataset.off;
            state.classList.toggle('text-emerald-700', data.active);
            state.classList.toggle('text-slate-500', !data.active);
        }
        toast(data.message, data.warning);
    } catch {
        setState(form.action, before);
        toast(t('Kaydedilemedi, tekrar dene.'), true);
    } finally {
        delete form.dataset.busy;
    }
}, true);
