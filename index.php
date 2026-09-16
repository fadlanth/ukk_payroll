<?php
declare(strict_types=1);

session_start();
require __DIR__ . '/config/database.php';
require __DIR__ . '/config/functions.php';

// Proteksi Autentikasi
requireAuth();
$user = currentUser();

// Total Master Karyawan
$totalKaryawan = (int) $pdo->query("SELECT COUNT(*) FROM karyawan")->fetchColumn();

// Periode aktif terbaru
$latestPeriode = $pdo->query("SELECT periode FROM penggajian ORDER BY periode DESC LIMIT 1")->fetchColumn() ?: date('Y-m');

// Total Pengeluaran Gaji Periode Aktif
$stmtSum = $pdo->prepare("SELECT COALESCE(SUM(gaji_pokok + lembur - pinjaman), 0) FROM penggajian WHERE periode = :periode");
$stmtSum->execute(['periode' => $latestPeriode]);
$totalGajiBulanIni = (float) $stmtSum->fetchColumn();

// Total Seluruh Slip Terbit
$totalSlipAll = (int) $pdo->query("SELECT COUNT(*) FROM penggajian")->fetchColumn();

// 5 Transaksi Penggajian Terakhir
$recent = $pdo->query(
    "SELECT p.id, p.periode, p.gaji_pokok, p.lembur, p.pinjaman, p.tanggal_bayar,
            (p.gaji_pokok + p.lembur - p.pinjaman) AS gaji_bersih,
            k.nama, k.jabatan, k.email, k.no_hp
     FROM penggajian p
     JOIN karyawan k ON p.karyawan_id = k.id
     ORDER BY p.id DESC
     LIMIT 5"
)->fetchAll();
$pageTitle = 'GajiHub - Dashboard Penggajian';
$activePage = 'dashboard';
require __DIR__ . '/includes/header.php';
?>

