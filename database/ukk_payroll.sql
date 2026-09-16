CREATE DATABASE IF NOT EXISTS ukk_payroll
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE ukk_payroll;

-- Hapus tabel lama jika ada
DROP TABLE IF EXISTS penggajian;
DROP TABLE IF EXISTS karyawan;
DROP TABLE IF EXISTS users;

-- 1. TABEL PENGGUNA / USERS (AUTENTIKASI)
CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'admin',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_email (email)
) ENGINE=InnoDB;

-- 2. TABEL MASTER KARYAWAN
CREATE TABLE IF NOT EXISTS karyawan (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nik VARCHAR(20) NULL,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(100) NULL,
    no_hp VARCHAR(20) NULL,
    jabatan VARCHAR(50) NOT NULL,
    gaji_pokok_default DECIMAL(15,2) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE INDEX idx_nik (nik),
    INDEX idx_nama (nama),
    INDEX idx_jabatan (jabatan)
) ENGINE=InnoDB;

-- 3. TABEL TRANSAKSI PENGGAJIAN BULANAN
CREATE TABLE IF NOT EXISTS penggajian (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    karyawan_id INT UNSIGNED NOT NULL,
    periode VARCHAR(7) NOT NULL, -- Format YYYY-MM, contoh: 2026-09
    gaji_pokok DECIMAL(15,2) NOT NULL DEFAULT 0,
    lembur DECIMAL(15,2) NOT NULL DEFAULT 0,
    pinjaman DECIMAL(15,2) NOT NULL DEFAULT 0,
    tanggal_bayar DATE NOT NULL,
    catatan VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_penggajian_karyawan 
        FOREIGN KEY (karyawan_id) REFERENCES karyawan(id) 
        ON DELETE CASCADE ON UPDATE CASCADE,
    UNIQUE KEY uq_karyawan_periode (karyawan_id, periode),
    INDEX idx_periode (periode)
) ENGINE=InnoDB;

-- 4. SEED DATA AKUN ADMIN DEFAULT (Password: admin123)
INSERT INTO users (nama, email, password, role) VALUES
('Administrator HRD', 'admin@payroll.com', '$2y$10$y6rklDgIoEPaU3TIXXzQEOg8MbY6T36Ljx19dTDfDqOKd95LxX5Vu', 'admin');

-- 5. SEED MASTER DATA KARYAWAN
INSERT INTO karyawan (id, nik, nama, email, no_hp, jabatan, gaji_pokok_default) VALUES
(1, 'NIK-001', 'Ahmad Fauzan', 'ahmad.fauzan@example.com', '6281234567890', 'Programmer', 5000000),
(2, 'NIK-002', 'Siti Rahma', 'siti.rahma@example.com', '6289876543210', 'Admin', 4000000),
(3, 'NIK-003', 'Budi Santoso', 'budi.santoso@example.com', '6285678901234', 'Supervisor', 6500000);

-- 6. SEED TRANSAKSI PENGGAJIAN BULANAN (Agustus & September 2026)
INSERT INTO penggajian (karyawan_id, periode, gaji_pokok, lembur, pinjaman, tanggal_bayar, catatan) VALUES
-- Periode Agustus 2026 (2026-08)
(1, '2026-08', 5000000, 350000, 100000, '2026-08-28', 'Gaji Bulan Agustus 2026'),
(2, '2026-08', 4000000, 200000, 50000, '2026-08-28', 'Gaji Bulan Agustus 2026'),
(3, '2026-08', 6500000, 600000, 300000, '2026-08-28', 'Gaji Bulan Agustus 2026'),

-- Periode September 2026 (2026-09)
(1, '2026-09', 5000000, 500000, 250000, '2026-09-28', 'Gaji Bulan September 2026'),
(2, '2026-09', 4000000, 300000, 100000, '2026-09-28', 'Gaji Bulan September 2026'),
(3, '2026-09', 6500000, 750000, 500000, '2026-09-28', 'Gaji Bulan September 2026');
