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

// フォームからのデータを取得
$user_id = $_SESSION['user_id'];
$learning_date = $_POST['learning_date'] ?? '';
$content = trim($_POST['content'] ?? '');
$category_ids = $_POST['category_ids'] ?? [];
$durations = $_POST['durations'] ?? [];
$artifact_title = trim($_POST['artifact_title'] ?? '');
$artifact_url = trim($_POST['artifact_url'] ?? '');

// バリデーション
if (empty($learning_date) || empty($category_ids) || empty($durations) || count($category_ids) !== count($durations)) {
    show_error_and_exit('入力内容が正しくありません。');
}
// ▼▼▼ このチェックを追加 ▼▼▼
if (empty($category_ids[0])) {
    show_error_and_exit('最初の学習項目のカテゴリを選択してください。');
}
// ▲▲▲ ここまで追加 ▲▲▲

// ▼▼▼ この新しいバリデーションループを追加 ▼▼▼
foreach ($category_ids as $index => $category_id) {
    // カテゴリIDが空でなく、かつ対応する時間が空の場合にエラー
    if (!empty($category_id) && empty($durations[$index])) {
        show_error_and_exit('カテゴリを選択した場合、学習時間も入力してください。（' . ($index + 1) . '番目の項目）');
    }
}
// ▲▲▲ ここまで追加 ▲▲▲

try {
    // トランザクション開始
    $pdo->beginTransaction();

    // 1. learning_logsテーブルに親レコードを挿入
    $sql_log = "INSERT INTO learning_logs (user_id, learning_date, content, artifact_title, artifact_url)
                VALUES (:user_id, :learning_date, :content, :artifact_title, :artifact_url)";
    $stmt_log = $pdo->prepare($sql_log);
    $stmt_log->bindValue(':user_id', $user_id, PDO::PARAM_INT);
    $stmt_log->bindValue(':learning_date', $learning_date, PDO::PARAM_STR);
    $stmt_log->bindValue(':content', $content, PDO::PARAM_STR);
    $stmt_log->bindValue(':artifact_title', $artifact_title, PDO::PARAM_STR);
    $stmt_log->bindValue(':artifact_url', $artifact_url, PDO::PARAM_STR);
    $stmt_log->execute();

    // 挿入された親レコードのIDを取得
    $log_id = $pdo->lastInsertId();

    // 2. learning_detailsテーブルに各学習項目を挿入
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

    // 3. log_categoriesテーブルに、このログで使われたカテゴリの関連を記録
    $sql_log_cat = "INSERT INTO log_categories (log_id, category_id) VALUES (:log_id, :category_id)";
    $stmt_log_cat = $pdo->prepare($sql_log_cat);
    
    // 複数の項目で同じカテゴリを選んだ場合も考慮し、重複を除外
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
    show_error_and_exit('ログの投稿に失敗しました。時間をおいて再度お試しください。', $e->getMessage());
}