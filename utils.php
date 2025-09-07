<?php
// エラーページを表示して処理を終了する関数
function show_error_and_exit($user_message, $system_message = '') {
    // 開発者向けに、詳細なエラーをサーバーのログに記録する（推奨）
    if (!empty($system_message)) {
        error_log($system_message);
    }

    // ユーザーには、安全で分かりやすいメッセージのみを表示する
    $error_message = $user_message;
    require __DIR__ . '/error.php';
    exit();
}