<?php
declare(strict_types=1);

session_start();
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/functions.php';

if (isLoggedIn()) {
    redirect(baseUrl('index.php'));
}

$email = trim($_POST['email'] ?? '');
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($email === '') {
        $errors[] = 'Email / Gmail wajib diisi.';
    }

    if (strlen($newPassword) < 6) {
        $errors[] = 'Kata sandi baru minimal 6 karakter.';
    }

    if ($newPassword !== $confirmPassword) {
        $errors[] = 'Konfirmasi kata sandi baru tidak cocok.';
    }

    if (!$errors) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if ($user) {
            $hashed = password_hash($newPassword, PASSWORD_BCRYPT);
            $update = $pdo->prepare("UPDATE users SET password = :pwd WHERE id = :id");
            $update->execute([
                'pwd' => $hashed,
                'id' => $user['id'],
            ]);

            flash('success', 'Kata sandi Anda berhasil diperbarui! Silakan masuk dengan kata sandi baru.');
            redirect('login.php');
        } else {
            $errors[] = 'Akun dengan email tersebut tidak ditemukan di database.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GajiHub - Lupa Kata Sandi</title>
    <!-- Library CSS FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= baseUrl('assets/css/style.css') ?>">
</head>
<body class="auth-body">

<main class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <div class="auth-logo">
                <i class="fa-solid fa-key"></i>
            </div>
            <h1>RESET KATA SANDI</h1>
            <p>Atur ulang kata sandi akun Anda menggunakan email terdaftar</p>
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
                <label for="email"><i class="fa-regular fa-envelope"></i> EMAIL / GMAIL TERDAFTAR</label>
                <input type="email" id="email" name="email" value="<?= e($email) ?>" 
                       placeholder="nama@gmail.com" required autofocus>
            </div>

            <div class="field">
                <label for="new_password"><i class="fa-solid fa-lock"></i> KATA SANDI BARU</label>
                <input type="password" id="new_password" name="new_password" 
                       placeholder="Minimal 6 karakter" required>
            </div>

            <div class="field">
                <label for="confirm_password"><i class="fa-solid fa-lock-open"></i> ULANGI KATA SANDI BARU</label>
                <input type="password" id="confirm_password" name="confirm_password" 
                       placeholder="Ulangi kata sandi baru" required>
            </div>

            <button type="submit" class="btn primary auth-btn">
                <i class="fa-solid fa-rotate"></i> SIMPAN KATA SANDI BARU
            </button>
        </form>

        <div class="auth-footer">
            Ingat kata sandi Anda? <a href="login.php" class="auth-link">Kembali ke Login</a>
        </div>
    </div>
</main>

</body>
</html>
