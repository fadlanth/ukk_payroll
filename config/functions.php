<?php
declare(strict_types=1);

/**
 * ==============================================================================
 * PAYROLLKU - FUNGSI PEMBANTU (HELPER FUNCTIONS)
 * ==============================================================================
 * Berkas ini berisi fungsi-fungsi modular yang digunakan di seluruh aplikasi:
 * 1. Keamanan & Sanitasi Input/Output
 * 2. Format Mata Uang, Waktu & Teks
 * 3. Navigasi & Penanganan Sesi Flash
 * 4. Autentikasi Pengguna & Session Guard
 * 5. Logika Bisnis Penggajian & Integrasi Pesan
 * ==============================================================================
 */

// ==============================================================================
// 1. KEAMANAN & SANITASI
// ==============================================================================

/**
 * Mencegah serangan XSS (Cross-Site Scripting) dengan membersihkan karakter khusus HTML.
 *
 * @param string|null $value Nilai teks yang akan ditampilkan ke HTML
 * @return string Teks yang aman dari eksekusi skrip berbahaya
 */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Membersihkan nomor telepon dan menyeragamkan ke format internasional Indonesia (62...).
 *
 * @param string|null $phone Nomor telepon dari input
 * @return string Nomor telepon bersih berawalan 62
 */
function cleanPhone(?string $phone): string
{
    if (!$phone) {
        return '';
    }

    $cleaned = preg_replace('/[^0-9]/', '', $phone);
    if (str_starts_with($cleaned, '0')) {
        $cleaned = '62' . substr($cleaned, 1);
    }

    return $cleaned;
}

// ==============================================================================
// 2. FORMAT MATA UANG & PERIODE
// ==============================================================================

/**
 * Mengubah nilai numerik menjadi format mata uang Rupiah Indonesia (Rp x.xxx.xxx).
 *
 * @param float|int $value Jumlah nominal angka
 * @return string Format Rupiah rapi
 */
function rupiah(float|int $value): string
{
    return 'Rp ' . number_format((float)$value, 0, ',', '.');
}

/**
 * Mengubah format periode YYYY-MM menjadi format nama bulan dalam Bahasa Indonesia.
 * Contoh: '2026-09' -> 'September 2026'
 *
 * @param string $periode Format YYYY-MM
 * @return string Nama bulan dan tahun
 */
function formatPeriode(string $periode): string
{
    $parts = explode('-', $periode);
    if (count($parts) !== 2) {
        return $periode;
    }

    $tahun = $parts[0];
    $bulanNum = (int)$parts[1];

    $namaBulan = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret',
        4 => 'April', 5 => 'Mei', 6 => 'Juni',
        7 => 'Juli', 8 => 'Agustus', 9 => 'September',
        10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];

    $bulanStr = $namaBulan[$bulanNum] ?? $parts[1];
    return "{$bulanStr} {$tahun}";
}

// ==============================================================================
// 3. NAVIGASI & NOTIFIKASI FLASH
// ==============================================================================

/**
 * Menghasilkan URL absolut relatif terhadap root proyek web.
 * Berfungsi sempurna baik di root server (php -S localhost:8000)
 * maupun di dalam subfolder Laragon (localhost/ukk_junior_web_programmer_crud_laragon/).
 *
 * @param string $path Jalur berkas relatif (misal: 'assets/css/style.css' atau 'karyawan/index.php')
 * @return string URL yang aman digunakan di href, src, ataupun redirect
 */
function baseUrl(string $path = ''): string
{
    static $basePrefix = null;
    if ($basePrefix === null) {
        $projectDir = str_replace('\\', '/', dirname(__DIR__));
        $docRoot = isset($_SERVER['DOCUMENT_ROOT']) ? str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT']) ?: $_SERVER['DOCUMENT_ROOT']) : '';

        if ($docRoot !== '' && stripos($projectDir, $docRoot) === 0) {
            $subPath = substr($projectDir, strlen($docRoot));
            $basePrefix = rtrim(str_replace('\\', '/', $subPath), '/');
        } else {
            $basePrefix = '';
        }
    }

    $cleanPath = ltrim($path, '/');
    if ($cleanPath === '') {
        return $basePrefix === '' ? '/' : $basePrefix . '/';
    }

    return ($basePrefix === '' ? '' : $basePrefix) . '/' . $cleanPath;
}

/**
 * Mengalihkan (redirect) pengguna ke halaman lain dan menghentikan eksekusi skrip.
 *
 * @param string $url Alamat tujuan pengalihan
 * @return never
 */
function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

/**
 * Menyimpan atau mengambil pesan notifikasi satu kali (Flash Message) dari sesi.
 *
 * @param string $key Kunci flash ('success' atau 'error')
 * @param string|null $message Jika diisi, menyimpan pesan. Jika null, mengambil pesan.
 * @return string|null Pesan notifikasi jika ada, atau null
 */
function flash(string $key, ?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['flash'][$key] = $message;
        return null;
    }

    $value = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $value;
}

// ==============================================================================
// 4. AUTENTIKASI PENGGUNA & SESSION GUARD
// ==============================================================================

