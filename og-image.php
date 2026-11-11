<?php
/**
 * Logbog – Dynamisk Social Share / Open Graph Image Generator (1200 x 630)
 * 
 * Genererer autentiske, rolige og æstetiske delingskort uden marketing-støj eller CTA-tricks.
 * Fuld support for nordiske karakterer (æ, ø, å, Æ, Ø, Å) på tværs af PHP GD og FreeType.
 * 
 * Understøtter 4 stilarter valgt per indlæg:
 *  - polaroid: Klassisk hvid/creme polaroidramme på mørkt træbord med titel og stempel
 *  - darkroom: 35mm filmnegativ med perforeringer, filmmarkeringer og EXIF
 *  - notebook: Redaktionelt split – venstre side fotografi, højre side manila-notesbogsblad
 *  - raw:      Rent fotografi i 1200x630 format uden rammer eller overlays
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

// Deaktiver fejlvisning i billed-output (fejl logges i stedet)
ini_set('display_errors', '0');
error_reporting(E_ALL);

$param = $_GET['p'] ?? $_GET['id'] ?? null;
if (!$param) {
    http_response_code(400);
    die('Mangler parameter');
}

$post = get_post_by_identifier((string)$param);
if (!$post || empty($post['image_path'])) {
    http_response_code(404);
    die('Indlæg eller billede ikke fundet');
}

$safeUid = !empty($post['uid']) ? $post['uid'] : (string)$post['id'];
$safeUid = preg_replace('/[^a-zA-Z0-9_-]/', '', $safeUid);

// Tilladte stilarter
$validStyles = ['polaroid', 'darkroom', 'notebook', 'raw'];
$style = $_GET['style'] ?? ($post['og_style'] ?? 'polaroid');
if (!in_array($style, $validStyles, true)) {
    $style = 'polaroid';
}

$sourceFile = UPLOADS_DIR . '/' . basename($post['image_path']);
if (!is_file($sourceFile)) {
    http_response_code(404);
    die('Billedfil ikke fundet');
}

// Opret cache-mappe hvis den ikke findes
$cacheDir = UPLOADS_DIR . '/og';
if (!is_dir($cacheDir)) {
    @mkdir($cacheDir, 0775, true);
}

$cacheFile = $cacheDir . '/' . $safeUid . '_' . $style . '.jpg';
$postTime = !empty($post['updated_at']) ? strtotime($post['updated_at']) : strtotime($post['published_at'] ?? 'now');
$sourceTime = filemtime($sourceFile);
$scriptTime = filemtime(__FILE__);
// Hvis enten indlæg, kildebillede eller genereringsscriptet er opdateret, regenereres kortet
$refTime = max($postTime, $sourceTime, $scriptTime);

// Tjek om gyldigt cachet billede allerede findes
$noCache = isset($_GET['nocache']);
if (!$noCache && is_file($cacheFile) && filemtime($cacheFile) >= $refTime) {
    send_image_file($cacheFile);
    exit;
}

// Kontroller om GD-biblioteket er tilgængeligt
if (!extension_loaded('gd') || !function_exists('imagecreatetruecolor')) {
    // Fallback: send det rå billede direkte hvis GD mangler på serveren
    send_image_file($sourceFile);
    exit;
}

// -------------------------------------------------------------
// Billedindlæsning og automatisk EXIF-orienteringskorrektion
// -------------------------------------------------------------
$srcImg = load_image_with_orientation($sourceFile);
if (!$srcImg) {
    send_image_file($sourceFile);
    exit;
}

// -------------------------------------------------------------
// Opret 1200 x 630 lærred
// -------------------------------------------------------------
$canvasW = 1200;
$canvasH = 630;
$canvas = imagecreatetruecolor($canvasW, $canvasH);
if (function_exists('imageantialias')) {
    @imageantialias($canvas, true);
}

// Stier til skrifttyper
$fonts = [
    'courier'      => __DIR__ . '/assets/fonts/courier.ttf',
    'courier_bold' => __DIR__ . '/assets/fonts/courier-bold.ttf',
    'serif'        => __DIR__ . '/assets/fonts/serif.ttf',
    'serif_bold'   => __DIR__ . '/assets/fonts/serif-bold.ttf',
    'display'      => __DIR__ . '/assets/fonts/display.ttf'
];

// -------------------------------------------------------------
// Render den valgte stil
// -------------------------------------------------------------
switch ($style) {
    case 'darkroom':
        render_darkroom_style($canvas, $srcImg, $post, $fonts);
        break;

    case 'notebook':
        render_notebook_style($canvas, $srcImg, $post, $fonts);
        break;

    case 'raw':
        render_raw_style($canvas, $srcImg, $post);
        break;

    case 'polaroid':
    default:
        render_polaroid_style($canvas, $srcImg, $post, $fonts);
        break;
}

// Frigiv kildebilledet
imagedestroy($srcImg);

// Gem til cache
@imagejpeg($canvas, $cacheFile, 90);

// Send til klient
header('Content-Type: image/jpeg');
header('Cache-Control: public, max-age=604800, stale-while-revalidate=86400');
imagejpeg($canvas, null, 90);
imagedestroy($canvas);
exit;

// =========================================================================
// TEKST- OG NORDISK KARAKTER-HÅNDTERING
// =========================================================================

/**
 * Konverterer UTF-8 tekst til numeriske tegnreferencer (&#...;), som FreeType i GD altid forstår fejlfrit.
 * Dette sikrer, at æ, ø, å, Æ, Ø, Å, é osv. altid gengives korrekt uanset server-locale og FreeType-version.
 */
