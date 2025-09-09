<?php
session_start();
require_once 'db_connect.php';
require_once 'utils.php';

// ▼▼▼▼▼ このPHPブロックを全面的に修正 ▼▼▼▼▼

// ログインしていなければ、ログインページにリダイレクト
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
$user_id = $_SESSION['user_id'];

// セッションからエラーと入力値を取得
$errors = $_SESSION['errors'] ?? [];
$old_input = $_SESSION['old_input'] ?? [];
unset($_SESSION['errors'], $_SESSION['old_input']);

try {
    // 現在のユーザー情報をデータベースから取得
    $sql = "SELECT * FROM users WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch();

} catch (PDOException $e) {
    handle_system_error('ユーザー情報の読み込みに失敗しました。', [], $e->getMessage());
}
// ▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>プロフィール編集</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>
<div class="page-container">
    <header class="form-header">
        <div class="header-content">
            <h1>アカウント編集</h1>
            <a href="profile.php?id=<?php echo htmlspecialchars($user_id); ?>"><i class="fa-solid fa-arrow-left"></i> 保存せずに戻る</a>
        </div>
    </header>
    <main class="main-content">
        <div class="profile-card">
            <form action="profile_edit_process.php" method="POST" enctype="multipart/form-data">
                <div class="profile-body">
                    <div class="form-row">
                        <label>プロフィール画像</label>
                        <div class="input-area profile-image-edit">
                            <?php if (!empty($user['profile_image'])): ?>
                                <img src="<?php echo htmlspecialchars($user['profile_image']); ?>" alt="現在のプロフィール画像" class="profile-avatar-large" id="image-preview">
                            <?php else: ?>
                                <i class="fa-solid fa-user-circle profile-avatar-large-placeholder" id="image-preview"></i>
                            <?php endif; ?>
                            <label for="profile_image" class="btn btn-primary">変更する</label>
                            <input type="file" id="profile_image" name="profile_image" accept="image/jpeg, image/png, image/gif" style="display: none;">
                            <?php if (isset($errors['profile_image'])): ?>
                                <p class="error-message"><?php echo htmlspecialchars($errors['profile_image']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="form-row">
                        <label for="nickname">ニックネーム (公開用)</label>
                        <div class="input-area">
                            <input type="text" id="nickname" name="nickname" value="<?php echo htmlspecialchars($old_input['nickname'] ?? $user['nickname']); ?>" required>
                            <?php if (isset($errors['nickname'])): ?>
                                <p class="error-message"><?php echo htmlspecialchars($errors['nickname']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="form-row">
                        <label for="experience">経験</label>
                        <div class="input-area">
                            <textarea id="experience" name="experience" rows="3"><?php echo htmlspecialchars($old_input['experience'] ?? $user['experience'] ?? ''); ?></textarea>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <label for="learning_goals">学習予定の言語/技術</label>
                        <div class="input-area">
                            <textarea id="learning_goals" name="learning_goals" rows="3"><?php echo htmlspecialchars($old_input['learning_goals'] ?? $user['learning_goals'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <div class="form-row">
                        <label for="bio">自己紹介</label>
                        <div class="input-area">
                            <textarea id="bio" name="bio" rows="5"><?php echo htmlspecialchars($old_input['bio'] ?? $user['bio'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <div class="form-row">
                        <label for="objective">目標</label>
                        <div class="input-area">
                            <textarea id="objective" name="objective" rows="3"><?php echo htmlspecialchars($old_input['objective'] ?? $user['objective'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>
                <div class="form-actions">
                    <a href="profile.php?id=<?php echo htmlspecialchars($user_id); ?>" class="btn btn-secondary">キャンセル</a>
                    <button type="submit" class="btn btn-primary">更新する</button>
                </div>
            </form>
        </div>
    </main>
</div>
<script src="js/main.js"></script>
</body>
</html>