// Analitik sayfasındaki günlük grafik. KPI kartına tıklanınca metrik değişir.
import {
    CategoryScale,
    Chart,
    Filler,
    LinearScale,
    LineController,
    LineElement,
    PointElement,
    Tooltip,
} from 'chart.js';

Chart.register(LineController, LineElement, PointElement, LinearScale, CategoryScale, Filler, Tooltip);

const DATA_COLOR = '#2f56b0';
const TEXT_MUTED = '#94a3b8';
const GRID = '#eef1f5';

const number = new Intl.NumberFormat('tr-TR');
const formatters = {
    visitors: (v) => number.format(v),
    views: (v) => number.format(v),
    clicks: (v) => number.format(v),
    ctr: (v) => `%${v.toLocaleString('tr-TR', { minimumFractionDigits: 1, maximumFractionDigits: 1 })}`,
};

// Hover'da dikey çizgi
const crosshair = {
    id: 'crosshair',
    afterDatasetsDraw(chart) {
        const active = chart.tooltip?.getActiveElements?.();
        if (!active?.length) return;

        const { ctx, chartArea } = chart;
        const x = active[0].element.x;
        ctx.save();
        ctx.beginPath();
        ctx.moveTo(x, chartArea.top);
        ctx.lineTo(x, chartArea.bottom);
        ctx.lineWidth = 1;
        ctx.strokeStyle = '#cbd5e1';
        ctx.stroke();
        ctx.restore();
    },
};

function init() {
    const canvas = document.getElementById('analytics-chart');
    const source = document.getElementById('analytics-data');
    if (!canvas || !source) return;

    const { daily, labels } = JSON.parse(source.textContent);
    const title = document.querySelector('[data-chart-title]');
    const tabs = [...document.querySelectorAll('[data-metric]')];
    let metric = 'clicks';

    const gradient = (ctx) => {
        const { chartArea } = ctx.chart;
        if (!chartArea) return 'transparent';
        const g = ctx.chart.ctx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
        g.addColorStop(0, 'rgba(47, 86, 176, 0.18)');
        g.addColorStop(1, 'rgba(47, 86, 176, 0)');
        return g;
    };

    const chart = new Chart(canvas, {
        type: 'line',
        data: {
            labels: daily.labels,
            datasets: [{
                data: daily[metric],
                borderColor: DATA_COLOR,
                backgroundColor: gradient,
                borderWidth: 2,
                fill: true,
                tension: 0.3,
                pointRadius: 0,
                pointHoverRadius: 5,
                pointHoverBorderWidth: 2,
                pointHoverBorderColor: '#ffffff',
                pointHoverBackgroundColor: DATA_COLOR,
            }],
        },
        options: {
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            animation: { duration: 300 },
            scales: {
                x: {
                    grid: { display: false },
                    border: { display: false },
                    ticks: { color: TEXT_MUTED, maxRotation: 0, autoSkipPadding: 24, font: { size: 11 } },
                },
                y: {
                    beginAtZero: true,
                    grid: { color: GRID },
                    border: { display: false },
                    ticks: {
                        color: TEXT_MUTED,
                        font: { size: 11 },
                        maxTicksLimit: 5,
                        callback: (v) => formatters[metric](v),
                    },
                },
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#16244a',
                    padding: 10,
                    cornerRadius: 8,
                    displayColors: false,
                    titleFont: { weight: '500' },
                    bodyFont: { weight: '600', size: 13 },
                    callbacks: {
                        label: (item) => `${labels[metric]}: ${formatters[metric](item.parsed.y)}`,
                    },
                },
            },
        },
        plugins: [crosshair],
    });

    const select = (key) => {
        metric = key;
        chart.data.datasets[0].data = daily[key];
        chart.update();
        title.textContent = labels[key];
        tabs.forEach((tab) => tab.setAttribute('aria-selected', String(tab.dataset.metric === key)));
    };

    tabs.forEach((tab) => tab.addEventListener('click', () => select(tab.dataset.metric)));
}

init();
