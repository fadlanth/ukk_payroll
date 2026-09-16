document.addEventListener('DOMContentLoaded', () => {
    // 1. Client-side Form Validation on payrollForm & salaryForm
    const payrollForm = document.querySelector('#payrollForm');
    if (payrollForm) {
        payrollForm.addEventListener('submit', (event) => {
            const karyawan = document.querySelector('#karyawan_id');
            const periode = document.querySelector('#periode');
            const tglBayar = document.querySelector('#tanggal_bayar');
            const captcha = document.querySelector('#captcha');
            const numericFields = [
                { id: 'gaji_pokok', label: 'Gaji Pokok' },
                { id: 'lembur', label: 'Uang Lembur' },
                { id: 'pinjaman', label: 'Pinjaman / Potongan' }
            ];

            const errors = [];

            if (!karyawan || !karyawan.value) {
                errors.push('Pilih karyawan terlebih dahulu.');
            }

            if (!periode || !periode.value) {
                errors.push('Periode bulan gaji wajib dipilih.');
            }

            if (!tglBayar || !tglBayar.value) {
                errors.push('Tanggal pembayaran wajib diisi.');
            }

            if (captcha && captcha.value.trim() === '') {
                errors.push('Jawaban verifikasi Captcha perkalian wajib diisi.');
            }

            numericFields.forEach((field) => {
                const input = document.querySelector(`#${field.id}`);
                if (!input) return;

                const value = Number(input.value);
                if (input.value === '' || Number.isNaN(value) || value < 0) {
                    errors.push(`${field.label} harus berupa angka 0 atau lebih.`);
                }
            });

            if (errors.length > 0) {
                event.preventDefault();
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Validasi Input Penggajian',
                        html: '<ul style="text-align:left;margin-left:10px;">' + errors.map(e => `<li>${e}</li>`).join('') + '</ul>',
                        confirmButtonColor: '#4338ca'
                    });
                } else {
                    alert(errors.join('\n'));
                }
            }
        });
    }

    const salaryForm = document.querySelector('#salaryForm');
    if (salaryForm) {
        salaryForm.addEventListener('submit', (event) => {
            const name = document.querySelector('#nama');
            const jabatan = document.querySelector('#jabatan');
            const errors = [];

            if (!name || !name.value.trim()) {
                errors.push('Nama karyawan wajib diisi.');
            }

            if (!jabatan || !jabatan.value) {
                errors.push('Jabatan wajib dipilih.');
            }

            if (errors.length > 0) {
                event.preventDefault();
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Validasi Input Master Karyawan',
                        html: '<ul style="text-align:left;margin-left:10px;">' + errors.map(e => `<li>${e}</li>`).join('') + '</ul>',
                        confirmButtonColor: '#4338ca'
                    });
                } else {
                    alert(errors.join('\n'));
                }
            }
        });
    }

    // 2. SweetAlert2 Confirmation for Data Deletion
    const deleteButtons = document.querySelectorAll('.btn-delete-swal');
    const deleteForm = document.querySelector('#deleteForm');
    const deleteIdInput = document.querySelector('#deleteId');

    deleteButtons.forEach((btn) => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const id = btn.getAttribute('data-id');
            const nama = btn.getAttribute('data-nama');

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Hapus Data Karyawan?',
                    text: `Apakah Anda yakin ingin menghapus data "${nama}"? Tindakan ini tidak dapat dibatalkan.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc2626',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: '<i class="fa-regular fa-trash-can"></i> Ya, Hapus Data',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed && deleteForm && deleteIdInput) {
                        deleteIdInput.value = id;
                        deleteForm.submit();
                    }
                });
            } else {
                if (confirm(`Yakin ingin menghapus data "${nama}"?`)) {
                    if (deleteForm && deleteIdInput) {
                        deleteIdInput.value = id;
                        deleteForm.submit();
                    }
                }
            }
        });
    });

    // 3. Send Email Modal / Trigger with SweetAlert2
    const btnSendEmail = document.querySelector('#btnSendEmail');
    if (btnSendEmail) {
        btnSendEmail.addEventListener('click', () => {
            const email = btnSendEmail.getAttribute('data-email');
            const mailto = btnSendEmail.getAttribute('data-mailto');
            const nama = btnSendEmail.getAttribute('data-nama');

            if (typeof Swal !== 'undefined') {
                if (!email) {
                    Swal.fire({
                        icon: 'info',
                        title: 'Email Belum Terdaftar',
                        text: `Karyawan ${nama} belum memiliki data email. Silakan lengkapi di menu edit atau masukkan tujuan secara manual di aplikasi email.`,
                        showCancelButton: true,
                        confirmButtonText: 'Buka Email Client',
                        cancelButtonText: 'Tutup',
                        confirmButtonColor: '#0284c7'
                    }).then((res) => {
                        if (res.isConfirmed) {
                            window.location.href = mailto;
                        }
                    });
                } else {
                    Swal.fire({
                        icon: 'question',
                        title: 'Kirim Slip Gaji via Email?',
                        html: `Kirim slip gaji resmi ke alamat: <strong>${email}</strong>`,
                        showCancelButton: true,
                        confirmButtonText: '<i class="fa-regular fa-envelope"></i> Buka Aplikasi Email',
                        cancelButtonText: 'Batal',
                        confirmButtonColor: '#0284c7'
                    }).then((res) => {
                        if (res.isConfirmed) {
                            window.location.href = mailto;
                        }
                    });
                }
            } else {
                window.location.href = mailto;
            }
        });
    }

    // 4. CAPTCHA Refresh (Ganti Soal tanpa Reload Halaman)
    const btnRefreshCaptcha = document.querySelector('#btnRefreshCaptcha');
    if (btnRefreshCaptcha) {
        btnRefreshCaptcha.addEventListener('click', async () => {
            btnRefreshCaptcha.classList.add('spinning');
            btnRefreshCaptcha.disabled = true;

            try {
                const refreshUrl = btnRefreshCaptcha.dataset.url || 'captcha_refresh.php';
                const response = await fetch(refreshUrl, {
                    method: 'GET',
                    headers: { 'Accept': 'application/json' },
                });

                if (!response.ok) throw new Error('Gagal memuat soal baru.');

                const data = await response.json();

                if (data.success) {
                    const c1El = document.querySelector('#captchaC1');
                    const c2El = document.querySelector('#captchaC2');
                    const captchaInput = document.querySelector('#captcha');

                    if (c1El) c1El.textContent = data.c1;
                    if (c2El) c2El.textContent = data.c2;
                    if (captchaInput) {
                        captchaInput.value = '';
                        captchaInput.focus();
                    }
                } else {
                    alert('Gagal memperbarui captcha. Silakan refresh halaman.');
                }
            } catch {
                alert('Terjadi kesalahan jaringan. Silakan refresh halaman.');
            } finally {
                btnRefreshCaptcha.classList.remove('spinning');
                btnRefreshCaptcha.disabled = false;
            }
        });
    }

    // 5. Sidebar Toggle — 1 tombol saja di topbar
    const layoutWrapper = document.querySelector('.layout-wrapper');
    const btnSidebarToggle = document.querySelector('#btnSidebarToggle');
    const sidebarBackdrop = document.querySelector('#sidebarBackdrop');

    if (layoutWrapper) {
        // Restore state dari localStorage (desktop only)
        if (window.innerWidth > 880 && localStorage.getItem('gajihub_sidebar_collapsed') === '1') {
            layoutWrapper.classList.add('sidebar-collapsed');
        }

        function updateToggleIcon() {
            if (!btnSidebarToggle) return;
            const icon = btnSidebarToggle.querySelector('i');
            if (!icon) return;
            const isCollapsed = layoutWrapper.classList.contains('sidebar-collapsed') ||
                                layoutWrapper.classList.contains('sidebar-open') === false && window.innerWidth <= 880;
            // Desktop: collapsed = bars (sidebar tertutup), not collapsed = bars-staggered (bisa tutup)
            if (window.innerWidth > 880) {
                if (layoutWrapper.classList.contains('sidebar-collapsed')) {
                    icon.className = 'fa-solid fa-bars';
                } else {
                    icon.className = 'fa-solid fa-bars-staggered';
                }
            } else {
                icon.className = 'fa-solid fa-bars';
            }
        }

        const handleToggleSidebar = () => {
            if (window.innerWidth <= 880) {
                layoutWrapper.classList.toggle('sidebar-open');
            } else {
                layoutWrapper.classList.toggle('sidebar-collapsed');
                const collapsed = layoutWrapper.classList.contains('sidebar-collapsed') ? '1' : '0';
                localStorage.setItem('gajihub_sidebar_collapsed', collapsed);
            }
            updateToggleIcon();
        };

        if (btnSidebarToggle) btnSidebarToggle.addEventListener('click', handleToggleSidebar);

        if (sidebarBackdrop) {
            sidebarBackdrop.addEventListener('click', () => {
                layoutWrapper.classList.remove('sidebar-open');
                updateToggleIcon();
            });
        }

        // Set ikon awal
        updateToggleIcon();
    }

    // 6. Action Dropdown: Close on outside click or when opening another
    document.addEventListener('click', (e) => {
        const openDropdowns = document.querySelectorAll('.action-dropdown[open]');
        openDropdowns.forEach((dd) => {
            if (!dd.contains(e.target)) {
                dd.removeAttribute('open');
            }
        });
    });

    document.querySelectorAll('.action-dropdown summary').forEach((summary) => {
        summary.addEventListener('click', () => {
            const currentDetails = summary.closest('.action-dropdown');
            document.querySelectorAll('.action-dropdown[open]').forEach((other) => {
                if (other !== currentDetails) {
                    other.removeAttribute('open');
                }
            });
        });
    });
});

