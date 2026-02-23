<?php
/**
 * ECCM – Database Connection (PDO)
 */

require_once __DIR__ . '/config.php';

function getDB(): PDO {
    global $db_config;
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $db_config['host'],
        $db_config['port'],
        $db_config['dbname'],
        $db_config['charset']
    );

    $pdo = new PDO($dsn, $db_config['username'], $db_config['password'], [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
    return $pdo;
}

/**
 * Test a database connection with given params (used in admin settings).
 */
function testDBConnection(string $host, int $port, string $dbname, string $user, string $pass): bool {
    try {
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $host, $port, $dbname);
        new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        return true;
    } catch (Exception $e) {
        return false;
    }
}
