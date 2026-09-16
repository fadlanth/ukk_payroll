<?php
declare(strict_types=1);

session_start();
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/functions.php';

// Proteksi Autentikasi
requireAuth();
$user = currentUser();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$isEdit = $id !== null && $id !== false;

$data = [
    'id' => $id ?: null,
    'nik' => '',
    'nama' => '',
    'email' => '',
    'no_hp' => '',
    'jabatan' => '',
    'gaji_pokok_default' => '5000000',
];

$errors = [];

if ($isEdit) {
    $stmt = $pdo->prepare("SELECT * FROM karyawan WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $found = $stmt->fetch();

    if (!$found) {
        flash('error', 'Data karyawan tidak ditemukan.');
        redirect('index.php');
    }

    $data = $found;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

    $data = [
        'id' => $postedId ?: null,
        'nik' => trim($_POST['nik'] ?? ''),
        'nama' => trim($_POST['nama'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'no_hp' => trim($_POST['no_hp'] ?? ''),
        'jabatan' => trim($_POST['jabatan'] ?? ''),
        'gaji_pokok_default' => $_POST['gaji_pokok_default'] ?? '',
    ];

    if ($data['nik'] !== '') {
        // Cek duplikasi NIK
        $nikCheckSql = "SELECT id FROM karyawan WHERE nik = :nik";
        $nikParams = ['nik' => $data['nik']];
        if ($postedId) {
            $nikCheckSql .= " AND id != :id";
            $nikParams['id'] = $postedId;
        }
        $nikCheckStmt = $pdo->prepare($nikCheckSql);
        $nikCheckStmt->execute($nikParams);
        if ($nikCheckStmt->fetch()) {
            $errors[] = 'NIK ' . $data['nik'] . ' sudah digunakan oleh karyawan lain.';
        }
    }

    if ($data['nama'] === '') {
        $errors[] = 'Nama karyawan wajib diisi.';
    }

    if ($data['email'] !== '' && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Format email tidak valid.';
    }

    if ($data['jabatan'] === '') {
        $errors[] = 'Jabatan wajib dipilih.';
    }

    $valPokok = $data['gaji_pokok_default'];
    if ($valPokok === '' || !is_numeric($valPokok) || (float)$valPokok < 0) {
        $errors[] = 'Gaji pokok default harus berupa angka 0 atau lebih.';
    }

    if (!$errors) {
        $values = [
            'nik' => $data['nik'] !== '' ? $data['nik'] : null,
            'nama' => $data['nama'],
            'email' => $data['email'] !== '' ? $data['email'] : null,
            'no_hp' => $data['no_hp'] !== '' ? $data['no_hp'] : null,
            'jabatan' => $data['jabatan'],
            'gaji_pokok_default' => (float)$data['gaji_pokok_default'],
        ];

        if ($postedId) {
            $stmt = $pdo->prepare(
                "UPDATE karyawan
                 SET nik = :nik,
                     nama = :nama,
                     email = :email,
                     no_hp = :no_hp,
                     jabatan = :jabatan,
                     gaji_pokok_default = :gaji_pokok_default
                 WHERE id = :id"
            );
            $values['id'] = $postedId;
            $stmt->execute($values);

            flash('success', 'Master data karyawan berhasil diperbarui.');
        } else {
            $stmt = $pdo->prepare(
                "INSERT INTO karyawan
                    (nik, nama, email, no_hp, jabatan, gaji_pokok_default)
                 VALUES
                    (:nik, :nama, :email, :no_hp, :jabatan, :gaji_pokok_default)"
            );
            $stmt->execute($values);

            flash('success', 'Master karyawan baru berhasil ditambahkan.');
        }

        redirect('index.php');
    }
}
$pageTitle = 'GajiHub - ' . ($isEdit ? 'Edit' : 'Tambah') . ' Master Karyawan';
$activePage = 'karyawan';
require __DIR__ . '/../includes/header.php';
?>

<main class="container page narrow">
    <div class="page-heading">
        <span class="eyebrow"><i class="fa-solid fa-id-card"></i> Master Karyawan</span>
        <h1><?= $isEdit ? 'Edit Profil Karyawan' : 'Tambah Karyawan Baru' ?></h1>
        <p>Data master karyawan hanya perlu diisi satu kali untuk digunakan pada penggajian tiap bulan.</p>
    </div>

    <?php if ($errors): ?>
        <div class="alert error">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <div>
                <strong>Periksa kembali input Anda:</strong>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= e($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    <?php endif; ?>

    <form class="card form-card" id="salaryForm" method="post">
        <?php if ($isEdit): ?>
            <input type="hidden" name="id" value="<?= (int)$data['id'] ?>">
        <?php endif; ?>

        <div class="form-grid">
            <div class="field">
                <label for="nik"><i class="fa-solid fa-id-card"></i> NIK (Nomor Induk Karyawan)</label>
                <input id="nik" name="nik" type="text" maxlength="20"
                       value="<?= e((string)($data['nik'] ?? '')) ?>"
                       placeholder="Contoh: NIK-001">
                <small style="color: var(--muted); font-size: 0.8rem;">Opsional. Harus unik jika diisi.</small>
            </div>

            <div class="field">
                <label for="nama"><i class="fa-regular fa-user"></i> Nama Lengkap Karyawan <span class="req">*</span></label>
                <input id="nama" name="nama" type="text" maxlength="100"
                       value="<?= e((string)$data['nama']) ?>"
                       placeholder="Contoh: Ahmad Fauzan" required>
            </div>

            <div class="field">
                <label for="email"><i class="fa-regular fa-envelope"></i> Alamat Email</label>
                <input id="email" name="email" type="email" maxlength="100"
                       value="<?= e((string)($data['email'] ?? '')) ?>"
                       placeholder="ahmad@perusahaan.com">
            </div>

            <div class="field">
                <label for="no_hp"><i class="fa-brands fa-whatsapp"></i> Nomor WhatsApp</label>
                <input id="no_hp" name="no_hp" type="text" maxlength="20"
                       value="<?= e((string)($data['no_hp'] ?? '')) ?>"
                       placeholder="Contoh: 081234567890">
            </div>

            <div class="field">
                <label for="jabatan"><i class="fa-solid fa-briefcase"></i> Jabatan / Posisi <span class="req">*</span></label>
                <select id="jabatan" name="jabatan" required>
                    <option value="">-- Pilih Jabatan --</option>
                    <?php foreach (['Staff', 'Admin', 'Supervisor', 'Programmer', 'Manager', 'Direktur'] as $option): ?>
                        <option value="<?= e($option) ?>" <?= ($data['jabatan'] ?? '') === $option ? 'selected' : '' ?>>
                            <?= e($option) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field">
                <label for="gaji_pokok_default"><i class="fa-solid fa-wallet"></i> Gaji Pokok Default (Rp) <span class="req">*</span></label>
                <input id="gaji_pokok_default" name="gaji_pokok_default" type="number" min="0" step="1000"
                       value="<?= e((string)$data['gaji_pokok_default']) ?>" required>
                <small style="color: var(--muted); font-size: 0.8rem;">Akan terisi otomatis saat membuat slip gaji bulanan.</small>
            </div>
        </div>

        <div class="form-actions">
            <a class="btn secondary" href="index.php"><i class="fa-solid fa-arrow-left"></i> Batal</a>
            <button class="btn primary" type="submit">
                <i class="fa-solid fa-floppy-disk"></i> <?= $isEdit ? 'Simpan Perubahan' : 'Simpan Karyawan' ?>
            </button>
        </div>
    </form>
</main>

<?php
$footerText = 'GajiHub · Form Master Karyawan';
require __DIR__ . '/../includes/footer.php';
