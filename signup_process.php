<?php
// 必要なファイルを読み込む
require_once 'db_connect.php'; // データベース接続
require_once 'utils.php'; // ★エラー処理関数を読み込む
require_once 'send_mail.php';  // メール送信関数
date_default_timezone_set('Asia/Tokyo');

// ▼▼▼ この行を追加 ▼▼▼
// -----------------------------------------------------
// ★★★ ここに、手順1で決めた招待コードを入力 ★★★
// -----------------------------------------------------
define('INVITATION_CODE', 'SagaFriends-ItStudy-Summit2025'); 
// ▲▲▲ ここまで追加 ▲▲▲

// エラーメッセージを格納する配列
$errors = [];

// --- バリデーション ---
$nickname = trim($_POST['nickname'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$invite_code = trim($_POST['invite_code'] ?? '');

// (前回作成したバリデーションコードをここに記述)
if (empty($_POST['nickname'])) {
    $errors[] = 'ニックネームは必須項目です。';
}
if (empty($_POST['email'])) {
    $errors[] = 'メールアドレスは必須項目です。';
} elseif (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'メールアドレスの形式が正しくありません。';
}
if (empty($_POST['password'])) {
    $errors[] = 'パスワードは必須項目です。';
} elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/', $_POST['password'])) {
    $errors[] = 'パスワードは8文字以上で、大文字、小文字、数字をそれぞれ1文字以上含める必要があります。';
}
// ▼▼▼ このif文を追加 ▼▼▼
if (empty($invite_code)) {
    $errors[] = '招待コードは必須項目です。';
} elseif ($invite_code !== INVITATION_CODE) {
    $errors[] = '招待コードが正しくありません。';
}
// ▲▲▲ ここまで追加 ▲▲▲
// バリデーションエラーがあった場合、エラーメッセージをまとめて表示
if (!empty($errors)) {
    // エラーメッセージをHTMLのリスト形式に変換
    $error_html = '<ul>';
    foreach ($errors as $error) {
        $error_html .= '<li>' . htmlspecialchars($error) . '</li>';
    }
    $error_html .= '</ul>';
    show_error_and_exit($error_html);
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
    if ($e->getCode() == 23000) { // 一意制約違反
        show_error_and_exit('このメールアドレスは既に使用されています。');
    }
    // その他のDBエラー
    show_error_and_exit('ユーザー登録中にエラーが発生しました。', $e->getMessage());
}