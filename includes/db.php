<?php
/**
 * Databaseforbindelse og initialisering via SQLite (PDO)
 */

require_once __DIR__ . '/../config.php';

function get_db(): PDO {
    static $db = null;

    if ($db !== null) {
        return $db;
    }

    // Opret mapper hvis de ikke findes
    if (!is_dir(DATA_DIR)) {
        @mkdir(DATA_DIR, 0755, true);
    }
    if (!is_dir(UPLOADS_DIR)) {
        @mkdir(UPLOADS_DIR, 0755, true);
    }

    // Sikr data-mappen med .htaccess, så logbog.db ikke kan downloades af besøgende
    $htaccess = DATA_DIR . '/.htaccess';
    if (!file_exists($htaccess)) {
        @file_put_contents($htaccess, "# Afvis direkte adgang til databasen\n<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n    Order deny,allow\n    Deny from all\n</IfModule>\n");
    }

    // Sikr uploads-mappen mod script-afvikling (uden Options-direktiver der giver 500-fejl på Gigahost)
    $uploadsHtaccess = UPLOADS_DIR . '/.htaccess';
    if (!file_exists($uploadsHtaccess)) {
        @file_put_contents($uploadsHtaccess, "# Forhindr afvikling af scripts i uploads-mappen\n<FilesMatch \"\\.(php|phtml|phar|php3|php4|php5|php7|php8|phps|cgi|pl|py|sh|shtml|html|htm|svg)$\">\n    <IfModule mod_authz_core.c>\n        Require all denied\n    </IfModule>\n    <IfModule !mod_authz_core.c>\n        Order deny,allow\n        Deny from all\n    </IfModule>\n</FilesMatch>\n");
    }

    $dsn = 'sqlite:' . DB_PATH;
    try {
        $db = new PDO($dsn);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $db->exec("PRAGMA foreign_keys = ON;");

        // Opret tabeller hvis de ikke findes
        init_db_schema($db);

        return $db;
    } catch (PDOException $e) {
        // Log fejl uden at lække fuld serversti til besøgende
        error_log('Database fejl: ' . $e->getMessage());
        die('Kunne ikke forbinde til databasen. Tjek tilladelser for mappen data/.');
    }
}

