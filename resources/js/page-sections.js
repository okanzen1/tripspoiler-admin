// Sayfa bölümleri listesi: tutamaçtan sürükleyerek sıra, anahtarla aç/kapa. Her değişiklik hemen kaydedilir.
//
// <ul data-page-sections data-url="…"> <li data-section="reviews"> <span data-drag-handle draggable="true"> … <button role="switch" data-section-switch> </li> </ul>
// Sabit bölümler (duyuru, partner) ayrı listede: <ul data-page-sections-fixed> (sürüklenmez)

import { t } from './i18n';
import { toast } from './toast';

document.querySelectorAll('[data-page-sections]').forEach((list) => {
    const root = list.closest('[data-page-sections-root]') ?? document;
    let dragged = null;

    const state = () => ({
        order: [...list.querySelectorAll(':scope > [data-section]')].map((row) => row.dataset.section),
        on: [...root.querySelectorAll('[data-section-switch][aria-checked="true"]')].map((button) => button.closest('[data-section]').dataset.section),
    });

    const save = async () => {
        try {
            const response = await fetch(list.dataset.url, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                },
                body: JSON.stringify(state()),
            });
            if (!response.ok) throw new Error();
            toast((await response.json()).message);
        } catch {
            toast(t('Kaydedilemedi, tekrar dene.'), true);
        }
    };

    const renumber = () => list.querySelectorAll(':scope > [data-section] [data-position]').forEach((el, i) => (el.textContent = i + 1));

    // Aç/kapa
    root.querySelectorAll('[data-section-switch]').forEach((button) => {
        button.addEventListener('click', () => {
            const on = button.getAttribute('aria-checked') !== 'true';
            button.setAttribute('aria-checked', on ? 'true' : 'false');
            button.closest('[data-section]').classList.toggle('opacity-60', !on);
            save();
        });
    });

    // Sürükle bırak (sadece tutamaçtan)
    list.querySelectorAll('[data-drag-handle]').forEach((handle) => {
        const row = handle.closest('[data-section]');
        handle.addEventListener('dragstart', (event) => {
            dragged = row;
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setDragImage(row, 20, 20);
            requestAnimationFrame(() => row.classList.add('opacity-40', 'ring-2', 'ring-brand-900/30'));
        });
        handle.addEventListener('dragend', () => {
            row.classList.remove('opacity-40', 'ring-2', 'ring-brand-900/30');
            dragged = null;
            renumber();
            save();
        });
    });

    list.addEventListener('dragover', (event) => {
        if (!dragged) return;
        event.preventDefault();
        const target = event.target.closest('[data-section]');
        if (!target || target === dragged || target.parentElement !== list) return;
        const rect = target.getBoundingClientRect();
        event.clientY < rect.top + rect.height / 2 ? target.before(dragged) : target.after(dragged);
    });

    // Klavye: tutamaçta ↑/↓
    list.querySelectorAll('[data-drag-handle]').forEach((handle) => {
        handle.addEventListener('keydown', (event) => {
            const row = handle.closest('[data-section]');
            if (event.key === 'ArrowUp' && row.previousElementSibling) row.previousElementSibling.before(row);
            else if (event.key === 'ArrowDown' && row.nextElementSibling) row.nextElementSibling.after(row);
            else return;
            event.preventDefault();
            handle.focus();
            renumber();
            save();
        });
    });
});
