// E-posta sayfaları: liste seçimi, gövde iframe'i, yazma formu, hazır yanıtlar, toplu e-posta alıcı sayısı.
import { ask, optionsFor } from './confirm-dialog';
import { t } from './i18n';

// Liste: seçim varsa işlem çubuğu görünür; butonlar formun yöntemini (PATCH/POST/DELETE) belirler
document.querySelectorAll('form[data-mail-list]').forEach((form) => {
    const boxes = [...form.querySelectorAll('input[name="uids[]"]')];
    const all = form.querySelector('[data-select-all]');
    const bar = form.querySelector('[data-selection-bar]');
    const method = form.querySelector('[data-method]');

    const sync = () => {
        const count = boxes.filter((box) => box.checked).length;
        bar?.classList.toggle('hidden', count === 0);
        bar?.classList.toggle('flex', count > 0);
        if (all) {
            all.checked = count > 0 && count === boxes.length;
            all.indeterminate = count > 0 && count < boxes.length;
        }
    };

    boxes.forEach((box) => box.addEventListener('change', sync));
    all?.addEventListener('change', () => {
        boxes.forEach((box) => (box.checked = all.checked));
        sync();
    });

    form.querySelectorAll('[data-method-value]').forEach((button) => {
        button.addEventListener('click', async (e) => {
            if (button.dataset.confirmClick && !button.dataset.confirmed) {
                e.preventDefault();
                if (!(await ask({ ...optionsFor(button, form), tone: 'danger' }))) return;
                button.dataset.confirmed = '1';
                button.click();
                delete button.dataset.confirmed;
                return;
            }
            method.value = button.dataset.methodValue;
        });
    });
});

// Gövde: iframe yüksekliğini içeriğe göre ayarla; "görselleri göster"
document.querySelectorAll('[data-mail-body]').forEach((box) => {
    const frame = box.querySelector('iframe');

    window.addEventListener('message', (e) => {
        const height = e.data?.mailboxBodyHeight;
        if (e.source === frame.contentWindow && Number.isFinite(height)) {
            frame.style.height = `${Math.min(Math.max(height, 80), 20000)}px`;
        }
    });

    box.querySelector('[data-show-images]')?.addEventListener('click', (e) => {
        frame.src = e.currentTarget.dataset.showImages;
        box.querySelector('[data-images-notice]')?.remove();
    });
});

// Yazma formu: CC/BCC alanları, imleci metnin başına koy, gönderirken butonu kilitle
document.querySelector('[data-show-cc]')?.addEventListener('click', (e) => {
    document.querySelector('[data-cc-fields]')?.classList.remove('hidden');
    e.currentTarget.classList.add('hidden');
});

const body = document.querySelector('textarea[data-autofocus-start]');
if (body && !body.closest('[data-step][hidden]')) {
    body.focus();
    body.setSelectionRange(0, 0);
    body.scrollTop = 0;
}

document.querySelectorAll('form[data-sending-form]').forEach((form) => {
    form.addEventListener('submit', () => {
        const button = form.querySelector('[data-submit-label]');
        if (!button) return;
        button.disabled = true;
        button.querySelector('span').textContent = button.dataset.submitLabel;
    });
});

// Hazır yanıt: seçilen metni imlecin olduğu yere ekle
document.querySelectorAll('select[data-template-picker]').forEach((select) => {
    const templates = JSON.parse(select.dataset.templates);
    select.addEventListener('change', () => {
        const target = document.getElementById(select.dataset.templatePicker);
        const text = templates[select.value];
        if (target && text) {
            const at = target.selectionStart ?? 0;
            target.setRangeText(text, at, target.selectionEnd ?? at, 'end');
            target.focus();
        }
        select.value = '';
    });
});

// Hazır yanıtlar sayfası: satır içi düzenleme
document.querySelectorAll('[data-editable]').forEach((item) => {
    item.querySelectorAll('[data-edit-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            item.querySelector('[data-edit]').classList.toggle('hidden');
            item.querySelector('[data-view]').classList.toggle('hidden');
        });
    });
});

// Toplu e-posta: abone seçimi, arama, alıcı sayısı ve "en az bir alıcı" kontrolü
document.querySelectorAll('[data-bulk-form]').forEach((wrapper) => {
    const form = wrapper.querySelector('form');
    const boxes = [...form.querySelectorAll('input[name="subscriber_ids[]"]')];
    const all = form.querySelector('[data-select-all-subscribers]');
    const search = form.querySelector('[data-subscriber-search]');
    const extra = form.elements.extra_emails;

    const selectedEmails = () => new Set(boxes.filter((b) => b.checked).map((b) => b.closest('li').dataset.email));

    const update = () => {
        const selected = selectedEmails();
        // Ek adreslerden zaten seçili abone olanlar ikinci kez sayılmaz (sunucu da aynısını yapar)
        const extras = new Set(extra.value.split(/[\s,;]+/).map((a) => a.trim().toLowerCase())
            .filter((a) => /^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(a) && !selected.has(a)));
        const total = selected.size + extras.size;

        wrapper.querySelectorAll('[data-recipient-count]').forEach((el) => (el.textContent = total));
        wrapper.querySelectorAll('[data-selected-subscribers]').forEach((el) => (el.textContent = selected.size));
        if (all) {
            all.checked = boxes.length > 0 && selected.size === boxes.length;
            all.indeterminate = selected.size > 0 && selected.size < boxes.length;
        }
        extra.setCustomValidity(total === 0 ? t('En az bir abone seç ya da bir adres yaz.') : '');
    };

    // "Tümünü seç" sadece aramada görünen satırları etkiler
    all?.addEventListener('change', () => {
        boxes.filter((b) => !b.closest('li').hidden).forEach((b) => (b.checked = all.checked));
        update();
    });

    search?.addEventListener('input', () => {
        const q = search.value.trim().toLowerCase();
        boxes.forEach((b) => (b.closest('li').hidden = q !== '' && !b.closest('li').dataset.email.includes(q)));
    });

    boxes.forEach((b) => b.addEventListener('change', update));
    extra.addEventListener('input', update);
    update();
});
