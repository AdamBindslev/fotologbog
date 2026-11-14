<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';

$view = $_GET['view'] ?? 'feed';
$filterYear = $_GET['year'] ?? null;
$filterMonth = $_GET['month'] ?? null;
$filterTag = trim((string)($_GET['tag'] ?? ''));
$filterLocation = trim((string)($_GET['location'] ?? ''));
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * POSTS_PER_PAGE;

$posts = [];
$totalPosts = 0;
$archiveMonths = [];
$activeFilterTitle = '';
$activeFilterType = '';
$tagInfo = null;

$monthNames = [
    '01' => 'Januar', '02' => 'Februar', '03' => 'Marts', '04' => 'April',
    '05' => 'Maj', '06' => 'Juni', '07' => 'Juli', '08' => 'August',
    '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'December'
];

if ($view === 'archive') {
    $archiveMonths = get_archive_months();
} elseif ($view === 'index') {
    // Indeks / sky visning håndteres i template
} elseif ($filterYear && $filterMonth) {
    $posts = get_posts_by_month((string)$filterYear, (string)$filterMonth);
    $totalPosts = count($posts);
} elseif ($filterTag !== '') {
    $tagInfo = get_tag_by_slug($filterTag);
    $posts = get_posts_by_tag($filterTag, POSTS_PER_PAGE, $offset);
    $totalPosts = count_posts_by_tag($filterTag);
    $activeFilterType = 'EMNE';
    $activeFilterTitle = '#' . ($tagInfo ? $tagInfo['name'] : $filterTag);
} elseif ($filterLocation !== '') {
    $posts = get_posts_by_location($filterLocation, POSTS_PER_PAGE, $offset);
    $totalPosts = count_posts_by_location($filterLocation);
    $activeFilterType = 'LOKATION';
    $activeFilterTitle = '⌖ ' . $filterLocation;
} else {
    $posts = get_recent_posts(POSTS_PER_PAGE, $offset);
    $totalPosts = count_total_posts();
}

$totalPages = max(1, (int)ceil($totalPosts / POSTS_PER_PAGE));

// URL-præfiks til sidenavigation ved aktive filtre
$filterQueryParam = '';
if ($filterTag !== '') {
    $filterQueryParam = 'tag=' . urlencode($filterTag) . '&';
} elseif ($filterLocation !== '') {
    $filterQueryParam = 'location=' . urlencode($filterLocation) . '&';
}

$dateRangeStr = '';
if (!empty($posts)) {
    $timestamps = array_filter(array_map(function($p) {
        return strtotime($p['published_at'] ?? '');
    }, $posts));
    if (!empty($timestamps)) {
        $minTs = min($timestamps);
        $maxTs = max($timestamps);
        $d1 = format_analog_date(date('Y-m-d H:i:s', $minTs));
        $d2 = format_analog_date(date('Y-m-d H:i:s', $maxTs));
        $dateRangeStr = ($d1 === $d2) ? $d1 : ($d1 . ' — ' . $d2);
    }
}

/**
 * Hjælper til at udskrive et månedligt kontaktark (Contact Sheet).
 * Bemærk: Tomme dage er kun passive arkivfelter og henviser ALDRIG til /app/.
 */
