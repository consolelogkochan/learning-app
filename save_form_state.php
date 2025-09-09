<?php
session_start();

// 権限チェック
if (!isset($_SESSION['user_id'])) {
    // ログインしていない場合は何もせず終了
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}

// POSTリクエストからフォームデータを取得し、セッションに保存
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['old_input'] = $_POST;
    header('Content-Type: application/json');
    echo json_encode(['status' => 'success']);
} else {
    // POST以外のリクエストは無効
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
}