<?php
declare(strict_types=1);

session_start();
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/functions.php';

// Proteksi Autentikasi
requireAuth();
$user = currentUser();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    flash('error', 'ID slip gaji tidak valid.');
    redirect('index.php');
}

$stmt = $pdo->prepare("
    SELECT p.*, k.nama, k.email, k.no_hp, k.jabatan, k.nik
    FROM penggajian p
    JOIN karyawan k ON p.karyawan_id = k.id
    WHERE p.id = :id
");
$stmt->execute(['id' => $id]);
$row = $stmt->fetch();

if (!$row) {
    flash('error', 'Data slip gaji tidak ditemukan.');
    redirect('index.php');
}

$calc = hitungGaji(
    (float)$row['gaji_pokok'],
    (float)$row['lembur'],
    (float)$row['pinjaman']
);

$periodeFormatted = formatPeriode($row['periode']);
$phone = cleanPhone($row['no_hp'] ?? '');
$waMessage = formatPesanWhatsApp($row, $calc);
$waUrl = $phone !== '' 
    ? 'https://wa.me/' . $phone . '?text=' . urlencode($waMessage)
    : 'https://wa.me/?text=' . urlencode($waMessage);

$emailSubject = 'Slip Gaji Resmi - ' . $row['nama'] . ' (Periode ' . $periodeFormatted . ')';
$emailBody = "Halo " . $row['nama'] . ",\n\nBerikut adalah rincian slip gaji Anda untuk Periode " . $periodeFormatted . ":\n"
    . "----------------------------------------\n"
    . "Jabatan: " . $row['jabatan'] . "\n"
    . "Gaji Pokok: " . rupiah((float)$row['gaji_pokok']) . "\n"
    . "Lembur: " . rupiah((float)$row['lembur']) . "\n"
    . "Total Penghasilan: " . rupiah($calc['total_penghasilan']) . "\n"
    . "Potongan/Pinjaman: " . rupiah((float)$row['pinjaman']) . "\n"
    . "Total Potongan: " . rupiah($calc['total_potongan']) . "\n"
    . "----------------------------------------\n"
    . "GAJI BERSIH: " . rupiah($calc['gaji_bersih']) . "\n"
    . "----------------------------------------\n\n"
    . "Terima kasih atas kerja keras Anda.\nSalam,\nPT. GajiHub Indonesia";

$mailtoUrl = 'mailto:' . urlencode($row['email'] ?? '') 
    . '?subject=' . rawurlencode($emailSubject) 
    . '&body=' . rawurlencode($emailBody);
$pageTitle = 'GajiHub - Slip Gaji ' . $row['nama'] . ' (' . $periodeFormatted . ')';
$activePage = 'penggajian';
$noPrintHeader = true;
require __DIR__ . '/../includes/header.php';
?>

<main class="container page">
    <?php if ($msg = flash('success')): ?>
        <div class="alert success no-print"><i class="fa-solid fa-circle-check"></i> <?= e($msg) ?></div>
    <?php endif; ?>

    <div class="page-heading no-print" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 24px;">
        <div>
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 4px;">
                <a href="index.php" class="btn secondary" style="min-height: 32px; padding: 0 12px; font-size: 0.82rem;" title="Kembali ke Daftar Transaksi">
                    <i class="fa-solid fa-arrow-left"></i> Kembali
                </a>
                <span class="eyebrow" style="margin: 0;"><i class="fa-solid fa-file-invoice-dollar"></i> SLIP GAJI BULANAN</span>
            </div>
            <h1 style="margin: 4px 0 4px; font-size: 1.75rem;">Slip Gaji: <?= e($row['nama']) ?></h1>
            <p style="margin: 0; color: var(--muted); font-size: 0.88rem;">
                Periode <strong><?= $periodeFormatted ?></strong> • No. Slip: <strong>#SLIP-<?= str_pad((string)$row['id'], 4, '0', STR_PAD_LEFT) ?></strong>
            </p>
        </div>

        <!-- Action Bar Terpadu & Minimalis -->
        <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
            <a href="cetak_pdf.php?id=<?= (int)$row['id'] ?>&action=download" class="btn btn-pdf" title="Unduh langsung berkas PDF">
                <i class="fa-solid fa-file-arrow-down"></i> Unduh PDF
            </a>
            <button class="btn btn-print" type="button" onclick="window.print()" title="Cetak langsung slip gaji">
                <i class="fa-solid fa-print"></i> Cetak
            </button>
            <?php if ($phone !== ''): ?>
                <a class="btn btn-whatsapp" href="<?= $waUrl ?>" target="_blank" rel="noopener" title="Kirim rincian via WhatsApp">
                    <i class="fa-brands fa-whatsapp"></i> WhatsApp
                </a>
            <?php endif; ?>
            <button type="button" class="btn btn-email" id="btnSendEmail"
                    data-email="<?= e($row['email'] ?? '') ?>"
                    data-mailto="<?= e($mailtoUrl) ?>"
                    data-nama="<?= e($row['nama']) ?>"
                    title="Kirim rincian via Email">
                <i class="fa-regular fa-envelope"></i> Email
            </button>
            <a href="form.php?id=<?= (int)$row['id'] ?>" class="btn secondary" title="Edit Data Transaksi">
                <i class="fa-regular fa-pen-to-square"></i> Edit
            </a>
        </div>
    </div>

    <!-- Dokumen Slip Gaji (Area Target Render PDF) -->
    <section class="card payslip" id="printArea">
        <div class="payslip-badge-top">SLIP PEMBAYARAN GAJI RESMI</div>
        
        <div class="payslip-head">
            <div>
                <span class="eyebrow"><i class="fa-solid fa-building"></i> PT. GAJIHUB INDONESIA</span>
                <h2 style="margin:4px 0 6px; font-size:1.6rem;"><?= e($row['nama']) ?></h2>
                <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap; margin-top:4px;">
                    <span class="badge-role" style="font-size:0.85rem;"><i class="fa-solid fa-briefcase"></i> <?= e($row['jabatan']) ?></span>
                    <?php if (!empty($row['nik'])): ?>
                        <span class="badge" style="background:#f1f5f9; color:#475569; padding:2px 8px; border-radius:4px; font-size:0.8rem;">
                            <i class="fa-solid fa-id-card"></i> NIK: <?= e($row['nik']) ?>
                        </span>
                    <?php endif; ?>
                    <?php if (!empty($row['email'])): ?>
                        <span class="badge" style="background:#f0f9ff; color:#0284c7; padding:2px 8px; border-radius:4px; font-size:0.8rem;">
                            <i class="fa-regular fa-envelope"></i> <?= e($row['email']) ?>
                        </span>
                    <?php endif; ?>
                    <?php if (!empty($row['no_hp'])): ?>
                        <span class="badge" style="background:#f0fdf4; color:#16a34a; padding:2px 8px; border-radius:4px; font-size:0.8rem;">
                            <i class="fa-brands fa-whatsapp"></i> <?= e($row['no_hp']) ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="status-badge"><i class="fa-solid fa-circle-check"></i> LUNAS / TERCATAT</div>
        </div>

        <div class="meta-grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));">
            <div>
                <span>No. Slip Gaji</span>
                <strong>#SLIP-<?= str_pad((string)$row['id'], 4, '0', STR_PAD_LEFT) ?></strong>
            </div>
            <div>
                <span>Periode Bulan</span>
                <strong style="color:var(--primary); font-weight:800;"><?= $periodeFormatted ?></strong>
            </div>
            <div>
                <span>Tanggal Bayar</span>
                <strong><?= date('d-m-Y', strtotime($row['tanggal_bayar'])) ?></strong>
            </div>
            <div>
                <span>Email Karyawan</span>
                <strong style="color:#0284c7; word-break:break-all;"><?= !empty($row['email']) ? e($row['email']) : '-' ?></strong>
            </div>
            <div>
                <span>No. WhatsApp / HP</span>
                <strong><?= !empty($row['no_hp']) ? e($row['no_hp']) : '-' ?></strong>
            </div>
        </div>

        <div class="detail-list">
            <div class="section-divider-title">
                <i class="fa-solid fa-arrow-trend-up text-success"></i> Komponen Penghasilan
            </div>
            <div class="detail-row">
                <span>Gaji Pokok</span>
                <strong><?= rupiah((float)$row['gaji_pokok']) ?></strong>
            </div>
            <div class="detail-row">
                <span>Uang Lembur</span>
                <strong><?= rupiah((float)$row['lembur']) ?></strong>
            </div>
            <div class="detail-row subtotal positive">
                <span>Total Penghasilan (A)</span>
                <strong><?= rupiah($calc['total_penghasilan']) ?></strong>
            </div>

            <div class="section-divider-title">
                <i class="fa-solid fa-arrow-trend-down text-danger"></i> Komponen Potongan
            </div>
            <div class="detail-row">
                <span>Pinjaman Karyawan</span>
                <strong><?= rupiah((float)$row['pinjaman']) ?></strong>
            </div>
            <div class="detail-row subtotal negative">
                <span>Total Potongan (B)</span>
                <strong><?= rupiah($calc['total_potongan']) ?></strong>
            </div>
        </div>

        <div class="net-salary-highlight">
            <div class="net-label">
                <span>TOTAL GAJI BERSIH (A − B)</span>
                <small>Take Home Pay Periode <?= $periodeFormatted ?></small>
            </div>
            <div class="net-amount">
                <?= rupiah($calc['gaji_bersih']) ?>
            </div>
        </div>

        <?php if (!empty($row['catatan'])): ?>
            <div style="margin-top:16px; padding:10px 14px; background:#f8fafc; border-radius:8px; font-size:0.88rem; color:#475569;">
                <strong><i class="fa-regular fa-note-sticky"></i> Catatan:</strong> <?= e($row['catatan']) ?>
            </div>
        <?php endif; ?>

        <div class="payslip-footer-note">
            <p>Dokumen ini diterbitkan secara sah oleh Sistem Informasi GajiHub.</p>
        </div>
    </section>
</main>

<?php
$footerText = 'GajiHub · Download PDF & Cetak Browser';
$noPrintFooter = true;
require __DIR__ . '/../includes/footer.php';
