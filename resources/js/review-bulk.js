// Yorumlar › Toplu ekle: yapıştırılan satırlarda kaç yorum bulunduğu (ve Özet adımındaki sayı).
// Biçim (sunucudaki App\Support\ReviewParser ile aynı): "1. Ad | 4★ | Yorum | dil | 2026-07-29" — dil ve tarih isteğe bağlı.
import { t } from './i18n';

const clean = (line) => line.replace(/\*\*/g, '').replace(/[\s  -​ ﻿]+/g, ' ').trim();
const RATING = /^([1-5])(?:\s?(?:★|⭐|\*|\/\s?5|stars?|yıldız))?$/iu;
const DATE = /^\d{4}-\d{2}-\d{2}$/;
const LANG = /^[a-z]{2}(?:-[A-Za-z]{2})?$/;

function parseLine(line) {
    const parts = line.replace(/^\d+\s?[.)-]\s?/, '').split('|').map((p) => p.trim());
    if (parts.length < 3) return null;
    const name = parts.shift();
    const rating = parts.shift().match(RATING);
    if (!rating) return null;
    if (parts.length && DATE.test(parts[parts.length - 1]) && !Number.isNaN(Date.parse(parts[parts.length - 1]))) parts.pop();
    if (parts.length > 1 && LANG.test(parts[parts.length - 1])) parts.pop();
    const comment = parts.join(' | ').trim();
    return name && comment ? { name, rating: Number(rating[1]), comment } : null;
}

export function parseReviews(text) {
    const reviews = [];
    const invalid = [];
    text.replace(/\r\n?/g, '\n').split('\n').map(clean).forEach((line, i) => {
        if (!line) return;
        const review = parseLine(line);
        if (review) reviews.push(review);
        else invalid.push(i + 1);
    });
    return { reviews, invalid };
}

document.querySelectorAll('[data-review-bulk-source]').forEach((source) => {
    const count = source.parentElement.querySelector('[data-review-bulk-count]');
    const summary = source.parentElement.querySelector('[data-review-bulk-summary]');
    const update = () => {
        const filled = source.value.trim() !== '';
        const { reviews, invalid } = parseReviews(source.value);
        const n = reviews.length;
        const error = !filled ? '' : invalid.length
            ? t('Şu satırlar okunamadı: :lines. Her satır "Ad | 4★ | Yorum | dil | 2026-07-29" biçiminde olmalı.', { lines: invalid.slice(0, 10).join(', ') })
            : n ? '' : t('Yorum bulunamadı.');
        count.textContent = filled ? error || t(':count yorum bulundu', { count: n }) : '';
        count.className = 'mt-2 text-sm font-semibold ' + (error ? 'text-red-600' : 'text-emerald-700');
        summary.value = error ? '' : n || '';
        summary.dataset.label = t(':count yorum', { count: n });
        // okunamayan satır varsa ileri geçilmesin
        source.setCustomValidity(error);
    };
    source.addEventListener('input', update);
    update();
});
