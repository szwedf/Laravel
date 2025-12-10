<?php
require_once __DIR__.'/../config/auth.php'; require_login();
require_once __DIR__.'/../config/config.php';

$rooms = [];
if ($_SERVER['REQUEST_METHOD']==='POST') {
  $in = $_POST['check_in'] ?? '';
  $out= $_POST['check_out'] ?? '';
  if ($in && $out) {
    $sql = "SELECT * FROM rooms r WHERE r.status='available'
            AND NOT EXISTS (
              SELECT 1 FROM reservations v
              WHERE v.room_id=r.id AND v.status IN ('pending','confirmed')
              AND NOT (v.check_out<=:cin OR v.check_in>=:cout)
            ) ORDER BY r.room_no";
    $st = $pdo->prepare($sql);
    $st->execute([':cin'=>$in, ':cout'=>$out]);
    $rooms = $st->fetchAll();
  }
}
?>
<!doctype html><meta charset="utf-8"><title>予約作成</title>
<h1>予約作成</h1>
<form method="post">
  チェックイン <input type="date" name="check_in" required>
  チェックアウト <input type="date" name="check_out" required>
  <button>空室検索</button>
</form>

<?php if($rooms): ?>
<form method="post" action="update.php">
  <input type="hidden" name="action" value="create">
  <input type="hidden" name="check_in" value="<?=htmlspecialchars($_POST['check_in'])?>">
  <input type="hidden" name="check_out" value="<?=htmlspecialchars($_POST['check_out'])?>">
  氏名 <input name="guest_name" required>
  メール <input name="guest_email" type="email">
  部屋
  <select name="room_id">
    <?php foreach($rooms as $r): ?>
      <option value="<?=$r['id']?>"><?=$r['room_no']?> (<?=$r['room_type']?>) ¥<?=number_format($r['price'])?></option>
    <?php endforeach; ?>
  </select>
  <button>予約確定</button>
</form>
<?php endif; ?>
