
document.addEventListener('DOMContentLoaded', () => {

    // =====================================================
    // FILTER OTOMATIS
    // =====================================================

    const form = document.getElementById('suFilterForm');

    if (form) {

        form.querySelectorAll('select').forEach((select) => {

            select.addEventListener('change', () => {

                form.submit();

            });

        });

    }

    // =====================================================
    // DATA GRAFIK
    // =====================================================

    const holder = document.getElementById('suChartData');

    if (!holder || typeof Chart === 'undefined') {

        return;

    }

    const products = JSON.parse(
        holder.dataset.products || '{"labels":[],"values":[]}'
    );

    const status = JSON.parse(
        holder.dataset.status || '{"labels":[],"values":[]}'
    );

    // Warna grafik
    const COLORS = {

        primary: '#ef2c23',

        primaryHover: '#c9231b',

        active: '#22c55e',

        inactive: '#ef4444',

        other: '#a3a3a3',

        grid: '#eeeeee',

        text: '#666666',

    };

    // =====================================================
    // GRAFIK DISTRIBUSI PRODUK
    // =====================================================

    const productCanvas = document.getElementById(
        'productChart'
    );

    if (productCanvas && products.labels.length > 0) {

        // Sesuaikan tinggi grafik dengan banyaknya produk
        const chartBox = productCanvas.parentElement;

        if (chartBox) {

            const chartHeight = Math.max(
                260,
                products.labels.length * 36 + 40
            );

            chartBox.style.height = `${chartHeight}px`;

        }

        new Chart(productCanvas, {

            type: 'bar',

            data: {

                labels: products.labels,

                datasets: [

                    {

                        label: 'Jumlah Pelanggan',

                        data: products.values,

                        backgroundColor: COLORS.primary,

                        hoverBackgroundColor:
                            COLORS.primaryHover,

                        borderRadius: 5,

                        borderSkipped: false,

                        maxBarThickness: 25,

                    }

                ],

            },

            options: {

                // Grafik horizontal agar nama produk
                // lebih mudah dibaca
                indexAxis: 'y',

                responsive: true,

                maintainAspectRatio: false,

                plugins: {

                    legend: {

                        display: false,

                    },

                    tooltip: {

                        callbacks: {

                            label: function (context) {

                                return `${context.raw} pelanggan`;

                            },

                        },

                    },

                },

                scales: {

                    x: {

                        beginAtZero: true,

                        ticks: {

                            precision: 0,

                            font: {

                                size: 11,

                            },

                        },

                        grid: {

                            color: COLORS.grid,

                        },

                    },

                    y: {

                        ticks: {

                            font: {

                                size: 11,

                            },

                        },

                        grid: {

                            display: false,

                        },

                    },

                },

            },

        });

    }

    // =====================================================
    // GRAFIK DISTRIBUSI STATUS PELANGGAN
    // =====================================================

    const statusCanvas = document.getElementById(
        'statusChart'
    );

    if (statusCanvas && status.values.some(value => value > 0)) {

        new Chart(statusCanvas, {

            type: 'doughnut',

            data: {

                labels: status.labels,

                datasets: [

                    {

                        data: status.values,

                        backgroundColor: [

                            COLORS.active,

                            COLORS.inactive,

                            COLORS.other,

                        ],

                        borderWidth: 0,

                        hoverOffset: 5,

                    }

                ],

            },

            options: {

                responsive: true,

                maintainAspectRatio: false,

                cutout: '60%',

                plugins: {

                    legend: {

                        display: false,

                    },

                    tooltip: {

                        callbacks: {

                            label: function (context) {

                                const total = context.dataset.data
                                    .reduce(
                                        (sum, value) => sum + value,
                                        0
                                    );

                                const percentage = total > 0

                                    ? (
                                        context.raw / total * 100
                                    ).toFixed(1)

                                    : 0;

                                return `${context.label}: `
                                    + `${context.raw} pelanggan `
                                    + `(${percentage}%)`;

                            },

                        },

                    },

                },

            },

        });

    }

});
