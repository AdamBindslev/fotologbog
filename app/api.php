<?php
/**
 * Logbog PWA Backend API
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Tillad JSON input
$action = $_GET['action'] ?? $_POST['action'] ?? '';
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';

$rawInput = file_get_contents('php://input');
$jsonData = [];
if (str_contains($contentType, 'application/json') && !empty($rawInput)) {
    $jsonData = json_decode($rawInput, true) ?: [];
}

// Flet input sammen
$input = array_merge($_GET, $_POST, $jsonData);
$action = $input['action'] ?? $action;

function json_reply(array $data, int $code = 200): void {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// -------------------------------------------------------------
// Handlinger der IKKE kræver autentificering
// -------------------------------------------------------------

// 1. Login med PIN-kode
if ($action === 'login') {
    $clientIp = get_client_ip();

    // Tjek om Lockdown er slået til (blokerer alle nye logins)
    if (is_lockdown_enabled()) {
        json_reply(['error' => '🔒 Logins er låst for nye enheder af administratoren (Lockdown aktiv).'], 403);
    }

    // Tjek for brute-force rate-limiting
    $rateLimit = is_ip_rate_limited($clientIp);
    if ($rateLimit['blocked']) {
        json_reply(['error' => 'For mange forkerte forsøg. IP-adressen er midlertidigt blokeret i ' . $rateLimit['wait_minutes'] . ' minutter.'], 429);
    }

    $pin = trim((string)($input['pin'] ?? ''));
    if (empty($pin)) {
        json_reply(['error' => 'PIN-kode mangler'], 400);
    }

    if (verify_pin($pin)) {
        // Nulstil fejlforsøg
        clear_failed_logins($clientIp);

        $token = generate_auth_token();
        $_SESSION['logged_in'] = true;
        
        // Sæt cookie (1 år)
        $cookieOptions = [
            'expires' => time() + (86400 * 365),
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax'
        ];
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            $cookieOptions['secure'] = true;
        }
        setcookie('logbog_token', $token, $cookieOptions);

        json_reply([
            'success' => true,
            'token' => $token,
            'message' => 'Login godkendt'
        ]);
    } else {
        $attempts = record_failed_login($clientIp);
        $remaining = max(0, 5 - $attempts);
        $errMsg = 'Forkert PIN-kode';
        if ($remaining > 0) {
            $errMsg .= " ($remaining forsøg tilbage før midlertidig blokering)";
        } else {
            $errMsg .= " (Blokeret i 15 minutter)";
        }
        json_reply(['error' => $errMsg], 401);
    }
}

// 2. Tjek login-status
if ($action === 'check_auth') {
    json_reply(['authenticated' => is_authenticated()]);
}

// -------------------------------------------------------------
// Alle efterfølgende handlinger KRÆVER autentificering
// -------------------------------------------------------------
require_auth(true);

// 3. Opret nyt indlæg
if ($action === 'create_post') {
    $title = trim((string)($input['title'] ?? ''));
    $content = trim((string)($input['content'] ?? ''));
    $location = trim((string)($input['location'] ?? ''));
    $latitude = isset($input['latitude']) && is_numeric($input['latitude']) ? (float)$input['latitude'] : null;
    $longitude = isset($input['longitude']) && is_numeric($input['longitude']) ? (float)$input['longitude'] : null;
    $publishedAt = trim((string)($input['published_at'] ?? ''));

    if (empty($publishedAt)) {
        $publishedAt = date('Y-m-d H:i:s');
    } else {
        $timeVal = strtotime($publishedAt);
        $publishedAt = $timeVal ? date('Y-m-d H:i:s', $timeVal) : date('Y-m-d H:i:s');
    }

    if (empty($title)) {
        $title = 'Log ' . date('d.m.Y', strtotime($publishedAt));
    }

    // Billede kan komme som base64 (fra Canvas) eller $_FILES
    $imageInput = null;
    if (!empty($input['image_data'])) {
        $imageInput = $input['image_data'];
    } elseif (!empty($_FILES['image'])) {
        $imageInput = $_FILES['image'];
    }

    if (!$imageInput) {
        json_reply(['error' => 'Du skal uploade et billede'], 400);
    }

    try {
        $imagePath = save_image_data($imageInput);
    } catch (Exception $e) {
        json_reply(['error' => $e->getMessage()], 400);
    }

    // Håndter EXIF-data (kan sendes fra app'en eller udtrækkes fra billedfil)
    $exifAperture = trim((string)($input['exif_aperture'] ?? ''));
    $exifShutter = trim((string)($input['exif_shutter'] ?? ''));
    $exifIso = trim((string)($input['exif_iso'] ?? ''));

    if (empty($exifAperture) && empty($exifShutter) && empty($exifIso)) {
        $extractedExif = extract_exif_from_image_file(UPLOADS_DIR . '/' . $imagePath);
        $exifAperture = $extractedExif['aperture'] ?? null;
        $exifShutter = $extractedExif['shutter'] ?? null;
        $exifIso = $extractedExif['iso'] ?? null;
    }

    $slug = slugify($title);
    $db = get_db();

    // Generer et unikt, ikke-fortløbende UID til permalinks
    $uid = generate_post_uid();
    $stmtCheck = $db->prepare("SELECT COUNT(*) FROM posts WHERE uid = :uid");
    $stmtCheck->execute([':uid' => $uid]);
    while ($stmtCheck->fetchColumn() > 0) {
        $uid = generate_post_uid();
        $stmtCheck->execute([':uid' => $uid]);
    }

    $stmt = $db->prepare("
        INSERT INTO posts (uid, title, slug, content, image_path, location, latitude, longitude, published_at, exif_aperture, exif_shutter, exif_iso, created_at, updated_at)
        VALUES (:uid, :title, :slug, :content, :image_path, :location, :latitude, :longitude, :published_at, :exif_aperture, :exif_shutter, :exif_iso, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
    ");

    $success = $stmt->execute([
        ':uid' => $uid,
        ':title' => $title,
        ':slug' => $slug,
        ':content' => $content,
        ':image_path' => $imagePath,
        ':location' => $location,
        ':latitude' => $latitude,
        ':longitude' => $longitude,
        ':published_at' => $publishedAt,
        ':exif_aperture' => $exifAperture ?: null,
        ':exif_shutter' => $exifShutter ?: null,
        ':exif_iso' => $exifIso ?: null
    ]);

    if ($success) {
        $newId = (int)$db->lastInsertId();

        // Gem tags hvis angivet (array eller kommasepareret streng)
        $tagsInput = $input['tags'] ?? [];
        if (is_string($tagsInput)) {
            $tagsInput = explode(',', $tagsInput);
        }
        if (is_array($tagsInput)) {
            set_post_tags($newId, $tagsInput);
        }

        json_reply([
            'success' => true,
            'id' => $newId,
            'uid' => $uid,
            'message' => 'Indlæg oprettet!',
            'post_url' => BASE_URL . '/post.php?p=' . $uid
        ]);
    } else {
        json_reply(['error' => 'Kunne ikke gemme indlægget i databasen'], 500);
    }
}

// 4. Hent liste af indlæg (til admin-oversigt)
if ($action === 'get_posts') {
    $posts = get_recent_posts(50, 0);
    foreach ($posts as &$p) {
        $p['tags'] = get_post_tags((int)$p['id']);
        $p['exif_formatted'] = format_exif($p);
    }
    unset($p);
    json_reply(['posts' => $posts]);
}

// 5. Hent et enkelt indlæg
if ($action === 'get_post') {
    $id = (int)($input['id'] ?? 0);
    $post = get_post_by_id($id);
    if ($post) {
        $post['tags'] = get_post_tags($id);
        $post['exif_formatted'] = format_exif($post);
        json_reply(['post' => $post]);
    } else {
        json_reply(['error' => 'Indlægget findes ikke'], 404);
    }
}

// Hent alle brugte tags (til hurtig-chips i skrivefladen)
if ($action === 'get_tags') {
    $tags = get_all_tags_with_counts();
    json_reply(['tags' => $tags]);
}

// 6. Opdater et eksisterende indlæg
if ($action === 'update_post') {
    $id = (int)($input['id'] ?? 0);
    $post = get_post_by_id($id);
    if (!$post) {
        json_reply(['error' => 'Indlægget findes ikke'], 404);
    }

    $title = trim((string)($input['title'] ?? $post['title']));
    $content = trim((string)($input['content'] ?? $post['content']));
    $location = trim((string)($input['location'] ?? $post['location']));
    $publishedAt = trim((string)($input['published_at'] ?? $post['published_at']));
    $latitude = isset($input['latitude']) && is_numeric($input['latitude']) ? (float)$input['latitude'] : $post['latitude'];
    $longitude = isset($input['longitude']) && is_numeric($input['longitude']) ? (float)$input['longitude'] : $post['longitude'];

    $imagePath = $post['image_path'];
    if (!empty($input['image_data'])) {
        try {
            $imagePath = save_image_data($input['image_data']);
        } catch (Exception $e) {
            json_reply(['error' => 'Kunne ikke gemme nyt billede: ' . $e->getMessage()], 400);
        }
    }

    $db = get_db();
    $stmt = $db->prepare("
        UPDATE posts 
        SET title = :title, 
            content = :content, 
            location = :location, 
            latitude = :latitude, 
            longitude = :longitude, 
            published_at = :published_at, 
            image_path = :image_path,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = :id
    ");

    $stmt->execute([
        ':title' => $title,
        ':content' => $content,
        ':location' => $location,
        ':latitude' => $latitude,
        ':longitude' => $longitude,
        ':published_at' => $publishedAt,
        ':image_path' => $imagePath,
        ':id' => $id
    ]);

    // Opdater tags hvis sendt med
    if (isset($input['tags'])) {
        $tagsInput = $input['tags'];
        if (is_string($tagsInput)) {
            $tagsInput = explode(',', $tagsInput);
        }
        if (is_array($tagsInput)) {
            set_post_tags($id, $tagsInput);
        }
    }

    // Opdater EXIF hvis sendt med
    if (isset($input['exif_aperture']) || isset($input['exif_shutter']) || isset($input['exif_iso'])) {
        $exifAperture = trim((string)($input['exif_aperture'] ?? ($post['exif_aperture'] ?? '')));
        $exifShutter = trim((string)($input['exif_shutter'] ?? ($post['exif_shutter'] ?? '')));
        $exifIso = trim((string)($input['exif_iso'] ?? ($post['exif_iso'] ?? '')));
        $stmtExif = $db->prepare("
            UPDATE posts 
            SET exif_aperture = :ap, exif_shutter = :sh, exif_iso = :iso 
            WHERE id = :id
        ");
        $stmtExif->execute([
            ':ap' => $exifAperture ?: null,
            ':sh' => $exifShutter ?: null,
            ':iso' => $exifIso ?: null,
            ':id' => $id
        ]);
    }

    json_reply(['success' => true, 'message' => 'Indlægget er opdateret']);
}

// 7. Slet et indlæg
if ($action === 'delete_post') {
    $id = (int)($input['id'] ?? 0);
    $post = get_post_by_id($id);
    if (!$post) {
        json_reply(['error' => 'Indlægget findes ikke'], 404);
    }

    // Slet billedfilen hvis den findes
    if (!empty($post['image_path'])) {
        $imgPath = UPLOADS_DIR . '/' . $post['image_path'];
        if (file_exists($imgPath)) {
            @unlink($imgPath);
        }
    }

    $db = get_db();
    $stmt = $db->prepare("DELETE FROM posts WHERE id = :id");
    $stmt->execute([':id' => $id]);

    json_reply(['success' => true, 'message' => 'Indlægget er slettet']);
}

// 8. Opdater PIN-kode
if ($action === 'update_pin') {
    $newPin = trim((string)($input['new_pin'] ?? ''));
    if (strlen($newPin) < 4) {
        json_reply(['error' => 'Ny PIN-kode skal være på mindst 4 tegn'], 400);
    }

    try {
        if (update_pin($newPin)) {
            json_reply(['success' => true, 'message' => 'PIN-koden er ændret']);
        } else {
            json_reply(['error' => 'Kunne ikke opdatere PIN-kode'], 500);
        }
    } catch (Throwable $e) {
        json_reply(['error' => 'Database fejl: ' . $e->getMessage()], 500);
    }
}

// 9. Download backup af SQLite database
if ($action === 'backup') {
    if (!file_exists(DB_PATH)) {
        json_reply(['error' => 'Databasen er ikke fundet'], 404);
    }
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="logbog_backup_' . date('Y-m-d_His') . '.db"');
    header('Content-Length: ' . filesize(DB_PATH));
    readfile(DB_PATH);
    exit;
}

// 10. Hent sikkerhedsindstillinger
if ($action === 'get_settings') {
    json_reply([
        'lockdown' => is_lockdown_enabled()
    ]);
}

// 11. Slå Lockdown til/fra
if ($action === 'toggle_lockdown') {
    $current = is_lockdown_enabled();
    $newStatus = !$current;
    set_lockdown($newStatus);
    json_reply([
        'success' => true,
        'lockdown' => $newStatus,
        'message' => $newStatus ? '🔒 Lockdown aktiveret: Alle nye logins er nu blokeret!' : '🔓 Lockdown deaktiveret: Nye logins er tilladt.'
    ]);
}

// 12. Log ud
if ($action === 'logout') {
    $_SESSION = [];
    if (session_id()) {
        session_destroy();
    }
    setcookie('logbog_token', '', [
        'expires' => time() - 3600,
        'path' => '/'
    ]);
    json_reply(['success' => true, 'message' => 'Logget ud']);
}

json_reply(['error' => 'Ukendt handling: ' . htmlspecialchars($action)], 400);
