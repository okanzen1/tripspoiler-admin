// Arka planda süren çeviriler: sağ üstte duran bildirim. Sayfa değişse de durur (sunucu durumu tutar).
// Formlar: data-translate="tür:id" (ör. "hero:1"). Onay penceresinden sonra fetch ile başlatılır; sayfa kilitlenmez,
// sadece o kaydın çeviri butonu iş bitene kadar pasif olur. Durum: GET /ceviri-durum?keys[]=…
import { t } from './i18n';

const config = window.translationJobs ?? { jobs: [], now: Math.floor(Date.now() / 1000) };
const jobs = new Map();
let clockOffset = Date.now() / 1000 - config.now; // sunucu ile tarayıcı saati farkı
let pollTimer;
let tickTimer;
let box;

const SEEN_KEY = 'translation-jobs-seen';
const MIN_KEY = 'translation-jobs-minimized';
const readMin = () => { try { return JSON.parse(localStorage.getItem(MIN_KEY) || '[]'); } catch { return []; } };
const setMin = (key, on) => {
    try { localStorage.setItem(MIN_KEY, JSON.stringify([...readMin().filter((k) => k !== key), ...(on ? [key] : [])].slice(-20))); } catch { /* gizli pencere */ }
};
const serverNow = () => Date.now() / 1000 - clockOffset;
const seenId = (job) => `${job.key}@${job.finished_at}`;

function readSeen() {
    try { return JSON.parse(localStorage.getItem(SEEN_KEY) || '[]'); } catch { return []; }
}
function markSeen(job) {
    try { localStorage.setItem(SEEN_KEY, JSON.stringify([...readSeen(), seenId(job)].slice(-50))); } catch { /* gizli pencere */ }
}

