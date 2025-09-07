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

            <p class="auth-navigation">アカウントをお持ちでない方は<a href="signup.php">新規登録</a>へ</p>

            <form action="login_process.php" method="POST">
                <div class="input-group">
                    <i class="fa-solid fa-envelope"></i>
                    <label for="email" class="sr-only">メールアドレス</label>
                    <input type="email" id="email" name="email" placeholder="メールアドレス" required>
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