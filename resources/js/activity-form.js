// Aktivite formu: görsel önizleme (sürükle-bırak), SEO karakter sayaçları ve Google önizlemesi.
import { t } from './i18n';

document.querySelectorAll('[data-image-input]').forEach((input) => {
    const zone = input.closest('[data-drop-zone]');
    const previews = input.closest('section').querySelector('[data-image-previews]');

    const render = () => {
        previews.replaceChildren(...[...input.files].map((file) => {
            const card = document.createElement('div');
            card.className = 'overflow-hidden rounded-2xl bg-white ring-1 ring-brand-200';
            card.innerHTML = '<img class="aspect-[4/3] w-full object-cover" alt="" /><p class="truncate px-3 py-2 text-xs font-medium text-brand-900"></p>';
            card.querySelector('img').src = URL.createObjectURL(file);
            card.querySelector('p').textContent = `${t('Yeni')} · ${file.name}`;
            return card;
        }));
    };

    input.addEventListener('change', render);

    ['dragenter', 'dragover'].forEach((type) => zone.addEventListener(type, (e) => {
        e.preventDefault();
        zone.classList.add('border-brand-900', 'bg-brand-50/40');
    }));
    ['dragleave', 'drop'].forEach((type) => zone.addEventListener(type, () => zone.classList.remove('border-brand-900', 'bg-brand-50/40')));
    zone.addEventListener('drop', (e) => {
        e.preventDefault();
        const files = new DataTransfer();
        [...e.dataTransfer.files].filter((f) => f.type.startsWith('image/')).forEach((f) => files.items.add(f));
        input.files = files.files;
        // change olayı: görseller önce küçültülür (image-compress.js), sonra önizlenir
        input.dispatchEvent(new Event('change', { bubbles: true }));
    });
});

// Karakter sayacı: önerilen uzunluğu aşınca turuncuya döner
document.querySelectorAll('[data-counter-for]').forEach((counter) => {
    const field = document.getElementById(counter.dataset.counterFor);
    const limit = Number(counter.dataset.limit);
    const update = () => {
        const length = field.value.length;
        counter.textContent = `${length} / ${limit}`;
        counter.classList.toggle('text-amber-600', length > limit);
        counter.classList.toggle('text-slate-400', length <= limit);
    };
    field.addEventListener('input', update);
    update();
});

// Google önizlemesi: SEO başlığı boşsa sayfanın adı (name/title alanı), adres için slug alanı kullanılır
document.querySelectorAll('[data-seo-preview]').forEach((preview) => {
    const form = preview.closest('form');
    const { slug, meta_title: title, meta_description: description } = form.elements;
    const name = form.elements.name ?? form.elements.title;
    const slugTarget = preview.querySelector('[data-seo-slug]');

    const update = () => {
        preview.querySelector('[data-seo-title]').textContent = title.value || name?.value || preview.dataset.seoFallback;
        preview.querySelector('[data-seo-description]').textContent = description.value || t('Açıklama…');
        if (slugTarget) slugTarget.textContent = slug?.value || slug?.placeholder || '…';
    };
    [name, slug, title, description].filter(Boolean).forEach((el) => el.addEventListener('input', update));
    update();
});

// Seçmeli liste (ör. blog yazısının ilgili aktiviteleri): arama ve seçili sayısı
document.querySelectorAll('[data-activity-picker]').forEach((picker) => {
    const boxes = [...picker.querySelectorAll('input[type="checkbox"]')];
    const search = picker.querySelector('[data-picker-search]');
    const form = picker.closest('form');

    const count = () => form.querySelectorAll('[data-picked-count]').forEach((el) => (el.textContent = boxes.filter((b) => b.checked).length));

    search?.addEventListener('input', () => {
        const q = search.value.trim().toLocaleLowerCase('tr');
        boxes.forEach((box) => (box.closest('li').hidden = q !== '' && !box.closest('li').dataset.search.includes(q)));
    });
    boxes.forEach((box) => box.addEventListener('change', count));
});
