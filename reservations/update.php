<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
require_login();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../config/reservation_service.php';

$redirect = '/hotel-admin/reservations/index.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header("Location: {$redirect}");
  exit;
}

if (!csrf_check($_POST['csrf'] ?? '')) {
  header("Location: {$redirect}?error=csrf");
  exit;
}

$action = $_POST['action'] ?? '';

try {
  if ($action === 'create') {
    $roomId = (int)($_POST['room_id'] ?? 0);
    $guestName = trim((string)($_POST['guest_name'] ?? ''));
    $guestEmail = trim((string)($_POST['guest_email'] ?? ''));
    $guests = max(1, (int)($_POST['guests'] ?? 1));
    $checkin = (string)($_POST['checkin'] ?? '');
    $checkout = (string)($_POST['checkout'] ?? '');

    if ($roomId <= 0 || $guestName === '' || $checkin === '' || $checkout === '') {
      header("Location: /hotel-admin/reservations/create.php?error=invalid_input");
      exit;
    }

    $pdo->beginTransaction();

    $roomStmt = $pdo->prepare("SELECT * FROM rooms WHERE id = ? FOR UPDATE");
    $roomStmt->execute([$roomId]);
    $room = $roomStmt->fetch(PDO::FETCH_ASSOC);

    if (!$room) {
      throw new RuntimeException('部屋が存在しません。');
    }

    reserve_inventory($pdo, $roomId, $checkin, $checkout, 1);

    $nights = nights_between($checkin, $checkout);
    $totalPrice = (int)$room['base_price'] * $guests * $nights;

    $insert = $pdo->prepare("
      INSERT INTO reservations
        (room_id, guest_name, guest_email, guests, checkin, checkout, total_price, status, payment_status)
      VALUES
        (?, ?, ?, ?, ?, ?, ?, 'pending', 'unpaid')
    ");

    $insert->execute([
      $roomId,
      $guestName,
      $guestEmail ?: null,
      $guests,
      $checkin,
      $checkout,
      $totalPrice
    ]);

    $reservationId = (int)$pdo->lastInsertId();

    add_status_history(
      $pdo,
      $reservationId,
      null,
      'pending',
      '管理画面から予約作成'
    );

    $pdo->commit();

    header("Location: {$redirect}?msg=created");
    exit;
  }

  if ($action === 'status') {
    $id = (int)($_POST['id'] ?? 0);
    $newStatus = (string)($_POST['status'] ?? 'pending');

    if (!in_array($newStatus, ['pending', 'confirmed', 'cancelled'], true)) {
      header("Location: {$redirect}?error=invalid_status");
      exit;
    }

    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT * FROM reservations WHERE id = ? FOR UPDATE");
    $stmt->execute([$id]);
    $reservation = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$reservation) {
      throw new RuntimeException('予約が存在しません。');
    }

    $oldStatus = $reservation['status'];

    if ($oldStatus !== $newStatus) {
      $cancelFee = (int)($reservation['cancel_fee'] ?? 0);
      $cancelReason = null;

      if ($newStatus === 'cancelled') {
        $cancelReason = trim((string)($_POST['cancel_reason'] ?? '管理画面からキャンセル'));
        $cancelFee = calculate_cancel_fee((int)$reservation['total_price'], $reservation['checkin']);

        release_inventory(
          $pdo,
          (int)$reservation['room_id'],
          $reservation['checkin'],
          $reservation['checkout'],
          1
        );

        $update = $pdo->prepare("
          UPDATE reservations
          SET status = 'cancelled',
              cancel_reason = ?,
              cancel_fee = ?,
              cancelled_at = NOW()
          WHERE id = ?
        ");

        $update->execute([$cancelReason, $cancelFee, $id]);

      } else {
        $update = $pdo->prepare("UPDATE reservations SET status = ? WHERE id = ?");
        $update->execute([$newStatus, $id]);
      }

      add_status_history(
        $pdo,
        $id,
        $oldStatus,
        $newStatus,
        $cancelReason ?: '管理画面でステータス変更'
      );
    }

    $pdo->commit();

    header("Location: {$redirect}?msg=status_updated");
    exit;
  }

  if ($action === 'payment') {
    $id = (int)($_POST['id'] ?? 0);
    $paymentStatus = (string)($_POST['payment_status'] ?? 'unpaid');

    if (!in_array($paymentStatus, ['unpaid', 'authorized', 'paid', 'failed', 'refunded'], true)) {
      header("Location: {$redirect}?error=invalid_payment");
      exit;
    }

    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT * FROM reservations WHERE id = ? FOR UPDATE");
    $stmt->execute([$id]);
    $reservation = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$reservation) {
      throw new RuntimeException('予約が存在しません。');
    }

    if ($paymentStatus === 'paid') {
      $update = $pdo->prepare("
        UPDATE reservations
        SET payment_status = 'paid',
            status = 'confirmed',
            paid_at = NOW()
        WHERE id = ?
      ");
      $update->execute([$id]);

      add_status_history(
        $pdo,
        $id,
        $reservation['status'],
        'confirmed',
        '決済完了により予約確定'
      );

    } elseif ($paymentStatus === 'failed') {
      release_inventory(
        $pdo,
        (int)$reservation['room_id'],
        $reservation['checkin'],
        $reservation['checkout'],
        1
      );

      $update = $pdo->prepare("
        UPDATE reservations
        SET payment_status = 'failed',
            status = 'cancelled',
            cancel_reason = '決済失敗',
            cancel_fee = 0,
            cancelled_at = NOW()
        WHERE id = ?
      ");
      $update->execute([$id]);

      add_status_history(
        $pdo,
        $id,
        $reservation['status'],
        'cancelled',
        '決済失敗により在庫解放'
      );

    } else {
      $update = $pdo->prepare("UPDATE reservations SET payment_status = ? WHERE id = ?");
      $update->execute([$paymentStatus, $id]);
    }

    $pdo->commit();

    header("Location: {$redirect}?msg=payment_updated");
    exit;
  }

  if ($action === 'checkin') {
    $id = (int)($_POST['id'] ?? 0);

    $pdo->prepare("
      UPDATE reservations
      SET checked_in_at = NOW(),
          status = IF(status = 'pending', 'confirmed', status)
      WHERE id = ?
        AND status != 'cancelled'
    ")->execute([$id]);

    add_status_history($pdo, $id, null, 'checked_in', 'チェックイン処理');

    header("Location: {$redirect}?msg=checked_in");
    exit;
  }

  if ($action === 'checkout') {
    $id = (int)($_POST['id'] ?? 0);

    $pdo->prepare("
      UPDATE reservations
      SET checked_out_at = NOW()
      WHERE id = ?
        AND status != 'cancelled'
    ")->execute([$id]);

    add_status_history($pdo, $id, null, 'checked_out', 'チェックアウト処理');

    header("Location: {$redirect}?msg=checked_out");
    exit;
  }

} catch (Throwable $e) {
  if ($pdo->inTransaction()) {
    $pdo->rollBack();
  }

  header("Location: {$redirect}?error=" . urlencode($e->getMessage()));
  exit;
}

header("Location: {$redirect}");
exit;