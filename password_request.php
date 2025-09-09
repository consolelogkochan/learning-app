<?php
// ▼▼▼▼▼ このPHPブロックを追加 ▼▼▼▼▼
session_start();

// セッションからエラーと入力値を取得
$errors = $_SESSION['errors'] ?? [];
$old_input = $_SESSION['old_input'] ?? [];

// 一度表示したら不要なので、セッションから削除
unset($_SESSION['errors'], $_SESSION['old_input']);
// ▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>パスワード再設定</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="auth-page">
<div class="page-container">
    <main class="auth-container">
        <div class="auth-card">
            <h1>パスワード再設定</h1>
            <p class="auth-navigation">ご登録のメールアドレスを入力してください。パスワード再設定用のリンクを記載したメールを送信します。</p>
            
            <form action="password_request_process.php" method="POST">
                <div class="form-group">
                    <label for="email" class="sr-only">メールアドレス</label>
                    <input type="email" id="email" name="email" placeholder="メールアドレス" required value="<?php echo htmlspecialchars($old_input['email'] ?? '', ENT_QUOTES); ?>">
                    <?php if (isset($errors['email'])): ?>
                        <p class="error-message"><?php echo htmlspecialchars($errors['email'], ENT_QUOTES); ?></p>
                    <?php endif; ?>
                </div>
                <button type="submit" class="btn btn-primary">送信する</button>
            </form>

            <div class="form-links">
                <a href="login.php">ログインページに戻る</a>
            </div>
        </div>
    </main>
</div>
</body>
</html>