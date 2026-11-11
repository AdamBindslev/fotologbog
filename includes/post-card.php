<?php
/**
 * Post Card skabelon – Enkelt opslag i notesbogs-feedet
 */
$uid = !empty($post['uid']) ? $post['uid'] : (string)$post['id'];
$postUrl = BASE_URL . '/post.php?p=' . htmlspecialchars($uid);
$headerStamp = format_analog_header($post['published_at'], $post['location'] ?? null);
?>
<article class="log-entry" id="entry-<?= htmlspecialchars($uid) ?>">
    <header class="entry-header">
        <time class="entry-timestamp" datetime="<?= htmlspecialchars($post['published_at']) ?>">
            <?= $headerStamp ?>
        </time>
        <h2 class="entry-title">
            <a href="<?= $postUrl ?>"><?= htmlspecialchars($post['title']) ?></a>
        </h2>
    </header>

    <?php if (!empty($post['image_path'])): ?>
        <div class="entry-photo-wrap">
            <a href="<?= $postUrl ?>">
                <img 
                    src="<?= UPLOADS_URL . '/' . htmlspecialchars($post['image_path']) ?>" 
                    alt="<?= htmlspecialchars($post['title']) ?>" 
                    class="entry-photo"
                    loading="lazy"
                >
            </a>
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
            <a href="<?= $postUrl ?>" class="entry-permalink" title="Permanent link">PERMALINK // <?= htmlspecialchars($uid) ?></a>
        </div>
    </footer>
</article>
