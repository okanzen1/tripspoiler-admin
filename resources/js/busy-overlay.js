// Uzun süren form işlemlerinde (ör. Google Çeviri) sayfayı kilitleyen bekleme ekranı.
// Formlarda: data-busy="Başlık" [data-busy-hint="Açıklama"]. Onay penceresi varsa "evet"ten sonra açılır;
// sunucu cevap verip sayfa yenilenince kendiliğinden kalkar, sonuç mesajı sayfada görünür.
import { t } from './i18n';

let overlay;
let timer;

function build() {
    overlay = document.createElement('div');
    overlay.setAttribute('role', 'alertdialog');
    overlay.setAttribute('aria-busy', 'true');
    overlay.className = 'fixed inset-0 z-[100] hidden place-items-center bg-slate-900/60 p-4 backdrop-blur-sm';
    overlay.innerHTML = `
        <div class="w-full max-w-sm rounded-2xl bg-white p-7 text-center shadow-2xl ring-1 ring-slate-200">
            <svg class="mx-auto size-11 animate-spin text-purple-600" viewBox="0 0 24 24" fill="none">
                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-20"/>
                <path d="M22 12a10 10 0 0 0-10-10" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
            </svg>
            <p data-busy-title class="mt-5 text-base font-semibold text-slate-900"></p>
            <p data-busy-hint class="mt-1.5 text-sm leading-relaxed text-slate-600"></p>
            <p class="mt-4 inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold tabular-nums text-slate-600">
                <span class="size-1.5 animate-pulse rounded-full bg-purple-500"></span><span data-busy-time>0 sn</span>
            </p>
        </div>`;
    document.body.append(overlay);
}

export function showBusy(title, hint = '') {
    if (!overlay) build();
    overlay.querySelector('[data-busy-title]').textContent = title;
    overlay.querySelector('[data-busy-hint]').textContent = hint || t('Bu işlem biraz sürebilir, sayfayı kapatma.');
    const time = overlay.querySelector('[data-busy-time]');
    const started = Date.now();
    const tick = () => {
        const s = Math.floor((Date.now() - started) / 1000);
        time.textContent = s < 60 ? `${s} sn` : `${Math.floor(s / 60)} dk ${String(s % 60).padStart(2, '0')} sn`;
    };
    tick();
    clearInterval(timer);
    timer = setInterval(tick, 1000);
    overlay.classList.replace('hidden', 'grid');
    document.activeElement?.blur();
}

export function hideBusy() {
    clearInterval(timer);
    overlay?.classList.replace('grid', 'hidden');
}

// Onay penceresi gönderimi yakalama aşamasında durdurur; buraya yalnızca gerçekten gönderilen formlar düşer
document.addEventListener('submit', (e) => {
    const form = e.target;
    if (e.defaultPrevented || !form.matches('form[data-busy]')) return;
    showBusy(form.dataset.busy, form.dataset.busyHint);
});

// Geri tuşuyla önbellekten dönülürse ekran açık kalmasın
window.addEventListener('pageshow', (e) => e.persisted && hideBusy());
