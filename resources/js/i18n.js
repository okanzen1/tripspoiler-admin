// Panel dilindeki JS metinleri: sözlük layout'ta window.i18n olarak gelir (Türkçe anahtar → çeviri)
export const t = (key, vars = {}) =>
    Object.entries(vars).reduce((text, [name, value]) => text.replaceAll(`:${name}`, value), window.i18n?.[key] ?? key);
