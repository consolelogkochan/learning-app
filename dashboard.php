<?php
// セッションを開始
session_start();
require_once 'db_connect.php';

// セッションからフラッシュメッセージを取得
$flash_message = $_SESSION['flash_message'] ?? null;
// 一度表示したら不要なので、セッションから削除する
if ($flash_message) {
    unset($_SESSION['flash_message']);
}

// ログインしているかチェック
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$nickname = $_SESSION['nickname'];
$user_id = $_SESSION['user_id'];

// ▼▼▼ ログインユーザーのプロフィール画像を取得するクエリを追加 ▼▼▼
try {
    $stmt_user = $pdo->prepare("SELECT profile_image FROM users WHERE id = :id");
    $stmt_user->bindValue(':id', $user_id, PDO::PARAM_INT);
    $stmt_user->execute();
    $current_user_image = $stmt_user->fetchColumn();
} catch (PDOException $e) {
    // 画像取得に失敗してもページ全体を停止させず、画像なしとして処理を続行
    $current_user_image = null;
    error_log('Failed to fetch user profile image           : ' . $e->getMessage());
}
// ▲▲▲ ここまで追加 ▲▲▲

// --- ここから追加：ログ取得処理 ---
try {
    // カテゴリ一覧を取得
    $sql_categories = "SELECT * FROM categories ORDER BY name ASC";
    $stmt_categories = $pdo->query($sql_categories);
    $categories = $stmt_categories->fetchAll();

    // --- 絞り込みと検索、ページネーションの最終確定ロジック ---
    $selected_category_id = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;
    $search_keyword = trim($_GET['search'] ?? '');
    
    // SQLの骨格を作成
    $sql_base = "FROM learning_logs ll JOIN users u ON ll.user_id = u.id";
    $join_clause = '';
    $where_conditions = [];
    $params = []; // パラメータは常にこの配列で一元管理

    if ($selected_category_id > 0) {
        $join_clause = " JOIN log_categories lc ON ll.id = lc.log_id";
        $where_conditions[] = "lc.category_id = ?"; // 名前付きプレースホルダーをやめる
        $params[] = $selected_category_id;
    }
    if (!empty($search_keyword)) {
        $where_conditions[] = "(ll.content LIKE ? OR u.nickname LIKE ?)";
        $params[] = '%' . $search_keyword . '%';
        $params[] = '%' . $search_keyword . '%';
    }
    $where_clause = !empty($where_conditions) ? " WHERE " . implode(" AND ", $where_conditions) : '';

    // --- 総件数を取得 ---
    $sql_total = "SELECT COUNT(DISTINCT ll.id) " . $sql_base . $join_clause . $where_clause;
    $stmt_total = $pdo->prepare($sql_total);
    $stmt_total->execute($params);
    $total_logs = $stmt_total->fetchColumn();

    // --- ページネーションの計算 ---
    $logs_per_page = 10;
    $total_pages = ceil($total_logs / $logs_per_page);
    $current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    if ($current_page < 1) $current_page = 1;
    $offset = ($current_page - 1) * $logs_per_page;

    // --- ログ本体を取得 ---
    $sql_logs = "SELECT DISTINCT ll.*, u.nickname, u.profile_image " . $sql_base . $join_clause . $where_clause . " ORDER BY ll.learning_date DESC, ll.created_at DESC LIMIT ? OFFSET ?";
    
    // LIMITとOFFSET用のパラメータを追加
    $params[] = $logs_per_page;
    $params[] = $offset;

    $stmt_logs = $pdo->prepare($sql_logs);
    $stmt_logs->execute($params);
    $logs = $stmt_logs->fetchAll();
    
    // --- 詳細取得の準備 ---
    $sql_details = "SELECT ld.duration_minutes, c.name AS category_name 
                    FROM learning_details ld JOIN categories c ON ld.category_id = c.id 
                    WHERE ld.log_id = :log_id";
    $stmt_details = $pdo->prepare($sql_details);

    // --- ▼▼▼ ここからグラフ用データ取得処理を追加 ▼▼▼ ---

    // ログインしているユーザーの、直近7日間の日毎の合計学習時間を取得
    $sql_chart = "
        SELECT 
            DATE(ll.learning_date) as date,
            SUM(ld.duration_minutes) as total_minutes
        FROM learning_logs ll
        JOIN learning_details ld ON ll.id = ld.log_id
        WHERE 
            ll.user_id = :user_id AND 
            ll.learning_date BETWEEN CURDATE() - INTERVAL 6 DAY AND CURDATE()
        GROUP BY DATE(ll.learning_date)
        ORDER BY DATE(ll.learning_date) ASC
    ";
    $stmt_chart = $pdo->prepare($sql_chart);
    $stmt_chart->bindValue(':user_id', $user_id, PDO::PARAM_INT);
    $stmt_chart->execute();
    $chart_data_raw = $stmt_chart->fetchAll();

    // Chart.jsが使いやすいようにデータを整形
    $chart_labels = [];
    $chart_data = [];
    
    // まずは直近7日間の日付の配列を作成
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-{$i} day"));
        $chart_labels[] = date('n/j', strtotime($date)); // '8/28' のような形式
        $chart_data[$date] = 0; // 学習時間を0で初期化
    }

    // データベースから取得した学習時間を対応する日付にセット
    foreach ($chart_data_raw as $row) {
        $chart_data[$row['date']] = (int)$row['total_minutes'];
    }

    // 最終的なデータ配列を作成 (連想配列からただの配列へ)
    $chart_data = array_values($chart_data);

    // --- ▲▲▲ ここまで追加 ▲▲▲ ---

    // --- ▼▼▼ ここから円グラフ用データ取得処理を追加 ▼▼▼ ---

    // ログインしているユーザーの、カテゴリ毎の合計学習時間を取得
    $sql_pie_chart = "
        SELECT 
            c.name AS category_name,
            SUM(ld.duration_minutes) as total_minutes
        FROM learning_details ld
        JOIN categories c ON ld.category_id = c.id
        JOIN learning_logs ll ON ld.log_id = ll.id
        WHERE ll.user_id = :user_id
        GROUP BY c.name
        HAVING SUM(ld.duration_minutes) > 0 -- 学習時間があるカテゴリのみ
        ORDER BY total_minutes DESC
    ";
    $stmt_pie_chart = $pdo->prepare($sql_pie_chart);
    $stmt_pie_chart->bindValue(':user_id', $user_id, PDO::PARAM_INT);
    $stmt_pie_chart->execute();
    $bar_chart_raw = $stmt_pie_chart->fetchAll();

    // Chart.jsが使いやすいようにデータを整形
    $bar_chart_labels = [];
    $bar_chart_data = [];
    foreach ($bar_chart_raw as $row) {
        $bar_chart_labels[] = $row['category_name'];
        $bar_chart_data[] = (int)$row['total_minutes'];
    }

    // --- ▲▲▲ ここまで追加 ▲▲▲ ---

    // --- ▼▼▼ ここからサマリー用データ取得処理を追加 ▼▼▼ ---

    // ログインユーザーの合計学習時間を取得
    $sql_summary = "
    SELECT
        SUM(CASE WHEN ll.learning_date >= CURDATE() - INTERVAL 6 DAY THEN ld.duration_minutes ELSE 0 END) as total_7_days,
        SUM(CASE WHEN ll.learning_date >= CURDATE() - INTERVAL 29 DAY THEN ld.duration_minutes ELSE 0 END) as total_30_days,
        SUM(ld.duration_minutes) as total_all_time
    FROM learning_details ld
    JOIN learning_logs ll ON ld.log_id = ll.id
    WHERE ll.user_id = :user_id
    ";
    $stmt_summary = $pdo->prepare($sql_summary);
    $stmt_summary->bindValue(':user_id', $user_id, PDO::PARAM_INT);
    $stmt_summary->execute();
    $summary_data = $stmt_summary->fetch();

    // 分を「〇時間〇分」の形式に変換するヘルパー関数
    function format_minutes($minutes) {
    if ($minutes < 1) return '0分';
    $h = floor($minutes / 60);
    $m = $minutes % 60;
    return "{$h}時間 {$m}分";
    }

