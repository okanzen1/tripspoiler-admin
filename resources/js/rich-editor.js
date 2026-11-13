// Zengin metin editörü (Quill). [data-rich-editor="alan"] içindeki div editöre dönüşür;
// içerik aynı formdaki gizli input[name=alan] ile eşitlenir. Sadece editör olan sayfalarda yüklenir.
// Görseller (araç çubuğu, yapıştırma, sürükle-bırak) data-upload-url'e yüklenir; içeriğe sadece adresi girer.
// data-word-paste: Word'den yapıştırınca listeler gerçek listeye döner, gömülü resimler (RTF / base64) yüklenir.
// data-image-sizes: resme tıklayınca Küçük / Orta / Tam boyut seçilir (width="33%|50%|100%").
import { compressImage } from './image-compress';
import { t } from './i18n';
import { toast } from './toast';
import Quill from 'quill';
import 'quill/dist/quill.snow.css';

const Delta = Quill.import('delta');

const IMAGE_SIZES = [['33%', 'Küçük'], ['50%', 'Orta'], ['100%', 'Tam']];

/**
 * Word'ün panoya koyduğu RTF içindeki resimler (sırasıyla). Word her resmi bir de WMF olarak koyar; onlar atlanır.
 * Yöntem: {\pict … \bliptag… hex} grupları; tür \pngblip / \jpegblip.
 *
 * @returns {Blob[]}
 */
