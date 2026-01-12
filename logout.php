<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';

// セッションを確実に破棄
logout();

// ログイン画面へ
header('Location: /hotel-admin/public/login.php', true, 302);
exit;
