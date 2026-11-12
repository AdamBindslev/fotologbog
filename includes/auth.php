<?php
/**
 * Autentificering og sessionsstyring
 */

require_once __DIR__ . '/db.php';

if (session_status() === PHP_SESSION_NONE) {
    $trustProxy = defined('TRUST_PROXY_HEADERS') && TRUST_PROXY_HEADERS === true;
    $isSecure = (
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
        (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) ||
        ($trustProxy && !empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https')
    );
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $isSecure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

/**
 * Henter klientens IP-adresse sikkert.
 * Bruger udelukkende REMOTE_ADDR for at forhindre at angribere omgår brute-force spærring
 * ved at forfalske HTTP_X_FORWARDED_FOR eller HTTP_CF_CONNECTING_IP headers.
 */
function get_client_ip(): string {
    $ip = trim((string)($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'));
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '127.0.0.1';
}

/**
 * Tjekker om IP-adressen er midlertidigt blokeret pga. for mange forkerte loginforsøg
 */
function is_ip_rate_limited(string $ip): array {
    $db = get_db();
    $lockoutSeconds = 900; // 15 minutter

    // Tjek specifik IP (maks 5 forsøg)
    $stmt = $db->prepare("SELECT attempts, last_attempt FROM login_attempts WHERE ip = :ip");
    $stmt->execute([':ip' => $ip]);
    $row = $stmt->fetch();

    if ($row) {
        $timeSinceLast = time() - (int)$row['last_attempt'];
        if ((int)$row['attempts'] >= 5) {
            if ($timeSinceLast < $lockoutSeconds) {
                $waitMinutes = (int)ceil(($lockoutSeconds - $timeSinceLast) / 60);
                return ['blocked' => true, 'wait_minutes' => $waitMinutes];
            } else {
                clear_failed_logins($ip);
            }
        }
    }

    return ['blocked' => false, 'wait_minutes' => 0];
}

/**
 * Registrerer et mislykket loginforsøg for IP-adressen
 */
function record_failed_login(string $ip): int {
    $db = get_db();
    $now = time();

    // Opdater IP-tæller
    $stmt = $db->prepare("SELECT attempts FROM login_attempts WHERE ip = :ip");
    $stmt->execute([':ip' => $ip]);
    $current = $stmt->fetchColumn();

    if ($current !== false) {
        $stmtUp = $db->prepare("UPDATE login_attempts SET attempts = attempts + 1, last_attempt = :now WHERE ip = :ip");
        $stmtUp->execute([':now' => $now, ':ip' => $ip]);
        return (int)$current + 1;
    } else {
        $stmtIn = $db->prepare("INSERT INTO login_attempts (ip, attempts, last_attempt) VALUES (:ip, 1, :now)");
        $stmtIn->execute([':ip' => $ip, ':now' => $now]);
        return 1;
    }
}

/**
 * Nulstiller fejlforsøg ved vellykket login
 */
function clear_failed_logins(string $ip): void {
    $db = get_db();
    $stmt = $db->prepare("DELETE FROM login_attempts WHERE ip = :ip");
    $stmt->execute([':ip' => $ip]);
}

/**
 * Henter den kryptografiske nøgle fra databasen
 */
function get_auth_secret(): string {
    static $secret = null;
    if ($secret !== null) {
        return $secret;
    }
    try {
        $db = get_db();
        $stmt = $db->prepare("SELECT value FROM settings WHERE key = 'auth_secret' LIMIT 1");
        $stmt->execute();
        $val = $stmt->fetchColumn();
        if (is_string($val) && strlen($val) >= 32) {
            $secret = $val;
            return $secret;
        }
        $newSec = bin2hex(random_bytes(32));
        $db->prepare("INSERT OR REPLACE INTO settings (key, value) VALUES ('auth_secret', :sec)")
           ->execute([':sec' => $newSec]);
        $secret = $newSec;
        return $secret;
    } catch (Throwable $e) {
        return AUTH_SECRET;
    }
}

/**
 * Henter aktuel token-version (bruges til at kunne ugyldiggøre gamle tokens)
 */
function get_token_version(): string {
    try {
        $db = get_db();
        $stmt = $db->prepare("SELECT value FROM settings WHERE key = 'token_version' LIMIT 1");
        $stmt->execute();
        $val = $stmt->fetchColumn();
        return $val ? (string)$val : '1';
    } catch (Throwable $e) {
        return '1';
    }
}

/**
 * Roterer token-version og auth_secret, så alle eksisterende tokens på andre enheder ugyldiggøres
 */
function revoke_all_tokens(): void {
    $db = get_db();
    $nextVer = (string)(((int)get_token_version()) + 1);
    $db->prepare("INSERT OR REPLACE INTO settings (key, value) VALUES ('token_version', :v)")
       ->execute([':v' => $nextVer]);
    $db->prepare("INSERT OR REPLACE INTO settings (key, value) VALUES ('auth_secret', :sec)")
       ->execute([':sec' => bin2hex(random_bytes(32))]);
}

/**
 * Tjekker om "Lockdown Mode" er slået til (blokerer alle nye logins)
 */
function is_lockdown_enabled(): bool {
    $db = get_db();
    $stmt = $db->prepare("SELECT value FROM settings WHERE key = 'lockdown_enabled'");
    $stmt->execute();
    $row = $stmt->fetch();
    return $row && ($row['value'] === '1' || $row['value'] === 'true');
}

/**
 * Slår "Lockdown Mode" til eller fra
 */
function set_lockdown(bool $enabled): bool {
    $db = get_db();
    $val = $enabled ? '1' : '0';
    $stmt = $db->prepare("INSERT OR REPLACE INTO settings (key, value) VALUES ('lockdown_enabled', :val)");
    return $stmt->execute([':val' => $val]);
}

/**
 * Validerer en indtastet PIN-kode.
 * VIGTIGT: Falder ALDRIG tilbage til DEFAULT_PIN!
 */
function verify_pin(string $input_pin): bool {
    $db = get_db();
    $stmt = $db->prepare("SELECT value FROM settings WHERE key = 'pin'");
    $stmt->execute();
    $row = $stmt->fetch();

    if ($row && !empty($row['value'])) {
        return password_verify($input_pin, $row['value']);
    }

    return false;
}

/**
 * Opdaterer PIN-koden i databasen (kræver min. 6 tegn)
 */
function update_pin(string $new_pin): bool {
    if (strlen(trim($new_pin)) < 6) {
        return false;
    }
    $db = get_db();
    $hash = password_hash($new_pin, PASSWORD_DEFAULT);
    $stmt = $db->prepare("INSERT OR REPLACE INTO settings (key, value) VALUES ('pin', :pin)");
    return $stmt->execute([':pin' => $hash]);
}

/**
 * Opretter en sikker 'Husk mig'-token til telefonen (gyldig i 1 år)
 */
function generate_auth_token(): string {
    $expires = time() + (86400 * 365); // 365 dage
    $version = get_token_version();
    $data = 'auth_ok:' . $version . ':' . $expires;
    $signature = hash_hmac('sha256', $data, get_auth_secret());
    return base64_encode($data . ':' . $signature);
}

/**
 * Validerer en auth-token med konstant-tids sammenligning og versions-tjek
 */
function validate_auth_token(?string $token): bool {
    if (empty($token)) {
        return false;
    }
    $decoded = base64_decode($token, true);
    if (!$decoded) {
        return false;
    }
    $parts = explode(':', $decoded);
    if (count($parts) !== 4) {
        return false;
    }
    [$prefix, $version, $expires, $signature] = $parts;
    if ($prefix !== 'auth_ok') {
        return false;
    }
    if (!hash_equals(get_token_version(), (string)$version)) {
        return false;
    }
    if ((int)$expires < time()) {
        return false; // Udløbet
    }
    $data = $prefix . ':' . $version . ':' . $expires;
    $expectedSignature = hash_hmac('sha256', $data, get_auth_secret());
    return hash_equals($expectedSignature, $signature);
}

/**
 * Sætter eller sletter logbog_token-cookien ensartet
 */
function set_auth_cookie(string $token, int $expiresTimestamp): void {
    $trustProxy = defined('TRUST_PROXY_HEADERS') && TRUST_PROXY_HEADERS === true;
    $isSecure = (
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
        (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) ||
        ($trustProxy && !empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https')
    );
    setcookie('logbog_token', $token, [
        'expires' => $expiresTimestamp,
        'path' => '/',
        'secure' => $isSecure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
}

/**
 * Henter eller opretter et sessions-bundet CSRF-token
 */
function get_csrf_token(): string {
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validerer CSRF-token fra X-CSRF-Token header eller POST/JSON body
 */
function verify_csrf_token(?string $providedToken = null): bool {
    $expected = $_SESSION['csrf_token'] ?? '';
    if ($expected === '') {
        return false;
    }
    $token = $providedToken ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if ($token === '') {
        return false;
    }
    return hash_equals($expected, (string)$token);
}

/**
 * Tjekker om den aktuelle anmoder er logget ind (via session, HttpOnly-cookie eller Authorization Bearer)
 */
function is_authenticated(): bool {
    // 1. Session check
    if (!empty($_SESSION['logged_in'])) {
        return true;
    }

    // 2. Cookie check
    if (!empty($_COOKIE['logbog_token']) && validate_auth_token($_COOKIE['logbog_token'])) {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
        $_SESSION['logged_in'] = true;
        return true;
    }

    // 3. Authorization Header check (tjekker standard, redirect og apache getallheaders)
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
    if (empty($authHeader) && function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    }
    if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        $bearerToken = trim($matches[1]);
        if (validate_auth_token($bearerToken)) {
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_regenerate_id(true);
            }
            $_SESSION['logged_in'] = true;
            return true;
        }
    }

    return false;
}

/**
 * Stopper udførsel hvis brugeren ikke er logget ind
 */
function require_auth(bool $is_api = false): void {
    if (!is_authenticated()) {
        if ($is_api) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(401);
            echo json_encode(['error' => 'Ikke autoriseret']);
            exit;
        } else {
            header('Location: ' . BASE_URL . '/app/');
            exit;
        }
    }
}
