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

                    <hr class="form-divider">

                    <div class="form-row">
                        <label for="email">メールアドレス</label>
                        <div class="input-area">
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($old_input['email'] ?? $user['email']); ?>" required>
                            <?php if (isset($errors['email'])): ?>
                                <p class="error-message"><?php echo htmlspecialchars($errors['email']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <hr class="form-divider">

                    <h3 class="form-section-title">パスワードを変更する</h3>
                    <div class="form-row">
                        <label for="current_password">現在のパスワード</label>
                        <div class="input-area">
                            <input type="password" id="current_password" name="current_password" placeholder="変更する場合に入力">
                            <small class="form-text">パスワードを変更する場合のみ、現在のパスワードを入力してください。</small>
                            <?php if (isset($errors['current_password'])): ?>
                                <p class="error-message"><?php echo htmlspecialchars($errors['current_password']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="form-row">
                        <label for="password">新しいパスワード</label>
                        <div class="input-area">
                            <input type="password" id="password" name="password" placeholder="新しいパスワード">
                            <small class="form-text">（大文字、小文字、数字を組み合わせた8文字以上）</small>
                            <?php if (isset($errors['password'])): ?>
                                <p class="error-message"><?php echo htmlspecialchars($errors['password']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="form-row">
                        <label for="confirm_password">新しいパスワード（確認用）</label>
                        <div class="input-area">
                            <input type="password" id="confirm_password" name="confirm_password" placeholder="新しいパスワードを再入力">
                            <?php if (isset($errors['confirm_password'])): ?>
                                <p class="error-message"><?php echo htmlspecialchars($errors['confirm_password']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="form-row">
                        <label></label> <div class="input-area">
                            <div class="show-password">
                                <input type="checkbox" id="show-password-check">
                                <label for="show-password-check">パスワードを表示する</label>
                            </div>
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
<?php
// もしバリデーションエラーが存在する場合のみ、スクロール用のスクリプトを出力
if (!empty($errors)) {
    // 最初に発生したエラーのキーを取得 (例: 'nickname', 'email'など)
    $first_error_key = array_key_first($errors);

    // エラーキーと、対応する入力欄のHTMLのid属性をマッピング
    $element_id_map = [
        'profile_image' => 'profile_image',
        'nickname' => 'nickname',
        'email' => 'email',
        'current_password' => 'current_password',
        'password' => 'password',
        'confirm_password' => 'confirm_password'
    ];

    // マップに存在するキーであれば、スクロール先のIDを決定
    if (isset($element_id_map[$first_error_key])) {
        $scroll_to_id = $element_id_map[$first_error_key];
        ?>
        <script>
            // ページの読み込みが完了したら実行
            document.addEventListener('DOMContentLoaded', function() {
                const errorElement = document.getElementById('<?php echo $scroll_to_id; ?>');
                if (errorElement) {
                    // エラー要素の位置までスムーズにスクロール
                    errorElement.scrollIntoView({
                        behavior: 'smooth', // スムーズなスクロール
                        block: 'center'     // 画面の中央に表示
                    });
                }
            });
        </script>
        <?php
    }
}
?>
</body>
</html>