<?php
/**
 * Logbog Diagnose og Rettighedstjek
 * Besøg: https://log.adambindslev.dk/check.php
 */

require_once __DIR__ . '/config.php';

header('Content-Type: text/html; charset=utf-8');

$results = [];

// 1. PHP Version
$results['PHP Version'] = [
    'ok' => version_compare(PHP_VERSION, '7.4.0', '>='),
    'val' => PHP_VERSION,
    'desc' => 'PHP 7.4 eller nyere påkrævet'
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
if (!$dataDirWritable && $dataDirExists) {
    @chmod(DATA_DIR, 0775);
    $dataDirWritable = is_writable(DATA_DIR);
}
$results['Mappen "data/"'] = [
    'ok' => $dataDirWritable,
    'val' => $dataDirWritable ? 'Skrivbar (' . substr(sprintf('%o', fileperms(DATA_DIR)), -4) . ')' : 'IKKE skrivbar',
    'desc' => 'Sti: ' . DATA_DIR
];

// 4. logbog.db fil
$dbFile = DATA_DIR . '/logbog.db';
$dbExists = file_exists($dbFile);
$dbWritable = $dbExists ? is_writable($dbFile) : $dataDirWritable;
if ($dbExists && !$dbWritable) {
    @chmod($dbFile, 0664);
    $dbWritable = is_writable($dbFile);
}
$results['Databasen "logbog.db"'] = [
    'ok' => $dbWritable,
    'val' => $dbExists ? ($dbWritable ? 'OK & Skrivbar' : 'IKKE skrivbar') : 'Oprettes automatisk ved første indlæg',
    'desc' => 'Sti: ' . $dbFile
];

// 5. Uploads mappe skrivbar
$uploadsDirExists = is_dir(UPLOADS_DIR);
$uploadsDirWritable = is_writable(UPLOADS_DIR);
if (!$uploadsDirWritable && $uploadsDirExists) {
    @chmod(UPLOADS_DIR, 0775);
    $uploadsDirWritable = is_writable(UPLOADS_DIR);
}
$results['Mappen "uploads/"'] = [
    'ok' => $uploadsDirWritable,
    'val' => $uploadsDirWritable ? 'Skrivbar (' . substr(sprintf('%o', fileperms(UPLOADS_DIR)), -4) . ')' : 'IKKE skrivbar',
    'desc' => 'Sti: ' . UPLOADS_DIR
];

// 6. Test billedskrivning
$testImgFile = UPLOADS_DIR . '/test_write.txt';
$canWriteImg = @file_put_contents($testImgFile, 'test');
if ($canWriteImg !== false) {
    @unlink($testImgFile);
}
$results['Test skrivning i uploads/'] = [
    'ok' => ($canWriteImg !== false),
    'val' => ($canWriteImg !== false) ? 'Testfil kunne skrives' : 'KUNNE IKKE SKRIVE FIL',
    'desc' => 'Billeder kan gemmes uden problemer'
];

// 7. Base URL og Uploads URL
$results['Konfiguration URL'] = [
    'ok' => true,
    'val' => 'BASE_URL: ' . BASE_URL . '<br>UPLOADS_URL: ' . UPLOADS_URL,
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
    <h1>[ SYSTEM STATUS & RETTIGHEDER ]</h1>
    <?php foreach ($results as $name => $r): ?>
        <div class="item">
            <div>
                <strong><?= htmlspecialchars($name) ?></strong>
                <div class="desc"><?= $r['desc'] ?></div>
            </div>
            <div style="text-align: right;">
                <span class="badge <?= $r['ok'] ? 'badge-ok' : 'badge-err' ?>"><?= $r['ok'] ? '✓ OK' : '✗ FEJL' ?></span>
                <div style="font-size: 12px; font-family: monospace; margin-top: 4px;"><?= $r['val'] ?></div>
            </div>
        </div>
    <?php endforeach; ?>
    <div style="margin-top: 24px; font-size: 13px; color: #777; border-top: 1px dashed #ccc; padding-top: 12px;">
        💡 Når alt er grønt, kan du trygt slette <code>check.php</code> igen.
    </div>
</div>
</body>
</html>
