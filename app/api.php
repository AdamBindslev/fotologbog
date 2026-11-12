<?php
/**
 * Logbog PWA Backend API
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';

$rawInput = file_get_contents('php://input');
$jsonData = [];
if (str_contains($contentType, 'application/json') && !empty($rawInput)) {
    $jsonData = json_decode($rawInput, true) ?: [];
}

// For POST-anmodninger ignoreres $_GET i data-payload for at forhindre parameter-forurening
if ($method === 'POST') {
    $input = array_merge($_POST, $jsonData);
} else {
    $input = $_GET;
}

$action = trim((string)($input['action'] ?? $_GET['action'] ?? ''));

function json_reply(array $data, int $code = 200): void {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Sikrer at skrivende/muterende handlinger kun kan kaldes via HTTP POST (modvirker CSRF via GET/links)
 */
function require_post_method(): void {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        json_reply(['error' => 'Denne handling kræver en POST-anmodning'], 405);
    }
}

/**
 * Sikrer gyldigt CSRF-token på muterende handlinger
 */
function require_csrf_token(array $input): void {
    $provided = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($input['csrf_token'] ?? null);
    if (!verify_csrf_token($provided)) {
        json_reply(['error' => 'Ugyldigt eller manglende sikkerhedstoken (CSRF). Genindlæs appen og prøv igen.'], 403);
    }
}

// -------------------------------------------------------------
// Handlinger der IKKE kræver autentificering
// -------------------------------------------------------------

