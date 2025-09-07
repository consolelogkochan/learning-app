<?php
// セッションを開始する (必ずファイルの冒頭に記述)
session_start();

require_once 'db_connect.php';
require_once 'utils.php'; // ★エラー処理関数を読み込む

// バリデーション
if (empty($_POST['email']) || empty($_POST['password'])) {
    show_error_and_exit('メールアドレスとパスワードを入力してください。');
}

$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

try {
    // ユーザーをメールアドレスで検索
    $sql = "SELECT * FROM users WHERE email = :email";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':email', $email, PDO::PARAM_STR);
    $stmt->execute();
    $user = $stmt->fetch();

    // ユーザーが存在し、アカウントが有効で、パスワードが一致するかチェック
    if ($user && $user['is_active'] == 1 && password_verify($password, $user['password'])) {
        // 認証成功

        // セッションIDを再生成してセキュリティを向上させる
        session_regenerate_id(true);

        // セッションにユーザー情報を保存
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['nickname'] = $user['nickname'];

        // メインのダッシュボードページにリダイレクト
        header('Location: dashboard.php');
        exit();

    } else {
        // 認証失敗
        show_error_and_exit('メールアドレスまたはパスワードが間違っています。');
    }

} catch (PDOException $e) {
    show_error_and_exit('認証処理中にエラーが発生しました。', $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ログインエラー</title>
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
            <h1>ログインエラー</h1>
            <p class="success-text"><?php echo htmlspecialchars($error_message); ?></p>
            <div class="form-links">
                <a href="login.php" class="btn btn-secondary">ログインページに戻る</a>
            </div>
        </div>
    </main>
</div>
</body>
</html>