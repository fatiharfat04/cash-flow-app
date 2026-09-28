import './bootstrap';

/**
 * Warna chart — mengikuti color tokens §9.1, JANGAN pakai warna default Chart.js (§9.5).
 */
const chartColors = {
    income: '#4A7C59',
    expense: '#B85C4A',
    grid: '#FCEFD9',
    text: '#6B6154',
    surface: '#FFFDF9',
};

const rupiah = (value) => 'Rp' + Number(value).toLocaleString('id-ID');

const dayLabel = (value) => {
    const date = new Date(value + 'T00:00:00');

    return Number.isNaN(date.getTime())
        ? value
        : date.toLocaleDateString('id-ID', { day: '2-digit', month: 'short' });
};

/**
 * Komponen Alpine yang menyalakan Chart.js dan menerima update dari Livewire
 * lewat browser event "chart-data-updated" (lihat §7.5 PROJECT.md).
 */
document.addEventListener('livewire:init', () => {
    const Alpine = window.Alpine;

    if (! Alpine) {
        return;
    }

    if (typeof window.Chart !== 'undefined') {
        window.Chart.defaults.font.family = "'Figtree', ui-sans-serif, system-ui, sans-serif";
        window.Chart.defaults.font.size = 12;
        window.Chart.defaults.color = chartColors.text;
    }

    Alpine.data('dashboardCharts', (initial) => ({
        trend: initial.trend ?? { labels: [], income: [], expense: [] },
        breakdown: initial.breakdown ?? { labels: [], values: [], colors: [] },
        trendChart: null,
        breakdownChart: null,

        init() {
            this.$nextTick(() => {
                this.drawTrend();
                this.drawBreakdown();
            });
        },

        destroy() {
            this.trendChart?.destroy();
            this.breakdownChart?.destroy();
            this.trendChart = null;
            this.breakdownChart = null;
        },

        updateCharts(detail) {
            if (! detail) {
                return;
            }

            if (detail.trend) {
                this.trend = detail.trend;
            }

            if (detail.breakdown) {
                this.breakdown = detail.breakdown;
            }

            this.drawTrend();
            this.drawBreakdown();
        },

        drawTrend() {
            const canvas = this.$refs.trendCanvas;

            if (! canvas || typeof window.Chart === 'undefined') {
                return;
            }

            const data = {
                labels: this.trend.labels ?? [],
                datasets: [
                    {
                        label: 'Pemasukan',
                        data: this.trend.income ?? [],
                        borderColor: chartColors.income,
                        backgroundColor: chartColors.income,
                        borderWidth: 2,
                        tension: 0.35,
                        pointRadius: 0,
                        pointHitRadius: 12,
                    },
                    {
                        label: 'Pengeluaran',
                        data: this.trend.expense ?? [],
                        borderColor: chartColors.expense,
                        backgroundColor: chartColors.expense,
                        borderWidth: 2,
                        tension: 0.35,
                        pointRadius: 0,
                        pointHitRadius: 12,
                    },
                ],
            };

            if (this.trendChart) {
                this.trendChart.data = data;
                this.trendChart.update();
                return;
            }

            this.trendChart = new window.Chart(canvas, {
                type: 'line',
                data,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: {
                            labels: { color: chartColors.text, boxWidth: 12, font: { size: 12 } },
                        },
                        tooltip: {
                            callbacks: {
                                label: (context) => `${context.dataset.label}: ${rupiah(context.parsed.y)}`,
                            },
                        },
                    },
                    scales: {
                        x: {
                            ticks: {
                                color: chartColors.text,
                                font: { size: 12 },
                                maxTicksLimit: 7,
                                callback: function (value) {
                                    return dayLabel(this.getLabelForValue(value));
                                },
                            },
                            grid: { color: chartColors.grid },
                        },
                        y: {
                            beginAtZero: true,
                            ticks: {
                                color: chartColors.text,
                                font: { size: 12 },
                                callback: (value) => rupiah(value),
                            },
                            grid: { color: chartColors.grid },
                        },
                    },
                },
            });
        },

        drawBreakdown() {
            const canvas = this.$refs.breakdownCanvas;

            if (! canvas || typeof window.Chart === 'undefined') {
                return;
            }

            const data = {
                labels: this.breakdown.labels ?? [],
                datasets: [{
                    data: this.breakdown.values ?? [],
                    backgroundColor: this.breakdown.colors ?? [],
                    borderColor: chartColors.surface,
                    borderWidth: 2,
                    hoverOffset: 6,
                }],
            };

            if (this.breakdownChart) {
                this.breakdownChart.data = data;
                this.breakdownChart.update();
                return;
            }

            this.breakdownChart = new window.Chart(canvas, {
                type: 'doughnut',
                data,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '65%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                color: chartColors.text,
                                boxWidth: 12,
                                font: { size: 12 },
                                padding: 12,
                            },
                        },
                        tooltip: {
                            callbacks: {
                                label: (context) => `${context.label}: ${rupiah(context.parsed)}`,
                            },
                        },
                    },
                },
            });
        },
    }));
});
