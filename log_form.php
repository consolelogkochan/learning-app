<?php
session_start();
require_once 'db_connect.php';
require_once 'utils.php';

// セッションからエラーと入力値を取得
$errors = $_SESSION['errors'] ?? [];
$old_input = $_SESSION['old_input'] ?? [];
unset($_SESSION['errors'], $_SESSION['old_input']);

// カテゴリ追加時のエラーを取得
$category_error = $_SESSION['category_error'] ?? '';
unset($_SESSION['category_error']);

// カテゴリ一覧を取得
try {
    $sql_categories = "SELECT * FROM categories ORDER BY name ASC";
    $stmt_categories = $pdo->query($sql_categories);
    $categories = $stmt_categories->fetchAll();
} catch (PDOException $e) {
    handle_system_error('カテゴリの読み込みに失敗しました。', [], $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>学習ログの投稿</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
</head>
<body>
<div class="page-container">
    <header class="form-header">
        <div class="header-content">
            <h1>学習ログ</h1>
            <a href="dashboard.php" class="save-state"><i class="fa-solid fa-arrow-left"></i> ダッシュボードに戻る</a>
        </div>
    </header>
    <main class="main-content">
    <div class="form-card">
        <section class="category-management">
            <h2>カテゴリ管理</h2>

            <?php if ($category_error): ?>
                <p class="error-message"><?php echo htmlspecialchars($category_error); ?></p>
            <?php endif; ?>
            
            <form action="add_category.php" method="POST" id="category-form">
                <input type="text" name="category_name" placeholder="新しいカテゴリ名" required>
                <button type="submit" class="btn btn-primary save-state">追加</button>
            </form>
        </section>
        <hr>
        <section class="log-form">
            <h2>学習ログを投稿する</h2>
            <form action="add_log_process.php" method="POST" id="log-form">
                <div class="form-group">
                    <label for="learning_date">学習日:</label>
                    <input type="date" id="learning_date" name="learning_date" value="<?php echo htmlspecialchars($old_input['learning_date'] ?? date('Y-m-d')); ?>" required>
                </div>

                <div id="learning-items-container">
                    <?php
                    $item_count = isset($old_input['category_ids']) ? count($old_input['category_ids']) : 1;
                    for ($i = 0; $i < $item_count; $i++):
                        $selected_cat_id = $old_input['category_ids'][$i] ?? '';
                        $duration_value = $old_input['durations'][$i] ?? '';
                        $selected_cat_name = 'カテゴリを選択';
                        foreach ($categories as $cat) {
                            if ($cat['id'] == $selected_cat_id) {
                                $selected_cat_name = htmlspecialchars($cat['name']);
                                break;
                            }
                        }
                    ?>
                    <div class="learning-item">
                        <div class="custom-select-wrapper">
                            <select name="category_ids[]" required style="display: none;">
                                <option value="">カテゴリを選択</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" <?php if ($category['id'] == $selected_cat_id) echo 'selected'; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="custom-select-trigger">
                                <span><?php echo $selected_cat_name; ?></span>
                                <i class="fa-solid fa-chevron-down"></i>
                            </div>
                            <div class="custom-options">
                                <?php foreach ($categories as $category): ?>
                                    <div class="custom-option" data-value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="duration-group">
                            <input type="number" name="durations[]" placeholder="学習時間 (分)" min="1" required value="<?php echo htmlspecialchars($duration_value); ?>">
                        </div>
                        <?php if ($i > 0): ?>
                            <button type="button" class="remove-item-btn">削除</button>
                        <?php endif; ?>
                    </div>
                    <?php endfor; ?>
                </div>
                
                <button type="button" id="add-item-btn" class="btn-add-item">＋ 学習項目を追加</button>
                <div class="form-group">
                    <label for="content">学習内容の詳細・メモ:</label><br>
                    <textarea id="content" name="content" rows="5"><?php echo htmlspecialchars($old_input['content'] ?? ''); ?></textarea>
                </div>
                <div class="form-group">
                    <label>成果物 (任意):</label>
                    <div class="artifact-inputs">
                        <input type="text" name="artifact_title" placeholder="成果物のタイトル" value="<?php echo htmlspecialchars($old_input['artifact_title'] ?? ''); ?>">
                        <input type="url" name="artifact_url" placeholder="https://example.com" value="<?php echo htmlspecialchars($old_input['artifact_url'] ?? ''); ?>">
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
    
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <?php if (!empty($errors)): ?>
    <script>
        // エラーをループして、一つずつトーストとして表示
        <?php
        $flat_errors = [];
        foreach ($errors as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $sub_error) { $flat_errors[] = $sub_error; }
            } else {
                $flat_errors[] = $value;
            }
        }
        foreach (array_unique($flat_errors) as $error_message):
        ?>
        Toastify({
            text: "<?php echo htmlspecialchars($error_message, ENT_QUOTES); ?>",
            duration: 0,
            close: true,
            gravity: "top",
            position: "center",
            backgroundColor: "#dc3545",
            stopOnFocus: true
        }).showToast();
        <?php endforeach; ?>
    </script>
    <?php endif; ?>
    </body>
</html>