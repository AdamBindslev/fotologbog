<?php
/**
 * Hjælpefunktioner til formatering og datahåndtering
 */

require_once __DIR__ . '/db.php';

/**
 * Formaterer dato i analog SYS.LOG stil: [YYYY.MM.DD]
 */
function format_analog_date(string $date_str): string {
    $time = strtotime($date_str);
    if (!$time) {
        return '[----.--.--]';
    }
    return '[' . date('Y.m.d', $time) . ']';
}

/**
 * Formaterer overskrift-tidsstempel med valgfri lokation: [YYYY.MM.DD // LOKATION]
 */
function format_analog_header(string $date_str, ?string $location = null): string {
    $datePart = format_analog_date($date_str);
    if (!empty(trim($location ?? ''))) {
        $locClean = mb_strtoupper(trim($location), 'UTF-8');
        return substr($datePart, 0, -1) . ' // ' . htmlspecialchars($locClean) . ']';
    }
    return $datePart;
}

/**
 * Formaterer klokkeslæt
 */
function format_time(string $date_str): string {
    $time = strtotime($date_str);
    return $time ? date('H:i', $time) : '';
}

/**
 * Konverterer almindelig tekst/noter til pænt formateret HTML med afsnit, klikbare links
 * og simpel skrivemaskine-markdown:
 *   **fed**           -> <strong>
 *   *kursiv*          -> <em>
 *   __understreget__  -> <u class="tw-underline">
 *   ==rødt bånd==     -> <span class="tw-red"> (også ~rødt bånd~)
 * Alle kombinationer (f.eks. ==**fed rød**== eller *__kursiv understreget__*) understøttes,
 * uden at skriftstørrelsen eller 32px-linjehøjden ændres.
 */
function render_content(?string $text): string {
    if (empty($text)) {
        return '';
    }

    // 1. Rens for al rå HTML først (XSS-beskyttelse)
    $text = htmlspecialchars(trim($text), ENT_QUOTES, 'UTF-8');

    // 2. Beskyt URL'er midlertidigt med pladsholdere, så '_' eller '~' i links ikke rammes af markdown
    $links = [];
    $text = preg_replace_callback(
        '~(https?://[^\s<]+)~i',
        function ($m) use (&$links) {
            $url = $m[1];
            // Hvis URL'en slutter på et markdown-tegn eller punktum/komma, pil det af selve linket
            $trailing = '';
            while (preg_match('/([.,;:!?)~*=]|&quot;|&#039;)$/', $url, $tm)) {
                $trailing = $tm[1] . $trailing;
                $url = substr($url, 0, -strlen($tm[1]));
            }
            $key = "\x02LINK" . count($links) . "\x03";
            $links[$key] = '<a href="' . $url . '" target="_blank" rel="noopener noreferrer">' . $url . '</a>' . $trailing;
            return $key;
        },
        $text
    );

    // 3. Simpel skrivemaskine-markdown (kan kombineres frit indeni hinanden)
    // Rødt farvebånd: ==tekst== eller ~tekst~
    $text = preg_replace('/==(.+?)==/s', '<span class="tw-red">$1</span>', $text);
    $text = preg_replace('/(?<!~)~(?!~)(.+?)(?<!~)~(?!~)/s', '<span class="tw-red">$1</span>', $text);

    // Understreget: __tekst__
    $text = preg_replace('/__(.+?)__/s', '<u class="tw-underline">$1</u>', $text);

    // Fed + Kursiv samlet: ***tekst***
    $text = preg_replace('/\*\*\*(.+?)\*\*\*/s', '<strong><em>$1</em></strong>', $text);

    // Fed: **tekst**
    $text = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $text);

    // Kursiv: *tekst*
    $text = preg_replace('/(?<!\*)\*(?!\*)(.+?)(?<!\*)\*(?!\*)/s', '<em>$1</em>', $text);

    // 4. Indsæt de beskyttede links igen
    if (!empty($links)) {
        $text = strtr($text, $links);
    }

    // 5. Del i afsnit ved dobbelte linjeskift
    $paragraphs = preg_split('/\n\s*\n/', $text);
    $html = '';
    foreach ($paragraphs as $p) {
        $p = nl2br(trim($p));
        if ($p !== '') {
            $html .= '<p>' . $p . '</p>' . "\n";
        }
    }

    return $html;
}

/**
 * Genererer et rent tekst-uddrag (uden HTML og uden markdown-tegn) til OpenGraph / sociale medier
 */
