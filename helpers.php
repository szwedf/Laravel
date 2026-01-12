<?php
function json_response($data, int $status = 200): void {
  http_response_code($status);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($data, JSON_UNESCAPED_UNICODE);
  exit;
}

function read_json_body(): array {
  $raw = file_get_contents('php://input');
  if (!$raw) return [];
  $data = json_decode($raw, true);
  return is_array($data) ? $data : [];
}

function require_params(array $src, array $keys): array {
  $out = [];
  foreach ($keys as $k) {
    if (!isset($src[$k]) || $src[$k] === '') {
      json_response(['ok' => false, 'error' => "Missing parameter: {$k}"], 400);
    }
    $out[$k] = $src[$k];
  }
  return $out;
}

function is_valid_date(string $s): bool {
  return (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $s);
}

function nights_between(string $checkin, string $checkout): int {
  $a = new DateTime($checkin);
  $b = new DateTime($checkout);
  return (int) $a->diff($b)->days;
}
