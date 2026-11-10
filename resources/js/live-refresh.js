// Sayfayı yenilemeden güncelle: aynı adres arka planda yeniden çekilir, yalnızca değişen yerler değiştirilir.
//  - [data-live="anahtar"] bölgeleri (ör. çeviri durumları) yenisiyle değiştirilir
//  - kaydedilen formun alanları sunucudaki son değerlerle eşitlenir (o an yazılan alana dokunulmaz)
//  - seçilmiş dosyalar temizlenir (bir sonraki kayıtta yeniden yüklenmesin)
// Kaydırma konumu, açık sekmeler ve yazılanlar yerinde kalır. Kullanım: window.liveRefresh(form?)

const SKIP = new Set(['_token', '_method']);

function sameForm(form, doc) {
    if (form.id) return doc.getElementById(form.id);
    const forms = [...document.querySelectorAll('form')].filter((f) => f.action === form.action);
    const index = forms.indexOf(form);
    return [...doc.querySelectorAll('form')].filter((f) => new URL(f.getAttribute('action') || '', location.href).href === form.action)[index] ?? null;
}

// Aynı ada sahip alanları sırayla eşleştir (dizi alanlar: "hero_featured[]" gibi)
function syncFields(form, fresh) {
    const byName = (root) => {
        const map = new Map();
        [...root.elements].forEach((el) => {
            if (!el.name || SKIP.has(el.name) || el.type === 'file' || el.type === 'submit' || el.type === 'button') return;
            if (!map.has(el.name)) map.set(el.name, []);
            map.get(el.name).push(el);
        });
        return map;
    };
    const current = byName(form);
    const next = byName(fresh);

    current.forEach((elements, name) => {
        const freshElements = next.get(name) ?? [];
        elements.forEach((el, i) => {
            if (el === document.activeElement) return;
            if (el.type === 'checkbox' || el.type === 'radio') {
                const match = freshElements.find((f) => f.value === el.value);
                const checked = match ? match.hasAttribute('checked') : false;
                if (el.checked !== checked) { el.checked = checked; el.dispatchEvent(new Event('change', { bubbles: true })); }
                return;
            }
            const source = freshElements[i];
            if (!source) return;
            const value = source.tagName === 'TEXTAREA' ? source.textContent
                : source.tagName === 'SELECT' ? ([...source.options].find((o) => o.hasAttribute('selected')) ?? source.options[0])?.value ?? ''
                : source.getAttribute('value') ?? '';
            if (el.value !== value) { el.value = value; el.dispatchEvent(new Event('input', { bubbles: true })); }
        });
    });
}

export async function liveRefresh(form = null, { main = false } = {}) {
    // Seçilen dosyalar kaydedildi: temizle
    form?.querySelectorAll('input[type=file]').forEach((input) => { input.value = ''; });
    form?.querySelector('[data-form-errors]')?.classList.add('hidden');

    let doc;
    try {
        const res = await fetch(location.href, { headers: { Accept: 'text/html', 'X-Live-Refresh': '1' }, credentials: 'same-origin', cache: 'no-store' });
        if (!res.ok || res.redirected) return;
        doc = new DOMParser().parseFromString(await res.text(), 'text/html');
    } catch {
        return;
    }

    // İçeriğin tamamı (ör. satırı bulunamayan silme): <main> yenisiyle değişir
    if (main) {
        const fresh = doc.querySelector('main');
        const old = document.querySelector('main');
        if (fresh && old) old.innerHTML = fresh.innerHTML;
    }

    doc.querySelectorAll('[data-live]').forEach((fresh) => {
        const old = document.querySelector(`[data-live="${CSS.escape(fresh.dataset.live)}"]`);
        if (old && old.innerHTML !== fresh.innerHTML) old.replaceWith(document.importNode(fresh, true));
    });

    if (form) {
        const fresh = sameForm(form, doc);
        if (fresh) syncFields(form, fresh);
    }

    document.dispatchEvent(new CustomEvent('live:refreshed'));
}

window.liveRefresh = liveRefresh;

/** Silinen satırı/kartı listeden yumuşakça kaldır; bulunamazsa içerik yerinde yenilenir */
export async function removeRow(form) {
    const row = form.closest('tr, li, [data-card]');
    if (!row) return liveRefresh(null, { main: true });
    row.style.transition = 'opacity .25s, transform .25s';
    row.style.opacity = '0';
    row.style.transform = 'translateX(8px)';
    await new Promise((resolve) => setTimeout(resolve, 250));
    row.remove();
    return liveRefresh(); // sayılar, durumlar ([data-live])
}

window.removeRow = removeRow;
