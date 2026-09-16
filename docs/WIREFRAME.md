# Wireframe & Layout GajiHub (Sidebar Layout with Toggle)

Dokumentasi rancangan tata letak antarmuka (UI) dengan navigasi **Sidebar** modern, bersih, dan **dapat dibuka/tutup (*collapsible*)**.

---

## 1. Tata Letak Global (Sidebar Layout & Toggle)

```text
+-------------------+-------------------------------------------------------------+
| [Rp] GajiHub  [=] | [=] GajiHub                         [User Profile: Admin]   | <- Topbar Konten
|-------------------|-------------------------------------------------------------|
| MENU UTAMA        |  KONTEN UTAMA (Page Content Area)                           |
| [o] Dashboard     |                                                             |
| [o] Data Karyawan |  * Sidebar dapat diperkecil/ditutup menggunakan tombol [=]  |
| [o] Transaksi     |  * Di layar desktop, sidebar bergeser keluar                |
|     Penggajian    |  * Di layar mobile, sidebar tampil sebagai drawer           |
|                   |                                                             |
|-------------------|                                                             |
| [User Shield]     |                                                             |
| Nama User         |                                                             |
| Administrator HRD |                                                             |
|                   |                                                             |
| [->] Keluar       |                                                             |
+-------------------+-------------------------------------------------------------+
```

---

## 2. Wireframe Dashboard (`index.php`)

```text
+-------------------+-------------------------------------------------------------+
| GajiHub       [=] | [=]                                   [Administrator HRD]   |
|-------------------|-------------------------------------------------------------|
| [•] Dashboard     | DASHBOARD PENGGAJIAN                                        |
| [ ] Data Karyawan | Selamat Datang di Sistem Informasi Penggajian GajiHub       |
| [ ] Transaksi Gaji|-------------------------------------------------------------|
|                   | [ Total Karyawan ]    [ Total Gaji Bulan Ini ]   [ Total Slip ] |
|-------------------|      12 Orang              Rp 48.500.000             15 Terbit  |
| Admin User        |-------------------------------------------------------------|
| Administrator HRD | Transaksi Penggajian Terakhir                               |
| [Keluar]          | +----+----------------+------------+--------------+------------+ |
|                   | | No | Nama Karyawan  | Periode    | Gaji Bersih  | Aksi       | |
|                   | +----+----------------+------------+--------------+------------+ |
|                   | | 1  | Budi Santoso   | 2026-03    | Rp 4.500.000 | [PDF][Edit]| |
|                   | | 2  | Siti Rahayu    | 2026-03    | Rp 5.200.000 | [PDF][Edit]| |
|                   | +----+----------------+------------+--------------+------------+ |
+-------------------+-------------------------------------------------------------+
```

---

## 3. Wireframe Data Karyawan (`karyawan.php`)

```text
+-------------------+-------------------------------------------------------------+
| GajiHub       [=] | [=]                                   [Administrator HRD]   |
|-------------------|-------------------------------------------------------------|
| [ ] Dashboard     | MASTER DATA KARYAWAN                      [+ Tambah Karyawan]|
| [•] Data Karyawan |-------------------------------------------------------------|
| [ ] Transaksi Gaji| [ Cari nama / jabatan...                 ] [Cari] [Reset]   |
|                   |-------------------------------------------------------------|
|-------------------| +----+----------+---------------+-------------+-----------+ |
| Admin User        | | No | NIK      | Nama Karyawan | Jabatan     | Aksi      | |
| Administrator HRD | +----+----------+---------------+-------------+-----------+ |
| [Keluar]          | | 1  | KRY-001  | Ahmad Subagyo | Programmer  | [E] [D]   | |
|                   | | 2  | KRY-002  | Dina Lestari  | UI/UX Des.  | [E] [D]   | |
|                   | +----+----------+---------------+-------------+-----------+ |
+-------------------+-------------------------------------------------------------+
```

---

## 4. Wireframe Transaksi Penggajian (`penggajian.php`)

