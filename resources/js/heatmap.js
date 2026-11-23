// Analitik › Sıcaklık haritası (resources/views/admin/analytics/heatmap.blade.php).
// Sitedeki sayfa, ziyaretçinin ekran boyunda bir çerçevede açılır (?ts-heatmap: site sayfası kaydırma konumunu ve boyunu
// postMessage ile bildirir, tripspoiler/resources/js/track.js). Harita çerçevenin üstüne çizilir ve sayfa kaydıkça birlikte kayar.
// Üç görünüm: tıklamalar (ısı), kaydırma (sayfanın her yerini ziyaretçilerin yüzde kaçı gördü), sorunlar (sinirli / boşa tıklama).
// Sağdaki ince şerit bütün sayfanın küçük haritası: tıklayınca / sürükleyince oraya gidilir.

const root = document.querySelector('[data-heatmap]');

if (root) {
    const data = JSON.parse(document.getElementById('heatmap-data').textContent);
    const W = data.width;
    const fold = data.fold;
    const mobile = data.device === 'mobile';
    const frame = root.querySelector('[data-heatmap-frame]');
    const viewport = root.querySelector('[data-heatmap-viewport]');
    const scaler = root.querySelector('[data-heatmap-scale]');
    const layer = root.querySelector('[data-heatmap-layer]');
    const canvas = root.querySelector('[data-heatmap-canvas]');
    const marks = root.querySelector('[data-heatmap-marks]');
    const loading = root.querySelector('[data-heatmap-loading]');
    const minimap = root.querySelector('[data-heatmap-minimap]');
    const miniCanvas = minimap.querySelector('canvas');
    const miniWindow = minimap.querySelector('[data-heatmap-window]');
    const origin = new URL(frame.src).origin;

    let H = Math.max(fold, ...data.points.map((p) => p[3] || 0).sort((a, b) => a - b).slice(-1), 2000);
    let heightKnown = false;
    let scroll = 0;
    let scale = 1;
    let mode = 'clicks';

    const CELL = 4;

    // Ziyaretçinin ekranındaki konum → buradaki sayfa. Bilgisayarda içerik ortada durduğu için ortaya göre, telefonda orana göre.
    const mapX = (x, vw) => Math.max(0, Math.min(W, mobile ? (x * W) / vw : x - vw / 2 + W / 2));
    const mapY = (y, ph) => (ph && heightKnown ? (y * H) / ph : y);

    // --- boyutlar ---
    function layout() {
        const available = root.clientWidth - minimap.offsetWidth - 16;
        scale = Math.min(1, available / W);
        viewport.style.width = `${W * scale}px`;
        viewport.style.height = `${fold * scale}px`;
        scaler.style.transform = `scale(${scale})`;
        minimap.style.height = `${fold * scale}px`;
        drawMinimap();
        follow();
    }

    function follow() {
        layer.style.transform = `translateY(${-scroll}px)`;
        const miniH = minimap.clientHeight;
        miniWindow.style.top = `${(scroll / H) * miniH}px`;
        miniWindow.style.height = `${Math.max(8, (fold / H) * miniH)}px`;
    }

    // --- renkler: soğuk (mavi) → sıcak (kırmızı) ---
    const STOPS = [[0, [37, 99, 235]], [0.35, [6, 182, 212]], [0.55, [34, 197, 94]], [0.75, [250, 204, 21]], [1, [239, 68, 68]]];
    function color(v) {
        for (let i = 1; i < STOPS.length; i++) {
            if (v <= STOPS[i][0]) {
                const [a, ca] = STOPS[i - 1];
                const [b, cb] = STOPS[i];
                const t = (v - a) / (b - a);
                return ca.map((c, k) => Math.round(c + (cb[k] - c) * t));
            }
        }
        return STOPS[STOPS.length - 1][1];
    }

    // --- tıklama yoğunluğu (ızgara + yumuşak leke) ---
    function density(points) {
        const cols = Math.ceil(W / CELL);
        const rows = Math.ceil(H / CELL);
        const grid = new Float32Array(cols * rows);
        const R = mobile ? 6 : 8;
        const kernel = [];
        for (let dy = -R; dy <= R; dy++) {
            for (let dx = -R; dx <= R; dx++) {
                const d2 = dx * dx + dy * dy;
                if (d2 <= R * R) kernel.push([dx, dy, Math.exp(-d2 / (R * R * 0.35))]);
            }
        }
        for (const [x, y, vw, ph] of points) {
            const cx = Math.round(mapX(x, vw) / CELL);
            const cy = Math.round(mapY(y, ph) / CELL);
            for (const [dx, dy, w] of kernel) {
                const gx = cx + dx;
                const gy = cy + dy;
                if (gx >= 0 && gx < cols && gy >= 0 && gy < rows) grid[gy * cols + gx] += w;
            }
        }
        let max = 0;
        for (let i = 0; i < grid.length; i++) if (grid[i] > max) max = grid[i];
        return { grid, cols, rows, max };
    }

    function drawClicks() {
        const { grid, cols, rows, max } = density(data.points);
        canvas.width = cols;
        canvas.height = rows;
        const ctx = canvas.getContext('2d');
        const image = ctx.createImageData(cols, rows);
        if (max > 0) {
            for (let i = 0; i < grid.length; i++) {
                const v = Math.pow(grid[i] / max, 0.55);
                if (v < 0.04) continue;
                const [r, g, b] = color(v);
                image.data.set([r, g, b, Math.round(Math.min(0.82, 0.18 + v * 0.75) * 255)], i * 4);
            }
        }
        ctx.putImageData(image, 0, 0);
    }

    // kaydırma: sayfanın her yüksekliği için o noktayı ekranında gören ziyaretçi oranı (reach[yüzde] = %)
    const reachAt = (y) => {
        const p = Math.max(0, Math.min(100, Math.floor((y / H) * 100)));
        return data.reach[p] ?? 0;
    };

    function drawScroll() {
        const rows = Math.ceil(H / CELL);
        canvas.width = 1;
        canvas.height = rows;
        const ctx = canvas.getContext('2d');
        const image = ctx.createImageData(1, rows);
        for (let row = 0; row < rows; row++) {
            // ilk ekran herkesçe görülür
            const y = row * CELL;
            const seen = y <= fold ? 100 : reachAt(y);
            const [r, g, b] = color(seen / 100);
            image.data.set([r, g, b, 90], row * 4);
        }
        ctx.putImageData(image, 0, 0);
    }

    function clear() {
        canvas.width = 1;
        canvas.height = 1;
    }

    function line(y, text, tone) {
        const el = document.createElement('div');
        el.className = 'absolute inset-x-0 border-t-2 border-dashed';
        el.style.top = `${y}px`;
        el.style.borderColor = tone;
        const pill = document.createElement('span');
        pill.className = 'absolute left-3 -translate-y-1/2 rounded-full px-3 py-1 text-[13px] font-bold text-white shadow-lg';
        pill.style.background = tone;
        pill.textContent = text;
        el.append(pill);
        marks.append(el);
    }

    function pin(x, y, cls, title) {
        const el = document.createElement('span');
        el.className = `absolute -translate-x-1/2 -translate-y-1/2 rounded-full ${cls}`;
        el.style.left = `${x}px`;
        el.style.top = `${y}px`;
        el.title = title;
        marks.append(el);
        return el;
    }

    function drawMarks() {
        marks.replaceChildren();
        line(fold, root.dataset.foldLabel, 'rgba(15,23,42,0.75)');
        if (mode === 'scroll' && Object.keys(data.reach).length) {
            for (const target of [75, 50, 25]) {
                let p = 0;
                while (p < 100 && (data.reach[p + 1] ?? 0) >= target) p++;
                const y = (p / 100) * H;
                if (y > fold + 40) line(y, root.dataset.reachLabel.replace(':p', target), target >= 75 ? '#f97316' : target >= 50 ? '#eab308' : '#3b82f6');
            }
        }
        if (mode === 'problems') {
            for (const [x, y, vw, ph, rage, dead] of data.points) {
                if (rage) pin(mapX(x, vw), mapY(y, ph), 'size-6 bg-red-500/80 ring-4 ring-red-500/30', root.dataset.rageLabel);
                else if (dead) pin(mapX(x, vw), mapY(y, ph), 'size-4 bg-amber-400/90 ring-4 ring-amber-400/30', root.dataset.deadLabel);
            }
        }
    }

    function draw() {
        layer.style.height = `${H}px`;
        if (mode === 'clicks') drawClicks();
        else if (mode === 'scroll') drawScroll();
        else clear();
        drawMarks();
        drawMinimap();
    }

    // --- bütün sayfanın küçük haritası ---
    function drawMinimap() {
        const width = minimap.clientWidth;
        const height = minimap.clientHeight;
        if (!width || !height) return;
        miniCanvas.width = width;
        miniCanvas.height = height;
        const ctx = miniCanvas.getContext('2d');
        ctx.clearRect(0, 0, width, height);
        // kaydırma rengi (arka plan)
        if (Object.keys(data.reach).length) {
            for (let row = 0; row < height; row++) {
                const y = (row / height) * H;
                const [r, g, b] = color((y <= fold ? 100 : reachAt(y)) / 100);
                ctx.fillStyle = `rgba(${r},${g},${b},0.35)`;
                ctx.fillRect(0, row, width, 1);
            }
        }
        // tıklamalar: noktalar
        for (const [x, y, vw, ph, rage] of data.points) {
            ctx.fillStyle = rage ? 'rgba(239,68,68,0.95)' : 'rgba(15,23,42,0.55)';
            ctx.fillRect((mapX(x, vw) / W) * width - 1, (mapY(y, ph) / H) * height - 1, 2, 2);
        }
    }

    // --- çerçevedeki sayfayla konuşma ---
    const scrollFrame = (y, smooth = false) => frame.contentWindow?.postMessage({ type: 'ts-heatmap-scroll', y: Math.max(0, y), smooth }, origin);

    let ready = false;
    window.addEventListener('message', (event) => {
        if (event.source !== frame.contentWindow || event.data?.type !== 'ts-heatmap') return;
        if (typeof event.data.scroll === 'number') {
            scroll = event.data.scroll;
            follow();
        }
        if (event.data.height && Math.abs(event.data.height - H) > 2) {
            H = event.data.height;
            heightKnown = true;
            draw();
            follow();
        } else if (event.data.height) {
            heightKnown = true;
        }
        if (!ready) {
            ready = true;
            loading.classList.add('hidden');
            draw();
        }
    });
    setTimeout(() => {
        if (!ready) loading.querySelector('[data-heatmap-loading-text]').textContent = root.dataset.failLabel;
    }, 9000);

    // görünüm düğmeleri
    document.querySelectorAll('[data-heatmap-mode]').forEach((button) => {
        button.addEventListener('click', () => {
            mode = button.dataset.heatmapMode;
            document.querySelectorAll('[data-heatmap-mode]').forEach((b) => b.setAttribute('aria-pressed', b === button ? 'true' : 'false'));
            document.querySelectorAll('[data-heatmap-legend]').forEach((el) => el.classList.toggle('hidden', el.dataset.heatmapLegend !== mode));
            draw();
        });
    });

    // küçük haritada tıkla / sürükle → oraya git
    let dragging = false;
    const jumpFromMini = (event) => {
        const rect = minimap.getBoundingClientRect();
        const y = ((event.clientY - rect.top) / rect.height) * H - fold / 2;
        scrollFrame(y);
    };
    minimap.addEventListener('pointerdown', (event) => { dragging = true; minimap.setPointerCapture(event.pointerId); jumpFromMini(event); });
    minimap.addEventListener('pointermove', (event) => dragging && jumpFromMini(event));
    minimap.addEventListener('pointerup', () => { dragging = false; });

    // çerçevenin üstünde tekerlek: sayfayı kaydır (harita üstteyken de)
    viewport.addEventListener('wheel', (event) => {
        if (event.target === frame) return;
        event.preventDefault();
        scrollFrame(scroll + event.deltaY / scale);
    }, { passive: false });

    // sağdaki listede bir öğeye tıklayınca: sayfada oraya git, yerini göster
    document.querySelectorAll('[data-heatmap-jump]').forEach((item) => {
        item.addEventListener('click', () => {
            const y = mapY(Number(item.dataset.heatmapJump), Number(item.dataset.ph) || 0);
            scrollFrame(y - fold / 3, true);
            const flash = pin(W / 2, y, 'pointer-events-none h-14 w-[96%] rounded-2xl ring-4 ring-fuchsia-500 bg-fuchsia-500/10 animate-pulse', '');
            setTimeout(() => flash.remove(), 2200);
            viewport.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        });
    });

    new ResizeObserver(layout).observe(root);
    layout();
    draw();
}
