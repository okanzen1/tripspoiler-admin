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
        chooseLabel.textContent = t(src ? 'Değiştir' : 'Resim seç');
        applyFocus();
    };

    input.addEventListener('change', () => {
        const file = input.files[0];
        if (!file) return;
        flag.value = '0';
        show(URL.createObjectURL(file));
    });

    remove.addEventListener('click', () => {
        input.value = '';
        flag.value = '1';
        show(null);
    });
});
