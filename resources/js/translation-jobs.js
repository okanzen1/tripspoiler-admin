// Kayıt sayfalarındaki "Çevir" formları (data-translate="tür:id", ör. "hero:1"): onay penceresinden sonra fetch ile
// sıraya alınır, kısa bir mesaj çıkar; bitince sağ üstteki zile bildirim gelir (eskiden sağ altta kutu çıkıyordu).
// Süren çevirinin butonu pasif kalır. Süren işler zilin özetinden gelir (notifications.js → "notifications:summary").
import { t } from './i18n';
import { toast } from './toast';

const running = new Set((window.translationJobs?.jobs ?? []).filter((job) => job.status === 'running').map((job) => job.key));

function lockButtons() {
    document.querySelectorAll('form[data-translate]').forEach((form) => {
        const busy = running.has(form.dataset.translate);
        const buttons = [...form.querySelectorAll('button[type=submit], button:not([type])')];
        if (form.id) buttons.push(...document.querySelectorAll(`button[form="${form.id}"]`));
        buttons.forEach((button) => {
            button.disabled = busy;
            button.classList.toggle('cursor-wait', busy);
            button.classList.toggle('opacity-60', busy);
            button.title = busy ? t('Çeviri sürüyor, bitince tekrar kullanılabilir.') : '';
        });
    });
}

// Bu sayfadaki kaydın çevirisi bitti: çeviri kutusu ve alanlar yerinde yenilensin (sayfa yenilenmez)
const liveKey = (key) => `translations-${String(key).replace(':', '-')}`;
const onThisPage = (key) => !!document.querySelector(`form[data-translate="${CSS.escape(key)}"], [data-live="${CSS.escape(liveKey(key))}"]`);

document.addEventListener('notifications:summary', (e) => {
    const now = new Set((e.detail.running ?? []).map((job) => job.key));
    const finished = [...running].filter((key) => !now.has(key));
    running.clear();
    now.forEach((key) => running.add(key));
    lockButtons();
    if (finished.some(onThisPage)) window.liveRefresh?.();
});

// Onay penceresi gönderimi yakalama aşamasında durdurur; buraya yalnızca "evet" denen formlar düşer
document.addEventListener('submit', async (e) => {
    const form = e.target;
    if (e.defaultPrevented || !form.matches('form[data-translate]')) return;
    e.preventDefault();

    const key = form.dataset.translate;
    if (running.has(key)) return;
    running.add(key);
    lockButtons();

    try {
        const res = await fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        });
        const data = await res.json().catch(() => ({}));
        if (res.ok && data.job) {
            toast(data.warning || t('Çeviri sıraya alındı. Bitince sağ üstteki zile bildirim gelir; bu arada başka işlerine devam edebilirsin.'), !!data.warning);
            document.dispatchEvent(new CustomEvent('notifications:refresh'));
            return;
        }
        running.delete(key);
        lockButtons();
        if (res.ok && data.message) {
            toast(data.message);
        } else {
            toast(res.status === 429 ? t('Çok sık çeviri isteği gönderildi, bir dakika sonra tekrar dene.')
                : res.status === 419 ? t('Oturum süresi doldu, sayfayı yenileyip tekrar dene.')
                : data.message || data.error || t('Çeviri başlatılamadı (:code).', { code: res.status }), true);
        }
    } catch {
        running.delete(key);
        lockButtons();
        toast(t('Çeviri başlatılamadı, tekrar dene.'), true);
    }
});

document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', lockButtons) : lockButtons();
// Yerinde yenilenen bölgelerdeki butonlar da süren çeviri için pasif kalsın
document.addEventListener('live:refreshed', lockButtons);
