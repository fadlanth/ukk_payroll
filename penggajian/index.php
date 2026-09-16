<?php
declare(strict_types=1);

session_start();
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/functions.php';

// Proteksi Autentikasi
requireAuth();
$user = currentUser();

$periode = trim($_GET['periode'] ?? '');
$q = trim($_GET['q'] ?? '');

// Ambil list semua periode yang ada di database untuk filter
$periodeList = $pdo->query("SELECT DISTINCT periode FROM penggajian ORDER BY periode DESC")->fetchAll(PDO::FETCH_COLUMN);

$sql = "SELECT p.*, k.nama, k.email, k.no_hp, k.jabatan,
               (p.gaji_pokok + p.lembur - p.pinjaman) AS gaji_bersih
        FROM penggajian p
        JOIN karyawan k ON p.karyawan_id = k.id
        WHERE 1=1";
$params = [];

if ($periode !== '') {
    $sql .= " AND p.periode = :periode";
    $params['periode'] = $periode;
}

if ($q !== '') {
    $sql .= " AND (k.nama LIKE :q OR k.jabatan LIKE :q OR p.catatan LIKE :q)";
    $params['q'] = '%' . $q . '%';
}

$sql .= " ORDER BY p.periode DESC, p.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

// Hitung total pengeluaran gaji pada filter aktif
$totalGajiFilter = array_sum(array_column($rows, 'gaji_bersih'));
$totalSlipCount = count($rows);
$pageTitle = 'GajiHub - Daftar Transaksi Penggajian Bulanan';
$activePage = 'penggajian';
require __DIR__ . '/../includes/header.php';
?>

