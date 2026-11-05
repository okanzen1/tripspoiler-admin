// Listede URL adını (slug) doğrudan düzenleme: Enter'a basınca ya da alandan çıkınca kaydeder.
import { slugify } from './slug';
import { toast } from './toast';

document.querySelectorAll('form[data-inline-slug]').forEach((form) => {
    const input = form.elements.slug;
    let saved = input.value;

    const save = async () => {
        const value = slugify(input.value);
        if (value === saved) {
            input.value = saved;
            return;
        }

        input.value = value;
        const body = new FormData(form); // devre dışı alanlar forma girmez: önce oku, sonra kilitle
        input.disabled = true;
        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body,
            });
            const data = await response.json();
            if (!response.ok) throw new Error(data.errors?.slug?.[0] ?? 'Kaydedilemedi.');

            saved = input.value = data.slug;
            form.classList.add('saved');
            setTimeout(() => form.classList.remove('saved'), 1200);
            toast(data.message);
        } catch (error) {
            input.value = saved;
            toast(error.message, true);
        } finally {
            input.disabled = false;
        }
    };

    form.addEventListener('submit', (e) => {
        e.preventDefault();
        input.blur();
    });
    input.addEventListener('blur', save);
    input.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            input.value = saved;
            input.blur();
        }
    });
});
