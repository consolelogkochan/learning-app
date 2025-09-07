<?php
require_once 'db_connect.php';
require_once 'utils.php'; // ★エラー処理関数を読み込む
require_once 'send_mail.php';
date_default_timezone_set('Asia/Tokyo');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    show_error_and_exit('不正なリクエストです。');
}

$email = $_POST['email'] ?? '';

try {
    // 入力されたメールアドレスを持つユーザーを検索
    $sql_find = "SELECT * FROM users WHERE email = :email";
    $stmt_find = $pdo->prepare($sql_find);
    $stmt_find->bindValue(':email', $email, PDO::PARAM_STR);
    $stmt_find->execute();
    $user = $stmt_find->fetch();

    // ユーザーが存在する場合のみ、トークン発行とメール送信を行う
    if ($user) {
        // トークンと有効期限を生成 (有効期限は15分)
        $token = bin2hex(random_bytes(32));
        $expires_at = date('Y-m-d H:i:s', strtotime('+15 minutes'));

        // データベースにトークンと有効期限を保存
        $sql_update = "UPDATE users SET password_reset_token = :token, reset_token_expires_at = :expires_at WHERE id = :id";
        $stmt_update = $pdo->prepare($sql_update);
        $stmt_update->bindValue(':token', $token);
        $stmt_update->bindValue(':expires_at', $expires_at);
        $stmt_update->bindValue(':id', $user['id'], PDO::PARAM_INT);
        $stmt_update->execute();

        // メール送信関数を呼び出す
        send_password_reset_email($email, $token);
    }

    // ★重要：ユーザーが存在してもしなくても、同じ完了画面にリダイレクトする
    header('Location: password_request_success.php');
    exit();

} catch (PDOException $e) {
    // DBエラーが発生した場合は、統一エラーページを表示
    show_error_and_exit('処理中にエラーが発生しました。時間をおいて再度お試しください。', $e->getMessage());
}