function init_db_schema(PDO $db): void {
    // Posts tabel
    $db->exec("
        CREATE TABLE IF NOT EXISTS posts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            uid TEXT,
            title TEXT NOT NULL,
            slug TEXT,
            content TEXT,
            image_path TEXT NOT NULL,
            location TEXT,
            latitude REAL,
            longitude REAL,
            published_at DATETIME NOT NULL,
            status TEXT NOT NULL DEFAULT 'published',
            og_style TEXT DEFAULT 'notebook',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
        CREATE INDEX IF NOT EXISTS idx_posts_published ON posts(published_at DESC);
    ");

    // Tjek om 'uid' kolonnen findes i en eksisterende tabel
    $cols = $db->query("PRAGMA table_info(posts)")->fetchAll();
    $existingCols = [];
    foreach ($cols as $col) {
        $existingCols[] = $col['name'] ?? '';
    }

    if (!in_array('uid', $existingCols)) {
        $db->exec("ALTER TABLE posts ADD COLUMN uid TEXT");
    }
    $db->exec("CREATE UNIQUE INDEX IF NOT EXISTS idx_posts_uid ON posts(uid)");

    // Tilføj EXIF-kolonner til posts hvis de ikke findes
    if (!in_array('exif_aperture', $existingCols)) {
        $db->exec("ALTER TABLE posts ADD COLUMN exif_aperture TEXT");
    }
    if (!in_array('exif_shutter', $existingCols)) {
        $db->exec("ALTER TABLE posts ADD COLUMN exif_shutter TEXT");
    }
    if (!in_array('exif_iso', $existingCols)) {
        $db->exec("ALTER TABLE posts ADD COLUMN exif_iso TEXT");
    }
    if (!in_array('exif_focal', $existingCols)) {
        $db->exec("ALTER TABLE posts ADD COLUMN exif_focal TEXT");
    }
    if (!in_array('exif_camera', $existingCols)) {
        $db->exec("ALTER TABLE posts ADD COLUMN exif_camera TEXT");
    }
    if (!in_array('og_style', $existingCols)) {
        $db->exec("ALTER TABLE posts ADD COLUMN og_style TEXT DEFAULT 'notebook'");
    }
    if (!in_array('status', $existingCols)) {
        $db->exec("ALTER TABLE posts ADD COLUMN status TEXT NOT NULL DEFAULT 'published'");
    }
    if (!in_array('exif_lens', $existingCols)) {
        $db->exec("ALTER TABLE posts ADD COLUMN exif_lens TEXT");
    }
    $db->exec("CREATE INDEX IF NOT EXISTS idx_posts_status ON posts(status)");

    // Flere billeder pr. indlæg (diptykon / serie)
    $db->exec("
        CREATE TABLE IF NOT EXISTS post_images (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            post_id INTEGER NOT NULL,
            image_path TEXT NOT NULL,
            sort_order INTEGER NOT NULL DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
        );
        CREATE INDEX IF NOT EXISTS idx_post_images_post ON post_images(post_id, sort_order);
    ");

    // Backfill eksisterende posts.image_path ind i post_images hvis de mangler
    $db->exec("
        INSERT INTO post_images (post_id, image_path, sort_order)
        SELECT p.id, p.image_path, 0
        FROM posts p
        WHERE p.image_path IS NOT NULL
          AND p.image_path != ''
          AND NOT EXISTS (SELECT 1 FROM post_images pi WHERE pi.post_id = p.id)
    ");

    // Tags & Post-Tags tabeller til emner
    $db->exec("
        CREATE TABLE IF NOT EXISTS tags (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL UNIQUE COLLATE NOCASE,
            slug TEXT NOT NULL UNIQUE,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
        CREATE INDEX IF NOT EXISTS idx_tags_slug ON tags(slug);

        CREATE TABLE IF NOT EXISTS post_tags (
            post_id INTEGER NOT NULL,
            tag_id INTEGER NOT NULL,
            PRIMARY KEY (post_id, tag_id),
            FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
            FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
        );
        CREATE INDEX IF NOT EXISTS idx_post_tags_post ON post_tags(post_id);
        CREATE INDEX IF NOT EXISTS idx_post_tags_tag ON post_tags(tag_id);
    ");

    // Udfyld manglende uid'er for eksisterende rækker så permalinks altid findes
    $stmtMissing = $db->query("SELECT id, published_at FROM posts WHERE uid IS NULL OR uid = ''");
    $missing = $stmtMissing->fetchAll();
    if (!empty($missing)) {
        $updateStmt = $db->prepare("UPDATE posts SET uid = :uid WHERE id = :id");
        foreach ($missing as $row) {
            $genUid = substr(hash('crc32b', 'logbog_' . $row['id'] . '_' . ($row['published_at'] ?? '')), 0, 6);
            if (strlen($genUid) < 6) {
                $genUid = str_pad($genUid, 6, '0');
            }
            $updateStmt->execute([':uid' => $genUid, ':id' => $row['id']]);
        }
    }

    // Normaliser datoformater i eksisterende indlæg til YYYY-MM-DD HH:MM:SS så sorteringen altid er 100% præcis
    try {
        $stmtDates = $db->query("SELECT id, published_at FROM posts WHERE published_at LIKE '%T%' OR published_at LIKE '%.%' OR published_at LIKE '%/%'");
        $dirtyDates = $stmtDates ? $stmtDates->fetchAll() : [];
        if (!empty($dirtyDates)) {
            $updateDateStmt = $db->prepare("UPDATE posts SET published_at = :pub WHERE id = :id");
            foreach ($dirtyDates as $dRow) {
                $raw = trim((string)$dRow['published_at']);
                $clean = str_replace('T', ' ', $raw);
                if (preg_match('/^(\d{1,2})\.(\d{1,2})\.(\d{4})(?:\s+(\d{1,2}):(\d{1,2})(?::(\d{1,2}))?)?$/', $clean, $m)) {
                    $clean = sprintf('%04d-%02d-%02d %02d:%02d:%02d', 
                        (int)$m[3], (int)$m[2], (int)$m[1], 
                        (int)($m[4] ?? 12), (int)($m[5] ?? 0), (int)($m[6] ?? 0)
                    );
                }
                $ts = strtotime($clean);
                if ($ts) {
                    $norm = date('Y-m-d H:i:s', $ts);
                    $updateDateStmt->execute([':pub' => $norm, ':id' => $dRow['id']]);
                }
            }
        }
    } catch (Throwable $e) {
        // Ignorer eventuelle datomigration fejl
    }

    // Settings tabel (til f.eks. brugerdefineret PIN eller profildata)
    $db->exec("
        CREATE TABLE IF NOT EXISTS settings (
            key TEXT PRIMARY KEY,
            value TEXT NOT NULL
        );
    ");

    // Login forsøg (til rate limiting og brute-force beskyttelse)
    $db->exec("
        CREATE TABLE IF NOT EXISTS login_attempts (
            ip TEXT PRIMARY KEY,
            attempts INTEGER NOT NULL DEFAULT 1,
            last_attempt INTEGER NOT NULL
        );
    ");

    // Private postkort / stille nik fra besøgende (vises kun for fotografen i /app/)
    $db->exec("
        CREATE TABLE IF NOT EXISTS postcards (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            post_id INTEGER NOT NULL,
            kind TEXT NOT NULL DEFAULT 'nod',
            sender_name TEXT,
            sender_contact TEXT,
            message TEXT,
            ip_hash TEXT,
            is_read INTEGER NOT NULL DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
        );
        CREATE INDEX IF NOT EXISTS idx_postcards_created ON postcards(created_at DESC);
        CREATE INDEX IF NOT EXISTS idx_postcards_unread ON postcards(is_read);
        CREATE INDEX IF NOT EXISTS idx_postcards_rate ON postcards(ip_hash, created_at);
    ");

    // Sæt standard PIN i settings hvis den ikke findes
    $stmt = $db->prepare("SELECT value FROM settings WHERE key = 'pin'");
    $stmt->execute();
    if (!$stmt->fetch()) {
        $stmtInsert = $db->prepare("INSERT INTO settings (key, value) VALUES ('pin', :pin)");
        $stmtInsert->execute([':pin' => password_hash(DEFAULT_PIN, PASSWORD_DEFAULT)]);
    }

    // Generer en kryptografisk stærk nøgle til auth-tokens hvis den ikke findes
    $stmtSec = $db->prepare("SELECT value FROM settings WHERE key = 'auth_secret'");
    $stmtSec->execute();
    if (!$stmtSec->fetch()) {
        $stmtInsSec = $db->prepare("INSERT INTO settings (key, value) VALUES ('auth_secret', :sec)");
        $stmtInsSec->execute([':sec' => bin2hex(random_bytes(32))]);
    }

    // Token-version så gamle tokens kan tilbagekaldes ved behov
    $stmtVer = $db->prepare("SELECT value FROM settings WHERE key = 'token_version'");
    $stmtVer->execute();
    if (!$stmtVer->fetch()) {
        $stmtInsVer = $db->prepare("INSERT INTO settings (key, value) VALUES ('token_version', '1')");
    }
}
