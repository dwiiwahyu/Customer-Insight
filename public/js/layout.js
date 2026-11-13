// ---------- Dropdown lonceng + profil: buka/tutup saat diklik, tutup kalau klik di luar ----------
const menus = [
    { wrap: 'notifMenu', btn: 'notifBtn', dropdown: 'notifDropdown' },
    { wrap: 'userMenu', btn: 'userBtn', dropdown: 'userDropdown' },
].map((m) => ({
    wrap: document.getElementById(m.wrap),
    btn: document.getElementById(m.btn),
    dropdown: document.getElementById(m.dropdown),
}));

menus.forEach((menu) => {
    menu.btn.addEventListener('click', () => {
        // tutup dropdown lain biar nggak numpuk
        menus.forEach((other) => {
            if (other !== menu) other.dropdown.classList.remove('show');
        });

        menu.dropdown.classList.toggle('show');
    });
});

document.addEventListener('click', (e) => {
    menus.forEach((menu) => {
        if (!menu.wrap.contains(e.target)) {
            menu.dropdown.classList.remove('show');
        }
    });
});

// ---------- Tombol kembali: balik ke halaman sebelumnya ----------
// Pakai atribut data-back di link. Kalau pengunjung datang dari halaman lain di aplikasi ini,
// klik = tombol Back browser (filter, halaman, dan posisi scroll ikut kembali).
// Kalau tidak (halaman dibuka langsung), link mengikuti href bawaannya.
document.querySelectorAll('[data-back]').forEach((link) => {
    link.addEventListener('click', (e) => {
        // klik dengan Ctrl/Cmd/Shift atau klik tengah (buka tab baru) tetap normal
        if (e.ctrlKey || e.metaKey || e.shiftKey || e.button !== 0) return;

        let dariAplikasiIni = false;

        try {
            const ref = new URL(document.referrer);
            dariAplikasiIni = ref.origin === location.origin && ref.pathname !== location.pathname;
        } catch (err) {
            // tidak ada referrer (halaman dibuka langsung)
        }

        if (dariAplikasiIni && window.history.length > 1) {
            e.preventDefault();
            window.history.back();
        }
    });
});