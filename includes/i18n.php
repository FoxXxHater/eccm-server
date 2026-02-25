<?php
/**
 * ECCM – Internationalization (i18n)
 * 
 * Supported languages: 'de' (Deutsch), 'en' (English)
 */

$ECCM_TRANSLATIONS = [

// ── General / Shared ──
'app_version'         => ['de'=>'1.0.6',      'en'=>'1.0.6'],
'login'               => ['de'=>'Anmelden',    'en'=>'Login'],
'logout'              => ['de'=>'Abmelden',    'en'=>'Logout'],
'admin'               => ['de'=>'Admin',       'en'=>'Admin'],
'save'                => ['de'=>'Speichern',   'en'=>'Save'],
'cancel'              => ['de'=>'Abbrechen',   'en'=>'Cancel'],
'delete'              => ['de'=>'Löschen',     'en'=>'Delete'],
'create'              => ['de'=>'Erstellen',   'en'=>'Create'],
'edit'                => ['de'=>'Bearbeiten',  'en'=>'Edit'],
'close'               => ['de'=>'Schließen',   'en'=>'Close'],
'back'                => ['de'=>'← Zurück',    'en'=>'← Back'],
'loading'             => ['de'=>'Laden…',      'en'=>'Loading…'],
'error'               => ['de'=>'Fehler',      'en'=>'Error'],
'success'             => ['de'=>'Erfolg',      'en'=>'Success'],
'confirm_delete'      => ['de'=>'Wirklich löschen?', 'en'=>'Really delete?'],
'yes'                 => ['de'=>'Ja',          'en'=>'Yes'],
'no'                  => ['de'=>'Nein',        'en'=>'No'],
'username'            => ['de'=>'Benutzername', 'en'=>'Username'],
'email'               => ['de'=>'E-Mail',      'en'=>'Email'],
'password'            => ['de'=>'Passwort',    'en'=>'Password'],
'role'                => ['de'=>'Rolle',       'en'=>'Role'],
'user'                => ['de'=>'Benutzer',    'en'=>'User'],
'settings'            => ['de'=>'Einstellungen','en'=>'Settings'],
'language'            => ['de'=>'Sprache',     'en'=>'Language'],
'saved'               => ['de'=>'✓ Gespeichert','en'=>'✓ Saved'],
'save_error'          => ['de'=>'✗ Speicherfehler','en'=>'✗ Save error'],

// ── Login Page ──
'login_title'         => ['de'=>'Anmelden',    'en'=>'Sign In'],
'login_subtitle'      => ['de'=>'Ethernet Cable Connection Manager', 'en'=>'Ethernet Cable Connection Manager'],
'login_user_or_email' => ['de'=>'Benutzername oder E-Mail', 'en'=>'Username or email'],
'login_button'        => ['de'=>'Anmelden',    'en'=>'Sign in'],
'login_forgot'        => ['de'=>'Passwort vergessen?', 'en'=>'Forgot password?'],
'login_invalid'       => ['de'=>'Ungültiger Benutzername oder Passwort.', 'en'=>'Invalid username or password.'],
'login_session_error' => ['de'=>'Ungültige Sitzung. Bitte versuche es erneut.', 'en'=>'Invalid session. Please try again.'],
'login_fields_required'=>['de'=>'Bitte Benutzername und Passwort eingeben.', 'en'=>'Please enter username and password.'],

// ── Forgot Password ──
'forgot_title'        => ['de'=>'Passwort vergessen', 'en'=>'Forgot Password'],
'forgot_subtitle'     => ['de'=>'Link zum Zurücksetzen per E-Mail erhalten', 'en'=>'Receive a reset link via email'],
'forgot_send'         => ['de'=>'Link senden',  'en'=>'Send link'],
'forgot_back'         => ['de'=>'← Zurück zum Login', 'en'=>'← Back to login'],
'forgot_sent'         => ['de'=>'Falls ein Konto mit dieser E-Mail existiert, wurde ein Link gesendet.', 'en'=>'If an account with this email exists, a reset link has been sent.'],
'forgot_enter_email'  => ['de'=>'Bitte E-Mail-Adresse eingeben.', 'en'=>'Please enter your email address.'],

// ── Reset Password ──
'reset_title'         => ['de'=>'Neues Passwort setzen', 'en'=>'Set New Password'],
'reset_new_pw'        => ['de'=>'Neues Passwort', 'en'=>'New password'],
'reset_confirm_pw'    => ['de'=>'Passwort bestätigen', 'en'=>'Confirm password'],
'reset_button'        => ['de'=>'Passwort ändern', 'en'=>'Change password'],
'reset_success'       => ['de'=>'Passwort wurde geändert!', 'en'=>'Password has been changed!'],
'reset_to_login'      => ['de'=>'→ Zum Login',  'en'=>'→ Go to login'],
'reset_invalid_token' => ['de'=>'Ungültiger oder abgelaufener Token.', 'en'=>'Invalid or expired token.'],
'reset_mismatch'      => ['de'=>'Passwörter stimmen nicht überein.', 'en'=>'Passwords do not match.'],
'reset_too_short'     => ['de'=>'Passwort muss mindestens 6 Zeichen lang sein.', 'en'=>'Password must be at least 6 characters.'],
'reset_failed'        => ['de'=>'Zurücksetzen fehlgeschlagen.', 'en'=>'Reset failed.'],

// ── Main App (index.php) ──
'profiles'            => ['de'=>'Profile',     'en'=>'Profiles'],
'active_profile'      => ['de'=>'Aktives Profil', 'en'=>'Active profile'],
'new'                 => ['de'=>'Neu',          'en'=>'New'],
'rename'              => ['de'=>'Umbenennen',   'en'=>'Rename'],
'duplicate'           => ['de'=>'Duplizieren',  'en'=>'Duplicate'],
'permissions'         => ['de'=>'🔒 Rechte',    'en'=>'🔒 Permissions'],
'export'              => ['de'=>'Export',        'en'=>'Export'],
'import'              => ['de'=>'Import',        'en'=>'Import'],
'owner'               => ['de'=>'Eigentümer',   'en'=>'Owner'],
'read_only'           => ['de'=>'Nur Lesen',    'en'=>'Read only'],

'add_device'          => ['de'=>'Gerät hinzufügen', 'en'=>'Add device'],
'device_name'         => ['de'=>'Name',          'en'=>'Name'],
'device_ports'        => ['de'=>'Ports',         'en'=>'Ports'],
'device_colour'       => ['de'=>'Farbe',         'en'=>'Colour'],
'select_colour'       => ['de'=>'Farbe wählen',  'en'=>'Select colour'],
'add_device_btn'      => ['de'=>'Gerät hinzufügen','en'=>'Add device'],
'clear_all'           => ['de'=>'Alles löschen', 'en'=>'Clear all'],
'port_help'           => ['de'=>'Zwei freie Ports klicken um zu verbinden', 'en'=>'Click two free ports to connect'],
'unlink_help'         => ['de'=>'Trennen in Verbindungen', 'en'=>'Unlink in Connections'],
'alias_help'          => ['de'=>'Alt-Klick für Alias', 'en'=>'Alt-click for alias'],
'reserve_help'        => ['de'=>'CTRL-Klick für Reserviert', 'en'=>'CTRL-click for Reserved'],

'backup_restore'      => ['de'=>'Backup / Restore (alle Profile)', 'en'=>'Backup / Restore (all profiles)'],
'backup_all'          => ['de'=>'Backup erstellen', 'en'=>'Backup all'],
'restore_all'         => ['de'=>'Wiederherstellen', 'en'=>'Restore all'],
'find_connection'     => ['de'=>'Verbindung suchen', 'en'=>'Find connection'],
'search_placeholder'  => ['de'=>'Filter nach Gerät, Port, Alias…', 'en'=>'Filter by device, port, alias…'],
'devices'             => ['de'=>'Geräte',       'en'=>'Devices'],
'connections'         => ['de'=>'Verbindungen',  'en'=>'Connections'],
'no_devices'          => ['de'=>'Noch keine Geräte – füge links eins hinzu.', 'en'=>'No devices yet — add one on the left.'],
'print_layout'        => ['de'=>'Druckansicht',  'en'=>'Print layout'],
'notifications'       => ['de'=>'Benachrichtigungen', 'en'=>'Notifications'],
'theme'               => ['de'=>'Design',        'en'=>'Theme'],
'dark'                => ['de'=>'Dunkel',        'en'=>'Dark'],
'light'               => ['de'=>'Hell',          'en'=>'Light'],
'enable_port_rename'  => ['de'=>'Port-Umbenennung aktivieren', 'en'=>'Enable port renaming'],
'max_ports_device'    => ['de'=>'Maximale Ports pro Gerät', 'en'=>'Maximum ports per device'],

// ── Profile creation modal ──
'new_profile'         => ['de'=>'Neues Profil erstellen', 'en'=>'Create new profile'],
'profile_name'        => ['de'=>'Profilname',    'en'=>'Profile name'],
'profile_name_ph'     => ['de'=>'z.B. Kunde XY Serverraum', 'en'=>'e.g. Customer XY Server Room'],
'perms_for_others'    => ['de'=>'Berechtigungen für andere Benutzer', 'en'=>'Permissions for other users'],
'perms_owner_hint'    => ['de'=>'Der Ersteller hat immer volle Rechte. Admins haben immer vollen Zugriff.', 'en'=>'The creator always has full permissions. Admins always have full access.'],
'perm_view'           => ['de'=>'Ansehen',       'en'=>'View'],
'perm_patch'          => ['de'=>'Patchen',       'en'=>'Patch'],
'perm_add_patch'      => ['de'=>'+Patch',        'en'=>'+Patch'],
'perm_edit_device'    => ['de'=>'Gerät edit.',   'en'=>'Edit dev.'],
'perm_add_device'     => ['de'=>'+Gerät',        'en'=>'+Device'],
'perm_delete'         => ['de'=>'Löschen',       'en'=>'Delete'],
'perm_manage'         => ['de'=>'Verwalten',     'en'=>'Manage'],
'enter_profile_name'  => ['de'=>'Bitte Profilname eingeben.', 'en'=>'Please enter a profile name.'],

// ── Permissions modal ──
'manage_perms'        => ['de'=>'Berechtigungen verwalten', 'en'=>'Manage permissions'],
'owner_admin_hint'    => ['de'=>'Der Eigentümer und Admins haben immer volle Rechte.', 'en'=>'The owner and admins always have full permissions.'],
'only_owner_rename'   => ['de'=>'Nur der Eigentümer kann umbenennen.', 'en'=>'Only the owner can rename.'],
'only_owner_delete'   => ['de'=>'Nur der Eigentümer kann löschen.', 'en'=>'Only the owner can delete.'],
'only_owner_perms'    => ['de'=>'Nur der Eigentümer oder ein Verwalter kann Rechte ändern.', 'en'=>'Only the owner or a manager can change permissions.'],
'min_one_profile'     => ['de'=>'Mindestens ein Profil muss erhalten bleiben.', 'en'=>'At least one profile must remain.'],
'confirm_delete_profile'=>['de'=>'Profil wirklich löschen?', 'en'=>'Really delete this profile?'],
'clear_all_confirm'   => ['de'=>'ALLE Geräte und Verbindungen löschen?', 'en'=>'Delete ALL devices and connections?'],

// ── Notification modal ──
'notif_title'         => ['de'=>'🔔 E-Mail-Benachrichtigungen', 'en'=>'🔔 Email Notifications'],
'notif_hint'          => ['de'=>'Wähle aus, bei welchen Änderungen du per E-Mail benachrichtigt werden möchtest. Du wirst nur bei Änderungen durch andere Benutzer benachrichtigt.', 'en'=>'Choose which changes you want to be notified about by email. You will only be notified about changes made by other users.'],
'notif_device_change' => ['de'=>'Gerät geändert', 'en'=>'Device changed'],
'notif_device_add'    => ['de'=>'Gerät hinzugefügt','en'=>'Device added'],
'notif_patch_change'  => ['de'=>'Patch geändert','en'=>'Patch changed'],
'notif_patch_add'     => ['de'=>'Patch hinzugefügt','en'=>'Patch added'],

// ── Connection table ──
'conn_device_a'       => ['de'=>'Gerät A',      'en'=>'Device A'],
'conn_port'           => ['de'=>'Port',          'en'=>'Port'],
'conn_alias'          => ['de'=>'Alias',         'en'=>'Alias'],
'conn_device_b'       => ['de'=>'Gerät B',      'en'=>'Device B'],
'conn_highlight_hint' => ['de'=>'Klicke eine Zeile um beide Enden hervorzuheben.', 'en'=>'Click a row to highlight both ends.'],

// ── Device layout modal ──
'layout_options'      => ['de'=>'Geräte-Layoutoptionen', 'en'=>'Device layout options'],
'layout_device'       => ['de'=>'Gerät',         'en'=>'Device'],
'layout_row_width'    => ['de'=>'Reihenbreite',  'en'=>'Row width'],
'layout_auto'         => ['de'=>'Auto',          'en'=>'Auto'],
'layout_force_full'   => ['de'=>'Volle Breite',  'en'=>'Force full row'],
'layout_13_24'        => ['de'=>'13–24 Ports',   'en'=>'13–24 ports'],
'layout_balanced'     => ['de'=>'Ausgeglichen',  'en'=>'Balanced'],
'layout_twelve'       => ['de'=>'12 + Rest',     'en'=>'12 + remainder'],
'layout_12_or_less'   => ['de'=>'≤12 Ports',     'en'=>'≤12 ports'],
'layout_single_row'   => ['de'=>'Einzelne Reihe','en'=>'Single row'],
'layout_split'        => ['de'=>'Geteilt',       'en'=>'Split'],
'layout_dual_link'    => ['de'=>'Dual Link',     'en'=>'Dual link'],
'layout_normal'       => ['de'=>'Normal',        'en'=>'Normal'],
'layout_dual_on'      => ['de'=>'Dual Link',     'en'=>'Dual link'],
'layout_numbering'    => ['de'=>'Port-Nummerierung','en'=>'Port numbering'],
'layout_left_right'   => ['de'=>'Links → Rechts','en'=>'Left → Right'],
'layout_top_bottom'   => ['de'=>'Oben ↓ Unten', 'en'=>'Top ↓ Bottom'],
'layout_bottom_top'   => ['de'=>'Unten ↑ Oben', 'en'=>'Bottom ↑ Top'],

// ── Admin ──
'admin_title'         => ['de'=>'ECCM Admin',    'en'=>'ECCM Admin'],
'tab_general'         => ['de'=>'Allgemein',     'en'=>'General'],
'tab_users'           => ['de'=>'Benutzer',      'en'=>'Users'],
'tab_smtp'            => ['de'=>'E-Mail / SMTP', 'en'=>'Email / SMTP'],
'tab_templates'       => ['de'=>'E-Mail-Vorlagen','en'=>'Email Templates'],
'tab_database'        => ['de'=>'Datenbank',     'en'=>'Database'],

// Admin: General tab
'general_title'       => ['de'=>'⚙️ Allgemeine Einstellungen', 'en'=>'⚙️ General Settings'],
'general_hint'        => ['de'=>'Globale Einstellungen für die gesamte Anwendung.', 'en'=>'Global settings for the entire application.'],
'app_name'            => ['de'=>'App-Name',      'en'=>'App Name'],
'app_name_hint'       => ['de'=>'Wird in E-Mails und der Oberfläche angezeigt.', 'en'=>'Shown in emails and the interface.'],
'default_language'    => ['de'=>'Standard-Sprache', 'en'=>'Default Language'],
'default_language_hint'=>['de'=>'Wird für neue Benutzer und die Login-Seite verwendet.', 'en'=>'Used for new users and the login page.'],
'general_saved'       => ['de'=>'Einstellungen gespeichert.', 'en'=>'Settings saved.'],

// Admin: Users
'create_user'         => ['de'=>'Neuen Benutzer erstellen', 'en'=>'Create new user'],
'user_list'           => ['de'=>'Benutzerliste', 'en'=>'User list'],
'created_at'          => ['de'=>'Erstellt',      'en'=>'Created'],
'actions'             => ['de'=>'Aktionen',      'en'=>'Actions'],
'edit_user'           => ['de'=>'Benutzer bearbeiten', 'en'=>'Edit user'],
'new_password_hint'   => ['de'=>'Neues Passwort (leer = unverändert)', 'en'=>'New password (empty = unchanged)'],
'user_created'        => ['de'=>'Erstellt!',     'en'=>'Created!'],
'confirm_delete_user' => ['de'=>'Benutzer löschen?', 'en'=>'Delete user?'],

// Admin: SMTP
'smtp_title'          => ['de'=>'📧 E-Mail / SMTP-Konfiguration', 'en'=>'📧 Email / SMTP Configuration'],
'smtp_hint'           => ['de'=>'Diese Einstellungen werden für Passwort-Zurücksetzungen und Benachrichtigungen verwendet.', 'en'=>'These settings are used for password resets and notifications.'],
'smtp_from_name'      => ['de'=>'Absender-Name', 'en'=>'From name'],
'smtp_from_email'     => ['de'=>'Absender-E-Mail','en'=>'From email'],
'smtp_server'         => ['de'=>'SMTP-Server',   'en'=>'SMTP Server'],
'smtp_host'           => ['de'=>'SMTP-Host',     'en'=>'SMTP Host'],
'smtp_port'           => ['de'=>'Port',          'en'=>'Port'],
'smtp_encryption'     => ['de'=>'Verschlüsselung','en'=>'Encryption'],
'smtp_user'           => ['de'=>'SMTP-Benutzer', 'en'=>'SMTP User'],
'smtp_pass'           => ['de'=>'SMTP-Passwort', 'en'=>'SMTP Password'],
'smtp_base_url'       => ['de'=>'Basis-URL (für Links, leer = automatisch)', 'en'=>'Base URL (for links, empty = auto)'],
'smtp_test_conn'      => ['de'=>'Verbindung testen', 'en'=>'Test connection'],
'smtp_test_email_title'=>['de'=>'📨 Test-E-Mail senden', 'en'=>'📨 Send test email'],
'smtp_test_hint'      => ['de'=>'Sendet eine Test-E-Mail mit den aktuellen Einstellungen.', 'en'=>'Sends a test email with the current settings.'],
'smtp_recipient'      => ['de'=>'Empfänger',     'en'=>'Recipient'],
'smtp_send_test'      => ['de'=>'Test senden',   'en'=>'Send test'],

// Admin: Templates
'tpl_title'           => ['de'=>'✉️ E-Mail-Vorlagen bearbeiten', 'en'=>'✉️ Edit Email Templates'],
'tpl_hint'            => ['de'=>'Passe Betreff und Inhalt der automatischen E-Mails an. Verwende die Platzhalter um dynamische Werte einzufügen.', 'en'=>'Customize subject and body of automatic emails. Use placeholders to insert dynamic values.'],
'tpl_save'            => ['de'=>'Vorlagen speichern', 'en'=>'Save templates'],
'tpl_reset'           => ['de'=>'Auf Standard zurücksetzen', 'en'=>'Reset to defaults'],
'tpl_reset_confirm'   => ['de'=>'Alle Vorlagen auf Standard zurücksetzen?', 'en'=>'Reset all templates to defaults?'],
'tpl_subject'         => ['de'=>'Betreff',       'en'=>'Subject'],
'tpl_body'            => ['de'=>'Inhalt',        'en'=>'Body'],
'tpl_placeholders'    => ['de'=>'Platzhalter',   'en'=>'Placeholders'],
'tpl_preview'         => ['de'=>'Vorschau',      'en'=>'Preview'],
'tpl_click_hint'      => ['de'=>'Klicke auf einen Platzhalter um ihn einzufügen.', 'en'=>'Click a placeholder to insert it.'],
'tpl_notification'    => ['de'=>'Änderungs-Benachrichtigung', 'en'=>'Change Notification'],
'tpl_password_reset'  => ['de'=>'Passwort zurücksetzen', 'en'=>'Password Reset'],

// Admin: Database
'db_title'            => ['de'=>'MySQL-Datenbankverbindung', 'en'=>'MySQL Database Connection'],
'db_hint'             => ['de'=>'Konfiguration wird in config.local.php gespeichert.', 'en'=>'Configuration is saved in config.local.php.'],
'db_host'             => ['de'=>'Host',          'en'=>'Host'],
'db_port'             => ['de'=>'Port',          'en'=>'Port'],
'db_name'             => ['de'=>'Datenbank',     'en'=>'Database'],
'db_user'             => ['de'=>'Benutzername',  'en'=>'Username'],
'db_pass'             => ['de'=>'Passwort',      'en'=>'Password'],
'db_test'             => ['de'=>'Verbindung testen', 'en'=>'Test connection'],

// ── Notification event labels (for emails) ──
'evt_device_change'   => ['de'=>'Gerät geändert',     'en'=>'Device changed'],
'evt_device_add'      => ['de'=>'Gerät hinzugefügt',  'en'=>'Device added'],
'evt_patch_change'    => ['de'=>'Patchung geändert',   'en'=>'Patch changed'],
'evt_patch_add'       => ['de'=>'Neue Patchung',       'en'=>'New patch'],
'evt_device_delete'   => ['de'=>'Gerät gelöscht',      'en'=>'Device deleted'],
'evt_patch_delete'    => ['de'=>'Patchung entfernt',   'en'=>'Patch removed'],
];

