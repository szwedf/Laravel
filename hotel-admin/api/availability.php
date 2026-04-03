<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

try {
    $checkin  = $_GET['checkin']  ?? '';
    $checkout = $_GET['checkout'] ?? '';
    $guests   = (int)($_GET['guests'] ?? 1);
    $type     = $_GET['type']     ?? '';

    $host = '127.0.0.1';
    $port = '8889'; 
    $db   = 'hotel_db';
    $user = 'root';
    $pass = 'root';

    $dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // 【修正ポイント】
    // エラーの原因となっている r.image_url を削除し、
    // 代わりに空の文字列（''）を image として取得するようにしました。
    $sql = "
        SELECT 
            r.id, 
            r.room_no, 
            r.type_code, 
            r.capacity, 
            r.base_price AS price, 
            '' AS image,
            rt.name_jp AS name
        FROM rooms AS r
        LEFT JOIN room_types AS rt ON r.type_code = rt.code
        WHERE r.capacity >= :guests
          AND (:type_filter = '' OR r.type_code = :type_param)
          AND r.id NOT IN (
              SELECT s.room_id 
              FROM reservations AS s 
              WHERE s.status IN ('pending', 'confirmed')
                AND NOT (s.checkout <= :ci OR s.checkin >= :co)
          )
        ORDER BY r.base_price ASC
    ";

    $st = $pdo->prepare($sql);
    $st->execute([
        ':guests'      => $guests,
        ':type_filter' => $type,
        ':type_param'  => $type,
        ':ci'          => $checkin,
        ':co'          => $checkout
    ]);

    $rooms = $st->fetchAll();
    
    echo json_encode([
        'ok' => true,
        'rooms' => $rooms
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'detail' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}