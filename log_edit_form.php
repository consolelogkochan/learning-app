<?php
session_start();
require_once 'db_connect.php';
require_once 'utils.php'; // ★エラー処理関数を読み込む

// ログインチェック
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$log_id = $_GET['id'] ?? 0;

if (empty($log_id)) {
    show_error_and_exit('編集するログが指定されていません。');
}


try {
    // 編集対象のログデータを取得
    $sql = "SELECT * FROM learning_logs WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':id', $log_id, PDO::PARAM_INT);
    $stmt->execute();
    $log = $stmt->fetch();

    // ログが存在しない、または自分が投稿したログでない場合はエラー
    if (!$log || $log['user_id'] !== $user_id) {
        show_error_and_exit('指定されたログを編集する権限がありません。');
    }

    // ログに紐づく詳細（カテゴリと時間）を取得
    $sql_details = "SELECT * FROM learning_details WHERE log_id = :log_id";
    $stmt_details = $pdo->prepare($sql_details);
    $stmt_details->bindValue(':log_id', $log_id, PDO::PARAM_INT);
    $stmt_details->execute();
    $details = $stmt_details->fetchAll();
    
    // カテゴリ一覧を取得
    $sql_categories = "SELECT * FROM categories ORDER BY name ASC";
    $categories = $pdo->query($sql_categories)->fetchAll();

} catch (PDOException $e) {
    show_error_and_exit('データの読み込みに失敗しました。時間をおいて再度お試しください。', $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>学習ログの編集</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>
<div class="page-container">
    <header class="form-header">
        <div class="header-content">
            <h1>学習ログの編集</h1>
            <a href="dashboard.php"><i class="fa-solid fa-arrow-left"></i> ダッシュボードに戻る</a>
            
        </div>
    </header>
    <main class="main-content">
    <div class="form-card">
        <section class="log-form">
            <h2>学習ログを編集する</h2>
            <form action="log_update_process.php" method="POST">
                <input type="hidden" name="log_id" value="<?php echo htmlspecialchars($log_id); ?>">
                
                <div class="form-group">
                    <label for="learning_date">学習日:</label>
                    <input type="date" id="learning_date" name="learning_date" value="<?php echo htmlspecialchars($log['learning_date']); ?>" required>
                </div>

                <div id="learning-items-container">
                    <?php foreach ($details as $detail): ?>
                    <div class="learning-item">
                        <div class="custom-select-wrapper">
                            <select name="category_ids[]" required style="display: none;">
                                <option value="">カテゴリを選択</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" <?php if($detail['category_id'] == $category['id']) echo 'selected'; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
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
                            <input type="number" name="durations[]" placeholder="学習時間 (分)" min="0" value="<?php echo htmlspecialchars($detail['duration_minutes']); ?>" required>
                            <button type="button" class="remove-item-btn">削除</button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" id="add-item-btn" class="btn-add-item">＋ 学習項目を追加</button>

                <div class="form-group">
                    <label for="content">学習内容の詳細・メモ:</label><br>
                    <textarea id="content" name="content" rows="5"><?php echo htmlspecialchars($log['content']); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label>成果物 (任意):</label>
                    <div class="artifact-inputs">
                        <input type="text" name="artifact_title" placeholder="成果物のタイトル" value="<?php echo htmlspecialchars($log['artifact_title']); ?>">
                        <input type="url" name="artifact_url" placeholder="https://example.com" value="<?php echo htmlspecialchars($log['artifact_url']); ?>">
                    </div>
                </div>

                <div class="form-actions">
                  <button type="submit" class="btn btn-primary">更新する</button>
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