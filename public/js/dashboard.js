const COLORS = {
    green: '#38e34d',
    orange: '#f7a23b',
    red: '#ee3a3a',
};

// ---------- Grafik garis: tren perilaku pembayaran ----------
const trendEl = document.getElementById('trendChart');
const trend = JSON.parse(trendEl.dataset.chart);

function garis(label, data, color) {
    return {
        label,
        data,
        borderColor: color,
        backgroundColor: '#fff',
        pointBackgroundColor: '#fff',
        pointBorderColor: color,
        pointBorderWidth: 2,
        pointRadius: 3,
        borderWidth: 2,
        tension: 0,
    };
}

new Chart(trendEl, {
    type: 'line',
    data: {
        labels: trend.labels,
        datasets: [
            garis('Tepat Waktu', trend.tepat_waktu, COLORS.green),
            garis('Terlambat', trend.terlambat, COLORS.orange),
            garis('Tunggakan', trend.tunggakan, COLORS.red),
        ],
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: {
            legend: {
                position: 'top',
                align: 'end',
                labels: { usePointStyle: true, boxWidth: 8, font: { size: 12 } },
            },
        },
        scales: {
            y: { beginAtZero: true, ticks: { precision: 0 } },
            x: { grid: { display: false } },
        },
    },
});

// ---------- Donut: status pelanggan ----------
const statusEl = document.getElementById('statusChart');
const status = JSON.parse(statusEl.dataset.chart);

new Chart(statusEl, {
    type: 'doughnut',
    data: {
        labels: status.labels,
        datasets: [{
            data: status.values,
            backgroundColor: [COLORS.green, COLORS.orange, COLORS.red],
            borderWidth: 0,
        }],
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '58%',
        plugins: { legend: { display: false } },
    },
});