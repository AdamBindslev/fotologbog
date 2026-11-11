<?php
/**
 * Konfiguration for Logbog
 */

// Sæt tidszone til København
date_default_timezone_set('Europe/Copenhagen');

// Generelle indstillinger
define('SITE_TITLE', 'ADAM BINDSLEV // LOGBOG');
define('SITE_SUBTITLE', 'SYS.LOG // ANALOG – Et billede og en tanke om dagen');
define('SITE_AUTHOR', 'Adam Bindslev');
define('SITE_DESCRIPTION', 'Daglig visuel logbog med fotografier, lokationer og korte refleksioner.');

// Stier på serveren
define('BASE_DIR', __DIR__);
define('DATA_DIR', BASE_DIR . '/data');
define('UPLOADS_DIR', BASE_DIR . '/uploads');
define('DB_PATH', DATA_DIR . '/logbog.db');

// URLs (justeres automatisk hvis logbogen ligger i en undermappe eller rod)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$basePath = rtrim($scriptDir, '/');
if (substr($basePath, -4) === '/app') {
    $basePath = substr($basePath, 0, -4);
}
define('BASE_URL', $protocol . $host . $basePath);
define('UPLOADS_URL', BASE_URL . '/uploads');

// Sikkerhed & PIN-kode
// Standard PIN-kode er "1234".
// Du kan ændre den her, eller lave et saltet hash:
define('DEFAULT_PIN', '1234');

// En hemmelig nøgle brugt til at signere sessions/tokens
define('AUTH_SECRET', 'logbog_secret_key_change_me_if_desired_' . md5(__DIR__));

// Antal indlæg pr. side i feedet
define('POSTS_PER_PAGE', 10);
