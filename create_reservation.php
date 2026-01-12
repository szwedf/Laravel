<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

$body = read_json_body();
$req = require_params($body, ['room_id','checkin','checkout','guests','guest_name']);

$room_id = (int)$req['room_id'];
$checkin = (string)$req['checkin'];
$checkout = (string)$req['checkout'];
$guests = (int)$req['guests'];
$guest_name = trim((string)$req['guest_name']);

$guest_email = isset($body['guest_email']) ? trim((string)$body['guest_email']) : null;
$note = isset($body['note']) ? trim((string)$body['note']) : null;

if ($room_id <= 0) json_response(['ok'=>false,'error'=>'Invalid room_id'], 400);
if (!is_valid_date($checkin) || !is_valid_date($checkout)) json_response(['ok'=>false,'error'=>'Invalid date format'], 400);
if ($checkin >= $checkout) json_response(['ok'=>false,'error'=>'checkin must be earlier than checkout'], 400);
if ($guests <= 0 || $guests > 10) json_response(['ok'=>false,'error'=>'Invalid guests'], 400);
if ($guest_name === '') json_response(['ok'=>false,'error'=>'guest_name is required'], 400);

try {
  $pdo->beginTransaction();

  $stmt = $pdo->prepare("SELECT id, capacity, base_price FROM rooms WHERE id = :id FOR UPDATE");
  $stmt->execute([':id' => $room_id]);
  $room = $stmt->fetch();
  if (!$room) { $pdo->rollBack(); json_response(['ok'=>false,'error'=>'Room not found'], 404); }
  if ((int)$room['capacity'] < $guests) { $pdo->rollBack(); json_response(['ok'=>false,'error'=>'Guests exceed room capacity'], 400); }

  $stmt = $pdo->prepare("
    SELECT 1
    FROM reservations s
    WHERE s.room_id = :room_id
      AND s.status = 'confirmed'
      AND NOT (s.checkout <= :checkin OR s.checkin >= :checkout)
    LIMIT 1
  ");
  $stmt->execute([':room_id'=>$room_id, ':checkin'=>$checkin, ':checkout'=>$checkout]);
  if ($stmt->fetch()) { $pdo->rollBack(); json_response(['ok'=>false,'error'=>'Room is already booked for that period'], 409); }

  $nights = nights_between($checkin, $checkout);
  $total_price = (int)$room['base_price'] * $nights;

  $stmt = $pdo->prepare("
    INSERT INTO reservations (room_id, checkin, checkout, guests, guest_name, guest_email, total_price, status, note)
    VALUES (:room_id, :checkin, :checkout, :guests, :guest_name, :guest_email, :total_price, 'confirmed', :note)
  ");
  $stmt->execute([
    ':room_id'=>$room_id, ':checkin'=>$checkin, ':checkout'=>$checkout,
    ':guests'=>$guests, ':guest_name'=>$guest_name,
    ':guest_email'=>($guest_email !== '' ? $guest_email : null),
    ':total_price'=>$total_price, ':note'=>($note !== '' ? $note : null),
  ]);

  $pdo->commit();
  json_response(['ok'=>true,'reservation_id'=>(int)$pdo->lastInsertId(),'total_price'=>$total_price,'nights'=>$nights]);
} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  json_response(['ok'=>false,'error'=>'Failed to create reservation','detail'=>$e->getMessage()], 500);
}
