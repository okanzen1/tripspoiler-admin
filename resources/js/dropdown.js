// Açılır menüler (<details data-dropdown>): dışarı tıklayınca ya da Esc ile kapanır.
document.addEventListener('click', (e) => {
    document.querySelectorAll('details[data-dropdown][open]').forEach((menu) => {
        if (!menu.contains(e.target)) menu.removeAttribute('open');
    });
});

document.addEventListener('keydown', (e) => {
    if (e.key !== 'Escape') return;
    document.querySelectorAll('details[data-dropdown][open]').forEach((menu) => {
        menu.removeAttribute('open');
        menu.querySelector('summary')?.focus();
    });
});
