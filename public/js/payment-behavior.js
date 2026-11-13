const COLORS = {
    green: '#38e34d',
    yellow: '#f2c94c',
    orange: '#f7a23b',
    red: '#ee2e24',
};

function dataOf(id) {
    return JSON.parse(document.getElementById(id).dataset.chart);
}

// ---------- Grafik batang: rata-rata keterlambatan per bulan ----------
const delay = dataOf('delayChart');

new Chart(document.getElementById('delayChart'), {
    type: 'bar',
    data: {
        labels: delay.labels,
        datasets: [{
            label: 'Rata-rata keterlambatan (hari)',
            data: delay.values,
            backgroundColor: COLORS.orange,
            borderRadius: 4,
        }],
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, title: { display: true, text: 'hari' } },
            x: { grid: { display: false } },
        },
    },
});

// ---------- Donut: komposisi pola pembayaran ----------
const pola = dataOf('polaChart');

new Chart(document.getElementById('polaChart'), {
    type: 'doughnut',
    data: {
        labels: pola.labels,
        datasets: [{
            data: pola.values,
            backgroundColor: [COLORS.green, COLORS.yellow, COLORS.orange, COLORS.red],
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

// ---------- Filter daftar peringatan ----------
const chips = document.querySelectorAll('.chip');
const notifs = document.querySelectorAll('.notif');

chips.forEach((chip) => {
    chip.addEventListener('click', () => {
        const type = chip.dataset.filter;

        chips.forEach((c) => c.classList.toggle('active', c === chip));
        notifs.forEach((n) => {
            n.hidden = !(type === 'all' || n.dataset.type === type);
        });
    });
});

// ---------- Filter pola: otomatis kirim saat dropdown diganti ----------
const polaForm = document.getElementById('polaForm');
polaForm.querySelector('select').addEventListener('change', () => polaForm.submit());