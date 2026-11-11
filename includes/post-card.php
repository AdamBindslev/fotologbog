<?php
/**
 * Post Card skabelon – Enkelt opslag i notesbogs-feedet
 */
$uid = !empty($post['uid']) ? $post['uid'] : (string)$post['id'];
$postUrl = BASE_URL . '/post.php?p=' . htmlspecialchars($uid);
?>
<article class="log-entry" id="entry-<?= htmlspecialchars($uid) ?>">
    <!-- Vintage formular-header -->
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

    <h2 class="entry-title">
        <a href="<?= $postUrl ?>"><?= htmlspecialchars($post['title']) ?></a>
    </h2>

    <?php if (!empty($post['image_path'])): ?>
        <div class="entry-photo-wrap">
            <span class="photo-tape photo-tape-top" aria-hidden="true"></span>
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
        <div class="meta-bottom">
            <span class="meta-theme">THEME &amp; NOTE</span>
            <a href="<?= $postUrl ?>" class="entry-stamp-badge" title="Permanent link">
                <span class="stamp-icon">REF</span>
                <span class="stamp-code"><?= htmlspecialchars($uid) ?></span>
            </a>
        </div>
    </footer>
</article>
