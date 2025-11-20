<?php
// === SMTP (Hostinger) ===
define('SMTP_HOST', 'smtp.hostinger.com');
define('SMTP_PORT', 465);      // 465 = SSL, 587 = TLS
define('SMTP_SECURE', 'ssl');  // 'ssl' або 'tls'
define('SMTP_USER', 'administration@korporatsiia-mov.com'); // твій ящик на Hostinger
define('SMTP_PASS', '3806629922189Za#');     // пароль від цього ящика

// === APP ===
define('APP_URL', 'https://korporatsiia-mov.com'); // твій домен, без слеша в кінці

// === MySQL (підстав свої дані) ===
$pdo = new PDO(
    'mysql:host=127.0.0.1;dbname=u436680074_school;charset=utf8mb4', // <- ВИПРАВЛЕНО
    'u436680074_school',
    'Vladik_1023',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
