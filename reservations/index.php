<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../config/reservation_service.php';

$status = $_GET['status'] ?? '';

$where = '';
$params = [];

if ($status !== '' && in_array($status, ['pending', 'confirmed', 'cancelled'], true)) {
  $where = 'WHERE v.status = :status';
  $params[':status'] = $status;
}

$sql = "
  SELECT
    v.*,
    r.room_no,
    r.type_code,
    r.base_price
  FROM reservations v
  JOIN rooms r ON r.id = v.room_id
  {$where}
  ORDER BY v.checkin DESC, v.id DESC
";

$st = $pdo->prepare($sql);
$st->execute($params);
$rows = $st->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <title>予約管理 | HOTEL AURELIA TOKYO</title>
  <link rel="stylesheet" href="/hotel-admin/public/admin.css">
</head>
<body>
<main class="admin-shell">
  <header class="admin-header">
    <div>
      <h1 class="admin-title">予約管理</h1>
      <p style="color:#6b7280;margin:6px 0 0;">予約・決済・チェックイン状態を管理します。</p>
    </div>

    <nav class="admin-nav">
      <a href="/hotel-admin/public/index.php">ダッシュボード</a>
      <a href="/hotel-admin/rooms/index.php">部屋管理</a>
      <a class="btn gold" href="/hotel-admin/reservations/create.php">＋ 新規予約</a>
    </nav>
  </header>

  <section class="panel" style="margin-bottom:18px;">
    <form method="get" class="form-inline">
      <label>ステータス</label>
      <select name="status">
        <option value="">すべて</option>
        <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>仮予約</option>
        <option value="confirmed" <?= $status === 'confirmed' ? 'selected' : '' ?>>確定</option>
        <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : '' ?>>キャンセル</option>
      </select>
      <button class="btn">絞り込み</button>
      <a class="btn" href="index.php">リセット</a>
    </form>
  </section>

  <section class="panel">
    <div class="table-wrap">
      <table class="admin-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>部屋</th>
            <th>宿泊者</th>
            <th>期間</th>
            <th>人数</th>
            <th>金額</th>
            <th>予約状態</th>
            <th>決済</th>
            <th>滞在</th>
            <th>操作</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $v): ?>
          <?php
            $nights = nights_between($v['checkin'], $v['checkout']);
            $stayStatus = $v['checked_out_at']
              ? 'チェックアウト済'
              : ($v['checked_in_at'] ? 'チェックイン済' : '未チェックイン');
          ?>
          <tr>
            <td>#<?= h($v['id']) ?></td>
            <td>
              <?= h($v['room_no']) ?><br>
              <small><?= h($v['type_code']) ?></small>
            </td>
            <td>
              <?= h($v['guest_name']) ?><br>
              <small><?= h($v['guest_email'] ?? '') ?></small>
            </td>
            <td>
              <?= h($v['checkin']) ?> → <?= h($v['checkout']) ?><br>
              <small><?= h($nights) ?>泊</small>
            </td>
            <td><?= h($v['guests'] ?? 1) ?>名</td>
            <td>
              ¥<?= number_format((int)$v['total_price']) ?><br>
              <?php if ((int)$v['cancel_fee'] > 0): ?>
                <small>キャンセル料 ¥<?= number_format((int)$v['cancel_fee']) ?></small>
              <?php endif; ?>
            </td>
            <td>
              <span class="badge <?= h($v['status']) ?>"><?= h($v['status']) ?></span>
              <?php if (!empty($v['cancel_reason'])): ?>
                <br><small>理由: <?= h($v['cancel_reason']) ?></small>
              <?php endif; ?>
            </td>
            <td>
              <span class="badge <?= h($v['payment_status']) ?>">
                <?= h($v['payment_status']) ?>
              </span>
            </td>
            <td><?= h($stayStatus) ?></td>
            <td>
              <div style="display:grid;gap:8px;min-width:260px;">
                <form method="post" action="update.php" class="form-inline">
                  <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                  <input type="hidden" name="action" value="status">
                  <input type="hidden" name="id" value="<?= h($v['id']) ?>">
                  <select name="status">
                    <?php foreach (['pending','confirmed','cancelled'] as $s): ?>
                      <option value="<?= h($s) ?>" <?= $v['status'] === $s ? 'selected' : '' ?>>
                        <?= h($s) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                  <input name="cancel_reason" placeholder="キャンセル理由">
                  <button class="btn">予約更新</button>
                </form>

                <form method="post" action="update.php" class="form-inline">
                  <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                  <input type="hidden" name="action" value="payment">
                  <input type="hidden" name="id" value="<?= h($v['id']) ?>">
                  <select name="payment_status">
                    <?php foreach (['unpaid','authorized','paid','failed','refunded'] as $p): ?>
                      <option value="<?= h($p) ?>" <?= $v['payment_status'] === $p ? 'selected' : '' ?>>
                        <?= h($p) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                  <button class="btn">決済更新</button>
                </form>

                <div class="form-inline">
                  <form method="post" action="update.php">
                    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                    <input type="hidden" name="action" value="checkin">
                    <input type="hidden" name="id" value="<?= h($v['id']) ?>">
                    <button class="btn">チェックイン</button>
                  </form>

                  <form method="post" action="update.php">
                    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                    <input type="hidden" name="action" value="checkout">
                    <input type="hidden" name="id" value="<?= h($v['id']) ?>">
                    <button class="btn">チェックアウト</button>
                  </form>

                  <a class="btn" href="history.php?id=<?= h($v['id']) ?>">履歴</a>
                </div>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>

        <?php if (!$rows): ?>
          <tr>
            <td colspan="10">予約データがありません。</td>
          </tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </section>
</main>
</body>
</html>