<?php
declare(strict_types=1);

session_start();
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/functions.php';

// Proteksi Autentikasi
requireAuth();
$user = currentUser();

$q = trim($_GET['q'] ?? '');

$sql = "SELECT k.*, 
               (SELECT COUNT(*) FROM penggajian p WHERE p.karyawan_id = k.id) AS total_slip
        FROM karyawan k";
$params = [];

if ($q !== '') {
    $sql .= " WHERE k.nama LIKE :q OR k.jabatan LIKE :q OR k.email LIKE :q OR k.no_hp LIKE :q OR k.nik LIKE :q";
    $params['q'] = '%' . $q . '%';
}

$sql .= " ORDER BY k.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();
$pageTitle = 'GajiHub - Master Data Karyawan';
$activePage = 'karyawan';
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
        <span class="eyebrow"><i class="fa-solid fa-database"></i> MASTER DATA</span>
        <h1>Master Karyawan</h1>
        <p>Data profil karyawan. Dari halaman ini Anda bisa membuat slip gaji bulanan untuk karyawan tertentu.</p>
    </div>

    <section class="card">
        <div class="toolbar">
            <form class="search-bar" method="get">
                <input
                    type="search"
                    name="q"
                    value="<?= e($q) ?>"
                    placeholder="Cari nama, jabatan, email, no hp, nik..."
                >
                <button class="btn primary" type="submit"><i class="fa-solid fa-magnifying-glass"></i> Cari</button>
                <?php if ($q !== ''): ?>
                    <a class="btn secondary" href="index.php"><i class="fa-solid fa-rotate-left"></i> Reset</a>
                <?php endif; ?>
            </form>
            <a class="btn primary" href="form.php"><i class="fa-solid fa-user-plus"></i> Tambah Karyawan Baru</a>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>NIK</th>
                    <th>Nama & Kontak Karyawan</th>
                    <th>Jabatan</th>
                    <th>Gaji Pokok Acuan</th>
                    <th>Riwayat Slip</th>
                    <th>Aksi & Penggajian</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $row): ?>
                    <?php 
                        $phone = cleanPhone($row['no_hp'] ?? '');
                    ?>
                    <tr>
                        <td>
                            <strong style="font-size:0.95rem;"><?= e($row['nik'] ?? '-') ?></strong>
                        </td>
                        <td>
                            <strong style="font-size: 1rem;"><?= e($row['nama']) ?></strong>
                            <div class="contact-badges">
                                <?php if (!empty($row['email'])): ?>
                                    <a class="badge-link email" href="mailto:<?= e($row['email']) ?>">
                                        <i class="fa-regular fa-envelope"></i> <?= e($row['email']) ?>
                                    </a>
                                <?php endif; ?>
                                <?php if ($phone !== ''): ?>
                                    <a class="badge-link whatsapp" href="https://wa.me/<?= $phone ?>" target="_blank" rel="noopener">
                                        <i class="fa-brands fa-whatsapp"></i> <?= e($row['no_hp'] ?? '') ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td><span class="badge-role"><?= e($row['jabatan']) ?></span></td>
                        <td><strong><?= rupiah((float)$row['gaji_pokok_default']) ?></strong></td>
                        <td>
                            <a href="<?= baseUrl('penggajian/index.php?q=' . urlencode($row['nama'])) ?>" class="badge-link" style="background:#f1f5f9;color:#334155;">
                                <i class="fa-solid fa-receipt"></i> <?= (int)$row['total_slip'] ?> Slip Tersimpan
                            </a>
                        </td>
                        <td class="actions">
                            <div class="actions-group">
                                <a class="btn-action detail" href="<?= baseUrl('penggajian/form.php?karyawan_id=' . (int)$row['id']) ?>" title="Buat Slip Gaji untuk Karyawan Ini">
                                    <i class="fa-solid fa-plus"></i>
                                </a>
                                <a class="btn-action edit" href="form.php?id=<?= (int)$row['id'] ?>" title="Edit Profil">
                                    <i class="fa-regular fa-pen-to-square"></i>
                                </a>
                                <button type="button" class="btn-action delete btn-delete-swal" 
                                        data-id="<?= (int)$row['id'] ?>" 
                                        data-nama="<?= e($row['nama']) ?>"
                                        title="Hapus Karyawan">
                                    <i class="fa-regular fa-trash-can"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (!$rows): ?>
                    <tr><td colspan="6" class="empty">Data karyawan tidak ditemukan.</td></tr>
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
$footerText = 'GajiHub · Relasi Master Karyawan';
require __DIR__ . '/../includes/footer.php';
