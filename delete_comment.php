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
$comment_id = $_POST['comment_id'] ?? 0;

if (empty($comment_id)) {
    show_error_and_exit('削除するコメントが指定されていません。');
}

// データベース処理
try {
    // --- 権限チェック ---
    $sql_check = "SELECT user_id FROM comments WHERE id = :id";
    $stmt_check = $pdo->prepare($sql_check);
    $stmt_check->bindValue(':id', $comment_id, PDO::PARAM_INT);
    $stmt_check->execute();
    $comment_owner = $stmt_check->fetch();

    if (!$comment_owner || $comment_owner['user_id'] !== $user_id) {
        show_error_and_exit('このコメントを削除する権限がありません。');
    }

    // --- 削除処理 ---
    $sql_delete = "DELETE FROM comments WHERE id = :id";
    $stmt_delete = $pdo->prepare($sql_delete);
    $stmt_delete->bindValue(':id', $comment_id, PDO::PARAM_INT);
    $stmt_delete->execute();

    // 元のページに戻る (リファラーを使用)
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit();

} catch (PDOException $e) {
    show_error_and_exit('コメントの削除に失敗しました。時間をおいて再度お試しください。', $e->getMessage());
}