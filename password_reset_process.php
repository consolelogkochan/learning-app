<?php
// ▼▼▼▼▼ この一行を追加 ▼▼▼▼▼
session_start();
// ▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲

require_once 'db_connect.php';
require_once 'utils.php';
date_default_timezone_set('Asia/Tokyo');

// ▼▼▼▼▼ バリデーション処理を全面的に修正 ▼▼▼▼▼
$token = $_POST['token'] ?? '';
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

// エラーを格納する連想配列
$errors = [];

// バリデーション
if (empty($password)) {
    $errors['password'] = '新しいパスワードを入力してください。';
} elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/', $password)) {
    $errors['password'] = 'パスワードは8文字以上で、大文字、小文字、数字をそれぞれ1文字以上含める必要があります。';
}

if (empty($confirm_password)) {
    $errors['confirm_password'] = '確認用パスワードを入力してください。';
} elseif ($password !== $confirm_password) {
    $errors['confirm_password'] = 'パスワードが一致しません。';
}


// バリデーションエラーがあった場合
if (!empty($errors)) {
    $_SESSION['errors'] = $errors;
    // エラー時は hidden の token だけセッションに戻す
    $_SESSION['token'] = $token; 
    header('Location: password_reset.php?token=' . urlencode($token));
    exit;
}
// ▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲


try {
    // トークンが有効か再度チェック
    $sql_check = "SELECT id FROM users WHERE password_reset_token = :token AND reset_token_expires_at > NOW()";
    $stmt_check = $pdo->prepare($sql_check);
    $stmt_check->bindValue(':token', $token, PDO::PARAM_STR);
    $stmt_check->execute();
    $user = $stmt_check->fetch();

    if (!$user) {
        // トークンが無効な場合は、ユーザーに入力フォームに戻ってもらう
        $_SESSION['error_message'] = 'このリンクは無効か、有効期限が切れています。もう一度、パスワード再設定を申請してください。';
        header('Location: password_request.php'); // 申請ページに戻す
        exit();
    } else {
        // パスワードをハッシュ化
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // データベースを更新
        $sql_update = "UPDATE users SET password = :password, password_reset_token = NULL, reset_token_expires_at = NULL WHERE id = :id";
        $stmt_update = $pdo->prepare($sql_update);
        $stmt_update->bindValue(':password', $hashed_password);
        $stmt_update->bindValue(':id', $user['id'], PDO::PARAM_INT);
        $stmt_update->execute();

        // 成功時はログインページにリダイレクト
        header('Location: login.php?status=reset_success');
        exit();
    }
} catch (PDOException $e) {
    // ▼▼▼▼▼ DBエラーの処理を新しい関数に変更 ▼▼▼▼▼
    handle_system_error('パスワードの更新中にエラーが発生しました。', [], $e->getMessage());
}