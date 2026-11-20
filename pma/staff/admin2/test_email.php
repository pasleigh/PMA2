<?php
// test_email.php
// Admin-only diagnostic page: sends one plain test email using the MAIL_* settings in
// config_main.php and shows the full SMTP conversation on screen.

if (session_status() === PHP_SESSION_NONE) session_start();

(@include_once('./set_dir_admin.php'))        or die('set_dir_admin.php missing');
(@include_once("$STAFF_DIR_VAL/config.php"))  or die('config.php missing');
(@include_once('../connect_pdo.php'))          or die('connect_pdo.php missing');
(@include_once('../setup_tables.php'))         or die('setup_tables.php missing');
(@include_once('../staff_functions.php'))      or die('staff_functions.php missing');

require_once __BASE_DIR__ . '/vendor/autoload.php';

(@include_once('./admin_functions.php'))       or die('admin_functions.php missing');

use PHPMailer\PHPMailer\PHPMailer;

$access_phrase = '';
if (!access_check_response($db, $this_staff_username, $this_staff_full_name, $staff_names, $access_phrase)) {
    die('System admin access is required for this page.');
}

$h = function ($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); };
$const = function ($name) { return defined($name) ? constant($name) : '(not defined)'; };

$default_to = GetStaffEmailFromID($db, $this_staff_id, $staff_names2);
$to = trim($_POST['to'] ?? $default_to);
$with_pdf = !empty($_POST['with_pdf']);
$log = '';
$result = '';

if (($_POST['op'] ?? '') === 'send' && $to !== '') {
    $mail = new PHPMailer(true);
    $mail->SMTPDebug = 3;   // commands, data and connection status
    $mail->Debugoutput = function ($str, $level) use (&$log) { $log .= rtrim($str, "\r\n") . "\n"; };
    try {
        if ($const('MAIL_TRANSPORT') === 'smtp') {
            $mail->isSMTP();
            $mail->Host = MAIL_SMTP_HOST;
            $mail->Port = MAIL_SMTP_PORT;
            $mail->Timeout = MAIL_SMTP_TIMEOUT;
            $mail->SMTPAuth = MAIL_SMTP_AUTH;
            if (MAIL_SMTP_AUTH) {
                $mail->Username = MAIL_SMTP_USER;
                $mail->Password = MAIL_SMTP_PASS;
            }
            if (MAIL_SMTP_SECURE == 'tls') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            } elseif (MAIL_SMTP_SECURE == 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } else {
                $mail->SMTPSecure = '';
                $mail->SMTPAutoTLS = false;
            }
        } elseif ($const('MAIL_TRANSPORT') === 'sendmail') {
            $mail->isSendmail();
        } else {
            $mail->isMail();
        }
        $mail->CharSet = PHPMailer::CHARSET_UTF8;
        $from = $const('MAIL_FROM_ADDRESS');
        $mail->setFrom($from !== '' && $from !== '(not defined)' ? $from : $to, 'PMA test');
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = 'PMA test email ' . date('Y-m-d H:i:s');
        $mail->Body = '<p>This is a test email from the Project Marking App (test_email.php).</p>';
        $mail->AltBody = 'This is a test email from the Project Marking App (test_email.php).';
        if ($with_pdf) {
            $mpdf = new \Mpdf\Mpdf(['tempDir' => PMA_TMP_DIR]);
            $mpdf->WriteHTML('<h1>PMA test attachment</h1>');
            $mail->addStringAttachment($mpdf->Output('', 'S'), 'pma-test.pdf', PHPMailer::ENCODING_BASE64, 'application/pdf');
        }
        $mail->send();
        $result = "Sent to $to. Check the inbox (and spam folder).";
    } catch (\Throwable $e) {
        $result = 'FAILED (' . get_class($e) . '): ' . $e->getMessage()
                . ($mail->ErrorInfo !== '' ? "\nPHPMailer ErrorInfo: " . $mail->ErrorInfo : '');
    }
}

$checks = [
    'MAIL_TRANSPORT'    => $const('MAIL_TRANSPORT'),
    'MAIL_SMTP_HOST'    => $const('MAIL_SMTP_HOST'),
    'MAIL_SMTP_PORT'    => $const('MAIL_SMTP_PORT'),
    'MAIL_SMTP_SECURE'  => $const('MAIL_SMTP_SECURE'),
    'MAIL_SMTP_AUTH'    => var_export($const('MAIL_SMTP_AUTH'), true),
    'MAIL_SMTP_USER'    => $const('MAIL_SMTP_USER') === '' ? '(EMPTY - check the mail entry in the credentials file)' : $const('MAIL_SMTP_USER'),
    'MAIL_SMTP_PASS'    => defined('MAIL_SMTP_PASS') ? (MAIL_SMTP_PASS === '' ? '(EMPTY - check the mail entry in the credentials file)' : 'set (' . strlen(MAIL_SMTP_PASS) . ' characters)') : '(not defined)',
    'MAIL_FROM_ADDRESS' => $const('MAIL_FROM_ADDRESS'),
    'MAIL_SMTP_DEBUG'   => $const('MAIL_SMTP_DEBUG'),
    'PMA_TMP_DIR'       => defined('PMA_TMP_DIR') ? PMA_TMP_DIR . (is_writable(PMA_TMP_DIR) ? ' (writable)' : ' (NOT writable)') : '(not defined)',
    'openssl extension' => extension_loaded('openssl') ? 'loaded' : 'NOT loaded (needed for TLS)',
    'PHP version'       => PHP_VERSION,
];
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Email test</title>
<style>
  body { font-family: system-ui, sans-serif; margin: 20px; }
  table { border-collapse: collapse; }
  td { border: 1px solid #ccc; padding: 4px 8px; }
  pre { background: #f4f4f4; padding: 10px; white-space: pre-wrap; word-break: break-all; }
  .ok { color: #070; } .bad { color: #b00; }
</style>
</head>
<body>
<h1>Email test</h1>
<p><?= $h($access_phrase) ?></p>

<h2>Settings</h2>
<table>
<?php foreach ($checks as $k => $v): ?>
  <tr><td><?= $h($k) ?></td><td><?= $h($v) ?></td></tr>
<?php endforeach; ?>
</table>

<h2>Send a test email</h2>
<form method="post">
  <input type="hidden" name="op" value="send">
  <?= csrf_token_field() ?>
  <label>To: <input type="email" name="to" value="<?= $h($to) ?>" size="40" required></label>
  <label><input type="checkbox" name="with_pdf" value="1" <?= $with_pdf ? 'checked' : '' ?>> attach a small PDF</label>
  <button type="submit">Send</button>
</form>

<?php if ($result !== ''): ?>
  <h2>Result</h2>
  <pre class="<?= strncmp($result, 'Sent', 4) === 0 ? 'ok' : 'bad' ?>"><?= $h($result) ?></pre>
  <h2>SMTP conversation</h2>
  <pre><?= $h($log !== '' ? $log : '(no SMTP output - transport is not smtp, or it failed before connecting)') ?></pre>
<?php endif; ?>
</body>
</html>
