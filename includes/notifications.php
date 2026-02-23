<?php
/**
 * ECCM – Notification System
 * 
 * Sends email notifications to subscribed users when profiles/devices change.
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/mailer.php';
require_once __DIR__ . '/config.php';

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

    while ($sub = $stmt3->fetch()) {
        $subject = "ECCM – {$eventLabels[$eventType]} in \"{$profile['name']}\"";
        $body  = "Hallo {$sub['username']},\n\n";
        $body .= "Im Profil \"{$profile['name']}\" gab es eine Änderung:\n\n";
        $body .= "Typ:      {$eventLabels[$eventType]}\n";
        $body .= "Durch:    {$actor}\n";
        $body .= "Details:  {$details}\n\n";
        $body .= "– ECCM Benachrichtigung";

        // Send async (don't block)
        try {
            sendMail($sub['email'], $subject, $body);
        } catch (\Exception $e) {
            error_log("ECCM Notification failed for {$sub['email']}: " . $e->getMessage());
        }
    }
}
