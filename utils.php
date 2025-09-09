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

// ▼▼▼▼▼ この関数を追記 ▼▼▼▼▼

/**
 * 重大なシステムエラーを処理し、統一エラーページにリダイレクトする。
 * 同時に、ユーザーの入力値をセッションに保存する試みを行う。
 *
 * @param string $user_message ユーザーに見せるエラーメッセージ
 * @param array $input_data ユーザーの入力データ (通常は $_POST)
 * @param string $log_message ログに残す技術的なエラーメッセージ (任意)
 */
function handle_system_error($user_message, $input_data = [], $log_message = '') {
    // エラーログに記録
    if ($log_message) {
        error_log($log_message);
    }

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // ユーザーの入力値をセッションに保存
    // パスワードなど、保存すべきでないものは除外する
    unset($input_data['password'], $input_data['password_confirm']);
    $_SESSION['old_input'] = $input_data;
    
    // ユーザー向けのエラーメッセージをセッションに保存
    $_SESSION['system_error'] = $user_message;

    // 統一エラーページにリダイレクト
    // header()関数の前に何も出力されていないことを保証する
    if (!headers_sent()) {
        header('Location: error.php');
        exit;
    } else {
        // ヘッダーが既に送信されている場合のフォールバック
        echo "致命的なエラーが発生しました。システム管理者に連絡してください。";
        exit;
    }
}