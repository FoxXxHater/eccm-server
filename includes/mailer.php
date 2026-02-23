<?php
/**
 * ECCM – Simple SMTP Mailer (no external dependencies)
 * 
 * Supports: PLAIN / LOGIN auth, STARTTLS, fallback to PHP mail()
 */

require_once __DIR__ . '/config.php';

function sendMail(string $to, string $subject, string $body): bool {
    global $mail_config;

    $smtpHost = $mail_config['smtp_host'] ?? '';

    if ($smtpHost === '') {
        // Fallback to PHP mail()
        $headers  = "From: {$mail_config['from_name']} <{$mail_config['from_email']}>\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        return @mail($to, $subject, $body, $headers);
    }

    return sendViaSMTP($to, $subject, $body);
}

function sendViaSMTP(string $to, string $subject, string $body): bool {
    global $mail_config;

    $host = $mail_config['smtp_host'];
    $port = (int)($mail_config['smtp_port'] ?? 587);
    $user = $mail_config['smtp_user'] ?? '';
    $pass = $mail_config['smtp_pass'] ?? '';
    $from = $mail_config['from_email'] ?? 'noreply@example.com';
    $fromName = $mail_config['from_name'] ?? 'ECCM';
    $encryption = $mail_config['smtp_encryption'] ?? 'tls'; // tls, ssl, none

    try {
        $prefix = ($encryption === 'ssl') ? 'ssl://' : '';
        $socket = @fsockopen($prefix . $host, $port, $errno, $errstr, 10);
        if (!$socket) {
            error_log("ECCM SMTP: Connection failed: $errstr ($errno)");
            return false;
        }

        stream_set_timeout($socket, 10);

        // Read greeting
        $resp = smtpRead($socket);
        if (substr($resp, 0, 3) !== '220') { fclose($socket); return false; }

        // EHLO
        smtpWrite($socket, "EHLO " . gethostname());
        $resp = smtpRead($socket);

        // STARTTLS if needed
        if ($encryption === 'tls') {
            smtpWrite($socket, "STARTTLS");
            $resp = smtpRead($socket);
            if (substr($resp, 0, 3) !== '220') { fclose($socket); return false; }

            $crypto = stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT);
            if (!$crypto) {
                // Try broader method
                $crypto = stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            }
            if (!$crypto) { fclose($socket); return false; }

            // Re-EHLO after TLS
            smtpWrite($socket, "EHLO " . gethostname());
            $resp = smtpRead($socket);
        }

        // AUTH LOGIN
        if ($user !== '') {
            smtpWrite($socket, "AUTH LOGIN");
            $resp = smtpRead($socket);
            if (substr($resp, 0, 3) !== '334') { fclose($socket); return false; }

            smtpWrite($socket, base64_encode($user));
            $resp = smtpRead($socket);
            if (substr($resp, 0, 3) !== '334') { fclose($socket); return false; }

            smtpWrite($socket, base64_encode($pass));
            $resp = smtpRead($socket);
            if (substr($resp, 0, 3) !== '235') {
                error_log("ECCM SMTP: Auth failed: $resp");
                fclose($socket);
                return false;
            }
        }

        // MAIL FROM
        smtpWrite($socket, "MAIL FROM:<$from>");
        $resp = smtpRead($socket);
        if (substr($resp, 0, 3) !== '250') { fclose($socket); return false; }

        // RCPT TO
        smtpWrite($socket, "RCPT TO:<$to>");
        $resp = smtpRead($socket);
        if (substr($resp, 0, 3) !== '250') { fclose($socket); return false; }

        // DATA
        smtpWrite($socket, "DATA");
        $resp = smtpRead($socket);
        if (substr($resp, 0, 3) !== '354') { fclose($socket); return false; }

        // Build message
        $msg  = "From: $fromName <$from>\r\n";
        $msg .= "To: $to\r\n";
        $msg .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
        $msg .= "MIME-Version: 1.0\r\n";
        $msg .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $msg .= "Date: " . date('r') . "\r\n";
        $msg .= "\r\n";
        $msg .= $body;
        $msg .= "\r\n.\r\n";

        fwrite($socket, $msg);
        $resp = smtpRead($socket);
        if (substr($resp, 0, 3) !== '250') { fclose($socket); return false; }

        // QUIT
        smtpWrite($socket, "QUIT");
        fclose($socket);
        return true;

    } catch (\Exception $e) {
        error_log("ECCM SMTP Exception: " . $e->getMessage());
        return false;
    }
}

