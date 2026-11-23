// Sağ üstteki zil (resources/views/components/admin/notification-bell.blade.php): okunmamış sayısı, son bildirimler,
// süren çeviriler. Sekme açıkken 30 sn'de bir (çeviri sürerken 8 sn'de bir) tek, hafif bir istekle güncellenir;
// sekme arka plandayken hiç sorgulamaz. Yeni bildirim gelince zilin altında kısa bir uyarı çıkar.
import { t } from './i18n';

const bell = document.querySelector('[data-notifications]');

const escape = (text) => String(text ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

function itemHtml(item) {
    return `
        <li>
            <a href="${escape(item.open)}" class="flex items-start gap-3 px-4 py-3 transition hover:bg-slate-50 ${item.read ? '' : 'bg-brand-50/40'}">
                <span class="mt-0.5 grid size-8 shrink-0 place-items-center rounded-lg ${escape(item.tone)}">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="${escape(item.icon)}" /></svg>
                </span>
                <span class="min-w-0 flex-1">
                    <span class="block text-sm text-slate-900 ${item.read ? 'font-medium' : 'font-semibold'}">${escape(item.title)}</span>
                    ${item.body ? `<span class="block truncate text-xs text-slate-500">${escape(item.body)}</span>` : ''}
                    <span class="mt-0.5 block text-[11px] text-slate-400" title="${escape(item.date)}">${escape(item.label)} · ${escape(item.time)}</span>
                </span>
                ${item.read ? '' : `<span class="mt-2 size-2 shrink-0 rounded-full bg-brand-600" aria-label="${escape(t('Okunmadı'))}"></span>`}
            </a>
        </li>`;
}

function popup(item) {
    let box = document.getElementById('notification-popup');
    if (!box) {
        box = document.createElement('div');
        box.id = 'notification-popup';
        box.setAttribute('role', 'status');
        box.className = 'fixed right-4 top-16 z-[60] w-[min(22rem,calc(100vw-2rem))] transition duration-300';
        document.body.append(box);
    }
    box.innerHTML = `<ul class="overflow-hidden rounded-2xl bg-white shadow-xl ring-1 ring-slate-200">${itemHtml(item)}</ul>`;
    box.style.opacity = '1';
    box.style.transform = 'none';
    clearTimeout(box.timer);
    box.timer = setTimeout(() => {
        box.style.opacity = '0';
        box.style.transform = 'translateY(-8px)';
        setTimeout(() => box.replaceChildren(), 300);
    }, 6000);
}

if (bell) {
    const badge = bell.querySelector('[data-notifications-badge]');
    const list = bell.querySelector('[data-notifications-list]');
    const status = bell.querySelector('[data-notifications-status]');
    const spinner = bell.querySelector('[data-notifications-running]');
    let latest = bell.dataset.latest || '';
    let timer;
    let busy = false;

    const setBadge = (count) => {
        badge.textContent = count > 99 ? '99+' : String(count);
        badge.classList.toggle('hidden', !count);
    };

    function render(data) {
        setBadge(data.unread);
        list.innerHTML = data.items.length
            ? data.items.map(itemHtml).join('')
            : `<li class="px-4 py-8 text-center text-sm text-slate-400">${escape(t('Henüz bildirim yok.'))}</li>`;

        const lines = data.running.map((job) => `${escape(job.title)} · ${job.done} / ${job.total}`);
        if (data.bulk) lines.push(escape(t('Toplu çeviri: :count kayıt sırada', { count: data.bulk })));
        const spin = '<svg class="size-3.5 shrink-0 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-20"/><path d="M22 12a10 10 0 0 0-10-10" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>';
        status.innerHTML = lines.map((line) => `<p class="flex items-center gap-2">${spin}<span class="truncate">${line}</span></p>`).join('');
        status.classList.toggle('hidden', !lines.length);
        spinner.classList.toggle('hidden', !lines.length);
        spinner.classList.toggle('grid', !!lines.length);

        // Yeni (okunmamış) bildirim: kısa uyarı (ilk yüklemede değil)
        const first = data.items[0];
        if (first && first.id !== latest && !first.read && latest !== null) popup(first);
        latest = first?.id ?? '';
    }

    async function refresh() {
        if (busy || document.hidden) return schedule();
        busy = true;
        try {
            const res = await fetch(bell.dataset.summaryUrl, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
            if (res.ok) {
                const data = await res.json();
                render(data);
                document.dispatchEvent(new CustomEvent('notifications:summary', { detail: data }));
                busy = false;
                return schedule(data.running.length ? 8000 : 30000);
            }
        } catch { /* ağ hatası: sonra tekrar */ }
        busy = false;
        schedule();
    }

    function schedule(ms = 30000) {
        clearTimeout(timer);
        timer = setTimeout(refresh, ms);
    }

    bell.querySelector('[data-notifications-read-all]')?.addEventListener('click', async () => {
        const token = document.querySelector('meta[name="csrf-token"]')?.content;
        await fetch(bell.dataset.readAllUrl, { method: 'POST', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': token }, credentials: 'same-origin' }).catch(() => {});
        refresh();
    });

    // Zil açılınca hemen güncel hali
    bell.addEventListener('toggle', () => bell.open && refresh());
    // Sekmeye dönünce hemen; çeviri başlatılınca birkaç saniye sonra
    document.addEventListener('visibilitychange', () => !document.hidden && refresh());
    document.addEventListener('notifications:refresh', () => schedule(3000));

    // Sayfada süren çeviri varsa hızlı başla, yoksa normal aralık
    schedule((window.translationJobs?.jobs ?? []).some((job) => job.status === 'running') ? 5000 : 30000);
}
