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
    <title>アカウント作成</title>
    <link rel="stylesheet" href="css/style.css"> 
</head>
<body class="auth-page">
<div class="page-container">
    <main class="auth-container">
        <div class="auth-card">
            <h1>アカウント作成</h1>
            <p class="auth-navigation">
                会員登録により、<a href="#">個人情報取り扱い</a>および<a href="#">利用規約</a>に同意するものとします。
            </p>

            <form action="signup_process.php" method="POST">
                <div class="form-group">
                    <label for="nickname">ニックネーム (公開用) <span class="required">*必須</span></label>
                    <input type="text" id="nickname" name="nickname" placeholder="学習 太郎" required value="<?php echo htmlspecialchars($old_input['nickname'] ?? '', ENT_QUOTES); ?>">
                    <?php if (isset($errors['nickname'])): ?>
                        <p class="error-message"><?php echo htmlspecialchars($errors['nickname'], ENT_QUOTES); ?></p>
                    <?php endif; ?>
                </div>
                <div class="form-group">
                    <label for="email">メールアドレス <span class="required">*必須</span></label>
                    <input type="email" id="email" name="email" placeholder="email@example.com" required value="<?php echo htmlspecialchars($old_input['email'] ?? '', ENT_QUOTES); ?>">
                    <?php if (isset($errors['email'])): ?>
                        <p class="error-message"><?php echo htmlspecialchars($errors['email'], ENT_QUOTES); ?></p>
                    <?php endif; ?>
                </div>
                <div class="form-group">
                    <label for="password">パスワード <span class="required">*必須</span></label>
                    <small class="form-text">（大文字、小文字、数字を組み合わせた8文字以上）</small>
                    <input type="password" id="password" name="password" required>
                    <?php if (isset($errors['password'])): ?>
                        <p class="error-message"><?php echo htmlspecialchars($errors['password'], ENT_QUOTES); ?></p>
                    <?php endif; ?>
                </div>
                <div class="form-group">
                    <label for="invite_code">招待コード <span class="required">*必須</span></label>
                    <input type="text" id="invite_code" name="invite_code" placeholder="管理者から伝えられたコード" required value="<?php echo htmlspecialchars($old_input['invite_code'] ?? '', ENT_QUOTES); ?>">
                    <?php if (isset($errors['invite_code'])): ?>
                        <p class="error-message"><?php echo htmlspecialchars($errors['invite_code'], ENT_QUOTES); ?></p>
                    <?php endif; ?>
                </div>
                
                <div class="show-password">
                    <input type="checkbox" id="show-password-check">
                    <label for="show-password-check">パスワードを表示する</label>
                </div>

                <button type="submit" class="btn btn-primary">登録する</button>
            </form>

            <div class="form-links">
                <p>すでにアカウントをお持ちですか？ <a href="login.php">ログイン</a></p>
            </div>
        </div>
    </main>
</div>
    <script src="js/main.js"></script>
</body>
</html>