function get_clean_excerpt(?string $content, string $fallback = '', int $maxLen = 160): string {
    $rendered = render_content($content);
    $plain = html_entity_decode(strip_tags($rendered), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $plain = trim((string)preg_replace('/\s+/u', ' ', $plain));
    if ($plain === '') {
        return $fallback;
    }
    if (mb_strlen($plain, 'UTF-8') > $maxLen) {
        return rtrim(mb_substr($plain, 0, $maxLen - 1, 'UTF-8')) . '…';
    }
    return $plain;
}

/**
 * Returnerer fuld absolut URL og billeddimensioner til OpenGraph (og:image) og Twitter Cards
 */
function get_social_image_meta(?string $imagePath): ?array {
    if (empty($imagePath)) {
        return null;
    }
    $safeFile = basename($imagePath);
    $fullUrl = rtrim(BASE_URL, '/') . '/uploads/' . rawurlencode($safeFile);
    $localFile = UPLOADS_DIR . '/' . $safeFile;

    $width = null;
    $height = null;
    $mime = 'image/jpeg';

    if (is_file($localFile)) {
        $info = @getimagesize($localFile);
        if ($info) {
            $width = (int)$info[0];
            $height = (int)$info[1];
            if (!empty($info['mime'])) {
                $mime = $info['mime'];
            }
        }
    }

    return [
        'url' => $fullUrl,
        'width' => $width,
        'height' => $height,
        'mime' => $mime
    ];
}

/**
 * Laver en URL-venlig slug
 */
function slugify(string $text): string {
    $text = mb_strtolower($text, 'UTF-8');
    $replacements = ['æ' => 'ae', 'ø' => 'oe', 'å' => 'aa', 'é' => 'e'];
    $text = strtr($text, $replacements);
    $text = preg_replace('/[^a-z0-9]+/i', '-', $text);
    return trim($text, '-');
}

/**
 * Normaliserer enhver dato-streng til gyldig YYYY-MM-DD HH:MM:SS til SQLite
 */
function normalize_datetime_input(?string $inputStr): string {
    $inputStr = trim((string)$inputStr);
    if ($inputStr === '') {
        return date('Y-m-d H:i:s');
    }
    // Udskift T (fra datetime-local) med mellemrum
    $cleanStr = str_replace('T', ' ', $inputStr);
    // Hvis der er prikker f.eks. "11.11.2026", konverter til "YYYY-MM-DD"
    if (preg_match('/^(\d{1,2})\.(\d{1,2})\.(\d{4})(?:\s+(\d{1,2}):(\d{1,2})(?::(\d{1,2}))?)?$/', $cleanStr, $m)) {
        $cleanStr = sprintf('%04d-%02d-%02d %02d:%02d:%02d', 
            (int)$m[3], (int)$m[2], (int)$m[1], 
            (int)($m[4] ?? 12), (int)($m[5] ?? 0), (int)($m[6] ?? 0)
        );
    }
    $timeVal = strtotime($cleanStr);
    if ($timeVal) {
        return date('Y-m-d H:i:s', $timeVal);
    }
    return date('Y-m-d H:i:s');
}

/**
 * Returnerer den pæne, kanoniske permalink-URL for et indlæg (/post/<uid>)
 */
function get_post_url(array|string $postOrUid): string {
    if (is_array($postOrUid)) {
        $uid = !empty($postOrUid['uid']) ? (string)$postOrUid['uid'] : (string)($postOrUid['id'] ?? '');
    } else {
        $uid = (string)$postOrUid;
    }
    return rtrim(BASE_URL, '/') . '/post/' . rawurlencode($uid);
}

/**
 * Henter seneste publicerede indlæg med paginering (altid nyeste øverst)
 */
function get_recent_posts(int $limit = 10, int $offset = 0): array {
    $db = get_db();
    $stmt = $db->prepare("
        SELECT * FROM posts 
        WHERE COALESCE(status, 'published') = 'published'
        ORDER BY datetime(published_at) DESC, published_at DESC, id DESC 
        LIMIT :limit OFFSET :offset
    ");
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Søger og paginerer indlæg til admin-historikken
 */
function search_admin_posts(string $query = '', int $limit = 50, int $offset = 0): array {
    $db = get_db();
    $query = trim($query);
    if ($query === '') {
        $stmt = $db->prepare("
            SELECT * FROM posts 
            ORDER BY datetime(published_at) DESC, published_at DESC, id DESC 
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    $like = '%' . $query . '%';
    $stmt = $db->prepare("
        SELECT DISTINCT p.* FROM posts p
        LEFT JOIN post_tags pt ON pt.post_id = p.id
        LEFT JOIN tags t ON t.id = pt.tag_id
        WHERE p.title LIKE :q
           OR p.content LIKE :q
           OR p.location LIKE :q
           OR p.published_at LIKE :q
           OR p.exif_camera LIKE :q
           OR t.name LIKE :q
        ORDER BY datetime(p.published_at) DESC, p.published_at DESC, p.id DESC
        LIMIT :limit OFFSET :offset
    ");
    $stmt->bindValue(':q', $like, PDO::PARAM_STR);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Tæller indlæg til admin-historikken
 */
function count_admin_posts(string $query = ''): int {
    $db = get_db();
    $query = trim($query);
    if ($query === '') {
        $stmt = $db->query("SELECT COUNT(*) FROM posts");
        return (int)$stmt->fetchColumn();
    }
    $like = '%' . $query . '%';
    $stmt = $db->prepare("
        SELECT COUNT(DISTINCT p.id) FROM posts p
        LEFT JOIN post_tags pt ON pt.post_id = p.id
        LEFT JOIN tags t ON t.id = pt.tag_id
        WHERE p.title LIKE :q
           OR p.content LIKE :q
           OR p.location LIKE :q
           OR p.published_at LIKE :q
           OR p.exif_camera LIKE :q
           OR t.name LIKE :q
    ");
    $stmt->execute([':q' => $like]);
    return (int)$stmt->fetchColumn();
}

/**
 * Tæller det samlede antal publicerede indlæg
 */
function count_total_posts(): int {
    $db = get_db();
    $stmt = $db->query("SELECT COUNT(*) FROM posts WHERE COALESCE(status, 'published') = 'published'");
    return (int)$stmt->fetchColumn();
}

/**
 * Genererer et ikke-fortløbende, unikt alfanumerisk ID (f.eks. "8f2a4c")
 */
function generate_post_uid(): string {
    return bin2hex(random_bytes(3));
}

/**
 * Henter et specifikt indlæg ud fra UID, ID eller slug
 */
function get_post_by_identifier(string $identifier): ?array {
    $db = get_db();
    $identifier = trim($identifier);
    if ($identifier === '') {
        return null;
    }

    // 1. Tjek UID først (vores primære permalink-nøgle)
    $stmt = $db->prepare("SELECT * FROM posts WHERE uid = :val LIMIT 1");
    $stmt->execute([':val' => $identifier]);
    $post = $stmt->fetch();
    if ($post) {
        return $post;
    }

    // 2. Hvis numerisk, tjek id (bagudkompatibilitet til gamle links)
    if (ctype_digit($identifier)) {
        $stmt = $db->prepare("SELECT * FROM posts WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => (int)$identifier]);
        $post = $stmt->fetch();
        if ($post) {
            return $post;
        }
    }

    // 3. Tjek slug som fallback
    $stmt = $db->prepare("SELECT * FROM posts WHERE slug = :slug LIMIT 1");
    $stmt->execute([':slug' => $identifier]);
    $post = $stmt->fetch();
    return $post ?: null;
}

/**
 * Henter forrige (ældre) og næste (nyere) indlæg i forhold til et givent indlæg
 */
function get_adjacent_posts(array $post): array {
    $db = get_db();
    $pub = $post['published_at'];
    $id = (int)$post['id'];

    // Forrige (ældre) publicerede indlæg i tidslinjen
    $stmtPrev = $db->prepare("
        SELECT id, uid, title, published_at FROM posts
        WHERE COALESCE(status, 'published') = 'published'
          AND ((datetime(published_at) < datetime(:pub))
           OR (datetime(published_at) = datetime(:pub) AND id < :id))
        ORDER BY datetime(published_at) DESC, id DESC
        LIMIT 1
    ");
    $stmtPrev->execute([':pub' => $pub, ':id' => $id]);
    $prev = $stmtPrev->fetch() ?: null;

    // Næste (nyere) publicerede indlæg i tidslinjen
    $stmtNext = $db->prepare("
        SELECT id, uid, title, published_at FROM posts
        WHERE COALESCE(status, 'published') = 'published'
          AND ((datetime(published_at) > datetime(:pub))
           OR (datetime(published_at) = datetime(:pub) AND id > :id))
        ORDER BY datetime(published_at) ASC, id ASC
        LIMIT 1
    ");
    $stmtNext->execute([':pub' => $pub, ':id' => $id]);
    $next = $stmtNext->fetch() ?: null;

    return ['prev' => $prev, 'next' => $next];
}

/**
 * Henter et specifikt indlæg ud fra ID
 */
function get_post_by_id(int $id): ?array {
    $db = get_db();
    $stmt = $db->prepare("SELECT * FROM posts WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    $post = $stmt->fetch();
    return $post ?: null;
}

/**
 * Henter arkivliste opdelt efter år og måned
 */
function get_archive_months(): array {
    $db = get_db();
    $stmt = $db->query("
        SELECT 
            strftime('%Y', published_at) as year,
            strftime('%m', published_at) as month,
            COUNT(*) as total
        FROM posts
        WHERE COALESCE(status, 'published') = 'published'
        GROUP BY year, month
        ORDER BY year DESC, month DESC
    ");
    return $stmt->fetchAll();
}

/**
 * Henter publicerede indlæg for en bestemt måned
 */
function get_posts_by_month(string $year, string $month): array {
    $db = get_db();
    $pattern = sprintf('%04d-%02d%%', (int)$year, (int)$month);
    $stmt = $db->prepare("
        SELECT * FROM posts 
        WHERE COALESCE(status, 'published') = 'published'
          AND published_at LIKE :pattern 
        ORDER BY datetime(published_at) DESC, published_at DESC, id DESC
    ");
    $stmt->execute([':pattern' => $pattern]);
    return $stmt->fetchAll();
}

/**
 * Opbygger en kontaktark-kalender (Contact Sheet) for en given måned.
 * VIGTIGT: Tomme dage er udelukkende visuelle pladsholdere uden links til /app/.
 */
function get_month_contact_sheet(string $year, string $month, ?array $preloadedPosts = null): array {
    $y = max(1970, min(2100, (int)$year));
    $m = max(1, min(12, (int)$month));
    $posts = $preloadedPosts ?? get_posts_by_month(sprintf('%04d', $y), sprintf('%02d', $m));

    $byDay = [];
    foreach ($posts as $p) {
        $ts = strtotime($p['published_at'] ?? '');
        if ($ts) {
            $dayNum = (int)date('j', $ts);
            // Hvis flere indlæg samme dag, behold det seneste som forside på kontaktarket og tæl antal
            if (!isset($byDay[$dayNum])) {
                $byDay[$dayNum] = [
                    'post' => $p,
                    'count' => 1
                ];
            } else {
                $byDay[$dayNum]['count']++;
            }
        }
    }

    $firstDayTs = mktime(0, 0, 0, $m, 1, $y);
    $daysInMonth = (int)date('t', $firstDayTs);
    $firstWeekdayIso = (int)date('N', $firstDayTs); // 1 (Man) til 7 (Søn)

    $days = [];
    $todayStr = date('Y-m-d');
    for ($d = 1; $d <= $daysInMonth; $d++) {
        $dateIso = sprintf('%04d-%02d-%02d', $y, $m, $d);
        $days[$d] = [
            'day' => $d,
            'date_iso' => $dateIso,
            'date_label' => sprintf('%02d.%02d', $d, $m),
            'is_future' => ($dateIso > $todayStr),
            'is_today' => ($dateIso === $todayStr),
            'entry' => $byDay[$d]['post'] ?? null,
            'count' => $byDay[$d]['count'] ?? 0
        ];
    }

    return [
        'year' => sprintf('%04d', $y),
        'month' => sprintf('%02d', $m),
        'days_in_month' => $daysInMonth,
        'first_weekday_iso' => $firstWeekdayIso,
        'captured_days_count' => count($byDay),
        'days' => $days
    ];
}

/**
 * Behandler og gemmer et uploadet billede med streng indholds- og MIME-validering
 */
function save_image_data(array|string $image_input): string {
    if (!is_dir(UPLOADS_DIR)) {
        @mkdir(UPLOADS_DIR, 0755, true);
    }

    $maxBytes = 15 * 1024 * 1024; // Maks 15 MB
    $allowedMimes = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp'
    ];
    $filename = date('Ymd_His') . '_' . bin2hex(random_bytes(4));

    // Tilfælde 1: Base64 data-URL (fra canvas klient-optimering)
    if (is_string($image_input) && preg_match('/^data:image\/([a-zA-Z0-9+.-]+);base64,/', $image_input)) {
        $commaPos = strpos($image_input, ',');
        if ($commaPos === false) {
            throw new Exception('Ugyldigt billedformat');
        }
        $data = substr($image_input, $commaPos + 1);
        $decoded = base64_decode($data, true);
        if ($decoded === false || strlen($decoded) < 32) {
            throw new Exception('Ugyldigt Base64-billede');
        }
        if (strlen($decoded) > $maxBytes) {
            throw new Exception('Billedet er for stort (maks 15 MB)');
        }

        // Streng verifikation af at de dekodede bytes faktisk er et gyldigt billede
        $imgInfo = @getimagesizefromstring($decoded);
        if (!$imgInfo || empty($imgInfo['mime']) || !isset($allowedMimes[$imgInfo['mime']])) {
            throw new Exception('Filindholdet er ikke et gyldigt JPEG-, PNG- eller WebP-billede');
        }

        $ext = $allowedMimes[$imgInfo['mime']];
        $targetFile = $filename . '.' . $ext;
        $destPath = UPLOADS_DIR . '/' . $targetFile;
        if (file_put_contents($destPath, $decoded, LOCK_EX) === false) {
            throw new Exception('Kunne ikke skrive billedfil til mappen uploads/. Tjek rettigheder.');
        }
        @chmod($destPath, 0644);
        return $targetFile;
    }

    // Tilfælde 2: Almindelig $_FILES upload
    if (is_array($image_input) && isset($image_input['tmp_name']) && is_uploaded_file($image_input['tmp_name'])) {
        if (!empty($image_input['size']) && (int)$image_input['size'] > $maxBytes) {
            throw new Exception('Billedet er for stort (maks 15 MB)');
        }
        $imgInfo = @getimagesize($image_input['tmp_name']);
        if (!$imgInfo || empty($imgInfo['mime']) || !isset($allowedMimes[$imgInfo['mime']])) {
            throw new Exception('Filen er ikke et gyldigt JPEG-, PNG- eller WebP-billede');
        }
        $ext = $allowedMimes[$imgInfo['mime']];
        $targetFile = $filename . '.' . $ext;
        $destPath = UPLOADS_DIR . '/' . $targetFile;
        if (!move_uploaded_file($image_input['tmp_name'], $destPath)) {
            throw new Exception('Kunne ikke gemme billedet på serveren');
        }
        @chmod($destPath, 0644);
        return $targetFile;
    }

    throw new Exception('Intet gyldigt billede modtaget');
}

/**
 * Sletter en billedfil og dens thumbnail sikkert fra uploads-mappen (forhindrer path traversal)
 */
function delete_uploaded_image(?string $imagePath): void {
    if (empty($imagePath)) {
        return;
    }
    $safeName = basename($imagePath);
    if ($safeName === '' || $safeName === '.' || $safeName === '..' || $safeName === '.htaccess') {
        return;
    }
    $fullPath = UPLOADS_DIR . '/' . $safeName;
    if (is_file($fullPath)) {
        @unlink($fullPath);
    }
    $thumbPath = UPLOADS_DIR . '/thumbs/' . pathinfo($safeName, PATHINFO_FILENAME) . '.jpg';
    if (is_file($thumbPath)) {
        @unlink($thumbPath);
    }
}

/**
 * Genererer en letvægts-thumbnail (max 480px) til kontaktark, hvis GD er tilgængelig.
 * Returnerer stien relativt til UPLOADS_DIR (f.eks. 'thumbs/20261112_abc.jpg') eller originalen som fallback.
 */
function ensure_thumbnail(?string $imagePath, int $maxDim = 480): string {
    if (empty($imagePath)) {
        return '';
    }
    $safeName = basename($imagePath);
    $srcPath = UPLOADS_DIR . '/' . $safeName;
    if (!is_file($srcPath)) {
        return $safeName;
    }

    $thumbDir = UPLOADS_DIR . '/thumbs';
    $thumbName = pathinfo($safeName, PATHINFO_FILENAME) . '.jpg';
    $thumbRel = 'thumbs/' . $thumbName;
    $thumbPath = $thumbDir . '/' . $thumbName;

    if (is_file($thumbPath) && filemtime($thumbPath) >= filemtime($srcPath)) {
        return $thumbRel;
    }

    if (!extension_loaded('gd') || !function_exists('imagecreatetruecolor')) {
        return $safeName;
    }

    if (!is_dir($thumbDir)) {
        @mkdir($thumbDir, 0755, true);
    }

    $info = @getimagesize($srcPath);
    if (!$info) {
        return $safeName;
    }
    $mime = $info['mime'] ?? '';
    $srcImg = null;
    if ($mime === 'image/jpeg' && function_exists('imagecreatefromjpeg')) {
        $srcImg = @imagecreatefromjpeg($srcPath);
    } elseif ($mime === 'image/png' && function_exists('imagecreatefrompng')) {
        $srcImg = @imagecreatefrompng($srcPath);
    } elseif ($mime === 'image/webp' && function_exists('imagecreatefromwebp')) {
        $srcImg = @imagecreatefromwebp($srcPath);
    }

    if (!$srcImg) {
        return $safeName;
    }

    $w = imagesx($srcImg);
    $h = imagesy($srcImg);
    if ($w <= $maxDim && $h <= $maxDim) {
        imagedestroy($srcImg);
        return $safeName;
    }

    if ($w > $h) {
        $nw = $maxDim;
        $nh = max(1, (int)round($h * ($maxDim / $w)));
    } else {
        $nh = $maxDim;
        $nw = max(1, (int)round($w * ($maxDim / $h)));
    }

    $dst = imagecreatetruecolor($nw, $nh);
    imagecopyresampled($dst, $srcImg, 0, 0, 0, 0, $nw, $nh, $w, $h);
    $saved = @imagejpeg($dst, $thumbPath, 82);
    imagedestroy($srcImg);
    imagedestroy($dst);

    return $saved ? $thumbRel : $safeName;
}

/**
 * Returnerer fuld URL til thumbnail til kontaktark
 */
function get_thumbnail_url(?string $imagePath): string {
    if (empty($imagePath)) {
        return '';
    }
    $rel = ensure_thumbnail($imagePath);
    return UPLOADS_URL . '/' . str_replace('%2F', '/', rawurlencode($rel));
}

/**
 * Henter alle billeder tilhørende et indlæg (op til 3), med fallback til posts.image_path
 */
function get_post_images(int $postId, ?string $fallbackImagePath = null): array {
    $db = get_db();
    $stmt = $db->prepare("SELECT image_path FROM post_images WHERE post_id = :pid ORDER BY sort_order ASC, id ASC");
    $stmt->execute([':pid' => $postId]);
    $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);
    if (!empty($rows)) {
        return array_values(array_filter(array_map('strval', $rows)));
    }
    if (!empty($fallbackImagePath)) {
        return [$fallbackImagePath];
    }
    return [];
}

/**
 * Gemmer op til 3 billeder for et indlæg i post_images og synkroniserer første billede til posts.image_path
 */
function set_post_images(int $postId, array $imagePaths): void {
    $clean = [];
    foreach ($imagePaths as $p) {
        $p = basename(trim((string)$p));
        if ($p !== '' && !in_array($p, $clean, true)) {
            $clean[] = $p;
        }
        if (count($clean) >= 3) {
            break;
        }
    }

    $db = get_db();
    $db->prepare("DELETE FROM post_images WHERE post_id = :pid")->execute([':pid' => $postId]);
    $stmtIns = $db->prepare("INSERT INTO post_images (post_id, image_path, sort_order) VALUES (:pid, :path, :ord)");
    foreach ($clean as $idx => $path) {
        $stmtIns->execute([':pid' => $postId, ':path' => $path, ':ord' => $idx]);
    }

    $primary = $clean[0] ?? '';
    $db->prepare("UPDATE posts SET image_path = :img WHERE id = :pid")->execute([':img' => $primary, ':pid' => $postId]);
}

/**
 * Sletter eventuelle cached Open Graph-billeder for et givent indlæg (UID eller ID)
 */
function delete_og_image_cache(?string $identifier): void {
    if (empty($identifier)) {
        return;
    }
    $safeUid = preg_replace('/[^a-zA-Z0-9_-]/', '', $identifier);
    if ($safeUid === '') {
        return;
    }
    $cacheDir = UPLOADS_DIR . '/og';
    if (!is_dir($cacheDir)) {
        return;
    }
    $pattern = $cacheDir . '/' . $safeUid . '_*.jpg';
    $files = glob($pattern);
    if (is_array($files)) {
        foreach ($files as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
    }
}

/**
 * Returnerer fulde sociale metadata (og:image) til et indlæg baseret på dets valgte stil.
 */
function get_post_og_meta(array $post): ?array {
    if (empty($post['image_path'])) {
        return null;
    }
    $uid = !empty($post['uid']) ? $post['uid'] : (string)$post['id'];
    $style = $post['og_style'] ?? 'notebook';
    $validStyles = ['polaroid', 'darkroom', 'notebook', 'raw'];
    if (!in_array($style, $validStyles, true)) {
        $style = 'notebook';
    }

    $version = !empty($post['updated_at']) ? strtotime($post['updated_at']) : strtotime($post['published_at'] ?? 'now');
    $url = rtrim(BASE_URL, '/') . '/og-image.php?p=' . rawurlencode($uid) . '&v=' . (int)$version;

    return [
        'url' => $url,
        'width' => 1200,
        'height' => 630,
        'mime' => 'image/jpeg',
        'style' => $style
    ];
}

/**
 * Returnerer en tape-monteringsvariant (1 til 5) deterministisk baseret på indlæggets ID/UID.
 */
function get_tape_variant(string|int $uid): int {
    if (is_numeric($uid)) {
        return (((int)$uid) % 5) + 1;
    }
    return (abs(crc32((string)$uid)) % 5) + 1;
}

/**
 * Gemmer/opdaterer tags for et indlæg (maks 3 tags)
 */
function set_post_tags(int $postId, array $tags): void {
    $db = get_db();
    
    // Rens og begræns til max 3 tags
    $cleanTags = [];
    foreach ($tags as $tag) {
        $tag = trim((string)$tag);
        $tag = ltrim($tag, '#');
        $tag = trim($tag);
        if ($tag !== '' && !in_array(mb_strtolower($tag, 'UTF-8'), array_map('mb_strtolower', $cleanTags))) {
            $cleanTags[] = $tag;
        }
        if (count($cleanTags) >= 3) {
            break;
        }
    }

    // Fjern eksisterende relationer
    $stmtDelete = $db->prepare("DELETE FROM post_tags WHERE post_id = :post_id");
    $stmtDelete->execute([':post_id' => $postId]);

    if (empty($cleanTags)) {
        return;
    }

    $stmtInsertTag = $db->prepare("INSERT OR IGNORE INTO tags (name, slug) VALUES (:name, :slug)");
    $stmtGetTag = $db->prepare("SELECT id FROM tags WHERE slug = :slug LIMIT 1");
    $stmtLink = $db->prepare("INSERT OR IGNORE INTO post_tags (post_id, tag_id) VALUES (:post_id, :tag_id)");

    foreach ($cleanTags as $tagName) {
        $slug = slugify($tagName);
        if ($slug === '') continue;

        $stmtInsertTag->execute([
            ':name' => $tagName,
            ':slug' => $slug
        ]);

        $stmtGetTag->execute([':slug' => $slug]);
        $tagId = $stmtGetTag->fetchColumn();

        if ($tagId) {
            $stmtLink->execute([
                ':post_id' => $postId,
                ':tag_id' => (int)$tagId
            ]);
        }
    }

    cleanup_unused_tags();
}

/**
 * Fjerner ubrugte tags og forældreløse relationer fra databasen
 */
function cleanup_unused_tags(): void {
    $db = get_db();
    // Fjern eventuelle forældreløse rækker i post_tags (hvis posts tidligere er slettet uden kaskade)
    $db->exec("DELETE FROM post_tags WHERE post_id NOT IN (SELECT id FROM posts)");
    // Fjern tags der ikke længere er knyttet til noget indlæg
    $db->exec("DELETE FROM tags WHERE id NOT IN (SELECT DISTINCT tag_id FROM post_tags)");
}

/**
 * Henter alle tags til et specifikt indlæg
 */
function get_post_tags(int $postId): array {
    $db = get_db();
    $stmt = $db->prepare("
        SELECT t.id, t.name, t.slug 
        FROM tags t
        JOIN post_tags pt ON pt.tag_id = t.id
        WHERE pt.post_id = :post_id
        ORDER BY t.name ASC
    ");
    $stmt->execute([':post_id' => $postId]);
    return $stmt->fetchAll();
}

/**
 * Henter alle anvendte tags med optælling af indlæg
 */
function get_all_tags_with_counts(): array {
    $db = get_db();
    cleanup_unused_tags();
    $stmt = $db->query("
        SELECT t.id, t.name, t.slug, COUNT(pt.post_id) as post_count
        FROM tags t
        JOIN post_tags pt ON pt.tag_id = t.id
        JOIN posts p ON p.id = pt.post_id
        WHERE COALESCE(p.status, 'published') = 'published'
        GROUP BY t.id, t.name, t.slug
        HAVING post_count > 0
        ORDER BY post_count DESC, t.name ASC
    ");
    return $stmt->fetchAll();
}

/**
 * Henter alle anvendte lokationer med optælling af publicerede indlæg
 */
function get_all_locations_with_counts(): array {
    $db = get_db();
    $stmt = $db->query("
        SELECT trim(location) as location, COUNT(*) as post_count
        FROM posts
        WHERE COALESCE(status, 'published') = 'published'
          AND location IS NOT NULL AND trim(location) != ''
        GROUP BY trim(location)
        HAVING post_count > 0
        ORDER BY post_count DESC, location ASC
    ");
    return $stmt->fetchAll();
}

/**
 * Henter et samlet indeks med tags og lokationer vægtet til en deterministisk, håndstemplet ord-sky
 */
function get_unified_index_items(): array {
    $tags = get_all_tags_with_counts();
    $locations = get_all_locations_with_counts();

    $items = [];
    $maxCount = 1;

    foreach ($tags as $t) {
        $count = (int)$t['post_count'];
        if ($count > $maxCount) $maxCount = $count;
        $items[] = [
            'type' => 'tag',
            'name' => '#' . $t['name'],
            'raw_name' => $t['name'],
            'slug' => $t['slug'],
            'count' => $count,
            'url' => BASE_URL . '/?tag=' . urlencode($t['slug'])
        ];
    }

    foreach ($locations as $l) {
        $count = (int)$l['post_count'];
        if ($count > $maxCount) $maxCount = $count;
        $items[] = [
            'type' => 'location',
            'name' => '⌖ ' . $l['location'],
            'raw_name' => $l['location'],
            'slug' => slugify($l['location']),
            'count' => $count,
            'url' => BASE_URL . '/?location=' . urlencode($l['location'])
        ];
    }

    // Deterministiske variations-parametre (hældning, drift, stil, farvetone) baseret på crc32
    // Så stemplerne i notesbogen har organisk karakter, men bliver på deres plads!
    $rotations = [-3.2, -2.2, -1.4, -0.8, 0, 0.8, 1.4, 2.2, 3.2];
    $drifts = [-6, -4, -2, 0, 2, 4, 6];
    $fontStyles = ['style-mono', 'style-typewriter', 'style-serif', 'style-display'];
    $colorTones = ['tone-ink', 'tone-red', 'tone-sepia', 'tone-faint'];

    foreach ($items as &$item) {
        $weight = 1;
        if ($maxCount > 1) {
            $weight = 1 + (int)round(($item['count'] - 1) / ($maxCount - 1) * 4);
        }
        $item['weight'] = max(1, min(5, $weight));

        $seed = abs(crc32($item['type'] . '::' . mb_strtolower($item['raw_name'], 'UTF-8')));
        $item['rot'] = $rotations[$seed % count($rotations)];
        $item['drift'] = $drifts[intdiv($seed, 7) % count($drifts)];
        $item['font_style'] = $fontStyles[intdiv($seed, 13) % count($fontStyles)];
        $item['color_tone'] = $colorTones[intdiv($seed, 29) % count($colorTones)];
        $item['sort_key'] = $seed;
    }
    unset($item);

    // Deterministisk spredning så ordene ligger stabilt fra besøg til besøg
    usort($items, fn($a, $b) => $a['sort_key'] <=> $b['sort_key']);

    return $items;
}

/**
 * Henter indlæg filtreret på et tag slug
 */
function get_posts_by_tag(string $tagSlug, int $limit = 10, int $offset = 0): array {
    $db = get_db();
    $stmt = $db->prepare("
        SELECT p.* 
        FROM posts p
        JOIN post_tags pt ON pt.post_id = p.id
        JOIN tags t ON t.id = pt.tag_id
        WHERE t.slug = :slug
          AND COALESCE(p.status, 'published') = 'published'
        ORDER BY datetime(p.published_at) DESC, p.published_at DESC, p.id DESC
        LIMIT :limit OFFSET :offset
    ");
    $stmt->bindValue(':slug', $tagSlug, PDO::PARAM_STR);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Tæller antal publicerede indlæg for et bestemt tag
 */
function count_posts_by_tag(string $tagSlug): int {
    $db = get_db();
    $stmt = $db->prepare("
        SELECT COUNT(p.id)
        FROM posts p
        JOIN post_tags pt ON pt.post_id = p.id
        JOIN tags t ON t.id = pt.tag_id
        WHERE t.slug = :slug
          AND COALESCE(p.status, 'published') = 'published'
    ");
    $stmt->execute([':slug' => $tagSlug]);
    return (int)$stmt->fetchColumn();
}

/**
 * Henter information om et tag ud fra slug
 */
function get_tag_by_slug(string $slug): ?array {
    $db = get_db();
    $stmt = $db->prepare("SELECT * FROM tags WHERE slug = :slug LIMIT 1");
    $stmt->execute([':slug' => $slug]);
    $res = $stmt->fetch();
    return $res ?: null;
}

/**
 * Henter indlæg filtreret på en lokation
 */
function get_posts_by_location(string $location, int $limit = 10, int $offset = 0): array {
    $db = get_db();
    $stmt = $db->prepare("
        SELECT * FROM posts 
        WHERE COALESCE(status, 'published') = 'published'
          AND trim(location) = trim(:location)
        ORDER BY datetime(published_at) DESC, published_at DESC, id DESC
        LIMIT :limit OFFSET :offset
    ");
    $stmt->bindValue(':location', $location, PDO::PARAM_STR);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Tæller antal publicerede indlæg for en bestemt lokation
 */
function count_posts_by_location(string $location): int {
    $db = get_db();
    $stmt = $db->prepare("
        SELECT COUNT(*) FROM posts 
        WHERE COALESCE(status, 'published') = 'published'
          AND trim(location) = trim(:location)
    ");
    $stmt->execute([':location' => $location]);
    return (int)$stmt->fetchColumn();
}

/**
 * Formaterer en læsevenlig retro EXIF-streng: f.eks. "FUJIFILM X-Pro3 · XF 35mm f/1.4 · ƒ/2.8 · 1/250s · ISO 400"
 */
function format_exif(array $post): ?string {
    $parts = [];
    if (!empty($post['exif_camera'])) {
        $parts[] = trim((string)$post['exif_camera']);
    }
    if (!empty($post['exif_lens'])) {
        $parts[] = trim((string)$post['exif_lens']);
    }
    if (!empty($post['exif_focal'])) {
        $parts[] = trim((string)$post['exif_focal']);
    }
    if (!empty($post['exif_aperture'])) {
        $parts[] = trim((string)$post['exif_aperture']);
    }
    if (!empty($post['exif_shutter'])) {
        $parts[] = trim((string)$post['exif_shutter']);
    }
    if (!empty($post['exif_iso'])) {
        $parts[] = trim((string)$post['exif_iso']);
    }

    if (empty($parts)) {
        return null;
    }

    return implode(' · ', $parts);
}

/**
 * Udtrækker kamera, objektiv, brændvidde, blænde, lukkertid, ISO og optagedato fra et lokalt billede via PHP exif
 */
function extract_exif_from_image_file(string $filePath): array {
    $result = [
        'camera' => null,
        'lens' => null,
        'focal' => null,
        'aperture' => null,
        'shutter' => null,
        'iso' => null,
        'date' => null
    ];

    if (!function_exists('exif_read_data') || !file_exists($filePath)) {
        return $result;
    }

    try {
        $exif = @exif_read_data($filePath);
        if (!$exif || !is_array($exif)) {
            return $result;
        }

        // Kamera og objektiv separat
        $make = trim((string)($exif['Make'] ?? ''));
        $model = trim((string)($exif['Model'] ?? ''));
        $lens = trim((string)($exif['UndefinedTag:0xA434'] ?? $exif['LensModel'] ?? ''));
        if ($model !== '') {
            if ($make !== '' && stripos($model, $make) === false) {
                $result['camera'] = $make . ' ' . $model;
            } else {
                $result['camera'] = $model;
            }
        }
        if ($lens !== '') {
            $result['lens'] = $lens;
        }

        // Brændvidde (FocalLengthIn35mmFilm eller FocalLength)
        if (!empty($exif['FocalLengthIn35mmFilm']) && is_numeric($exif['FocalLengthIn35mmFilm'])) {
            $result['focal'] = round((float)$exif['FocalLengthIn35mmFilm']) . 'mm';
        } elseif (isset($exif['FocalLength'])) {
            $fl = $exif['FocalLength'];
            if (is_string($fl) && strpos($fl, '/') !== false) {
                $p = explode('/', $fl);
                $val = (float)$p[0] / max(1, (float)$p[1]);
                if ($val > 0) $result['focal'] = round($val) . 'mm';
            } elseif (is_numeric($fl) && (float)$fl > 0) {
                $result['focal'] = round((float)$fl) . 'mm';
            }
        }

        // Blænde (FNumber eller ApertureValue)
        if (isset($exif['FNumber'])) {
            $fNumber = $exif['FNumber'];
            if (is_string($fNumber) && strpos($fNumber, '/') !== false) {
                $p = explode('/', $fNumber);
                $val = (float)$p[0] / max(1, (float)$p[1]);
                $result['aperture'] = 'ƒ/' . round($val, 1);
            } elseif (is_numeric($fNumber)) {
                $result['aperture'] = 'ƒ/' . round((float)$fNumber, 1);
            }
        } elseif (isset($exif['COMPUTED']['ApertureFNumber'])) {
            $result['aperture'] = str_replace('f/', 'ƒ/', $exif['COMPUTED']['ApertureFNumber']);
        } elseif (isset($exif['ApertureValue'])) {
            $av = $exif['ApertureValue'];
            if (is_string($av) && strpos($av, '/') !== false) {
                $p = explode('/', $av);
                $val = (float)$p[0] / max(1, (float)$p[1]);
                $f = pow(sqrt(2), $val);
                $result['aperture'] = 'ƒ/' . round($f, 1);
            }
        }

        // Lukkertid (ExposureTime eller ShutterSpeedValue)
        if (isset($exif['ExposureTime'])) {
            $exp = $exif['ExposureTime'];
            if (is_string($exp) && strpos($exp, '/') !== false) {
                $result['shutter'] = $exp . 's';
            } elseif (is_numeric($exp)) {
                $expNum = (float)$exp;
                if ($expNum < 1 && $expNum > 0) {
                    $denom = round(1 / $expNum);
                    $result['shutter'] = '1/' . $denom . 's';
                } else {
                    $result['shutter'] = round($expNum, 1) . 's';
                }
            }
        } elseif (isset($exif['ShutterSpeedValue'])) {
            $ssv = $exif['ShutterSpeedValue'];
            if (is_string($ssv) && strpos($ssv, '/') !== false) {
                $p = explode('/', $ssv);
                $val = (float)$p[0] / max(1, (float)$p[1]);
                $exp = pow(2, -$val);
                if ($exp < 1 && $exp > 0) {
                    $result['shutter'] = '1/' . round(1 / $exp) . 's';
                } else {
                    $result['shutter'] = round($exp, 1) . 's';
                }
            }
        }

        // ISO (ISOSpeedRatings, ISO eller PhotographicSensitivity)
        if (isset($exif['ISOSpeedRatings'])) {
            $iso = is_array($exif['ISOSpeedRatings']) ? $exif['ISOSpeedRatings'][0] : $exif['ISOSpeedRatings'];
            $result['iso'] = 'ISO ' . $iso;
        } elseif (isset($exif['PhotographicSensitivity'])) {
            $iso = is_array($exif['PhotographicSensitivity']) ? $exif['PhotographicSensitivity'][0] : $exif['PhotographicSensitivity'];
            $result['iso'] = 'ISO ' . $iso;
        } elseif (isset($exif['ISO'])) {
            $result['iso'] = 'ISO ' . $exif['ISO'];
        }

        // Optagedato (DateTimeOriginal eller DateTime)
        if (!empty($exif['DateTimeOriginal'])) {
            $result['date'] = $exif['DateTimeOriginal'];
        } elseif (!empty($exif['DateTime'])) {
            $result['date'] = $exif['DateTime'];
        }
    } catch (Throwable $e) {
        // Ignorer eventuelle parse fejl på ufuldstændig EXIF
    }

    return $result;
}

/**
 * Udskriver den diskrete kolofon i bunden af notesbogen
 */
function render_colophon(): void {
    $rssUrl = htmlspecialchars(BASE_URL . '/rss.php', ENT_QUOTES, 'UTF-8');
    $repoUrl = defined('COLOPHON_REPO_URL') ? trim((string)COLOPHON_REPO_URL) : '';
    $repoLabel = defined('COLOPHON_REPO_LABEL') ? trim((string)COLOPHON_REPO_LABEL) : 'Bygget på Logbog — start din egen →';
    $ethos = defined('COLOPHON_ETHOS') ? trim((string)COLOPHON_ETHOS) : '';
    ?>
    <footer class="notebook-colophon" aria-label="Kolofon">
        <?php if ($ethos !== ''): ?>
            <p class="colophon-ethos"><?= htmlspecialchars($ethos, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
        <p class="colophon-links">
            <a href="<?= $rssUrl ?>" title="Abonnér via RSS">[ RSS FEED ]</a>
            <?php if ($repoUrl !== ''): ?>
                <span class="colophon-sep" aria-hidden="true">·</span>
                <a href="<?= htmlspecialchars($repoUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer"><?= htmlspecialchars($repoLabel, ENT_QUOTES, 'UTF-8') ?></a>
            <?php endif; ?>
        </p>
    </footer>
    <?php
}
