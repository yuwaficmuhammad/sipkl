<?php
// Menerima variabel $active_page dari halaman yang melakukan include
$active_page = $active_page ?? 'home';
$role = getRole();
?>
<nav class="bottom-nav">
    <a href="<?= BASE_URL ?>index.php" class="nav-item <?= $active_page == 'home' ? 'active' : '' ?>">
        <i data-lucide="home"></i>
        <span>Home</span>
    </a>
    
    <?php if($role == 'siswa' || $role == 'pembimbing_sekolah'): ?>
    <a href="<?= BASE_URL ?><?= $role == 'siswa' ? 'siswa/siswa_logbook.php' : 'guru/guru_logbook.php' ?>" class="nav-item <?= $active_page == 'logbook' ? 'active' : '' ?>">
        <i data-lucide="book-open"></i>
        <span>Logbook</span>
    </a>
    <a href="<?= BASE_URL ?><?= $role == 'pembimbing_sekolah' ? 'guru/guru_gate.php' : '#' ?>" class="nav-item <?= $active_page == 'gate' ? 'active' : '' ?>">
        <i data-lucide="shield"></i>
        <span>Gate</span>
    </a>
    <?php endif; ?>
    
    <?php if($role == 'pembimbing_dudika'): ?>
    <a href="<?= BASE_URL ?>dudi/dudi_nilai.php" class="nav-item <?= $active_page == 'nilai' ? 'active' : '' ?>">
        <i data-lucide="star"></i>
        <span>Nilai</span>
    </a>
    <?php endif; ?>
    
    <?php if($role == 'admin'): ?>
    <a href="<?= BASE_URL ?>admin/admin_users.php" class="nav-item <?= $active_page == 'users' ? 'active' : '' ?>">
        <i data-lucide="users"></i>
        <span>Users</span>
    </a>
    <a href="<?= BASE_URL ?>admin/admin_proyek.php" class="nav-item <?= $active_page == 'proyek' ? 'active' : '' ?>">
        <i data-lucide="folder-git-2"></i>
        <span>Proyek</span>
    </a>
    <?php endif; ?>
    
    <a href="#" class="nav-item <?= $active_page == 'profil' ? 'active' : '' ?>">
        <i data-lucide="user"></i>
        <span>Profil</span>
    </a>
</nav>

<!-- SWEETALERT2 CDN & SMART SCRIPTS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    // 1. Matikan Page Loader setelah semua termuat
    const loader = document.getElementById('page-loader');
    if(loader) {
        setTimeout(() => {
            loader.style.opacity = '0';
            setTimeout(() => loader.style.display = 'none', 300);
        }, 150);
    }
    
    // 2. Cegat Notification Banner menjadi SWAL
    const banner = document.querySelector('.notification-banner');
    if(banner && banner.innerText.trim() !== '') {
        let isSuccess = banner.classList.contains('success');
        let isWarning = banner.classList.contains('warning');
        let isDanger = banner.classList.contains('danger');
        
        // Hanya cegat jika itu adalah pesan notifikasi sistem sesungguhnya
        if(isSuccess || isWarning || isDanger) {
            banner.style.display = 'none'; // Sembunyikan banner asli
            let icon = isSuccess ? 'success' : (isWarning ? 'warning' : 'error');
            let title = isSuccess ? 'Berhasil!' : (isWarning ? 'Perhatian' : 'Gagal!');
            
            Swal.fire({
                icon: icon, title: title, text: banner.innerText.trim(),
                confirmButtonColor: '#0ea5e9', confirmButtonText: 'Tutup'
            });
        }
    }
    
    // 3. Cegat tombol Confirm (Hapus / ACC) menjadi SWAL
    document.body.addEventListener('click', function(e) {
        let el = e.target.closest('[onclick*="confirm"]');
        if(el) {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            let match = el.getAttribute('onclick').match(/confirm\(['"](.*?)['"]\)/);
            let msg = match ? match[1] : 'Anda yakin melanjutkan?';
            Swal.fire({
                title: 'Konfirmasi', text: msg, icon: 'warning',
                showCancelButton: true, confirmButtonColor: '#0ea5e9', cancelButtonColor: '#f43f5e',
                confirmButtonText: 'Ya, Lanjutkan', cancelButtonText: 'Batal'
            }).then((result) => {
                if(result.isConfirmed) {
                    el.removeAttribute('onclick'); // prevent loop
                    if(el.tagName === 'A' && el.hasAttribute('href') && el.getAttribute('href') !== '#') {
                        window.location.href = el.getAttribute('href');
                    } else {
                        el.click(); // trigger actual navigation/action
                    }
                }
            });
        }
    }, true);
    
    // 4. Cegat form onsubmit Confirm menjadi SWAL
    document.body.addEventListener('submit', function(e) {
        let form = e.target;
        let onsubmit = form.getAttribute('onsubmit');
        if(onsubmit && onsubmit.includes('confirm')) {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            let match = onsubmit.match(/confirm\(['"](.*?)['"]\)/);
            let msg = match ? match[1] : 'Anda yakin?';
            Swal.fire({
                title: 'Konfirmasi', text: msg, icon: 'warning',
                showCancelButton: true, confirmButtonColor: '#0ea5e9', cancelButtonColor: '#f43f5e',
                confirmButtonText: 'Ya, Lanjutkan'
            }).then((result) => {
                if(result.isConfirmed) {
                    form.removeAttribute('onsubmit'); // prevent loop
                    form.submit();
                }
            });
        }
    }, true);
});
</script>
