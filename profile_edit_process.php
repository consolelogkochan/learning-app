<?php
session_start();
require_once 'db_connect.php';
require_once 'utils.php';

// 権限チェック
if (!isset($_SESSION['user_id'])) {
    handle_system_error('この操作を行うにはログインが必要です。');
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    handle_system_error('不正なリクエストです。');
}

$user_id = $_SESSION['user_id'];

// ▼▼▼▼▼ バリデーション処理を全面的に修正 ▼▼▼▼▼
$errors = [];
$profile_image_path = null;

// フォームからのデータを取得
$nickname = trim($_POST['nickname'] ?? '');
$bio = trim($_POST['bio'] ?? '');
$experience = trim($_POST['experience'] ?? '');
$learning_goals = trim($_POST['learning_goals'] ?? '');
$objective = trim($_POST['objective'] ?? '');

// ニックネームのバリデーション
if (empty($nickname)) {
    $errors['nickname'] = 'ニックネームは必須項目です。';
}

// 画像ファイルのバリデーション
if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['profile_image'];
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($file['type'], $allowed_types)) {
        $errors['profile_image'] = '許可されていないファイル形式です。jpeg, png, gif形式の画像を選択してください。';
    }
    // ここにファイルサイズのチェックなどを追加することも可能
} elseif (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] !== UPLOAD_ERR_NO_FILE) {
    // ファイルが選択されたが、何らかの理由でアップロードに失敗した場合
    $errors['profile_image'] = '画像のアップロードに失敗しました。';
}

// バリデーションエラーがあった場合
if (!empty($errors)) {
    $_SESSION['errors'] = $errors;
    $_SESSION['old_input'] = $_POST;
    header('Location: profile_edit.php');
    exit;
}
// ▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲


try {
    // --- 画像アップロード処理 ---
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['profile_image'];
        $filename = uniqid('', true) . '_' . basename($file['name']);
        $target_path = 'uploads/' . $filename;

        if (move_uploaded_file($file['tmp_name'], $target_path)) {
            $profile_image_path = $target_path;
            
            // 古い画像があれば削除
            $stmt_old_img = $pdo->prepare("SELECT profile_image FROM users WHERE id = :id");
            $stmt_old_img->execute(['id' => $user_id]);
            $old_image = $stmt_old_img->fetchColumn();
            if ($old_image && file_exists($old_image)) {
                unlink($old_image);
            }
        } else {
            // ファイルの移動に失敗した場合
            throw new Exception('ファイルのアップロード処理に失敗しました。');
        }
    }

    // --- データベース更新処理 ---
    $sql_base = "UPDATE users SET nickname = :nickname, bio = :bio, experience = :experience, learning_goals = :learning_goals, objective = :objective";
    if ($profile_image_path) {
        $sql = $sql_base . ", profile_image = :profile_image WHERE id = :id";
    } else {
        $sql = $sql_base . " WHERE id = :id";
    }

    $stmt = $pdo->prepare($sql);
    
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
    $_SESSION['nickname'] = $nickname;

    header('Location: profile.php?id=' . $user_id);
    exit();

} catch (Exception $e) { // PDOExceptionとExceptionの両方をキャッチ
    handle_system_error('プロフィールの更新に失敗しました。', $_POST, $e->getMessage());
}