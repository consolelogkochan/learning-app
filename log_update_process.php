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

$user_id = $_SESSION['user_id'];

// フォームからのデータを取得
$log_id = $_POST['log_id'] ?? 0;
$learning_date = $_POST['learning_date'] ?? '';
$content = trim($_POST['content'] ?? '');
$category_ids = $_POST['category_ids'] ?? [];
$durations = $_POST['durations'] ?? [];
$artifact_title = trim($_POST['artifact_title'] ?? '');
$artifact_url = trim($_POST['artifact_url'] ?? '');

// バリデーション
if (empty($log_id) || empty($learning_date) || empty($category_ids) || empty($durations) || count($category_ids) !== count($durations)) {
    show_error_and_exit('入力内容が正しくありません。');
}

// ▼▼▼ この新しいバリデーションループを追加 ▼▼▼
foreach ($category_ids as $index => $category_id) {
    // カテゴリIDが空でなく、かつ対応する時間が空の場合にエラー
    if (!empty($category_id) && empty($durations[$index])) {
        show_error_and_exit('カテゴリを選択した場合、学習時間も入力してください。（' . ($index + 1) . '番目の項目）');
    }
}
// ▲▲▲ ここまで追加 ▲▲▲

// データベース処理
try {
    // --- ▼▼▼ 権限チェック ▼▼▼ ---
    $sql_check = "SELECT user_id FROM learning_logs WHERE id = :id";
    $stmt_check = $pdo->prepare($sql_check);
    $stmt_check->bindValue(':id', $log_id, PDO::PARAM_INT);
    $stmt_check->execute();
    $log_owner = $stmt_check->fetch();

    if (!$log_owner || $log_owner['user_id'] !== $user_id) {
        show_error_and_exit('このログを編集する権限がありません。');
    }
    // --- ▲▲▲ 権限チェック ▲▲▲ ---

    // トランザクション開始
    $pdo->beginTransaction();

    // 1. learning_logsテーブルの親レコードを更新
    $sql_log = "UPDATE learning_logs SET 
                    learning_date = :learning_date, 
                    content = :content, 
                    artifact_title = :artifact_title, 
                    artifact_url = :artifact_url 
                WHERE id = :id";
    $stmt_log = $pdo->prepare($sql_log);
    $stmt_log->bindValue(':learning_date', $learning_date, PDO::PARAM_STR);
    $stmt_log->bindValue(':content', $content, PDO::PARAM_STR);
    $stmt_log->bindValue(':artifact_title', $artifact_title, PDO::PARAM_STR);
    $stmt_log->bindValue(':artifact_url', $artifact_url, PDO::PARAM_STR);
    $stmt_log->bindValue(':id', $log_id, PDO::PARAM_INT);
    $stmt_log->execute();

    // 2. 既存の子レコード（詳細・カテゴリ関連）を一旦すべて削除
    $stmt_delete_details = $pdo->prepare("DELETE FROM learning_details WHERE log_id = :log_id");
    $stmt_delete_details->bindValue(':log_id', $log_id, PDO::PARAM_INT);
    $stmt_delete_details->execute();

    $stmt_delete_log_cats = $pdo->prepare("DELETE FROM log_categories WHERE log_id = :log_id");
    $stmt_delete_log_cats->bindValue(':log_id', $log_id, PDO::PARAM_INT);
    $stmt_delete_log_cats->execute();

    // 3. 新しい子レコードを再登録（新規投稿時と同じロジック）
    $sql_detail = "INSERT INTO learning_details (log_id, category_id, duration_minutes) VALUES (:log_id, :category_id, :duration_minutes)";
    $stmt_detail = $pdo->prepare($sql_detail);
    foreach ($category_ids as $index => $category_id) {
        $duration = $durations[$index];
        if (!empty($category_id) && is_numeric($duration)) {
            $stmt_detail->bindValue(':log_id', $log_id, PDO::PARAM_INT);
            $stmt_detail->bindValue(':category_id', $category_id, PDO::PARAM_INT);
            $stmt_detail->bindValue(':duration_minutes', $duration, PDO::PARAM_INT);
            $stmt_detail->execute();
        }
    }

    $sql_log_cat = "INSERT INTO log_categories (log_id, category_id) VALUES (:log_id, :category_id)";
    $stmt_log_cat = $pdo->prepare($sql_log_cat);
    $unique_category_ids = array_unique($category_ids);
    foreach ($unique_category_ids as $category_id) {
        if (!empty($category_id)) {
            $stmt_log_cat->bindValue(':log_id', $log_id, PDO::PARAM_INT);
            $stmt_log_cat->bindValue(':category_id', $category_id, PDO::PARAM_INT);
            $stmt_log_cat->execute();
        }
    }

    // すべてのクエリが成功したらコミット
    $pdo->commit();

    // ダッシュボードにリダイレクト
    header('Location: dashboard.php');
    exit();

} catch (PDOException $e) {
    // エラーが発生したらロールバック
    $pdo->rollBack();
    show_error_and_exit('ログの更新に失敗しました。時間をおいて再度お試しください。', $e->getMessage());
}