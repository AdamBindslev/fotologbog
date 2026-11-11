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
 * Konverterer almindelig tekst/noter til pænt formateret HTML med afsnit og klikbare links
 */
function render_content(?string $text): string {
    if (empty($text)) {
        return '';
    }

    // Rens for skadelig HTML
    $text = htmlspecialchars(trim($text), ENT_QUOTES, 'UTF-8');

    // Gør links klikbare
    $text = preg_replace(
        '~(https?://[^\s<]+)~i',
        '<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>',
        $text
    );

    // Del i afsnit ved dobbelte linjeskift
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
 * Henter seneste indlæg med paginering
 */
function get_recent_posts(int $limit = 10, int $offset = 0): array {
    $db = get_db();
    $stmt = $db->prepare("
        SELECT * FROM posts 
        ORDER BY published_at DESC, id DESC 
        LIMIT :limit OFFSET :offset
    ");
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Tæller det samlede antal indlæg
 */
function count_total_posts(): int {
    $db = get_db();
    $stmt = $db->query("SELECT COUNT(*) FROM posts");
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
        GROUP BY year, month
        ORDER BY year DESC, month DESC
    ");
    return $stmt->fetchAll();
}

/**
 * Henter indlæg for en bestemt måned
 */
function get_posts_by_month(string $year, string $month): array {
    $db = get_db();
    $pattern = sprintf('%04d-%02d%%', (int)$year, (int)$month);
    $stmt = $db->prepare("
        SELECT * FROM posts 
        WHERE published_at LIKE :pattern 
        ORDER BY published_at DESC, id DESC
    ");
    $stmt->execute([':pattern' => $pattern]);
    return $stmt->fetchAll();
}

/**
 * Behandler og gemmer et uploadet billede (enten multipart file eller base64 data-URL)
 */
function save_image_data(array|string $image_input): string {
    if (!is_dir(UPLOADS_DIR)) {
        @mkdir(UPLOADS_DIR, 0755, true);
    }

    $filename = date('Ymd_His') . '_' . bin2hex(random_bytes(4));

    // Tilfælde 1: Base64 data-URL (fra canvas klient-optimering)
    if (is_string($image_input) && preg_match('/^data:image\/(\w+);base64,/', $image_input, $matches)) {
        $ext = strtolower($matches[1]);
        if ($ext === 'jpeg') $ext = 'jpg';
        if (!in_array($ext, ['jpg', 'png', 'webp'])) {
            $ext = 'jpg';
        }
        $data = substr($image_input, strpos($image_input, ',') + 1);
        $decoded = base64_decode($data);
        if ($decoded === false) {
            throw new Exception('Ugyldigt billedformat');
        }
        $targetFile = $filename . '.' . $ext;
        $destPath = UPLOADS_DIR . '/' . $targetFile;
        if (file_put_contents($destPath, $decoded) === false) {
            throw new Exception('Kunne ikke skrive billedfil til mappen uploads/. Tjek rettigheder.');
        }
        @chmod($destPath, 0644);
        return $targetFile;
    }

    // Tilfælde 2: Almindelig $_FILES upload
    if (is_array($image_input) && isset($image_input['tmp_name']) && is_uploaded_file($image_input['tmp_name'])) {
        $imgInfo = getimagesize($image_input['tmp_name']);
        if (!$imgInfo) {
            throw new Exception('Filen er ikke et gyldigt billede');
        }
        $mime = $imgInfo['mime'];
        $ext = match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            default      => 'jpg'
        };

        $targetFile = $filename . '.' . $ext;
        if (!move_uploaded_file($image_input['tmp_name'], UPLOADS_DIR . '/' . $targetFile)) {
            throw new Exception('Kunne ikke gemme billedet på serveren');
        }
        return $targetFile;
    }

    throw new Exception('Intet billede modtaget');
}
