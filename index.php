<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';

$view = $_GET['view'] ?? 'feed';
$filterYear = $_GET['year'] ?? null;
$filterMonth = $_GET['month'] ?? null;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * POSTS_PER_PAGE;

$posts = [];
$totalPosts = 0;
$archiveMonths = [];

if ($view === 'archive') {
    $archiveMonths = get_archive_months();
} elseif ($filterYear && $filterMonth) {
    $posts = get_posts_by_month($filterYear, $filterMonth);
    $totalPosts = count($posts);
} else {
    $posts = get_recent_posts(POSTS_PER_PAGE, $offset);
    $totalPosts = count_total_posts();
}

$totalPages = ceil($totalPosts / POSTS_PER_PAGE);
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

    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <link rel="alternate" type="application/rss+xml" title="<?= htmlspecialchars(SITE_TITLE) ?> RSS Feed" href="<?= BASE_URL ?>/rss.php">

    <!-- Counter.dev Analytics -->
    <script src="https://cdn.counter.dev/script.js" data-id="9d6fe738-e118-4e38-b9a6-bc8a0281391f" data-utcoffset="1"></script>
</head>
<body>

<div class="site-container">

    <!-- Header -->
    <header class="site-header">
        <h1 class="site-title"><a href="<?= BASE_URL ?>/"><?= htmlspecialchars(SITE_TITLE) ?></a></h1>
        <div class="site-subtitle"><?= htmlspecialchars(SITE_SUBTITLE) ?></div>
        <nav class="site-nav">
            <a href="<?= BASE_URL ?>/" class="<?= ($view === 'feed' && !$filterYear) ? 'active' : '' ?>">Logbog</a>
            <a href="<?= BASE_URL ?>/?view=archive" class="<?= ($view === 'archive') ? 'active' : '' ?>">Arkiv</a>
            <a href="<?= BASE_URL ?>/rss.php" target="_blank">RSS</a>
        </nav>
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

        <?php elseif (!empty($filterYear) && !empty($filterMonth)): ?>
            <!-- Filtreret månedsvisning -->
            <div style="margin-bottom: 2.5rem; font-family: var(--font-mono); font-size: 0.9rem; color: var(--text-muted);">
                Viser arkiv for: <strong><?= htmlspecialchars($filterYear) ?>.<?= htmlspecialchars($filterMonth) ?></strong> 
                (<?= count($posts) ?> indlæg) — <a href="<?= BASE_URL ?>/" style="color: inherit;">Nulstil filter</a>
            </div>
            
            <div class="log-feed">
                <?php foreach ($posts as $post): ?>
                    <?php include __DIR__ . '/includes/post-card.php'; ?>
                <?php endforeach; ?>
            </div>

        <?php else: ?>
            <!-- Standard Feed -->
            <?php if (empty($posts)): ?>
                <div class="empty-log">
                    <p>[ SYSTEM.STATUS: LOGBOG ER TOM ]</p>
                    <p style="margin-top: 0.5rem; font-size: 0.9rem;">Start dit daglige projekt nu ved at lægge dit første billede op.</p>
                    <a href="<?= BASE_URL ?>/app/">Åbn skriveflade</a>
                </div>
            <?php else: ?>
                <div class="log-feed">
                    <?php foreach ($posts as $post): ?>
                        <?php include __DIR__ . '/includes/post-card.php'; ?>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <nav class="pagination" aria-label="Sidenavigation">
                        <div>
                            <?php if ($page > 1): ?>
                                <a href="<?= BASE_URL ?>/?page=<?= $page - 1 ?>">← [ NYERE ]</a>
                            <?php endif; ?>
                        </div>
                        <div style="color: var(--text-muted);">
                            SIDE <?= $page ?> AF <?= $totalPages ?>
                        </div>
                        <div>
                            <?php if ($page < $totalPages): ?>
                                <a href="<?= BASE_URL ?>/?page=<?= $page + 1 ?>">[ TIDLIGERE ] →</a>
                            <?php endif; ?>
                        </div>
                    </nav>
                <?php endif; ?>

            <?php endif; ?>
        <?php endif; ?>
    </main>

</div>

</body>
</html>
