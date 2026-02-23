<?php
/**
 * ECCM – Authentication Helpers
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/config.php';

function startSecureSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        // Compatible with PHP 7.0+
        session_set_cookie_params(0, '/', '', false, true);
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
    $expires = date('Y-m-d H:i:s', time() + ($app_config['token_lifetime'] ?? 3600));

    $db->prepare('INSERT INTO password_resets (user_id, token, expires_at) VALUES (:uid, :token, :expires)')
       ->execute(['uid' => $user['id'], 'token' => $token, 'expires' => $expires]);

    // Build reset URL
    $base = $mail_config['base_url'] ?: (
        (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') .
        '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') .
        dirname($_SERVER['SCRIPT_NAME'] ?? '')
    );
    $base = rtrim($base, '/');
    $resetUrl = $base . '/reset_password.php?token=' . $token;

    // Send email
    require_once __DIR__ . '/mailer.php';

    $subject = 'ECCM – Passwort zurücksetzen';
    $body  = "Hallo {$user['username']},\n\n";
    $body .= "Es wurde eine Passwortzurücksetzung angefordert.\n";
    $body .= "Klicke auf den folgenden Link, um dein Passwort zu ändern:\n\n";
    $body .= "$resetUrl\n\n";
    $body .= "Dieser Link ist 1 Stunde gültig.\n\n";
    $body .= "Falls du das nicht angefordert hast, ignoriere diese E-Mail.\n\n";
    $body .= "– ECCM System";

    return sendMail($email, $subject, $body);
}

/**
 * Validate token and reset password.
 */
function resetPasswordWithToken(string $token, string $newPassword): bool {
    $db = getDB();

    $stmt = $db->prepare(
        'SELECT * FROM password_resets WHERE token = :token AND used = 0 AND expires_at > NOW() LIMIT 1'
    );
    $stmt->execute(['token' => $token]);
    $row = $stmt->fetch();
    if (!$row) return false;

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
