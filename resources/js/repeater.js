// Tekrarlanan satırlar (ör. aktivite SSS'leri): ekle, sil, yukarı/aşağı taşı.
// Alan adları her değişiklikte sırayla yeniden numaralanır: prefix[0][question], prefix[1][question]…
// Böylece sunucu satırların ekrandaki sırasını alır.
import { ask } from './confirm-dialog';
import { t } from './i18n';

document.querySelectorAll('[data-repeater]').forEach((box) => {
    const prefix = box.dataset.repeater;
    const list = box.querySelector('[data-repeater-list]');
    const template = box.querySelector('template[data-repeater-template]');
    const empty = box.querySelector('[data-repeater-empty]');
    const max = Number(box.dataset.max) || Infinity;
    const add = box.querySelector('[data-repeater-add]');

    const renumber = () => {
        const rows = [...list.querySelectorAll('[data-repeater-row]')];
        rows.forEach((row, i) => {
            row.querySelectorAll('[data-field]').forEach((el) => (el.name = `${prefix}[${i}][${el.dataset.field}]`));
            row.querySelector('[data-repeater-number]').textContent = i + 1;
            row.querySelector('[data-repeater-up]').disabled = i === 0;
            row.querySelector('[data-repeater-down]').disabled = i === rows.length - 1;
        });
        empty?.classList.toggle('hidden', rows.length > 0);
        add.disabled = rows.length >= max;
        box.dispatchEvent(new CustomEvent('repeater:change', { detail: { count: rows.length } }));
    };

    add.addEventListener('click', () => {
        list.append(template.content.cloneNode(true));
        renumber();
        list.lastElementChild.querySelector('[data-field]')?.focus();
    });

    list.addEventListener('click', async (e) => {
        const row = e.target.closest('[data-repeater-row]');
        if (!row) return;

        if (e.target.closest('[data-repeater-remove]')) {
            const hasText = [...row.querySelectorAll('[data-field]')].some((el) => !['hidden', 'checkbox', 'radio'].includes(el.type) && el.tagName !== 'SELECT' && el.value.trim() !== '');
            if (hasText && !(await ask({ message: box.dataset.removeConfirm || t('Bu satır silinsin mi? Kaydettiğinde kalıcı olarak silinir.'), tone: 'danger' }))) return;
            row.remove();
        } else if (e.target.closest('[data-repeater-up]')) {
            row.previousElementSibling?.before(row);
        } else if (e.target.closest('[data-repeater-down]')) {
            row.nextElementSibling?.after(row);
        } else {
            return;
        }
        renumber();
    });

    renumber();
});
