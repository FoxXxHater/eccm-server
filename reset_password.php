<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/i18n.php';
startSecureSession();
detectLanguage();
$t = function($k){ return __t($k); };
$appName = getAppName();

$token   = $_GET['token'] ?? '';
$message = '';
$success = false;
$valid   = false;

if ($token !== '') {
    $db = getDB();
    $stmt = $db->prepare('SELECT * FROM password_resets WHERE token = :token AND used = 0 AND expires_at > NOW() LIMIT 1');
    $stmt->execute(['token' => $token]);
    $valid = (bool)$stmt->fetch();
}
if (!$valid && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $message = $t('reset_invalid_token');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token    = $_POST['token'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['password_confirm'] ?? '';
    $csrf     = $_POST['csrf_token'] ?? '';
    if (!verifyCsrf($csrf)) {
        $message = $t('login_session_error');
    } elseif (strlen($password) < 6) {
        $message = $t('reset_too_short');
        $valid = true;
    } elseif ($password !== $confirm) {
        $message = $t('reset_mismatch');
        $valid = true;
    } else {
        if (resetPasswordWithToken($token, $password)) {
            $success = true;
            $message = $t('reset_success');
        } else {
            $message = $t('reset_failed');
        }
    }
}
$csrf = csrfToken();
?>
<!DOCTYPE html>
<html lang="<?=($GLOBALS['ECCM_LANG']??'de')?>">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?=htmlspecialchars($appName)?> – <?=$t('reset_title')?></title>
<link rel="icon" type="image/png" sizes="32x32" href="https://img.icons8.com/stickers/32/ethernet-on.png">
<style>
:root{--bg:#0f1115;--panel:#171a21;--ink:#e8eaf1;--line:#262a33;--accent:#3b82f6;--danger:#ef4444;--success:#22c55e}
*,*::before,*::after{box-sizing:border-box}
html,body{height:100%;margin:0;font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif}
body{background:var(--bg);color:var(--ink);display:flex;align-items:center;justify-content:center}
.box{background:var(--panel);border:1px solid var(--line);border-radius:16px;padding:32px;width:100%;max-width:420px}
.box h1{font-size:20px;margin:0 0 6px;text-align:center}
.box .subtitle{text-align:center;color:#a6adbb;font-size:13px;margin-bottom:24px}
label{display:block;font-size:13px;color:#a6adbb;margin:12px 0 4px}
input[type=password]{width:100%;background:#0f131b;color:var(--ink);border:1px solid var(--line);border-radius:8px;padding:10px 12px;font-size:14px;outline:none}
input:focus{border-color:var(--accent)}
.btn{display:block;width:100%;padding:12px;border:none;border-radius:8px;font-size:15px;font-weight:600;cursor:pointer;margin-top:16px;background:var(--accent);color:#fff}.btn:hover{opacity:.9}
.msg{border-radius:8px;padding:10px;font-size:13px;margin-bottom:12px;text-align:center}
.msg.error{background:rgba(239,68,68,.15);border:1px solid var(--danger);color:#fca5a5}
.msg.ok{background:rgba(34,197,94,.15);border:1px solid var(--success);color:#86efac}
.links{text-align:center;margin-top:16px;font-size:13px}.links a{color:var(--accent);text-decoration:none}
</style></head><body>
<div class="box">
    <h1>🔌 <?=htmlspecialchars($appName)?></h1>
    <div class="subtitle"><?=$t('reset_title')?></div>
    <?php if ($message): ?><div class="msg <?=$success?'ok':'error'?>"><?=htmlspecialchars($message)?></div><?php endif;?>
    <?php if ($success): ?><div class="links"><a href="login.php"><?=$t('reset_to_login')?></a></div><?php endif;?>
    <?php if ($valid && !$success): ?>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?=htmlspecialchars($csrf)?>">
        <input type="hidden" name="token" value="<?=htmlspecialchars($token)?>">
        <label for="password"><?=$t('reset_new_pw')?></label>
        <input type="password" id="password" name="password" required minlength="6" autofocus>
        <label for="password_confirm"><?=$t('reset_confirm_pw')?></label>
        <input type="password" id="password_confirm" name="password_confirm" required minlength="6">
        <button type="submit" class="btn"><?=$t('reset_button')?></button>
    </form>
    <?php endif;?>
    <div class="links"><a href="login.php"><?=$t('forgot_back')?></a></div>
</div></body></html>
