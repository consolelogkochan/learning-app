<?php
// ▼▼▼▼▼ このPHPブロックを追加 ▼▼▼▼▼
session_start();

// セッションからエラーメッセージと入力値を取得
$errors = $_SESSION['errors'] ?? [];
$error_message = $_SESSION['error_message'] ?? '';
$old_email = $_SESSION['old_input']['email'] ?? '';

// 一度表示したら不要なので、セッションから関連するキーをすべて削除する
unset($_SESSION['errors'], $_SESSION['error_message'], $_SESSION['old_input']);
// ▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ログイン</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>
<body class="auth-page">
<div class="page-container">
    <main class="auth-container">
        <div class="auth-card">
            <h1>ログイン</h1>

            <?php if (isset($_GET['status']) && $_GET['status'] === 'reset_success'): ?>
                <p class="success-message">パスワードが正常に更新されました。新しいパスワードでログインしてください。</p>
            <?php endif; ?>

            <?php if ($error_message): // 認証失敗時のエラー ?>
                <p class="error-message"><?php echo htmlspecialchars($error_message, ENT_QUOTES); ?></p>
            <?php endif; ?>
            <?php if (!empty($errors)): // バリデーションエラー ?>
                <?php foreach ($errors as $error): ?>
                    <p class="error-message"><?php echo htmlspecialchars($error, ENT_QUOTES); ?></p>
                <?php endforeach; ?>
            <?php endif; ?>

            <p class="auth-navigation">アカウントをお持ちでない方は<a href="signup.php">新規登録</a>へ</p>

            <form action="login_process.php" method="POST">
                <div class="input-group">
                    <i class="fa-solid fa-envelope"></i>
                    <label for="email" class="sr-only">メールアドレス</label>
                    <input type="email" id="email" name="email" placeholder="メールアドレス" required
                           value="<?php echo htmlspecialchars($old_email, ENT_QUOTES); ?>">
                </div>
                <div class="input-group">
                    <i class="fa-solid fa-key"></i>
                    <label for="password" class="sr-only">パスワード</label>
                    <input type="password" id="password" name="password" placeholder="パスワード" required>
                </div>
                <button type="submit" class="btn btn-primary">ログイン</button>
            </form>

            <div class="form-links">
                <p>パスワードを忘れた方は<a href="password_request.php">こちら</a></p>
            </div>
        </div>
    </main>
</div>
</body>
</html>