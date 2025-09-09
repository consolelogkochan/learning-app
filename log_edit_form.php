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
$user_id = $_SESSION['user_id'];

// セッションからエラーと入力値を取得
$errors = $_SESSION['errors'] ?? [];
$old_input = $_SESSION['old_input'] ?? [];
unset($_SESSION['errors'], $_SESSION['old_input']);

// カテゴリ追加時のエラーを取得
$category_error = $_SESSION['category_error'] ?? '';
unset($_SESSION['category_error']);

// URLから編集対象のIDを取得
$log_id = $_GET['id'] ?? 0;
if (empty($log_id)) {
    handle_system_error('編集するログが指定されていません。');
}

try {
    // DBから元々のログデータを取得
    $sql = "SELECT * FROM learning_logs WHERE id = :id AND user_id = :user_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id' => $log_id, 'user_id' => $user_id]);
    $log = $stmt->fetch();

    if (!$log) {
        handle_system_error('指定されたログを編集する権限がありません。');
    }

    // ログに紐づく詳細（カテゴリと時間）を取得
    $sql_details = "SELECT * FROM learning_details WHERE log_id = :log_id";
    $stmt_details = $pdo->prepare($sql_details);
    $stmt_details->bindValue(':log_id', $log_id, PDO::PARAM_INT);
    $stmt_details->execute();
    $details = $stmt_details->fetchAll();
    
    // カテゴリ一覧を取得
    $categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();

} catch (PDOException $e) {
    handle_system_error('データの読み込みに失敗しました。', [], $e->getMessage());
}

// エラーで戻ってきた場合、表示する学習項目を$old_inputに差し替える
if (!empty($old_input)) {
    $display_details = [];
    foreach ($old_input['category_ids'] as $index => $cat_id) {
        $display_details[] = [
            'category_id' => $cat_id,
            'duration_minutes' => $old_input['durations'][$index] ?? ''
        ];
    }
} else {
    $display_details = $details;
}
// ▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>学習ログの編集</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
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
        <!-- ▼▼▼▼▼ カテゴリ管理セクションを追加 ▼▼▼▼▼ -->
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
        <!-- ▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲ -->

        <section class="log-form">
            <h2>学習ログを編集する</h2>
            <form action="log_update_process.php" method="POST" id="log-form">
                <input type="hidden" name="log_id" value="<?php echo htmlspecialchars($log_id); ?>">
                
                <div class="form-group">
                    <label for="learning_date">学習日:</label>
                    <!-- ▼▼▼ value属性を修正 ▼▼▼ -->
                    <input type="date" id="learning_date" name="learning_date" value="<?php echo htmlspecialchars($old_input['learning_date'] ?? $log['learning_date']); ?>" required>
                </div>

                <!-- ▼▼▼▼▼ 動的な学習項目部分を全面的に修正 ▼▼▼▼▼ -->
                <div id="learning-items-container">
                    <?php foreach ($display_details as $index => $detail): ?>
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
                                <span>
                                    <?php
                                    $selected_cat_name = 'カテゴリを選択';
                                    foreach ($categories as $cat) {
                                        if ($cat['id'] == $detail['category_id']) {
                                            $selected_cat_name = htmlspecialchars($cat['name']);
                                            break;
                                        }
                                    }
                                    echo $selected_cat_name;
                                    ?>
                                </span>
                                <i class="fa-solid fa-chevron-down"></i>
                            </div>
                            <div class="custom-options">
                                <?php foreach ($categories as $category): ?>
                                    <div class="custom-option" data-value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="duration-group">
                            <input type="number" name="durations[]" placeholder="学習時間 (分)" min="1" value="<?php echo htmlspecialchars($detail['duration_minutes']); ?>" required>
                        </div>
                        <?php if ($index > 0): ?>
                            <button type="button" class="remove-item-btn">削除</button>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <!-- ▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲ -->

                <button type="button" id="add-item-btn" class="btn-add-item">＋ 学習項目を追加</button>

                <div class="form-group">
                    <label for="content">学習内容の詳細・メモ:</label><br>
                    <!-- ▼▼▼ textarea内を修正 ▼▼▼ -->
                    <textarea id="content" name="content" rows="5"><?php echo htmlspecialchars($old_input['content'] ?? $log['content']); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label>成果物 (任意):</label>
                    <div class="artifact-inputs">
                        <!-- ▼▼▼ value属性を修正 ▼▼▼ -->
                        <input type="text" name="artifact_title" placeholder="成果物のタイトル" value="<?php echo htmlspecialchars($old_input['artifact_title'] ?? $log['artifact_title']); ?>">
                        <input type="url" name="artifact_url" placeholder="https://example.com" value="<?php echo htmlspecialchars($old_input['artifact_url'] ?? $log['artifact_url']); ?>">
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

    <!-- ▼▼▼ トースト通知のスクリプトを追加 ▼▼▼ -->
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <?php if (!empty($errors)): ?>
    <script>
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
        Toastify({ text: "<?php echo htmlspecialchars($error_message, ENT_QUOTES); ?>", duration: 0, close: true, gravity: "top", position: "center", backgroundColor: "#dc3545", stopOnFocus: true }).showToast();
        <?php endforeach; ?>
    </script>
    <?php endif; ?>
</body>
</html>