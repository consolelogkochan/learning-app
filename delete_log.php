<?php
session_start();
require_once 'db_connect.php';
require_once 'utils.php'; // ★エラー処理関数を読み込む

// 権限チェックとリクエストメソッドの検証
if (!isset($_SESSION['user_id'])) {
    show_error_and_exit('この操作を行うにはログインが必要です。');
}
// POSTリクエストかチェック
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    show_error_and_exit('不正なリクエストです。');
}

// 入力値の検証
$user_id = $_SESSION['user_id'];
$log_id = $_POST['log_id'] ?? 0;

if (empty($log_id)) {
    show_error_and_exit('削除するログが指定されていません。');
}

// データベース処理
try {
    // --- 権限チェック ---
    $sql_check = "SELECT user_id FROM learning_logs WHERE id = :id";
    $stmt_check = $pdo->prepare($sql_check);
    // ▼▼▼ この行を修正しました ▼▼▼
    $stmt_check->bindValue(':id', $log_id, PDO::PARAM_INT);
    $stmt_check->execute();
    $log_owner = $stmt_check->fetch();

    if (!$log_owner || $log_owner['user_id'] !== $user_id) {
        show_error_and_exit('このログを削除する権限がありません。');
    }

    // --- 削除処理 ---
    $sql_delete = "DELETE FROM learning_logs WHERE id = :id";
    $stmt_delete = $pdo->prepare($sql_delete);
    $stmt_delete->bindValue(':id', $log_id, PDO::PARAM_INT);
    $stmt_delete->execute();

    // ダッシュボードにリダイレクト
    header('Location: dashboard.php');
    exit();

} catch (PDOException $e) {
    show_error_and_exit('ログの削除に失敗しました。時間をおいて再度お試しください。', $e->getMessage());
}