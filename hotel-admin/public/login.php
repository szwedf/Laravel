<?php
require_once __DIR__.'/../config/auth.php';
require_once __DIR__.'/../config/csrf.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!csrf_check($_POST['csrf'] ?? '')) $error = 'CSRF token error';
  else if (login($_POST['email'] ?? '', $_POST['password'] ?? '')) {
    header('Location: /hotel-admin/public/index.php'); exit;
  } else $error = 'メールまたはパスワードが違います';
}
?>
<!doctype html><meta charset="utf-8">
<title>Login</title>
<form method="post" style="max-width:360px;margin:5rem auto;font:14px/1.6 system-ui">
  <h1>Hotel Admin</h1>
  <?php if($error): ?><p style="color:#d33"><?=htmlspecialchars($error)?></p><?php endif; ?>
  <label>メール<input type="email" name="email" required></label><br>
  <label>パスワード<input type="password" name="password" required></label><br>
  <input type="hidden" name="csrf" value="<?=csrf_token()?>">
  <button>ログイン</button>
</form>
