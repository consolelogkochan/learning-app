<?php
session_start();
require_once 'db_connect.php';
require_once 'utils.php'; // ★エラー処理関数を読み込む

// 権限チェックとリクエストメソッドの検証
if (!isset($_SESSION['user_id'])) {
    show_error_and_exit('この操作を行うにはログインが必要です。');
}
// POSTリクエストかチェック
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    show_error_and_exit('不正なリクエストです。');
}

$user_id = $_SESSION['user_id'];
$profile_image_path = null;

// --- 画像アップロード処理 ---
if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['profile_image'];
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    
    if (!in_array($file['type'], $allowed_types)) {
        // ファイル形式が許可されていない場合のみエラー
        show_error_and_exit('許可されていないファイル形式です。jpeg, png, gif形式の画像を選択してください。');
    }
    
    $filename = uniqid('', true) . '_' . basename($file['name']);
    $target_path = 'uploads/' . $filename;

    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        $profile_image_path = $target_path;

        // 古い画像があれば削除する
        try {
            $sql_old_img = "SELECT profile_image FROM users WHERE id = :id";
            $stmt_old_img = $pdo->prepare($sql_old_img);
            $stmt_old_img->bindValue(':id', $user_id, PDO::PARAM_INT);
            $stmt_old_img->execute();
            $old_image = $stmt_old_img->fetchColumn();
            if ($old_image && file_exists($old_image)) {
                unlink($old_image);
            }
        } catch (PDOException $e) {
            error_log('Failed to delete old profile image: ' . $e->getMessage());
        }
    }
}
// --- ▲画像アップロード処理▲ ---

// --- データベース更新処理 ---
try {
    // フォームからのデータを取得
    $nickname = trim($_POST['nickname'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $experience = trim($_POST['experience'] ?? '');
    $learning_goals = trim($_POST['learning_goals'] ?? '');
    $objective = trim($_POST['objective'] ?? '');
    
    // ニックネームのバリデーション
    if (empty($nickname)) {
        show_error_and_exit('ニックネームは必須項目です。');
    }

    // SQL文を構築
    $sql_base = "UPDATE users SET nickname = :nickname, bio = :bio, experience = :experience, learning_goals = :learning_goals, objective = :objective";
    if ($profile_image_path) {
        $sql = $sql_base . ", profile_image = :profile_image WHERE id = :id";
    } else {
        $sql = $sql_base . " WHERE id = :id";
    }

    $stmt = $pdo->prepare($sql);
    
    // 値をバインド
    $stmt->bindValue(':nickname', $nickname, PDO::PARAM_STR);
    $stmt->bindValue(':bio', $bio, PDO::PARAM_STR);
    $stmt->bindValue(':experience', $experience, PDO::PARAM_STR);
    $stmt->bindValue(':learning_goals', $learning_goals, PDO::PARAM_STR);
    $stmt->bindValue(':objective', $objective, PDO::PARAM_STR);
    $stmt->bindValue(':id', $user_id, PDO::PARAM_INT);
    if ($profile_image_path) {
        $stmt->bindValue(':profile_image', $profile_image_path, PDO::PARAM_STR);
    }
    
    $stmt->execute();

    // セッションのニックネーム情報も更新
    $_SESSION['nickname'] = $nickname;

    // 編集が完了したら、自分のプロフィールページにリダイレクト
    header('Location: profile.php?id=' . $user_id);
    exit();

} catch (PDOException $e) {
    show_error_and_exit('プロフィールの更新に失敗しました。', $e->getMessage());
}