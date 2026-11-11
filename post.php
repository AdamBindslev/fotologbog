<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$post = get_post_by_id($id);

if (!$post) {
    http_response_code(404);
    die('Indlægget blev ikke fundet. <a href="' . BASE_URL . '/">Gå til forsiden</a>');
}

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
    <meta property="og:url" content="<?= BASE_URL . '/post.php?id=' . $post['id'] ?>">
    <?php if ($imageUrl): ?>
    <meta property="og:image" content="<?= $imageUrl ?>">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:image" content="<?= $imageUrl ?>">
    <?php endif; ?>

    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <link rel="alternate" type="application/rss+xml" title="<?= htmlspecialchars(SITE_TITLE) ?> RSS Feed" href="<?= BASE_URL ?>/rss.php">

    <!-- Counter.dev Analytics -->
    <script src="https://cdn.counter.dev/script.js" data-id="9d6fe738-e118-4e38-b9a6-bc8a0281391f" data-utcoffset="1"></script>
</head>
<body>

<div class="site-container">

    <header class="site-header">
        <div class="site-title"><a href="<?= BASE_URL ?>/"><?= htmlspecialchars(SITE_TITLE) ?></a></div>
        <div class="site-subtitle"><?= htmlspecialchars(SITE_SUBTITLE) ?></div>
        <nav class="site-nav">
            <a href="<?= BASE_URL ?>/">← Tilbage til logbog</a>
            <a href="<?= BASE_URL ?>/?view=archive">Arkiv</a>
        </nav>
    </header>

    <main>
        <article class="log-entry" style="margin-bottom: 3rem;">
            <header class="entry-header">
                <time class="entry-timestamp" datetime="<?= htmlspecialchars($post['published_at']) ?>">
                    <?= $headerStamp ?>
                </time>
                <h1 class="entry-title" style="font-size: 2.1rem;">
                    <?= htmlspecialchars($post['title']) ?>
                </h1>
            </header>

            <?php if (!empty($post['image_path'])): ?>
                <div class="entry-photo-wrap" style="box-shadow: 0 8px 24px rgba(0,0,0,0.06);">
                    <img 
                        src="<?= $imageUrl ?>" 
                        alt="<?= htmlspecialchars($post['title']) ?>" 
                        class="entry-photo"
                    >
                </div>
            <?php endif; ?>

            <?php if (!empty($post['content'])): ?>
                <div class="entry-body" style="font-size: 1.2rem; line-height: 1.75;">
                    <?= render_content($post['content']) ?>
                </div>
            <?php endif; ?>

            <footer class="entry-meta">
                <div>
                    <span>Tidspunkt: KL. <?= format_time($post['published_at']) ?></span>
                    <?php if (!empty($post['location'])): ?>
                        <span>• Lokation: <?= htmlspecialchars($post['location']) ?></span>
                    <?php endif; ?>
                </div>
                <div>
                    <span>ID: #<?= $post['id'] ?></span>
                </div>
            </footer>
        </article>

        <div style="border-top: 1px solid var(--border-subtle); padding-top: 2rem; font-family: var(--font-mono); font-size: 0.9rem;">
            <a href="<?= BASE_URL ?>/" style="color: var(--text-main); text-decoration: none;">← Tilbage til alle indlæg</a>
        </div>
    </main>

</div>

</body>
</html>
