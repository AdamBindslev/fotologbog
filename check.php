<?php
/**
 * Logbog Diagnose og Rettighedstjek (Kun for autoriseret bruger)
 * Besøg: https://ditdomaene.dk/check.php
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';

// Kræv at brugeren er logget ind før systemstatus vises (forhindrer informationslækage)
require_auth(false);

header('Content-Type: text/html; charset=utf-8');

$results = [];

// 1. PHP Version
$results['PHP Version'] = [
    'ok' => version_compare(PHP_VERSION, '8.0.0', '>='),
    'val' => PHP_VERSION,
    'desc' => 'PHP 8.0 eller nyere påkrævet'
];

// 2. PDO SQLite
$hasSqlite = extension_loaded('pdo_sqlite');
$results['PDO SQLite Driver'] = [
    'ok' => $hasSqlite,
    'val' => $hasSqlite ? 'Aktiveret (SQLite v' . (new PDO('sqlite::memory:'))->query('select sqlite_version()')->fetchColumn() . ')' : 'Mangler',
    'desc' => 'Nødvendigt til databasen'
];

// 3. Data mappe skrivbar
$dataDirExists = is_dir(DATA_DIR);
$dataDirWritable = is_writable(DATA_DIR);
$results['Mappen "data/"'] = [
    'ok' => $dataDirWritable,
    'val' => $dataDirWritable ? 'Skrivbar (' . substr(sprintf('%o', fileperms(DATA_DIR)), -4) . ')' : 'IKKE skrivbar',
    'desc' => 'Relativ sti: /data'
];

// 4. logbog.db fil
$dbFile = DATA_DIR . '/logbog.db';
$dbExists = file_exists($dbFile);
$dbWritable = $dbExists ? is_writable($dbFile) : $dataDirWritable;
$results['Databasen "logbog.db"'] = [
    'ok' => $dbWritable,
    'val' => $dbExists ? ($dbWritable ? 'OK & Skrivbar' : 'IKKE skrivbar') : 'Oprettes automatisk',
    'desc' => 'Relativ sti: /data/logbog.db'
];

// 5. Uploads mappe skrivbar
$uploadsDirExists = is_dir(UPLOADS_DIR);
$uploadsDirWritable = is_writable(UPLOADS_DIR);
$results['Mappen "uploads/"'] = [
    'ok' => $uploadsDirWritable,
    'val' => $uploadsDirWritable ? 'Skrivbar (' . substr(sprintf('%o', fileperms(UPLOADS_DIR)), -4) . ')' : 'IKKE skrivbar',
    'desc' => 'Relativ sti: /uploads'
];

// 5b. GD Billedbehandling (til Open Graph delingskort)
$hasGd = extension_loaded('gd') && function_exists('imagecreatetruecolor');
$results['GD Billedbehandling'] = [
    'ok' => $hasGd,
    'val' => $hasGd ? 'Aktiveret (understøtter dynamiske delingskort)' : 'Mangler (delinger bruger rå fotos som fallback)',
    'desc' => 'Påkrævet til automatisk generering af sociale kort (1200x630)'
];

// 6. Base URL og Uploads URL
$results['Konfiguration URL'] = [
    'ok' => true,
    'val' => 'BASE_URL: ' . htmlspecialchars(BASE_URL) . '<br>UPLOADS_URL: ' . htmlspecialchars(UPLOADS_URL),
    'desc' => 'Protokol: ' . (str_starts_with(BASE_URL, 'https') ? 'HTTPS' : 'HTTP')
];
?>
<!DOCTYPE html>
<html lang="da">
<head>
    <meta charset="UTF-8">
    <title>Logbog Systemtjek</title>
    <style>
        body { font-family: -apple-system, sans-serif; background: #faf8f5; color: #242220; padding: 40px 20px; }
        .box { max-width: 600px; margin: 0 auto; background: white; border: 1px solid #e6e2d8; border-radius: 12px; padding: 24px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        h1 { font-size: 20px; margin-bottom: 20px; font-family: monospace; }
        .item { padding: 12px 0; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; }
        .item:last-child { border-bottom: none; }
        .badge { font-weight: bold; padding: 4px 10px; border-radius: 6px; font-size: 13px; font-family: monospace; }
        .badge-ok { background: #dcfce7; color: #166534; }
        .badge-err { background: #fee2e2; color: #991b1b; }
        .desc { font-size: 12px; color: #777; margin-top: 2px; }
    </style>
</head>
<body>
<div class="box">
    <h1>[ SYSTEM STATUS &amp; RETTIGHEDER ]</h1>
    <?php foreach ($results as $name => $r): ?>
        <div class="item">
            <div>
                <strong><?= htmlspecialchars($name) ?></strong>
                <div class="desc"><?= htmlspecialchars($r['desc']) ?></div>
            </div>
            <div style="text-align: right;">
                <span class="badge <?= $r['ok'] ? 'badge-ok' : 'badge-err' ?>"><?= $r['ok'] ? '✓ OK' : '✗ FEJL' ?></span>
                <div style="font-size: 12px; font-family: monospace; margin-top: 4px;"><?= $r['val'] ?></div>
            </div>
        </div>
    <?php endforeach; ?>
    <div style="margin-top: 24px; font-size: 13px; color: #777; border-top: 1px dashed #ccc; padding-top: 12px;">
        Siden er beskyttet med login. Når alt er grønt, kan du også slette <code>check.php</code>.
    </div>
</div>
</body>
</html>
