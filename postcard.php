<?php
/**
 * Offentligt endepunkt til private postkort & stille nik.
 * Bevidst adskilt fra /app/ så administrationsstien forbliver skjult.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

function postcard_reply(array $data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    postcard_reply(['error' => 'Kun POST-anmodninger er tilladt'], 405);
}

$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
$rawInput = file_get_contents('php://input');
$jsonData = [];
if (str_contains($contentType, 'application/json') && !empty($rawInput)) {
    $jsonData = json_decode($rawInput, true) ?: [];
}
$input = array_merge($_POST, $jsonData);

// Honeypot mod simple spam-bots (svarer venligt uden at gemme)
$honeypot = trim((string)($input['website'] ?? ''));
if ($honeypot !== '') {
    postcard_reply([
        'success' => true,
        'kind' => 'nod',
        'message' => 'Tak — dit postkort er stemplet og lagt i fotografens indbakke.'
    ]);
}

$postId = (int)($input['post_id'] ?? 0);
if ($postId <= 0) {
    postcard_reply(['error' => 'Ugyldigt indlæg'], 400);
}

$db = get_db();

// Bekræft at indlægget findes og er offentliggjort
$stmtPost = $db->prepare("SELECT id FROM posts WHERE id = :id AND status = 'published'");
$stmtPost->execute([':id' => $postId]);
if (!$stmtPost->fetch()) {
    postcard_reply(['error' => 'Indlægget blev ikke fundet'], 404);
}

// Anonymiseret IP-hash til rate limiting (gemmer aldrig rå IP-adresser på besøgende)
$ipHash = hash('sha256', get_client_ip() . '|' . AUTH_SECRET);

// Rate limit 1: Maks 1 postkort pr. indlæg pr. IP-hash inden for 10 minutter
$stmtRecentPost = $db->prepare("
    SELECT COUNT(*) FROM postcards
    WHERE post_id = :post_id AND ip_hash = :ip_hash
      AND created_at >= datetime('now', '-10 minutes')
");
$stmtRecentPost->execute([':post_id' => $postId, ':ip_hash' => $ipHash]);
if ((int)$stmtRecentPost->fetchColumn() > 0) {
    postcard_reply(['error' => t('postcard_rate_post_error')], 429);
}

// Rate limit 2: Maks 10 postkort pr. IP-hash i timen
$stmtHourly = $db->prepare("
    SELECT COUNT(*) FROM postcards
    WHERE ip_hash = :ip_hash
      AND created_at >= datetime('now', '-1 hour')
");
$stmtHourly->execute([':ip_hash' => $ipHash]);
if ((int)$stmtHourly->fetchColumn() >= 10) {
    postcard_reply(['error' => t('postcard_rate_hour_error')], 429);
}

$senderName = trim((string)($input['sender_name'] ?? ''));
$senderContact = trim((string)($input['sender_contact'] ?? ''));
$message = trim((string)($input['message'] ?? ''));

$senderName = mb_substr($senderName, 0, 80);
$senderContact = mb_substr($senderContact, 0, 120);
$message = mb_substr($message, 0, 1000);

$kind = ($message !== '') ? 'note' : 'nod';

$stmtInsert = $db->prepare("
    INSERT INTO postcards (post_id, kind, sender_name, sender_contact, message, ip_hash, is_read)
    VALUES (:post_id, :kind, :sender_name, :sender_contact, :message, :ip_hash, 0)
");
$stmtInsert->execute([
    ':post_id' => $postId,
    ':kind' => $kind,
    ':sender_name' => $senderName !== '' ? $senderName : null,
    ':sender_contact' => $senderContact !== '' ? $senderContact : null,
    ':message' => $message !== '' ? $message : null,
    ':ip_hash' => $ipHash
]);

postcard_reply([
    'success' => true,
    'kind' => $kind,
    'message' => $kind === 'note' ? t('postcard_sent_note') : t('postcard_sent_nod')
]);
