<?php
// hotel-admin/api/db.php
// MAMPのMySQL設定に合わせて調整してください。
// 典型: host=127.0.0.1, port=8889, user=root, pass=root, db=hotel_db

declare(strict_types=1);

$DB_HOST = '127.0.0.1';
$DB_PORT = '8889';        // ← MAMP標準は8889。3306の場合は変更
$DB_NAME = 'hotel_db';
$DB_USER = 'root';
$DB_PASS = 'root';        // ← 環境によって空文字のこともあります

$dsn = "mysql:host={$DB_HOST};port={$DB_PORT};dbname={$DB_NAME};charset=utf8mb4";

$options = [
  PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
  $pdo = new PDO($dsn, $DB_USER, $DB_PASS, $options);
} catch (PDOException $e) {
  http_response_code(500);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode([
    'ok' => false,
    'message' => 'DB接続に失敗しました。hotel-admin/api/db.php の接続情報を確認してください。',
    'detail' => $e->getMessage(),
  ], JSON_UNESCAPED_UNICODE);
  exit;
}