function smtpWrite($socket, string $cmd): void {
    fwrite($socket, $cmd . "\r\n");
}

function smtpRead($socket): string {
    $data = '';
    while ($line = fgets($socket, 512)) {
        $data .= $line;
        // If 4th char is space, it's the last line
        if (isset($line[3]) && $line[3] === ' ') break;
    }
    return $data;
}

/**
 * Test SMTP connection (used from admin panel)
 */
function testSMTPConnection(array $cfg): array {
    $host = $cfg['smtp_host'] ?? '';
    $port = (int)($cfg['smtp_port'] ?? 587);
    $user = $cfg['smtp_user'] ?? '';
    $pass = $cfg['smtp_pass'] ?? '';
    $encryption = $cfg['smtp_encryption'] ?? 'tls';

    if ($host === '') {
        return ['ok' => false, 'message' => 'Kein SMTP-Host angegeben. PHP mail() wird verwendet.'];
    }

    try {
        $prefix = ($encryption === 'ssl') ? 'ssl://' : '';
        $socket = @fsockopen($prefix . $host, $port, $errno, $errstr, 10);
        if (!$socket) {
            return ['ok' => false, 'message' => "Verbindung fehlgeschlagen: $errstr ($errno)"];
        }

        stream_set_timeout($socket, 10);
        $resp = smtpRead($socket);
        if (substr($resp, 0, 3) !== '220') {
            fclose($socket);
            return ['ok' => false, 'message' => 'Server antwortet nicht korrekt: ' . trim($resp)];
        }

        smtpWrite($socket, "EHLO " . gethostname());
        $resp = smtpRead($socket);

        if ($encryption === 'tls') {
            smtpWrite($socket, "STARTTLS");
            $resp = smtpRead($socket);
            if (substr($resp, 0, 3) !== '220') {
                fclose($socket);
                return ['ok' => false, 'message' => 'STARTTLS fehlgeschlagen: ' . trim($resp)];
            }
            $crypto = stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT);
            if (!$crypto) {
                $crypto = stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            }
            if (!$crypto) {
                fclose($socket);
                return ['ok' => false, 'message' => 'TLS-Verschlüsselung fehlgeschlagen.'];
            }
            smtpWrite($socket, "EHLO " . gethostname());
            $resp = smtpRead($socket);
        }

        if ($user !== '') {
            smtpWrite($socket, "AUTH LOGIN");
            $resp = smtpRead($socket);
            if (substr($resp, 0, 3) !== '334') {
                fclose($socket);
                return ['ok' => false, 'message' => 'Server unterstützt AUTH LOGIN nicht: ' . trim($resp)];
            }
            smtpWrite($socket, base64_encode($user));
            smtpRead($socket);
            smtpWrite($socket, base64_encode($pass));
            $resp = smtpRead($socket);
            if (substr($resp, 0, 3) !== '235') {
                fclose($socket);
                return ['ok' => false, 'message' => 'Authentifizierung fehlgeschlagen: ' . trim($resp)];
            }
        }

        smtpWrite($socket, "QUIT");
        fclose($socket);
        return ['ok' => true, 'message' => 'SMTP-Verbindung und Authentifizierung erfolgreich!'];

    } catch (\Exception $e) {
        return ['ok' => false, 'message' => 'Fehler: ' . $e->getMessage()];
    }
}