function to_gd_text(?string $text): string {
    if ($text === null || $text === '') {
        return '';
    }
    // 1. Afkod eventuelle HTML-entiteter (hvis teksten f.eks. indeholder &amp; eller &oslash;)
    $clean = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

    // 2. Konverter alle non-ASCII tegn (>= 128) til numeriske HTML-entiteter (&#230; osv.)
    // FreeType i GD oversætter automatisk numeriske entiteter direkte til Unicode-glyffen i skrifttypen
    if (function_exists('mb_encode_numericentity')) {
        return mb_encode_numericentity($clean, [0x80, 0x10ffff, 0, 0x10ffff], 'UTF-8');
    }

    return preg_replace_callback('/[\x80-\x{10ffff}]/u', function ($m) {
        $char = $m[0];
        $codepoint = 0;
        $len = strlen($char);
        if ($len === 2) {
            $codepoint = ((ord($char[0]) & 0x1f) << 6) | (ord($char[1]) & 0x3f);
        } elseif ($len === 3) {
            $codepoint = ((ord($char[0]) & 0x0f) << 12) | ((ord($char[1]) & 0x3f) << 6) | (ord($char[2]) & 0x3f);
        } elseif ($len === 4) {
            $codepoint = ((ord($char[0]) & 0x07) << 18) | ((ord($char[1]) & 0x3f) << 12) | ((ord($char[2]) & 0x3f) << 6) | (ord($char[3]) & 0x3f);
        }
        return '&#' . $codepoint . ';';
    }, $clean);
}

/**
 * Fallback til indbyggede GD bitmap-skrifttyper (imagestring), som kun understøtter ISO-8859-1 (Latin-1).
 */
function to_latin_text(?string $text): string {
    if ($text === null || $text === '') {
        return '';
    }
    $clean = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return mb_convert_encoding($clean, 'ISO-8859-1', 'UTF-8');
}

/**
 * Tegner en tekstlinje robust med FreeType, eller falder pænt tilbage til imagestring
 */
function gd_draw_single_line($canvas, ?string $fontFile, int $fontSize, int $x, int $y, int $color, string $text): void {
    if ($fontFile && is_file($fontFile) && function_exists('imagettftext')) {
        @imagettftext($canvas, $fontSize, 0, $x, $y, $color, $fontFile, to_gd_text($text));
    } else {
        $latin = to_latin_text($text);
        $gdFont = $fontSize >= 16 ? 5 : ($fontSize >= 12 ? 4 : 3);
        $fontH = imagefontheight($gdFont);
        imagestring($canvas, $gdFont, $x, max(0, $y - $fontH), $latin, $color);
    }
}

