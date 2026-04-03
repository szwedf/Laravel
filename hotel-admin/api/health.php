<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/db.php';

try {
  $v = $pdo->query('SELECT VERSION() AS v')->fetch();
  echo json_encode(['ok'=>true,'mysql_version'=>$v['v'] ?? null], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'detail'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
}
