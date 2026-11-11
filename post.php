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
    <div class="notebook-wrapper">

        <!-- Spiral-ryg med wire og stansehuller -->
        <aside class="notebook-spine" aria-hidden="true">
            <div class="spine-wire"></div>
            <div class="punch-hole hole-top"></div>
            <div class="punch-hole hole-mid"></div>
            <div class="punch-hole hole-bot"></div>
        </aside>

        <div class="notebook-content">

            <!-- Header med The Spiral / Collegiate æstetik -->
            <header class="site-header">
                <div class="header-rule-top"></div>
                <div class="site-header-inner">
                    <div class="header-brand">
                        <div class="site-title">
                            <a href="<?= BASE_URL ?>/">ADAM BINDSLEV <span class="title-slash">//</span> LOGBOG</a>
                        </div>
                        <div class="header-badge-strip">
                            <span class="header-badge">NARROW RULED</span>
                            <span class="badge-dot">•</span>
                            <span class="header-badge">MARGINAL</span>
                        </div>
                    </div>
                    <nav class="site-nav">
                        <a href="<?= BASE_URL ?>/">[ ← LOGBOG ]</a>
                        <a href="<?= BASE_URL ?>/?view=archive">[ ARKIV ]</a>
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

                    <?php if (!empty($post['image_path'])): ?>
                        <div class="entry-photo-wrap">
                            <span class="photo-tape photo-tape-top" aria-hidden="true"></span>
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
                        <div class="meta-bottom">
                            <span class="meta-theme">COMBINATION THEME AND NOTE BOOK</span>
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

</body>
</html>
