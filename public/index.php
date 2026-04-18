<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/config.php';

function h($s) {
  return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

try {
  $roomStats = $pdo->query("
    SELECT
      COUNT(*) AS room_master_count,
      COALESCE(SUM(stock), 0) AS total_stock,
      COUNT(DISTINCT type_code) AS type_count,
      COALESCE(SUM(CASE WHEN stock > 0 THEN stock ELSE 0 END), 0) AS available_stock
    FROM rooms
  ")->fetch(PDO::FETCH_ASSOC);

  $reservationStats = $pdo->query("
    SELECT
      COUNT(*) AS total_reservations,
      SUM(CASE WHEN status IN ('pending','confirmed') THEN 1 ELSE 0 END) AS active_reservations,
      SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) AS confirmed_reservations,
      SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) AS cancelled_reservations,
      COALESCE(SUM(CASE WHEN status = 'confirmed' THEN total_price ELSE 0 END), 0) AS confirmed_sales
    FROM reservations
  ")->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
  $roomStats = [
    'room_master_count' => 0,
    'total_stock' => 0,
    'type_count' => 0,
    'available_stock' => 0
  ];
  $reservationStats = [
    'total_reservations' => 0,
    'active_reservations' => 0,
    'confirmed_reservations' => 0,
    'cancelled_reservations' => 0,
    'confirmed_sales' => 0
  ];
}
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <title>Dashboard | HOTEL AURELIA TOKYO</title>
  <link rel="stylesheet" href="/hotel-admin/public/admin.css">
</head>
<body>
  <main class="admin-shell">
    <header class="admin-header">
      <div>
        <h1 class="admin-title">ダッシュボード</h1>
        <p style="color:#6b7280;margin:6px 0 0;">HOTEL AURELIA TOKYO 管理画面</p>
      </div>

      <nav class="admin-nav">
        <a href="/hotel-admin/rooms/index.php">部屋管理</a>
        <a href="/hotel-admin/reservations/index.php">予約管理</a>
        <a href="/hotel-admin/public/logout.php">ログアウト</a>
      </nav>
    </header>

    <section class="kpi-grid">
      <div class="kpi-card">
        <div class="kpi-label">部屋マスター数</div>
        <div class="kpi-value"><?= h($roomStats['room_master_count']) ?></div>
      </div>

      <div class="kpi-card">
        <div class="kpi-label">販売可能在庫数</div>
        <div class="kpi-value"><?= h($roomStats['available_stock']) ?></div>
      </div>

      <div class="kpi-card">
        <div class="kpi-label">有効予約数</div>
        <div class="kpi-value"><?= h($reservationStats['active_reservations']) ?></div>
      </div>

      <div class="kpi-card">
        <div class="kpi-label">確定売上</div>
        <div class="kpi-value">¥<?= number_format((int)$reservationStats['confirmed_sales']) ?></div>
      </div>
    </section>

    <section class="panel">
      <h2 style="margin-top:0;">管理メニュー</h2>
      <div class="admin-nav">
        <a class="btn gold" href="/hotel-admin/reservations/index.php">予約一覧を見る</a>
        <a class="btn" href="/hotel-admin/reservations/create.php">新規予約を作成</a>
        <a class="btn" href="/hotel-admin/rooms/index.php">部屋マスターを管理</a>
      </div>
    </section>
  </main>
</body>
</html>