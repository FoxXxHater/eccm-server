<?php
/**
 * ECCM – Notification System
 * 
 * Sends email notifications to subscribed users when profiles/devices change.
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/mailer.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/i18n.php';

/**
 * Notify subscribed users about a change.
 * 
 * @param int    $profileId   The profile that changed
 * @param int    $actorUserId The user who made the change
 * @param string $eventType   'device_change','device_add','patch_change','patch_add','device_delete','patch_delete'
 * @param string $details     Human-readable description of what changed
 */
function notifyProfileChange(int $profileId, int $actorUserId, string $eventType, string $details): void {
    $db = getDB();

    // Ensure table exists
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS notification_subscriptions (
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
    } catch (\Exception $e) {
        // Table might already exist, ignore
    }

    // Get profile info
    $stmt = $db->prepare('SELECT p.name, u.username AS owner_name FROM profiles p JOIN users u ON u.id = p.owner_id WHERE p.id = :pid');
    $stmt->execute(['pid' => $profileId]);
    $profile = $stmt->fetch();
    if (!$profile) return;

    // Get actor name
    $stmt2 = $db->prepare('SELECT username FROM users WHERE id = :uid');
    $stmt2->execute(['uid' => $actorUserId]);
    $actor = $stmt2->fetchColumn() ?: 'Unbekannt';

    // Map event types to subscription columns
    $eventMap = [
        'device_change'  => 'on_device_change',
        'device_add'     => 'on_device_add',
        'patch_change'   => 'on_patch_change',
        'patch_add'      => 'on_patch_add',
        'device_delete'  => 'on_device_change',
        'patch_delete'   => 'on_patch_change',
    ];
    $col = $eventMap[$eventType] ?? null;
    if (!$col) return;

    // Find subscribed users (not the actor themselves)
    $sql = "SELECT ns.user_id, u.email, u.username 
            FROM notification_subscriptions ns
            JOIN users u ON u.id = ns.user_id
            WHERE ns.profile_id = :pid AND ns.{$col} = 1 AND ns.user_id != :actor";
    $stmt3 = $db->prepare($sql);
    $stmt3->execute(['pid' => $profileId, 'actor' => $actorUserId]);

    $eventLabels = [
        'device_change'  => 'Gerät geändert',
        'device_add'     => 'Gerät hinzugefügt',
        'patch_change'   => 'Patchung geändert',
        'patch_add'      => 'Neue Patchung',
        'device_delete'  => 'Gerät gelöscht',
        'patch_delete'   => 'Patchung entfernt',
    ];

    // Load email template
    $tpl = loadEmailTemplate($db, 'notification');
    $baseUrl = ($mail_config['base_url'] ?? '') ?: (
        (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') .
        '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') .
        dirname(dirname($_SERVER['SCRIPT_NAME'] ?? ''))
    );

    while ($sub = $stmt3->fetch()) {
        $replacements = [
            '{{username}}'     => $sub['username'],
            '{{profile_name}}' => $profile['name'],
            '{{event_type}}'   => $eventLabels[$eventType] ?? $eventType,
            '{{actor}}'        => $actor,
            '{{details}}'      => $details,
            '{{owner}}'        => $profile['owner_name'],
            '{{date}}'         => date('d.m.Y H:i'),
            '{{app_name}}'     => getAppName(),
            '{{base_url}}'     => rtrim($baseUrl, '/'),
        ];

        $subject = str_replace(array_keys($replacements), array_values($replacements), $tpl['subject']);
        $body    = str_replace(array_keys($replacements), array_values($replacements), $tpl['body']);

        try {
            sendMail($sub['email'], $subject, $body);
        } catch (\Exception $e) {
            error_log("ECCM Notification failed for {$sub['email']}: " . $e->getMessage());
        }
    }
}

/**
 * Load an email template from DB, falling back to defaults.
 */
function loadEmailTemplate(PDO $db, string $key): array {
    $defaults = [
        'notification' => [
            'subject' => '{{app_name}} – {{event_type}} in "{{profile_name}}"',
            'body'    => "Hallo {{username}},\n\nim Profil \"{{profile_name}}\" gab es eine Änderung:\n\nTyp:      {{event_type}}\nDurch:    {{actor}}\nDetails:  {{details}}\nZeit:     {{date}}\n\n– {{app_name}} Benachrichtigung",
        ],
        'password_reset' => [
            'subject' => '{{app_name}} – Passwort zurücksetzen',
            'body'    => "Hallo {{username}},\n\nes wurde eine Passwortzurücksetzung angefordert.\n\nKlicke auf den folgenden Link:\n\n{{reset_url}}\n\nDieser Link ist 1 Stunde gültig.\n\n– {{app_name}}",
        ],
    ];

    try {
        $stmt = $db->prepare('SELECT subject_tpl, body_tpl FROM email_templates WHERE template_key = :k');
        $stmt->execute(['k' => $key]);
        $row = $stmt->fetch();
        if ($row) {
            return ['subject' => $row['subject_tpl'], 'body' => $row['body_tpl']];
        }
    } catch (\Exception $e) {
        // Table might not exist yet
    }

    return $defaults[$key] ?? ['subject' => '', 'body' => ''];
}
