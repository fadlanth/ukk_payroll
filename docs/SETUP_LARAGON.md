# Setup Laragon + MariaDB

## 1. Letakkan proyek
Ekstrak folder ini ke:
`C:\laragon\www\ukk_junior_web_programmer_crud_laragon`

## 2. Jalankan Laragon
Start All. Pastikan Apache/Nginx dan MySQL/MariaDB aktif.

## 3. Buat database
Cara paling mudah:
- Buka HeidiSQL dari Laragon, atau
- Buka phpMyAdmin jika tersedia.
- Jalankan isi file `database/ukk_payroll.sql`.

SQL tersebut membuat:
- database `ukk_payroll`
- tabel `karyawan`
- 3 data contoh

## 4. Cek konfigurasi PHP
Default proyek menggunakan:
- host: `127.0.0.1`
- database: `ukk_payroll`
- user: `root`
- password: kosong

Jika konfigurasi Laragon kamu berbeda, ubah `config/database.php`.

## 5. Buka aplikasi
`http://localhost/ukk_junior_web_programmer_crud_laragon/`

Bila virtual host otomatis Laragon aktif:
`http://ukk_junior_web_programmer_crud_laragon.test/`

## 6. Uji CRUD
- CREATE: Tambah Karyawan
- READ: Data Karyawan / Dashboard
- UPDATE: Edit
- DELETE: Hapus
- Detail: lihat slip dan perhitungan gaji

## 7. Uji perhitungan
Contoh:
Gaji Pokok = 5.000.000
Lembur = 500.000
Pinjaman = 250.000

Maka:
Total Penghasilan = 5.500.000
Total Potongan = 250.000
Gaji Bersih = 5.250.000
