<?php
/**
 * ECCM – Admin API
 *
 * Actions:
 *   list_users     – list all users
 *   create_user    – create a new user
 *   update_user    – update username/email/password/role
 *   delete_user    – delete a user
 *   test_db        – test database connection
 *   save_db        – save database config to config.local.php
 *   get_db_config  – return current DB config
 */
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/auth.php';
startSecureSession();

if (!isLoggedIn() || currentUserRole() !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Zugriff verweigert']);
    exit;
}

$input  = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $input['action'] ?? $_POST['action'] ?? $_GET['action'] ?? '';

try {
    $db = getDB();

    switch ($action) {

        /* ── LIST USERS ────────────────────────────── */
        case 'list_users':
            $rows = $db->query('SELECT id, username, email, role, created_at FROM users ORDER BY id')->fetchAll();
            echo json_encode(['ok' => true, 'users' => $rows]);
            break;

        /* ── CREATE USER ───────────────────────────── */
        case 'create_user':
            $username = trim($input['username'] ?? '');
            $email    = trim($input['email'] ?? '');
            $password = $input['password'] ?? '';
            $role     = in_array($input['role'] ?? '', ['admin','user']) ? $input['role'] : 'user';

            if ($username === '' || $email === '' || strlen($password) < 6) {
                echo json_encode(['error' => 'Benutzername, E-Mail und Passwort (min. 6 Zeichen) erforderlich.']);
                exit;
            }

            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare(
                'INSERT INTO users (username, email, password, role) VALUES (:u, :e, :p, :r)'
            );
            $stmt->execute(['u' => $username, 'e' => $email, 'p' => $hash, 'r' => $role]);
            echo json_encode(['ok' => true, 'id' => $db->lastInsertId()]);
            break;

        /* ── UPDATE USER ───────────────────────────── */
        case 'update_user':
            $id       = (int)($input['id'] ?? 0);
            $username = trim($input['username'] ?? '');
            $email    = trim($input['email'] ?? '');
            $role     = in_array($input['role'] ?? '', ['admin','user']) ? $input['role'] : 'user';
            $password = $input['password'] ?? '';

            if ($id <= 0 || $username === '' || $email === '') {
                echo json_encode(['error' => 'Ungültige Daten.']);
                exit;
            }

            if ($password !== '' && strlen($password) >= 6) {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $db->prepare('UPDATE users SET username=:u, email=:e, role=:r, password=:p WHERE id=:id')
                   ->execute(['u'=>$username,'e'=>$email,'r'=>$role,'p'=>$hash,'id'=>$id]);
            } else {
                $db->prepare('UPDATE users SET username=:u, email=:e, role=:r WHERE id=:id')
                   ->execute(['u'=>$username,'e'=>$email,'r'=>$role,'id'=>$id]);
            }
            echo json_encode(['ok' => true]);
            break;

        /* ── DELETE USER ───────────────────────────── */
        case 'delete_user':
            $id = (int)($input['id'] ?? 0);
            if ($id <= 0) { echo json_encode(['error' => 'Ungültige ID']); exit; }
            if ($id === currentUserId()) { echo json_encode(['error' => 'Du kannst dich nicht selbst löschen.']); exit; }
            $db->prepare('DELETE FROM users WHERE id = :id')->execute(['id' => $id]);
            echo json_encode(['ok' => true]);
            break;

        /* ── TEST DB CONNECTION ───────────────────── */
        case 'test_db':
            $ok = testDBConnection(
                $input['host']     ?? 'localhost',
                (int)($input['port'] ?? 3306),
                $input['dbname']   ?? '',
                $input['username'] ?? '',
                $input['password'] ?? ''
            );
            echo json_encode(['ok' => $ok, 'message' => $ok ? 'Verbindung erfolgreich!' : 'Verbindung fehlgeschlagen.']);
            break;

        /* ── GET GENERAL SETTINGS ─────────────── */
        case 'get_general_settings':
            $db = getDB();
            $db->exec("CREATE TABLE IF NOT EXISTS app_settings (
                setting_key VARCHAR(100) PRIMARY KEY,
                setting_value TEXT NOT NULL
            ) ENGINE=InnoDB");
            $rows = $db->query('SELECT * FROM app_settings')->fetchAll();
            $settings = [];
            foreach ($rows as $r) { $settings[$r['setting_key']] = $r['setting_value']; }
            // Defaults
            if (!isset($settings['app_name'])) $settings['app_name'] = 'ECCM';
            if (!isset($settings['default_language'])) $settings['default_language'] = 'de';
            echo json_encode(['ok' => true, 'settings' => $settings]);
            break;

        /* ── SAVE GENERAL SETTINGS ────────────── */
        case 'save_general_settings':
            $db = getDB();
            $db->exec("CREATE TABLE IF NOT EXISTS app_settings (
                setting_key VARCHAR(100) PRIMARY KEY,
                setting_value TEXT NOT NULL
            ) ENGINE=InnoDB");
            $vals = $input['settings'] ?? [];
            $ins = $db->prepare('INSERT INTO app_settings (setting_key, setting_value) VALUES (:k, :v) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
            foreach ($vals as $k => $v) {
                $ins->execute(['k' => $k, 'v' => (string)$v]);
            }
            echo json_encode(['ok' => true, 'message' => 'Einstellungen gespeichert.']);
            break;

        /* ── SAVE DB CONFIG ───────────────────────── */
        case 'save_db':
            $cfg  = "<?php\n";
            $cfg .= "// Auto-generated by ECCM Admin\n";
            $cfg .= "\$db_config = [\n";
            $cfg .= "    'host'     => " . var_export($input['host'] ?? 'localhost', true) . ",\n";
            $cfg .= "    'port'     => " . ((int)($input['port'] ?? 3306)) . ",\n";
            $cfg .= "    'dbname'   => " . var_export($input['dbname'] ?? 'eccm_db', true) . ",\n";
            $cfg .= "    'username' => " . var_export($input['username'] ?? 'root', true) . ",\n";
            $cfg .= "    'password' => " . var_export($input['password'] ?? '', true) . ",\n";
            $cfg .= "    'charset'  => 'utf8mb4',\n";
            $cfg .= "];\n";

            $path = __DIR__ . '/../includes/config.local.php';
            if (@file_put_contents($path, $cfg) !== false) {
                echo json_encode(['ok' => true, 'message' => 'Konfiguration gespeichert.']);
            } else {
                echo json_encode(['error' => 'Konnte Datei nicht schreiben. Bitte Berechtigungen prüfen.']);
            }
            break;

        /* ── GET CURRENT DB CONFIG ────────────────── */
        case 'get_db_config':
            global $db_config;
            $safe = $db_config;
            $safe['password'] = $safe['password'] ? '••••••' : '';
            echo json_encode(['ok' => true, 'config' => $safe]);
            break;

        /* ── GET SMTP CONFIG ──────────────────────── */
        case 'get_smtp_config':
            global $mail_config;
            $safe = $mail_config;
            $safe['smtp_pass'] = ($safe['smtp_pass'] ?? '') ? '••••••' : '';
            echo json_encode(['ok' => true, 'config' => $safe]);
            break;

        /* ── SAVE SMTP CONFIG ─────────────────────── */
        case 'save_smtp':
            $path = __DIR__ . '/../includes/config.local.php';
            $existing = file_exists($path) ? file_get_contents($path) : "<?php\n";

            // Remove old mail_config if present
            $existing = preg_replace('/\$mail_config\s*=\s*\[.*?\];\s*/s', '', $existing);

            $cfg  = "\n\$mail_config = [\n";
            $cfg .= "    'from_email'      => " . var_export($input['from_email'] ?? 'noreply@example.com', true) . ",\n";
            $cfg .= "    'from_name'       => " . var_export($input['from_name'] ?? 'ECCM System', true) . ",\n";
            $cfg .= "    'smtp_host'       => " . var_export($input['smtp_host'] ?? '', true) . ",\n";
            $cfg .= "    'smtp_port'       => " . ((int)($input['smtp_port'] ?? 587)) . ",\n";
            $cfg .= "    'smtp_user'       => " . var_export($input['smtp_user'] ?? '', true) . ",\n";
            $cfg .= "    'smtp_pass'       => " . var_export($input['smtp_pass'] ?? '', true) . ",\n";
            $cfg .= "    'smtp_encryption' => " . var_export($input['smtp_encryption'] ?? 'tls', true) . ",\n";
            $cfg .= "    'base_url'        => " . var_export($input['base_url'] ?? '', true) . ",\n";
            $cfg .= "];\n";

            if (@file_put_contents($path, $existing . $cfg) !== false) {
                echo json_encode(['ok' => true, 'message' => 'SMTP-Konfiguration gespeichert.']);
            } else {
                echo json_encode(['error' => 'Konnte Datei nicht schreiben.']);
            }
            break;

        /* ── TEST SMTP CONNECTION ─────────────────── */
        case 'test_smtp':
            require_once __DIR__ . '/../includes/mailer.php';
            $result = testSMTPConnection($input);
            echo json_encode($result);
            break;

        /* ── SEND TEST EMAIL ──────────────────────── */
        case 'send_test_email':
            require_once __DIR__ . '/../includes/mailer.php';

            // Temporarily override mail_config
            global $mail_config;
            $mail_config['smtp_host']       = $input['smtp_host'] ?? $mail_config['smtp_host'];
            $mail_config['smtp_port']       = (int)($input['smtp_port'] ?? $mail_config['smtp_port']);
            $mail_config['smtp_user']       = $input['smtp_user'] ?? $mail_config['smtp_user'];
            $mail_config['smtp_pass']       = ($input['smtp_pass'] ?? '') ?: $mail_config['smtp_pass'];
            $mail_config['smtp_encryption'] = $input['smtp_encryption'] ?? ($mail_config['smtp_encryption'] ?? 'tls');
            $mail_config['from_email']      = $input['from_email'] ?? $mail_config['from_email'];
            $mail_config['from_name']       = $input['from_name'] ?? $mail_config['from_name'];

            $testTo = trim($input['test_email'] ?? '');
            if ($testTo === '') {
                echo json_encode(['ok' => false, 'message' => 'Keine Test-E-Mail-Adresse angegeben.']);
                break;
            }

            $ok = sendMail($testTo, 'ECCM – Test-E-Mail', "Dies ist eine Test-E-Mail vom ECCM System.\n\nWenn du das liest, funktioniert der E-Mail-Versand!\n\n– ECCM");
            echo json_encode(['ok' => $ok, 'message' => $ok ? 'Test-E-Mail gesendet an ' . $testTo . '!' : 'Versand fehlgeschlagen. Prüfe die SMTP-Einstellungen und das Error-Log.']);
            break;

        /* ── GET EMAIL TEMPLATES ───────────────── */
        case 'get_email_templates':
            $db = getDB();
            // Ensure table exists
            $db->exec("CREATE TABLE IF NOT EXISTS email_templates (
                template_key VARCHAR(100) PRIMARY KEY,
                subject_tpl  TEXT NOT NULL,
                body_tpl     TEXT NOT NULL,
                updated_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB");

            $rows = $db->query('SELECT * FROM email_templates ORDER BY template_key')->fetchAll();
            $templates = [];
            foreach ($rows as $r) {
                $templates[$r['template_key']] = ['subject' => $r['subject_tpl'], 'body' => $r['body_tpl']];
            }

            // Merge with defaults for any missing templates
            $defaults = getDefaultEmailTemplates();
            foreach ($defaults as $key => $tpl) {
                if (!isset($templates[$key])) {
                    $templates[$key] = $tpl;
                }
            }

            echo json_encode(['ok' => true, 'templates' => $templates, 'placeholders' => getTemplatePlaceholders()]);
            break;

        /* ── SAVE EMAIL TEMPLATES ─────────────── */
        case 'save_email_templates':
            $db = getDB();
            $db->exec("CREATE TABLE IF NOT EXISTS email_templates (
                template_key VARCHAR(100) PRIMARY KEY,
                subject_tpl  TEXT NOT NULL,
                body_tpl     TEXT NOT NULL,
                updated_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB");

            $tpls = $input['templates'] ?? [];
            $ins = $db->prepare('INSERT INTO email_templates (template_key, subject_tpl, body_tpl) VALUES (:k, :s, :b) ON DUPLICATE KEY UPDATE subject_tpl = VALUES(subject_tpl), body_tpl = VALUES(body_tpl)');
            foreach ($tpls as $key => $tpl) {
                $ins->execute([
                    'k' => $key,
                    's' => $tpl['subject'] ?? '',
                    'b' => $tpl['body'] ?? '',
                ]);
            }
            echo json_encode(['ok' => true, 'message' => 'E-Mail-Vorlagen gespeichert.']);
            break;

        /* ── RESET EMAIL TEMPLATES TO DEFAULT ── */
        case 'reset_email_templates':
            $db = getDB();
            $db->exec("DELETE FROM email_templates");
            echo json_encode(['ok' => true, 'message' => 'Vorlagen auf Standard zurückgesetzt.']);
            break;

        /* ── PREVIEW EMAIL TEMPLATE ────────────── */
        case 'preview_email_template':
            $subject = $input['subject'] ?? '';
            $body = $input['body'] ?? '';
            $sample = [
                '{{username}}'     => 'Max Mustermann',
                '{{profile_name}}' => 'Kunde 01',
                '{{event_type}}'   => 'Neue Patchung',
                '{{actor}}'        => 'admin',
                '{{details}}'      => 'Neue Verbindung: Core Switch Port 5 ↔ Access Switch Port 5',
                '{{owner}}'        => 'foxxxhater',
                '{{date}}'         => date('d.m.Y H:i'),
                '{{app_name}}'     => 'ECCM',
                '{{base_url}}'     => 'https://eccm.example.com',
            ];
            foreach ($sample as $k => $v) {
                $subject = str_replace($k, $v, $subject);
                $body = str_replace($k, $v, $body);
            }
            echo json_encode(['ok' => true, 'subject' => $subject, 'body' => $body]);
            break;

        default:
            echo json_encode(['error' => 'Unbekannte Aktion: ' . $action]);
    }

} catch (PDOException $e) {
    http_response_code(500);
    $msg = $e->getMessage();
    // Check for duplicate entry
    if (strpos($msg, 'Duplicate entry') !== false) {
        echo json_encode(['error' => 'Benutzername oder E-Mail existiert bereits.']);
    } else {
        echo json_encode(['error' => $msg]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

/**
 * Default email templates with placeholders.
 */
function getDefaultEmailTemplates(): array {
    return [
        'notification' => [
            'subject' => '{{app_name}} – {{event_type}} in "{{profile_name}}"',
            'body'    => "Hallo {{username}},\n\nim Profil \"{{profile_name}}\" gab es eine Änderung:\n\nTyp:      {{event_type}}\nDurch:    {{actor}}\nDetails:  {{details}}\nZeit:     {{date}}\n\n– {{app_name}} Benachrichtigung",
        ],
        'password_reset' => [
            'subject' => '{{app_name}} – Passwort zurücksetzen',
            'body'    => "Hallo {{username}},\n\nes wurde eine Passwortzurücksetzung angefordert.\n\nKlicke auf den folgenden Link, um dein Passwort zu ändern:\n\n{{reset_url}}\n\nDieser Link ist 1 Stunde gültig.\n\nFalls du das nicht angefordert hast, ignoriere diese E-Mail.\n\n– {{app_name}}",
        ],
    ];
}

/**
 * Available placeholders per template.
 */
function getTemplatePlaceholders(): array {
    return [
        'notification' => [
            '{{username}}'     => 'Name des Empfängers',
            '{{profile_name}}' => 'Name des Profils/Kunden',
            '{{event_type}}'   => 'Art der Änderung (z.B. "Neue Patchung")',
            '{{actor}}'        => 'Benutzername des Verursachers',
            '{{details}}'      => 'Details zur Änderung',
            '{{owner}}'        => 'Eigentümer des Profils',
            '{{date}}'         => 'Datum und Uhrzeit',
            '{{app_name}}'     => 'App-Name (ECCM)',
            '{{base_url}}'     => 'Basis-URL der Anwendung',
        ],
        'password_reset' => [
            '{{username}}'   => 'Benutzername',
            '{{reset_url}}'  => 'Link zum Zurücksetzen',
            '{{app_name}}'   => 'App-Name (ECCM)',
            '{{base_url}}'   => 'Basis-URL der Anwendung',
        ],
    ];
}
