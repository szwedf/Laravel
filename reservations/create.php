<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../config/reservation_service.php';

$checkin = $_POST['checkin'] ?? '';
$checkout = $_POST['checkout'] ?? '';
$rooms = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $checkin && $checkout) {
  $sql = "
    SELECT r.*
    FROM rooms r
    ORDER BY r.room_no
  ";

  $allRooms = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

  foreach ($allRooms as $room) {
    ensure_inventory_rows($pdo, (int)$room['id'], $checkin, $checkout);

    $stmt = $pdo->prepare("
      SELECT MIN(total_stock - reserved_count)
      FROM room_inventory
      WHERE room_id = ?
        AND stay_date >= ?
        AND stay_date < ?
    ");

    $stmt->execute([(int)$room['id'], $checkin, $checkout]);
    $available = (int)$stmt->fetchColumn();

    if ($available > 0) {
      $room['available'] = $available;
      $rooms[] = $room;
    }
  }
}
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <title>新規予約 | HOTEL AURELIA TOKYO</title>
  <link rel="stylesheet" href="/hotel-admin/public/admin.css">
</head>
<body>
<main class="admin-shell">
  <header class="admin-header">
    <div>
      <h1 class="admin-title">新規予約</h1>
      <p style="color:#6b7280;margin:6px 0 0;">日程から空室を検索して登録します。</p>
    </div>
    <nav class="admin-nav">
      <a href="/hotel-admin/reservations/index.php">予約一覧へ</a>
      <a href="/hotel-admin/public/index.php">ダッシュボード</a>
    </nav>
  </header>

  <section class="panel" style="margin-bottom:18px;">
    <form method="post" class="form-inline">
      <label>チェックイン
        <input type="date" name="checkin" value="<?= h($checkin) ?>" required>
      </label>
      <label>チェックアウト
        <input type="date" name="checkout" value="<?= h($checkout) ?>" required>
      </label>
      <button class="btn gold">空室検索</button>
    </form>
  </section>

  <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
    <section class="panel">
      <h2 style="margin-top:0;">空室結果</h2>

      <?php if ($rooms): ?>
        <form method="post" action="update.php" style="display:grid;gap:14px;max-width:760px;">
          <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
          <input type="hidden" name="action" value="create">
          <input type="hidden" name="checkin" value="<?= h($checkin) ?>">
          <input type="hidden" name="checkout" value="<?= h($checkout) ?>">

          <label>宿泊者名
            <input name="guest_name" required>
          </label>

          <label>メールアドレス
            <input name="guest_email" type="email">
          </label>

          <label>人数
            <input name="guests" type="number" min="1" max="6" value="2" required>
          </label>

          <label>部屋
            <select name="room_id" required>
              <?php foreach ($rooms as $r): ?>
                <option value="<?= h($r['id']) ?>">
                  <?= h($r['room_no']) ?> / <?= h($r['type_code']) ?> /
                  ¥<?= number_format((int)$r['base_price']) ?> / 1名1泊 /
                  残 <?= h($r['available']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </label>

          <button class="btn gold">予約登録</button>
        </form>
      <?php else: ?>
        <p>指定日程で空室がありません。</p>
      <?php endif; ?>
    </section>
  <?php endif; ?>
</main>
</body>
</html>