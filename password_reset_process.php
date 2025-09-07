<?php
require_once 'db_connect.php';
require_once 'utils.php'; // ★エラー処理関数を読み込む
date_default_timezone_set('Asia/Tokyo');

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $error_message = '不正なリクエストです。';
} else {
    $token = $_POST['token'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // バリデーション
    if (empty($token) || empty($password) || empty($confirm_password)) {
        $error_message = 'すべての項目を入力してください。';
    } elseif ($password !== $confirm_password) {
        $error_message = 'パスワードが一致しません。';
    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/', $password)) {
        $error_message = 'パスワードは8文字以上で、大文字、小文字、数字をそれぞれ1文字以上含める必要があります。';
    }

    // バリデーションエラーがなければDB処理へ
    if (empty($error_message)) {
        try {
            // トークンが有効か再度チェック
            $sql_check = "SELECT id FROM users WHERE password_reset_token = :token AND reset_token_expires_at > NOW()";
            $stmt_check = $pdo->prepare($sql_check);
            $stmt_check->bindValue(':token', $token, PDO::PARAM_STR);
            $stmt_check->execute();
            $user = $stmt_check->fetch();

            if (!$user) {
                $error_message = 'このリンクは無効か、有効期限が切れています。';
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
            // ▼ catchブロックの処理をshow_error_and_exitに変更 ▼
            show_error_and_exit('パスワードの更新中にエラーが発生しました。', $e->getMessage());
        }
    }
}
// もし$error_messageに何か入っていたら、統一エラーページを表示
if (!empty($error_message)) {
    show_error_and_exit($error_message);
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>エラー</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body class="auth-page">
<div class="page-container">
    <main class="auth-container">
        <div class="auth-card" style="text-align: center;">
            <div class="error-icon">
                <i class="fa-solid fa-circle-xmark"></i>
            </div>
            <h1>処理エラー</h1>
            <p class="success-text"><?php echo htmlspecialchars($error_message); ?></p>
            <div class="form-links">
                <a href="password_request.php" class="btn btn-secondary">パスワード再設定をやり直す</a>
            </div>
        </div>
    </main>
</div>
</body>
</html>