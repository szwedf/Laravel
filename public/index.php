<?php
require_once __DIR__.'/../config/auth.php';
require_login();
require_once __DIR__.'/../config/config.php';
$rooms = $pdo->query('SELECT COUNT(*) c FROM rooms')->fetch()['c'] ?? 0;
$resv  = $pdo->query("SELECT COUNT(*) c FROM reservations WHERE status IN ('pending','confirmed')")->fetch()['c'] ?? 0;
?>
<!doctype html><meta charset="utf-8">
<title>Dashboard</title>
<h1>ダッシュボード</h1>
<ul>
  <li>部屋数：<?=$rooms?></li>
  <li>有効予約数：<?=$resv?></li>
</ul>
<nav>
  <a href="/hotel-admin/rooms/index.php">部屋管理</a> /
  <a href="/hotel-admin/reservations/index.php">予約管理</a> /
  <a href="/hotel-admin/public/logout.php">ログアウト</a>
</nav>
