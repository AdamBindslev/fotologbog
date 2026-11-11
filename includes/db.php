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
        @file_put_contents($htaccess, "# Afvis direkte adgang til databasen\nOrder deny,allow\nDeny from all\n<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n");
    }

    $dsn = 'sqlite:' . DB_PATH;
    try {
        $db = new PDO($dsn);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        // Opret tabeller hvis de ikke findes
        init_db_schema($db);

        return $db;
    } catch (PDOException $e) {
        // Log fejl og vis en læsevenlig besked
        error_log('Database fejl: ' . $e->getMessage());
        die('Kunne ikke forbinde til databasen. Tjek tilladelser for mappen ' . htmlspecialchars(DATA_DIR));
    }
}

function init_db_schema(PDO $db): void {
    // Posts tabel
    $db->exec("
        CREATE TABLE IF NOT EXISTS posts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            slug TEXT,
            content TEXT,
            image_path TEXT NOT NULL,
            location TEXT,
            latitude REAL,
            longitude REAL,
            published_at DATETIME NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
        CREATE INDEX IF NOT EXISTS idx_posts_published ON posts(published_at DESC);
    ");

    // Settings tabel (til f.eks. brugerdefineret PIN eller profildata)
    $db->exec("
        CREATE TABLE IF NOT EXISTS settings (
            key TEXT PRIMARY KEY,
            value TEXT NOT NULL
        );
    ");

    // Sæt standard PIN i settings hvis den ikke findes
    $stmt = $db->prepare("SELECT value FROM settings WHERE key = 'pin'");
    $stmt->execute();
    if (!$stmt->fetch()) {
        $stmtInsert = $db->prepare("INSERT INTO settings (key, value) VALUES ('pin', :pin)");
        $stmtInsert->execute([':pin' => password_hash(DEFAULT_PIN, PASSWORD_DEFAULT)]);
    }
}
