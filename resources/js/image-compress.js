// Görselleri yüklemeden önce tarayıcıda küçültür: sunucunun sınırını (layout'taki <meta name="upload-limit">)
// aşan ya da gereğinden büyük (uzun kenarı 2560 px üstü) görseller WebP'ye çevrilip sıkıştırılır.
// Böylece "dosya çok büyük" hatası hiç çıkmaz; sunucu da gelen görseli ayrıca boyutlandırıp WebP kaydeder.
//
// Paneldeki tüm görsel alanlarına (input[type=file][accept*=image]) kendiliğinden uygulanır: değişiklik olayı
// yakalanır, dosyalar küçültülür ve olay yeniden gönderilir; önizleme gibi diğer betikler küçültülmüş dosyayı görür.
// Küçültme olunca input'a "image:compressed" olayı gider (detail: [{ name, before, after, width, height }]).

const MAX_EDGE = 2560;
const meta = document.querySelector('meta[name="upload-limit"]');
const fileLimit = Number(meta?.content) || 2 * 1024 * 1024;
const requestLimit = Number(meta?.dataset.request) || fileLimit * 4;

const load = (file) => new Promise((resolve, reject) => {
    const image = new Image();
    image.onload = () => resolve(image);
    image.onerror = reject;
    image.src = URL.createObjectURL(file);
});

const encode = (canvas, type, quality) => new Promise((resolve) => canvas.toBlob(resolve, type, quality));

/**
 * Tek görseli gerekiyorsa küçültür; gerekmiyorsa ya da okunamıyorsa aynı dosyayı döner.
 *
 * @param {File} file
 * @param {number} maxBytes hedef en büyük boyut
 * @returns {Promise<File>}
 */
export async function compressImage(file, maxBytes = fileLimit * 0.85) {
    if (!file.type.startsWith('image/') || file.type === 'image/gif' || file.type === 'image/svg+xml') return file;

    let image;
    try {
        image = await load(file);
    } catch {
        return file; // tarayıcı okuyamadı; sunucu karar versin
    }

    const longEdge = Math.max(image.naturalWidth, image.naturalHeight);
    if (file.size <= maxBytes && longEdge <= MAX_EDGE) return file;

    let scale = Math.min(1, MAX_EDGE / longEdge);
    const canvas = document.createElement('canvas');
    const context = canvas.getContext('2d');

    // Önce kaliteyi, yetmezse boyutu düşür
    for (let attempt = 0; attempt < 8; attempt++) {
        canvas.width = Math.round(image.naturalWidth * scale);
        canvas.height = Math.round(image.naturalHeight * scale);
        context.clearRect(0, 0, canvas.width, canvas.height);
        context.imageSmoothingQuality = 'high';
        context.drawImage(image, 0, 0, canvas.width, canvas.height);

        for (const quality of [0.86, 0.78, 0.7, 0.6]) {
            let blob = await encode(canvas, 'image/webp', quality);
            // WebP kodlayamayan eski tarayıcılar PNG döner; o durumda JPEG
            if (blob && blob.type !== 'image/webp') blob = await encode(canvas, 'image/jpeg', quality);
            if (blob && blob.size <= maxBytes) {
                const extension = blob.type === 'image/webp' ? 'webp' : 'jpg';
                return new File([blob], file.name.replace(/\.[^.]+$/, '') + '.' + extension, { type: blob.type, lastModified: Date.now() });
            }
        }
        scale *= 0.8;
    }

    return file;
}

/**
 * Birden çok dosya: toplamları da formun sınırını aşmasın diye her birine pay bölünür.
 *
 * @param {FileList|File[]} files
 * @returns {Promise<{ files: File[], changes: object[] }>}
 */
export async function compressImages(files) {
    const list = [...files];
    const share = Math.min(fileLimit, requestLimit / Math.max(1, list.length)) * 0.85;
    const changes = [];
    const result = [];

    for (const file of list) {
        const compressed = await compressImage(file, share);
        if (compressed !== file) changes.push({ name: file.name, before: file.size, after: compressed.size });
        result.push(compressed);
    }

    return { files: result, changes };
}

// Otomatik uygulama: kullanıcının seçtiği dosyalar küçültülür, sonra olay diğer betikler için yeniden gönderilir
const redispatched = new WeakSet();

document.addEventListener('change', async (event) => {
    const input = event.target;
    if (!(input instanceof HTMLInputElement) || input.type !== 'file' || !input.files?.length) return;
    if (!(input.accept || '').includes('image') || input.dataset.noCompress !== undefined) return;
    if (redispatched.has(event)) return;

    event.stopImmediatePropagation();
    input.dataset.compressing = '';
    input.closest('form')?.setAttribute('data-compressing', '');

    try {
        const { files, changes } = await compressImages(input.files);
        if (changes.length) {
            const transfer = new DataTransfer();
            files.forEach((file) => transfer.items.add(file));
            input.files = transfer.files;
            input.dispatchEvent(new CustomEvent('image:compressed', { bubbles: true, detail: changes }));
        }
    } finally {
        delete input.dataset.compressing;
        input.closest('form')?.removeAttribute('data-compressing');
        const again = new Event('change', { bubbles: true });
        redispatched.add(again);
        input.dispatchEvent(again);
    }
}, true);

// Küçültme bitmeden form gönderilirse bekle (büyük dosya sunucuya gitmesin)
document.addEventListener('submit', (event) => {
    const form = event.target;
    if (!form.hasAttribute('data-compressing')) return;
    event.preventDefault();
    const wait = setInterval(() => {
        if (!form.hasAttribute('data-compressing')) {
            clearInterval(wait);
            form.requestSubmit(event.submitter ?? undefined);
        }
    }, 150);
}, true);
