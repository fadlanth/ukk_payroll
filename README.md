# GajiHub — Sistem Manajemen & Penggajian Karyawan

Aplikasi web pengelolaan data karyawan dan transaksi slip gaji bulanan berbasis **PHP 8 (Native PDO)**, **MySQL / MariaDB**, **Vanilla CSS**, dan **JavaScript**. Dilengkapi fitur kalkulasi gaji otomatis, ekspor dokumen PDF resmi, verifikasi keamanan CAPTCHA, dan integrasi distribusi slip via WhatsApp & Email.

---

## 🚀 Panduan Instalasi & Menjalankan Aplikasi

### Persyaratan Sistem
* Web Server (XAMPP / Laragon / LAMPP)
* PHP 8.0 atau lebih baru
* MySQL / MariaDB

### Langkah Instalasi (XAMPP)
1. **Clone Repository ke folder `htdocs`**:
   ```bash
   cd C:\xampp\htdocs
   git clone https://github.com/fadlanth/ukk_payroll.git
   ```

2. **Jalankan Apache & MySQL** di XAMPP Control Panel.

3. **Import Database**:
   * Buka browser: `http://localhost/phpmyadmin`
   * Buat database baru bernama: **`ukk_payroll`**
   * Pilih tab **Import** -> pilih berkas: `database/ukk_payroll.sql`
   * Klik **Import / Go**.

4. **Akses Aplikasi di Browser**:
   * Buka URL: `http://localhost/ukk_payroll`

---

## 🔑 Akun Masuk Sistem (Default)

* **Email**: `admin@payroll.com`
* **Kata Sandi**: `admin123`
* **Fitur Autentikasi**: Registrasi Akun Baru (`auth/register.php`) & Reset Kata Sandi (`auth/lupa_password.php`).

---

## ✨ Fitur Utama

1. **Autentikasi & Keamanan Sesi**:
   - Login dengan enkripsi kata sandi `PASSWORD_BCRYPT`.
   - Validasi sesi (`requireAuth()`) di seluruh halaman aplikasi.
   - Proteksi keamanan terhadap serangan SQL Injection (PDO Parameterized Query) dan XSS (`htmlspecialchars`).

2. **Master Data Karyawan (CRUD)**:
   - Pengelolaan data karyawan lengkap dengan **NIK**, Nama, Jabatan, Email, No. WhatsApp, dan Gaji Pokok Acuan.
   - Pencarian real-time berdasarkan nama atau jabatan.

3. **Transaksi & Perhitungan Gaji Bulanan**:
   - Pemilihan periode gaji bulanan fleksibel.
   - Perhitungan otomatis komponen penghasilan dan potongan:
     * **Total Penghasilan** = Gaji Pokok + Uang Lembur
     * **Total Potongan** = Pinjaman Karyawan
     * **Gaji Bersih (Take Home Pay)** = Total Penghasilan − Total Potongan
   - Pencegahan penggajian ganda pada periode yang sama via `UNIQUE KEY (karyawan_id, periode)`.
   - Verifikasi CAPTCHA matematika dinamis sebelum penerbitan slip.

4. **Ekspor & Integrasi Distribusi**:
   - **Unduh Dokumen PDF Resmi** menggunakan library backend **Dompdf**.
   - **Pratinjau PDF Langsung** pada tab peramban.
   - **Kirim Rincian via WhatsApp** dengan pesan otomatis melalui `wa.me`.
   - **Kirim Rincian via Email** langsung terintegrasi dengan email client.
   - **Cetak Langsung** (*Print View*) yang responsif dan rapi.

5. **Antarmuka Minimalis & Responsif**:
   - Tata letak dashboard dan navigasi sidebar yang dapat diciutkan (*collapsible*).
   - Menu aksi praktis bertipe *Dropdown* (`[ ⋮ ]`) untuk menjaga kerapian tabel.

---

## 📁 Struktur Berkas Proyek

```text
ukk_payroll/
├── index.php                 # Dashboard statistik & ringkasan aktivitas
├── auth/                     # Modul Autentikasi Pengguna
│   ├── login.php             # Form masuk sistem
│   ├── register.php          # Registrasi pengguna baru
│   ├── lupa_password.php     # Form reset kata sandi
│   └── logout.php            # Logout dan pembersihan sesi
├── karyawan/                 # Modul Master Karyawan
│   ├── index.php             # Daftar data master karyawan & pencarian
│   ├── form.php              # Form tambah & edit data karyawan
│   └── delete.php            # Proses hapus karyawan (CSRF/Auth guarded)
├── penggajian/               # Modul Transaksi Penggajian
│   ├── index.php             # Riwayat slip gaji & filter periode
│   ├── form.php              # Form input slip gaji + verifikasi CAPTCHA
│   ├── detail.php            # Tampilan rincian slip gaji & bilah integrasi
│   ├── cetak_pdf.php         # Mesin pembuat file PDF resmi (Dompdf)
│   └── delete.php            # Proses hapus slip gaji
├── api/                      # Endpoint Asinkron (AJAX)
│   └── captcha_refresh.php   # Regenerasi soal CAPTCHA dinamis
├── assets/                   # Aset Frontend
│   ├── css/
│   │   └── style.css         # Desain sistem, tipografi, dan responsif
│   └── js/
│       └── app.js            # Logika interaktif, dialog SweetAlert2, dan dropdown
├── config/
│   ├── database.php          # Konfigurasi koneksi PDO MySQL
│   └── functions.php         # Kumpulan fungsi pembantu (baseUrl, rupiah, auth, dll.)
├── database/
│   └── ukk_payroll.sql       # Struktur tabel dan seed data awal
├── includes/
│   ├── header.php            # Template header & navigasi sidebar
│   └── footer.php            # Template penutup halaman
├── vendor/                   # Dependensi Composer (Dompdf & pustaka pendukung)
└── docs/                     # Dokumentasi rancangan tata letak
```

---

## 🛠️ Teknologi & Pustaka yang Digunakan

* **Backend**: PHP 8 (Native PDO)
* **Basis Data**: MySQL / MariaDB (InnoDB Engine)
* **Frontend**: Vanilla HTML5, CSS3, JavaScript (ES6)
* **Pustaka Pihak Ketiga**:
  * [Dompdf](https://github.com/dompdf/dompdf) untuk pembuatan berkas PDF
  * [Font Awesome 6](https://fontawesome.com/) untuk ikon grafis
  * [SweetAlert2](https://sweetalert2.github.io/) untuk kotak dialog konfirmasi
