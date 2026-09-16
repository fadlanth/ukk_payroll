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
    flash('error', 'ID transaksi slip gaji tidak valid.');
    redirect('index.php');
}

$stmt = $pdo->prepare("DELETE FROM penggajian WHERE id = :id");
$stmt->execute(['id' => $id]);

if ($stmt->rowCount() > 0) {
    flash('success', 'Data slip gaji berhasil dihapus.');
} else {
    flash('error', 'Data slip gaji tidak ditemukan atau sudah dihapus.');
}

redirect('index.php');
