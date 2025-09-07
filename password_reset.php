<?php
require_once 'db_connect.php';
require_once 'utils.php'; // ★エラー処理関数を読み込む
date_default_timezone_set('Asia/Tokyo');

$token = $_GET['token'] ?? '';
$error = '';
$user = null;

if (empty($token)) {
    $error = '不正なアクセスです。';
} else {
    try {
        $sql = "SELECT * FROM users WHERE password_reset_token = :token AND reset_token_expires_at > NOW()";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':token', $token, PDO::PARAM_STR);
        $stmt->execute();
        $user = $stmt->fetch();

        if (!$user) {
            $error = 'このリンクは無効か、有効期限が切れています。';
        }

    } catch (PDOException $e) {
        // ▼ データベースエラー時のみ、統一エラーページを表示 ▼
        show_error_and_exit('処理中にエラーが発生しました。時間をおいて再度お試しください。', $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>新しいパスワードの設定</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body class="auth-page">
<div class="page-container">
    <main class="auth-container">
        <div class="auth-card">
            <h1>新しいパスワードの設定</h1>

            <?php if ($error): ?>
                <div style="text-align: center;">
                    <div class="error-icon">
                        <i class="fa-solid fa-circle-xmark"></i>
                    </div>
                    <p class="success-text"><?php echo htmlspecialchars($error); ?></p>
                    <div class="form-links">
                        <a href="password_request.php" class="btn btn-secondary">再設定をやり直す</a>
                    </div>
                </div>
            <?php else: ?>
                <form action="password_reset_process.php" method="POST">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                    
                    <div class="form-group">
                        <label for="password">新しいパスワード</label>
                        <small class="form-text">（大文字、小文字、数字を組み合わせた8文字以上）</small>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">新しいパスワード（確認用）</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>

                    <div class="show-password">
                        <input type="checkbox" id="show-password-check">
                        <label for="show-password-check">パスワードを表示する</label>
                    </div>
                    <button type="submit" class="btn btn-primary">パスワードを更新する</button>
                </form>
            <?php endif; ?>

        </div>
    </main>
</div>
<script src="js/main.js"></script>
</body>
</html>