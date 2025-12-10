<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
require_login();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/csrf.php';

$redirect = '/hotel-admin/rooms/index.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header("Location: {$redirect}");
  exit;
}

if (!csrf_check($_POST['csrf'] ?? '')) {
  header("Location: /hotel-admin/rooms/edit.php?id=".(int)($_POST['id'] ?? 0)."&error=CSRF%20token%20invalid");
  exit;
}

$action = $_POST['action'] ?? '';

if ($action === 'update') {
  $id        = (int)($_POST['id'] ?? 0);
  $room_no   = trim((string)($_POST['room_no'] ?? ''));
  $room_type = (string)($_POST['room_type'] ?? 'single');
  $capacity  = max(1, (int)($_POST['capacity'] ?? 1));
  $price     = (float)($_POST['price'] ?? 0);
  $status    = (string)($_POST['status'] ?? 'available');
  $desc      = trim((string)($_POST['description'] ?? ''));

  // ざっくりバリデーション
  $types  = ['single','double','twin','suite'];
  $states = ['available','maintenance'];
  if ($room_no === '' || !in_array($room_type, $types, true) || !in_array($status, $states, true)) {
    header("Location: /hotel-admin/rooms/edit.php?id={$id}&error=入力値が不正です");
    exit;
  }

  try {
    $sql = 'UPDATE rooms
              SET room_no=?, room_type=?, capacity=?, price=?, status=?, description=?
            WHERE id=?';
    $st = $pdo->prepare($sql);
    $st->execute([$room_no, $room_type, $capacity, $price, $status, $desc ?: null, $id]);
  } catch (PDOException $e) {
    // 重複エラー（ユニーク制約）など
    if ($e->getCode() === '23000') {
      header("Location: /hotel-admin/rooms/edit.php?id={$id}&error=その部屋番号は既に存在します");
      exit;
    }
    header("Location: /hotel-admin/rooms/edit.php?id={$id}&error=更新に失敗しました");
    exit;
  }

  header("Location: {$redirect}?msg=updated");
  exit;
}

if ($action === 'delete') {
  $id = (int)($_POST['id'] ?? 0);

  // 参照チェック：未完了予約がある部屋は削除不可
  try {
    $chk = $pdo->prepare("SELECT COUNT(*) c FROM reservations WHERE room_id=? AND status IN ('pending','confirmed')");
    $chk->execute([$id]);
    $count = (int)$chk->fetch()['c'];
    if ($count > 0) {
      header("Location: /hotel-admin/rooms/edit.php?id={$id}&error=未完了の予約があるため削除できません");
      exit;
    }

    $del = $pdo->prepare('DELETE FROM rooms WHERE id=?');
    $del->execute([$id]);
  } catch (PDOException $e) {
    header("Location: /hotel-admin/rooms/edit.php?id={$id}&error=削除に失敗しました");
    exit;
  }

  header("Location: {$redirect}?msg=deleted");
  exit;
}

// 想定外アクション
header("Location: {$redirect}");
exit;
