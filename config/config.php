<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) session_start();

$DB_NAME = 'hotel_db';

/* ★ここをあなたの環境に合わせる（MAMPのWebStartで確認） */
$host   = 'localhost/127.0.0.1';
$port   = 8889;                 // MAMP既定は8889（変えていればその値）
$user   = 'root';
$pass   = 'root';
$socket = '/Applications/MAMP/tmp/mysql/mysql.sock';

$options = [
  PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  PDO::ATTR_TIMEOUT            => 5,
];

function connectPdo(?string $db = null): PDO {
  global $host, $port, $user, $pass, $socket, $options;

  // 1) TCP
  $dsn = $db
    ? "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4"
    : "mysql:host={$host};port={$port};charset=utf8mb4";
  try { return new PDO($dsn, $user, $pass, $options); } catch (PDOException $e) {}

  // 2) UNIXソケット
  if (is_readable($socket)) {
    $dsn = $db
      ? "mysql:unix_socket={$socket};dbname={$db};charset=utf8mb4"
      : "mysql:unix_socket={$socket};charset=utf8mb4";
    try { return new PDO($dsn, $user, $pass, $options); } catch (PDOException $e) {}
  }

  // 3) どちらもダメならエラー投げ
  throw new PDOException('MySQL connection failed (port/socket mismatch?)');
}

// DBに直接つなぐ → 失敗したら作ってから再接続
try {
  $pdo = connectPdo($DB_NAME);
} catch (PDOException $e) {
  // サーバ接続だけ試みてDBを作成
  $tmp = connectPdo(null);
  $tmp->exec("CREATE DATABASE IF NOT EXISTS `{$DB_NAME}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
  $tmp = null;
  $pdo = connectPdo($DB_NAME);
}
