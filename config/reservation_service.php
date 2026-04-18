<?php
declare(strict_types=1);

function h($s): string {
  return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function nights_between(string $checkin, string $checkout): int {
  $in = strtotime($checkin);
  $out = strtotime($checkout);

  if (!$in || !$out) {
    return 1;
  }

  $diff = (int)(($out - $in) / 86400);
  return max(1, $diff);
}

function stay_dates(string $checkin, string $checkout): array {
  $dates = [];
  $in = new DateTimeImmutable($checkin);
  $out = new DateTimeImmutable($checkout);

  for ($d = $in; $d < $out; $d = $d->modify('+1 day')) {
    $dates[] = $d->format('Y-m-d');
  }

  return $dates;
}

function calculate_cancel_fee(int $totalPrice, string $checkin): int {
  $today = new DateTimeImmutable('today');
  $checkinDate = new DateTimeImmutable($checkin);

  $daysBefore = (int)$today->diff($checkinDate)->format('%r%a');

  if ($daysBefore >= 7) {
    return 0;
  }

  if ($daysBefore >= 3) {
    return (int)floor($totalPrice * 0.3);
  }

  if ($daysBefore >= 1) {
    return (int)floor($totalPrice * 0.5);
  }

  return $totalPrice;
}

function add_status_history(
  PDO $pdo,
  int $reservationId,
  ?string $fromStatus,
  string $toStatus,
  ?string $note = null,
  ?string $changedBy = 'admin'
): void {
  $stmt = $pdo->prepare("
    INSERT INTO reservation_status_history
      (reservation_id, from_status, to_status, note, changed_by)
    VALUES
      (?, ?, ?, ?, ?)
  ");

  $stmt->execute([
    $reservationId,
    $fromStatus,
    $toStatus,
    $note,
    $changedBy
  ]);
}

function ensure_inventory_rows(PDO $pdo, int $roomId, string $checkin, string $checkout): void {
  $roomStmt = $pdo->prepare("SELECT stock FROM rooms WHERE id = ?");
  $roomStmt->execute([$roomId]);
  $stock = (int)$roomStmt->fetchColumn();

  foreach (stay_dates($checkin, $checkout) as $date) {
    $stmt = $pdo->prepare("
      INSERT IGNORE INTO room_inventory
        (room_id, stay_date, total_stock, reserved_count)
      VALUES
        (?, ?, ?, 0)
    ");
    $stmt->execute([$roomId, $date, $stock]);
  }
}

function reserve_inventory(PDO $pdo, int $roomId, string $checkin, string $checkout, int $rooms = 1): void {
  ensure_inventory_rows($pdo, $roomId, $checkin, $checkout);

  $stmt = $pdo->prepare("
    SELECT *
    FROM room_inventory
    WHERE room_id = ?
      AND stay_date >= ?
      AND stay_date < ?
    ORDER BY stay_date
    FOR UPDATE
  ");

  $stmt->execute([$roomId, $checkin, $checkout]);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

  $expectedDays = count(stay_dates($checkin, $checkout));

  if (count($rows) !== $expectedDays) {
    throw new RuntimeException('在庫日付データが不足しています。');
  }

  foreach ($rows as $row) {
    $available = (int)$row['total_stock'] - (int)$row['reserved_count'];

    if ($available < $rooms) {
      throw new RuntimeException('指定日程の在庫が不足しています。');
    }
  }

  $update = $pdo->prepare("
    UPDATE room_inventory
    SET reserved_count = reserved_count + ?,
        updated_at = NOW()
    WHERE room_id = ?
      AND stay_date >= ?
      AND stay_date < ?
  ");

  $update->execute([$rooms, $roomId, $checkin, $checkout]);
}

function release_inventory(PDO $pdo, int $roomId, string $checkin, string $checkout, int $rooms = 1): void {
  ensure_inventory_rows($pdo, $roomId, $checkin, $checkout);

  $stmt = $pdo->prepare("
    UPDATE room_inventory
    SET reserved_count = GREATEST(reserved_count - ?, 0),
        updated_at = NOW()
    WHERE room_id = ?
      AND stay_date >= ?
      AND stay_date < ?
  ");

  $stmt->execute([$rooms, $roomId, $checkin, $checkout]);
}