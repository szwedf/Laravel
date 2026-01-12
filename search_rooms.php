<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

$checkin  = $_GET['checkin']  ?? '';
$checkout = $_GET['checkout'] ?? '';
$guests   = $_GET['guests']   ?? '';
$type     = $_GET['type']     ?? 'any';

if (!is_valid_date($checkin) || !is_valid_date($checkout)) {
  json_response(['ok' => false, 'error' => 'Invalid date format. Use YYYY-MM-DD'], 400);
}
if ($checkin >= $checkout) {
  json_response(['ok' => false, 'error' => 'checkin must be earlier than checkout'], 400);
}
$guests_i = (int)$guests;
if ($guests_i <= 0 || $guests_i > 10) {
  json_response(['ok' => false, 'error' => 'Invalid guests'], 400);
}
if ($type === '') $type = 'any';

$sql = "
SELECT
  r.id, r.room_no, r.type_code, rt.name_jp AS type_name,
  r.floor, r.capacity, r.base_price, r.size_sqm, r.bed_type, r.view_label, r.description
FROM rooms r
JOIN room_types rt ON rt.code = r.type_code
WHERE r.capacity >= :guests
  AND (:type = 'any' OR r.type_code = :type)
  AND NOT EXISTS (
    SELECT 1
    FROM reservations s
    WHERE s.room_id = r.id
      AND s.status = 'confirmed'
      AND NOT (s.checkout <= :checkin OR s.checkin >= :checkout)
  )
ORDER BY r.base_price ASC, r.room_no ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([
  ':guests' => $guests_i,
  ':type' => $type,
  ':checkin' => $checkin,
  ':checkout' => $checkout
]);

json_response(['ok' => true, 'count' => $stmt->rowCount(), 'rooms' => $stmt->fetchAll()]);
