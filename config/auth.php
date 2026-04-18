<?php
require_once __DIR__.'/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function login($email, $password) {
    global $pdo;
    $st = $pdo->prepare('SELECT * FROM users WHERE email = ?');
    $st->execute([$email]);
    $u = $st->fetch();

    // password_verify を使わず、そのまま比較する（緊急用）
    if ($u && $password === $u['password_hash']) {
        $_SESSION['uid'] = $u['id'];
        $_SESSION['role'] = $u['role'];
        return true;
    }
    return false;
}

function require_login() {
    if (empty($_SESSION['uid'])) {
        header('Location: /hotel-admin/public/login.php');
        exit;
    }
}