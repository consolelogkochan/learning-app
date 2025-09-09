<?php
session_start();
require_once 'db_connect.php';
require_once 'utils.php';

// ▼▼▼▼▼ エラー処理を全面的に修正 ▼▼▼▼▼

// --- 事前チェック ---
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['flash_message'] = ['type' => 'error', 'message' => '不正なリクエストです。'];
    header('Location: dashboard.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$log_id = $_POST['log_id'] ?? null;
$content = preg_replace('/^\s+|\s+$/u', '', $_POST['content'] ?? '');

// アンカー付きリダイレクト先のURLを準備
$redirect_url = $log_id ? "dashboard.php#log-{$log_id}" : "dashboard.php";

// --- バリデーション ---
if (empty($log_id) || empty($content)) {
    $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'コメント内容が入力されていません。'];
    header("Location: {$redirect_url}");
    exit();
}

// --- データベース処理 ---
try {
    // commentsテーブルにデータを挿入
    $sql = "INSERT INTO comments (user_id, log_id, content) VALUES (:user_id, :log_id, :content)";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindValue(':log_id', $log_id, PDO::PARAM_INT);
    $stmt->bindValue(':content', $content, PDO::PARAM_STR);
    $stmt->execute();

    // --- 成功メッセージをセットしてリダイレクト ---
    $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'コメントを投稿しました。'];
    header("Location: {$redirect_url}");
    exit();

} catch (PDOException $e) {
    error_log('Comment submission failed: ' . $e->getMessage());
    $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'コメントの投稿に失敗しました。'];
    header("Location: {$redirect_url}");
    exit();
}
// ▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