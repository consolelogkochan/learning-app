<?php
session_start();
require_once 'db_connect.php';
require_once 'utils.php';

// ▼▼▼▼▼ エラー処理を全面的に修正 ▼▼▼▼▼

// --- 事前チェック ---
if (!isset($_SESSION['user_id'])) {
    // ログインしていない場合は、flashメッセージは不要でログインページへ
    header('Location: login.php');
    exit();
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['flash_message'] = [
        'type' => 'error',
        'message' => '不正なリクエストです。'
    ];
    header('Location: dashboard.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$log_id = $_POST['log_id'] ?? 0;

if (empty($log_id)) {
    $_SESSION['flash_message'] = [
        'type' => 'error',
        'message' => '削除するログが指定されていません。'
    ];
    header('Location: dashboard.php');
    exit();
}

try {
    // --- 権限チェック ---
    $sql_check = "SELECT user_id FROM learning_logs WHERE id = :id";
    $stmt_check = $pdo->prepare($sql_check);
    $stmt_check->bindValue(':id', $log_id, PDO::PARAM_INT);
    $stmt_check->execute();
    $log_owner = $stmt_check->fetch();

    if (!$log_owner || $log_owner['user_id'] !== $user_id) {
        $_SESSION['flash_message'] = [
            'type' => 'error',
            'message' => 'このログを削除する権限がありません。'
        ];
        header('Location: dashboard.php');
        exit();
    }

    // --- 削除処理 ---
    $sql_delete = "DELETE FROM learning_logs WHERE id = :id";
    $stmt_delete = $pdo->prepare($sql_delete);
    $stmt_delete->bindValue(':id', $log_id, PDO::PARAM_INT);
    $stmt_delete->execute();

    // --- 成功メッセージをセットしてリダイレクト ---
    $_SESSION['flash_message'] = [
        'type' => 'success',
        'message' => '学習ログを削除しました。'
    ];
    header('Location: dashboard.php');
    exit();

} catch (PDOException $e) {
    error_log('Log deletion failed: ' . $e->getMessage()); // 開発者向けログ
    $_SESSION['flash_message'] = [
        'type' => 'error',
        'message' => 'エラーが発生し、ログを削除できませんでした。'
    ];
    header('Location: dashboard.php');
    exit();
}
// ▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