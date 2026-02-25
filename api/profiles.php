<?php
/**
 * ECCM – Profile API (AJAX)
 *
 * Actions:
 *   load            – load all accessible profiles for current user
 *   save            – save current profile data
 *   create_profile  – create a new profile with permissions
 *   rename_profile  – rename a profile
 *   delete_profile  – delete a profile
 *   duplicate_profile – duplicate a profile
 *   switch_profile  – set active profile
 *   get_permissions  – get permissions for a profile
 *   set_permissions  – set permissions for a profile (owner/manage only)
 *   list_users       – list users for permission assignment
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

// Read JSON body
$raw = file_get_contents('php://input');
$input = json_decode($raw, true) ?? [];
$action = $input['action'] ?? $_GET['action'] ?? '';

try {
    $db = getDB();

    switch ($action) {

        /* ── LOAD ─────────────────────────────────────────── */
        case 'load':
            // Get all profiles the user owns or has view permission for
            $sql = '
                SELECT p.id, p.name, p.data, p.owner_id, u.username AS owner_name,
                       pp.can_view, pp.can_patch, pp.can_add_patch,
                       pp.can_edit_device, pp.can_add_device, pp.can_delete, pp.can_manage
                FROM profiles p
                LEFT JOIN profile_permissions pp ON pp.profile_id = p.id AND pp.user_id = :uid1
                LEFT JOIN users u ON u.id = p.owner_id
                WHERE p.owner_id = :uid2 OR pp.can_view = 1' .
                ($isAdmin ? ' OR 1=1' : '') . '
                ORDER BY p.name
            ';
            $stmt = $db->prepare($sql);
            $stmt->execute(['uid1' => $userId, 'uid2' => $userId]);

            $profiles = [];
            $profileMeta = [];
            while ($row = $stmt->fetch()) {
                $isOwner = ((int)$row['owner_id'] === $userId);
                $profiles[$row['name']] = json_decode($row['data'], true);
                $profileMeta[$row['name']] = [
                    'id'        => (int)$row['id'],
                    'owner'     => $row['owner_name'],
                    'owner_id'  => (int)$row['owner_id'],
                    'is_owner'  => $isOwner,
                    'perms'     => $isOwner || $isAdmin ? [
                        'can_view'=>1,'can_patch'=>1,'can_add_patch'=>1,
                        'can_edit_device'=>1,'can_add_device'=>1,'can_delete'=>1,'can_manage'=>1
                    ] : [
                        'can_view'       => (int)($row['can_view'] ?? 1),
                        'can_patch'      => (int)($row['can_patch'] ?? 0),
                        'can_add_patch'  => (int)($row['can_add_patch'] ?? 0),
                        'can_edit_device'=> (int)($row['can_edit_device'] ?? 0),
                        'can_add_device' => (int)($row['can_add_device'] ?? 0),
                        'can_delete'     => (int)($row['can_delete'] ?? 0),
                        'can_manage'     => (int)($row['can_manage'] ?? 0),
                    ]
                ];
            }

            // Get active profile
            $stmt2 = $db->prepare('SELECT p.name FROM user_active_profile ua JOIN profiles p ON p.id = ua.profile_id WHERE ua.user_id = :uid');
            $stmt2->execute(['uid' => $userId]);
            $activeRow = $stmt2->fetch();
            $current = $activeRow ? $activeRow['name'] : null;

            // If no profiles, create default
            if (empty($profiles)) {
                $default = ['devices'=>[],'links'=>[],'portAliases'=>new \stdClass,'reservedPorts'=>new \stdClass,'portSpeeds'=>new \stdClass,'portVlans'=>new \stdClass,'portLinkedTo'=>new \stdClass];
                $db->prepare('INSERT INTO profiles (owner_id, name, data) VALUES (:uid, :name, :data)')
                   ->execute(['uid' => $userId, 'name' => 'Default', 'data' => json_encode($default)]);
                $pid = (int)$db->lastInsertId();
                $profiles['Default'] = $default;
                $profileMeta['Default'] = [
                    'id'=>$pid, 'owner'=>currentUsername(), 'owner_id'=>$userId,
                    'is_owner'=>true,
                    'perms'=>['can_view'=>1,'can_patch'=>1,'can_add_patch'=>1,'can_edit_device'=>1,'can_add_device'=>1,'can_delete'=>1,'can_manage'=>1]
                ];
                $current = 'Default';
                $db->prepare('INSERT INTO user_active_profile (user_id, profile_id) VALUES (:uid, :pid) ON DUPLICATE KEY UPDATE profile_id = :pid2')
                   ->execute(['uid' => $userId, 'pid' => $pid, 'pid2' => $pid]);
            }
            if (!$current) $current = array_key_first($profiles);

            // Load settings
            $stmt3 = $db->prepare('SELECT settings FROM user_settings WHERE user_id = :uid');
            $stmt3->execute(['uid' => $userId]);
            $sr = $stmt3->fetch();
            $settings = $sr ? json_decode($sr['settings'], true) : ['maxPorts'=>512,'enablePortRename'=>false];

            echo json_encode([
                'ok'          => true,
                'current'     => $current,
                'profiles'    => $profiles,
                'profileMeta' => $profileMeta,
                'settings'    => $settings,
            ]);
            break;

        /* ── SAVE profile data ────────────────────────────── */
        case 'save':
            $profileName = $input['profileName'] ?? '';
            $data        = $input['data'] ?? null;
            $settings    = $input['settings'] ?? null;

            if ($profileName && $data !== null) {
                // Find profile and check write perms
                $perm = getEffectivePerms($db, $userId, $profileName, $isAdmin);
                if (!$perm) {
                    echo json_encode(['error' => 'Profil nicht gefunden']);
                    exit;
                }
                // Need at least one write perm to save
                $canWrite = $perm['can_patch'] || $perm['can_add_patch'] || $perm['can_edit_device'] || $perm['can_add_device'] || $perm['can_delete'];
                if (!$canWrite) {
                    echo json_encode(['error' => 'Keine Berechtigung zum Speichern']);
                    exit;
                }

                $profileId = $perm['profile_id'];

                // Load old data for diff comparison
                $stmtOld = $db->prepare('SELECT data FROM profiles WHERE id = :pid');
                $stmtOld->execute(['pid' => $profileId]);
                $oldJson = $stmtOld->fetchColumn();
                $oldData = $oldJson ? json_decode($oldJson, true) : null;

                // Save new data
                $db->prepare('UPDATE profiles SET data = :data WHERE id = :pid')
                   ->execute(['data' => json_encode($data), 'pid' => $profileId]);

                // Detect changes and send notifications
                if ($oldData !== null) {
                    require_once __DIR__ . '/../includes/notifications.php';
                    $changes = detectChanges($oldData, $data);
                    if (!empty($changes)) {
                        error_log("ECCM: " . count($changes) . " change(s) detected in profile '$profileName' (id=$profileId) by user $userId");
                    }
                    foreach ($changes as $ch) {
                        error_log("ECCM: Notifying for {$ch['type']}: {$ch['details']}");
                        notifyProfileChange($profileId, $userId, $ch['type'], $ch['details']);
                    }
                }
            }

            // Save settings (always allowed for own settings)
            if ($settings !== null) {
                $sJson = json_encode($settings);
                $db->prepare('INSERT INTO user_settings (user_id, settings) VALUES (:uid, :s) ON DUPLICATE KEY UPDATE settings = VALUES(settings)')
                   ->execute(['uid' => $userId, 's' => $sJson]);
            }

            echo json_encode(['ok' => true]);
            break;

        /* ── CREATE PROFILE ───────────────────────────────── */
        case 'create_profile':
            $name = trim($input['name'] ?? '');
            $permsToSet = $input['permissions'] ?? [];

            if ($name === '') {
                echo json_encode(['error' => 'Profilname erforderlich']);
                exit;
            }

            $default = ['devices'=>[],'links'=>[],'portAliases'=>new \stdClass,'reservedPorts'=>new \stdClass,'portSpeeds'=>new \stdClass,'portVlans'=>new \stdClass,'portLinkedTo'=>new \stdClass];
            $db->prepare('INSERT INTO profiles (owner_id, name, data) VALUES (:uid, :name, :data)')
               ->execute(['uid' => $userId, 'name' => $name, 'data' => json_encode($default)]);
            $pid = (int)$db->lastInsertId();

            // Set active
            $db->prepare('INSERT INTO user_active_profile (user_id, profile_id) VALUES (:uid, :pid) ON DUPLICATE KEY UPDATE profile_id = VALUES(profile_id)')
               ->execute(['uid' => $userId, 'pid' => $pid]);

            // Save permissions for other users
            if (!empty($permsToSet)) {
                $ins = $db->prepare('INSERT INTO profile_permissions (profile_id, user_id, can_view, can_patch, can_add_patch, can_edit_device, can_add_device, can_delete, can_manage) VALUES (:pid, :uid, :v, :p, :ap, :ed, :ad, :del, :man)');
                foreach ($permsToSet as $p) {
                    $targetUid = (int)($p['user_id'] ?? 0);
                    if ($targetUid <= 0 || $targetUid === $userId) continue;
                    $ins->execute([
                        'pid' => $pid, 'uid' => $targetUid,
                        'v'   => (int)($p['can_view'] ?? 1),
                        'p'   => (int)($p['can_patch'] ?? 0),
                        'ap'  => (int)($p['can_add_patch'] ?? 0),
                        'ed'  => (int)($p['can_edit_device'] ?? 0),
                        'ad'  => (int)($p['can_add_device'] ?? 0),
                        'del' => (int)($p['can_delete'] ?? 0),
                        'man' => (int)($p['can_manage'] ?? 0),
                    ]);
                }
            }

            echo json_encode(['ok' => true, 'id' => $pid, 'name' => $name]);
            break;

        /* ── RENAME PROFILE ───────────────────────────────── */
        case 'rename_profile':
            $oldName = $input['oldName'] ?? '';
            $newName = trim($input['newName'] ?? '');
            if ($oldName === '' || $newName === '') {
                echo json_encode(['error' => 'Name erforderlich']);
                exit;
            }
            $perm = getEffectivePerms($db, $userId, $oldName, $isAdmin);
            if (!$perm || !$perm['is_owner']) {
                echo json_encode(['error' => 'Nur der Eigentümer kann umbenennen']);
                exit;
            }
            $db->prepare('UPDATE profiles SET name = :name WHERE id = :pid')
               ->execute(['name' => $newName, 'pid' => $perm['profile_id']]);
            echo json_encode(['ok' => true]);
            break;

        /* ── DELETE PROFILE ───────────────────────────────── */
        case 'delete_profile':
            $name = $input['name'] ?? '';
            $perm = getEffectivePerms($db, $userId, $name, $isAdmin);
            if (!$perm || (!$perm['is_owner'] && !$isAdmin)) {
                echo json_encode(['error' => 'Nur der Eigentümer kann löschen']);
                exit;
            }
            $db->prepare('DELETE FROM profiles WHERE id = :pid')
               ->execute(['pid' => $perm['profile_id']]);
            echo json_encode(['ok' => true]);
            break;

        /* ── DUPLICATE PROFILE ────────────────────────────── */
        case 'duplicate_profile':
            $srcName = $input['sourceName'] ?? '';
            $newName = trim($input['newName'] ?? '');
            $perm = getEffectivePerms($db, $userId, $srcName, $isAdmin);
            if (!$perm) {
                echo json_encode(['error' => 'Quellprofil nicht gefunden']);
                exit;
            }
            $stmt = $db->prepare('SELECT data FROM profiles WHERE id = :pid');
            $stmt->execute(['pid' => $perm['profile_id']]);
            $srcData = $stmt->fetchColumn();

            $db->prepare('INSERT INTO profiles (owner_id, name, data) VALUES (:uid, :name, :data)')
               ->execute(['uid' => $userId, 'name' => $newName, 'data' => $srcData]);
            $pid = (int)$db->lastInsertId();

            $db->prepare('INSERT INTO user_active_profile (user_id, profile_id) VALUES (:uid, :pid) ON DUPLICATE KEY UPDATE profile_id = VALUES(profile_id)')
               ->execute(['uid' => $userId, 'pid' => $pid]);

            echo json_encode(['ok' => true, 'id' => $pid]);
            break;

        /* ── SWITCH ACTIVE PROFILE ────────────────────────── */
        case 'switch_profile':
            $name = $input['name'] ?? '';
            $perm = getEffectivePerms($db, $userId, $name, $isAdmin);
            if (!$perm) {
                echo json_encode(['error' => 'Profil nicht gefunden']);
                exit;
            }
            $db->prepare('INSERT INTO user_active_profile (user_id, profile_id) VALUES (:uid, :pid) ON DUPLICATE KEY UPDATE profile_id = VALUES(profile_id)')
               ->execute(['uid' => $userId, 'pid' => $perm['profile_id']]);
            echo json_encode(['ok' => true]);
            break;

        /* ── GET PERMISSIONS ──────────────────────────────── */
        case 'get_permissions':
            $name = $input['name'] ?? $_GET['name'] ?? '';
            $perm = getEffectivePerms($db, $userId, $name, $isAdmin);
            if (!$perm || (!$perm['is_owner'] && !$perm['can_manage'] && !$isAdmin)) {
                echo json_encode(['error' => 'Keine Berechtigung']);
                exit;
            }

            // List all assigned permissions
            $stmt = $db->prepare('
                SELECT pp.*, u.username FROM profile_permissions pp
                JOIN users u ON u.id = pp.user_id
                WHERE pp.profile_id = :pid ORDER BY u.username
            ');
            $stmt->execute(['pid' => $perm['profile_id']]);
            $perms = $stmt->fetchAll();
            echo json_encode(['ok' => true, 'permissions' => $perms, 'profile_id' => $perm['profile_id']]);
            break;

        /* ── SET PERMISSIONS ──────────────────────────────── */
        case 'set_permissions':
            $name = $input['name'] ?? '';
            $permsToSet = $input['permissions'] ?? [];

            $perm = getEffectivePerms($db, $userId, $name, $isAdmin);
            if (!$perm || (!$perm['is_owner'] && !$perm['can_manage'] && !$isAdmin)) {
                echo json_encode(['error' => 'Keine Berechtigung']);
                exit;
            }

            $pid = $perm['profile_id'];

            // Delete existing (except owner cannot be removed)
            $db->prepare('DELETE FROM profile_permissions WHERE profile_id = :pid')
               ->execute(['pid' => $pid]);

            $ins = $db->prepare('INSERT INTO profile_permissions (profile_id, user_id, can_view, can_patch, can_add_patch, can_edit_device, can_add_device, can_delete, can_manage) VALUES (:pid, :uid, :v, :p, :ap, :ed, :ad, :del, :man)');
            foreach ($permsToSet as $p) {
                $targetUid = (int)($p['user_id'] ?? 0);
                if ($targetUid <= 0) continue;
                $ins->execute([
                    'pid' => $pid, 'uid' => $targetUid,
                    'v'   => (int)($p['can_view'] ?? 1),
                    'p'   => (int)($p['can_patch'] ?? 0),
                    'ap'  => (int)($p['can_add_patch'] ?? 0),
                    'ed'  => (int)($p['can_edit_device'] ?? 0),
                    'ad'  => (int)($p['can_add_device'] ?? 0),
                    'del' => (int)($p['can_delete'] ?? 0),
                    'man' => (int)($p['can_manage'] ?? 0),
                ]);
            }
            echo json_encode(['ok' => true]);
            break;

        /* ── LIST USERS (for permission picker) ───────────── */
        case 'list_users':
            $rows = $db->query('SELECT id, username, email FROM users ORDER BY username')->fetchAll();
            echo json_encode(['ok' => true, 'users' => $rows]);
            break;

        default:
            echo json_encode(['error' => 'Unbekannte Aktion: ' . $action]);
    }

} catch (PDOException $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    http_response_code(500);
    $msg = $e->getMessage();
    if (strpos($msg, 'Duplicate entry') !== false) {
        echo json_encode(['error' => 'Ein Profil mit diesem Namen existiert bereits.']);
    } else {
        echo json_encode(['error' => $msg]);
    }
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

/* ── Helper: effective permissions for a user on a profile ── */
function getEffectivePerms(PDO $db, int $userId, string $profileName, bool $isAdmin): ?array {
    $stmt = $db->prepare('
        SELECT p.id AS profile_id, p.owner_id,
               pp.can_view, pp.can_patch, pp.can_add_patch,
               pp.can_edit_device, pp.can_add_device, pp.can_delete, pp.can_manage
        FROM profiles p
        LEFT JOIN profile_permissions pp ON pp.profile_id = p.id AND pp.user_id = :uid1
        WHERE p.name = :pname AND (p.owner_id = :uid2 OR pp.can_view = 1' . ($isAdmin ? ' OR 1=1' : '') . ')
        LIMIT 1
    ');
    $stmt->execute(['uid1' => $userId, 'uid2' => $userId, 'pname' => $profileName]);
    $row = $stmt->fetch();
    if (!$row) return null;

    $isOwner = ((int)$row['owner_id'] === $userId);
    return [
        'profile_id'     => (int)$row['profile_id'],
        'is_owner'       => $isOwner || $isAdmin,
        'can_view'       => $isOwner || $isAdmin ? 1 : (int)($row['can_view'] ?? 0),
        'can_patch'      => $isOwner || $isAdmin ? 1 : (int)($row['can_patch'] ?? 0),
        'can_add_patch'  => $isOwner || $isAdmin ? 1 : (int)($row['can_add_patch'] ?? 0),
        'can_edit_device'=> $isOwner || $isAdmin ? 1 : (int)($row['can_edit_device'] ?? 0),
        'can_add_device' => $isOwner || $isAdmin ? 1 : (int)($row['can_add_device'] ?? 0),
        'can_delete'     => $isOwner || $isAdmin ? 1 : (int)($row['can_delete'] ?? 0),
        'can_manage'     => $isOwner || $isAdmin ? 1 : (int)($row['can_manage'] ?? 0),
    ];
}

/**
 * Compare old and new profile data, return list of changes for notifications.
 */
function detectChanges(array $old, array $new): array {
    $changes = [];

    // Safe string helper – converts any value to a safe string for comparison/concatenation
    $s = function($v) { return is_array($v) || is_object($v) ? json_encode($v) : (string)$v; };

    $oldDevices = $old['devices'] ?? [];
    $newDevices = $new['devices'] ?? [];
    $oldLinks   = $old['links'] ?? [];
    $newLinks   = $new['links'] ?? [];

    // Index devices by ID
    $oldDevMap = [];
    foreach ($oldDevices as $d) { if (isset($d['id'])) $oldDevMap[$s($d['id'])] = $d; }
    $newDevMap = [];
    foreach ($newDevices as $d) { if (isset($d['id'])) $newDevMap[$s($d['id'])] = $d; }

    // Detect added devices
    foreach ($newDevMap as $id => $d) {
        if (!isset($oldDevMap[$id])) {
            $changes[] = ['type' => 'device_add', 'details' => 'Neues Gerät: "' . $s($d['name'] ?? $id) . '" (' . $s($d['ports'] ?? '?') . ' Ports)'];
        }
    }

    // Detect deleted devices
    foreach ($oldDevMap as $id => $d) {
        if (!isset($newDevMap[$id])) {
            $changes[] = ['type' => 'device_change', 'details' => 'Gerät gelöscht: "' . $s($d['name'] ?? $id) . '"'];
        }
    }

    // Detect changed devices (name, ports, color)
    foreach ($newDevMap as $id => $d) {
        if (!isset($oldDevMap[$id])) continue;
        $od = $oldDevMap[$id];
        $diffs = [];
        if ($s($od['name'] ?? '') !== $s($d['name'] ?? '')) $diffs[] = 'Name: "' . $s($od['name'] ?? '') . '" → "' . $s($d['name'] ?? '') . '"';
        if (($od['ports'] ?? 0) != ($d['ports'] ?? 0)) $diffs[] = 'Ports: ' . $s($od['ports'] ?? 0) . ' → ' . $s($d['ports'] ?? 0);
        if ($s($od['color'] ?? '') !== $s($d['color'] ?? '')) $diffs[] = 'Farbe geändert';
        if (!empty($diffs)) {
            $changes[] = ['type' => 'device_change', 'details' => 'Gerät "' . $s($d['name'] ?? $id) . '" geändert: ' . implode(', ', $diffs)];
        }
    }

    // ── Link handling ──
    // Links have structure: {id, a:{deviceId, port, sub}, b:{deviceId, port, sub}}
    // Helper to extract endpoint info safely
    $endpointKey = function($ep) {
        if (!is_array($ep)) return '?:?';
        return ($ep['deviceId'] ?? '?') . ':' . ($ep['port'] ?? '?') . ':' . ($ep['sub'] ?? '');
    };
    $linkKey = function($l) use ($endpointKey) {
        $a = $endpointKey($l['a'] ?? []);
        $b = $endpointKey($l['b'] ?? []);
        // Sort to make comparison order-independent
        return ($a < $b) ? "$a-$b" : "$b-$a";
    };

    $oldLinkSet = [];
    foreach ($oldLinks as $l) { if (is_array($l)) $oldLinkSet[$linkKey($l)] = $l; }
    $newLinkSet = [];
    foreach ($newLinks as $l) { if (is_array($l)) $newLinkSet[$linkKey($l)] = $l; }

    // Helper to describe a link endpoint nicely
    $describeEndpoint = function($ep) use ($newDevMap, $oldDevMap) {
        if (!is_array($ep)) return '?';
        $devId = $ep['deviceId'] ?? '?';
        $port = $ep['port'] ?? '?';
        $sub = $ep['sub'] ?? null;
        // Resolve device name
        $devName = $devId;
        if (isset($newDevMap[$devId])) $devName = $newDevMap[$devId]['name'] ?? $devId;
        elseif (isset($oldDevMap[$devId])) $devName = $oldDevMap[$devId]['name'] ?? $devId;
        $portStr = 'Port ' . $port;
        if ($sub !== null && $sub !== '') $portStr .= '/' . $sub;
        return $devName . ' ' . $portStr;
    };
    $describeLink = function($l) use ($describeEndpoint) {
        return $describeEndpoint($l['a'] ?? []) . ' ↔ ' . $describeEndpoint($l['b'] ?? []);
    };

    // Detect added links
    foreach ($newLinkSet as $key => $l) {
        if (!isset($oldLinkSet[$key])) {
            $changes[] = ['type' => 'patch_add', 'details' => 'Neue Verbindung: ' . $describeLink($l)];
        }
    }

    // Detect removed links
    foreach ($oldLinkSet as $key => $l) {
        if (!isset($newLinkSet[$key])) {
            $changes[] = ['type' => 'patch_change', 'details' => 'Verbindung entfernt: ' . $describeLink($l)];
        }
    }

    // Detect changes in port aliases and reserved ports (compare as JSON)
    if (json_encode($old['portAliases'] ?? []) !== json_encode($new['portAliases'] ?? [])) {
        $changes[] = ['type' => 'patch_change', 'details' => 'Port-Aliase geändert'];
    }
    if (json_encode($old['reservedPorts'] ?? []) !== json_encode($new['reservedPorts'] ?? [])) {
        $changes[] = ['type' => 'patch_change', 'details' => 'Reservierte Ports geändert'];
    }

    return $changes;
}
