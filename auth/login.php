<?php
declare(strict_types=1);

session_start();
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/functions.php';

// Jika sudah login, langsung ke dashboard
if (isLoggedIn()) {
    redirect(baseUrl('index.php'));
}

$email = trim($_POST['email'] ?? '');
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';

    if ($email === '') {
        $errors[] = 'Email / Gmail wajib diisi.';
    }

    if ($password === '') {
        $errors[] = 'Kata sandi wajib diisi.';
    }

    if (!$errors) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            loginUser($user);
            flash('success', 'Selamat datang kembali, ' . $user['nama'] . '!');
            redirect(baseUrl('index.php'));
        } else {
            $errors[] = 'Email atau kata sandi yang Anda masukkan salah.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GajiHub - Masuk ke Sistem (Login)</title>
    <!-- Library CSS FontAwesome & SweetAlert2 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.5/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="<?= baseUrl('assets/css/style.css') ?>">
</head>
<body class="auth-body">

<main class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <div class="auth-logo">
                <i class="fa-solid fa-money-bill-wave"></i>
            </div>
            <h1>MASUK</h1>
            <p>Silakan masukkan email dan kata sandi Anda</p>
        </div>

        <?php if ($msg = flash('success')): ?>
            <div class="alert success"><i class="fa-solid fa-circle-check"></i> <?= e($msg) ?></div>
        <?php endif; ?>

        <?php if ($msg = flash('error')): ?>
            <div class="alert error"><i class="fa-solid fa-triangle-exclamation"></i> <?= e($msg) ?></div>
        <?php endif; ?>

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
                <label for="email"><i class="fa-regular fa-envelope"></i> EMAIL / GMAIL</label>
                <input type="email" id="email" name="email" value="<?= e($email) ?>" 
                       placeholder="nama@gmail.com" required autofocus>
            </div>

            <div class="field">
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <label for="password"><i class="fa-solid fa-lock"></i> KATA SANDI</label>
                    <a href="lupa_password.php" class="auth-link-sm">Lupa Kata Sandi?</a>
                </div>
                <div class="password-input-wrap">
                    <input type="password" id="password" name="password" 
                           placeholder="Masukkan kata sandi..." required>
                    <button type="button" class="btn-toggle-pwd" id="btnTogglePwd" title="Tampilkan/Sembunyikan Sandi">
                        <i class="fa-regular fa-eye" id="eyeIcon"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn primary auth-btn">
                <i class="fa-solid fa-right-to-bracket"></i> MASUK / LOGIN
            </button>
        </form>

        <div class="auth-footer">
            Belum memiliki akun? <a href="register.php" class="auth-link">Daftar Akun Baru</a>
        </div>
    </div>
</main>

<script>
// Toggle Show / Hide Password
document.addEventListener('DOMContentLoaded', () => {
    const btn = document.getElementById('btnTogglePwd');
    const pwdInput = document.getElementById('password');
    const eyeIcon = document.getElementById('eyeIcon');

    if (btn && pwdInput && eyeIcon) {
        btn.addEventListener('click', () => {
            if (pwdInput.type === 'password') {
                pwdInput.type = 'text';
                eyeIcon.classList.remove('fa-eye');
                eyeIcon.classList.add('fa-eye-slash');
            } else {
                pwdInput.type = 'password';
                eyeIcon.classList.remove('fa-eye-slash');
                eyeIcon.classList.add('fa-eye');
            }
        });
    }
});
</script>
</body>
</html>
