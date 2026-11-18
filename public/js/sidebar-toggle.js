document.addEventListener('DOMContentLoaded', () => {
    const body    = document.body;
    const toggle  = document.getElementById('menuToggle');
    const overlay = document.getElementById('sidebarOverlay');

    if (!toggle) return;

    const setOpen = (open) => {
        body.classList.toggle('sidebar-open', open);
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    };

    // Buka / tutup lewat tombol hamburger
    toggle.addEventListener('click', () => {
        setOpen(!body.classList.contains('sidebar-open'));
    });

    // Tutup kalau area gelap diklik
    if (overlay) {
        overlay.addEventListener('click', () => setOpen(false));
    }

    // Tutup dengan tombol Esc
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') setOpen(false);
    });

    // Kalau layar dilebarkan kembali ke ukuran desktop, pastikan menu tertutup
    window.matchMedia('(min-width: 901px)').addEventListener('change', (e) => {
        if (e.matches) setOpen(false);
    });
});