function rtfImages(rtf) {
    if (!rtf) return [];
    const header = /{\\pict[\s\S]+?\\bliptag-?\d+(\\blipupi-?\d+)?({\\\*\\blipuid\s?[\da-fA-F]+)?[\s}]*?/;
    const picture = new RegExp('(?:(' + header.source + '))([\\da-fA-F\\s]+)\\}', 'g');

    return (rtf.match(picture) ?? []).map((group) => {
        const type = group.includes('\\pngblip') ? 'image/png' : group.includes('\\jpegblip') ? 'image/jpeg' : null;
        if (!type) return null;
        const hex = group.replace(header, '').replace(/[^\da-fA-F]/g, '');
        const bytes = new Uint8Array(hex.length / 2);
        for (let i = 0; i < bytes.length; i++) bytes[i] = parseInt(hex.substr(i * 2, 2), 16);

        return new Blob([bytes], { type });
    }).filter(Boolean);
}

async function dataUrlToBlob(src) {
    return (await fetch(src)).blob();
}

document.querySelectorAll('[data-rich-editor]').forEach((box) => {
    const form = box.closest('form');
    const input = form.querySelector(`input[name="${box.dataset.richEditor}"]`);
    const uploadUrl = box.dataset.uploadUrl;

    const upload = async (file) => {
        if (!(file instanceof File)) {
            file = new File([file], 'yapistirilan.' + (file.type.split('/')[1] || 'png'), { type: file.type || 'image/png' });
        }
        const body = new FormData();
        body.append('image', await compressImage(file));
        body.append('_token', form.querySelector('input[name="_token"]').value);

        const response = await fetch(uploadUrl, { method: 'POST', body, headers: { Accept: 'application/json' } });
        if (!response.ok) throw new Error();

        return (await response.json()).url;
    };

    const insertImages = async (index, files) => {
        for (const file of [...files].filter((f) => f.type.startsWith('image/'))) {
            try {
                toast(t('Görsel yükleniyor…'));
                const url = await upload(file);
                quill.insertEmbed(index, 'image', url, 'user');
                quill.setSelection(++index, 0);
            } catch {
                toast(t('Görsel yüklenemedi (en fazla 5 MB; JPG, PNG, WebP).'), true);
            }
        }
    };

    const quill = new Quill(box.querySelector('div'), {
        theme: 'snow',
        placeholder: box.dataset.placeholder || t('Yazmaya başla…'),
        // Yalnızca araç çubuğundaki biçimler: başka yerden (sohbet, web sayfası, Word) yapıştırılan arka plan rengi,
        // yazı rengi, yazı tipi ve boyutu editöre hiç girmez; başlıklar, kalın, liste ve bağlantılar kalır
        formats: ['header', 'bold', 'italic', 'underline', 'list', 'indent', 'link', 'blockquote', 'image'],
        modules: {
            toolbar: {
                container: [
                    [{ header: [2, 3, false] }],
                    ['bold', 'italic', 'underline'],
                    [{ list: 'ordered' }, { list: 'bullet' }],
                    uploadUrl ? ['link', 'blockquote', 'image'] : ['link', 'blockquote'],
                    ['clean'],
                ],
                handlers: {
                    image() {
                        const picker = Object.assign(document.createElement('input'), { type: 'file', accept: 'image/*', multiple: true });
                        picker.onchange = () => insertImages((quill.getSelection(true) ?? { index: quill.getLength() }).index, picker.files);
                        picker.click();
                    },
                },
            },
            // Yapıştırılan / sürüklenen dosyalar da sunucuya yüklenir
            uploader: {
                mimetypes: ['image/png', 'image/jpeg', 'image/webp', 'image/gif'],
                handler(range, files) {
                    if (uploadUrl) insertImages(range.index, files);
                },
            },
        },
    });

    // Başka yerden kopyalanan base64 ya da yabancı sitedeki görseller içeriğe girmez
    quill.clipboard.addMatcher('IMG', (node) => (node.getAttribute('src')?.startsWith(box.dataset.mediaUrl || '\0') ? new Delta().insert({ image: node.getAttribute('src') }) : new Delta()));

    if (box.dataset.wordPaste !== undefined) {
        // Word listeleri: madde işareti (mso-list:Ignore) silinir, paragraf liste satırı olur
        quill.clipboard.addMatcher('SPAN', (node, delta) => (/mso-list:\s*ignore/i.test(node.getAttribute('style') || '') ? new Delta() : delta));
        quill.clipboard.addMatcher('P', (node, delta) => {
            const style = node.getAttribute('style') || '';
            if (!/MsoList/i.test(node.className) && !/mso-list:\s*l\d/i.test(style)) return delta;
            const marker = node.querySelector('span[style*="mso-list"]')?.textContent.trim() ?? '';
            const list = /^(\d+|[a-z]|[ivxlc]+)[.)]$/i.test(marker) ? 'ordered' : 'bullet';
            const ops = delta.ops;
            const last = ops[ops.length - 1];
            if (!last || typeof last.insert !== 'string' || !last.insert.endsWith('\n')) return delta.insert('\n', { list });

            return delta.compose(new Delta().retain(delta.length() - 1).retain(1, { list }));
        });

        // Gömülü resimler: önce yüklenir, sonra metinle birlikte yerine konur
        quill.root.addEventListener('paste', async (event) => {
            const html = event.clipboardData?.getData('text/html');
            if (!html || !uploadUrl || !/<img[^>]+src=["']?(data:|file:)/i.test(html)) return;
            event.preventDefault();
            event.stopImmediatePropagation();

            const doc = new DOMParser().parseFromString(html, 'text/html');
            const rtf = rtfImages(event.clipboardData.getData('text/rtf'));
            const images = [...doc.querySelectorAll('img')];
            let fromRtf = 0;
            let missing = 0;
            const index = (quill.getSelection(true) ?? { index: quill.getLength() }).index;

            toast(t('Resimler yükleniyor…'));
            for (const img of images) {
                const src = img.getAttribute('src') || '';
                try {
                    if (src.startsWith('data:')) img.setAttribute('src', await upload(await dataUrlToBlob(src)));
                    else if (src.startsWith('file:')) {
                        const blob = rtf[fromRtf++];
                        blob ? img.setAttribute('src', await upload(blob)) : (missing++, img.remove());
                    }
                } catch {
                    missing++;
                    img.remove();
                }
            }

            quill.clipboard.dangerouslyPasteHTML(index, doc.body.innerHTML, 'user');
            toast(missing ? t(':count resim alınamadı; onları sürükleyip bırak.', { count: missing }) : t('Yapıştırıldı.'), missing > 0);
        }, true);
    }

    if (box.dataset.imageSizes !== undefined) {
        // Resme tıklayınca küçük boyut çubuğu
        box.style.position = 'relative';
        const bar = document.createElement('div');
        bar.className = 'absolute z-20 hidden items-center gap-1 rounded-xl bg-slate-900 p-1 text-xs font-semibold text-white shadow-xl';
        bar.innerHTML = IMAGE_SIZES.map(([value, label]) => `<button type="button" data-size="${value}" class="rounded-lg px-2.5 py-1.5 transition hover:bg-white/15">${t(label)}</button>`).join('');
        box.append(bar);
        let selected = null;

        const hide = () => { bar.classList.add('hidden'); bar.classList.remove('flex'); selected?.classList.remove('outline', 'outline-2', 'outline-brand-900'); selected = null; };
        quill.root.addEventListener('click', (event) => {
            const img = event.target.closest('img');
            if (!img) return hide();
            hide();
            selected = img;
            img.classList.add('outline', 'outline-2', 'outline-brand-900');
            const boxRect = box.getBoundingClientRect();
            const rect = img.getBoundingClientRect();
            bar.style.top = `${rect.top - boxRect.top + 8}px`;
            bar.style.left = `${rect.left - boxRect.left + 8}px`;
            bar.classList.remove('hidden');
            bar.classList.add('flex');
            const current = img.getAttribute('width') || '100%';
            bar.querySelectorAll('[data-size]').forEach((button) => button.classList.toggle('bg-white/20', button.dataset.size === current));
        });
        bar.addEventListener('click', (event) => {
            const button = event.target.closest('[data-size]');
            if (!button || !selected) return;
            const blot = Quill.find(selected);
            if (blot) quill.formatText(quill.getIndex(blot), 1, 'width', button.dataset.size === '100%' ? false : button.dataset.size, 'user');
            hide();
        });
        quill.on('text-change', (_delta, _old, source) => source === 'user' && hide());
    }

    if (input.value) {
        quill.clipboard.dangerouslyPasteHTML(input.value);
    }

    // Quill 2 getSemanticHTML boşlukları &nbsp; yapıyor (satır kırılmaz); normal boşluğa çevrilir
    const sync = () => (input.value = quill.getText().trim() === '' && !quill.root.querySelector('img') ? '' : quill.getSemanticHTML().replace(/&nbsp;|\u00a0/g, ' '));
    quill.on('text-change', sync);
    form.addEventListener('submit', sync);
});
