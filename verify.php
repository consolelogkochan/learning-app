<?php
require_once 'db_connect.php';
date_default_timezone_set('Asia/Tokyo');

$token = $_GET['token'] ?? '';
$is_success = false;
$message_title = '';
$message_body = '';
$link_url = '';
$link_text = '';

if (empty($token)) {
    $message_title = '不正なアクセス';
    $message_body = 'リンクが正しくありません。';
    $link_url = 'signup.php';
    $link_text = '新規登録ページへ';
} else {
    try {
        $sql = "SELECT * FROM users WHERE verification_token = :token AND is_active = 0 AND token_expires_at > NOW()";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':token', $token, PDO::PARAM_STR);
        $stmt->execute();
        $user = $stmt->fetch();

        if ($user) {
            $update_sql = "UPDATE users SET is_active = 1, verification_token = NULL, token_expires_at = NULL WHERE id = :id";
            $update_stmt = $pdo->prepare($update_sql);
            $update_stmt->bindValue(':id', $user['id'], PDO::PARAM_INT);
            $update_stmt->execute();

            $is_success = true;
            $message_title = '認証が完了しました！';
            $message_body = 'アカウントが有効になりました。ログインページからログインしてください。';
            $link_url = 'login.php';
            $link_text = 'ログインページへ';
        } else {
            $message_title = '認証エラー';
            $message_body = 'このリンクは無効か、有効期限が切れています。お手数ですが、もう一度登録をやり直してください。';
            $link_url = 'signup.php';
            $link_text = '新規登録ページへ';
        }
    } catch (PDOException $e) {
        $message_title = 'データベースエラー';
        $message_body = '処理中にエラーが発生しました。';
        $link_url = 'signup.php';
        $link_text = '新規登録ページへ';
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($message_title); ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body class="auth-page">
<div class="page-container">
    <main class="auth-container">
        <div class="auth-card" style="text-align: center;">
            <?php if ($is_success): ?>
                <div class="success-icon">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
            <?php else: ?>
                <div class="error-icon">
                    <i class="fa-solid fa-circle-xmark"></i>
                </div>
            <?php endif; ?>
            
            <h1><?php echo htmlspecialchars($message_title); ?></h1>
            <p class="success-text"><?php echo htmlspecialchars($message_body); ?></p>
            
            <div class="form-links">
                <a href="<?php echo $link_url; ?>" class="btn <?php echo $is_success ? 'btn-primary' : 'btn-secondary'; ?>">
                    <?php echo $link_text; ?>
                </a>
            </div>
        </div>
    </main>
</div>
</body>
</html>