<main class="container page">
    <?php if ($msg = flash('success')): ?>
        <div class="alert success"><i class="fa-solid fa-circle-check"></i> <?= e($msg) ?></div>
    <?php endif; ?>

    <?php if ($msg = flash('error')): ?>
        <div class="alert error"><i class="fa-solid fa-triangle-exclamation"></i> <?= e($msg) ?></div>
    <?php endif; ?>

    <div class="page-heading">
        <span class="eyebrow"><i class="fa-solid fa-calendar-days"></i> PENGGAJIAN BULANAN</span>
        <h1>Transaksi & Riwayat Penggajian</h1>
        <p>Kelola seluruh slip gaji karyawan per periode bulan, unduh PDF, dan kirim rincian via WhatsApp.</p>
    </div>

    <!-- Filter & Toolbar Bar -->
    <section class="card" style="margin-bottom: 24px;">
        <form method="get" class="filter-grid" style="display:flex; gap:12px; flex-wrap:wrap; align-items:center;">
            <div style="flex: 1; min-width: 200px;">
                <label style="font-size:0.8rem; font-weight:700; color:var(--muted);"><i class="fa-regular fa-calendar"></i> FILTER PERIODE BULAN</label>
                <select name="periode" onchange="this.form.submit()">
                    <option value="">-- Semua Periode Bulan --</option>
                    <?php foreach ($periodeList as $p): ?>
                        <option value="<?= e($p) ?>" <?= $periode === $p ? 'selected' : '' ?>>
                            <?= formatPeriode($p) ?> (<?= e($p) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="flex: 1.5; min-width: 250px;">
                <label style="font-size:0.8rem; font-weight:700; color:var(--muted);"><i class="fa-solid fa-magnifying-glass"></i> CARI KARYAWAN / JABATAN</label>
                <input type="search" name="q" value="<?= e($q) ?>" placeholder="Ketik nama karyawan atau jabatan...">
            </div>

            <div style="display:flex; gap:8px; align-self:flex-end;">
                <button class="btn primary" type="submit"><i class="fa-solid fa-filter"></i> Terapkan</button>
                <?php if ($periode !== '' || $q !== ''): ?>
                    <a class="btn secondary" href="index.php"><i class="fa-solid fa-rotate-left"></i> Reset</a>
                <?php endif; ?>
            </div>
        </form>
    </section>

    <!-- Stat Ringkasan Filter -->
    <section class="stats">
        <article class="stat-card">
            <div class="stat-icon"><i class="fa-solid fa-calendar-check text-primary"></i></div>
            <div>
                <span>Periode Terpilih</span>
                <strong><?= $periode !== '' ? formatPeriode($periode) : 'Semua Periode' ?></strong>
            </div>
        </article>
        <article class="stat-card">
            <div class="stat-icon"><i class="fa-solid fa-file-invoice text-warning"></i></div>
            <div>
                <span>Total Slip Ditemukan</span>
                <strong><?= $totalSlipCount ?> Slip</strong>
            </div>
        </article>
        <article class="stat-card">
            <div class="stat-icon"><i class="fa-solid fa-money-bill-wave text-success"></i></div>
            <div>
                <span>Total Gaji Bersih</span>
                <strong class="text-success"><?= rupiah($totalGajiFilter) ?></strong>
            </div>
        </article>
    </section>

    <!-- Tabel Data Penggajian -->
    <section class="card">
        <div class="section-title" style="margin-bottom: 16px;">
            <div>
                <span class="eyebrow">Daftar Transaksi</span>
                <h2>Data Slip Gaji Terbit</h2>
            </div>
            <a class="btn primary" href="form.php"><i class="fa-solid fa-plus-circle"></i> Input Slip Gaji</a>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>#No Slip</th>
                    <th>Periode Gaji</th>
                    <th>Nama & Jabatan Karyawan</th>
                    <th>Gaji Pokok</th>
                    <th>Lembur</th>
                    <th>Potongan</th>
                    <th>Gaji Bersih</th>
                    <th>Aksi</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $row): ?>
                    <?php 
                        $phone = cleanPhone($row['no_hp'] ?? '');
                        $calc = hitungGaji((float)$row['gaji_pokok'], (float)$row['lembur'], (float)$row['pinjaman']);
                        $waText = urlencode(formatPesanWhatsApp($row, $calc));
                    ?>
                    <tr>
                        <td>
                            <a href="detail.php?id=<?= (int)$row['id'] ?>" title="Lihat Rincian Slip Gaji" style="font-weight:700; color:var(--primary);">
                                #SLIP-<?= str_pad((string)$row['id'], 4, '0', STR_PAD_LEFT) ?>
                            </a>
                        </td>
                        <td>
                            <span class="badge" style="background:#eef2ff; padding:4px 8px; border-radius:6px;">
                                <i class="fa-regular fa-calendar"></i> <?= formatPeriode($row['periode']) ?>
                            </span>
                        </td>
                        <td>
                            <strong style="font-size:0.96rem; color:var(--text);"><?= e($row['nama']) ?></strong>
                            <div style="font-size:0.82rem; color:var(--muted); font-weight:500;"><?= e($row['jabatan']) ?></div>
                            <?php if (!empty($row['email'])): ?>
                                <a href="mailto:<?= e($row['email']) ?>" style="display:inline-flex; align-items:center; gap:5px; font-size:0.78rem; color:#0284c7; margin-top:3px; text-decoration:none;" title="Kirim email ke <?= e($row['email']) ?>">
                                    <i class="fa-regular fa-envelope"></i> <?= e($row['email']) ?>
                                </a>
                            <?php endif; ?>
                        </td>
                        <td><?= rupiah((float)$row['gaji_pokok']) ?></td>
                        <td><span class="text-success">+<?= rupiah((float)$row['lembur']) ?></span></td>
                        <td><span class="text-danger">-<?= rupiah((float)$row['pinjaman']) ?></span></td>
                        <td><strong class="text-success" style="font-size:1.05rem;"><?= rupiah((float)$row['gaji_bersih']) ?></strong></td>
                        <td class="actions">
                            <div class="actions-group">
                                <a class="btn-action detail" href="detail.php?id=<?= (int)$row['id'] ?>" title="Lihat Rincian Slip Gaji">
                                    <i class="fa-regular fa-eye"></i>
                                </a>
                                <details class="dropdown action-dropdown">
                                    <summary class="btn-action" title="Menu Opsi & Integrasi">
                                        <i class="fa-solid fa-ellipsis-vertical"></i>
                                    </summary>
                                    <div class="dropdown-menu">
                                        <a href="cetak_pdf.php?id=<?= (int)$row['id'] ?>&action=download">
                                            <i class="fa-solid fa-file-arrow-down" style="color:var(--pdf-red);"></i> Unduh File PDF
                                        </a>
                                        <a href="cetak_pdf.php?id=<?= (int)$row['id'] ?>&action=preview" target="_blank">
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
                                        <a href="form.php?id=<?= (int)$row['id'] ?>">
                                            <i class="fa-regular fa-pen-to-square" style="color:#475569;"></i> Edit Slip
                                        </a>
                                        <button type="button" class="btn-delete-swal"
                                                data-id="<?= (int)$row['id'] ?>"
                                                data-nama="Slip <?= e($row['nama']) ?> Periode <?= formatPeriode($row['periode']) ?>"
                                                style="color:var(--danger);">
                                            <i class="fa-regular fa-trash-can"></i> Hapus Slip
                                        </button>
                                    </div>
                                </details>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (!$rows): ?>
                    <tr><td colspan="8" class="empty">Tidak ada data penggajian untuk filter ini.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

<!-- Hidden Form for SweetAlert Delete -->
<form id="deleteForm" method="post" action="delete.php" style="display:none;">
    <input type="hidden" name="id" id="deleteId">
</form>

<?php
$footerText = 'GajiHub · Transaksi Penggajian Bulanan';
require __DIR__ . '/../includes/footer.php';
