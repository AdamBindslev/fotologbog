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
    $stmt = $db->query("
        SELECT t.id, t.name, t.slug, COUNT(pt.post_id) as post_count
        FROM tags t
        JOIN post_tags pt ON pt.tag_id = t.id
        GROUP BY t.id, t.name, t.slug
        HAVING post_count > 0
        ORDER BY post_count DESC, t.name ASC
    ");
    return $stmt->fetchAll();
}

/**
 * Henter alle anvendte lokationer med optælling af indlæg
 */
function get_all_locations_with_counts(): array {
    $db = get_db();
    $stmt = $db->query("
        SELECT trim(location) as location, COUNT(*) as post_count
        FROM posts
        WHERE location IS NOT NULL AND trim(location) != ''
        GROUP BY trim(location)
        HAVING post_count > 0
        ORDER BY post_count DESC, location ASC
    ");
    return $stmt->fetchAll();
}

/**
 * Henter et samlet indeks med tags og lokationer vægtet til en ord-sky
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
            'name' => '📍 ' . $l['location'],
            'raw_name' => $l['location'],
            'slug' => slugify($l['location']),
            'count' => $count,
            'url' => BASE_URL . '/?location=' . urlencode($l['location'])
        ];
    }

    // Beregn en visuel vægt (1 til 5) til CSS font-størrelser
    foreach ($items as &$item) {
        $weight = 1;
        if ($maxCount > 1) {
            $weight = 1 + (int)round(($item['count'] - 1) / ($maxCount - 1) * 4);
        }
        $item['weight'] = max(1, min(5, $weight));
    }
    unset($item);

    // Sortér alfabetisk for et ægte register-look
    usort($items, function($a, $b) {
        return strcasecmp($a['raw_name'], $b->raw_name ?? $b['raw_name']);
    });

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
        ORDER BY p.published_at DESC, p.id DESC
        LIMIT :limit OFFSET :offset
    ");
    $stmt->bindValue(':slug', $tagSlug, PDO::PARAM_STR);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Tæller antal indlæg for et bestemt tag
 */
function count_posts_by_tag(string $tagSlug): int {
    $db = get_db();
    $stmt = $db->prepare("
        SELECT COUNT(p.id)
        FROM posts p
        JOIN post_tags pt ON pt.post_id = p.id
        JOIN tags t ON t.id = pt.tag_id
        WHERE t.slug = :slug
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
        WHERE trim(location) = trim(:location)
        ORDER BY published_at DESC, id DESC
        LIMIT :limit OFFSET :offset
    ");
    $stmt->bindValue(':location', $location, PDO::PARAM_STR);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Tæller antal indlæg for en bestemt lokation
 */
function count_posts_by_location(string $location): int {
    $db = get_db();
    $stmt = $db->prepare("
        SELECT COUNT(*) FROM posts 
        WHERE trim(location) = trim(:location)
    ");
    $stmt->execute([':location' => $location]);
    return (int)$stmt->fetchColumn();
}

/**
 * Formaterer en læsevenlig retro EXIF-streng: f.eks. "ƒ/2.8 · 1/250s · ISO 400"
 */
function format_exif(array $post): ?string {
    $parts = [];
    if (!empty($post['exif_aperture'])) {
        $parts[] = $post['exif_aperture'];
    }
    if (!empty($post['exif_shutter'])) {
        $parts[] = $post['exif_shutter'];
    }
    if (!empty($post['exif_iso'])) {
        $parts[] = $post['exif_iso'];
    }

    if (empty($parts)) {
        return null;
    }

    return implode(' · ', $parts);
}

/**
 * Udtrækker blænde, lukkertid og ISO fra et lokalt billede via PHP exif hvis muligt
 */
function extract_exif_from_image_file(string $filePath): array {
    $result = [
        'aperture' => null,
        'shutter' => null,
        'iso' => null
    ];

    if (!function_exists('exif_read_data') || !file_exists($filePath)) {
        return $result;
    }

    try {
        $exif = @exif_read_data($filePath);
        if (!$exif || !is_array($exif)) {
            return $result;
        }

        // Blænde (FNumber)
        if (isset($exif['FNumber'])) {
            $fNumber = $exif['FNumber'];
            if (is_string($fNumber) && str_contains($fNumber, '/')) {
                $p = explode('/', $fNumber);
                $val = (float)$p[0] / max(1, (float)$p[1]);
                $result['aperture'] = 'ƒ/' . round($val, 1);
            } elseif (is_numeric($fNumber)) {
                $result['aperture'] = 'ƒ/' . round((float)$fNumber, 1);
            }
        } elseif (isset($exif['COMPUTED']['ApertureFNumber'])) {
            $result['aperture'] = str_replace('f/', 'ƒ/', $exif['COMPUTED']['ApertureFNumber']);
        }

        // Lukkertid (ExposureTime)
        if (isset($exif['ExposureTime'])) {
            $exp = $exif['ExposureTime'];
            if (is_string($exp) && str_contains($exp, '/')) {
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
        }

        // ISO (ISOSpeedRatings)
        if (isset($exif['ISOSpeedRatings'])) {
            $iso = is_array($exif['ISOSpeedRatings']) ? $exif['ISOSpeedRatings'][0] : $exif['ISOSpeedRatings'];
            $result['iso'] = 'ISO ' . $iso;
        } elseif (isset($exif['ISO'])) {
            $result['iso'] = 'ISO ' . $exif['ISO'];
        }
    } catch (Throwable $e) {
        // Ignorer eventuelle parse fejl på ufuldstændig EXIF
    }

    return $result;
}

