<?php
// PHPMailerクラスをインポート
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// ▼▼▼▼▼ ここから修正・追加 ▼▼▼▼▼

// Composerのオートローダーを読み込む
require_once __DIR__ . '/vendor/autoload.php';

// .envファイルを読み込む処理を追加
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// エラー処理関数を読み込む
require_once __DIR__ . '/utils.php';

// ▲▲▲▲▲ ここまで修正・追加 ▲▲▲▲▲


// メール送信を行う関数
function send_verification_email($to, $token) {
    $mail = new PHPMailer(true);

    try {
        // SMTPサーバー設定
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'ik.tenzan.096@gmail.com'; // ★★★ あなたのGmailアドレス ★★★
        $mail->Password = 'rama nthq lwff sviv';        // ★★★ あなたのアプリパスワード ★★★
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;

        // 文字エンコーディング設定
        $mail->CharSet = 'UTF-8';

        // 送信者と受信者の設定
        $mail->setFrom('ik.tenzan.096@gmail.com', '学習管理アプリ'); // 送信者
        $mail->addAddress($to); // 受信者

        // メールの内容
        $mail->isHTML(true);
        $mail->Subject = 'メールアドレス認証のお願い';

        // ▼▼▼▼▼ ここを修正 ▼▼▼▼▼
        // 確認用URLを.envの値を使って生成
        $base_url = $_ENV['APP_URL'];
        $verification_url = $base_url . "/verify.php?token=" . urlencode($token);
        // ▲▲▲▲▲ ここを修正 ▲▲▲▲▲


        $mail->Body = "
            <h2>アカウント登録ありがとうございます！</h2>
            <p>以下のリンクをクリックして、メールアドレスの認証を完了してください。</p>
            <p><a href='{$verification_url}'>認証する</a></p>
            <p>このリンクの有効期限は1時間です。</p>
        ";

        $mail->send();
        // 成功した場合は何も返さなくて良い（呼び出し元はエラーがないことで成功と判断する）

    } catch (Exception $e) {
        // ▼▼▼ メール送信失敗時に統一エラーページを表示 ▼▼▼
        show_error_and_exit('メールの送信に失敗しました。時間をおいて再度お試しください。', "Mailer Error: {$mail->ErrorInfo}");
    }
}

// ▼▼▼ この関数を追記 ▼▼▼
function send_password_reset_email($to, $token) {
    $mail = new PHPMailer(true);

    try {
        // (SMTPサーバー設定は、既存の関数と全く同じです)
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'ik.tenzan.096@gmail.com'; // ★★★ あなたのGmailアドレス ★★★
        $mail->Password = 'rama nthq lwff sviv';        // ★★★ あなたのアプリパスワード ★★★
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;
        $mail->CharSet = 'UTF-8';

        // 送信者と受信者の設定
        $mail->setFrom('ik.tenzan.096@gmail.com', '学習管理アプリ');
        $mail->addAddress($to);

        // メールの内容 (件名と本文を変更)
        $mail->isHTML(true);
        $mail->Subject = 'パスワードの再設定';

        // ▼▼▼▼▼ ここを修正 ▼▼▼▼▼
        // 再設定用URLを.envの値を使って生成
        $base_url = $_ENV['APP_URL'];
        $reset_url = $base_url . "/password_reset.php?token=" . urlencode($token);
        // ▲▲▲▲▲ ここを修正 ▲▲▲▲▲


        $mail->Body = "
            <h2>パスワードの再設定</h2>
            <p>パスワードを再設定するには、以下のリンクをクリックしてください。このリンクは15分間有効です。</p>
            <p><a href='{$reset_url}'>パスワードを再設定する</a></p>
        ";

        $mail->send();
        // 成功した場合は何も返さない

    } catch (Exception $e) {
        // ▼▼▼ メール送信失敗時に統一エラーページを表示 ▼▼▼
        show_error_and_exit('メールの送信に失敗しました。時間をおいて再度お試しください。', "Mailer Error: {$mail->ErrorInfo}");
    }
}

// ▼▼▼ この関数を丸ごと追記 ▼▼▼
/**
 * メールアドレス変更の認証メールを送信する
 * @param string $to 新しいメールアドレス
 * @param string $token 認証用トークン
 */

function send_email_change_verification($to, $token) {
    $mail = new PHPMailer(true);

    try {
        // SMTPサーバー設定 (既存の関数と全く同じ)
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'ik.tenzan.096@gmail.com'; // あなたのGmailアドレス
        $mail->Password = 'rama nthq lwff sviv';        // あなたのアプリパスワード
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;
        $mail->CharSet = 'UTF-8';

        // 送信者と受信者の設定
        $mail->setFrom('ik.tenzan.096@gmail.com', '学習管理アプリ');
        $mail->addAddress($to);

        // メールの内容
        $mail->isHTML(true);
        $mail->Subject = '【学習管理アプリ】メールアドレスの変更認証';

        // 認証用URLを生成
        // (注意: 飛び先は verify_email_change.php)
        $verification_url = $_ENV['APP_URL'] . "/verify_email_change.php?token=" . urlencode($token);

        $mail->Body = "
            <h2>メールアドレス変更の確認</h2>
            <p>このメールアドレスを新しい連絡先として登録するには、以下のリンクをクリックしてください。</p>
            <p>この操作に心当たりがない場合は、このメールを無視してください。</p>
            <p><a href='{$verification_url}'>メールアドレスを認証する</a></p>
            <p>このリンクの有効期限は1時間です。</p>
        ";

        $mail->send();

    } catch (Exception $e) {
        // エラー処理は既存の関数と同様
        show_error_and_exit('メールの送信に失敗しました。時間をおいて再度お試しください。', "Mailer Error: {$mail->ErrorInfo}");
    }
}