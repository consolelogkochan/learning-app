<?php
session_start();
require_once 'db_connect.php';
require_once 'utils.php'; // ★エラー処理関数を読み込む

// ログインチェック
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$nickname = $_SESSION['nickname'];

// カテゴリ一覧を取得
try {
    $sql_categories = "SELECT * FROM categories ORDER BY name ASC";
    $stmt_categories = $pdo->query($sql_categories);
    $categories = $stmt_categories->fetchAll();
} catch (PDOException $e) {
    show_error_and_exit('カテゴリの読み込みに失敗しました。', $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>学習ログの投稿</title>
    <link rel="stylesheet" href="css/style.css">
    <!-- Font Awesomeを読み込み -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>
<div class="page-container">
    <header class="form-header">
        <div class="header-content">
            <h1>学習ログ</h1>
            <a href="dashboard.php"><i class="fa-solid fa-arrow-left"></i> ダッシュボードに戻る</a>
            
        </div>
    </header>
    <main class="main-content">
    <div class="form-card">
        <section class="category-management">
            <h2>カテゴリ管理</h2>
            <form action="add_category.php" method="POST">
                <input type="text" name="category_name" placeholder="新しいカテゴリ名" required>
                <button type="submit" class="btn btn-primary">追加</button>
            </form>
        </section>
        <hr>
        <section class="log-form">
            <h2>学習ログを投稿する</h2>
            <form action="add_log_process.php" method="POST">
                <div class="form-group">
                    <label for="learning_date">学習日:</label>
                    <input type="date" id="learning_date" name="learning_date" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <div id="learning-items-container">
                    <div class="learning-item">
                        <div class="custom-select-wrapper">
                            <select name="category_ids[]" required style="display: none;">
                                <option value="">カテゴリを選択</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="custom-select-trigger">
                                <span>カテゴリを選択</span>
                                <i class="fa-solid fa-chevron-down"></i>
                            </div>
                            <div class="custom-options">
                                <?php foreach ($categories as $category): ?>
                                    <div class="custom-option" data-value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="duration-group">
                            <input type="number" name="durations[]" placeholder="学習時間 (分)" min="0" required>
                        </div>
                    </div>
                </div>
                <button type="button" id="add-item-btn" class="btn-add-item">＋ 学習項目を追加</button>
                <div class="form-group">
                    <label for="content">学習内容の詳細・メモ:</label><br>
                    <textarea id="content" name="content" rows="5"></textarea>
                </div>
                <div class="form-group">
                    <label>成果物 (任意):</label>
                    <div class="artifact-inputs">
                        <input type="text" name="artifact_title" placeholder="成果物のタイトル">
                        <input type="url" name="artifact_url" placeholder="https://example.com">
                    </div>
                </div>
                <div class="form-actions">
                  <button type="submit" class="btn btn-primary">投稿する</button>
                  <a href="dashboard.php" class="btn btn-secondary">キャンセル</a>
                </div>
            </form>
        </section>
    </div>
    </main>
</div>
    <script>
        const categories = <?php echo json_encode($categories); ?>;
    </script>
    <script src="js/main.js"></script>
</body>
</html>