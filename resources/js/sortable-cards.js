// Kart ızgaraları: tutamaçtan sürükleyerek sıralama ve arama. Sıra sunucuya kaydedilir (Setting).
//
// <div data-sortable-grid data-order-key="site_settings_order" data-order-url="…">
//     <a data-card="hero" data-search="…"> … <span data-drag-handle draggable="true">…</span> … </a>
// </div>
// <input data-card-search="site_settings_order">   (isteğe bağlı arama)
// <span data-order-saved="site_settings_order" class="hidden">✓</span>   (isteğe bağlı "kaydedildi")
// <p data-card-empty="site_settings_order" class="hidden">…</p>   (isteğe bağlı "sonuç yok")

const normalize = (text) => String(text).toLocaleLowerCase('tr').replace(/ı/g, 'i').normalize('NFD').replace(/[̀-ͯ]/g, '');

document.querySelectorAll('[data-sortable-grid]').forEach((grid) => {
    const key = grid.dataset.orderKey;
    const cards = () => [...grid.querySelectorAll(':scope > [data-card]')];
    let dragged = null;

    // Kaydedilmiş sıra (data-initial-order="hero,steps,…"); listede olmayan kartlar sonda kalır
    const initial = (grid.dataset.initialOrder ?? '').split(',').filter(Boolean);
    if (initial.length) {
        cards().sort((a, b) => (initial.indexOf(a.dataset.card) + 1 || 999) - (initial.indexOf(b.dataset.card) + 1 || 999))
            .forEach((card) => grid.appendChild(card));
    }

    const save = () => fetch(grid.dataset.orderUrl, {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
        },
        body: JSON.stringify({ key, order: cards().map((card) => card.dataset.card) }),
    }).then((response) => {
        const note = document.querySelector(`[data-order-saved="${key}"]`);
        if (!response.ok || !note) return;
        note.classList.remove('hidden');
        clearTimeout(save.timer);
        save.timer = setTimeout(() => note.classList.add('hidden'), 2000);
    });

    grid.querySelectorAll('[data-drag-handle]').forEach((handle) => {
        // Tutamaca tıklamak kartın bağlantısını açmasın
        handle.addEventListener('click', (e) => e.preventDefault());
        handle.addEventListener('dragstart', (e) => {
            const card = (dragged = handle.closest('[data-card]'));
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setDragImage(card, 30, 30);
            requestAnimationFrame(() => dragged === card && card.classList.add('opacity-40'));
        });
        handle.addEventListener('dragend', () => {
            dragged?.classList.remove('opacity-40');
            dragged = null;
            save();
        });
    });

    grid.addEventListener('dragover', (e) => {
        if (!dragged) return;
        e.preventDefault();
        const target = e.target.closest('[data-card]');
        if (!target || target === dragged || target.parentNode !== grid) return;
        const box = target.getBoundingClientRect();
        const after = (e.clientY - box.top) / box.height > 0.5 || (e.clientX - box.left) / box.width > 0.5;
        grid.insertBefore(dragged, after ? target.nextSibling : target);
    });
    grid.addEventListener('drop', (e) => e.preventDefault());

    // Arama: başlık ve açıklamada (Türkçe karakter fark etmez)
    const search = document.querySelector(`[data-card-search="${key}"]`);
    search?.addEventListener('input', () => {
        const words = normalize(search.value).split(/\s+/).filter(Boolean);
        let shown = 0;
        cards().forEach((card) => {
            const ok = words.every((w) => normalize(card.dataset.search ?? card.textContent).includes(w));
            card.classList.toggle('hidden', !ok);
            shown += ok;
        });
        document.querySelector(`[data-card-empty="${key}"]`)?.classList.toggle('hidden', shown > 0);
    });
});
