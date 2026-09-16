<?php
declare(strict_types=1);

session_start();
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/functions.php';

// Jika sudah login, langsung ke dashboard
if (isLoggedIn()) {
    redirect(baseUrl('index.php'));
}

$nama = trim($_POST['nama'] ?? '');
$email = trim($_POST['email'] ?? '');
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($nama === '') {
        $errors[] = 'Nama lengkap wajib diisi.';
    }

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Format email / gmail tidak valid.';
    }

    if (strlen($password) < 6) {
        $errors[] = 'Kata sandi minimal 6 karakter.';
    }

    if ($password !== $confirmPassword) {
        $errors[] = 'Konfirmasi kata sandi tidak cocok.';
    }

    // Cek apakah email sudah terdaftar
    if (!$errors) {
        $check = $pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
        $check->execute(['email' => $email]);
        if ($check->fetch()) {
            $errors[] = 'Email ini sudah terdaftar. Silakan gunakan email lain atau login.';
        }
    }

    if (!$errors) {
        $hashed = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("INSERT INTO users (nama, email, password, role) VALUES (:nama, :email, :password, 'admin')");
        $stmt->execute([
            'nama' => $nama,
            'email' => $email,
            'password' => $hashed,
        ]);

        flash('success', 'Registrasi berhasil! Silakan masuk dengan akun baru Anda.');
        redirect('login.php');
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GajiHub - Daftar Akun Baru (Register)</title>
    <!-- Library CSS FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= baseUrl('assets/css/style.css') ?>">
</head>
<body class="auth-body">

<main class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <div class="auth-logo">
                <i class="fa-solid fa-user-plus"></i>
            </div>
            <h1>DAFTAR AKUN</h1>
            <p>Buat akun administrator baru untuk mengelola penggajian</p>
        </div>

        <?php if ($errors): ?>
            <div class="alert error">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <div>
                    <?php foreach ($errors as $err): ?>
                        <div><?= e($err) ?></div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <form method="post" class="auth-form" autocomplete="off">
            <div class="field">
                <label for="nama"><i class="fa-regular fa-user"></i> NAMA LENGKAP</label>
                <input type="text" id="nama" name="nama" value="<?= e($nama) ?>" 
                       placeholder="Contoh: Budi Pratama" required autofocus>
            </div>

            <div class="field">
                <label for="email"><i class="fa-regular fa-envelope"></i> EMAIL / GMAIL</label>
                <input type="email" id="email" name="email" value="<?= e($email) ?>" 
                       placeholder="nama@gmail.com" required>
            </div>

            <div class="field">
                <label for="password"><i class="fa-solid fa-lock"></i> KATA SANDI (MIN. 6 KARAKTER)</label>
                <input type="password" id="password" name="password" 
                       placeholder="Minimal 6 karakter" required>
            </div>

            <div class="field">
                <label for="confirm_password"><i class="fa-solid fa-lock-open"></i> KONFIRMASI KATA SANDI</label>
                <input type="password" id="confirm_password" name="confirm_password" 
                       placeholder="Ulangi kata sandi" required>
            </div>

            <button type="submit" class="btn primary auth-btn">
                <i class="fa-solid fa-user-check"></i> DAFTAR SEKARANG
            </button>
        </form>

        <div class="auth-footer">
            Sudah memiliki akun? <a href="login.php" class="auth-link">Masuk di Sini</a>
        </div>
    </div>
</main>

</body>
</html>
