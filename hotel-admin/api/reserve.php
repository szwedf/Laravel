<?php
// hotel-admin/api/reserve.php
// POST: type, checkin, checkout, guests, guest_name, email, phone

declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

require __DIR__ . '/db.php';

function post(string $k, string $default=''): string {
  return isset($_POST[$k]) ? trim((string)$_POST[$k]) : $default;
}

$type = post('type');
$checkin = post('checkin');
$checkout = post('checkout');
$guests = (int) (post('guests','2') ?: 2);
$guest_name = post('guest_name');
$email = post('email');
$phone = post('phone');

if ($type === '' || $checkin === '' || $checkout === '' || $guest_name === '' || $email === '') {
  http_response_code(400);
  echo json_encode(['ok'=>false,'message'=>'必須項目が不足しています。'], JSON_UNESCAPED_UNICODE);
  exit;
}

try {
  // nights
  $a = new DateTime($checkin);
  $b = new DateTime($checkout);
  $nights = (int)$a->diff($b)->format('%a');
  if ($nights <= 0) $nights = 1;

  // Allocate a room (one that is free for the span)
  $pdo->beginTransaction();

  $allocSql = "
    SELECT rooms.id, rooms.base_price
    FROM rooms
    WHERE rooms.status='active'
      AND rooms.type_code = :type
      AND rooms.capacity >= :guests
      AND NOT EXISTS (
        SELECT 1 FROM reservations r
        WHERE r.room_id = rooms.id
          AND r.status IN ('reserved','checked_in')
          AND NOT (r.checkout <= :checkin OR r.checkin >= :checkout)
      )
    ORDER BY rooms.base_price ASC, rooms.id ASC
    LIMIT 1
    FOR UPDATE
  ";

  $st = $pdo->prepare($allocSql);
  $st->execute([
    ':type'=>$type,
    ':guests'=>$guests,
    ':checkin'=>$checkin,
    ':checkout'=>$checkout
  ]);
  $room = $st->fetch();

  if (!$room){
    $pdo->rollBack();
    echo json_encode(['ok'=>false,'message'=>'申し訳ありません。選択した条件では空室がありません。'], JSON_UNESCAPED_UNICODE);
    exit;
  }

  $total = (int)$room['base_price'] * $nights;

  $ins = $pdo->prepare("
    INSERT INTO reservations (room_id, checkin, checkout, guests, guest_name, email, phone, total_price, status)
    VALUES (:room_id, :checkin, :checkout, :guests, :guest_name, :email, :phone, :total_price, 'reserved')
  ");
  $ins->execute([
    ':room_id'=>$room['id'],
    ':checkin'=>$checkin,
    ':checkout'=>$checkout,
    ':guests'=>$guests,
    ':guest_name'=>$guest_name,
    ':email'=>$email,
    ':phone'=>$phone ?: null,
    ':total_price'=>$total,
  ]);

  $id = (int)$pdo->lastInsertId();
  $pdo->commit();

  $code = 'AUR-' . date('Ymd') . '-' . str_pad((string)$id, 6, '0', STR_PAD_LEFT);

  echo json_encode(['ok'=>true, 'reservation'=>['id'=>$id,'code'=>$code]], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  http_response_code(500);
  echo json_encode(['ok'=>false,'message'=>'予約処理でエラーが発生しました。','detail'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
}
