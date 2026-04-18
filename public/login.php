<?php
require_once __DIR__.'/../config/auth.php';
// require_login(); は絶対に書かない！
require_once __DIR__.'/../config/config.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    if (login($email, $password)) {
        header('Location: /hotel-admin/public/index.php');
        exit;
    }
    $error = 'メールアドレスまたはパスワードが違います';
}
?>
<!doctype html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <title>Hotel Admin - ログイン</title>
</head>
<body>
    <h1>Hotel Admin</h1>
    <?php if ($error): ?><p style="color:red"><?=htmlspecialchars($error)?></p><?php endif; ?>
    <form method="post">
        <div>メール：<input type="email" name="email" required></div>
        <div>パスワード：<input type="password" name="password" required></div>
        <button type="submit">ログイン</button>
    </form>
</body>
</html>