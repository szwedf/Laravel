<?php
require_once __DIR__.'/../config/auth.php'; require_login();
require_once __DIR__.'/../config/config.php';
$st = $pdo->prepare('INSERT INTO rooms (room_no,room_type,capacity,price,status) VALUES (?,?,?,?,?)');
$st->execute([
  trim($_POST['room_no'] ?? ''),
  $_POST['room_type'] ?? 'single',
  (int)($_POST['capacity'] ?? 1),
  (float)($_POST['price'] ?? 0),
  $_POST['status'] ?? 'available',
]);
header('Location: /hotel-admin/rooms/index.php');
