// Editörde tablo: tablo tek parça (düzenlenemeyen) bir blok olarak durur, tıklayınca basit metin penceresinde
// düzenlenir: her satır bir tablo satırı, hücreler "|" ile ayrılır, ilk satır başlık. **kalın** ve [yazı](adres) desteklenir.
// Kayıtta saf <table> gider (sunucu yine de temizler: HtmlSanitizer).
import Quill from 'quill';
import { t } from './i18n';

const BlockEmbed = Quill.import('blots/block/embed');
const esc = (text) => text.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
const safeHref = (href) => /^(https?:|mailto:|tel:|\/(?!\/)|#)/i.test(href.trim()) ? href.trim() : null;

/** Hücre içi: yalnızca kalın, italik, bağlantı ve satır sonu kalır */
function cleanInline(node) {
    let out = '';
    node.childNodes.forEach((child) => {
        if (child.nodeType === Node.TEXT_NODE) {
            out += esc(child.textContent);
        } else if (child.nodeType === Node.ELEMENT_NODE) {
            const tag = child.tagName;
            const inner = cleanInline(child);
            if (tag === 'STRONG' || tag === 'B') out += `<strong>${inner}</strong>`;
            else if (tag === 'EM' || tag === 'I') out += `<em>${inner}</em>`;
            else if (tag === 'BR') out += '<br>';
            else if (tag === 'A' && safeHref(child.getAttribute('href') || '')) out += `<a href="${esc(safeHref(child.getAttribute('href')))}">${inner}</a>`;
            else out += inner + (/^(P|DIV|LI)$/.test(tag) ? ' ' : '');
        }
    });

    return out.trim();
}

/** Tablo → { head: string[]|null, rows: string[][] } (hücreler temiz HTML) */
function readTable(html) {
    const table = new DOMParser().parseFromString(html, 'text/html').querySelector('table');
    if (!table) return { head: null, rows: [] };
    let head = null;
    const rows = [];
    [...table.rows].forEach((tr, i) => {
        const cells = [...tr.cells].map(cleanInline);
        const isHead = tr.parentElement?.tagName === 'THEAD' || (i === 0 && [...tr.cells].every((c) => c.tagName === 'TH'));
        if (isHead && !head) head = cells;
        else rows.push(cells);
    });

    return { head, rows };
}

export function buildTable({ head, rows }) {
    const width = Math.max(head?.length ?? 0, ...rows.map((r) => r.length));
    if (!width) return '';
    const pad = (cells) => [...cells, ...Array(width - cells.length).fill('')];
    let html = '<table>';
    if (head) html += '<thead><tr>' + pad(head).map((c) => `<th scope="col">${c}</th>`).join('') + '</tr></thead>';
    html += '<tbody>' + rows.map((row) => '<tr>' + pad(row).map((c) => `<td>${c}</td>`).join('') + '</tr>').join('') + '</tbody>';

    return html + '</table>';
}

const cleanTable = (html) => buildTable(readTable(html));

// Metin ↔ hücre (kalın ve bağlantı korunur)
const cellToText = (html) => {
    const box = document.createElement('div');
    box.innerHTML = html.replace(/<strong>(.*?)<\/strong>/g, '**$1**').replace(/<a href="([^"]*)">(.*?)<\/a>/g, '[$2]($1)').replace(/<br>/g, ' ');

    return box.textContent.replace(/\|/g, '/').trim();
};
const textToCell = (text) => esc(text.trim())
    .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
    .replace(/\[([^\]]+)\]\(([^)\s]+)\)/g, (all, label, href) => (safeHref(href.replace(/&amp;/g, '&')) ? `<a href="${href}">${label}</a>` : label));

export function tableToText(html) {
    const { head, rows } = readTable(html);
    const line = (cells) => cells.map(cellToText).join(' | ');

    return [head ? line(head) : null, ...rows.map(line)].filter((l) => l !== null).join('\n');
}

export function textToTable(text) {
    const lines = text.split('\n').map((l) => l.trim()).filter((l) => l && !/^\|?\s*:?-{2,}:?\s*(\|\s*:?-{2,}:?\s*)*\|?$/.test(l));
    const cells = lines.map((l) => l.replace(/^\||\|$/g, '').split('|').map(textToCell));
    if (!cells.length) return '';

    return buildTable({ head: cells[0], rows: cells.slice(1) });
}

class TableBlot extends BlockEmbed {
    static blotName = 'tablo';
    static tagName = 'DIV';
    static className = 'ql-tablo';

