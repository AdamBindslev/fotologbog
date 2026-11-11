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

    <!-- Fonts: Newsreader (Editorial Serif) + JetBrains Mono (Technical Analog Monospace) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:ital,wght@0,400;0,500;0,600;0,700;1,400&family=Newsreader:ital,opsz,wght@0,6..72,400;0,6..72,500;0,6..72,600;1,6..72,400;1,6..72,500&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <link rel="alternate" type="application/rss+xml" title="<?= htmlspecialchars(SITE_TITLE) ?> RSS Feed" href="<?= BASE_URL ?>/rss.php">

    <!-- Counter.dev Analytics -->
    <script src="https://cdn.counter.dev/script.js" data-id="9d6fe738-e118-4e38-b9a6-bc8a0281391f" data-utcoffset="1"></script>
</head>
<body>

<div class="site-container">

    <header class="site-header">
        <div class="site-header-inner">
            <div class="site-title"><a href="<?= BASE_URL ?>/"><?= htmlspecialchars(SITE_TITLE) ?></a></div>
            <nav class="site-nav">
                <a href="<?= BASE_URL ?>/">← Logbog</a>
                <span class="nav-sep">/</span>
                <a href="<?= BASE_URL ?>/?view=archive">Arkiv</a>
            </nav>
        </div>
    </header>

    <main>
        <article class="log-entry log-entry-single">
            <header class="entry-header">
                <time class="entry-timestamp" datetime="<?= htmlspecialchars($post['published_at']) ?>">
                    <?= $headerStamp ?>
                </time>
                <h1 class="entry-title">
                    <?= htmlspecialchars($post['title']) ?>
                </h1>
            </header>

            <?php if (!empty($post['image_path'])): ?>
                <div class="entry-photo-wrap">
                    <img 
                        src="<?= $imageUrl ?>" 
                        alt="<?= htmlspecialchars($post['title']) ?>" 
                        class="entry-photo"
                    >
                </div>
            <?php endif; ?>

            <?php if (!empty($post['content'])): ?>
                <div class="entry-body">
                    <?= render_content($post['content']) ?>
                </div>
            <?php endif; ?>

            <footer class="entry-meta">
                <div class="meta-left">
                    <span class="meta-time">KL. <?= format_time($post['published_at']) ?></span>
                    <?php if (!empty($post['location'])): ?>
                        <span class="meta-sep">•</span>
                        <span class="meta-loc"><?= htmlspecialchars($post['location']) ?></span>
                    <?php endif; ?>
                </div>
                <div class="meta-right">
                    <span class="entry-ref">REF // <?= htmlspecialchars($uid) ?></span>
                </div>
            </footer>
        </article>

        <div class="single-post-footer">
            <a href="<?= BASE_URL ?>/" class="back-link">← Tilbage til alle indlæg</a>
        </div>
    </main>

</div>

</body>
</html>
