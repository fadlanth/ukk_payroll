<?php
declare(strict_types=1);

session_start();
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/functions.php';

requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    flash('error', 'ID karyawan tidak valid.');
    redirect('index.php');
}

$stmt = $pdo->prepare("DELETE FROM karyawan WHERE id = :id");
$stmt->execute(['id' => $id]);

if ($stmt->rowCount() > 0) {
    flash('success', 'Data karyawan berhasil dihapus.');
} else {
    flash('error', 'Data karyawan tidak ditemukan.');
}

redirect('index.php');