// =========================================================================
// STILART 1: POLAROID
// =========================================================================
function render_polaroid_style($canvas, $srcImg, array $post, array $fonts): void {
    $w = 1200;
    $h = 630;

    // 1. Baggrund: Dyb, varm mørk skrivebordsflade (#231c17)
    $bg = imagecolorallocate($canvas, 35, 28, 23);
    imagefilledrectangle($canvas, 0, 0, $w, $h, $bg);

    // Dæmpet mørkere kant/vignette
    $borderDark = imagecolorallocate($canvas, 22, 17, 14);
    for ($i = 0; $i < 16; $i++) {
        imagerectangle($canvas, $i, $i, $w - 1 - $i, $h - 1 - $i, $borderDark);
    }

    // 2. Polaroid kort: 590 x 554 px, centreret
    $cardW = 590;
    $cardH = 554;
    $cardX = (int)(($w - $cardW) / 2);
    $cardY = (int)(($h - $cardH) / 2);

    // Blød skygge under kortet
    for ($s = 10; $s >= 1; $s--) {
        $alpha = (int)(127 - ($s * 3.5));
        $shadow = imagecolorallocatealpha($canvas, 10, 8, 6, max(0, min(127, $alpha)));
        imagefilledrectangle($canvas, $cardX - $s + 2, $cardY + 3, $cardX + $cardW + $s + 2, $cardY + $cardH + $s + 5, $shadow);
    }

    // Hvid/creme polaroidramme (#f8f5ee)
    $paperColor = imagecolorallocate($canvas, 248, 245, 238);
    imagefilledrectangle($canvas, $cardX, $cardY, $cardX + $cardW, $cardY + $cardH, $paperColor);

    // Subtil 1px papirkant
    $cardBorder = imagecolorallocate($canvas, 220, 213, 198);
    imagerectangle($canvas, $cardX, $cardY, $cardX + $cardW, $cardY + $cardH, $cardBorder);

    // 3. Foto-areal inde i polaroidet
    $photoMargin = 28;
    $photoW = $cardW - ($photoMargin * 2); // 534 px
    $photoH = 416;
    $photoX = $cardX + $photoMargin;
    $photoY = $cardY + $photoMargin;

    // Kopiér og crop fotoet præcist
    copy_image_cover($canvas, $srcImg, $photoX, $photoY, $photoW, $photoH);

    // Fin 1px indre kant om fotoet
    $photoBorder = imagecolorallocate($canvas, 208, 199, 182);
    imagerectangle($canvas, $photoX, $photoY, $photoX + $photoW, $photoY + $photoH, $photoBorder);

    // 4. Polaroid-hage (nederste tekstflade, ren og rolig)
    $chinY = $photoY + $photoH;
    $title = trim($post['title'] ?? 'Fotolog');
    if (mb_strlen($title, 'UTF-8') > 36) {
        $title = mb_substr($title, 0, 35, 'UTF-8') . '…';
    }

    // Titel i typewriter/skrivemaskine skrift
    $inkColor = imagecolorallocate($canvas, 26, 23, 21);
    $fontTitle = is_file($fonts['courier_bold']) ? $fonts['courier_bold'] : null;
    gd_draw_single_line($canvas, $fontTitle, 18, $photoX + 4, $chinY + 54, $inkColor, $title);

    // Dato- og stedstempel i terracotta/rød blæk (roligt og diskret i hjørnet)
    $dateStr = format_stamp_date($post['published_at']);
    $locStr = !empty($post['location']) ? ' · ' . mb_strtoupper(trim($post['location']), 'UTF-8') : '';
    $stampText = $dateStr . $locStr;
    if (mb_strlen($stampText, 'UTF-8') > 32) {
        $stampText = mb_substr($stampText, 0, 31, 'UTF-8') . '…';
    }

    $stampColor = imagecolorallocate($canvas, 181, 52, 36); // #b53424
    $fontStamp = is_file($fonts['courier']) ? $fonts['courier'] : $fontTitle;

    if ($fontStamp && function_exists('imagettfbbox')) {
        $bbox = @imagettfbbox(10, 0, $fontStamp, to_gd_text($stampText));
        $stampW = $bbox ? abs($bbox[2] - $bbox[0]) : 180;
        $stampX = ($cardX + $cardW - $photoMargin) - $stampW - 8;
        $stampY = $chinY + 84;
        gd_draw_single_line($canvas, $fontStamp, 10, max($photoX + 4, $stampX), $stampY, $stampColor, $stampText);
    } else {
        gd_draw_single_line($canvas, null, 10, $photoX + 4, $chinY + 84, $stampColor, $stampText);
    }
}

