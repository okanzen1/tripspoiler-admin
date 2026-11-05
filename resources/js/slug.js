// URL adı (slug) alanı: boşken kaynak alandan üretilen değeri placeholder olarak gösterir.

const map = { ç: 'c', ğ: 'g', ı: 'i', İ: 'i', ö: 'o', ş: 's', ü: 'u', Ç: 'c', Ğ: 'g', Ö: 'o', Ş: 's', Ü: 'u' };

export function slugify(text, separator = '-') {
    return text
        .replace(/[çğıİöşüÇĞÖŞÜ]/g, (c) => map[c])
        .normalize('NFD')
        .replace(/[̀-ͯ]/g, '')
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, separator)
        .replace(/^-+|-+$/g, '');
}

document.querySelectorAll('input[data-slug-from]').forEach((input) => {
    const source = input.form?.elements[input.dataset.slugFrom];
    if (!source) return;

    const separator = input.dataset.slugSeparator ?? '-';
    const sync = () => (input.placeholder = slugify(source.value, separator) || '');
    source.addEventListener('input', sync);
    sync();
});