// --- ▲▲▲ ここまで追加 ▲▲▲ ---

} catch (PDOException $e) {
    require_once 'utils.php';
    handle_system_error('データの取得中にエラーが発生しました。時間をおいて再度お試しください。', [], $e->getMessage());
}
// --- ここまで追加 ---

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ダッシュボード</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
</head>
<body>
<div class="page-container">
<header class="main-header">
    <div class="header-center">
        <h1>ホーム</h1>
        <p>ようこそ、<?php echo htmlspecialchars($nickname, ENT_QUOTES, 'UTF-8'); ?>さん！</p>
    </div>
    <div class="header-right">
        <div class="profile-menu-container">
            <button type="button" class="profile-menu-toggle">
                <?php if (!empty($current_user_image)): ?>
                    <img src="<?php echo htmlspecialchars($current_user_image); ?>" alt="プロフィール画像" class="profile-avatar">
                <?php else: ?>
                    <!-- 画像がない場合のプレースホルダーアイコン -->
                    <i class="fa-solid fa-user-circle profile-avatar-placeholder"></i>
                <?php endif; ?>
            </button>
            <div class="profile-menu">
                <a href="profile.php?id=<?php echo $user_id; ?>" class="menu-item">
                    <i class="fa-solid fa-user"></i> マイプロフィール
                </a>
                <a href="logout.php" class="menu-item">
                    <i class="fa-solid fa-right-from-bracket"></i> ログアウト
                </a>
            </div>
        </div>
    </div>
