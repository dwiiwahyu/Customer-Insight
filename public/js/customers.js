// ---------- Filter dropdown: otomatis dikirim begitu nilainya diganti ----------
const filterForm = document.getElementById('filterForm');

if (filterForm) {
    filterForm.querySelectorAll('select').forEach((select) => {
        select.addEventListener('change', () => filterForm.submit());
    });
}

// ---------- Tombol "Tambah pelanggan": dropdown ----------
const addMenu = document.getElementById('addMenu');
const addBtn = document.getElementById('addBtn');
const addDropdown = document.getElementById('addDropdown');

if (addMenu) {
    addBtn.addEventListener('click', () => addDropdown.classList.toggle('show'));

    // tutup kalau klik di luar
    document.addEventListener('click', (e) => {
        if (!addMenu.contains(e.target)) {
            addDropdown.classList.remove('show');
        }
    });
}

// ---------- Jendela upload CSV ----------
const uploadModal = document.getElementById('uploadModal');
const uploadForm = document.getElementById('uploadForm');
const uploadSubmit = document.getElementById('uploadSubmit');

function openUpload() {
    addDropdown.classList.remove('show');
    uploadModal.hidden = false;
}

function closeUpload() {
    uploadModal.hidden = true;
}

if (uploadModal) {
    document.getElementById('openUpload').addEventListener('click', openUpload);
    document.getElementById('closeUpload').addEventListener('click', closeUpload);

    // klik area gelap di luar kotak = tutup
    uploadModal.addEventListener('click', (e) => {
        if (e.target === uploadModal) closeUpload();
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeUpload();
    });

    // cegah klik dobel saat file sedang diproses
    uploadForm.addEventListener('submit', () => {
        uploadSubmit.disabled = true;
        uploadSubmit.textContent = 'Memproses...';
    });

    // Jenis data: opsi REVENUE & centang dummy hanya untuk data pelanggan
    const typeSelect = document.getElementById('type');
    const masterOptions = document.getElementById('masterOptions');
    const hintMaster = document.getElementById('hintMaster');
    const hintPayments = document.getElementById('hintPayments');

    function syncType() {
        const isMaster = typeSelect.value === 'master';

        masterOptions.hidden = !isMaster;
        hintMaster.hidden = !isMaster;
        hintPayments.hidden = isMaster;
    }

    typeSelect.addEventListener('change', syncType);
    syncType();
}