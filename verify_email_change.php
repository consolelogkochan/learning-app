<?php
session_start();
require_once 'db_connect.php';
require_once 'utils.php';
date_default_timezone_set('Asia/Tokyo');

// --- トークンの検証 ---
$token = $_GET['token'] ?? '';
if (empty($token)) {
    handle_system_error('不正なアクセスです。');
}

try {
    $pdo->beginTransaction();

    // 1. トークンを使ってユーザー情報を検索
    $sql_find = "SELECT * FROM users WHERE email_change_token = :token AND email_token_expires_at > UTC_TIMESTAMP()";
    $stmt_find = $pdo->prepare($sql_find);
    $stmt_find->execute(['token' => $token]);
    $user = $stmt_find->fetch();

    if (!$user) {
        handle_system_error('このリンクは無効か、有効期限が切れています。');
    }
    $user_id = $user['id'];

    // ▼▼▼▼▼ ここからが大きな変更点 ▼▼▼▼▼

    // 2. セッションから更新すべきプロフィール情報を取得
    $update_data = $_SESSION['pending_profile_update'] ?? null;
    if (!$update_data) {
        handle_system_error('更新データが見つかりません。セッションが切れた可能性があります。もう一度やり直してください。');
    }

    // 3. 更新する値を決定
    $update_params = [
        'nickname' => $update_data['nickname'],
        'email' => $user['unverified_email'], // DBの一時保存メールを正とする
        'bio' => $update_data['bio'],
        'experience' => $update_data['experience'],
        'learning_goals' => $update_data['learning_goals'],
        'objective' => $update_data['objective'],
        'unverified_email' => null,
        'email_change_token' => null,
        'email_token_expires_at' => null,
        'id' => $user_id
    ];

    // もしパスワード変更もリクエストされていた場合、ハッシュ化して追加
    if (!empty($update_data['password'])) {
        $update_params['password'] = password_hash($update_data['password'], PASSWORD_DEFAULT);
    } else {
        $update_params['password'] = $user['password']; // パスワード変更がない場合は元の値を維持
    }

    // (画像処理はここで行う)
    
    // 4. データベースを更新
    $sql_parts = [];
    foreach ($update_params as $key => $value) {
        if ($key !== 'id') {
            $sql_parts[] = "{$key} = :{$key}";
        }
    }
    $sql_update = "UPDATE users SET " . implode(', ', $sql_parts) . " WHERE id = :id";
    $stmt_update = $pdo->prepare($sql_update);
    $stmt_update->execute($update_params);

    // 5. 不要になったセッション情報を削除
    unset($_SESSION['pending_profile_update']);
    
    // --- ▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲ ---
    
    $pdo->commit();

    // ニックネームはセッションに反映
    $_SESSION['nickname'] = $update_params['nickname'];

    // ▼▼▼ このメッセージ生成部分を修正 ▼▼▼
    $success_messages = [];
    $success_messages[] = ['type' => 'success', 'message' => 'プロフィール情報を更新しました。'];
    $success_messages[] = ['type' => 'success', 'message' => 'メールアドレスを更新しました。'];

    // パスワードも同時に更新されたかチェック
    if (!empty($update_data['password'])) {
        $success_messages[] = ['type' => 'success', 'message' => 'パスワードを更新しました。'];
    }
    
    $_SESSION['flash_messages'] = $success_messages;
    // ▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲
    header('Location: profile.php?id=' . $user_id);
    exit();

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    handle_system_error('情報の更新中にエラーが発生しました。', [], $e->getMessage());
}