// 1. Login med PIN-kode
if ($action === 'login') {
    require_post_method();
    $clientIp = get_client_ip();

    // Tjek om Lockdown er slået til (blokerer alle nye logins)
    if (is_lockdown_enabled()) {
        json_reply(['error' => '🔒 Logins er låst for nye enheder af administratoren (Lockdown aktiv).'], 403);
    }

    // Tjek for brute-force rate-limiting (både per IP og globalt)
    $rateLimit = is_ip_rate_limited($clientIp);
    if ($rateLimit['blocked']) {
        json_reply(['error' => 'For mange forkerte forsøg. Adgangen er midlertidigt blokeret i ' . $rateLimit['wait_minutes'] . ' minutter.'], 429);
    }

    $pin = trim((string)($input['pin'] ?? ''));
    if (empty($pin)) {
        json_reply(['error' => 'PIN-kode mangler'], 400);
    }

    if (verify_pin($pin)) {
        clear_failed_logins($clientIp);

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }

        $token = generate_auth_token();
        $_SESSION['logged_in'] = true;
        $csrfToken = get_csrf_token();
        set_auth_cookie($token, time() + (86400 * 365));

        json_reply([
            'success' => true,
            'csrf_token' => $csrfToken,
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
    $authed = is_authenticated();
    json_reply([
        'authenticated' => $authed,
        'csrf_token' => $authed ? get_csrf_token() : null
    ]);
}

// -------------------------------------------------------------
// Alle efterfølgende handlinger KRÆVER autentificering
// -------------------------------------------------------------
require_auth(true);

// 3. Opret nyt indlæg
if ($action === 'create_post') {
    require_post_method();
    require_csrf_token($input);

    $title = trim((string)($input['title'] ?? ''));
    $content = trim((string)($input['content'] ?? ''));
    $location = trim((string)($input['location'] ?? ''));
    $latitude = isset($input['latitude']) && is_numeric($input['latitude']) ? (float)$input['latitude'] : null;
    $longitude = isset($input['longitude']) && is_numeric($input['longitude']) ? (float)$input['longitude'] : null;
    $publishedAt = normalize_datetime_input($input['published_at'] ?? '');
    $status = (($input['status'] ?? 'published') === 'draft') ? 'draft' : 'published';

    if (empty($title)) {
        $title = 'Log ' . date('d.m.Y', strtotime($publishedAt));
    }

    // Billede(r) kan komme som images_data (array op til 3), image_data (base64) eller $_FILES
    $rawImages = [];
    if (!empty($input['images_data']) && is_array($input['images_data'])) {
        $rawImages = array_slice($input['images_data'], 0, 3);
    } elseif (!empty($input['image_data'])) {
        $rawImages = [$input['image_data']];
    } elseif (!empty($_FILES['image'])) {
        $rawImages = [$_FILES['image']];
    }

    if (empty($rawImages)) {
        json_reply(['error' => 'Du skal uploade mindst ét billede'], 400);
    }

    $savedPaths = [];
    try {
        foreach ($rawImages as $imgItem) {
            if (!empty($imgItem)) {
                $p = save_image_data($imgItem);
                ensure_thumbnail($p);
                $savedPaths[] = $p;
            }
        }
    } catch (Exception $e) {
        foreach ($savedPaths as $sp) {
            delete_uploaded_image($sp);
        }
        json_reply(['error' => $e->getMessage()], 400);
    }

    if (empty($savedPaths)) {
        json_reply(['error' => 'Du skal uploade mindst ét gyldigt billede'], 400);
    }
    $imagePath = $savedPaths[0];

    // Håndter EXIF-data (kan sendes fra app'en eller udtrækkes fra primær billedfil)
    $exifCamera = trim((string)($input['exif_camera'] ?? ''));
    $exifLens = trim((string)($input['exif_lens'] ?? ''));
    $exifFocal = trim((string)($input['exif_focal'] ?? ''));
    $exifAperture = trim((string)($input['exif_aperture'] ?? ''));
    $exifShutter = trim((string)($input['exif_shutter'] ?? ''));
    $exifIso = trim((string)($input['exif_iso'] ?? ''));

    if (empty($exifCamera) && empty($exifLens) && empty($exifFocal) && empty($exifAperture) && empty($exifShutter) && empty($exifIso)) {
        $extractedExif = extract_exif_from_image_file(UPLOADS_DIR . '/' . $imagePath);
        $exifCamera = $extractedExif['camera'] ?? null;
        $exifLens = $extractedExif['lens'] ?? null;
        $exifFocal = $extractedExif['focal'] ?? null;
        $exifAperture = $extractedExif['aperture'] ?? null;
        $exifShutter = $extractedExif['shutter'] ?? null;
        $exifIso = $extractedExif['iso'] ?? null;
    }

    $slug = slugify($title);
    $db = get_db();

    // Stil til Open Graph delingskort (notebook er standard)
    $validStyles = ['polaroid', 'darkroom', 'notebook', 'raw'];
    $ogStyle = trim((string)($input['og_style'] ?? 'notebook'));
    if (!in_array($ogStyle, $validStyles, true)) {
        $ogStyle = 'notebook';
    }

    // Generer et unikt, ikke-fortløbende UID til permalinks
    $uid = generate_post_uid();
    $stmtCheck = $db->prepare("SELECT COUNT(*) FROM posts WHERE uid = :uid");
    $stmtCheck->execute([':uid' => $uid]);
    while ($stmtCheck->fetchColumn() > 0) {
        $uid = generate_post_uid();
        $stmtCheck->execute([':uid' => $uid]);
    }

    $stmt = $db->prepare("
        INSERT INTO posts (
            uid, title, slug, content, image_path, location, latitude, longitude, published_at, status,
            og_style, exif_camera, exif_lens, exif_focal, exif_aperture, exif_shutter, exif_iso, created_at, updated_at
        )
        VALUES (
            :uid, :title, :slug, :content, :image_path, :location, :latitude, :longitude, :published_at, :status,
            :og_style, :exif_camera, :exif_lens, :exif_focal, :exif_aperture, :exif_shutter, :exif_iso, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
        )
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
        ':status' => $status,
        ':og_style' => $ogStyle,
        ':exif_camera' => $exifCamera ?: null,
        ':exif_lens' => $exifLens ?: null,
        ':exif_focal' => $exifFocal ?: null,
        ':exif_aperture' => $exifAperture ?: null,
        ':exif_shutter' => $exifShutter ?: null,
        ':exif_iso' => $exifIso ?: null
    ]);

    if ($success) {
        $newId = (int)$db->lastInsertId();
        set_post_images($newId, $savedPaths);

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
            'status' => $status,
            'message' => $status === 'draft' ? 'Kladde gemt!' : 'Indlæg oprettet!',
            'post_url' => get_post_url($uid)
        ]);
    } else {
        json_reply(['error' => 'Kunne ikke gemme indlægget i databasen'], 500);
    }
}

// 4. Hent liste af indlæg (til admin-oversigt med søgning, paginering og manglende datoer)
if ($action === 'get_posts') {
    $q = trim((string)($input['q'] ?? ''));
    $limit = max(1, min(100, (int)($input['limit'] ?? 50)));
    $offset = max(0, (int)($input['offset'] ?? 0));

    $posts = search_admin_posts($q, $limit, $offset);
    $total = count_admin_posts($q);

    foreach ($posts as &$p) {
        $p['tags'] = get_post_tags((int)$p['id']);
        $p['images'] = get_post_images((int)$p['id'], $p['image_path'] ?? null);
        $p['exif_formatted'] = format_exif($p);
    }
    unset($p);

    // Find manglende dage blandt de seneste 14 dage (kun til hjælp inde i fotografens private app)
    $db = get_db();
    $sinceDate = date('Y-m-d', strtotime('-13 days'));
    $stmtDates = $db->prepare("SELECT DISTINCT substr(published_at, 1, 10) FROM posts WHERE published_at >= :since");
    $stmtDates->execute([':since' => $sinceDate]);
    $postedDates = array_flip($stmtDates->fetchAll(PDO::FETCH_COLUMN));

    $missingDates = [];
    for ($i = 0; $i < 14; $i++) {
        $ts = strtotime("-$i days");
        $dIso = date('Y-m-d', $ts);
        if (!isset($postedDates[$dIso])) {
            $missingDates[] = [
                'date_iso' => $dIso,
                'label' => ($i === 0) ? 'I dag (' . date('d.m', $ts) . ')' : (($i === 1) ? 'I går (' . date('d.m', $ts) . ')' : date('d.m.Y', $ts))
            ];
        }
    }

    json_reply([
        'posts' => $posts,
        'total' => $total,
        'limit' => $limit,
        'offset' => $offset,
        'missing_recent_dates' => $missingDates
    ]);
}

