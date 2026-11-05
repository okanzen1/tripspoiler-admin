// Adım adım form (x-admin.wizard bileşeni ile birlikte çalışır)
import { t } from './i18n';

function fieldsOf(step) {
    return [...step.querySelectorAll('input, select, textarea')].filter((el) => el.type !== 'hidden');
}

function summaryValue(form, name) {
    const fields = [...form.elements].filter((el) => el.name === name && !el.disabled);
    const field = fields.find((el) => (el.type === 'radio' || el.type === 'checkbox' ? el.checked : true));

    if (field?.type === 'file') {
        if (field.multiple) return field.files.length ? t(":count yeni görsel", { count: field.files.length }) : '—';
        return field.files[0]?.name ?? '—';
    }
    if (field?.type === 'checkbox') return field.dataset.label ?? t('Evet');
    // Boş slug alanı: otomatik üretilecek değeri göster
    if (field?.value === '' && field.dataset.slugFrom && field.placeholder) return `${field.placeholder} (${t('otomatik')})`;
    if (!field || field.value === '') return '—';
    if (field.type === 'hidden' && field.dataset.label) return field.dataset.label;
    if (field.type === 'password') return '•'.repeat(8);
    if (field.type === 'radio') return field.dataset.label ?? field.value;
    if (field.tagName === 'SELECT') return field.selectedOptions[0]?.textContent ?? field.value;

    return field.value;
}

function initWizard(form) {
    const steps = [...form.querySelectorAll('[data-step]')];
    const indicators = [...form.querySelectorAll('[data-step-indicator]')];
    const prev = form.querySelector('[data-wizard-prev]');
    const next = form.querySelector('[data-wizard-next]');
    const submit = form.querySelector('[data-wizard-submit]');
    let current = Math.min(Number(form.dataset.start) || 0, steps.length - 1);
    // Düzenleme modu: her adıma geçilebilir, "Kaydet" her zaman görünür
    const free = form.dataset.free === '1';

    const isValid = (index) => fieldsOf(steps[index]).every((el) => el.reportValidity());

    const show = (index, focus = true) => {
        current = index;
        const last = index === steps.length - 1;

        steps.forEach((step, i) => step.toggleAttribute('hidden', i !== index));
        indicators.forEach((el, i) => {
            el.dataset.state = i < index ? 'done' : i === index ? 'current' : 'upcoming';
        });

        prev.classList.toggle('invisible', index === 0);
        next.toggleAttribute('hidden', last);
        submit.toggleAttribute('hidden', !last && !free);

        if (last) {
            form.querySelectorAll('[data-summary-for]').forEach((el) => {
                el.textContent = summaryValue(form, el.dataset.summaryFor);
            });
        }

        if (focus) fieldsOf(steps[index]).find((el) => !el.disabled)?.focus();
    };

    next.addEventListener('click', () => isValid(current) && show(current + 1));
    prev.addEventListener('click', () => show(current - 1));

    // Tamamlanmış adımlara geri dönülebilir (düzenleme modunda her adıma)
    indicators.forEach((el, i) => {
        el.querySelector('button').addEventListener('click', () => (free || i < current) && show(i));
    });

    // Enter son adıma kadar formu göndermesin, ileri geçsin
    form.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && e.target.tagName === 'INPUT' && current < steps.length - 1) {
            e.preventDefault();
            next.click();
        }
    });

    // Son adımda tüm adımları tekrar doğrula
    form.addEventListener('submit', (e) => {
        const invalid = steps.findIndex((_, i) => !fieldsOf(steps[i]).every((el) => el.checkValidity()));
        if (invalid !== -1) {
            e.preventDefault();
            show(invalid);
            isValid(invalid);
        }
    });

    show(current, false);
}

document.querySelectorAll('[data-wizard]').forEach(initWizard);
