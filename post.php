<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$param = $_GET['p'] ?? $_GET['id'] ?? null;
if (!$param) {
    http_response_code(404);
    die('Indlægget blev ikke fundet. <a href="' . htmlspecialchars(BASE_URL) . '/">Gå til forsiden</a>');
}

$post = get_post_by_identifier((string)$param);

if (!$post || (($post['status'] ?? 'published') !== 'published' && !is_authenticated())) {
    http_response_code(404);
    die('Indlægget blev ikke fundet. <a href="' . htmlspecialchars(BASE_URL) . '/">Gå til forsiden</a>');
}

$isDraftPreview = (($post['status'] ?? 'published') !== 'published');
$uid = !empty($post['uid']) ? $post['uid'] : (string)$post['id'];
$postUrl = get_post_url($post);
$postTags = get_post_tags((int)$post['id']);
$exifStr = format_exif($post);
$headerStamp = format_analog_header($post['published_at'], $post['location'] ?? null);
$pageTitle = htmlspecialchars($post['title']) . ' – ' . htmlspecialchars(SITE_TITLE);
$postImages = get_post_images((int)$post['id'], $post['image_path'] ?? null);
$imageUrl = !empty($postImages[0]) ? UPLOADS_URL . '/' . htmlspecialchars($postImages[0]) : '';
$socialImg = get_post_og_meta($post) ?? get_social_image_meta($post['image_path'] ?? null);
$fallbackDesc = trim($headerStamp . ($exifStr ? ' · ' . $exifStr : ''));
$excerpt = get_clean_excerpt($post['content'] ?? '', $fallbackDesc, 160);
$adjacent = get_adjacent_posts($post);
$prevPost = $adjacent['prev'];
$nextPost = $adjacent['next'];
?>
<!DOCTYPE html>
<html lang="da">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <meta name="description" content="<?= htmlspecialchars($excerpt) ?>">

    <!-- Social & OpenGraph (med fuld absolut URL og dimensioner så billedet altid vises) -->
    <meta property="og:site_name" content="<?= htmlspecialchars(SITE_TITLE) ?>">
    <meta property="og:locale" content="da_DK">
    <meta property="og:title" content="<?= htmlspecialchars($post['title']) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($excerpt) ?>">
    <meta property="og:type" content="article">
    <meta property="og:url" content="<?= htmlspecialchars($postUrl) ?>">
    <?php if ($socialImg): ?>
    <meta property="og:image" content="<?= htmlspecialchars($socialImg['url']) ?>">
    <meta property="og:image:secure_url" content="<?= htmlspecialchars($socialImg['url']) ?>">
    <meta property="og:image:type" content="<?= htmlspecialchars($socialImg['mime']) ?>">
    <?php if ($socialImg['width'] && $socialImg['height']): ?>
    <meta property="og:image:width" content="<?= (int)$socialImg['width'] ?>">
    <meta property="og:image:height" content="<?= (int)$socialImg['height'] ?>">
    <?php endif; ?>
    <meta property="og:image:alt" content="<?= htmlspecialchars($post['title']) ?>">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= htmlspecialchars($post['title']) ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars($excerpt) ?>">
    <meta name="twitter:image" content="<?= htmlspecialchars($socialImg['url']) ?>">
    <?php endif; ?>

    <link rel="icon" type="image/svg+xml" href="<?= htmlspecialchars(BASE_URL) ?>/assets/icons/favicon.svg">
    <link rel="apple-touch-icon" href="<?= htmlspecialchars(BASE_URL) ?>/assets/icons/icon-192.png">
    <link rel="stylesheet" href="<?= htmlspecialchars(BASE_URL) ?>/assets/css/style.css">
    <link rel="alternate" type="application/rss+xml" title="<?= htmlspecialchars(SITE_TITLE) ?> RSS Feed" href="<?= htmlspecialchars(BASE_URL) ?>/rss.php">

    <?php if (defined('COUNTER_DEV_ID') && COUNTER_DEV_ID !== ''): ?>
    <!-- Counter.dev Analytics -->
    <script src="https://cdn.counter.dev/script.js" data-id="<?= htmlspecialchars(COUNTER_DEV_ID) ?>" data-utcoffset="1"></script>
    <?php endif; ?>
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
                            <?php 
                            $titleParts = explode('//', SITE_TITLE, 2);
                            if (count($titleParts) === 2): ?>
                                <a href="<?= htmlspecialchars(BASE_URL) ?>/"><?= htmlspecialchars(trim($titleParts[0])) ?> <span class="title-slash">//</span> <?= htmlspecialchars(trim($titleParts[1])) ?></a>
                            <?php else: ?>
                                <a href="<?= htmlspecialchars(BASE_URL) ?>/"><?= htmlspecialchars(SITE_TITLE) ?></a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <nav class="site-nav">
                        <a href="<?= htmlspecialchars(BASE_URL) ?>/">[ ← LOGBOG ]</a>
                        <a href="<?= htmlspecialchars(BASE_URL) ?>/?view=archive">[ ARKIV ]</a>
                        <a href="<?= htmlspecialchars(BASE_URL) ?>/?view=index">[ INDEKS ]</a>
                    </nav>
                </div>
                <div class="header-rule-bottom"></div>
            </header>

            <main>
                <?php if ($isDraftPreview): ?>
                    <div class="draft-preview-banner">[ KLADDE – KUN SYNLIG FOR DIG ]</div>
                <?php endif; ?>
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
                            <a href="<?= htmlspecialchars(BASE_URL) ?>/?location=<?= urlencode($post['location']) ?>" class="field-val" style="color: inherit; text-decoration: none;">
                                <?= htmlspecialchars($post['location']) ?>
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>

                    <h1 class="entry-title">
                        <?= htmlspecialchars($post['title']) ?>
                    </h1>

                    <?php if (!empty($postImages)): 
                        $tapeVariant = get_tape_variant($uid);
                        $imgCount = count($postImages);
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
                            <?php if ($imgCount === 1): ?>
                                <img 
                                    src="<?= $imageUrl ?>" 
                                    alt="<?= htmlspecialchars($post['title']) ?>" 
                                    class="entry-photo loupe-clickable"
                                    data-src="<?= $imageUrl ?>"
                                    data-title="<?= htmlspecialchars($post['title']) ?>"
                                    data-stamp="<?= htmlspecialchars($headerStamp) ?>"
                                    data-exif="<?= htmlspecialchars($exifStr ?? '') ?>"
                                    title="Klik for at åbne i lup (Fuld skærm)"
                                >
                            <?php else: ?>
                                <div class="entry-photo-grid count-<?= $imgCount ?>">
                                    <?php foreach ($postImages as $idx => $pImg):
                                        $pImgUrl = UPLOADS_URL . '/' . htmlspecialchars($pImg);
                                    ?>
                                        <div class="entry-photo-grid-item">
                                            <img 
                                                src="<?= $pImgUrl ?>" 
                                                alt="<?= htmlspecialchars($post['title']) ?> (<?= $idx + 1 ?>/<?= $imgCount ?>)" 
                                                class="entry-photo loupe-clickable"
                                                data-src="<?= $pImgUrl ?>"
                                                data-title="<?= htmlspecialchars($post['title']) ?> (<?= $idx + 1 ?>/<?= $imgCount ?>)"
                                                data-stamp="<?= htmlspecialchars($headerStamp) ?>"
                                                data-exif="<?= htmlspecialchars($exifStr ?? '') ?>"
                                                title="Klik for at åbne i lup (Fuld skærm)"
                                            >
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            <div class="entry-photo-caption-bar">
                                <?php if (!empty($exifStr)): ?>
                                    <div class="entry-photo-exif" title="Kameradata (EXIF)">
                                        <span class="exif-icon" aria-hidden="true">⌀</span>
                                        <span class="exif-data"><?= htmlspecialchars($exifStr) ?></span>
                                    </div>
                                <?php else: ?>
                                    <div class="entry-photo-exif">
                                        <span class="exif-icon" aria-hidden="true">⌀</span>
                                        <span class="exif-data">Ingen Exif</span>
                                    </div>
                                <?php endif; ?>
                                <button 
                                    type="button" 
                                    class="loupe-trigger-btn"
                                    data-src="<?= $imageUrl ?>"
                                    data-title="<?= htmlspecialchars($post['title']) ?>"
                                    data-stamp="<?= htmlspecialchars($headerStamp) ?>"
                                    data-exif="<?= htmlspecialchars($exifStr ?? '') ?>"
                                    title="Åbn i lup (Fuld skærm)"
                                >[ ⤢ LUP ]</button>
                            </div>
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
                                        <a href="<?= htmlspecialchars(BASE_URL) ?>/?tag=<?= urlencode($t['slug']) ?>" class="entry-tag-stamp" title="Se alle indlæg under #<?= htmlspecialchars($t['name']) ?>">
                                            <span class="tag-hash">#</span><span class="tag-label"><?= htmlspecialchars($t['name']) ?></span>
                                        </a>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span class="meta-theme">COMBINATION THEME AND NOTE BOOK</span>
                                <?php endif; ?>
                            </div>
                            <div class="entry-share-actions">
                                <button type="button" class="share-btn" id="copyPostLinkBtn" data-url="<?= htmlspecialchars($postUrl) ?>">[ KOPIÉR LINK ]</button>
                                <button type="button" class="share-btn" id="nativeShareBtn" data-url="<?= htmlspecialchars($postUrl) ?>" data-title="<?= htmlspecialchars($post['title']) ?>" style="display:none;">[ DEL ]</button>
                            </div>
                            <div class="entry-stamp-badge">
                                <span class="stamp-icon">REF</span>
                                <span class="stamp-code"><?= htmlspecialchars($uid) ?></span>
                            </div>
                        </div>
                    </footer>
                </article>

                <!-- Bladring mellem dage i logbogen -->
                <nav class="single-post-nav" aria-label="Bladr i logbogen">
                    <div class="post-nav-slot post-nav-prev">
                        <?php if ($prevPost): ?>
                            <a href="<?= htmlspecialchars(get_post_url($prevPost)) ?>" id="prevPostLink" class="post-nav-card" title="<?= htmlspecialchars($prevPost['title']) ?>">
                                <span class="post-nav-dir">← [ FORRIGE OPTEGNELSE ]</span>
                                <span class="post-nav-meta"><?= format_analog_date($prevPost['published_at']) ?> · <?= htmlspecialchars($prevPost['title']) ?></span>
                            </a>
                        <?php endif; ?>
                    </div>
                    <div class="post-nav-center">
                        <a href="<?= htmlspecialchars(BASE_URL) ?>/" class="back-link">[ OVERSIGT ]</a>
                    </div>
                    <div class="post-nav-slot post-nav-next">
                        <?php if ($nextPost): ?>
                            <a href="<?= htmlspecialchars(get_post_url($nextPost)) ?>" id="nextPostLink" class="post-nav-card" title="<?= htmlspecialchars($nextPost['title']) ?>">
                                <span class="post-nav-dir">[ NÆSTE OPTEGNELSE ] →</span>
                                <span class="post-nav-meta"><?= format_analog_date($nextPost['published_at']) ?> · <?= htmlspecialchars($nextPost['title']) ?></span>
                            </a>
                        <?php endif; ?>
                    </div>
                </nav>
            </main>

        </div><!-- /.notebook-content -->
    </div><!-- /.notebook-wrapper -->