// 5. Hent et enkelt indlæg
if ($action === 'get_post') {
    $id = (int)($input['id'] ?? 0);
    $post = get_post_by_id($id);
    if ($post) {
        $post['tags'] = get_post_tags($id);
        $post['images'] = get_post_images($id, $post['image_path'] ?? null);
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
    require_post_method();
    require_csrf_token($input);

    $id = (int)($input['id'] ?? 0);
    $post = get_post_by_id($id);
    if (!$post) {
        json_reply(['error' => 'Indlægget findes ikke'], 404);
    }

    $title = trim((string)($input['title'] ?? $post['title']));
    $content = trim((string)($input['content'] ?? $post['content']));
    $location = trim((string)($input['location'] ?? $post['location']));
    $publishedAt = normalize_datetime_input($input['published_at'] ?? $post['published_at']);
    $latitude = isset($input['latitude']) && is_numeric($input['latitude']) ? (float)$input['latitude'] : $post['latitude'];
    $longitude = isset($input['longitude']) && is_numeric($input['longitude']) ? (float)$input['longitude'] : $post['longitude'];
    $status = isset($input['status']) ? (($input['status'] === 'draft') ? 'draft' : 'published') : ($post['status'] ?? 'published');

    $currentImages = get_post_images($id, $post['image_path'] ?? null);
    $finalImages = $currentImages;

    if (isset($input['existing_images']) && is_array($input['existing_images'])) {
        $kept = [];
        foreach ($input['existing_images'] as $ex) {
            $b = basename((string)$ex);
            if (in_array($b, $currentImages, true) && !in_array($b, $kept, true)) {
                $kept[] = $b;
            }
        }
        $finalImages = $kept;
    }

    if (!empty($input['images_data']) && is_array($input['images_data'])) {
        foreach ($input['images_data'] as $imgData) {
            if (count($finalImages) >= 3) break;
            if (!empty($imgData)) {
                try {
                    $newPath = save_image_data($imgData);
                    ensure_thumbnail($newPath);
                    $finalImages[] = $newPath;
                } catch (Exception $e) {
                    json_reply(['error' => 'Kunne ikke gemme nyt billede: ' . $e->getMessage()], 400);
                }
            }
        }
    } elseif (!empty($input['image_data'])) {
        try {
            $newImagePath = save_image_data($input['image_data']);
            ensure_thumbnail($newImagePath);
            $finalImages = [$newImagePath];
        } catch (Exception $e) {
            json_reply(['error' => 'Kunne ikke gemme nyt billede: ' . $e->getMessage()], 400);
        }
    }

    if (empty($finalImages)) {
        json_reply(['error' => 'Et indlæg skal have mindst ét billede'], 400);
    }

    // Slet billedfiler der blev fjernet
    foreach ($currentImages as $oldImg) {
        if (!in_array($oldImg, $finalImages, true)) {
            delete_uploaded_image($oldImg);
        }
    }
    $imagePath = $finalImages[0];

    $validStyles = ['polaroid', 'darkroom', 'notebook', 'raw'];
    $ogStyle = isset($input['og_style']) ? trim((string)$input['og_style']) : ($post['og_style'] ?? 'notebook');
    if (!in_array($ogStyle, $validStyles, true)) {
        $ogStyle = $post['og_style'] ?? 'notebook';
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
            status = :status,
            image_path = :image_path,
            og_style = :og_style,
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
        ':status' => $status,
        ':image_path' => $imagePath,
        ':og_style' => $ogStyle,
        ':id' => $id
    ]);

    set_post_images($id, $finalImages);

    // Ryd eventuelt cached Open Graph billede, så det opdateres med de nye data
    delete_og_image_cache($post['uid'] ?? (string)$id);

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
    if (
        isset($input['exif_camera']) ||
        isset($input['exif_lens']) ||
        isset($input['exif_focal']) ||
        isset($input['exif_aperture']) ||
        isset($input['exif_shutter']) ||
        isset($input['exif_iso'])
    ) {
        $exifCamera = trim((string)($input['exif_camera'] ?? ($post['exif_camera'] ?? '')));
        $exifLens = trim((string)($input['exif_lens'] ?? ($post['exif_lens'] ?? '')));
        $exifFocal = trim((string)($input['exif_focal'] ?? ($post['exif_focal'] ?? '')));
        $exifAperture = trim((string)($input['exif_aperture'] ?? ($post['exif_aperture'] ?? '')));
        $exifShutter = trim((string)($input['exif_shutter'] ?? ($post['exif_shutter'] ?? '')));
        $exifIso = trim((string)($input['exif_iso'] ?? ($post['exif_iso'] ?? '')));
        $stmtExif = $db->prepare("
            UPDATE posts 
            SET exif_camera = :cam, exif_lens = :lens, exif_focal = :foc, exif_aperture = :ap, exif_shutter = :sh, exif_iso = :iso 
            WHERE id = :id
        ");
        $stmtExif->execute([
            ':cam' => $exifCamera ?: null,
            ':lens' => $exifLens ?: null,
            ':foc' => $exifFocal ?: null,
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
    require_post_method();
    require_csrf_token($input);

    $id = (int)($input['id'] ?? 0);
    $post = get_post_by_id($id);
    if (!$post) {
        json_reply(['error' => 'Indlægget findes ikke'], 404);
    }

    // Slet alle billedfiler tilhørende indlægget sikkert
    $allImgs = get_post_images($id, $post['image_path'] ?? null);
    foreach ($allImgs as $imgP) {
        delete_uploaded_image($imgP);
    }
    delete_og_image_cache($post['uid'] ?? (string)$id);

    $db = get_db();
    $stmtPt = $db->prepare("DELETE FROM post_tags WHERE post_id = :id");
    $stmtPt->execute([':id' => $id]);

    $stmt = $db->prepare("DELETE FROM posts WHERE id = :id");
    $stmt->execute([':id' => $id]);

    // Ryd op i eventuelle tags der ikke længere optræder på andre indlæg
    cleanup_unused_tags();

    json_reply(['success' => true, 'message' => 'Indlægget er slettet']);
}

// 8. Opdater PIN-kode (kræver nuværende PIN og mindst 6 tegn)
if ($action === 'update_pin') {
    require_post_method();
    require_csrf_token($input);

    $currentPin = trim((string)($input['current_pin'] ?? ''));
    $newPin = trim((string)($input['new_pin'] ?? ''));
    if (empty($currentPin) || !verify_pin($currentPin)) {
        json_reply(['error' => 'Nuværende PIN-kode er forkert'], 403);
    }
    if (strlen($newPin) < 6) {
        json_reply(['error' => 'Ny PIN-kode skal være på mindst 6 tegn'], 400);
    }

    try {
        if (update_pin($newPin)) {
            revoke_all_tokens();
            $freshToken = generate_auth_token();
            set_auth_cookie($freshToken, time() + (86400 * 365));
            json_reply([
                'success' => true,
                'csrf_token' => get_csrf_token(),
                'message' => 'PIN-koden er ændret, og gamle sessioner er nulstillet'
            ]);
        } else {
            json_reply(['error' => 'Kunne ikke opdatere PIN-kode'], 500);
        }
    } catch (Throwable $e) {
        error_log('PIN update fejl: ' . $e->getMessage());
        json_reply(['error' => 'Der opstod en fejl under opdatering af PIN-kode'], 500);
    }
}

// 9. Download backup af SQLite database
if ($action === 'backup') {
    require_post_method();
    require_csrf_token($input);

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
    require_post_method();
    require_csrf_token($input);

    $current = is_lockdown_enabled();
    $newStatus = !$current;
    set_lockdown($newStatus);
    json_reply([
        'success' => true,
        'lockdown' => $newStatus,
        'message' => $newStatus ? '🔒 Lockdown aktiveret: Alle nye logins er nu blokeret!' : '🔓 Lockdown deaktiveret: Nye logins er tilladt.'
    ]);
}

// 12. Log alle andre enheder ud (roter token-nøgle, men behold aktuel enhed)
if ($action === 'revoke_sessions') {
    require_post_method();
    require_csrf_token($input);
    revoke_all_tokens();
    $freshToken = generate_auth_token();
    set_auth_cookie($freshToken, time() + (86400 * 365));
    json_reply([
        'success' => true,
        'csrf_token' => get_csrf_token(),
        'message' => 'Alle andre enheder og tidligere tokens er nu logget ud.'
    ]);
}

// 13. Log ud
if ($action === 'logout') {
    require_post_method();
    require_csrf_token($input);

    $_SESSION = [];
    if (session_id()) {
        session_destroy();
    }
    set_auth_cookie('', time() - 3600);
    json_reply(['success' => true, 'message' => 'Logget ud']);
}

json_reply(['error' => 'Ukendt handling: ' . htmlspecialchars($action)], 400);
