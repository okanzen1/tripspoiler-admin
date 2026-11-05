// Aktif/pasif anahtarları (x-admin.toggle): sayfa yenilemeden günceller, sonucu küçük bir bildirimle gösterir.
import { t } from './i18n';
import { toast } from './toast';

document.querySelectorAll('form[data-toggle]').forEach((form) => {
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const button = form.querySelector('[role="switch"]');
        button.disabled = true;

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: new FormData(form),
            });
            if (!response.ok) throw new Error(response.statusText);

            const data = await response.json();
            // Aynı kayda ait tüm anahtarlar (ör. listede ve başlıkta) birlikte güncellensin
            document.querySelectorAll('form[data-toggle]').forEach((other) => {
                if (other.action === form.action) other.querySelector('[role="switch"]').setAttribute('aria-checked', String(data.active));
            });
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
            toast(t('Kaydedilemedi, tekrar dene.'), true);
        } finally {
            button.disabled = false;
        }
    });
});
