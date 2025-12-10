<?php
require_once __DIR__.'/../config/auth.php'; require_login();
require_once __DIR__.'/../config/config.php';
$rows = $pdo->query(
 "SELECT v.*, r.room_no FROM reservations v
  JOIN rooms r ON r.id=v.room_id
  ORDER BY v.check_in DESC"
)->fetchAll();
?>
<!doctype html><meta charset="utf-8"><title>予約一覧</title>
<h1>予約一覧</h1>
<a href="create.php">＋ 新規予約</a>
<table border="1" cellspacing="0" cellpadding="6">
  <tr><th>ID</th><th>部屋</th><th>氏名</th><th>期間</th><th>金額</th><th>状態</th><th>操作</th></tr>
  <?php foreach($rows as $v): ?>
  <tr>
    <td><?=$v['id']?></td>
    <td><?=$v['room_no']?></td>
    <td><?=htmlspecialchars($v['guest_name'])?></td>
    <td><?=$v['check_in']?> → <?=$v['check_out']?></td>
    <td>¥<?=number_format($v['total_price'])?></td>
    <td><?=$v['status']?></td>
    <td>
      <form method="post" action="update.php" style="display:inline">
        <input type="hidden" name="action" value="status">
        <input type="hidden" name="id" value="<?=$v['id']?>">
        <select name="status">
          <?php foreach(['pending','confirmed','cancelled'] as $s): ?>
          <option <?=$v['status']===$s?'selected':''?>><?=$s?></option>
          <?php endforeach; ?>
        </select>
        <button>更新</button>
      </form>
    </td>
  </tr>
  <?php endforeach; ?>
</table>
