<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';

$param = $_GET['p'] ?? $_GET['id'] ?? null;
if (!$param) {
    http_response_code(404);
    die('Indlægget blev ikke fundet. <a href="' . BASE_URL . '/">Gå til forsiden</a>');
}

$post = get_post_by_identifier((string)$param);

if (!$post) {
    http_response_code(404);
    die('Indlægget blev ikke fundet. <a href="' . BASE_URL . '/">Gå til forsiden</a>');
}

$uid = !empty($post['uid']) ? $post['uid'] : (string)$post['id'];
$postUrl = BASE_URL . '/post.php?p=' . htmlspecialchars($uid);
$postTags = get_post_tags((int)$post['id']);
$exifStr = format_exif($post);
$headerStamp = format_analog_header($post['published_at'], $post['location'] ?? null);
$pageTitle = htmlspecialchars($post['title']) . ' – ' . htmlspecialchars(SITE_TITLE);
$imageUrl = !empty($post['image_path']) ? UPLOADS_URL . '/' . htmlspecialchars($post['image_path']) : '';
$excerpt = mb_substr(strip_tags($post['content'] ?? ''), 0, 160, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="da">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <meta name="description" content="<?= htmlspecialchars($excerpt) ?>">

    <!-- Social & OpenGraph -->
    <meta property="og:title" content="<?= htmlspecialchars($post['title']) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($excerpt) ?>">
    <meta property="og:type" content="article">
    <meta property="og:url" content="<?= $postUrl ?>">
    <?php if ($imageUrl): ?>
    <meta property="og:image" content="<?= $imageUrl ?>">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:image" content="<?= $imageUrl ?>">
    <?php endif; ?>

    <!-- Fonts: Bebas Neue (Vintage Display) + Fraunces (Retro Serif) + Courier Prime (Typewriter) + JetBrains Mono -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Courier+Prime:ital,wght@0,400;0,700;1,400&family=Fraunces:ital,opsz,wght@0,9..144,500;0,9..144,600;0,9..144,700;1,9..144,400&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <link rel="alternate" type="application/rss+xml" title="<?= htmlspecialchars(SITE_TITLE) ?> RSS Feed" href="<?= BASE_URL ?>/rss.php">

    <!-- Counter.dev Analytics -->
    <script src="https://cdn.counter.dev/script.js" data-id="9d6fe738-e118-4e38-b9a6-bc8a0281391f" data-utcoffset="1"></script>
</head>
<body>

<div class="site-container">
    <div class="notebook-wrapper folio-container">

        <div class="notebook-content">

            <!-- Header med The Spiral / Collegiate æstetik -->
            <header class="site-header">
                <div class="header-rule-top"></div>
                <div class="site-header-inner">
                    <div class="header-brand">
                        <div class="site-title">
                            <a href="<?= BASE_URL ?>/">ADAM BINDSLEV <span class="title-slash">//</span> LOGBOG</a>
                        </div>
                    </div>
                    <nav class="site-nav">
                        <a href="<?= BASE_URL ?>/">[ ← LOGBOG ]</a>
                        <a href="<?= BASE_URL ?>/?view=archive">[ ARKIV ]</a>
                        <a href="<?= BASE_URL ?>/?view=index">[ INDEKS ]</a>
                    </nav>
                </div>
                <div class="header-rule-bottom"></div>
            </header>

            <main>
                <article class="log-entry log-entry-single">
                    <div class="entry-form-header">
                        <div class="form-field form-date">
                            <span class="field-label">DATO:</span>
                            <time class="field-val" datetime="<?= htmlspecialchars($post['published_at']) ?>">
                                <?= format_analog_date($post['published_at']) ?>
                            </time>
                        </div>
                        <div class="form-field form-time">
                            <span class="field-label">KL:</span>
                            <span class="field-val"><?= format_time($post['published_at']) ?></span>
                        </div>
                        <?php if (!empty($post['location'])): ?>
                        <div class="form-field form-loc">
                            <span class="field-label">LOKATION:</span>
                            <span class="field-val"><?= htmlspecialchars($post['location']) ?></span>
                        </div>
                        <?php endif; ?>
                    </div>

                    <h1 class="entry-title">
                        <?= htmlspecialchars($post['title']) ?>
                    </h1>

                    <?php if (!empty($post['image_path'])): 
                        $tapeVariant = get_tape_variant($uid);
                    ?>
                        <div class="entry-photo-wrap tape-style-<?= $tapeVariant ?>">
                            <?php if ($tapeVariant === 1): ?>
                                <span class="photo-tape tape-top-center-left" aria-hidden="true"></span>
                            <?php elseif ($tapeVariant === 2): ?>
                                <span class="photo-tape tape-top-center-right" aria-hidden="true"></span>
                            <?php elseif ($tapeVariant === 3): ?>
                                <span class="photo-tape tape-corner-tl" aria-hidden="true"></span>
                                <span class="photo-tape tape-corner-br" aria-hidden="true"></span>
                            <?php elseif ($tapeVariant === 4): ?>
                                <span class="photo-tape tape-corner-tr" aria-hidden="true"></span>
                            <?php elseif ($tapeVariant === 5): ?>
                                <span class="photo-tape tape-top-left-straight" aria-hidden="true"></span>
                                <span class="photo-tape tape-top-right-straight" aria-hidden="true"></span>
                            <?php endif; ?>
                            <img 
                                src="<?= $imageUrl ?>" 
                                alt="<?= htmlspecialchars($post['title']) ?>" 
                                class="entry-photo"
                            >
                            <?php if (!empty($exifStr)): ?>
                                <div class="entry-photo-exif" title="Kameraindstillinger: Blænde, lukkertid og ISO">
                                    <span class="exif-icon" aria-hidden="true">📷</span>
                                    <span class="exif-data"><?= htmlspecialchars($exifStr) ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($post['content'])): ?>
                        <div class="entry-body">
                            <?= render_content($post['content']) ?>
                        </div>
                    <?php endif; ?>

                    <footer class="entry-meta">
                        <div class="meta-bottom">
                            <div class="meta-tags">
                                <?php if (!empty($postTags)): ?>
                                    <?php foreach ($postTags as $t): ?>
                                        <a href="<?= BASE_URL ?>/?tag=<?= urlencode($t['slug']) ?>" class="entry-tag-stamp" title="Se alle indlæg under #<?= htmlspecialchars($t['name']) ?>">
                                            <span class="tag-hash">#</span><span class="tag-label"><?= htmlspecialchars($t['name']) ?></span>
                                        </a>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span class="meta-theme">COMBINATION THEME AND NOTE BOOK</span>
                                <?php endif; ?>
                            </div>
                            <div class="entry-stamp-badge">
                                <span class="stamp-icon">REF</span>
                                <span class="stamp-code"><?= htmlspecialchars($uid) ?></span>
                            </div>
                        </div>
                    </footer>
                </article>

                <div class="single-post-footer">
                    <a href="<?= BASE_URL ?>/" class="back-link">← Tilbage til alle indlæg</a>
                </div>
            </main>

        </div><!-- /.notebook-content -->
    </div><!-- /.notebook-wrapper -->
</div><!-- /.site-container -->

<script>
function alignNotebookBaselineGrid() {
    document.querySelectorAll('.log-entry').forEach(entry => {
        const photoWrap = entry.querySelector('.entry-photo-wrap');
        const body = entry.querySelector('.entry-body');
        if (!photoWrap || !body) return;

        photoWrap.style.marginBottom = '';
        const entryRect = entry.getBoundingClientRect();
        const photoRect = photoWrap.getBoundingClientRect();
        
        const currentDist = photoRect.bottom - entryRect.top;
        const gridStep = 32;
        const remainder = currentDist % gridStep;
        
        let gap = 24 + ((gridStep - (remainder + 24 % gridStep)) % gridStep);
        if (gap < 24) gap += gridStep;
        
        photoWrap.style.marginBottom = gap + 'px';
    });
}
window.addEventListener('load', alignNotebookBaselineGrid);
window.addEventListener('resize', alignNotebookBaselineGrid);
if (document.readyState === 'complete') {
    alignNotebookBaselineGrid();
}
</script>

</body>
</html>
