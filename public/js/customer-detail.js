const COLORS = {
    green: '#38e34d',
    orange: '#f7a23b',
    red: '#ee2e24',
};

const TAB_NAMES = ['overview', 'pembayaran', 'insight'];
const chartsReady = {};

function dataOf(id) {
    return JSON.parse(document.getElementById(id).dataset.chart);
}

// ---------- Grafik: dibuat sekali, saat tab pertama kali dibuka ----------
const chartBuilders = {
    pembayaran() {
        const trend = dataOf('payTrendChart');

        new Chart(document.getElementById('payTrendChart'), {
            type: 'line',
            data: {
                labels: trend.labels,
                datasets: [{
                    label: 'Keterlambatan (hari)',
                    data: trend.values,
                    borderColor: COLORS.orange,
                    backgroundColor: '#fff',
                    pointBorderColor: COLORS.orange,
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    borderWidth: 2,
                    tension: 0.2,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'top', align: 'end', labels: { usePointStyle: true, boxWidth: 8 } } },
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0 }, title: { display: true, text: 'hari' } },
                    x: { grid: { display: false } },
                },
            },
        });

        const donut = dataOf('payDonutChart');

        new Chart(document.getElementById('payDonutChart'), {
            type: 'doughnut',
            data: {
                labels: donut.labels,
                datasets: [{
                    data: donut.values,
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
    },
};

// ---------- Ganti tab ----------
const tabButtons = document.querySelectorAll('.tab');
const tabPanels = document.querySelectorAll('.tab-panel');

function showTab(name) {
    tabButtons.forEach((btn) => btn.classList.toggle('active', btn.dataset.tab === name));
    tabPanels.forEach((panel) => panel.classList.toggle('active', panel.id === 'tab-' + name));

    history.replaceState(null, '', '#' + name);

    if (chartBuilders[name] && !chartsReady[name]) {
        chartsReady[name] = true;
        chartBuilders[name]();
    }
}

tabButtons.forEach((btn) => btn.addEventListener('click', () => showTab(btn.dataset.tab)));

// Buka tab sesuai #hash di URL (default: overview). Link lama ke #penggunaan otomatis jatuh ke overview.
const fromHash = location.hash.replace('#', '');
showTab(TAB_NAMES.includes(fromHash) ? fromHash : 'overview');