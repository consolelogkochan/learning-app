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
$comment_id = $_POST['comment_id'] ?? 0;

if (empty($comment_id)) {
    $_SESSION['flash_message'] = ['type' => 'error', 'message' => '削除するコメントが指定されていません。'];
    header('Location: dashboard.php');
    exit();
}

// --- データベース処理 ---
try {
    // --- 権限チェック と log_id取得 ---
    $sql_check = "SELECT user_id, log_id FROM comments WHERE id = :id";
    $stmt_check = $pdo->prepare($sql_check);
    $stmt_check->bindValue(':id', $comment_id, PDO::PARAM_INT);
    $stmt_check->execute();
    $comment = $stmt_check->fetch();
    
    // アンカー付きリダイレクト先のURLを準備
    $log_id = $comment['log_id'] ?? null;
    $redirect_url = $log_id ? "dashboard.php#log-{$log_id}" : "dashboard.php";

    if (!$comment || $comment['user_id'] !== $user_id) {
        $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'このコメントを削除する権限がありません。'];
        header("Location: {$redirect_url}");
        exit();
    }

    // --- 削除処理 ---
    $sql_delete = "DELETE FROM comments WHERE id = :id";
    $stmt_delete = $pdo->prepare($sql_delete);
    $stmt_delete->bindValue(':id', $comment_id, PDO::PARAM_INT);
    $stmt_delete->execute();

    // --- 成功メッセージをセットしてリダイレクト ---
    $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'コメントを削除しました。'];
    header("Location: {$redirect_url}");
    exit();

} catch (PDOException $e) {
    error_log('Comment deletion failed: ' . $e->getMessage());
    $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'コメントの削除に失敗しました。'];
    // catchブロックでは$log_idが取得できない可能性があるので、アンカーなしでリダイレクト
    header('Location: dashboard.php');
    exit();
}
// ▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