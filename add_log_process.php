<?php
session_start();
require_once 'db_connect.php';
require_once 'utils.php';

// 権限チェック
if (!isset($_SESSION['user_id'])) {
    handle_system_error('この操作を行うにはログインが必要です。');
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    handle_system_error('不正なリクエストです。');
}

$user_id = $_SESSION['user_id'];

// フォームからのデータを取得
$learning_date = $_POST['learning_date'] ?? '';
$content = trim($_POST['content'] ?? '');
$category_ids = $_POST['category_ids'] ?? [];
$durations = $_POST['durations'] ?? [];
$artifact_title = trim($_POST['artifact_title'] ?? '');
$artifact_url = trim($_POST['artifact_url'] ?? '');

// ▼▼▼▼▼ バリデーション処理を全面的に修正 ▼▼▼▼▼
$errors = [];

if (empty($learning_date)) {
    $errors['date'] = '学習日を入力してください。';
}

// 学習項目が最低1つは有効かチェック
$has_valid_item = false;
foreach ($category_ids as $index => $cat_id) {
    if (!empty($cat_id) && isset($durations[$index]) && $durations[$index] !== '' && is_numeric($durations[$index])) {
        $has_valid_item = true;
        if ($durations[$index] <= 0) {
            $errors['items'][$index] = '学習時間は1分以上で入力してください。';
        }
        elseif (strlen((string)$durations[$index]) > 4) {
            $errors['items'][$index] = '学習時間は最大4桁（9999分）までです。';
        }
    } elseif (!empty($cat_id) && (empty($durations[$index]) || !is_numeric($durations[$index]))) {
        $errors['items'][$index] = 'カテゴリを選択した場合、学習時間も半角数字で入力してください。';
    }
}
if (!$has_valid_item) {
    $errors['items_general'] = '学習項目を1つ以上、正しく入力してください。';
}

// 成果物URLのバリデーション (http:// or https:// で始まるかチェック)
if (!empty($artifact_url) && !preg_match('/^https?:\/\/.+/', $artifact_url)) {
    $errors['artifact_url'] = 'URLは http:// または https:// から始まる正しい形式で入力してください。';
}
if (!empty($artifact_title) && empty($artifact_url)) {
    $errors['artifact_url'] = '成果物のタイトルを入力した場合は、URLも入力してください。';
}

// バリデーションエラーがあった場合
if (!empty($errors)) {
    $_SESSION['errors'] = $errors;
    $_SESSION['old_input'] = $_POST;
    header("Location: log_form.php");
    exit;
}
// ▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲

try {
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
    $log_id = $pdo->lastInsertId();

    // 2. learning_detailsテーブルに各学習項目を挿入
    $sql_detail = "INSERT INTO learning_details (log_id, category_id, duration_minutes) VALUES (:log_id, :category_id, :duration)";
    $stmt_detail = $pdo->prepare($sql_detail);
    foreach ($category_ids as $index => $category_id) {
        $duration = $durations[$index];
        if (!empty($category_id) && is_numeric($duration) && $duration > 0) {
            $stmt_detail->bindValue(':log_id', $log_id, PDO::PARAM_INT);
            $stmt_detail->bindValue(':category_id', $category_id, PDO::PARAM_INT);
            $stmt_detail->bindValue(':duration', $duration, PDO::PARAM_INT);
            $stmt_detail->execute();
        }
    }
    
    // log_categoriesテーブルはlearning_detailsに統合できるため、このロジックは不要になる可能性があります。
    // もし残す場合はこのままでOKです。

    $pdo->commit();
    header('Location: dashboard.php');
    exit();

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    handle_system_error('ログの投稿に失敗しました。', $_POST, $e->getMessage());
}