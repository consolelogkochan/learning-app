<?php
// セッションを開始する (必ずファイルの冒頭に記述)
session_start();

require_once 'db_connect.php';
require_once 'utils.php'; // ★エラー処理関数を読み込む

// ▼▼▼▼▼ バリデーション処理を修正 ▼▼▼▼▼
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

$errors = [];
if (empty($email)) {
    $errors['email'] = 'メールアドレスを入力してください。';
}
if (empty($password)) {
    $errors['password'] = 'パスワードを入力してください。';
}

// バリデーションエラーがあった場合
if (!empty($errors)) {
    // エラーと入力値をセッションに保存して、フォームに戻す
    $_SESSION['errors'] = $errors;
    $_SESSION['old_input'] = $_POST;
    header('Location: login.php');
    exit;
}
// ▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲

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
        // ▼▼▼▼▼ 認証失敗の処理を修正 ▼▼▼▼▼
        // エラーとメールアドレスをセッションに保存して、フォームに戻す
        $_SESSION['error_message'] = 'メールアドレスまたはパスワードが間違っています。';
        $_SESSION['old_input']['email'] = $email;
        header('Location: login.php');
        exit;
        // ▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲
    }

} catch (PDOException $e) {
    // ▼▼▼▼▼ DBエラーの処理を修正 ▼▼▼▼▼
    // 新しいシステムエラー処理関数を呼び出す
    handle_system_error('認証処理中にエラーが発生しました。', $_POST, $e->getMessage());
    // ▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲
}
?>

