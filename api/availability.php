<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: http://localhost:5173'); // 開発中
require __DIR__.'/../config/config.php';

$cin  = $_GET['check_in']  ?? '';
$cout = $_GET['check_out'] ?? '';
$gu   = (int)($_GET['guests'] ?? 1);
$type = $_GET['type'] ?? '';

$sql = "SELECT id, room_no AS roomNo, room_type AS type, capacity, price, description
        FROM rooms r
        WHERE r.status='available' AND capacity >= ?
          ".($type ? "AND room_type = ? " : "")."
          AND NOT EXISTS(
            SELECT 1 FROM reservations v
            WHERE v.room_id=r.id AND v.status IN ('pending','confirmed')
              AND NOT (v.check_out<=? OR v.check_in>=?)
          )
        ORDER BY price ASC";
$st = $pdo->prepare($sql);
$p = [$gu];
if ($type) $p[] = $type;
$p[] = $cin; $p[] = $cout;
$st->execute($p);

echo json_encode(['rooms'=>$st->fetchAll()], JSON_UNESCAPED_UNICODE);
