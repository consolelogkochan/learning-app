<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>エラー</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body class="auth-page">
<div class="page-container">
    <main class="auth-container">
        <div class="auth-card" style="text-align: center;">
            <div class="error-icon">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <h1>エラーが発生しました</h1>
            <p class="success-text">
                <?php echo htmlspecialchars($error_message ?? '不明なエラーが発生しました。時間をおいて再度お試しください。'); ?>
            </p>
            <div class="form-links">
                <a href="dashboard.php" class="btn btn-secondary">戻る</a>
            </div>
        </div>
    </main>
</div>
</body>
</html>