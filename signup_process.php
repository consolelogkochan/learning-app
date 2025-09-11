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
    // まずは入力されたメールアドレスで既存ユーザーを検索
    $sql_find = "SELECT * FROM users WHERE email = :email";
    $stmt_find = $pdo->prepare($sql_find);
    $stmt_find->bindValue(':email', $email, PDO::PARAM_STR);
    $stmt_find->execute();
    $existing_user = $stmt_find->fetch();

    $token = bin2hex(random_bytes(32));
    $token_expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    if ($existing_user) {
        // --- ユーザーが存在する場合 ---
        if ($existing_user['is_active'] == 1) {
            // A) 認証済みユーザーの場合 -> エラー
            $_SESSION['errors']['email'] = 'このメールアドレスは既に使用されています。';
            $_SESSION['old_input'] = $_POST;
            header('Location: signup.php');
            exit;
        } else {
            // B) 未認証ユーザーの場合 -> 情報を更新してメール再送
            $sql_update = "UPDATE users SET nickname = :nickname, password = :password, verification_token = :token, token_expires_at = :expires_at WHERE id = :id";
            $stmt_update = $pdo->prepare($sql_update);
            $stmt_update->bindValue(':nickname', $nickname, PDO::PARAM_STR);
            $stmt_update->bindValue(':password', $hashed_password, PDO::PARAM_STR);
            $stmt_update->bindValue(':token', $token, PDO::PARAM_STR);
            $stmt_update->bindValue(':expires_at', $token_expires_at, PDO::PARAM_STR);
            $stmt_update->bindValue(':id', $existing_user['id'], PDO::PARAM_INT);
            $stmt_update->execute();
        }
    } else {
        // --- C) ユーザーが存在しない場合 -> 新規登録 ---
        $sql_insert = "INSERT INTO users (nickname, email, password, is_active, verification_token, token_expires_at) 
                       VALUES (:nickname, :email, :password, 0, :token, :expires_at)";
        $stmt_insert = $pdo->prepare($sql_insert);
        $stmt_insert->bindValue(':nickname', $nickname, PDO::PARAM_STR);
        $stmt_insert->bindValue(':email', $email, PDO::PARAM_STR);
        $stmt_insert->bindValue(':password', $hashed_password, PDO::PARAM_STR);
        $stmt_insert->bindValue(':token', $token, PDO::PARAM_STR);
        $stmt_insert->bindValue(':expires_at', $token_expires_at, PDO::PARAM_STR);
        $stmt_insert->execute();
    }

    // B)とC)のどちらの場合でも認証メールを送信する
    send_verification_email($email, $token);
    
    header('Location: signup_success.php');
    exit();

} catch (PDOException $e) {
    // 上記ロジックで重複エラーは処理済みのため、ここは予期せぬDBエラー
    handle_system_error('ユーザー登録中にエラーが発生しました。', $_POST, $e->getMessage());
}