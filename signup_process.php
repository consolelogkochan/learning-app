<?php
session_start();

// 必要なファイルを読み込む
require_once 'db_connect.php'; // データベース接続、.envを読み込む処理もここに含まれる
require_once 'utils.php'; // ★エラー処理関数を読み込む
require_once 'send_mail.php';  // メール送信関数
date_default_timezone_set('Asia/Tokyo');

// ▼▼▼▼▼ バリデーション処理を全面的に修正 ▼▼▼▼▼
// エラーメッセージを格納する配列
$errors = [];

// POSTデータを取得
$nickname = trim($_POST['nickname'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$invite_code = trim($_POST['invite_code'] ?? '');

// ニックネームのバリデーション
if (empty($nickname)) {
    $errors['nickname'] = 'ニックネームは必須項目です。';
}

// メールアドレスのバリデーション
if (empty($email)) {
    $errors['email'] = 'メールアドレスは必須項目です。';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'メールアドレスの形式が正しくありません。';
}

// パスワードのバリデーション
if (empty($password)) {
    $errors['password'] = 'パスワードは必須項目です。';
} elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/', $password)) {
    $errors['password'] = 'パスワードは8文字以上で、大文字、小文字、数字をそれぞれ1文字以上含める必要があります。';
}

// 招待コードのバリデーション
if (empty($invite_code)) {
    $errors['invite_code'] = '招待コードは必須項目です。';
} elseif ($invite_code !== $_ENV['INVITATION_CODE']) { // .envから読み込む
    $errors['invite_code'] = '招待コードが正しくありません。';
}

// バリデーションエラーがあった場合
if (!empty($errors)) {
    // エラーと入力値をセッションに保存して、フォームに戻す
    $_SESSION['errors'] = $errors;
    $_SESSION['old_input'] = $_POST;
    header('Location: signup.php');
    exit;
}


// --- DB登録とメール送信 ---
try {
    // パスワードをハッシュ化
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    // 認証用トークンと有効期限を生成
    $token = bin2hex(random_bytes(32));
    $token_expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

    // is_active=0 (未認証) の状態でユーザー情報を挿入
    $sql = "INSERT INTO users (nickname, email, password, is_active, verification_token, token_expires_at) 
            VALUES (:nickname, :email, :password, 0, :token, :expires_at)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':nickname', $nickname, PDO::PARAM_STR);
    $stmt->bindValue(':email', $email, PDO::PARAM_STR);
    $stmt->bindValue(':password', $hashed_password, PDO::PARAM_STR);
    $stmt->bindValue(':token', $token, PDO::PARAM_STR);
    $stmt->bindValue(':expires_at', $token_expires_at, PDO::PARAM_STR);
    $stmt->execute();

    // DB登録後にメール送信関数を呼び出す
    send_verification_email($email, $token);
    
    // メール送信成功時の画面にリダイレクト
    header('Location: signup_success.php');
    exit();

} catch (PDOException $e) {
    // ▼▼▼▼▼ DBエラーの処理を修正 ▼▼▼▼▼
    if ($e->getCode() == 23000) { // 一意制約違反（メールアドレス重複）
        $_SESSION['errors']['email'] = 'このメールアドレスは既に使用されています。';
        $_SESSION['old_input'] = $_POST;
        header('Location: signup.php');
        exit;
    }
    // その他のDBエラーはシステムエラーとして処理
    handle_system_error('ユーザー登録中にエラーが発生しました。', $_POST, $e->getMessage());
    // ▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲
}