// =========================================================================
// STILART 2: 35MM MØRKEKAMMER / NEGATIV
// =========================================================================
function render_darkroom_style($canvas, $srcImg, array $post, array $fonts): void {
    $w = 1200;
    $h = 630;

    // 1. Mørkekammer sort baggrund
    $bg = imagecolorallocate($canvas, 14, 14, 14);
    imagefilledrectangle($canvas, 0, 0, $w, $h, $bg);

    // 2. 35mm film perforeringer (sprocket holes)
    $sprocketFill = imagecolorallocate($canvas, 7, 7, 7);
    $sprocketBorder = imagecolorallocate($canvas, 32, 32, 32);
    $sprocketW = 16;
    $sprocketH = 26;
    $spacing = 46;

    for ($x = 18; $x < $w - 20; $x += $spacing) {
        // Top perforering
        imagefilledrectangle($canvas, $x, 14, $x + $sprocketW, 14 + $sprocketH, $sprocketFill);
        imagerectangle($canvas, $x, 14, $x + $sprocketW, 14 + $sprocketH, $sprocketBorder);

        // Bund perforering
        imagefilledrectangle($canvas, $x, $h - 14 - $sprocketH, $x + $sprocketW, $h - 14, $sprocketFill);
        imagerectangle($canvas, $x, $h - 14 - $sprocketH, $x + $sprocketW, $h - 14, $sprocketBorder);
    }

    // 3. Filmkant markeringer i dæmpet ravgul / orange (#d68331)
    $amber = imagecolorallocate($canvas, 206, 126, 44);
    $fontMono = is_file($fonts['courier']) ? $fonts['courier'] : null;

    $cam = !empty($post['exif_camera']) ? mb_strtoupper(trim($post['exif_camera']), 'UTF-8') : '35MM ANALOG';
    $topFilmLabel = '▶ 24A     ' . $cam . '     SAFETY FILM';
    $dateStamp = date('d.m.Y', strtotime($post['published_at']));
    $bottomFilmLabel = '24     SYS.LOG // FOTO     ' . $dateStamp;

    gd_draw_single_line($canvas, $fontMono, 10, 36, 52, $amber, $topFilmLabel);
    gd_draw_single_line($canvas, $fontMono, 10, 36, $h - 48, $amber, $bottomFilmLabel);

    // 4. Centralt filmbillede
    $frameX = 80;
    $frameY = 66;
    $frameW = 1040;
    $frameH = 496;

    copy_image_cover($canvas, $srcImg, $frameX, $frameY, $frameW, $frameH);

    // Filmramme kant
    $frameBorder = imagecolorallocate($canvas, 36, 36, 36);
    imagerectangle($canvas, $frameX, $frameY, $frameX + $frameW, $frameY + $frameH, $frameBorder);

    // Dæmpet mørkt panel i bunden af billedet til titel & EXIF
    $panelH = 70;
    $panelY = $frameY + $frameH - $panelH;
    for ($py = $panelY; $py <= $frameY + $frameH; $py++) {
        $pct = ($py - $panelY) / $panelH;
        $alpha = (int)(127 - ($pct * 95)); // Blød opadgående gradient
        $barColor = imagecolorallocatealpha($canvas, 10, 10, 10, max(0, min(127, $alpha)));
        imageline($canvas, $frameX, $py, $frameX + $frameW, $py, $barColor);
    }

    // Titel i filmrammen
    $white = imagecolorallocate($canvas, 245, 242, 236);
    $title = trim($post['title'] ?? '');
    gd_draw_single_line($canvas, $fonts['courier_bold'] ?? $fontMono, 16, $frameX + 24, $panelY + 36, $white, $title);

    // EXIF data under titlen hvis til stede
    $exif = format_exif($post);
    if ($exif) {
        $faintText = imagecolorallocate($canvas, 175, 168, 158);
        gd_draw_single_line($canvas, $fontMono, 10, $frameX + 24, $panelY + 56, $faintText, $exif);
    }
}

