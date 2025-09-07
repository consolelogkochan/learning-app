<?php
session_start();
require_once 'db_connect.php';
require_once 'utils.php'; // ★エラー処理関数を読み込む

// 権限チェックとリクエストメソッドの検証
if (!isset($_SESSION['user_id'])) {
    show_error_and_exit('コメントを投稿するにはログインが必要です。');
}
// POSTリクエストかチェック
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    show_error_and_exit('不正なリクエストです。');
}

// フォームからのデータを取得
$user_id = $_SESSION['user_id'];
$log_id = $_POST['log_id'] ?? null;
$content = preg_replace('/^\s+|\s+$/u', '', $_POST['content'] ?? '');

// バリデーション
if (empty($log_id) || empty($content)) {
    show_error_and_exit('コメント内容が入力されていません。');
}

// データベース処理
try {
    // commentsテーブルにデータを挿入
    $sql = "INSERT INTO comments (user_id, log_id, content) VALUES (:user_id, :log_id, :content)";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindValue(':log_id', $log_id, PDO::PARAM_INT);
    $stmt->bindValue(':content', $content, PDO::PARAM_STR);
    $stmt->execute();

    // 元のダッシュボードページにリダイレクト
    header('Location: dashboard.php');
    exit();

} catch (PDOException $e) {
    show_error_and_exit('コメントの投稿に失敗しました。時間をおいて再度お試しください。', $e->getMessage());
}