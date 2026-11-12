// Aktivite formu: görsel önizleme ve sürükle-bırak sıralama, SEO karakter sayaçları ve Google önizlemesi.
import { t } from './i18n';

document.querySelectorAll('[data-image-input]').forEach((input) => {
    const zone = input.closest('[data-drop-zone]');
    const section = input.closest('section');
    const template = section.querySelector('[data-new-image-template]');
    const hint = section.querySelector('[data-image-hint]');
    const form = input.closest('form');
    // Izgara kaydettikten sonra sunucudaki haliyle değişir (data-live); bu yüzden olaylar section'a bağlı ve ızgara her seferinde aranır
    const grid = () => section.querySelector('[data-image-grid]');
    const cards = () => [...grid().children];
    // Yeni (henüz yüklenmemiş) kartların dosyaları
    const fileOf = new WeakMap();

    // Yeni dosyalar ekrandaki sırayla input'ta dursun (seçimler birikir)
    const syncInput = () => {
        const files = new DataTransfer();
        cards().filter((card) => fileOf.has(card)).forEach((card) => files.items.add(fileOf.get(card)));
        input.files = files.files;
    };

    // Sıra numaraları ve "Kapak" rozeti (silinmek üzere işaretlenenler sayılmaz)
    const refresh = () => {
        let number = 0;
        cards().forEach((card) => {
            const removed = !!card.querySelector('[data-remove]')?.checked;
            if (!removed) number++;
            const badge = card.querySelector('[data-image-number]');
            badge.textContent = removed ? '' : number;
            badge.hidden = removed;
            card.querySelector('[data-cover-badge]').hidden = removed || number !== 1;
            if (!fileOf.has(card)) card.querySelector('[data-make-cover]').hidden = removed || number === 1;
        });
        if (hint) hint.hidden = cards().length < 2;
    };

    input.addEventListener('change', () => {
        const known = new Set(cards().filter((card) => fileOf.has(card)).map((card) => fileOf.get(card)));
        [...input.files].filter((file) => !known.has(file)).forEach((file) => {
            const card = template.content.firstElementChild.cloneNode(true);
            card.querySelector('img').src = URL.createObjectURL(file);
            card.querySelector('[data-new-name]').textContent = `${t('Yeni')} · ${file.name}`;
            fileOf.set(card, file);
            grid().appendChild(card);
        });
        syncInput();
        refresh();
    });

    section.addEventListener('click', (e) => {
        const card = e.target.closest('[data-image-grid] > *');
        if (!card) return;
        if (e.target.closest('[data-make-cover]')) grid().prepend(card);
        else if (e.target.closest('[data-drop-new]')) card.remove();
        else return;
        syncInput();
        refresh();
    });
    section.addEventListener('change', (e) => e.target.matches('[data-remove]') && refresh());

    // Sürükle-bırak sıralama
    let dragged = null;
    section.addEventListener('dragstart', (e) => {
        dragged = e.target.closest?.('[data-image-grid] > *') ?? null;
        if (!dragged) return;
        e.dataTransfer.effectAllowed = 'move';
        const card = dragged;
        requestAnimationFrame(() => card.classList.add('opacity-40', 'ring-2', 'ring-brand-900'));
    });
    section.addEventListener('dragend', () => {
        if (!dragged) return;
        dragged.classList.remove('opacity-40', 'ring-2', 'ring-brand-900');
        dragged = null;
        syncInput();
        refresh();
    });
    section.addEventListener('dragover', (e) => {
        if (!dragged) return;
        e.preventDefault();
        const target = e.target.closest('[data-image-grid] > *');
        if (!target || target === dragged) return;
        const box = target.getBoundingClientRect();
        target.parentNode.insertBefore(dragged, (e.clientX - box.left) / box.width > 0.5 ? target.nextSibling : target);
    });
    section.addEventListener('drop', (e) => dragged && e.preventDefault());

    // Gönderilen veriye sıra eklenir ("id:5" mevcut görsel, "new:0" ilk yeni dosya). Panel formları
    // sayfa yenilemeden (FormData ile) kaydettiği için submit yerine formdata olayı kullanılır.
    form.addEventListener('formdata', (e) => {
        e.formData.delete('image_order[]');
        let fresh = 0;
        cards().forEach((card) => e.formData.append('image_order[]', fileOf.has(card) ? `new:${fresh++}` : card.dataset.imageCard));
    });

    // Kaydettikten sonra ızgara sunucudan yeniden gelir: numaraları yeniden yaz
    document.addEventListener('live:refreshed', refresh);

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

    refresh();
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
