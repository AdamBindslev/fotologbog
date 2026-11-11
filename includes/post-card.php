<?php
/**
 * Post Card skabelon – Enkelt opslag i notesbogs-feedet
 */
$uid = !empty($post['uid']) ? $post['uid'] : (string)$post['id'];
$postUrl = BASE_URL . '/post.php?p=' . htmlspecialchars($uid);
$postTags = get_post_tags((int)$post['id']);
$exifStr = format_exif($post);
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
            <a href="<?= BASE_URL ?>/?location=<?= urlencode($post['location']) ?>" class="field-val" style="color: inherit; text-decoration: none;" title="Filtrer på <?= htmlspecialchars($post['location']) ?>">
                <?= htmlspecialchars($post['location']) ?>
            </a>
        </div>
        <?php endif; ?>
    </div>

    <h2 class="entry-title">
        <a href="<?= $postUrl ?>"><?= htmlspecialchars($post['title']) ?></a>
    </h2>

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
            <a href="<?= $postUrl ?>">
                <img 
                    src="<?= UPLOADS_URL . '/' . htmlspecialchars($post['image_path']) ?>" 
                    alt="<?= htmlspecialchars($post['title']) ?>" 
                    class="entry-photo"
                    loading="lazy"
                >
            </a>
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
                    <span class="meta-theme">THEME &amp; NOTE</span>
                <?php endif; ?>
            </div>
            <a href="<?= $postUrl ?>" class="entry-stamp-badge" title="Permanent link">
                <span class="stamp-icon">REF</span>
                <span class="stamp-code"><?= htmlspecialchars($uid) ?></span>
            </a>
        </div>
    </footer>
</article>
