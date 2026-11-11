<?php
/**
 * Autentificering og sessionsstyring
 */

require_once __DIR__ . '/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Henter klientens IP-adresse
 */
function get_client_ip(): string {
    $headers = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
    foreach ($headers as $h) {
        if (!empty($_SERVER[$h])) {
            $ips = explode(',', $_SERVER[$h]);
            return trim($ips[0]);
        }
    }
    return '127.0.0.1';
}

/**
 * Tjekker om IP-adressen er blokeret pga. for mange forkerte loginforsøg (5 forsøg inden for 15 min)
 */
function is_ip_rate_limited(string $ip): array {
    $db = get_db();
    $stmt = $db->prepare("SELECT attempts, last_attempt FROM login_attempts WHERE ip = :ip");
    $stmt->execute([':ip' => $ip]);
    $row = $stmt->fetch();

    if ($row) {
        $lockoutSeconds = 900; // 15 minutter
        $timeSinceLast = time() - (int)$row['last_attempt'];
        if ((int)$row['attempts'] >= 5) {
            if ($timeSinceLast < $lockoutSeconds) {
                $waitMinutes = ceil(($lockoutSeconds - $timeSinceLast) / 60);
                return ['blocked' => true, 'wait_minutes' => $waitMinutes];
            } else {
                // Tiden er udløbet, nulstil
                clear_failed_logins($ip);
            }
        }
    }
    return ['blocked' => false, 'wait_minutes' => 0];
}

/**
 * Registrerer et mislykket loginforsøg
 */
function record_failed_login(string $ip): int {
    $db = get_db();
    $stmt = $db->prepare("
        INSERT INTO login_attempts (ip, attempts, last_attempt) 
        VALUES (:ip, 1, :now)
        ON CONFLICT(ip) DO UPDATE SET 
            attempts = attempts + 1,
            last_attempt = :now
    ");
    $stmt->execute([':ip' => $ip, ':now' => time()]);

    $stmtCheck = $db->prepare("SELECT attempts FROM login_attempts WHERE ip = :ip");
    $stmtCheck->execute([':ip' => $ip]);
    return (int)$stmtCheck->fetchColumn();
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
    $stmt = $db->prepare("
        INSERT INTO settings (key, value) 
        VALUES ('lockdown_enabled', :val) 
        ON CONFLICT(key) DO UPDATE SET value = :val
    ");
    return $stmt->execute([':val' => $val]);
}

/**
 * Validerer en indtastet PIN-kode
 */
function verify_pin(string $input_pin): bool {
    $db = get_db();
    $stmt = $db->prepare("SELECT value FROM settings WHERE key = 'pin'");
    $stmt->execute();
    $row = $stmt->fetch();

    if ($row && !empty($row['value'])) {
        // Tjek om det matcher hashed PIN
        if (password_verify($input_pin, $row['value'])) {
            return true;
        }
    }

    // Fallback hvis standard-PIN er sat i config.php
    if ($input_pin === DEFAULT_PIN) {
        return true;
    }

    return false;
}

/**
 * Opdaterer PIN-koden i databasen
 */
function update_pin(string $new_pin): bool {
    if (strlen(trim($new_pin)) < 4) {
        return false;
    }
    $db = get_db();
    $hash = password_hash($new_pin, PASSWORD_DEFAULT);
    $stmt = $db->prepare("INSERT INTO settings (key, value) VALUES ('pin', :pin) ON CONFLICT(key) DO UPDATE SET value = :pin");
    return $stmt->execute([':pin' => $hash]);
}

/**
 * Opretter en sikker 'Husk mig'-token til telefonen (gyldig i 1 år)
 */
function generate_auth_token(): string {
    $expires = time() + (86400 * 365); // 365 dage
    $data = 'auth_ok:' . $expires;
    $signature = hash_hmac('sha256', $data, AUTH_SECRET);
    return base64_encode($data . ':' . $signature);
}

/**
 * Validerer en auth-token
 */
function validate_auth_token(?string $token): bool {
    if (empty($token)) {
        return false;
    }
    $decoded = base64_decode($token);
    if (!$decoded) {
        return false;
    }
    $parts = explode(':', $decoded);
    if (count($parts) !== 3) {
        return false;
    }
    [$prefix, $expires, $signature] = $parts;
    if ($prefix !== 'auth_ok') {
        return false;
    }
    if ((int)$expires < time()) {
        return false; // Udløbet
    }
    $data = $prefix . ':' . $expires;
    $expectedSignature = hash_hmac('sha256', $data, AUTH_SECRET);
    return hash_equals($expectedSignature, $signature);
}

/**
 * Tjekker om den aktuelle anmoder er logget ind (via session, cookie eller Bearer-header)
 */
function is_authenticated(): bool {
    // 1. Session check
    if (!empty($_SESSION['logged_in'])) {
        return true;
    }

    // 2. Cookie check
    if (!empty($_COOKIE['logbog_token']) && validate_auth_token($_COOKIE['logbog_token'])) {
        $_SESSION['logged_in'] = true;
        return true;
    }

    // 3. Authorization Header check (til PWA API kald)
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        $bearerToken = trim($matches[1]);
        if (validate_auth_token($bearerToken)) {
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