<main class="container page">
    <?php if ($msg = flash('success')): ?>
        <div class="alert success"><i class="fa-solid fa-circle-check"></i> <?= e($msg) ?></div>
    <?php endif; ?>

    <?php if ($msg = flash('error')): ?>
        <div class="alert error"><i class="fa-solid fa-triangle-exclamation"></i> <?= e($msg) ?></div>
    <?php endif; ?>

    <div class="page-heading" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 24px;">
        <div>
            <span class="eyebrow"><i class="fa-solid fa-gauge-high"></i> DASHBOARD</span>
            <h1 style="margin: 4px 0 6px; font-size: 1.75rem;">Ringkasan Penggajian</h1>
            <p style="margin: 0; color: var(--muted);">Selamat datang kembali, <strong><?= e($user['nama'] ?? 'Admin') ?></strong>. Akses cepat transaksi dan master data.</p>
        </div>
        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <a class="btn primary" href="<?= baseUrl('penggajian/form.php') ?>"><i class="fa-solid fa-plus"></i> Input Slip Gaji</a>
            <a class="btn secondary" href="<?= baseUrl('penggajian/index.php') ?>"><i class="fa-solid fa-receipt"></i> Riwayat Slip</a>
        </div>
    </div>

    <!-- Kartu Statistik -->
    <section class="stats">
        <article class="stat-card">
            <div class="stat-icon"><i class="fa-solid fa-users text-primary"></i></div>
            <div>
                <span>Master Karyawan</span>
                <strong><?= $totalKaryawan ?> Orang</strong>
            </div>
        </article>
        <article class="stat-card">
            <div class="stat-icon"><i class="fa-solid fa-money-bill-trend-up text-success"></i></div>
            <div>
                <span>Gaji Periode <?= formatPeriode($latestPeriode) ?></span>
                <strong class="text-success"><?= rupiah($totalGajiBulanIni) ?></strong>
            </div>
        </article>
        <article class="stat-card">
            <div class="stat-icon"><i class="fa-solid fa-receipt text-warning"></i></div>
            <div>
                <span>Total Slip Terbit</span>
                <strong><?= $totalSlipAll ?> Transaksi</strong>
            </div>
        </article>
    </section>

    <!-- 5 Transaksi Penggajian Terakhir -->
    <section class="card">
        <div class="section-title">
            <div>
                <span class="eyebrow">Aktivitas Terkini</span>
                <h2><i class="fa-solid fa-clock-rotate-left"></i> 5 Slip Gaji Terakhir</h2>
            </div>
            <a class="btn secondary" href="<?= baseUrl('penggajian/index.php') ?>"><i class="fa-solid fa-arrow-right"></i> Semua Transaksi</a>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>#No Slip</th>
                    <th>Periode</th>
                    <th>Nama & Kontak Karyawan</th>
                    <th>Jabatan</th>
                    <th>Gaji Bersih</th>
                    <th>Aksi & Integrasi</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($recent as $row): ?>
                    <?php
                        $phone = cleanPhone($row['no_hp'] ?? '');
                        $calc = hitungGaji((float)$row['gaji_pokok'], (float)$row['lembur'], (float)$row['pinjaman']);
                        $waText = urlencode(formatPesanWhatsApp($row, $calc));
                    ?>
                    <tr>
                        <td>
                            <a href="<?= baseUrl('penggajian/detail.php?id=' . (int)$row['id']) ?>" title="Lihat Rincian Slip Gaji" style="font-weight:700; color:var(--primary);">
                                #SLIP-<?= str_pad((string)$row['id'], 4, '0', STR_PAD_LEFT) ?>
                            </a>
                        </td>
                        <td>
                            <span class="badge" style="background:#eef2ff; padding:3px 8px; border-radius:6px;">
                                <?= formatPeriode($row['periode']) ?>
                            </span>
                        </td>
                        <td>
                            <strong style="color:var(--text);"><?= e($row['nama']) ?></strong>
                            <?php if (!empty($row['email'])): ?>
                                <div>
                                    <a href="mailto:<?= e($row['email']) ?>" style="display:inline-flex; align-items:center; gap:4px; font-size:0.78rem; color:#0284c7; text-decoration:none; margin-top:2px;" title="<?= e($row['email']) ?>">
                                        <i class="fa-regular fa-envelope"></i> <?= e($row['email']) ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge-role"><?= e($row['jabatan']) ?></span></td>
                        <td><strong class="text-success"><?= rupiah((float)$row['gaji_bersih']) ?></strong></td>
                        <td class="actions">
                            <div class="actions-group">
                                <a class="btn-action detail" href="<?= baseUrl('penggajian/detail.php?id=' . (int)$row['id']) ?>" title="Lihat Rincian Slip Gaji">
                                    <i class="fa-regular fa-eye"></i>
                                </a>
                                <details class="dropdown action-dropdown">
                                    <summary class="btn-action" title="Menu Opsi & Integrasi">
                                        <i class="fa-solid fa-ellipsis-vertical"></i>
                                    </summary>
                                    <div class="dropdown-menu">
                                        <a href="<?= baseUrl('penggajian/cetak_pdf.php?id=' . (int)$row['id'] . '&action=download') ?>">
                                            <i class="fa-solid fa-file-arrow-down" style="color:var(--pdf-red);"></i> Unduh File PDF
                                        </a>
                                        <a href="<?= baseUrl('penggajian/cetak_pdf.php?id=' . (int)$row['id'] . '&action=preview') ?>" target="_blank">
                                            <i class="fa-solid fa-file-lines" style="color:var(--primary);"></i> Pratinjau PDF
                                        </a>
                                        <?php if ($phone !== ''): ?>
                                            <a href="https://wa.me/<?= $phone ?>?text=<?= $waText ?>" target="_blank" rel="noopener">
                                                <i class="fa-brands fa-whatsapp" style="color:var(--whatsapp);"></i> Kirim WhatsApp
                                            </a>
                                        <?php endif; ?>
                                        <?php if (!empty($row['email'])): ?>
                                            <a href="mailto:<?= e($row['email']) ?>">
                                                <i class="fa-regular fa-envelope" style="color:var(--email-blue);"></i> Kirim Email
                                            </a>
                                        <?php endif; ?>
                                        <hr>
                                        <a href="<?= baseUrl('penggajian/form.php?id=' . (int)$row['id']) ?>">
                                            <i class="fa-regular fa-pen-to-square" style="color:#475569;"></i> Edit Transaksi
                                        </a>
                                    </div>
                                </details>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (!$recent): ?>
                    <tr><td colspan="6" class="empty">Belum ada transaksi penggajian terbit.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

<?php
$footerText = 'GajiHub · Relasi Master Karyawan + Transaksi Bulanan';
require __DIR__ . '/includes/footer.php';