```text
+-------------------+-------------------------------------------------------------+
| GajiHub       [=] | [=]                                   [Administrator HRD]   |
|-------------------|-------------------------------------------------------------|
| [ ] Dashboard     | TRANSAKSI & RIWAYAT PENGGAJIAN                              |
| [ ] Data Karyawan | Filter: [ Periode Bulan v ] [ Cari Karyawan... ] [Terapkan] |
| [•] Transaksi Gaji|-------------------------------------------------------------|
|                   | [ Periode Aktif ]     [ Total Slip Terbit ]   [ Total Gaji] |
|-------------------|   Maret 2026                 15 Slip          Rp 48.500.000 |
| Admin User        |-------------------------------------------------------------|
| Administrator HRD | Daftar Slip Gaji Terbit                  [+ Input Slip Gaji]|
| [Keluar]          | +----+---------+---------------+--------------+-----------+ |
|                   | | No | Periode | Nama Karyawan | Gaji Bersih  | Aksi      | |
|                   | +----+---------+---------------+--------------+-----------+ |
|                   | | 1  | 2026-03 | Ahmad Subagyo | Rp 4.800.000 | [PDF][WA] | |
|                   | | 2  | 2026-03 | Dina Lestari  | Rp 5.200.000 | [PDF][WA] | |
|                   | +----+---------+---------------+--------------+-----------+ |
+-------------------+-------------------------------------------------------------+
```

---

## 5. Wireframe Form Input Slip Gaji (`penggajian_form.php`)

```text
+-------------------+-------------------------------------------------------------+
| GajiHub       [=] | [=]                                   [Administrator HRD]   |
|-------------------|-------------------------------------------------------------|
| [ ] Dashboard     | FORM HITUNG GAJI                                            |
| [ ] Data Karyawan | Pilih Karyawan  : [ Dropdown Karyawan             v ]       |
| [•] Transaksi Gaji| Periode (Bulan) : [ YYYY-MM                       ]       |
|     (Aktif)       | Gaji Pokok (Rp) : [ 4.500.000 (Otomatis terisi)   ]       |
|-------------------| Uang Lembur     : [ 500.000                       ]       |
| Admin User        | Potongan Pinjam : [ 200.000                       ]       |
| Administrator HRD |-------------------------------------------------------------|
| [Keluar]          | Pratinjau Gaji Bersih : Rp 4.800.000 (Real-time hitung)     |
|                   |-------------------------------------------------------------|
|                   | Verifikasi Keamanan (CAPTCHA):                              |
|                   | [ Berapa 4 x 3 ? ]   Jawaban: [ 12 ]   [Ganti Soal]         |
|                   |-------------------------------------------------------------|
|                   |                                     [Batal]  [Simpan Gaji]  |
+-------------------+-------------------------------------------------------------+
```

---

## 6. Wireframe Detail Slip Gaji & Aksi Cetak (`detail.php`)

```text
+-------------------+-------------------------------------------------------------+
| GajiHub       [=] | [=]                                   [Administrator HRD]   |
|-------------------|-------------------------------------------------------------|
| [ ] Dashboard     | SLIP GAJI KARYAWAN                                          |
| [ ] Data Karyawan | Aksi: [Download PDF]  [Kirim WA]  [Kirim Email]  [Print]    |
| [•] Transaksi Gaji|-------------------------------------------------------------|
|                   | +---------------------------------------------------------+ |
|-------------------| |                       GAJIHUB                           | |
| Admin User        | |            SLIP GAJI KARYAWAN PERIODE 2026-03           | |
| Administrator HRD | | NIK: KRY-001          Nama: Ahmad Subagyo               | |
| [Keluar]          | | Jabatan: Programmer   Tanggal: 2026-03-15               | |
|                   | |---------------------------------------------------------| |
|                   | | Gaji Pokok                               Rp 4.500.000   | |
|                   | | Uang Lembur                             +Rp   500.000   | |
|                   | | Potongan Pinjaman                       -Rp   200.000   | |
|                   | |---------------------------------------------------------| |
|                   | | TOTAL GAJI BERSIH (TAKE HOME PAY)       =Rp 4.800.000   | |
|                   | +---------------------------------------------------------+ |
+-------------------+-------------------------------------------------------------+
```