</header>

    <main class="main-content">
        <section class="stats-section">
            <h2>学習サマリー</h2>
            <div class="summary-card-wrapper">
                <div class="summary-card">
                    <div class="summary-card-icon" style="color: #007bff;">
                        <i class="fa-solid fa-calendar-week fa-2x"></i>
                    </div>
                    <div class="summary-card-content">
                        <span class="summary-card-label">直近7日間</span>
                        <span class="summary-card-spacer"></span>
                        <span class="summary-card-value"><?php echo format_minutes($summary_data['total_7_days'] ?? 0); ?></span>
                    </div>
                </div>

                <div class="summary-card">
                    <div class="summary-card-icon" style="color: #17a2b8;">
                        <i class="fa-solid fa-calendar-days fa-2x"></i>
                    </div>
                    <div class="summary-card-content">
                        <span class="summary-card-label">直近30日間</span>
                        <span class="summary-card-spacer"></span>
                        <span class="summary-card-value"><?php echo format_minutes($summary_data['total_30_days'] ?? 0); ?></span>
                    </div>
                </div>

                <div class="summary-card">
                    <div class="summary-card-icon" style="color: #28a745;">
                        <i class="fa-solid fa-trophy fa-2x"></i>
                    </div>
                    <div class="summary-card-content">
                        <span class="summary-card-label">総合計</span>
                        <span class="summary-card-spacer"></span>
                        <span class="summary-card-value"><?php echo format_minutes($summary_data['total_all_time'] ?? 0); ?></span>
                    </div>
                </div>
            </div>
            <div class="charts-wrapper">
                <div class="chart-container">
                    <div class="chart-canvas-container">
                        <canvas 
                            id="learningTimeChart" 
                            data-labels='<?php echo json_encode($chart_labels); ?>' 
                            data-data='<?php echo json_encode($chart_data); ?>'
                        ></canvas>
                    </div>
                </div>
                <div class="chart-container">
                    <h3>カテゴリ別学習時間の割合</h3>
                    <div class="chart-canvas-container">
                        <canvas 
                            id="categoryBarChart"
                            data-labels='<?php echo json_encode($bar_chart_labels); ?>'
                            data-data='<?php echo json_encode($bar_chart_data); ?>'
                        ></canvas>
                    </div>
                </div>
            </div>
        </section>

        <hr>

        <a href="log_form.php" class="btn-add-log-floating">
          <i class="fa-solid fa-plus"></i>
        </a>
                

        <section class="log-list">
            <div class="list-header">
                <h2>学習ログ一覧</h2>
                <?php if ($total_logs > 0): ?>
                    <span class="log-count">
                        <?php
                            // 表示件数の計算
                            $start_item = ($current_page - 1) * $logs_per_page + 1;
                            $end_item = $start_item + count($logs) - 1;
                        ?>
                        全 <?php echo $total_logs; ?> 件中 <?php echo $start_item; ?> - <?php echo $end_item; ?> 件表示
                    </span>
                <?php endif; ?>
            </div>

            <div class="category-filter">
                <div class="filter-controls">
                    <form action="dashboard.php" method="GET" class="filter-form">
                        <div class="filter-group">
                            <label for="category_filter"><i class="fa-solid fa-filter"></i> カテゴリ:</label>
                            <div class="custom-select-wrapper">
                                <select name="category_id" id="category_filter" style="display: none;">
                                    <option value="">すべて表示</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>" <?php if ($selected_category_id == $category['id']) echo 'selected'; ?>>
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="custom-select-trigger">
                                    <span>
                                        <?php
                                            $selected_category_name = 'すべて表示';
                                            if ($selected_category_id > 0) {
                                                foreach ($categories as $category) {
                                                    if ($category['id'] == $selected_category_id) {
                                                        $selected_category_name = htmlspecialchars($category['name']);
                                                        break;
                                                    }
                                                }
                                            }
                                            echo $selected_category_name;
                                        ?>
                                    </span>
                                    <i class="fa-solid fa-chevron-down"></i>
                                </div>
                                <div class="custom-options">
                                    <div class="custom-option" data-value="">すべて表示</div>
                                    <?php foreach ($categories as $category): ?>
                                        <div class="custom-option" data-value="<?php echo $category['id']; ?>">
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        <div class="filter-group">
                            <label for="search"><i class="fa-solid fa-magnifying-glass"></i> キーワード:</label>
                            <input type="search" name="search" id="search" placeholder="内容、投稿者名で検索..." value="<?php echo htmlspecialchars($search_keyword ?? '', ENT_QUOTES); ?>">
                        </div>
                        <div class="filter-actions">
                            <button type="submit" class="btn btn-primary">検索</button>
                            <a href="dashboard.php" class="btn-clear">クリア</a>
                        </div>
                    </form>
                </div>
            </div>

            <?php if (empty($logs)): ?>
                <p>まだ学習ログがありません。</p>
            <?php else: ?>
                <?php foreach ($logs as $log): ?>
                    <div class="log-card" id="log-<?php echo $log['id']; ?>">
                        <div class="log-header">
                            <div class="author-info">
                                <a href="profile.php?id=<?php echo $log['user_id']; ?>">
                                    <?php if (!empty($log['profile_image'])): ?>
                                        <img src="<?php echo htmlspecialchars($log['profile_image']); ?>" alt="" class="profile-avatar">
                                    <?php else: ?>
                                        <img src="placeholder.png" alt="" class="profile-avatar">
                                    <?php endif; ?>
                                </a>
                                <div>
                                    <strong>
                                        <a href="profile.php?id=<?php echo $log['user_id']; ?>">
                                            <?php echo htmlspecialchars($log['nickname'], ENT_QUOTES, 'UTF-8'); ?>
                                        </a>
                                    </strong>
                                    <small>学習日: <?php echo htmlspecialchars($log['learning_date'], ENT_QUOTES, 'UTF-8'); ?></small>
                                </div>
                            </div>
                            
                            <?php if ($log['user_id'] === $user_id): ?>
                            <div class="log-menu-container menu-container">
                                <button type="button" class="menu-toggle-btn">⋮</button>
                                <div class="log-menu">
                                    <a href="log_edit_form.php?id=<?php echo $log['id']; ?>" class="menu-item"><i class="fa-solid fa-pen-to-square"></i>編集</a>
                                    <form action="delete_log.php" method="POST" onsubmit="return confirm('本当にこのログを削除しますか？');">
                                        <input type="hidden" name="log_id" value="<?php echo $log['id']; ?>">
                                        <button type="submit" class="menu-item delete"><i class="fa-solid fa-trash-can"></i>削除</button>
                                    </form>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>

                        <div class="log-body">
                            <?php
                                // 各ログに紐づく詳細を取得して表示
                                $stmt_details->bindValue(':log_id', $log['id'], PDO::PARAM_INT);
                                $stmt_details->execute();
                                $details = $stmt_details->fetchAll();
                                $total_duration = 0;
                            ?>
                            <div class="log-categories">
                                <?php foreach ($details as $detail): ?>
                                    <span class="log-category-tag"><?php echo htmlspecialchars($detail['category_name'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    <?php $total_duration += $detail['duration_minutes']; ?>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="log-summary">
                                <div class="log-summary-item">
                                    <i class="fa-solid fa-clock"></i>
                                    <span>合計学習時間: <strong><?php echo $total_duration; ?> 分</strong></span>
                                </div>
                                <?php if (!empty($log['artifact_url'])): ?>
                                    <div class="log-summary-item">
                                        <i class="fa-solid fa-link"></i>
                                        <span>成果物: 
                                            <a href="<?php echo htmlspecialchars($log['artifact_url'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">
                                                <?php echo htmlspecialchars($log['artifact_title'], ENT_QUOTES, 'UTF-8'); ?>
                                            </a>
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="log-details-breakdown">
                                <ul>
                                    <?php foreach ($details as $detail): ?>
                                        <li>
                                        <div class="detail-category-wrapper">
                                            <span class="detail-category-name"><?php echo htmlspecialchars($detail['category_name']); ?></span>
                                        </div>
                                        <div class="detail-duration-wrapper">
                                            <i class="fa-solid fa-clock detail-time-icon"></i>
                                            <span class="detail-duration-value"><?php echo $detail['duration_minutes']; ?> 分</span>
                                        </div>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>

                            <?php if (!empty($log['content'])): ?>
                                <div class="log-content">
                                    <p><?php echo nl2br(htmlspecialchars($log['content'], ENT_QUOTES, 'UTF-8')); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="log-footer">
                            <?php
                                try{
                                    // コメント取得
                                    $sql_comments = "SELECT c.*, u.nickname, u.profile_image FROM comments c JOIN users u ON c.user_id = u.id WHERE c.log_id = :log_id ORDER BY c.created_at ASC";
                                    $stmt_comments = $pdo->prepare($sql_comments);
                                    $stmt_comments->bindValue(':log_id', $log['id'], PDO::PARAM_INT);
                                    $stmt_comments->execute();
                                    $comments = $stmt_comments->fetchAll();
                                    $comment_count = count($comments);
                                } catch (PDOException $e) {
                                    // エラーが発生したら、コメントは0件として扱い、エラーメッセージを表示
                                    $comments = [];
                                    $comment_count = 0;
                                    echo '<p class="error-message">コメントの読み込みに失敗しました。</p>';
                                }
                            ?>
                            
                            <?php if ($comment_count > 0): ?>
                                <div class="comment-list-wrapper">
                                    <?php foreach ($comments as $index => $comment): ?>
                                        <div class="comment" <?php if ($index >= 3) echo 'style="display: none;"'; ?>>
                                        <div class="comment-meta">
                                            <div class="comment-author-info"> <?php if (!empty($comment['profile_image'])): ?>
                                                <img src="<?php echo htmlspecialchars($comment['profile_image']); ?>" alt="" class="profile-avatar profile-avatar-comment">
                                            <?php endif; ?>
                                            <strong><a href="profile.php?id=<?php echo $comment['user_id']; ?>"><?php echo htmlspecialchars($comment['nickname'], ENT_QUOTES, 'UTF-8'); ?></a></strong>
                                            <small class="comment-timestamp"><?php echo date('Y年n月j日 H:i', strtotime($comment['created_at'])); ?></small>
                                            </div> <?php if ($comment['user_id'] === $user_id): ?>
                                            <div class="comment-menu-container menu-container">
                                                <button type="button" class="menu-toggle-btn">⋮</button>
                                                <div class="log-menu">
                                                <form action="delete_comment.php" method="POST" onsubmit="return confirm('本当にこのコメントを削除しますか？');">
                                                    <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                                                    <button type="submit" class="menu-item delete">
                                                    <i class="fa-solid fa-trash-can"></i> 削除
                                                    </button>
                                                </form>
                                                </div>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                            <p><?php echo nl2br(htmlspecialchars($comment['content'], ENT_QUOTES, 'UTF-8')); ?></p>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php if ($comment_count > 3): ?>
                                    <button type="button" class="toggle-comments-btn">もっと読む (残り<?php echo $comment_count - 3; ?>件)</button>
                                <?php endif; ?>
                            <?php endif; ?>

                            <form action="add_comment_process.php" method="POST" class="comment-form">
                                <input type="hidden" name="log_id" value="<?php echo $log['id']; ?>">
                                <textarea name="content" rows="2" placeholder="コメントを追加..." required></textarea>
                                <button type="submit">投稿</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
        
        <?php if ($total_pages > 1): ?>
            <?php
                // ページネーションのリンクに絞り込み条件を追加するための準備
                $pagination_params = '';
                if ($selected_category_id > 0) {
                    $pagination_params = '&category_id=' . $selected_category_id;
                }
                if (!empty($search_keyword)) {
                    $pagination_params .= '&search=' . urlencode($search_keyword);
                }
            ?>
            <nav class="pagination">
                <ul>
                    <?php if ($current_page > 1): ?>
                        <li><a href="?page=<?php echo $current_page - 1; ?><?php echo $pagination_params; ?>">前へ</a></li>
                    <?php endif; ?>

                    <?php 
                    // 表示するページ番号の範囲を決定
                    $range = 2; 
                    $start = max(1, $current_page - $range);
                    $end = min($total_pages, $current_page + $range);

                    if ($start > 1) {
                        echo '<li><a href="?page=1' . $pagination_params . '">1</a></li>';
                        if ($start > 2) {
                            echo '<li><span>...</span></li>';
                        }
                    }

                    for ($i = $start; $i <= $end; $i++): ?>
                        <?php if ($i == $current_page): ?>
                            <li class="active"><span><?php echo $i; ?></span></li>
                        <?php else: ?>
                            <li><a href="?page=<?php echo $i; ?><?php echo $pagination_params; ?>"><?php echo $i; ?></a></li>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php
                    if ($end < $total_pages) {
                        if ($end < $total_pages - 1) {
                            echo '<li><span>...</span></li>';
                        }
                        echo '<li><a href="?page=' . $total_pages . $pagination_params . '">' . $total_pages . '</a></li>';
                    }
                    ?>

                    <?php if ($current_page < $total_pages): ?>
                        <li><a href="?page=<?php echo $current_page + 1; ?><?php echo $pagination_params; ?>">次へ</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </main>
</div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        // PHPからカテゴリのリストをJavaScriptのグローバル変数に渡す
        const categories = <?php echo json_encode($categories); ?>;
        // PHPからグラフ用のデータをJavaScriptのグローバル変数に渡す
        const chartLabels = <?php echo json_encode($chart_labels); ?>;
        const chartData = <?php echo json_encode($chart_data); ?>;
    </script>
    
    <script src="js/main.js"></script>
    
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <?php if ($flash_message): ?>
    <script>
        // メッセージタイプに応じて背景色を決定
        const messageType = "<?php echo htmlspecialchars($flash_message['type'], ENT_QUOTES); ?>";
        const backgroundColor = messageType === 'success' ? '#28a745' : '#dc3545'; // 成功なら緑、エラーなら赤

        Toastify({
            text: "<?php echo htmlspecialchars($flash_message['message'], ENT_QUOTES); ?>",
            duration: 5000, // 5秒間表示 
            gravity: "top",
            position: "center",
            backgroundColor: backgroundColor,
            stopOnFocus: true
        }).showToast();
    </script>
    <?php endif; ?>

</body>
</html>