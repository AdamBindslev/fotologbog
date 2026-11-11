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

if ($view === 'archive') {
    $archiveMonths = get_archive_months();
} elseif ($view === 'index') {
    // Indeks / sky visning håndteres i template
} elseif ($filterYear && $filterMonth) {
    $posts = get_posts_by_month($filterYear, $filterMonth);
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
    $activeFilterTitle = '📍 ' . $filterLocation;
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
    $newestDate = $posts[0]['published_at'] ?? '';
    $oldestDate = $posts[count($posts) - 1]['published_at'] ?? '';
    if ($newestDate && $oldestDate) {
        $d1 = format_analog_date($oldestDate);
        $d2 = format_analog_date($newestDate);
        $dateRangeStr = ($d1 === $d2) ? $d1 : ($d1 . ' — ' . $d2);
    }
}
?>
<!DOCTYPE html>
<html lang="da">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(SITE_TITLE) ?></title>
    <meta name="description" content="<?= htmlspecialchars(SITE_DESCRIPTION) ?>">
    
    <!-- OpenGraph & Meta -->
    <meta property="og:title" content="<?= htmlspecialchars(SITE_TITLE) ?>">
    <meta property="og:description" content="<?= htmlspecialchars(SITE_DESCRIPTION) ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= BASE_URL ?>">
    <?php if (!empty($posts[0]['image_path'])): ?>
    <meta property="og:image" content="<?= UPLOADS_URL . '/' . htmlspecialchars($posts[0]['image_path']) ?>">
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
                        <h1 class="site-title">
                            <a href="<?= BASE_URL ?>/">ADAM BINDSLEV <span class="title-slash">//</span> LOGBOG</a>
                        </h1>
                    </div>
                    <nav class="site-nav">
                        <a href="<?= BASE_URL ?>/" class="<?= ($view === 'feed' && !$filterYear && !$filterTag && !$filterLocation) ? 'active' : '' ?>">[ LOGBOG ]</a>
                        <a href="<?= BASE_URL ?>/?view=archive" class="<?= ($view === 'archive' || $filterYear) ? 'active' : '' ?>">[ ARKIV ]</a>
                        <a href="<?= BASE_URL ?>/?view=index" class="<?= ($view === 'index') ? 'active' : '' ?>">[ INDEKS ]</a>
                        <a href="<?= BASE_URL ?>/rss.php" target="_blank" rel="noopener">[ RSS ]</a>
                    </nav>
                </div>
                <div class="header-rule-bottom"></div>
            </header>

            <!-- Main Content -->
            <main>
        <?php if ($view === 'archive'): ?>
            <!-- Arkivvisning -->
            <h2 class="archive-title">[ ARKIVOVERSIGT ]</h2>
            <?php if (empty($archiveMonths)): ?>
                <div class="empty-log">
                    <p>Ingen indlæg i arkivet endnu.</p>
                    <a href="<?= BASE_URL ?>/app/">Opret det første indlæg</a>
                </div>
            <?php else: ?>
                <ul class="archive-list">
                    <?php 
                    $monthNames = [
                        '01' => 'Januar', '02' => 'Februar', '03' => 'Marts', '04' => 'April',
                        '05' => 'Maj', '06' => 'Juni', '07' => 'Juli', '08' => 'August',
                        '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'December'
                    ];
                    foreach ($archiveMonths as $item): 
                        $mName = $monthNames[$item['month']] ?? $item['month'];
                    ?>
                        <li class="archive-item">
                            <a href="<?= BASE_URL ?>/?year=<?= $item['year'] ?>&month=<?= $item['month'] ?>">
                                [<?= $item['year'] ?>.<?= $item['month'] ?>] <?= $mName ?> <?= $item['year'] ?>
                            </a>
                            <span class="archive-count"><?= $item['total'] ?> <?= $item['total'] == 1 ? 'indlæg' : 'indlæg' ?></span>
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
                        <p style="margin-top: 0.5rem; font-size: 0.95rem;">Tags og steder du angiver i dine opslag samles automatisk i denne sky.</p>
                    </div>
                <?php else: ?>
                    <div class="index-cloud-area">
                        <?php foreach ($indexItems as $item): ?>
                            <a href="<?= $item['url'] ?>" 
                               class="index-cloud-item type-<?= $item['type'] ?> weight-<?= $item['weight'] ?>"
                               title="<?= $item['type'] === 'tag' ? 'Emne' : 'Lokation' ?>: <?= htmlspecialchars($item['raw_name']) ?> (<?= $item['count'] ?> indlæg)">
                                <?= htmlspecialchars($item['name']) ?>
                                <span class="cloud-count">(<?= $item['count'] ?>)</span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

        <?php elseif (!empty($filterYear) && !empty($filterMonth)): ?>
            <!-- Filtreret månedsvisning -->
            <div class="active-filter-banner">
                <div class="filter-info">
                    <span class="filter-type">ARKIV-MÅNED:</span>
                    <span class="filter-query"><?= htmlspecialchars($filterYear) ?>.<?= htmlspecialchars($filterMonth) ?></span>
                    <span class="filter-count">(<?= count($posts) ?> indlæg)</span>
                </div>
                <a href="<?= BASE_URL ?>/" class="filter-reset-btn">✕ Nulstil filter</a>
            </div>
            
            <div class="log-feed">
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
                        <span class="filter-count">(<?= $totalPosts ?> <?= $totalPosts === 1 ? 'indlæg' : 'indlæg' ?>)</span>
                    </div>
                    <a href="<?= BASE_URL ?>/" class="filter-reset-btn">✕ Nulstil filter</a>
                </div>
            <?php endif; ?>

            <?php if (empty($posts)): ?>
                <div class="empty-log">
                    <p>[ INGEN INDLÆG FUNDET ]</p>
                    <?php if (!empty($activeFilterTitle)): ?>
                        <p style="margin-top: 0.5rem; font-size: 0.9rem;">Der blev ikke fundet nogen indlæg under <?= htmlspecialchars($activeFilterTitle) ?>.</p>
                        <a href="<?= BASE_URL ?>/">Gå til forsiden</a>
                    <?php else: ?>
                        <p style="margin-top: 0.5rem; font-size: 0.9rem;">Start dit daglige projekt nu ved at lægge dit første billede op.</p>
                        <a href="<?= BASE_URL ?>/app/">Åbn skriveflade</a>
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
                                <a href="<?= BASE_URL ?>/?<?= $filterQueryParam ?>page=<?= $page - 1 ?>">← [ NYERE ]</a>
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
                                <a href="<?= BASE_URL ?>/?<?= $filterQueryParam ?>page=<?= $page + 1 ?>">[ TIDLIGERE ] →</a>
                            <?php endif; ?>
                        </div>
                    </nav>
                <?php endif; ?>

            <?php endif; ?>
        <?php endif; ?>
            </main>

        </div><!-- /.notebook-content -->
    </div><!-- /.notebook-wrapper -->
</div><!-- /.site-container -->

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
// Kør også straks i tilfælde af cachede billeder
if (document.readyState === 'complete') {
    alignNotebookBaselineGrid();
}
</script>

</body>
</html>