/**
 * Get translation for a key.
 */
function __t(string $key, ?string $lang = null): string {
    global $ECCM_TRANSLATIONS, $ECCM_LANG;
    $lang = $lang ?? $ECCM_LANG ?? 'de';
    return $ECCM_TRANSLATIONS[$key][$lang] ?? $ECCM_TRANSLATIONS[$key]['de'] ?? $key;
}

/**
 * Get all translations as JSON for JavaScript.
 */
function getTranslationsJSON(?string $lang = null): string {
    global $ECCM_TRANSLATIONS, $ECCM_LANG;
    $lang = $lang ?? $ECCM_LANG ?? 'de';
    $flat = [];
    foreach ($ECCM_TRANSLATIONS as $key => $vals) {
        $flat[$key] = $vals[$lang] ?? $vals['de'] ?? $key;
    }
    return json_encode($flat, JSON_UNESCAPED_UNICODE);
}

/**
 * Detect the active language for the current user.
 */
function detectLanguage(): string {
    global $ECCM_LANG;

    // 1. User's preference (from session/settings)
    if (isset($_SESSION['eccm_lang']) && in_array($_SESSION['eccm_lang'], ['de','en'])) {
        $ECCM_LANG = $_SESSION['eccm_lang'];
        return $ECCM_LANG;
    }

    // 2. Global default from app_settings
    try {
        $db = getDB();
        // User-specific language
        if (isset($_SESSION['eccm_user_id'])) {
            $stmt = $db->prepare('SELECT settings FROM user_settings WHERE user_id = :uid');
            $stmt->execute(['uid' => $_SESSION['eccm_user_id']]);
            $row = $stmt->fetch();
            if ($row) {
                $s = json_decode($row['settings'], true);
                if (isset($s['language']) && in_array($s['language'], ['de','en'])) {
                    $ECCM_LANG = $s['language'];
                    $_SESSION['eccm_lang'] = $ECCM_LANG;
                    return $ECCM_LANG;
                }
            }
        }
        // Global default
        $stmt2 = $db->prepare("SELECT setting_value FROM app_settings WHERE setting_key = 'default_language'");
        $stmt2->execute();
        $val = $stmt2->fetchColumn();
        if ($val && in_array($val, ['de','en'])) {
            $ECCM_LANG = $val;
            return $ECCM_LANG;
        }
    } catch (\Exception $e) {
        // DB not available yet
    }

    $ECCM_LANG = 'de';
    return $ECCM_LANG;
}

/**
 * Load global app settings from DB.
 */
function getAppSetting(string $key, string $default = ''): string {
    try {
        $db = getDB();
        $db->exec("CREATE TABLE IF NOT EXISTS app_settings (
            setting_key VARCHAR(100) PRIMARY KEY,
            setting_value TEXT NOT NULL
        ) ENGINE=InnoDB");
        $stmt = $db->prepare('SELECT setting_value FROM app_settings WHERE setting_key = :k');
        $stmt->execute(['k' => $key]);
        $val = $stmt->fetchColumn();
        return ($val !== false) ? $val : $default;
    } catch (\Exception $e) {
        return $default;
    }
}

function getAppName(): string {
    return getAppSetting('app_name', 'ECCM');
}