</div><!-- /.site-container -->

<!-- Mørkekammer Lightbox (Fuldskærms-lup) -->
<div id="darkroomLightbox" class="darkroom-lightbox" role="dialog" aria-modal="true" aria-hidden="true">
    <button type="button" class="darkroom-close" id="darkroomCloseBtn" aria-label="Luk visning">[ ✕ LUK // ESC ]</button>
    <div class="darkroom-stage">
        <img id="darkroomImg" class="darkroom-img" src="" alt="">
        <div class="darkroom-caption">
            <div class="darkroom-caption-main">
                <span id="darkroomStamp" class="darkroom-stamp"></span>
                <span id="darkroomTitle" class="darkroom-title"></span>
            </div>
            <div id="darkroomExif" class="darkroom-exif"></div>
        </div>
    </div>
</div>

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

// Mørkekammer Lightbox & Piletast-bladring
(function() {
    const lb = document.getElementById('darkroomLightbox');
    const lbImg = document.getElementById('darkroomImg');
    const lbStamp = document.getElementById('darkroomStamp');
    const lbTitle = document.getElementById('darkroomTitle');
    const lbExif = document.getElementById('darkroomExif');
    const closeBtn = document.getElementById('darkroomCloseBtn');

    function openLoupe(el) {
        const src = el.getAttribute('data-src');
        if (!src) return;
        lbImg.src = src;
        lbImg.alt = el.getAttribute('data-title') || '';
        lbStamp.textContent = el.getAttribute('data-stamp') || '';
        lbTitle.textContent = el.getAttribute('data-title') || '';
        const exif = el.getAttribute('data-exif') || '';
        lbExif.textContent = '⌀ ' + (exif || 'Ingen Exif');
        lbExif.style.display = 'block';
        lb.classList.add('active');
        lb.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }

    function closeLoupe() {
        if (!lb.classList.contains('active')) return;
        lb.classList.remove('active');
        lb.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }

    document.querySelectorAll('.loupe-trigger-btn, .loupe-clickable').forEach(el => {
        el.addEventListener('click', (e) => {
            e.preventDefault();
            openLoupe(el);
        });
    });

    if (closeBtn) closeBtn.addEventListener('click', closeLoupe);
    if (lb) {
        lb.addEventListener('click', (e) => {
            if (e.target === lb || e.target.classList.contains('darkroom-stage')) {
                closeLoupe();
            }
        });
    }

    window.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && lb && lb.classList.contains('active')) {
            closeLoupe();
            return;
        }
        if (lb && lb.classList.contains('active')) return;
        if (e.key === 'ArrowLeft') {
            const prev = document.getElementById('prevPostLink');
            if (prev) window.location.href = prev.href;
        } else if (e.key === 'ArrowRight') {
            const next = document.getElementById('nextPostLink');
            if (next) window.location.href = next.href;
        }
    });

    // Diskret link-kopiering & native deling
    const copyBtn = document.getElementById('copyPostLinkBtn');
    const shareBtn = document.getElementById('nativeShareBtn');
    if (copyBtn) {
        copyBtn.addEventListener('click', async () => {
            const url = copyBtn.getAttribute('data-url');
            try {
                await navigator.clipboard.writeText(url);
                copyBtn.textContent = '[ KOPIERET ✓ ]';
                copyBtn.classList.add('copied');
                setTimeout(() => {
                    copyBtn.textContent = '[ KOPIÉR LINK ]';
                    copyBtn.classList.remove('copied');
                }, 2200);
            } catch (_) {
                window.prompt('Kopiér link:', url);
            }
        });
    }
    if (shareBtn && navigator.share) {
        shareBtn.style.display = 'inline-block';
        shareBtn.addEventListener('click', async () => {
            try {
                await navigator.share({
                    title: shareBtn.getAttribute('data-title') || document.title,
                    url: shareBtn.getAttribute('data-url')
                });
            } catch (_) {}
        });
    }
})();
</script>

</body>
</html>
