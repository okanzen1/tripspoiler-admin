// Resim yükleme alanı (x-admin.image-upload): seçilen dosyayı önizler, "Kaldır" ile siler.
import { t } from './i18n';

document.querySelectorAll('[data-image-upload]').forEach((box) => {
    const input = box.querySelector('input[type="file"]');
    const preview = box.querySelector('[data-preview]');
    const placeholder = box.querySelector('[data-placeholder]');
    const remove = box.querySelector('[data-remove]');
    const flag = box.querySelector('[data-remove-flag]');
    const chooseLabel = box.querySelector('[data-choose-label]');
    const focusInput = box.querySelector('[data-image-focus]');

    const applyFocus = () => {
        if (focusInput) preview.style.objectPosition = focusInput.value + '% center';
    };
    if (focusInput) focusInput.addEventListener('input', applyFocus);
    applyFocus();

    const show = (src) => {
        preview.src = src ?? '';
        preview.classList.toggle('hidden', !src);
        placeholder.classList.toggle('hidden', !!src);
        remove.classList.toggle('hidden', !src);
        box.querySelector('[data-change-chip]')?.classList.toggle('hidden', !src);
        chooseLabel.textContent = t(src ? 'Değiştir' : 'Resim seç');
        applyFocus();
    };

    input.addEventListener('change', () => {
        const file = input.files[0];
        if (!file) return;
        flag.value = '0';
        show(URL.createObjectURL(file));
    });

    // Geniş alan (shape="wide"): resim üstüne sürükleyip bırakma
    const drop = box.querySelector('[data-drop]');
    if (drop) {
        ['dragenter', 'dragover'].forEach((type) => drop.addEventListener(type, (e) => {
            if (![...(e.dataTransfer?.types ?? [])].includes('Files')) return;
            e.preventDefault();
            drop.dataset.over = '';
        }));
        ['dragleave', 'drop'].forEach((type) => drop.addEventListener(type, () => delete drop.dataset.over));
        drop.addEventListener('drop', (e) => {
            const file = [...(e.dataTransfer?.files ?? [])].find((f) => f.type.startsWith('image/'));
            if (!file) return;
            e.preventDefault();
            const transfer = new DataTransfer();
            transfer.items.add(file);
            input.files = transfer.files;
            input.dispatchEvent(new Event('change', { bubbles: true })); // küçültme (image-compress.js) + önizleme
        });
    }

    remove.addEventListener('click', () => {
        input.value = '';
        flag.value = '1';
        show(null);
    });
});