function render_contact_sheet_grid(array $sheet, array $monthNames, bool $linkToAnchor = false): void {
    $mName = $monthNames[$sheet['month']] ?? $sheet['month'];
    $weekdays = ['MAN', 'TIR', 'ONS', 'TOR', 'FRE', 'LØR', 'SØN'];
    ?>
    <section class="contact-sheet-card">
        <div class="contact-sheet-header">
            <div class="contact-sheet-title">
                <a href="<?= htmlspecialchars(BASE_URL) ?>/?year=<?= urlencode($sheet['year']) ?>&amp;month=<?= urlencode($sheet['month']) ?>">
                    [<?= htmlspecialchars($sheet['year']) ?>.<?= htmlspecialchars($sheet['month']) ?>] <?= htmlspecialchars($mName) ?> <?= htmlspecialchars($sheet['year']) ?>
                </a>
            </div>
            <div class="contact-sheet-stats">
                KONTAKTARK // <?= (int)$sheet['captured_days_count'] ?> AF <?= (int)$sheet['days_in_month'] ?> DAGE DOKUMENTERET
            </div>
        </div>

        <div class="contact-sheet-weekdays" aria-hidden="true">
            <?php foreach ($weekdays as $wd): ?>
                <div class="contact-wd"><?= $wd ?></div>
            <?php endforeach; ?>
        </div>

        <div class="contact-sheet-grid">
            <?php for ($pad = 1; $pad < $sheet['first_weekday_iso']; $pad++): ?>
                <div class="contact-cell contact-cell-pad" aria-hidden="true"></div>
            <?php endfor; ?>

            <?php foreach ($sheet['days'] as $dInfo): 
                $entry = $dInfo['entry'];
                $dayStr = sprintf('%02d', $dInfo['day']);
            ?>
                <?php if ($entry): 
                    $eUid = !empty($entry['uid']) ? $entry['uid'] : (string)$entry['id'];
                    $href = $linkToAnchor 
                        ? ('#entry-' . htmlspecialchars($eUid))
                        : htmlspecialchars(get_post_url($entry));
                    $thumbUrl = get_thumbnail_url($entry['image_path']);
                ?>
                    <a href="<?= $href ?>" class="contact-cell contact-cell-filled" title="<?= htmlspecialchars($dInfo['date_label'] . ' – ' . $entry['title']) ?>">
                        <img src="<?= $thumbUrl ?>" alt="<?= htmlspecialchars($entry['title']) ?>" loading="lazy" class="contact-thumb">
                        <span class="contact-day-num"><?= $dayStr ?></span>
                        <?php if ($dInfo['count'] > 1): ?>
                            <span class="contact-multi-badge">×<?= (int)$dInfo['count'] ?></span>
                        <?php endif; ?>
                    </a>
                <?php else: ?>
                    <div class="contact-cell contact-cell-empty <?= $dInfo['is_future'] ? 'is-future' : '' ?> <?= $dInfo['is_today'] ? 'is-today' : '' ?>" aria-label="<?= htmlspecialchars($dInfo['date_label']) ?> – ingen optegnelse">
                        <span class="contact-day-num"><?= $dayStr ?></span>
                        <span class="contact-empty-mark" aria-hidden="true"><?= $dInfo['is_future'] ? '·' : '—' ?></span>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </section>
    <?php
}
?>
<?php
$socialImg = !empty($posts[0]['image_path']) ? (get_post_og_meta($posts[0]) ?? get_social_image_meta($posts[0]['image_path'])) : null;
$ogTitle = !empty($activeFilterTitle) ? ($activeFilterTitle . ' – ' . SITE_TITLE) : SITE_TITLE;
?>
<!DOCTYPE html>
<html lang="da">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($ogTitle) ?></title>
    <meta name="description" content="<?= htmlspecialchars(SITE_DESCRIPTION) ?>">
    
    <!-- OpenGraph & Meta (med fuld absolut URL til nyeste billede) -->
    <meta property="og:site_name" content="<?= htmlspecialchars(SITE_TITLE) ?>">
    <meta property="og:locale" content="da_DK">
    <meta property="og:title" content="<?= htmlspecialchars($ogTitle) ?>">
    <meta property="og:description" content="<?= htmlspecialchars(SITE_DESCRIPTION) ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= htmlspecialchars(BASE_URL . '/') ?>">
    <?php if ($socialImg): ?>
    <meta property="og:image" content="<?= htmlspecialchars($socialImg['url']) ?>">
    <meta property="og:image:secure_url" content="<?= htmlspecialchars($socialImg['url']) ?>">
    <meta property="og:image:type" content="<?= htmlspecialchars($socialImg['mime']) ?>">
    <?php if ($socialImg['width'] && $socialImg['height']): ?>
    <meta property="og:image:width" content="<?= (int)$socialImg['width'] ?>">
    <meta property="og:image:height" content="<?= (int)$socialImg['height'] ?>">
    <?php endif; ?>
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= htmlspecialchars($ogTitle) ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars(SITE_DESCRIPTION) ?>">
    <meta name="twitter:image" content="<?= htmlspecialchars($socialImg['url']) ?>">
    <?php endif; ?>

    <link rel="icon" type="image/svg+xml" href="<?= htmlspecialchars(BASE_URL) ?>/assets/icons/favicon.svg">
    <link rel="apple-touch-icon" href="<?= htmlspecialchars(BASE_URL) ?>/assets/icons/icon-192.png">
    <link rel="stylesheet" href="<?= htmlspecialchars(BASE_URL) ?>/assets/css/style.css?v=<?= @filemtime(__DIR__ . '/assets/css/style.css') ?: '1' ?>">
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
                        <h1 class="site-title">
                            <?php 
                            $titleParts = explode('//', SITE_TITLE, 2);
                            if (count($titleParts) === 2): ?>
                                <a href="<?= htmlspecialchars(BASE_URL) ?>/"><?= htmlspecialchars(trim($titleParts[0])) ?> <span class="title-slash">//</span> <?= htmlspecialchars(trim($titleParts[1])) ?></a>
                            <?php else: ?>
                                <a href="<?= htmlspecialchars(BASE_URL) ?>/"><?= htmlspecialchars(SITE_TITLE) ?></a>
                            <?php endif; ?>
                        </h1>
                    </div>
                    <nav class="site-nav">
                        <a href="<?= htmlspecialchars(BASE_URL) ?>/" class="<?= ($view === 'feed' && !$filterYear && !$filterTag && !$filterLocation) ? 'active' : '' ?>">[ LOGBOG ]</a>
                        <a href="<?= htmlspecialchars(BASE_URL) ?>/?view=archive" class="<?= ($view === 'archive' || $filterYear) ? 'active' : '' ?>">[ ARKIV ]</a>
                        <a href="<?= htmlspecialchars(BASE_URL) ?>/?view=index" class="<?= ($view === 'index') ? 'active' : '' ?>">[ INDEKS ]</a>
                        <a href="<?= htmlspecialchars(BASE_URL) ?>/rss.php" target="_blank" rel="noopener">[ RSS ]</a>
                    </nav>
                </div>
                <div class="header-rule-bottom"></div>
            </header>

            <!-- Main Content -->
            <main>
        <?php if ($view === 'archive'): ?>
            <!-- Arkivvisning med Kontaktark-stak (Nyeste forrest, til at bladre i) -->
            <h2 class="archive-title">[ ARKIV &amp; KONTAKTARK ]</h2>
            <?php if (empty($archiveMonths)): ?>
                <div class="empty-log">
                    <p>[ INGEN INDLÆG ARKIVERET ENDNU ]</p>
                    <p style="margin-top: 0.5rem; font-size: 0.9rem;">Tidslinjen og kontaktarkene opbygges i takt med, at dagens fotografier føjes til logbogen.</p>
                </div>
            <?php else: 
                $totalSheets = count($archiveMonths);
            ?>
                <!-- Bladre-stak af Kontaktark (kun ét ark synligt ad gangen, nyeste forrest) -->
                <div class="contact-deck-wrapper" id="contactDeck">
                    <div class="contact-deck-controls">
                        <button type="button" class="deck-nav-btn" id="deckOlderBtn" title="Bladr bagud til ældre måned">
                            ← [ ÆLDRE ]
                        </button>

                        <div class="deck-selector-wrap">
                            <label for="deckMonthSelect" class="deck-pos-badge" id="deckPosBadge">
                                ARK 1 AF <?= $totalSheets ?> (NYESTE FORREST)
                            </label>
                            <select id="deckMonthSelect" class="deck-month-select" aria-label="Vælg måned i kontaktark">
                                <?php foreach ($archiveMonths as $idx => $item): 
                                    $mName = $monthNames[$item['month']] ?? $item['month'];
                                ?>
                                    <option value="<?= $idx ?>">
                                        [<?= htmlspecialchars($item['year']) ?>.<?= htmlspecialchars($item['month']) ?>] <?= htmlspecialchars($mName) ?> <?= htmlspecialchars($item['year']) ?> (<?= (int)$item['total'] ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <button type="button" class="deck-nav-btn" id="deckNewerBtn" disabled title="Bladr frem til nyere måned">
                            [ NYERE ] →
                        </button>
                    </div>

                    <div class="contact-deck-stage">
                        <?php foreach ($archiveMonths as $idx => $item): 
                            $sheet = get_month_contact_sheet((string)$item['year'], (string)$item['month']);
                            $mName = $monthNames[$item['month']] ?? $item['month'];
                        ?>
                            <div class="contact-deck-slide <?= $idx === 0 ? 'active' : '' ?>" data-sheet-index="<?= $idx ?>">
                                <?php render_contact_sheet_grid($sheet, $monthNames, false); ?>
                                <div class="contact-sheet-footer-bar">
                                    <span class="sheet-order-tag">
                                        <?= $idx === 0 ? '★ NYESTE MÅNEDSARK (FORREST)' : ('ARK #' . ($idx + 1) . ' I ARKIVSTAKKEN') ?>
                                    </span>
                                    <a href="<?= htmlspecialchars(BASE_URL) ?>/?year=<?= urlencode($item['year']) ?>&amp;month=<?= urlencode($item['month']) ?>" class="sheet-open-month-link">
                                        [ LÆS ALLE <?= (int)$item['total'] ?> NOTER FRA <?= htmlspecialchars(mb_strtoupper($mName, 'UTF-8')) ?> → ]
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Kompakt månedsregister nedenunder -->
                <h3 class="archive-subtitle">[ MÅNEDSOVERSIGT ]</h3>
                <ul class="archive-list">
                    <?php foreach ($archiveMonths as $idx => $item): 
                        $mName = $monthNames[$item['month']] ?? $item['month'];
                    ?>
                        <li class="archive-item">
                            <a href="<?= htmlspecialchars(BASE_URL) ?>/?year=<?= urlencode($item['year']) ?>&amp;month=<?= urlencode($item['month']) ?>" data-jump-sheet="<?= $idx ?>">
                                [<?= htmlspecialchars($item['year']) ?>.<?= htmlspecialchars($item['month']) ?>] <?= htmlspecialchars($mName) ?> <?= htmlspecialchars($item['year']) ?>
                            </a>
                            <span class="archive-count"><?= (int)$item['total'] ?> indlæg</span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

        <?php elseif ($view === 'index'): 
            $indexItems = get_unified_index_items();
            $tagCount = count(get_all_tags_with_counts());
            $locCount = count(get_all_locations_with_counts());
        ?>
            <!-- [ INDEKS ] Emne- og Stedregister -->
            <section class="index-notebook-sheet">
                <div class="index-header-area">
                    <h2 class="index-title">[ EMNE- &amp; STEDREGISTER ]</h2>
                    <div class="index-meta">
                        <?= $tagCount ?> emner · <?= $locCount ?> lokationer
                    </div>
                </div>

                <?php if (empty($indexItems)): ?>
                    <div class="index-empty-state">
                        <p>[ INGEN EMNER ELLER LOKATIONER ARKIVERET ENDNU ]</p>
                        <p style="margin-top: 0.5rem; font-size: 0.95rem;">Emner og lokationer fra logbogens optegnelser samles automatisk i dette register.</p>
                    </div>
                <?php else: ?>
                    <div class="index-cloud-area">
                        <?php foreach ($indexItems as $item): ?>
                            <a href="<?= htmlspecialchars($item['url']) ?>" 
                               class="index-cloud-item type-<?= htmlspecialchars($item['type']) ?> weight-<?= (int)$item['weight'] ?> <?= htmlspecialchars($item['font_style']) ?> <?= htmlspecialchars($item['color_tone']) ?>"
                               style="--cloud-rot: <?= $item['rot'] ?>deg; --cloud-drift: <?= $item['drift'] ?>px;"
                               title="<?= $item['type'] === 'tag' ? 'Emne' : 'Lokation' ?>: <?= htmlspecialchars($item['raw_name']) ?> (<?= (int)$item['count'] ?> indlæg)">
                                <span class="cloud-text"><?= htmlspecialchars($item['name']) ?></span>
                                <span class="cloud-count">(<?= (int)$item['count'] ?>)</span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

        <?php elseif (!empty($filterYear) && !empty($filterMonth)): 
            $monthSheet = get_month_contact_sheet((string)$filterYear, (string)$filterMonth, $posts);
        ?>
            <!-- Filtreret månedsvisning med Kontaktark i toppen -->
            <div class="active-filter-banner">
                <div class="filter-info">
                    <span class="filter-type">ARKIV-MÅNED:</span>
                    <span class="filter-query"><?= htmlspecialchars((string)$filterYear) ?>.<?= htmlspecialchars((string)$filterMonth) ?></span>
                    <span class="filter-count">(<?= count($posts) ?> indlæg)</span>
                </div>
                <a href="<?= htmlspecialchars(BASE_URL) ?>/?view=archive" class="filter-reset-btn">← Alle måneder</a>
            </div>

            <?php render_contact_sheet_grid($monthSheet, $monthNames, true); ?>
            
            <div class="log-feed" style="margin-top: 2.5rem;">
                <?php foreach ($posts as $post): ?>
                    <?php include __DIR__ . '/includes/post-card.php'; ?>
                <?php endforeach; ?>
            </div>

        <?php else: ?>
            <!-- Standard Feed eller Tag/Lokations-filtreret Feed -->
            <?php if (!empty($activeFilterTitle)): ?>
                <div class="active-filter-banner">
                    <div class="filter-info">
                        <span class="filter-type"><?= htmlspecialchars($activeFilterType) ?>:</span>
                        <span class="filter-query"><?= htmlspecialchars($activeFilterTitle) ?></span>
                        <span class="filter-count">(<?= $totalPosts ?> indlæg)</span>
                    </div>
                    <a href="<?= htmlspecialchars(BASE_URL) ?>/" class="filter-reset-btn">✕ Nulstil filter</a>
                </div>
            <?php endif; ?>

            <?php if (empty($posts)): ?>
                <div class="empty-log">
                    <p>[ INGEN INDLÆG FUNDET ]</p>
                    <?php if (!empty($activeFilterTitle)): ?>
                        <p style="margin-top: 0.5rem; font-size: 0.9rem;">Der blev ikke fundet nogen indlæg under <?= htmlspecialchars($activeFilterTitle) ?>.</p>
                        <a href="<?= htmlspecialchars(BASE_URL) ?>/">Gå til forsiden</a>
                    <?php else: ?>
                        <p style="margin-top: 0.5rem; font-size: 0.9rem;">Logbogen afventer sin første optegnelse.</p>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <?php if (empty($activeFilterTitle) && !empty($dateRangeStr)): ?>
                    <div class="archive-period-banner">
                        <span class="period-label">[ ARKIV-PERIODE:</span>
                        <strong class="period-dates"><?= $dateRangeStr ?></strong>
                        <span class="period-page">· SIDE <?= $page ?> AF <?= $totalPages ?> ]</span>
                    </div>
                <?php endif; ?>

                <div class="log-feed">
                    <?php foreach ($posts as $post): ?>
                        <?php include __DIR__ . '/includes/post-card.php'; ?>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <nav class="pagination" aria-label="Sidenavigation">
                        <div class="pagination-prev">
                            <?php if ($page > 1): ?>
                                <a href="<?= htmlspecialchars(BASE_URL) ?>/?<?= $filterQueryParam ?>page=<?= $page - 1 ?>">← [ NYERE ]</a>
                            <?php endif; ?>
                        </div>
                        <div class="pagination-info">
                            <?php if (!empty($dateRangeStr)): ?>
                                <span class="pagination-dates">[ <?= $dateRangeStr ?> ]</span>
                            <?php endif; ?>
                            <span class="pagination-count">SIDE <?= $page ?> AF <?= $totalPages ?></span>
                        </div>
                        <div class="pagination-next">
                            <?php if ($page < $totalPages): ?>
                                <a href="<?= htmlspecialchars(BASE_URL) ?>/?<?= $filterQueryParam ?>page=<?= $page + 1 ?>">[ TIDLIGERE ] →</a>
                            <?php endif; ?>
                        </div>
                    </nav>
                <?php endif; ?>

            <?php endif; ?>
        <?php endif; ?>
        <?php render_colophon(); ?>
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
// Juster foto-margin så teksten under billedet låses til det linierede 32px-baseline-grid
function alignNotebookBaselineGrid() {
    document.querySelectorAll('.log-entry').forEach(entry => {
        const photoWrap = entry.querySelector('.entry-photo-wrap');
        const body = entry.querySelector('.entry-body');
        if (!photoWrap || !body) return;

        // Nulstil inline margin for præcis genmåling
        photoWrap.style.marginBottom = '';
        const entryRect = entry.getBoundingClientRect();
        const photoRect = photoWrap.getBoundingClientRect();
        
        // Afstand fra toppen af arket (hvor 32px-linjerne starter) til bunden af foto-rammen
        const currentDist = photoRect.bottom - entryRect.top;
        const gridStep = 32;
        const remainder = currentDist % gridStep;
        
        // Sikr et luftigt naturligt mellemrum (mindst 24px) der rammer næste 32px raster
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

// Mørkekammer Lightbox
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
        }
    });
})();

