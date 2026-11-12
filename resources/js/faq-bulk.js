// SSS "Toplu yapıştır": yapıştırılan metni soru-cevap çiftlerine ayırıp SSS satırlarına yerleştirir.
// Desteklenen biçimler:
//   1) "Q: …" / "A: …" (önünde numara olabilir: "1. Q: …"; "Soru:" / "Cevap:" da olur)
//   2) numaralı: "1. Soru?" satırı, altındaki satırlar cevap
//   3) numarasız: "?" ile biten satır soru, altındaki satırlar cevap
// Boş satırlar varsa önce onlar doldurulur; sonra "Soru ekle" düğmesiyle yeni satır açılır (en fazla data-max).
import { t } from './i18n';

const clean = (line) => line.replace(/\*\*/g, '').replace(/\s+/g, ' ').trim();
const Q = /^(?:\d+\s*[.)-]\s*)?(?:Q|S|Soru|Question)\s*[:：]\s*(.*)$/i;
const A = /^(?:A|C|Cevap|Answer)\s*[:：]\s*(.*)$/i;
const NUM = /^\d+\s*[.)-]\s+(.*)$/;

export function parseFaqs(text) {
    const lines = text.replace(/\r\n?/g, '\n').split('\n').map(clean);
    const mode = lines.some((l) => Q.test(l)) ? 'qa' : lines.filter((l) => NUM.test(l)).length >= 2 ? 'num' : 'mark';
    const pairs = [];
    let cur = null;
    let inAnswer = false;
    const start = (question) => {
        cur = { question, answer: [] };
        pairs.push(cur);
        inAnswer = false;
    };

    for (const line of lines) {
        if (line === '') continue;
        let m;
        if (mode === 'qa' && (m = line.match(Q))) start(m[1]);
        else if (mode === 'qa' && (m = line.match(A)) && cur) {
            inAnswer = true;
            if (m[1]) cur.answer.push(m[1]);
        } else if (mode === 'num' && (m = line.match(NUM))) start(m[1]);
        else if (mode === 'mark' && line.endsWith('?') && line.length < 300 && (!cur || cur.answer.length)) start(line);
        else if (cur) {
            // Q: modunda "A:" gelmeden önceki satırlar sorunun devamı
            if (mode === 'qa' && !inAnswer) cur.question += ' ' + line;
            else cur.answer.push(line);
        }
    }

    return pairs
        .map((p) => ({ question: p.question.trim(), answer: p.answer.join('\n').trim() }))
        .filter((p) => p.question && p.answer);
}

document.querySelectorAll('[data-faq-bulk]').forEach((bulk) => {
    const box = bulk.closest('[data-repeater]');
    const input = bulk.querySelector('[data-faq-bulk-text]');
    const status = bulk.querySelector('[data-faq-bulk-status]');
    const add = box.querySelector('[data-repeater-add]');
    const list = box.querySelector('[data-repeater-list]');

    const say = (message, ok) => {
        status.textContent = message;
        status.className = 'text-sm font-medium ' + (ok ? 'text-emerald-700' : 'text-red-600');
    };

    bulk.querySelector('[data-faq-bulk-add]').addEventListener('click', () => {
        const pairs = parseFaqs(input.value);
        if (!pairs.length) {
            say(t('Soru bulunamadı. Her soru kendi satırında, cevabı altında olmalı.'), false);
            return;
        }
        let added = 0;
        for (const pair of pairs) {
            // önce boş satırları doldur
            let row = [...list.querySelectorAll('[data-repeater-row]')].find((r) =>
                !r.querySelector('[data-field="question"]').value.trim() && !r.querySelector('[data-field="answer"]').value.trim());
            if (!row) {
                if (add.disabled) break;
                add.click();
                row = list.lastElementChild;
            }
            row.querySelector('[data-field="question"]').value = pair.question;
            row.querySelector('[data-field="answer"]').value = pair.answer;
            added++;
        }
        const skipped = pairs.length - added;
        say(skipped
            ? t(':added soru eklendi; en fazla :max soru olabildiği için :skipped tanesi eklenemedi.', { added, max: box.dataset.max, skipped })
            : t(':added soru eklendi. Kontrol edip kaydet.', { added }), !skipped);
        if (added) {
            input.value = '';
            box.dispatchEvent(new Event('input', { bubbles: true })); // kaydedilmemiş değişiklik uyarısı için
            list.lastElementChild?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    });
});

// SSS › Toplu ekle sayfası: yapıştırılan metinde kaç soru bulunduğu (ve Onay adımındaki özet)
document.querySelectorAll('[data-faq-bulk-source]').forEach((source) => {
    const count = source.parentElement.querySelector('[data-faq-bulk-count]');
    const summary = source.parentElement.querySelector('[data-faq-bulk-summary]');
    const update = () => {
        const n = source.value.trim() ? parseFaqs(source.value).length : 0;
        count.textContent = source.value.trim() ? (n ? t(':count soru bulundu', { count: n }) : t('Soru bulunamadı. Her soru kendi satırında, cevabı altında olmalı.')) : '';
        count.className = 'mt-2 text-sm font-semibold ' + (n ? 'text-emerald-700' : 'text-red-600');
        summary.value = n || '';
        summary.dataset.label = t(':count soru', { count: n });
        // soru bulunamazsa ileri geçilmesin
        source.setCustomValidity(source.value.trim() && !n ? t('Soru bulunamadı. Her soru kendi satırında, cevabı altında olmalı.') : '');
    };
    source.addEventListener('input', update);
    update();
});
