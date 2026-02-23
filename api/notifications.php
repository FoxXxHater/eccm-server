<?php
/**
 * ECCM – Notification Subscription API
 *
 * Actions:
 *   get_subscriptions  – list user's notification subscriptions
 *   save_subscriptions – update subscriptions for a profile
 *   get_all_profiles   – list all profiles user has access to (for subscription picker)
 */
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/auth.php';
startSecureSession();

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Nicht angemeldet']);
    exit;
}

$userId = currentUserId();
$isAdmin = (currentUserRole() === 'admin');

$raw = file_get_contents('php://input');
$input = json_decode($raw, true) ?? [];
$action = $input['action'] ?? $_GET['action'] ?? '';

try {
    $db = getDB();

    // Ensure table exists (auto-migrate)
    $db->exec("CREATE TABLE IF NOT EXISTS notification_subscriptions (
        id              INT AUTO_INCREMENT PRIMARY KEY,
        user_id         INT NOT NULL,
        profile_id      INT NOT NULL,
        on_device_change TINYINT(1) NOT NULL DEFAULT 0,
        on_device_add    TINYINT(1) NOT NULL DEFAULT 0,
        on_patch_change  TINYINT(1) NOT NULL DEFAULT 0,
        on_patch_add     TINYINT(1) NOT NULL DEFAULT 0,
        FOREIGN KEY (user_id)    REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (profile_id) REFERENCES profiles(id) ON DELETE CASCADE,
        UNIQUE KEY uq_user_profile_notif (user_id, profile_id)
    ) ENGINE=InnoDB");

    switch ($action) {

        /* ── GET subscriptions for current user ─── */
        case 'get_subscriptions':
            $stmt = $db->prepare('
                SELECT ns.*, p.name AS profile_name, u.username AS owner_name
                FROM notification_subscriptions ns
                JOIN profiles p ON p.id = ns.profile_id
                JOIN users u ON u.id = p.owner_id
                WHERE ns.user_id = :uid
                ORDER BY p.name
            ');
            $stmt->execute(['uid' => $userId]);
            $subs = $stmt->fetchAll();
            echo json_encode(['ok' => true, 'subscriptions' => $subs]);
            break;

        /* ── SAVE subscriptions ─────────────────── */
        case 'save_subscriptions':
            $subs = $input['subscriptions'] ?? [];

            $db->beginTransaction();

            // Delete all current subscriptions for this user
            $db->prepare('DELETE FROM notification_subscriptions WHERE user_id = :uid')
               ->execute(['uid' => $userId]);

            // Insert new ones
            $ins = $db->prepare('INSERT INTO notification_subscriptions 
                (user_id, profile_id, on_device_change, on_device_add, on_patch_change, on_patch_add) 
                VALUES (:uid, :pid, :dc, :da, :pc, :pa)');

            foreach ($subs as $s) {
                $pid = (int)($s['profile_id'] ?? 0);
                if ($pid <= 0) continue;
                // Check any flag is set
                $dc = (int)($s['on_device_change'] ?? 0);
                $da = (int)($s['on_device_add'] ?? 0);
                $pc = (int)($s['on_patch_change'] ?? 0);
                $pa = (int)($s['on_patch_add'] ?? 0);
                if (!$dc && !$da && !$pc && !$pa) continue; // skip empty
                $ins->execute([
                    'uid' => $userId, 'pid' => $pid,
                    'dc' => $dc, 'da' => $da, 'pc' => $pc, 'pa' => $pa
                ]);
            }

            $db->commit();
            echo json_encode(['ok' => true]);
            break;

        /* ── GET all accessible profiles ────────── */
        case 'get_all_profiles':
            $sql = '
                SELECT p.id, p.name, u.username AS owner_name
                FROM profiles p
                JOIN users u ON u.id = p.owner_id
                LEFT JOIN profile_permissions pp ON pp.profile_id = p.id AND pp.user_id = :uid1
                WHERE p.owner_id = :uid2 OR pp.can_view = 1' .
                ($isAdmin ? ' OR 1=1' : '') . '
                ORDER BY p.name
            ';
            $stmt = $db->prepare($sql);
            $stmt->execute(['uid1' => $userId, 'uid2' => $userId]);
            $profiles = $stmt->fetchAll();
            echo json_encode(['ok' => true, 'profiles' => $profiles]);
            break;

        default:
            echo json_encode(['error' => 'Unbekannte Aktion: ' . $action]);
    }

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
