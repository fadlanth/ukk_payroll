# Checklist Debugging CRUD

## Database connection
Gejala:
`Koneksi database gagal`

Cek:
- MariaDB/MySQL Laragon aktif.
- Database `ukk_payroll` sudah dibuat.
- Nama database/user/password sesuai `config/database.php`.

## CREATE tidak masuk
Cek:
- Form memakai method POST.
- Field sesuai nama kolom.
- Tidak ada error SQL.
- Lihat database tabel `karyawan`.

## UPDATE tidak bekerja
Cek:
- URL edit memiliki `?id=...`.
- Hidden input `id` dikirim.
- Query `UPDATE` memakai WHERE id.

## DELETE tidak bekerja
Cek:
- Hapus menggunakan POST.
- ID dikirim.
- Query DELETE memiliki `WHERE id = :id`.

## SQL Injection
Jangan lakukan:
```php
$sql = "SELECT * FROM karyawan WHERE nama = '$q'";
```

Gunakan prepared statement seperti proyek ini:
```php
$stmt = $pdo->prepare(
    "SELECT * FROM karyawan WHERE nama LIKE :q OR jabatan LIKE :q"
);
$stmt->execute(['q' => '%' . $q . '%']);
```

## XSS
Jangan langsung echo input user.
Gunakan:
```php
<?= e($row['nama']) ?>
```

## Rumus salah
Harus:
```php
$totalPenghasilan = $gajiPokok + $lembur;
$totalPotongan = $pinjaman;
$gajiBersih = $totalPenghasilan - $totalPotongan;
```

## Checklist sebelum UKK
- [ ] Database berhasil terhubung.
- [ ] CREATE berhasil.
- [ ] READ berhasil.
- [ ] UPDATE berhasil.
- [ ] DELETE berhasil.
- [ ] Search berhasil.
- [ ] Validasi input bekerja.
- [ ] Rumus gaji benar.
- [ ] Tidak ada error PHP.
- [ ] Tampilan responsif.
- [ ] Cetak slip berhasil.
