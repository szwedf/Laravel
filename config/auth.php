<?php
require_once __DIR__.'/config.php';

function login($email, $password) {
  global $pdo;
  $st = $pdo->prepare('SELECT * FROM users WHERE email = ?');
  $st->execute([$email]);
  $u = $st->fetch();
  if ($u && password_verify($password, $u['password_hash'])) {
    $_SESSION['uid'] = $u['id'];
    $_SESSION['role'] = $u['role'];
    return true;
  }
  return false;
}
function require_login() {
  if (empty($_SESSION['uid'])) {
    header('Location: /hotel-admin/public/login.php'); exit;
  }
}
function logout() { session_destroy(); }
