<?php
declare(strict_types=1);

session_start();
require __DIR__ . '/../config/functions.php';

logoutUser();

// Hancurkan seluruh sesi untuk keamanan penuh
session_destroy();
session_regenerate_id(true);

session_start();
flash('success', 'Anda telah berhasil keluar (logout).');
redirect('login.php');
