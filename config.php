<?php
/**
 * Konfiguration for Logbog
 */

// Sæt tidszone til København
date_default_timezone_set('Europe/Copenhagen');

// Indlæs lokale indstillinger hvis filen findes (tillader personlig konfiguration uden at ændre Git)
if (file_exists(__DIR__ . '/config.local.php')) {
    require_once __DIR__ . '/config.local.php';
}

// Generelle indstillinger (standardværdier for det offentlige arkiv)
if (!defined('SITE_TITLE')) define('SITE_TITLE', 'FOTOLOGBOG // SYS.LOG');
if (!defined('SITE_SUBTITLE')) define('SITE_SUBTITLE', '');
if (!defined('SITE_AUTHOR')) define('SITE_AUTHOR', 'Fotograf');
if (!defined('SITE_DESCRIPTION')) define('SITE_DESCRIPTION', 'Daglig visuel fotologbog med fotografier, lokationer og korte refleksioner.');

// Valgfri statistik (f.eks. Counter.dev data-id – lad stå tom for ingen tracking)
if (!defined('COUNTER_DEV_ID')) define('COUNTER_DEV_ID', '');

// Link til projektets kildekode i sidens diskrete kolofon (lad stå tom for at skjule)
if (!defined('COLOPHON_REPO_URL')) define('COLOPHON_REPO_URL', 'https://github.com/AdamBindslev/fotologbog');

// Stier på serveren
define('BASE_DIR', __DIR__);
define('DATA_DIR', BASE_DIR . '/data');
define('UPLOADS_DIR', BASE_DIR . '/uploads');
define('DB_PATH', DATA_DIR . '/logbog.db');

// Sikkerhedsheaders mod MIME-sniffing og Clickjacking
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=(self)');
}

// URLs (brug fast SITE_URL fra config.local.php hvis defineret – beskytter mod Host Header Injection)
if (defined('SITE_URL') && SITE_URL !== '') {
    $parsedSite = parse_url(rtrim(SITE_URL, '/'));
    $basePath = rtrim($parsedSite['path'] ?? '', '/');
    define('BASE_URL', rtrim(SITE_URL, '/'));
} else {
    $trustProxy = defined('TRUST_PROXY_HEADERS') && TRUST_PROXY_HEADERS === true;
    $isHttps = (
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
        (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) ||
        ($trustProxy && !empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') ||
        ($trustProxy && !empty($_SERVER['HTTP_X_FORWARDED_SSL']) && strtolower($_SERVER['HTTP_X_FORWARDED_SSL']) === 'on')
    );
    $protocol = $isHttps ? "https://" : "http://";
    $rawHost = $_SERVER['HTTP_HOST'] ?? 'localhost';
    // Beskyt mod Host Header Injection – tillad kun gyldige domænetegn og evt. portnummer
    $host = preg_match('/^[a-zA-Z0-9.-]+(?::\d+)?$/', $rawHost) ? $rawHost : 'localhost';
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    $basePath = rtrim($scriptDir, '/');
    if (substr($basePath, -4) === '/app') {
        $basePath = substr($basePath, 0, -4);
    }
    define('BASE_URL', $protocol . $host . $basePath);
}
// UPLOADS_URL som rod-relativ URL (/uploads) så den aldrig blokeres af Mixed Content på HTTPS
define('UPLOADS_URL', ($basePath ?: '') . '/uploads');

// Sikkerhed & PIN-kode
// Standard PIN-kode "1234" bruges KUN første gang databasen oprettes.
define('DEFAULT_PIN', '1234');

// Fallback nøgle (den primære kryptografiske nøgle genereres med random_bytes(32) og gemmes i SQLite settings)
define('AUTH_SECRET', 'logbog_fallback_secret_' . hash('sha256', __DIR__));

// Antal indlæg pr. side i feedet
define('POSTS_PER_PAGE', 10);

// Tekst-oversættelse & tilpasning (overskrives via $SITE_TEXTS i config.local.php)
require_once __DIR__ . '/includes/texts.php';
