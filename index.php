<?php
// セッションを開始して、ログイン状態を確認する
session_start();

// もしログインしていれば (セッションにuser_idがあれば)
if (isset($_SESSION['user_id'])) {
    // ダッシュボードにリダイレクト
    header('Location: dashboard.php');
    exit();
} else {
    // ログインしていなければ、ログインページにリダイレクト
    header('Location: login.php');
    exit();
}