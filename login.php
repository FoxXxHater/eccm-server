<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/i18n.php';
try { startSecureSession(); } catch (Exception $e) {}
detectLanguage();
$t = function($k){ return __t($k); };
$appName = getAppName();

if (isLoggedIn()) { header('Location: index.php'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login    = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';
    $csrf     = $_POST['csrf_token'] ?? '';

    if (!verifyCsrf($csrf)) {
        $error = $t('login_session_error');
    } elseif ($login === '' || $password === '') {
        $error = $t('login_fields_required');
    } else {
        try {
            $user = authenticate($login, $password);
            if ($user) { loginUser($user); header('Location: index.php'); exit; }
            $error = $t('login_invalid');
        } catch (PDOException $e) { $error = $t('error') . ': ' . $e->getMessage();
        } catch (Exception $e) { $error = $t('error') . ': ' . $e->getMessage(); }
    }
}
$csrf = csrfToken();
?>
<!DOCTYPE html>
<html lang="<?=($GLOBALS['ECCM_LANG']??'de')?>">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?=htmlspecialchars($appName)?> – <?=$t('login')?></title>
<link rel="icon" type="image/png" sizes="32x32" href="https://img.icons8.com/stickers/32/ethernet-on.png">
<style>
:root{--bg:#0f1115;--panel:#171a21;--ink:#e8eaf1;--line:#262a33;--accent:#3b82f6;--danger:#ef4444}
*,*::before,*::after{box-sizing:border-box}
html,body{height:100%;margin:0;font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif}
body{background:var(--bg);color:var(--ink);display:flex;align-items:center;justify-content:center}
.login-box{background:var(--panel);border:1px solid var(--line);border-radius:16px;padding:32px;width:100%;max-width:400px}
.login-box h1{font-size:20px;margin:0 0 6px;text-align:center}
.login-box .subtitle{text-align:center;color:#a6adbb;font-size:13px;margin-bottom:24px}
label{display:block;font-size:13px;color:#a6adbb;margin:12px 0 4px}
input[type=text],input[type=password]{width:100%;background:#0f131b;color:var(--ink);border:1px solid var(--line);border-radius:8px;padding:10px 12px;font-size:14px;outline:none}
input:focus{border-color:var(--accent)}
.btn{display:block;width:100%;padding:12px;border:none;border-radius:8px;font-size:15px;font-weight:600;cursor:pointer;margin-top:16px;background:var(--accent);color:#fff}.btn:hover{opacity:.9}
.error{background:rgba(239,68,68,.15);border:1px solid var(--danger);color:#fca5a5;border-radius:8px;padding:10px;font-size:13px;margin-bottom:12px;text-align:center}
.links{text-align:center;margin-top:16px;font-size:13px}.links a{color:var(--accent);text-decoration:none}.links a:hover{text-decoration:underline}
</style></head><body>
<div class="login-box">
    <h1>🔌 <?=htmlspecialchars($appName)?></h1>
    <div class="subtitle"><?=$t('login_subtitle')?></div>
    <?php if ($error): ?><div class="error"><?=htmlspecialchars($error)?></div><?php endif;?>
    <form method="post" autocomplete="on">
        <input type="hidden" name="csrf_token" value="<?=htmlspecialchars($csrf)?>">
        <label for="login"><?=$t('login_user_or_email')?></label>
        <input type="text" id="login" name="login" required autofocus value="<?=htmlspecialchars($_POST['login']??'')?>">
        <label for="password"><?=$t('password')?></label>
        <input type="password" id="password" name="password" required>
        <button type="submit" class="btn"><?=$t('login_button')?></button>
    </form>
    <div class="links"><a href="forgot_password.php"><?=$t('login_forgot')?></a></div>
</div></body></html>
