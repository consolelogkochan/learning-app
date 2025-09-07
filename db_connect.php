<?php

// ▼▼▼▼▼ ここから修正・追加 ▼▼▼▼▼

// Composerのオートローダーと、.envファイルを読み込むライブラリを読み込む
require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// .envファイルからデータベース接続情報を取得
$db_host = $_ENV['DB_HOST'];
$db_name = $_ENV['DB_NAME'];
$db_user = $_ENV['DB_USER'];
$db_pass = $_ENV['DB_PASS'];

// ▲▲▲▲▲ ここまで修正・追加 ▲▲▲▲▲

// データソース名 (DSN) の設定
$dsn = "mysql:host={$db_host};dbname={$db_name};charset=utf8mb4";

try {
    // PDO (PHP Data Objects) インスタンスの作成
    $pdo = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,      // エラー発生時に例外をスローする
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // 結果を連想配列形式で取得する
        PDO::ATTR_EMULATE_PREPARES => false,              // SQLインジェクション対策
    ]);
} catch (PDOException $e) {
    // 接続エラー時は、エラー処理関数を読み込んで実行
    require_once __DIR__ . '/utils.php';
    show_error_and_exit('データベースに接続できません。設定を確認してください。', $e->getMessage());
}