// =========================================================================
// STILART 3: NOTESBOGSARK (SPLIT)
// =========================================================================
function render_notebook_style($canvas, $srcImg, array $post, array $fonts): void {
    $w = 1200;
    $h = 630;

    // 1. Venstre side: Fotografi (620 px bredt)
    $splitX = 620;
    copy_image_cover($canvas, $srcImg, 0, 0, $splitX, $h);

    // 2. Højre side: Manila papirark (#fbf7ee)
    $paperBg = imagecolorallocate($canvas, 251, 247, 238);
    imagefilledrectangle($canvas, $splitX, 0, $w, $h, $paperBg);

    // Dæmpet sepia kant mellem foto og papir
    $seamColor = imagecolorallocate($canvas, 218, 209, 192);
    imageline($canvas, $splitX, 0, $splitX, $h, $seamColor);

    // 3. Spiralhuller langs papirkanten (taktil notesbogsfornemmelse)
    $holeDark = imagecolorallocate($canvas, 38, 30, 24);
    $holeRing = imagecolorallocate($canvas, 185, 175, 160);
    $holeRadius = 7;
    $step = 42;

    for ($y = 28; $y < $h - 10; $y += $step) {
        imagefilledellipse($canvas, $splitX + 16, $y, $holeRadius * 2, $holeRadius * 2, $holeDark);
        imageellipse($canvas, $splitX + 16, $y, ($holeRadius * 2) + 2, ($holeRadius * 2) + 2, $holeRing);
    }

    // 4. Tekstindhold på manila-siden
    $contentX = $splitX + 54;
    $maxW = $w - $contentX - 48; // ca. 478 px

    // Top: Analog dato og lokation i terrakotta rød (#b53424)
    $redInk = imagecolorallocate($canvas, 181, 52, 36);
    $dateStr = format_stamp_date($post['published_at']);
    $locStr = !empty($post['location']) ? ' · ' . mb_strtoupper(trim($post['location']), 'UTF-8') : '';
    $headerText = 'LOG // ' . $dateStr . $locStr;

    $fontCourier = is_file($fonts['courier']) ? $fonts['courier'] : null;
    $fontSerifBold = is_file($fonts['serif_bold']) ? $fonts['serif_bold'] : null;

    gd_draw_single_line($canvas, $fontCourier, 11, $contentX, 85, $redInk, $headerText);

    // Fin skillelinje
    $ruleColor = imagecolorallocate($canvas, 222, 213, 194);
    imageline($canvas, $contentX, 105, $contentX + $maxW, 105, $ruleColor);

    // Overskrift i smuk serif typografi
    $titleInk = imagecolorallocate($canvas, 24, 21, 19);
    $title = trim($post['title'] ?? '');

    draw_wrapped_text($canvas, $fontSerifBold, 26, $contentX, 165, $titleInk, $title, $maxW, 38, 3);

    // Uddrag af logbogsnoterne (hvis udfyldt) – roligt og autentisk
    $rawContent = trim($post['content'] ?? '');
    if (!empty($rawContent)) {
        // Fjern eventuel markdown formatering
        $cleanNote = preg_replace('/[*_=#]/u', '', $rawContent);
        $cleanNote = preg_replace('/\s+/u', ' ', $cleanNote);
        $cleanNote = trim($cleanNote);

        $noteInk = imagecolorallocate($canvas, 80, 72, 64);
        draw_wrapped_text($canvas, $fontCourier, 12, $contentX, 330, $noteInk, $cleanNote, $maxW, 22, 5);
    }

    // Bund: EXIF kameradata hvis tilgængelig
    $exif = format_exif($post);
    if ($exif) {
        $faintSepia = imagecolorallocate($canvas, 145, 133, 121);
        gd_draw_single_line($canvas, $fontCourier, 10, $contentX, $h - 55, $faintSepia, '⌀ ' . $exif);
    }
}

