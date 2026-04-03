<?php
// hotel-admin/api/room.php
// GET: type=double

declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

require __DIR__ . '/db.php';

$type = isset($_GET['type']) ? trim((string)$_GET['type']) : '';
if ($type === '') {
  http_response_code(400);
  echo json_encode(['ok'=>false,'message'=>'type は必須です'], JSON_UNESCAPED_UNICODE);
  exit;
}

try {
  $sql = "
    SELECT
      rt.code,
      rt.name_jp,
      rt.max_capacity,
      rt.default_bed,
      MIN(rooms.base_price) AS price_from,
      COALESCE(MAX(rooms.description), '') AS description
    FROM room_types rt
    JOIN rooms ON rooms.type_code = rt.code AND rooms.status = 'active'
    WHERE rt.code = :type
    GROUP BY rt.code, rt.name_jp, rt.max_capacity, rt.default_bed
  ";
  $st = $pdo->prepare($sql);
  $st->execute([':type'=>$type]);
  $item = $st->fetch();

  if (!$item){
    http_response_code(404);
    echo json_encode(['ok'=>false,'message'=>'該当の客室タイプが見つかりません'], JSON_UNESCAPED_UNICODE);
    exit;
  }

  echo json_encode(['ok'=>true, 'item'=>$item], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'message'=>'取得に失敗しました','detail'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
}
