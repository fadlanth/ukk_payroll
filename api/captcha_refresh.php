<?php
declare(strict_types=1);

session_start();
header('Content-Type: application/json; charset=utf-8');

// Memeriksa bahwa request berasal dari pengguna terautentikasi
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Acak ulang dua bilangan 2 sampai 9
$c1 = rand(2, 9);
$c2 = rand(2, 9);

$_SESSION['captcha_c1']       = $c1;
$_SESSION['captcha_c2']       = $c2;
$_SESSION['captcha_slip_ans'] = $c1 * $c2;
$_SESSION['captcha_slip_q']   = "{$c1} × {$c2}";

echo json_encode([
    'success'  => true,
    'c1'       => $c1,
    'c2'       => $c2,
    'question' => "{$c1} × {$c2}",
]);