/**
 * Memeriksa apakah pengguna saat ini sedang login dengan sesi aktif.
 *
 * @return bool True jika sudah login, False jika belum
 */
function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Penjaga sesi (Session Guard).
 * Memastikan halaman hanya bisa diakses setelah login. Jika belum, dialihkan ke auth/login.php.
 *
 * @return void
 */
function requireAuth(): void
{
    if (!isLoggedIn()) {
        flash('error', 'Silakan masuk (login) terlebih dahulu untuk mengakses halaman ini.');
        redirect(baseUrl('auth/login.php'));
    }
}

/**
 * Mengambil data ringkas pengguna yang sedang login saat ini.
 *
 * @return array{id: int|null, nama: string, email: string, role: string}|null
 */
function currentUser(): ?array
{
    if (!isLoggedIn()) {
        return null;
    }

    return [
        'id' => isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null,
        'nama' => (string)($_SESSION['user_name'] ?? 'User'),
        'email' => (string)($_SESSION['user_email'] ?? ''),
        'role' => (string)($_SESSION['user_role'] ?? 'admin'),
    ];
}

/**
 * Mendaftarkan data pengguna ke dalam variabel sesi setelah proses login berhasil.
 *
 * @param array $user Data baris pengguna dari tabel database `users`
 * @return void
 */
function loginUser(array $user): void
{
    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['user_name'] = (string)$user['nama'];
    $_SESSION['user_email'] = (string)$user['email'];
    $_SESSION['user_role'] = (string)($user['role'] ?? 'admin');
}

/**
 * Menghapus seluruh variabel sesi autentikasi saat pengguna keluar (logout).
 *
 * @return void
 */
function logoutUser(): void
{
    unset(
        $_SESSION['user_id'],
        $_SESSION['user_name'],
        $_SESSION['user_email'],
        $_SESSION['user_role']
    );
}

// ==============================================================================
// 5. LOGIKA BISNIS PENGGAJIAN & INTEGRASI
// ==============================================================================

/**
 * Menghitung rincian kalkulasi gaji bersih berdasarkan rumus standar UKK.
 * Rumus:
 * - Total Penghasilan = Gaji Pokok + Uang Lembur
 * - Total Potongan = Pinjaman Karyawan
 * - Gaji Bersih = Total Penghasilan - Total Potongan
 *
 * @param float $gajiPokok Nominal gaji pokok
 * @param float $lembur Nominal uang lembur
 * @param float $pinjaman Nominal potongan pinjaman
 * @return array{total_penghasilan: float, total_potongan: float, gaji_bersih: float}
 */
function hitungGaji(float $gajiPokok, float $lembur, float $pinjaman): array
{
    $totalPenghasilan = $gajiPokok + $lembur;
    $totalPotongan = $pinjaman;
    $gajiBersih = $totalPenghasilan - $totalPotongan;

    return [
        'total_penghasilan' => $totalPenghasilan,
        'total_potongan' => $totalPotongan,
        'gaji_bersih' => $gajiBersih,
    ];
}

/**
 * Membuat format pesan teks resmi slip gaji untuk dikirim melalui WhatsApp API.
 *
 * @param array $data Data transaksi slip dan karyawan
 * @param array $calc Hasil kalkulasi dari hitungGaji()
 * @return string Teks template pesan WhatsApp
 */
function formatPesanWhatsApp(array $data, array $calc): string
{
    $nama = (string)$data['nama'];
    $jabatan = (string)$data['jabatan'];
    $periode = formatPeriode((string)($data['periode'] ?? date('Y-m')));
    $pokok = rupiah((float)$data['gaji_pokok']);
    $lembur = rupiah((float)$data['lembur']);
    $penghasilan = rupiah((float)$calc['total_penghasilan']);
    $pinjaman = rupiah((float)$data['pinjaman']);
    $potongan = rupiah((float)$calc['total_potongan']);
    $bersih = rupiah((float)$calc['gaji_bersih']);
    $tanggal = !empty($data['tanggal_bayar']) ? date('d-m-Y', strtotime($data['tanggal_bayar'])) : date('d-m-Y');

    return "*SLIP GAJI RESMI - GAJIHUB*\n"
        . "----------------------------------------\n"
        . "👤 *Nama*: {$nama}\n"
        . "💼 *Jabatan*: {$jabatan}\n"
        . "📅 *Periode*: {$periode}\n"
        . "🗓️ *Tgl Pembayaran*: {$tanggal}\n"
        . "----------------------------------------\n"
        . "📈 *PENGHASILAN*\n"
        . "• Gaji Pokok: {$pokok}\n"
        . "• Lembur: {$lembur}\n"
        . "• *Total Penghasilan*: {$penghasilan}\n"
        . "----------------------------------------\n"
        . "📉 *POTONGAN*\n"
        . "• Pinjaman Karyawan: {$pinjaman}\n"
        . "• *Total Potongan*: {$potongan}\n"
        . "----------------------------------------\n"
        . "💰 *GAJI BERSIH (TAKE HOME PAY)*:\n"
        . "👉 *{$bersih}*\n"
        . "----------------------------------------\n"
        . "_Dokumen sah digenerate oleh Sistem GajiHub._";
}
