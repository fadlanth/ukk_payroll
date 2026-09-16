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

// Ambil semua karyawan untuk pilihan dropdown
$karyawanList = $pdo->query("SELECT id, nik, nama, jabatan, gaji_pokok_default, email, no_hp FROM karyawan ORDER BY nama ASC")->fetchAll();

$defaultKaryawanId = filter_input(INPUT_GET, 'karyawan_id', FILTER_VALIDATE_INT) ?: ($karyawanList[0]['id'] ?? '');

$data = [
    'id' => $id ?: null,
    'karyawan_id' => $defaultKaryawanId,
    'periode' => date('Y-m'),
    'gaji_pokok' => '',
    'lembur' => '0',
    'pinjaman' => '0',
    'tanggal_bayar' => date('Y-m-d'),
    'catatan' => '',
];

// Set gaji pokok acuan default jika ada karyawan yang dipilih
if (!$isEdit && $defaultKaryawanId) {
    foreach ($karyawanList as $k) {
        if ((int)$k['id'] === (int)$defaultKaryawanId) {
            $data['gaji_pokok'] = (string)$k['gaji_pokok_default'];
            break;
        }
    }
}

$errors = [];

if ($isEdit) {
    $stmt = $pdo->prepare("SELECT * FROM penggajian WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $found = $stmt->fetch();

    if (!$found) {
        flash('error', 'Data transaksi penggajian tidak ditemukan.');
        redirect('index.php');
    }

    $data = $found;
}

// Generate Captcha Perkalian jika belum ada di sesi
if (empty($_SESSION['captcha_slip_ans']) || empty($_SESSION['captcha_slip_q']) || empty($_SESSION['captcha_c1']) || empty($_SESSION['captcha_c2'])) {
    $c1 = rand(2, 9);
    $c2 = rand(2, 9);
    $_SESSION['captcha_c1'] = $c1;
    $_SESSION['captcha_c2'] = $c2;
    $_SESSION['captcha_slip_ans'] = $c1 * $c2;
    $_SESSION['captcha_slip_q'] = "{$c1} × {$c2}";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

    $data = [
        'id' => $postedId ?: null,
        'karyawan_id' => filter_input(INPUT_POST, 'karyawan_id', FILTER_VALIDATE_INT),
        'periode' => trim($_POST['periode'] ?? ''),
        'gaji_pokok' => $_POST['gaji_pokok'] ?? '',
        'lembur' => $_POST['lembur'] ?? '',
        'pinjaman' => $_POST['pinjaman'] ?? '',
        'tanggal_bayar' => trim($_POST['tanggal_bayar'] ?? ''),
        'catatan' => trim($_POST['catatan'] ?? ''),
    ];

    // 1. Validasi Captcha Perkalian
    $userCaptcha = filter_input(INPUT_POST, 'captcha', FILTER_VALIDATE_INT);
    $expectedCaptcha = $_SESSION['captcha_slip_ans'] ?? null;

    if ($userCaptcha === null || $userCaptcha === false || $userCaptcha !== $expectedCaptcha) {
        $errors[] = 'Jawaban Captcha perkalian salah (' . ($_SESSION['captcha_slip_q'] ?? '') . '). Silakan hitung kembali.';
    }

    // Refresh Captcha untuk percobaan berikutnya
    $c1 = rand(2, 9);
    $c2 = rand(2, 9);
    $_SESSION['captcha_c1'] = $c1;
    $_SESSION['captcha_c2'] = $c2;
    $_SESSION['captcha_slip_ans'] = $c1 * $c2;
    $_SESSION['captcha_slip_q'] = "{$c1} × {$c2}";

    // 2. Validasi Karyawan & Field
    if (!$data['karyawan_id']) {
        $errors[] = 'Pilih karyawan terlebih dahulu.';
    }

    if ($data['periode'] === '' || !preg_match('/^\d{4}-\d{2}$/', $data['periode'])) {
        $errors[] = 'Format periode tidak valid (contoh: 2026-09).';
    }

    if ($data['tanggal_bayar'] === '') {
        $errors[] = 'Tanggal pembayaran wajib diisi.';
    }

    foreach ([
        'gaji_pokok' => 'Gaji pokok',
        'lembur' => 'Uang lembur',
        'pinjaman' => 'Pinjaman karyawan',
    ] as $field => $label) {
        $value = $data[$field];
        if ($value === '' || !is_numeric($value) || (float)$value < 0) {
            $errors[] = $label . ' harus berupa angka 0 atau lebih.';
        }
    }

    // 3. Cek duplikasi transaksi karyawan pada periode yang sama
    if (!$errors) {
        $checkSql = "SELECT id FROM penggajian WHERE karyawan_id = :karyawan_id AND periode = :periode";
        $checkParams = [
            'karyawan_id' => $data['karyawan_id'],
            'periode' => $data['periode']
        ];
        if ($postedId) {
            $checkSql .= " AND id != :id";
            $checkParams['id'] = $postedId;
        }
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute($checkParams);
        if ($checkStmt->fetch()) {
            $errors[] = "Slip gaji untuk karyawan ini pada periode " . formatPeriode($data['periode']) . " sudah pernah dibuat. Silakan edit slip yang sudah ada.";
        }
    }

    if (!$errors) {
        $values = [
            'karyawan_id' => (int)$data['karyawan_id'],
            'periode' => $data['periode'],
            'gaji_pokok' => (float)$data['gaji_pokok'],
            'lembur' => (float)$data['lembur'],
            'pinjaman' => (float)$data['pinjaman'],
            'tanggal_bayar' => $data['tanggal_bayar'],
            'catatan' => $data['catatan'] !== '' ? $data['catatan'] : null,
        ];

        if ($postedId) {
            $stmt = $pdo->prepare(
                "UPDATE penggajian
                 SET karyawan_id = :karyawan_id,
                     periode = :periode,
                     gaji_pokok = :gaji_pokok,
                     lembur = :lembur,
                     pinjaman = :pinjaman,
                     tanggal_bayar = :tanggal_bayar,
                     catatan = :catatan
                 WHERE id = :id"
            );
            $values['id'] = $postedId;
            $stmt->execute($values);

            flash('success', 'Transaksi slip gaji berhasil diperbarui.');
            redirect('detail.php?id=' . $postedId);
        } else {
            $stmt = $pdo->prepare(
                "INSERT INTO penggajian
                    (karyawan_id, periode, gaji_pokok, lembur, pinjaman, tanggal_bayar, catatan)
                 VALUES
                    (:karyawan_id, :periode, :gaji_pokok, :lembur, :pinjaman, :tanggal_bayar, :catatan)"
            );
            $stmt->execute($values);
            $newId = (int)$pdo->lastInsertId();

            flash('success', 'Transaksi slip gaji periode ' . formatPeriode($data['periode']) . ' berhasil dibuat.');
            redirect('detail.php?id=' . $newId);
        }
    }
}

$pageTitle = 'GajiHub - ' . ($isEdit ? 'Edit' : 'Input') . ' Slip Gaji Bulanan';
$activePage = 'input_slip';
require __DIR__ . '/../includes/header.php';
?>

<main class="container page narrow">
    <div class="page-heading">
        <span class="eyebrow"><i class="fa-solid fa-receipt"></i> TRANSAKSI BULANAN</span>
        <h1><?= $isEdit ? 'Edit Slip Gaji Bulanan' : 'Input Slip Gaji Baru' ?></h1>
        <p>Pilih karyawan dan periode bulan penggajian untuk menghitung total gaji bersih.</p>
    </div>

    <?php if ($errors): ?>
        <div class="alert error">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <div>
                <strong>Periksa kembali data Anda:</strong>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= e($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    <?php endif; ?>

    <form id="payrollForm" class="card form-card" method="post">
        <?php if ($isEdit): ?>
            <input type="hidden" name="id" value="<?= (int)$data['id'] ?>">
        <?php endif; ?>


        <div class="form-grid">
            <!-- Pilih Karyawan -->
            <div class="field full">
                <label for="karyawan_id"><i class="fa-solid fa-user-check"></i> Pilih Karyawan <span class="req">*</span></label>
                <select id="karyawan_id" name="karyawan_id" required>
                    <option value="">-- Pilih Karyawan Terdaftar --</option>
                    <?php foreach ($karyawanList as $k): ?>
                        <option value="<?= (int)$k['id'] ?>"
                                data-pokok="<?= (float)$k['gaji_pokok_default'] ?>"
                                data-jabatan="<?= e($k['jabatan']) ?>"
                                <?= (int)$data['karyawan_id'] === (int)$k['id'] ? 'selected' : '' ?>>
                            <?= !empty($k['nik']) ? '[' . e($k['nik']) . '] ' : '' ?><?= e($k['nama']) ?> — <?= e($k['jabatan']) ?> (Default: <?= rupiah((float)$k['gaji_pokok_default']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Periode & Tanggal Bayar -->
            <div class="field">
                <label for="periode"><i class="fa-regular fa-calendar"></i> Periode Bulan Gaji <span class="req">*</span></label>
                <input id="periode" name="periode" type="month"
                       value="<?= e((string)$data['periode']) ?>" required>
            </div>

            <div class="field">
                <label for="tanggal_bayar"><i class="fa-regular fa-calendar-check"></i> Tanggal Pembayaran <span class="req">*</span></label>
                <input id="tanggal_bayar" name="tanggal_bayar" type="date"
                       value="<?= e((string)$data['tanggal_bayar']) ?>" required>
            </div>

            <!-- Tabel Kalkulasi Gaji -->
            <div class="field full">
                <label style="margin-bottom: 8px; display:block;">
                    <i class="fa-solid fa-table"></i> Rincian Komponen Gaji <span class="req">*</span>
                </label>
                <div class="payroll-calc-table">
                    <div class="payroll-calc-header">
                        <div class="calc-col penghasilan">
                            <i class="fa-solid fa-arrow-trend-up"></i> PENGHASILAN
                        </div>
                        <div class="calc-col potongan">
                            <i class="fa-solid fa-arrow-trend-down"></i> POTONGAN
                        </div>
                    </div>

                    <div class="payroll-calc-body">
                        <!-- Gaji Pokok | Pinjaman -->
                        <div class="calc-row">
                            <div class="calc-cell label">Gaji Pokok</div>
                            <div class="calc-cell input">
                                <div class="input-rp-wrap">
                                    <span class="rp-prefix">Rp</span>
                                    <input id="gaji_pokok" name="gaji_pokok" type="number" min="0" step="1000"
                                           value="<?= e((string)$data['gaji_pokok']) ?>" required placeholder="0">
                                </div>
                            </div>
                            <div class="calc-cell label">Pinjaman Karyawan</div>
                            <div class="calc-cell input">
                                <div class="input-rp-wrap">
                                    <span class="rp-prefix">Rp</span>
                                    <input id="pinjaman" name="pinjaman" type="number" min="0" step="1000"
                                           value="<?= e((string)$data['pinjaman']) ?>" required placeholder="0">
                                </div>
                            </div>
                        </div>

                        <!-- Lembur | (kosong) -->
                        <div class="calc-row">
                            <div class="calc-cell label">Lembur</div>
                            <div class="calc-cell input">
                                <div class="input-rp-wrap">
                                    <span class="rp-prefix">Rp</span>
                                    <input id="lembur" name="lembur" type="number" min="0" step="1000"
                                           value="<?= e((string)$data['lembur']) ?>" required placeholder="0">
                                </div>
                            </div>
                            <div class="calc-cell label" style="color:var(--muted); font-style:italic;">—</div>
                            <div class="calc-cell input"></div>
                        </div>

                        <!-- Subtotal -->
                        <div class="calc-row subtotal-row">
                            <div class="calc-cell label subtotal-label">Total Penghasilan</div>
                            <div class="calc-cell input subtotal-val positive">
                                <span id="liveTotalPenghasilan">Rp 0</span>
                            </div>
                            <div class="calc-cell label subtotal-label">Total Potongan</div>
                            <div class="calc-cell input subtotal-val negative">
                                <span id="liveTotalPotongan">Rp 0</span>
                            </div>
                        </div>
                    </div>

                    <!-- Gaji Bersih -->
                    <div class="payroll-calc-footer">
                        <div class="calc-net-label">
                            <i class="fa-solid fa-calculator"></i> Gaji Bersih
                        </div>
                        <div class="calc-net-value" id="liveGajiBersih">Rp 0</div>
                    </div>
                </div>
            </div>

            <!-- Captcha Verifikasi -->
            <div class="field full">
                <div class="captcha-card">
                    <div class="captcha-header">
                        <label for="captcha">
                            <i class="fa-solid fa-shield-halved"></i> Verifikasi Keamanan (Captcha) <span class="req">*</span>
                        </label>
                        <button type="button" class="btn-refresh-captcha" id="btnRefreshCaptcha" data-url="<?= baseUrl('api/captcha_refresh.php') ?>" title="Ganti soal captcha">
                            <i class="fa-solid fa-rotate-right"></i> Ganti Soal
                        </button>
                    </div>
                    <div class="captcha-box-wrapper">
                        <div class="captcha-badge" id="captchaBadge">
                            <div class="captcha-noise-line"></div>
                            <span class="captcha-digit tilt-left" id="captchaC1"><?= (int)$_SESSION['captcha_c1'] ?></span>
                            <span class="captcha-digit op">×</span>
                            <span class="captcha-digit tilt-right" id="captchaC2"><?= (int)$_SESSION['captcha_c2'] ?></span>
                            <span class="captcha-digit op">=</span>
                            <span class="captcha-digit" style="color:#7c3aed;">?</span>
                        </div>
                        <input type="number" id="captcha" name="captcha"
                               placeholder="Tulis hasilnya..." required autocomplete="off"
                               style="max-width:200px; font-weight:700; font-size:1.1rem;">
                    </div>
                    <div class="captcha-note">
                        <i class="fa-solid fa-circle-info"></i>
                        Hitung hasil perkalian di atas sebelum menyimpan slip gaji.
                    </div>
                </div>
            </div>
        </div>

        <div class="form-actions">
            <a class="btn secondary" href="index.php"><i class="fa-solid fa-arrow-left"></i> Kembali</a>
            <button class="btn primary" type="submit">
                <i class="fa-solid fa-check-circle"></i> <?= $isEdit ? 'Simpan Perubahan Slip' : 'Terbitkan Slip Gaji' ?>
            </button>
        </div>
    </form>
    <script>
    // Auto-fill Gaji Pokok saat Karyawan dipilih & Live Calculation
    document.addEventListener('DOMContentLoaded', () => {
        const selectKaryawan = document.getElementById('karyawan_id');
        const inputPokok = document.getElementById('gaji_pokok');
        const inputLembur = document.getElementById('lembur');
        const inputPinjaman = document.getElementById('pinjaman');
        const liveBersih = document.getElementById('liveGajiBersih');
        const livePenghasilan = document.getElementById('liveTotalPenghasilan');
        const livePotongan = document.getElementById('liveTotalPotongan');

        function fmt(n) {
            return 'Rp ' + n.toLocaleString('id-ID');
        }

        function updateLiveCalc() {
            const pokok = parseFloat(inputPokok.value) || 0;
            const lembur = parseFloat(inputLembur.value) || 0;
            const pinjaman = parseFloat(inputPinjaman.value) || 0;
            const totalPenghasilan = pokok + lembur;
            const totalPotongan = pinjaman;
            const bersih = totalPenghasilan - totalPotongan;
            if (livePenghasilan) livePenghasilan.textContent = fmt(totalPenghasilan);
            if (livePotongan) livePotongan.textContent = fmt(totalPotongan);
            if (liveBersih) {
                liveBersih.textContent = fmt(bersih);
                liveBersih.style.color = bersih >= 0 ? 'var(--success)' : 'var(--danger)';
            }
        }

        if (selectKaryawan) {
            selectKaryawan.addEventListener('change', () => {
                const selected = selectKaryawan.options[selectKaryawan.selectedIndex];
                const defaultPokok = selected.getAttribute('data-pokok');
                if (defaultPokok && (!inputPokok.value || inputPokok.value === '0')) {
                    inputPokok.value = defaultPokok;
                }
                updateLiveCalc();
            });
        }

        [inputPokok, inputLembur, inputPinjaman].forEach(el => {
            if (el) el.addEventListener('input', updateLiveCalc);
        });

        updateLiveCalc();
    });
    </script>
</main>

<?php
$footerText = 'GajiHub · Form Transaksi Penggajian';
require __DIR__ . '/../includes/footer.php';
