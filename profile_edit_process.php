<?php
session_start();
require_once 'db_connect.php';
require_once 'utils.php';
require_once 'send_mail.php'; // メール送信関数を読み込む

// --- 事前チェック ---
if (!isset($_SESSION['user_id'])) {
    handle_system_error('この操作を行うにはログインが必要です。');
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    handle_system_error('不正なリクエストです。');
}

$user_id = $_SESSION['user_id'];
$errors = [];
$profile_image_path = null;

// --- フォームからのデータを取得 ---
$nickname = trim($_POST['nickname'] ?? '');
$email = trim($_POST['email'] ?? '');
$current_password = $_POST['current_password'] ?? '';
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';
$bio = trim($_POST['bio'] ?? '');
$experience = trim($_POST['experience'] ?? '');
$learning_goals = trim($_POST['learning_goals'] ?? '');
$objective = trim($_POST['objective'] ?? '');

try {
    // --- DBから現在のユーザー情報を取得 ---
    $stmt_user = $pdo->prepare("SELECT * FROM users WHERE id = :id");
    $stmt_user->execute(['id' => $user_id]);
    $user = $stmt_user->fetch();

    // --- バリデーション ---
    if (empty($nickname)) $errors['nickname'] = 'ニックネームは必須です。';
    if (empty($email)) $errors['email'] = 'メールアドレスは必須です。';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'メールアドレスの形式が正しくありません。';

    // --- 処理の分岐を判定 ---
    $is_email_changed = ($email !== $user['email']);
    $is_password_changed = !empty($password) || !empty($current_password) || !empty($confirm_password);

    // ▼▼▼ この重複チェックブロックを丸ごと追加 ▼▼▼
    // もしメールアドレスが変更されていれば、重複がないかチェックする
    if ($is_email_changed) {
        $stmt_check_email = $pdo->prepare("SELECT id FROM users WHERE email = :email");
        $stmt_check_email->execute(['email' => $email]);
        if ($stmt_check_email->fetch()) {
            $errors['email'] = 'このメールアドレスは既に使用されています。';
        }
    }
    // ▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲

    // --- パスワード変更バリデーション ---
    if ($is_password_changed) {
        if (empty($current_password)) $errors['current_password'] = '現在のパスワードを入力してください。';
        if (empty($password)) $errors['password'] = '新しいパスワードを入力してください。';
        if (empty($confirm_password)) $errors['confirm_password'] = '確認用パスワードを入力してください。';
        
        // 上記エラーがない場合のみ、さらに詳細なチェック
        if (empty($errors)) {
            if (!password_verify($current_password, $user['password'])) {
                $errors['current_password'] = '現在のパスワードが間違っています。';
            }
            if ($password !== $confirm_password) {
                $errors['confirm_password'] = '新しいパスワードが一致しません。';
            }
            if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/', $password)) {
                $errors['password'] = 'パスワードは8文字以上で、大文字、小文字、数字をそれぞれ1文字以上含める必要があります。';
            }
        }
    }

    // --- 画像ファイルのバリデーション ---
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['profile_image'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($file['type'], $allowed_types)) {
            $errors['profile_image'] = '許可されていないファイル形式です。';
        }
    } elseif (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $errors['profile_image'] = '画像のアップロードに失敗しました。';
    }

    // --- バリデーションエラーがあれば、フォームに戻る ---
    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        $_SESSION['old_input'] = $_POST;
        header('Location: profile_edit.php');
        exit;
    }

    // --- 処理の実行 ---
    $pdo->beginTransaction();

    $success_messages = []; // ★成功メッセージを格納する配列を用意

    // 1. 画像アップロード処理 (バリデーション通過後)
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['profile_image'];
        $filename = uniqid('', true) . '_' . basename($file['name']);
        $target_path = 'uploads/' . $filename;

        if (move_uploaded_file($file['tmp_name'], $target_path)) {
            $profile_image_path = $target_path;
            if ($user['profile_image'] && file_exists($user['profile_image'])) {
                unlink($user['profile_image']);
            }
        } else {
            throw new Exception('ファイルのアップロード処理に失敗しました。');
        }
    }

    // 2. 更新内容を配列にまとめる
    $update_params = [
        'nickname' => $nickname,
        'bio' => $bio,
        'experience' => $experience,
        'learning_goals' => $learning_goals,
        'objective' => $objective,
        'id' => $user_id
    ];

    if ($is_password_changed) {
        $update_params['password'] = password_hash($password, PASSWORD_DEFAULT);
    }
    if ($profile_image_path) {
        $update_params['profile_image'] = $profile_image_path;
    }


    if ($is_email_changed) {
        // --- A) メールアドレス変更がある場合 -> 認証プロセスへ ---
        $token = bin2hex(random_bytes(32));
        $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $sql = "UPDATE users SET unverified_email = :email, email_change_token = :token, email_token_expires_at = :expires_at WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'email' => $email,
            'token' => $token,
            'expires_at' => $expires_at,
            'id' => $user_id
        ]);

        // 更新すべき情報をセッションに一時保存
        $_SESSION['pending_profile_update'] = $_POST;
        
        // (注意: この時点では他の情報は更新しない)
        send_email_change_verification($email, $token); 
        
        // メッセージを配列に追加
        $success_messages[] = ['type' => 'success', 'message' => '新しいメールアドレスに認証メールを送信しました。メールを確認して変更を完了してください。'];
        
    } else {
        // --- B) メールアドレス変更がない場合 -> 即時更新 ---
        $sql_parts = [];
        foreach ($update_params as $key => $value) {
            if ($key !== 'id') {
                $sql_parts[] = "{$key} = :{$key}";
            }
        }
        $sql = "UPDATE users SET " . implode(', ', $sql_parts) . " WHERE id = :id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($update_params);

        $_SESSION['nickname'] = $nickname;
        // ▼▼▼ このメッセージ生成部分を修正 ▼▼▼
        $success_messages = [];
        $success_messages[] = ['type' => 'success', 'message' => 'プロフィール情報を更新しました。'];
        if ($is_password_changed) {
            $success_messages[] = ['type' => 'success', 'message' => 'パスワードを更新しました。'];
        }
        $_SESSION['flash_messages'] = $success_messages;
        // ▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲
    }

    // ★セッションには配列を保存
    $_SESSION['flash_messages'] = $success_messages; // 'flash_message' -> 'flash_messages' (複数形に)
    
    $pdo->commit();
    header('Location: profile.php?id=' . $user_id);
    exit();

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    
    // メールアドレス重複エラーのハンドリング
    if ($e instanceof PDOException && $e->getCode() == 23000) {
        $_SESSION['errors']['email'] = 'このメールアドレスは既に使用されています。';
        $_SESSION['old_input'] = $_POST;
        header('Location: profile_edit.php');
        exit;
    }

    handle_system_error('プロフィールの更新に失敗しました。', $_POST, $e->getMessage());
}