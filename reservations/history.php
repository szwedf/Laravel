<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/reservation_service.php';

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("
  SELECT h.*
  FROM reservation_status_history h
  WHERE h.reservation_id = ?
  ORDER BY h.created_at DESC, h.id DESC
");
$stmt->execute([$id]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <title>予約履歴 | HOTEL AURELIA TOKYO</title>
  <link rel="stylesheet" href="/hotel-admin/public/admin.css">
</head>
<body>
<main class="admin-shell">
  <header class="admin-header">
    <div>
      <h1 class="admin-title">予約履歴</h1>
      <p style="color:#6b7280;margin:6px 0 0;">予約ID #<?= h($id) ?> のステータス変更履歴</p>
    </div>
    <nav class="admin-nav">
      <a href="/hotel-admin/reservations/index.php">予約一覧へ</a>
    </nav>
  </header>

  <section class="panel">
    <table class="admin-table">
      <thead>
        <tr>
          <th>日時</th>
          <th>変更前</th>
          <th>変更後</th>
          <th>メモ</th>
          <th>変更者</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><?= h($r['created_at']) ?></td>
            <td><?= h($r['from_status'] ?? '-') ?></td>
            <td><?= h($r['to_status']) ?></td>
            <td><?= h($r['note'] ?? '') ?></td>
            <td><?= h($r['changed_by'] ?? '') ?></td>
          </tr>
        <?php endforeach; ?>

        <?php if (!$rows): ?>
          <tr><td colspan="5">履歴がありません。</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </section>
</main>
</body>
</html>