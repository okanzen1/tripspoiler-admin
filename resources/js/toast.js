// Ekranın altında kısa süre görünen bildirim

export function toast(message, warning = false) {
    let box = document.getElementById('toast');
    if (!box) {
        box = document.createElement('div');
        box.id = 'toast';
        box.setAttribute('role', 'status');
        box.className = 'pointer-events-none fixed bottom-6 left-4 right-4 z-50 mx-auto w-fit max-w-[calc(100vw-2rem)] rounded-xl px-4 py-2.5 text-sm font-medium text-white shadow-lg transition-opacity duration-300 break-words text-center';
        document.body.append(box);
    }
    box.textContent = message;
    box.classList.toggle('bg-brand-900', !warning);
    box.classList.toggle('bg-amber-600', warning);
    box.style.opacity = '1';
    clearTimeout(box.timer);
    box.timer = setTimeout(() => (box.style.opacity = '0'), warning ? 5000 : 2500);
}