// =========================================================================
// STILART 4: RENT FOTOGRAFI (RAW)
// =========================================================================
function render_raw_style($canvas, $srcImg, array $post): void {
    $w = 1200;
    $h = 630;

    $srcW = imagesx($srcImg);
    $srcH = imagesy($srcImg);
    $aspect = $srcW / max(1, $srcH);

    if ($aspect >= 1.45) {
        // Landskabsbillede: beskær og fyld hele 1200 x 630
        copy_image_cover($canvas, $srcImg, 0, 0, $w, $h);
    } else {
        // Højformat eller kvadratisk: mørk, neutral baggrund med det skarpe foto centreret
        $bg = imagecolorallocate($canvas, 18, 16, 14);
        imagefilledrectangle($canvas, 0, 0, $w, $h, $bg);

        // Skaler det centrale foto proportionelt
        $fitH = $h;
        $fitW = (int)($fitH * $aspect);
        $offsetX = (int)(($w - $fitW) / 2);

        // Subtil skygge
        $shadow = imagecolorallocatealpha($canvas, 0, 0, 0, 80);
        imagefilledrectangle($canvas, $offsetX - 6, 0, $offsetX + $fitW + 6, $h, $shadow);

        imagecopyresampled($canvas, $srcImg, $offsetX, 0, 0, 0, $fitW, $fitH, $srcW, $srcH);
    }
}

// =========================================================================
// HJÆLPEFUNKTIONER
// =========================================================================

/**
 * Indlæser et billede og korrigerer automatisk rotation ifølge EXIF orientation
 */
function load_image_with_orientation(string $filePath) {
    $info = @getimagesize($filePath);
    if (!$info) {
        return null;
    }

    $mime = $info['mime'] ?? '';
    $img = null;

    if ($mime === 'image/jpeg' || $mime === 'image/pjpeg') {
        $img = @imagecreatefromjpeg($filePath);
    } elseif ($mime === 'image/png') {
        $img = @imagecreatefrompng($filePath);
    } elseif ($mime === 'image/webp') {
        $img = function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($filePath) : null;
    }

    if (!$img) {
        return null;
    }

    // Tjek EXIF orientation hvis tilgængeligt
    if (function_exists('exif_read_data') && ($mime === 'image/jpeg' || $mime === 'image/pjpeg')) {
        $exif = @exif_read_data($filePath);
        $orientation = $exif['Orientation'] ?? 1;

        switch ($orientation) {
            case 3:
                $rotated = imagerotate($img, 180, 0);
                imagedestroy($img);
                $img = $rotated;
                break;
            case 6:
                $rotated = imagerotate($img, -90, 0);
                imagedestroy($img);
                $img = $rotated;
                break;
            case 8:
                $rotated = imagerotate($img, 90, 0);
                imagedestroy($img);
                $img = $rotated;
                break;
        }
    }

    return $img;
}

/**
 * Kopiér og crop et kildebillede så det dækker målrektanglet proportionelt (object-fit: cover)
 */
