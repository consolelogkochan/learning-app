<?php
// ▼▼▼▼▼ このPHPブロックを全面的に修正 ▼▼▼▼▼
session_start();
require_once 'db_connect.php';
require_once 'utils.php';
date_default_timezone_set('Asia/Tokyo');

// セッションからバリデーションエラーを取得
$errors = $_SESSION['errors'] ?? [];
unset($_SESSION['errors']);

// URLパラメータからトークンを取得
$token = $_GET['token'] ?? '';
$user = null;

// トークンの有効性をチェック
if (empty($token)) {
    $errors['token'] = '不正なアクセスです。';
} else {
    try {
        $sql = "SELECT * FROM users WHERE password_reset_token = :token AND reset_token_expires_at > NOW()";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':token', $token, PDO::PARAM_STR);
        $stmt->execute();
        $user = $stmt->fetch();

        if (!$user) {
            $errors['token'] = 'このリンクは無効か、有効期限が切れています。';
        }
    } catch (PDOException $e) {
        handle_system_error('処理中にエラーが発生しました。時間をおいて再度お試しください。', [], $e->getMessage());
    }
}
// ▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲
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

            <?php if (isset($errors['token'])): // トークン自体が無効な場合のエラー表示 ?>
                <div style="text-align: center;">
                    <div class="error-icon">
                        <i class="fa-solid fa-circle-xmark"></i>
                    </div>
                    <p class="success-text"><?php echo htmlspecialchars($errors['token']); ?></p>
                    <div class="form-links">
                        <a href="password_request.php" class="btn btn-secondary">再設定をやり直す</a>
                    </div>
                </div>
            <?php else: // トークンが有効な場合にフォームを表示 ?>
                <form action="password_reset_process.php" method="POST">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                    
                    <div class="form-group">
                        <label for="password">新しいパスワード</label>
                        <small class="form-text">（大文字、小文字、数字を組み合わせた8文字以上）</small>
                        <input type="password" id="password" name="password" required>
                        <?php if (isset($errors['password'])): ?>
                            <p class="error-message"><?php echo htmlspecialchars($errors['password'], ENT_QUOTES); ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">新しいパスワード（確認用）</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                        <?php if (isset($errors['confirm_password'])): ?>
                            <p class="error-message"><?php echo htmlspecialchars($errors['confirm_password'], ENT_QUOTES); ?></p>
                        <?php endif; ?>
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