const escape = (text) => String(text ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

function elapsed(job) {
    const s = Math.max(0, Math.floor((job.finished_at ?? serverNow()) - job.started_at));
    return s < 60 ? t(':s sn', { s }) : t(':m dk :s sn', { m: Math.floor(s / 60), s: String(s % 60).padStart(2, '0') });
}

const samePage = (url) => {
    try { return new URL(url, location.href).pathname === location.pathname; } catch { return false; }
};

/* ---------- Butonlar: süren işin butonu pasif ---------- */
function lockButtons() {
    document.querySelectorAll('form[data-translate]').forEach((form) => {
        const running = jobs.get(form.dataset.translate)?.status === 'running';
        const buttons = [...form.querySelectorAll('button[type=submit], button:not([type])')];
        if (form.id) buttons.push(...document.querySelectorAll(`button[form="${form.id}"]`));
        buttons.forEach((button) => {
            button.disabled = running;
            button.classList.toggle('cursor-wait', running);
            button.classList.toggle('opacity-60', running);
            button.title = running ? t('Çeviri sürüyor, bitince tekrar kullanılabilir.') : '';
        });
    });
}

/* ---------- Bildirim ---------- */
// Küçültülmüş hali: tek satırlık hap (simge + kısa durum); tıklayınca açılır
function pill(job) {
    const running = job.status === 'running';
    const failed = job.status === 'failed';
    const dot = running
        ? '<svg class="size-4 animate-spin text-purple-600" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-20"/><path d="M22 12a10 10 0 0 0-10-10" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>'
        : `<span class="size-2.5 rounded-full ${failed ? 'bg-red-500' : 'bg-emerald-500'}"></span>`;
    const label = running ? (job.total ? `${job.done} / ${job.total}` : t('Başlatılıyor…')) : failed ? t('Çeviri yapılamadı') : t('Çeviri tamamlandı');

    return `
        <button type="button" data-job="${escape(job.key)}" data-job-expand title="${escape(job.title || '')}"
            class="pointer-events-auto ml-auto inline-flex max-w-full items-center gap-2 rounded-full bg-white py-2 pl-3 pr-4 text-xs font-semibold text-slate-700 shadow-lg ring-1 ${failed ? 'ring-red-200' : running ? 'ring-purple-200' : 'ring-emerald-200'} transition hover:-translate-y-0.5">
            ${dot}<span class="truncate">${escape(t('Çeviri'))} · <span class="tabular-nums">${escape(label)}</span></span>
            <svg class="size-3.5 shrink-0 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="m18 15-6-6-6 6"/></svg>
        </button>`;
}

function card(job) {
    if (readMin().includes(job.key)) return pill(job);
    const running = job.status === 'running';
    const failed = job.status === 'failed';
    const percent = job.total ? Math.round((job.done / job.total) * 100) : 0;
    const icon = running
        ? '<svg class="size-5 animate-spin text-purple-600" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-20"/><path d="M22 12a10 10 0 0 0-10-10" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>'
        : failed
            ? '<svg class="size-5 text-red-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="9"/><path d="M12 8v4M12 16h.01"/></svg>'
            : '<svg class="size-5 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 5 5L20 7"/></svg>';
    const heading = running ? t('Diğer dillere çevriliyor…') : failed ? t('Çeviri yapılamadı') : t('Çeviri tamamlandı');
    const action = !running && !failed && !samePage(job.url)
        ? `<a href="${escape(job.url)}" class="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-emerald-700">${escape(t('Aç'))}</a>`
        : '';

    return `
        <div data-job="${escape(job.key)}" role="status" class="pointer-events-auto w-full rounded-2xl bg-white p-4 shadow-xl ring-1 ${failed ? 'ring-red-200' : running ? 'ring-purple-200' : 'ring-emerald-200'}">
            <div class="flex items-start gap-3">
                <span class="mt-0.5 shrink-0">${icon}</span>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold text-slate-900">${escape(heading)}</p>
                    <p class="truncate text-xs text-slate-500">${escape(job.title || t('Çeviri başlatılıyor…'))}</p>
                    ${running ? `
                        <div class="mt-2.5 h-1.5 overflow-hidden rounded-full bg-purple-100">
                            <div class="h-full rounded-full bg-purple-600 transition-all duration-500" style="width:${job.total ? Math.max(4, percent) : 4}%"></div>
                        </div>
                        <p class="mt-1.5 flex justify-between text-[11px] font-medium tabular-nums text-slate-500">
                            <span>${job.total ? escape(t(':done / :total dil', { done: job.done, total: job.total })) : escape(t('Başlatılıyor…'))}</span>
                            <span data-job-time>${escape(elapsed(job))}</span>
                        </p>
                        <p class="mt-1 text-[11px] text-slate-400">${escape(t('Bu arada başka işlerine devam edebilirsin.'))}</p>`
                    : `<p class="mt-1 text-xs leading-relaxed ${failed ? 'text-red-700' : 'text-slate-600'}">${escape(job.message)}</p>
                       ${action ? `<div class="mt-2.5">${action}</div>` : ''}`}
                </div>
                <div class="-m-1 flex shrink-0 items-center">
                    <button type="button" data-job-minimize class="rounded-lg p-1 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600" aria-label="${escape(t('Küçült'))}" title="${escape(t('Küçült'))}">
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M6 12h12"/></svg></button>
                    ${running ? '' : `<button type="button" data-job-close class="rounded-lg p-1 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600" aria-label="${escape(t('Kapat'))}">
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg></button>`}
                </div>
            </div>
        </div>`;
}

function render() {
    if (!box) {
        box = document.createElement('div');
        // sağ altta, footer'ın üstünde: sağ üstteki sayfa butonlarını kapatmasın
        box.className = 'pointer-events-none fixed bottom-24 right-4 z-[9998] flex w-[min(22rem,calc(100vw-2rem))] flex-col items-stretch gap-2';
        box.addEventListener('click', (e) => {
            const el = e.target.closest('[data-job]');
            if (!el) return;
            if (e.target.closest('[data-job-close]')) dismiss(el.dataset.job);
            else if (e.target.closest('[data-job-minimize]')) { setMin(el.dataset.job, true); render(); }
            else if (e.target.closest('[data-job-expand]')) { setMin(el.dataset.job, false); render(); }
        });
        document.body.append(box);
    }
    box.innerHTML = [...jobs.values()].map(card).join('');
    lockButtons();
}

function dismiss(key) {
    const job = jobs.get(key);
    if (job && job.status !== 'running') markSeen(job);
    jobs.delete(key);
    render();
}

function upsert(job) {
    const before = jobs.get(job.key);
    jobs.set(job.key, { ...before, ...job });
    // Biten başarılı iş bir süre sonra kendiliğinden kapanır (hata kalır, kapatılana kadar)
    if (job.status === 'done' && before?.status !== 'done') {
        markSeen(job);
        // Bu sayfanın kaydıysa çeviri durumları ve alanlar yerinde güncellensin (sayfa yenilenmez)
        if (before && samePage(job.url)) window.liveRefresh?.();
        setTimeout(() => jobs.get(job.key)?.status === 'done' && dismiss(job.key), 12000);
    }
}

/* ---------- Durum sorgusu ---------- */
async function poll() {
    const keys = [...jobs.values()].filter((job) => job.status === 'running').map((job) => job.key);
    if (!keys.length || !config.statusUrl) return stopPolling();

    try {
        const url = new URL(config.statusUrl, location.href);
        keys.forEach((key) => url.searchParams.append('keys[]', key));
        const res = await fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
        if (!res.ok) return;
        const data = await res.json();
        clockOffset = Date.now() / 1000 - data.now;
        data.jobs.forEach(upsert);
        // Başlatma isteği sunucuya hiç ulaşmadıysa sonsuza dek "başlatılıyor" kalmasın
        keys.forEach((key) => {
            const job = jobs.get(key);
            if (job?.local && !data.jobs.some((j) => j.key === key) && serverNow() - job.started_at > 20) {
                upsert({ key, status: 'failed', message: t('Çeviri başlatılamadı, tekrar dene.'), finished_at: serverNow() });
            }
        });
        render();
    } catch { /* ağ hatası: bir sonraki turda tekrar dene */ }
}

function startPolling() {
    if (!pollTimer) pollTimer = setInterval(poll, 2000);
    if (!tickTimer) {
        tickTimer = setInterval(() => {
            jobs.forEach((job) => {
                if (job.status === 'running') {
                    const el = box?.querySelector(`[data-job="${CSS.escape(job.key)}"] [data-job-time]`);
                    if (el) el.textContent = elapsed(job);
                }
            });
        }, 1000);
    }
}

function stopPolling() {
    clearInterval(pollTimer);
    clearInterval(tickTimer);
    pollTimer = tickTimer = null;
}

/* ---------- Çeviri formları ---------- */
// Onay penceresi gönderimi yakalama aşamasında durdurur; buraya yalnızca "evet" denen formlar düşer
document.addEventListener('submit', async (e) => {
    const form = e.target;
    if (e.defaultPrevented || !form.matches('form[data-translate]')) return;
    e.preventDefault();

    const key = form.dataset.translate;
    if (jobs.get(key)?.status === 'running') return;
    upsert({ key, title: '', status: 'running', done: 0, total: 0, started_at: serverNow(), finished_at: null, url: location.href, local: true });
    render();
    startPolling();

    try {
        const res = await fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        });
        const data = await res.json().catch(() => ({}));
        if (res.ok && data.job) {
            upsert({ ...data.job, local: false });
        } else if (res.ok && data.message) {
            upsert({ key, status: 'done', message: data.message, finished_at: serverNow() });
        } else {
            const message = res.status === 429 ? t('Çok sık çeviri isteği gönderildi, bir dakika sonra tekrar dene.')
                : res.status === 419 ? t('Oturum süresi doldu, sayfayı yenileyip tekrar dene.')
                : data.message || data.error || t('Çeviri başlatılamadı (:code).', { code: res.status });
            upsert({ key, status: 'failed', message, finished_at: serverNow() });
        }
    } catch {
        // Sayfadan ayrılınca istek yarıda kesilebilir; çeviri sunucuda sürer, durum sorgusu takip eder
    }
    render();
});

/* ---------- Açılışta: süren ve az önce biten işler ---------- */
const seen = readSeen();
config.jobs.filter((job) => job.status === 'running' || !seen.includes(seenId(job))).forEach(upsert);
if (jobs.size) {
    document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', render) : render();
    startPolling();
}

// Yerinde yenilenen bölgelerdeki butonlar da süren çeviri için pasif kalsın
document.addEventListener('live:refreshed', lockButtons);
