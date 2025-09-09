<?php
session_start();
require_once 'db_connect.php';
require_once 'utils.php';

// ▼▼▼▼▼ このPHPブロックを全面的に修正 ▼▼▼▼▼

// ログインチェック
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// URLから表示するユーザーのIDを取得
$profile_user_id = $_GET['id'] ?? 0;
if (empty($profile_user_id)) {
    $_SESSION['flash_message'] = ['type' => 'error', 'message' => '表示するユーザーが指定されていません。'];
    header('Location: dashboard.php');
    exit();
}

// ログインしているユーザー自身のID
$current_user_id = $_SESSION['user_id'] ?? 0;

try {
    // 表示するユーザーの情報をデータベースから取得
    $sql = "SELECT * FROM users WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':id', $profile_user_id, PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch();

    // ユーザーが存在しない場合はエラー
    if (!$user) {
        $_SESSION['flash_message'] = ['type' => 'error', 'message' => '指定されたユーザーは見つかりません。'];
        header('Location: dashboard.php');
        exit();
    }

} catch (PDOException $e) {
    error_log('User profile fetch failed: ' . $e->getMessage());
    $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'ユーザー情報の取得に失敗しました。'];
    header('Location: dashboard.php');
    exit();
}
// ▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($user['nickname']); ?>さんのプロフィール</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>
<div class="page-container">
    <header class="form-header">
        <div class="header-content">
            <h1>アカウント</h1>
            <a href="dashboard.php"><i class="fa-solid fa-arrow-left"></i> ダッシュボードに戻る</a>
            
        </div>
    </header>
    <main class="main-content">
        <div class="profile-card">
            <div class="profile-header">
                <div class="profile-header-main">
                    <?php if (!empty($user['profile_image'])): ?>
                        <img src="<?php echo htmlspecialchars($user['profile_image']); ?>" alt="プロフィール画像" class="profile-avatar-large">
                    <?php else: ?>
                        <i class="fa-solid fa-user-circle profile-avatar-large-placeholder"></i>
                    <?php endif; ?>
                    <div class="profile-name">
                        <h2><?php echo htmlspecialchars($user['nickname']); ?></h2>
                        <span class="account-id">アカウントID: <?php echo htmlspecialchars($user['id']); ?></span>
                    </div>
                </div>
                <?php if ($profile_user_id == $current_user_id): ?>
                    <div class="profile-actions">
                        <a href="profile_edit.php" class="btn btn-primary"><i class="fa-solid fa-pen"></i> 編集</a>
                    </div>
                <?php endif; ?>
            </div>

            <div class="profile-body">
                <div class="profile-item">
                    <label>メールアドレス</label>
                    <p><?php echo htmlspecialchars($user['email']); ?></p>
                </div>
                <div class="profile-item">
                    <label>経験</label>
                    <p><?php echo nl2br(htmlspecialchars($user['experience'] ?? '')); ?></p>
                </div>
                <div class="profile-item">
                    <label>学習予定の言語/技術</label>
                    <p><?php echo nl2br(htmlspecialchars($user['learning_goals'] ?? '')); ?></p>
                </div>
                <div class="profile-item">
                    <label>自己紹介</label>
                    <p><?php echo nl2br(htmlspecialchars($user['bio'] ?? '')); ?></p>
                </div>
                <div class="profile-item">
                    <label>目標</label>
                    <p><?php echo nl2br(htmlspecialchars($user['objective'] ?? '')); ?></p>
                </div>
            </div>
        </div>
    </main>
</div>
</body>
</html>