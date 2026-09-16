# PayrollKu — Sistem Penggajian GajiHub

Aplikasi Pengelolaan & Transaksi Slip Gaji Bulanan berbasis **PHP 8 (Native PDO)**, **MySQL / MariaDB**, **Vanilla CSS**, dan **JavaScript**. Didesain khusus untuk mempermudah manajemen gaji bulanan.

---

## 🔑 Akun Uji Coba (Login UKK)
Untuk kemudahan pengujian saat demo di depan penguji:
* **Email / Gmail**: `admin@payroll.com`
* **Kata Sandi**: `admin123`
* **Fitur Tambahan Auth**: Registrasi Akun Baru (`register.php`) & Reset Kata Sandi (`lupa_password.php`).

---

## 🚀 Fitur Utama

1. **Sistem Autentikasi Pengguna & Keamanan Sesi**:
   - Login menggunakan Email/Gmail dan Sandi (`password_hash` & `password_verify` BCRYPT).
   - Register akun admin baru dengan validasi konfirmasi password & email unik.
   - Fitur Lupa / Reset Sandi langsung dari form login.
   - Proteksi sesi (`requireAuth()`) di seluruh halaman aplikasi & aksi delete.

2. **Arsitektur Database Relasional (Opsi 1 - Master & Transaksi)**:
   - **Tabel `karyawan`**: Data master karyawan (Nama, Email, No HP, Jabatan, Gaji Pokok Default).
   - **Tabel `penggajian`**: Transaksi riwayat gaji bulanan karyawan per periode (`YYYY-MM`).
   - **Relasi Foreign Key**: `karyawan_id` terhubung dengan `ON DELETE CASCADE`.
   - **Integritas Periode**: `UNIQUE KEY (karyawan_id, periode)` mencegah duplikasi input slip gaji pada karyawan yang sama di bulan yang sama.

3. **Input Slip Gaji & Verifikasi Keamanan**:
   - **Dropdown Periode Fleksibel**: Bebas memilih bulan & tahun (misal input slip Agustus saat di bulan September).
   - **Fitur Captcha Perkalian**: Perlindungan bot dan human verification sebelum slip diterbitkan.
   - **Otomatisasi Hitung**: Auto-load gaji pokok default saat nama karyawan dipilih.

4. **Kalkulasi & Slip Gaji**:
   - Total Penghasilan = Gaji Pokok + Lembur
   - Total Potongan = Pinjaman
   - Gaji Bersih = Total Penghasilan - Potongan
   - Format Rupiah dan tanggal bayar rapi.

5. **Ekspor & Integrasi Distribusi**:
   - **Download PDF Resmi Langsung** (menggunakan library **Dompdf** di backend untuk menghasilkan file PDF A4 vektor resmi, bukan screenshot browser).
   - **Pratinjau Dokumen PDF** di tab baru atau unduh langsung file `.pdf`.
   - **Kirim WhatsApp** (otomatis memformat pesan rincian slip dan tautan langsung ke `wa.me`).
   - **Kirim Email** (otomatis membuat template rincian slip melalui `mailto:`).

---

## 📁 Arsitektur & Struktur Berkas Modular

```text
ukk_junior_web_programmer_crud_laragon/
├── index.php                 # Dashboard statistik & ringkasan penggajian
├── auth/                     # Modul Autentikasi Pengguna
│   ├── login.php             # Halaman login masuk sistem
│   ├── register.php          # Registrasi akun admin baru
│   ├── lupa_password.php     # Form lupa & reset kata sandi
│   └── logout.php            # Skrip keluar & destroy sesi
├── karyawan/                 # Modul Master Data Karyawan
│   ├── index.php             # Master data karyawan (CRUD Read & Search)
│   ├── form.php              # Tambah & Edit master data karyawan
│   └── delete.php            # Hapus data karyawan (dengan proteksi auth)
├── penggajian/               # Modul Transaksi Penggajian
│   ├── index.php             # Transaksi riwayat slip gaji bulanan & filter periode
│   ├── form.php              # Input slip gaji bulanan + Captcha perkalian
│   ├── detail.php            # Lembar slip gaji resmi (Download PDF, WhatsApp, Email)
│   ├── cetak_pdf.php         # Generator file PDF resmi (Backend Dompdf)
│   └── delete.php            # Hapus transaksi slip gaji (dengan proteksi auth)
├── api/                      # Endpoint Asinkron (AJAX)
│   └── captcha_refresh.php   # Endpoint generate ulang soal captcha
├── assets/                   # Berkas Statis Frontend
│   ├── css/
│   │   └── style.css         # Tema modern, responsif, dan layout print
│   └── js/
│       └── app.js            # Interaktivitas SweetAlert2, format live, & PDF
├── config/
│   ├── database.php          # Koneksi database PDO
│   └── functions.php         # Helper modular, baseUrl(), format rupiah, dll.
├── database/
│   └── ukk_payroll.sql       # Skema tabel database (users, karyawan, penggajian)
├── includes/
│   ├── header.php            # Layout partial header & sidebar navigasi (DRY)
│   └── footer.php            # Layout partial footer & skrip umum (DRY)
└── docs/                     # Dokumentasi acuan UKK
```

---

## 💡 Panduan Menjawab Pertanyaan Penguji UKK

1. **T: Kenapa memisahkan tabel Karyawan dan Penggajian?**  
   *J:* "Karena hubungan datanya adalah 1 to Many (Satu karyawan menerima banyak slip gaji setiap bulannya). Jika disatukan dalam 1 tabel, data profil karyawan akan berulang (redundant) setiap bulan, melanggar kaidah normalisasi database."

2. **T: Bagaimana mencegah slip ganda di bulan yang sama?**  
   *J:* "Di tabel database kami pasang `UNIQUE KEY (karyawan_id, periode)`. Di sisi PHP juga dicek sebelum query INSERT dijalankan, sehingga tidak mungkin 1 karyawan mendapatkan 2 slip di periode bulan yang sama."

3. **T: Bagaimana keamanan password disimpan di database?**  
   *J:* "Password di-hash menggunakan fungsi bawaan PHP `password_hash($password, PASSWORD_BCRYPT)` yang aman, otomatis memakai salt, dan diverifikasi dengan `password_verify()`. Password teks asli tidak pernah disimpan di database."

4. **T: Bagaimana mencegah SQL Injection dan XSS?**  
   *J:* "SQL Injection dicegah dengan **PDO Prepared Statements** (parameter binding `:param`). Sedangkan XSS dicegah dengan fungsi `htmlspecialchars()` melalui fungsi pembantu `e()` di setiap output HTML."

