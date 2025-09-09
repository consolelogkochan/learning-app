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

// 入力値の検証
$category_name = preg_replace('/^\s+|\s+$/u', '', $_POST['category_name'] ?? '');

if (empty($category_name)) {
    // ▼▼▼ エラー処理をセッション保存に変更 ▼▼▼
    $_SESSION['category_error'] = 'カテゴリ名が入力されていません。';
    header('Location: log_form.php');
    exit();
}

try {
    $sql = "INSERT INTO categories (name) VALUES (:name)";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':name', $category_name, PDO::PARAM_STR);
    $stmt->execute();

    // 成功したらフォームページに戻る
    header('Location: log_form.php');
    exit();

} catch (PDOException $e) {
    if ($e->getCode() == 23000) { // 一意制約違反
        // ▼▼▼ エラー処理をセッション保存に変更 ▼▼▼
        $_SESSION['category_error'] = 'そのカテゴリは既に存在します。';
        header('Location: log_form.php');
        exit();
    }
    // その他のデータベースエラー
    handle_system_error('データベースへの保存中にエラーが発生しました。', [], $e->getMessage());
}