function copy_image_cover($dst, $src, int $dstX, int $dstY, int $dstW, int $dstH): void {
    $srcW = imagesx($src);
    $srcH = imagesy($src);

    $srcAspect = $srcW / max(1, $srcH);
    $dstAspect = $dstW / max(1, $dstH);

    if ($srcAspect > $dstAspect) {
        // Kilden er bredere: crop siderne
        $cropW = (int)($srcH * $dstAspect);
        $cropH = $srcH;
        $cropX = (int)(($srcW - $cropW) / 2);
        $cropY = 0;
    } else {
        // Kilden er højere: crop top og bund
        $cropW = $srcW;
        $cropH = (int)($srcW / $dstAspect);
        $cropX = 0;
        $cropY = (int)(($srcH - $cropH) / 2);
    }

    imagecopyresampled($dst, $src, $dstX, $dstY, $cropX, $cropY, $dstW, $dstH, $cropW, $cropH);
}

/**
 * Sender en eksisterende billedfil direkte med korrekte cache- og MIME-headers
 */
function send_image_file(string $file): void {
    $mime = 'image/jpeg';
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    if ($ext === 'png') $mime = 'image/png';
    elseif ($ext === 'webp') $mime = 'image/webp';

    header('Content-Type: ' . $mime);
    header('Content-Length: ' . filesize($file));
    header('Cache-Control: public, max-age=604800, stale-while-revalidate=86400');
    header('ETag: "' . md5_file($file) . '"');
    readfile($file);
}

/**
 * Formaterer en dato til stempel (f.eks. "11. NOV 2026")
 */
function format_stamp_date(string $dateStr): string {
    $ts = strtotime($dateStr);
    if (!$ts) return date('d.m.Y');
    $months = [1=>'JAN', 2=>'FEB', 3=>'MAR', 4=>'APR', 5=>'MAJ', 6=>'JUN', 7=>'JUL', 8=>'AUG', 9=>'SEP', 10=>'OKT', 11=>'NOV', 12=>'DEC'];
    $m = (int)date('n', $ts);
    return date('d', $ts) . '. ' . ($months[$m] ?? date('M', $ts)) . ' ' . date('Y', $ts);
}

/**
 * Tegner ombrudt tekst over flere linjer via imagettftext med fuld support for nordiske tegn
 */
function draw_wrapped_text($image, ?string $fontFile, int $fontSize, int $x, int $y, int $color, string $text, int $maxWidth, int $lineHeight, int $maxLines = 4): void {
    $cleanText = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $words = preg_split('/\s+/u', $cleanText, -1, PREG_SPLIT_NO_EMPTY);
    if (empty($words)) {
        return;
    }

    $lines = [];
    $currentLine = '';

    $useTtf = $fontFile && is_file($fontFile) && function_exists('imagettftext') && function_exists('imagettfbbox');

    foreach ($words as $word) {
        $testLine = $currentLine === '' ? $word : $currentLine . ' ' . $word;
        $lineWidth = 0;

        if ($useTtf) {
            $bbox = @imagettfbbox($fontSize, 0, $fontFile, to_gd_text($testLine));
            $lineWidth = $bbox ? abs($bbox[2] - $bbox[0]) : 0;
        } else {
            $lineWidth = strlen(to_latin_text($testLine)) * imagefontwidth(4);
        }

        if ($lineWidth > $maxWidth && $currentLine !== '') {
            $lines[] = $currentLine;
            $currentLine = $word;
            if (count($lines) >= $maxLines - 1) {
                break;
            }
        } else {
            $currentLine = $testLine;
        }
    }
    if ($currentLine !== '' && count($lines) < $maxLines) {
        $lines[] = $currentLine;
    }

    $currentY = $y;
    foreach ($lines as $idx => $line) {
        if ($idx === $maxLines - 1 && count($words) > count(explode(' ', implode(' ', $lines)))) {
            $line .= '…';
        }
        gd_draw_single_line($image, $fontFile, $fontSize, $x, $currentY, $color, $line);
        $currentY += $lineHeight;
    }
}
