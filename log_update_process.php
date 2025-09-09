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
$log_id = (int)($_POST['log_id'] ?? 0);
$learning_date = $_POST['learning_date'] ?? '';
$content = trim($_POST['content'] ?? '');
$category_ids = $_POST['category_ids'] ?? [];
$durations = $_POST['durations'] ?? [];
$artifact_title = trim($_POST['artifact_title'] ?? '');
$artifact_url = trim($_POST['artifact_url'] ?? '');

// ▼▼▼▼▼ バリデーション処理を全面的に修正 ▼▼▼▼▼
$errors = [];

if (empty($log_id)) {
    // 編集時はlog_idが必須
    handle_system_error('不正な操作です。対象のログが見つかりません。');
}

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

// 成果物URLのバリデーション
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
    header("Location: log_edit_form.php?id=" . $log_id);
    exit;
}
// ▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲

try {
    // 権限チェック
    $stmt_check = $pdo->prepare("SELECT user_id FROM learning_logs WHERE id = :id");
    $stmt_check->bindValue(':id', $log_id, PDO::PARAM_INT);
    $stmt_check->execute();
    $log_owner = $stmt_check->fetch();
    if (!$log_owner || $log_owner['user_id'] !== $user_id) {
        handle_system_error('このログを編集する権限がありません。', $_POST);
    }

    $pdo->beginTransaction();

    // 1. 親レコードを更新
    $sql_log = "UPDATE learning_logs SET learning_date = :ld, content = :c, artifact_title = :at, artifact_url = :au WHERE id = :id";
    $stmt_log = $pdo->prepare($sql_log);
    $stmt_log->bindValue(':ld', $learning_date, PDO::PARAM_STR);
    $stmt_log->bindValue(':c', $content, PDO::PARAM_STR);
    $stmt_log->bindValue(':at', $artifact_title, PDO::PARAM_STR);
    $stmt_log->bindValue(':au', $artifact_url, PDO::PARAM_STR);
    $stmt_log->bindValue(':id', $log_id, PDO::PARAM_INT);
    $stmt_log->execute();

    // 2. 既存の子レコードを一旦すべて削除
    $stmt_delete = $pdo->prepare("DELETE FROM learning_details WHERE log_id = :log_id");
    $stmt_delete->bindValue(':log_id', $log_id, PDO::PARAM_INT);
    $stmt_delete->execute();
    
    // (log_categoriesテーブルがあればこちらも削除)
    // $stmt_delete_log_cats = $pdo->prepare("DELETE FROM log_categories WHERE log_id = :log_id");
    // $stmt_delete_log_cats->execute(['log_id' => $log_id]);

    // 3. 新しい子レコードを再登録
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

    $pdo->commit();
    header('Location: dashboard.php');
    exit();

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    handle_system_error('ログの更新に失敗しました。', $_POST, $e->getMessage());
}