<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
require_login();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/csrf.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$st = $pdo->prepare('SELECT * FROM rooms WHERE id = ?');
$st->execute([$id]);
$room = $st->fetch();
if (!$room) {
  http_response_code(404);
  echo 'Room not found';
  exit;
}

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
$types  = ['single','double','twin','suite'];
$states = ['available','maintenance'];
?>
<!doctype html><meta charset="utf-8">
<title>部屋編集</title>
<h1>部屋編集</h1>

<p><a href="/hotel-admin/rooms/index.php">← 一覧へ戻る</a></p>

<?php if (!empty($_GET['error'])): ?>
  <p style="color:#d33"><?= h($_GET['error']) ?></p>
<?php endif; ?>

<form action="update.php" method="post" style="max-width:760px;display:grid;gap:12px">
  <input type="hidden" name="action" value="update">
  <input type="hidden" name="id" value="<?= (int)$room['id'] ?>">
  <input type="hidden" name="csrf" value="<?= csrf_token() ?>">

  <label>部屋番号
    <input name="room_no" required value="<?= h($room['room_no']) ?>">
  </label>

  <label>タイプ
    <select name="room_type">
      <?php foreach($types as $t): ?>
        <option value="<?= $t ?>" <?= $room['room_type']===$t?'selected':'' ?>><?= strtoupper($t) ?></option>
      <?php endforeach; ?>
    </select>
  </label>

  <label>定員
    <input type="number" name="capacity" min="1" value="<?= (int)$room['capacity'] ?>" required>
  </label>

  <label>料金（1泊）
    <input type="number" name="price" min="0" step="0.01" value="<?= (float)$room['price'] ?>" required>
  </label>

  <label>状態
    <select name="status">
      <?php foreach($states as $s): ?>
        <option value="<?= $s ?>" <?= $room['status']===$s?'selected':'' ?>><?= $s ?></option>
      <?php endforeach; ?>
    </select>
  </label>

  <label>説明
    <textarea name="description" rows="4"><?= h($room['description'] ?? '') ?></textarea>
  </label>

  <div style="display:flex;gap:12px">
    <button>更新</button>
    <a href="/hotel-admin/rooms/index.php">キャンセル</a>
  </div>
</form>

<hr>

<form action="update.php" method="post" onsubmit="return confirm('この部屋を削除します。よろしいですか？');">
  <input type="hidden" name="action" value="delete">
  <input type="hidden" name="id" value="<?= (int)$room['id'] ?>">
  <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
  <button style="color:#fff;background:#b71c1c;border:0;padding:8px 12px;border-radius:6px;cursor:pointer">
    削除
  </button>
</form>