    static create(value) {
        const node = super.create();
        node.setAttribute('contenteditable', 'false');
        node.innerHTML = cleanTable(String(value || ''));

        return node;
    }

    static value(node) {
        return node.querySelector('table')?.outerHTML ?? '';
    }

    html() {
        return this.domNode.querySelector('table')?.outerHTML ?? '';
    }
}
Quill.register(TableBlot, true);

let dialog;
/** Tablo penceresi; sonuç: yeni tablo HTML'i, '' = sil, null = vazgeç */
function openDialog(html) {
    if (!dialog) {
        dialog = document.createElement('dialog');
        dialog.className = 'w-[min(44rem,calc(100vw-2rem))] rounded-2xl p-0 shadow-2xl backdrop:bg-slate-900/40';
        dialog.innerHTML = `
            <form method="dialog" class="space-y-3 p-5">
                <h3 class="text-base font-semibold text-slate-900">${t('Tablo')}</h3>
                <p class="text-sm text-slate-500">${t('Her satır tablonun bir satırı. Hücreleri | ile ayır. İlk satır başlık olur.')}<br>
                    <span class="text-slate-400">${t('Kalın: **yazı** · Bağlantı: [yazı](https://…)')}</span></p>
                <textarea rows="10" class="block w-full rounded-xl border border-slate-300 p-3 font-mono text-sm focus:border-brand-900 focus:ring-brand-900" spellcheck="false"></textarea>
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <button value="delete" data-delete class="rounded-xl px-3 py-2 text-sm font-semibold text-rose-600 hover:bg-rose-50">${t('Tabloyu sil')}</button>
                    <div class="ms-auto flex gap-2">
                        <button value="cancel" class="rounded-xl px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100">${t('Vazgeç')}</button>
                        <button value="save" class="rounded-xl bg-brand-900 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-800">${t('Kaydet')}</button>
                    </div>
                </div>
            </form>`;
        document.body.append(dialog);
    }
    const area = dialog.querySelector('textarea');
    area.value = html ? tableToText(html) : t('Semt | En uygun | Not\nPlaka | İlk kez gelenler | Merkezi');
    dialog.querySelector('[data-delete]').hidden = !html;
    dialog.returnValue = '';
    dialog.showModal();
    area.focus();

    return new Promise((resolve) => dialog.addEventListener('close', () => {
        if (dialog.returnValue === 'save') resolve(textToTable(area.value));
        else resolve(dialog.returnValue === 'delete' ? '' : null);
    }, { once: true }));
}

const ICON = '<svg viewBox="0 0 18 18"><rect class="ql-stroke" x="2.5" y="3.5" width="13" height="11" rx="1.5" fill="none"/><line class="ql-stroke" x1="2.5" y1="7.5" x2="15.5" y2="7.5"/><line class="ql-stroke" x1="2.5" y1="11" x2="15.5" y2="11"/><line class="ql-stroke" x1="7" y1="7.5" x2="7" y2="14.5"/><line class="ql-stroke" x1="11.5" y1="7.5" x2="11.5" y2="14.5"/></svg>';

/** Editöre tablo desteğini bağlar: araç çubuğu düğmesi, yapıştırma, tıklayınca düzenleme */
export function attachTables(quill, Delta) {
    quill.clipboard.addMatcher('TABLE', (node) => {
        const html = cleanTable(node.outerHTML);

        return html ? new Delta().insert({ tablo: html }) : new Delta();
    });

    const toolbar = quill.getModule('toolbar');
    const button = toolbar?.container.querySelector('button.ql-tablo');
    if (button) {
        button.innerHTML = ICON;
        button.title = t('Tablo ekle');
    }
    toolbar?.addHandler('tablo', async () => {
        const range = quill.getSelection(true) ?? { index: quill.getLength() };
        const html = await openDialog('');
        if (html) {
            quill.insertEmbed(range.index, 'tablo', html, 'user');
            quill.setSelection(range.index + 1, 0, 'silent');
        }
    });

    quill.root.addEventListener('click', async (event) => {
        const node = event.target.closest('.ql-tablo');
        if (!node) return;
        const blot = Quill.find(node);
        if (!blot) return;
        const result = await openDialog(TableBlot.value(node));
        if (result === null) return;
        const index = quill.getIndex(blot);
        quill.deleteText(index, 1, 'user');
        if (result) quill.insertEmbed(index, 'tablo', result, 'user');
    });
}
