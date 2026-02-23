<?php
require_once __DIR__ . '/includes/auth.php';
startSecureSession();

$message = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $csrf  = $_POST['csrf_token'] ?? '';

    if (!verifyCsrf($csrf)) {
        $message = 'Ungültige Sitzung.';
    } elseif ($email === '') {
        $message = 'Bitte gib deine E-Mail-Adresse ein.';
    } else {
        // Always show success to not reveal if email exists
        createPasswordResetToken($email);
        $success = true;
        $message = 'Falls ein Konto mit dieser E-Mail existiert, wurde ein Link zum Zurücksetzen gesendet.';
    }
}

$csrf = csrfToken();
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>ECCM – Passwort vergessen</title>
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
input[type=email]{width:100%;background:#0f131b;color:var(--ink);border:1px solid var(--line);border-radius:8px;padding:10px 12px;font-size:14px;outline:none}
input:focus{border-color:var(--accent)}
.btn{display:block;width:100%;padding:12px;border:none;border-radius:8px;font-size:15px;font-weight:600;cursor:pointer;margin-top:16px;background:var(--accent);color:#fff}
.btn:hover{opacity:.9}
.msg{border-radius:8px;padding:10px;font-size:13px;margin-bottom:12px;text-align:center}
.msg.error{background:rgba(239,68,68,.15);border:1px solid var(--danger);color:#fca5a5}
.msg.ok{background:rgba(34,197,94,.15);border:1px solid var(--success);color:#86efac}
.links{text-align:center;margin-top:16px;font-size:13px}
.links a{color:var(--accent);text-decoration:none}
.links a:hover{text-decoration:underline}
</style>
</head>
<body>
<div class="box">
    <h1>🔌 ECCM</h1>
    <div class="subtitle">Passwort zurücksetzen</div>

    <?php if ($message): ?>
        <div class="msg <?= $success ? 'ok' : 'error' ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if (!$success): ?>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">

        <label for="email">E-Mail-Adresse</label>
        <input type="email" id="email" name="email" required autofocus
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">

        <button type="submit" class="btn">Link zum Zurücksetzen senden</button>
    </form>
    <?php endif; ?>

    <div class="links">
        <a href="login.php">← Zurück zum Login</a>
    </div>
</div>
</body>
</html>