// Kontaktark Bladre-stak (Flip-deck med nyeste måned forrest)
(function() {
    const deck = document.getElementById('contactDeck');
    if (!deck) return;

    const slides = Array.from(deck.querySelectorAll('.contact-deck-slide'));
    const olderBtn = document.getElementById('deckOlderBtn');
    const newerBtn = document.getElementById('deckNewerBtn');
    const selectEl = document.getElementById('deckMonthSelect');
    const posBadge = document.getElementById('deckPosBadge');
    const total = slides.length;
    let currentIndex = 0; // 0 = Nyeste måned (forrest)

    function showSheet(idx) {
        if (idx < 0 || idx >= total) return;
        currentIndex = idx;
        slides.forEach((s, i) => {
            s.classList.toggle('active', i === currentIndex);
        });
        if (selectEl) selectEl.value = String(currentIndex);
        if (posBadge) {
            posBadge.textContent = currentIndex === 0
                ? `ARK 1 AF ${total} (NYESTE FORREST)`
                : `ARK ${currentIndex + 1} AF ${total}`;
        }
        if (newerBtn) newerBtn.disabled = (currentIndex <= 0);
        if (olderBtn) olderBtn.disabled = (currentIndex >= total - 1);
    }

    showSheet(0);

    if (olderBtn) {
        olderBtn.addEventListener('click', () => showSheet(currentIndex + 1));
    }
    if (newerBtn) {
        newerBtn.addEventListener('click', () => showSheet(currentIndex - 1));
    }
    if (selectEl) {
        selectEl.addEventListener('change', (e) => showSheet(parseInt(e.target.value, 10) || 0));
    }

    // Tillad også venstre/højre piletast til at bladre i kontaktarkene når lightbox ikke er åben
    window.addEventListener('keydown', (e) => {
        const lb = document.getElementById('darkroomLightbox');
        if (lb && lb.classList.contains('active')) return;
        if (e.target && ['INPUT', 'TEXTAREA', 'SELECT'].includes(e.target.tagName)) return;
        if (e.key === 'ArrowLeft') {
            showSheet(currentIndex + 1); // Ældre
        } else if (e.key === 'ArrowRight') {
            showSheet(currentIndex - 1); // Nyere
        }
    });
})();
</script>

</body>
</html>
