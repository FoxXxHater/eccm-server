<?php
/**
 * ECCM – Installation / Setup v2
 */
require_once __DIR__ . '/includes/config.php';

$message = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $host   = trim($_POST['host'] ?? 'localhost');
    $port   = (int)($_POST['port'] ?? 3306);
    $dbname = trim($_POST['dbname'] ?? 'eccm_db');
    $user   = trim($_POST['username'] ?? 'root');
    $pass   = $_POST['password'] ?? '';
    $adminUser  = trim($_POST['admin_user'] ?? 'admin');
    $adminEmail = trim($_POST['admin_email'] ?? 'admin@example.com');
    $adminPass  = $_POST['admin_pass'] ?? 'admin123';

    try {
        $pdo = new PDO("mysql:host=$host;port=$port;charset=utf8mb4", $user, $pass,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `$dbname`");

        $pdo->exec("CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(100) NOT NULL UNIQUE,
            email VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            role ENUM('admin','user') NOT NULL DEFAULT 'user',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB");

        $pdo->exec("CREATE TABLE IF NOT EXISTS password_resets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            token VARCHAR(64) NOT NULL UNIQUE,
            expires_at DATETIME NOT NULL,
            used TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB");

        $pdo->exec("CREATE TABLE IF NOT EXISTS profiles (
            id INT AUTO_INCREMENT PRIMARY KEY,
            owner_id INT NOT NULL,
            name VARCHAR(255) NOT NULL,
            data LONGTEXT NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY uq_owner_profile (owner_id, name)
        ) ENGINE=InnoDB");

        $pdo->exec("CREATE TABLE IF NOT EXISTS profile_permissions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            profile_id INT NOT NULL,
            user_id INT NOT NULL,
            can_view TINYINT(1) NOT NULL DEFAULT 1,
            can_patch TINYINT(1) NOT NULL DEFAULT 0,
            can_add_patch TINYINT(1) NOT NULL DEFAULT 0,
            can_edit_device TINYINT(1) NOT NULL DEFAULT 0,
            can_add_device TINYINT(1) NOT NULL DEFAULT 0,
            can_delete TINYINT(1) NOT NULL DEFAULT 0,
            can_manage TINYINT(1) NOT NULL DEFAULT 0,
            can_export TINYINT(1) NOT NULL DEFAULT 0,
            can_backup TINYINT(1) NOT NULL DEFAULT 0,
            FOREIGN KEY (profile_id) REFERENCES profiles(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY uq_profile_user (profile_id, user_id)
        ) ENGINE=InnoDB");

        // Migration: add columns if missing (existing installations)
        try { $pdo->exec("ALTER TABLE profile_permissions ADD COLUMN can_export TINYINT(1) NOT NULL DEFAULT 0"); } catch(Exception $e) {}
        try { $pdo->exec("ALTER TABLE profile_permissions ADD COLUMN can_backup TINYINT(1) NOT NULL DEFAULT 0"); } catch(Exception $e) {}

        $pdo->exec("CREATE TABLE IF NOT EXISTS user_active_profile (
            user_id INT PRIMARY KEY,
            profile_id INT NOT NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (profile_id) REFERENCES profiles(id) ON DELETE CASCADE
        ) ENGINE=InnoDB");

        $pdo->exec("CREATE TABLE IF NOT EXISTS user_settings (
            user_id INT PRIMARY KEY,
            settings TEXT NOT NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB");

        $pdo->exec("CREATE TABLE IF NOT EXISTS notification_subscriptions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            profile_id INT NOT NULL,
            on_device_change TINYINT(1) NOT NULL DEFAULT 0,
            on_device_add TINYINT(1) NOT NULL DEFAULT 0,
            on_patch_change TINYINT(1) NOT NULL DEFAULT 0,
            on_patch_add TINYINT(1) NOT NULL DEFAULT 0,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (profile_id) REFERENCES profiles(id) ON DELETE CASCADE,
            UNIQUE KEY uq_user_profile_notif (user_id, profile_id)
        ) ENGINE=InnoDB");

        $pdo->exec("CREATE TABLE IF NOT EXISTS app_settings (
            setting_key VARCHAR(100) PRIMARY KEY,
            setting_value TEXT NOT NULL
        ) ENGINE=InnoDB");

        $pdo->exec("CREATE TABLE IF NOT EXISTS email_templates (
            template_key VARCHAR(100) PRIMARY KEY,
            subject_tpl TEXT NOT NULL,
            body_tpl TEXT NOT NULL,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB");

        $pdo->prepare("INSERT INTO app_settings (setting_key, setting_value) VALUES ('app_name', 'ECCM') ON DUPLICATE KEY UPDATE setting_key=setting_key")->execute();
        $pdo->prepare("INSERT INTO app_settings (setting_key, setting_value) VALUES ('default_language', 'de') ON DUPLICATE KEY UPDATE setting_key=setting_key")->execute();

        $hash = password_hash($adminPass, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (:u, :e, :p, 'admin') ON DUPLICATE KEY UPDATE password = VALUES(password), email = VALUES(email)");
        $stmt->execute(['u' => $adminUser, 'e' => $adminEmail, 'p' => $hash]);

        $cfg  = "<?php\n// Auto-generated by ECCM Installer\n";
        $cfg .= "\$db_config = [\n";
        $cfg .= "    'host'     => " . var_export($host, true) . ",\n";
        $cfg .= "    'port'     => $port,\n";
        $cfg .= "    'dbname'   => " . var_export($dbname, true) . ",\n";
        $cfg .= "    'username' => " . var_export($user, true) . ",\n";
        $cfg .= "    'password' => " . var_export($pass, true) . ",\n";
        $cfg .= "    'charset'  => 'utf8mb4',\n];\n";
        file_put_contents(__DIR__ . '/includes/config.local.php', $cfg);

        $success = true;
        $message = 'Installation erfolgreich! Bitte lösche install.php!';
    } catch (Exception $e) {
        $message = 'Fehler: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>ECCM – Installation</title>
<link rel="icon" type="image/png" sizes="32x32" href="https://img.icons8.com/stickers/32/ethernet-on.png">
<style>
:root{--bg:#0f1115;--panel:#171a21;--ink:#e8eaf1;--line:#262a33;--accent:#3b82f6;--danger:#ef4444;--success:#22c55e}
*,*::before,*::after{box-sizing:border-box}
html,body{height:100%;margin:0;font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif}
body{background:var(--bg);color:var(--ink);display:flex;align-items:center;justify-content:center;padding:20px}
.box{background:var(--panel);border:1px solid var(--line);border-radius:16px;padding:32px;width:100%;max-width:500px}
h1{font-size:22px;margin:0 0 6px;text-align:center}.subtitle{text-align:center;color:#a6adbb;font-size:13px;margin-bottom:24px}
h2{font-size:15px;margin:20px 0 8px;color:var(--accent)}
label{display:block;font-size:13px;color:#a6adbb;margin:8px 0 4px}
input{width:100%;background:#0f131b;color:var(--ink);border:1px solid var(--line);border-radius:8px;padding:10px 12px;font-size:14px;outline:none}
input:focus{border-color:var(--accent)}.row{display:flex;gap:10px}.row>div{flex:1}
.btn{display:block;width:100%;padding:12px;border:none;border-radius:8px;font-size:15px;font-weight:600;cursor:pointer;margin-top:20px;background:var(--accent);color:#fff}.btn:hover{opacity:.9}
.msg{border-radius:8px;padding:12px;font-size:13px;margin:16px 0;text-align:center}
.msg.ok{background:rgba(34,197,94,.15);border:1px solid var(--success);color:#86efac}
.msg.error{background:rgba(239,68,68,.15);border:1px solid var(--danger);color:#fca5a5}
a{color:var(--accent)}
</style></head><body>
<div class="box">
<h1>🔌 ECCM Installation</h1><div class="subtitle">Ethernet Cable Connection Manager – Setup</div>
<?php if ($message): ?><div class="msg <?=$success?'ok':'error'?>"><?=htmlspecialchars($message)?></div><?php endif;?>
<?php if ($success): ?><p style="text-align:center"><a href="login.php">→ Zum Login</a></p><?php else: ?>
<form method="post">
<h2>MySQL-Datenbank</h2>
<div class="row"><div><label>Host</label><input type="text" name="host" value="localhost" required></div><div style="max-width:100px"><label>Port</label><input type="number" name="port" value="3306" required></div></div>
<label>Datenbankname</label><input type="text" name="dbname" value="eccm_db" required>
<div class="row"><div><label>DB-Benutzer</label><input type="text" name="username" value="root" required></div><div><label>DB-Passwort</label><input type="password" name="password"></div></div>
<h2>Admin-Konto</h2>
<div class="row"><div><label>Benutzername</label><input type="text" name="admin_user" value="admin" required></div><div><label>E-Mail</label><input type="email" name="admin_email" value="admin@example.com" required></div></div>
<label>Passwort</label><input type="password" name="admin_pass" value="admin123" required>
<button type="submit" class="btn">Installieren</button>
</form><?php endif;?>
</div></body></html>
