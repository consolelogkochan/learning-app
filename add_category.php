<?php
session_start();
require_once 'db_connect.php';
require_once 'utils.php'; // ★エラー処理関数を読み込む

// 権限チェックとリクエストメソッドの検証
if (!isset($_SESSION['user_id'])) {
    show_error_and_exit('この操作を行うにはログインが必要です。');
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    show_error_and_exit('不正なリクエストです。');
}

// 入力値の検証
$category_name = preg_replace('/^\s+|\s+$/u', '', $_POST['category_name'] ?? '');

if (empty($category_name)) {
    show_error_and_exit('カテゴリ名が入力されていません。');
}

// データベース処理
try {
    $sql = "INSERT INTO categories (name) VALUES (:name)";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':name', $category_name, PDO::PARAM_STR);
    $stmt->execute();

    // 成功したらフォームページに戻る
    header('Location: log_form.php');
    exit();

} catch (PDOException $e) {
    // getCode()でエラーの種類を判別
    if ($e->getCode() == 23000) { // 23000は一意制約違反のエラーコード
        show_error_and_exit('そのカテゴリは既に存在します。');
    }
    // その他のデータベースエラー
    show_error_and_exit('データベースへの保存中にエラーが発生しました。', $e->getMessage());
}