
document.addEventListener('DOMContentLoaded', function () {

    // Ambil panel notifikasi
    const panel = document.getElementById('notificationPanel');

    if (!panel) return;

    // Ambil semua tombol filter
    const chips = panel.querySelectorAll('.chip[data-filter]');

    // Ambil semua kartu notifikasi
    const notifications = panel.querySelectorAll('.notif[data-type]');

    // Pesan ketika kategori kosong
    const emptyMessage = document.getElementById('notificationEmpty');

    chips.forEach(function (chip) {

        chip.addEventListener('click', function () {

            const filter = chip.dataset.filter;

            // Hapus status aktif dari semua tombol
            chips.forEach(function (item) {

                item.classList.remove('active');

                item.setAttribute('aria-pressed', 'false');

            });

            // Aktifkan tombol yang dipilih
            chip.classList.add('active');

            chip.setAttribute('aria-pressed', 'true');

            let visibleCount = 0;

            // Filter kartu notifikasi
            notifications.forEach(function (notification) {

                const type = notification.dataset.type;

                const visible =
                    filter === 'all' || type === filter;

                notification.hidden = !visible;

                if (visible) {
                    visibleCount++;
                }

            });

            // Tampilkan pesan jika kategori tidak memiliki data
            if (emptyMessage) {

                emptyMessage.hidden =
                    visibleCount > 0 || notifications.length === 0;

            }

        });

    });

});
