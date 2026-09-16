<?php
declare(strict_types=1);

/**
 * Template Header Bersama (Clean Code - DRY)
 * 
 * Variabel yang dapat dikirim sebelum require:
 * @var string $pageTitle Judul halaman browser
 * @var string $activePage Halaman yang sedang aktif ('dashboard', 'karyawan', 'penggajian', 'input_slip')
 * @var bool   $noPrintHeader Menambahkan class 'no-print' pada sidebar (khusus cetak/slip)
 */

$pageTitle = $pageTitle ?? 'GajiHub - Sistem Penggajian';
$activePage = $activePage ?? '';
$noPrintHeader = $noPrintHeader ?? false;
$currentUser = currentUser();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>

    <!-- Preconnect & Google Fonts (Plus Jakarta Sans & Inter) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;0,800;1,400&family=Inter:wght@400;500;600;700&display=swap">

    <!-- Library CSS Pihak Ketiga: FontAwesome & SweetAlert2 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.5/dist/sweetalert2.min.css">

    <!-- Stylesheet Utama Aplikasi -->
    <link rel="stylesheet" href="<?= baseUrl('assets/css/style.css') ?>">
</head>
<body class="app-body">

<div class="layout-wrapper">
    <!-- SIDEBAR NAVIGATION -->
    <aside class="sidebar <?= $noPrintHeader ? 'no-print' : '' ?>">
        <div class="sidebar-header">
            <a class="brand" href="<?= baseUrl('index.php') ?>">
                <i class="fa-solid fa-money-bill-wave"></i>
                <span>GajiHub</span>
            </a>
        </div>

        <div class="sidebar-menu-wrapper">
            <div class="sidebar-label">Menu Utama</div>
            <nav class="sidebar-nav">
                <a class="sidebar-link <?= $activePage === 'dashboard' ? 'active' : '' ?>" href="<?= baseUrl('index.php') ?>">
                    <i class="fa-solid fa-chart-pie"></i>
                    <span>Dashboard</span>
                </a>
                <a class="sidebar-link <?= $activePage === 'karyawan' ? 'active' : '' ?>" href="<?= baseUrl('karyawan/index.php') ?>">
                    <i class="fa-solid fa-users"></i>
                    <span>Data Karyawan</span>
                </a>
                <a class="sidebar-link <?= in_array($activePage, ['penggajian', 'input_slip'], true) ? 'active' : '' ?>" href="<?= baseUrl('penggajian/index.php') ?>">
                    <i class="fa-solid fa-file-invoice-dollar"></i>
                    <span>Transaksi Penggajian</span>
                </a>
            </nav>
        </div>

        <?php if ($currentUser): ?>
            <div class="sidebar-footer">
                <div class="user-card-sidebar">
                    <div class="user-avatar-sidebar">
                        <i class="fa-solid fa-user-shield"></i>
                    </div>
                    <div class="user-meta-sidebar">
                        <strong class="user-name-sidebar"><?= e($currentUser['nama']) ?></strong>
                        <span class="user-role-sidebar">Administrator HRD</span>
                    </div>
                </div>

                <a href="<?= baseUrl('auth/logout.php') ?>" class="sidebar-logout-btn" title="Keluar dari sistem" onclick="return confirm('Apakah Anda yakin ingin keluar?')">
                    <i class="fa-solid fa-arrow-right-from-bracket"></i>
                    <span>Keluar</span>
                </a>
            </div>
        <?php endif; ?>
    </aside>

    <!-- MAIN CONTENT AREA -->
    <div class="content-wrapper">
        <!-- Content Topbar with Toggle -->
        <header class="content-topbar no-print">
            <div class="topbar-left">
                <button id="btnSidebarToggle" class="btn-sidebar-toggle" type="button" title="Buka / Tutup Sidebar">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <span class="topbar-brand-indicator">
                    <i class="fa-solid fa-money-bill-wave"></i> GajiHub
                </span>
            </div>
            <?php if ($currentUser): ?>
                <div class="topbar-right">
                    <span class="user-pill-sm">
                        <i class="fa-solid fa-circle-user"></i> <?= e($currentUser['nama']) ?>
                    </span>
                </div>
            <?php endif; ?>
        </header>

        <!-- Backdrop overlay for mobile screen -->
        <div id="sidebarBackdrop" class="sidebar-backdrop no-print"></div>
