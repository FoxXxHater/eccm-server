<?php
/**
 * ECCM – Authentication Helpers
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/config.php';

function startSecureSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        // Compatible with PHP 7.0+
        session_set_cookie_params(604800, '/', '', false, true); // 7 days
        session_start();
    }
}

function isLoggedIn(): bool {
    startSecureSession();
    return !empty($_SESSION['user_id']);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function requireAdmin(): void {
    requireLogin();
    if (($_SESSION['role'] ?? '') !== 'admin') {
        http_response_code(403);
        echo 'Zugriff verweigert – Nur für Administratoren.';
        exit;
    }
}

function currentUserId(): int {
    return (int)($_SESSION['user_id'] ?? 0);
}

function currentUserRole(): string {
    return $_SESSION['role'] ?? 'user';
}

function currentUsername(): string {
    return $_SESSION['username'] ?? '';
}

/**
 * Authenticate user by username/email + password.
 * Returns user row on success, false on failure.
 */
function authenticate(string $login, string $password) {
    $db = getDB();
    $stmt = $db->prepare('SELECT * FROM users WHERE username = :login1 OR email = :login2 LIMIT 1');
    $stmt->execute(['login1' => $login, 'login2' => $login]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        return $user;
    }
    return false;
}

/**
 * Log user in (set session).
 */
function loginUser(array $user): void {
    startSecureSession();
    session_regenerate_id(true);
    $_SESSION['user_id']  = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email']    = $user['email'];
    $_SESSION['role']     = $user['role'];
}

function logout(): void {
    startSecureSession();
    $_SESSION = [];
    session_destroy();
}

/**
 * Create a password-reset token and send email.
 */
function createPasswordResetToken(string $email): bool {
    global $mail_config, $app_config;
    $db = getDB();

    $stmt = $db->prepare('SELECT id, username FROM users WHERE email = :email LIMIT 1');
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();
    if (!$user) return false; // don't reveal if email exists

    // Invalidate old tokens
    $db->prepare('UPDATE password_resets SET used = 1 WHERE user_id = :uid AND used = 0')
       ->execute(['uid' => $user['id']]);

    $token = bin2hex(random_bytes(32));
    $lifetime = (int)($app_config['token_lifetime'] ?? 3600);

    $db->prepare('INSERT INTO password_resets (user_id, token, expires_at) VALUES (:uid, :token, DATE_ADD(NOW(), INTERVAL ' . $lifetime . ' SECOND))')
       ->execute(['uid' => $user['id'], 'token' => $token]);

    // Build reset URL
    $base = !empty($mail_config['base_url']) ? $mail_config['base_url'] : '';
    if ($base === '') {
        $proto = 'http';
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') $proto = 'https';
        elseif (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) $proto = $_SERVER['HTTP_X_FORWARDED_PROTO'];
        elseif (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on') $proto = 'https';
        $host = $_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? 'localhost');
        $dir = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
        $base = $proto . '://' . $host . $dir;
    }
    $base = rtrim($base, '/');
    $resetUrl = $base . '/reset_password.php?token=' . $token;

    // Send email using template
    require_once __DIR__ . '/mailer.php';
    require_once __DIR__ . '/notifications.php';
    require_once __DIR__ . '/i18n.php';

    $tpl = loadEmailTemplate($db, 'password_reset');
    $replacements = [
        '{{username}}'   => $user['username'],
        '{{reset_url}}'  => $resetUrl,
        '{{app_name}}'   => getAppName(),
        '{{base_url}}'   => $base,
    ];

    $subject = str_replace(array_keys($replacements), array_values($replacements), $tpl['subject']);
    $body    = str_replace(array_keys($replacements), array_values($replacements), $tpl['body']);

    return sendMail($email, $subject, $body);
}

/**
 * Validate token and reset password.
 */
function resetPasswordWithToken(string $token, string $newPassword): bool {
    $db = getDB();
    $token = trim(preg_replace('/\s+/', '', $token));

    // Check token exists and is unused
    $stmt = $db->prepare('SELECT * FROM password_resets WHERE token = :token AND used = 0 LIMIT 1');
    $stmt->execute(['token' => $token]);
    $row = $stmt->fetch();
    if (!$row) return false;

    // Check expiry with timezone fallback
    $stmt2 = $db->prepare('SELECT 
        (expires_at > NOW()) AS not_expired_by_expiry,
        (created_at > DATE_SUB(NOW(), INTERVAL 2 HOUR)) AS not_expired_by_created
        FROM password_resets WHERE id = :id');
    $stmt2->execute(['id' => $row['id']]);
    $check = $stmt2->fetch();
    if (!$check || (!$check['not_expired_by_expiry'] && !$check['not_expired_by_created'])) return false;

    $hash = password_hash($newPassword, PASSWORD_DEFAULT);
    $db->prepare('UPDATE users SET password = :pw WHERE id = :uid')
       ->execute(['pw' => $hash, 'uid' => $row['user_id']]);

    $db->prepare('UPDATE password_resets SET used = 1 WHERE id = :id')
       ->execute(['id' => $row['id']]);

    return true;
}

/**
 * CSRF helpers
 */
function csrfToken(): string {
    startSecureSession();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf(string $token): bool {
    return hash_equals(csrfToken(), $token);
}
