<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';

$posts = get_recent_posts(30, 0);

header('Content-Type: application/rss+xml; charset=utf-8');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
  <channel>
    <title><![CDATA[<?= SITE_TITLE ?>]]></title>
    <link><?= BASE_URL ?>/</link>
    <description><![CDATA[<?= SITE_DESCRIPTION ?>]]></description>
    <language>da-DK</language>
    <atom:link href="<?= BASE_URL ?>/rss.php" rel="self" type="application/rss+xml" />
    <?php if (!empty($posts)): ?>
      <lastBuildDate><?= date(DATE_RSS, strtotime($posts[0]['published_at'])) ?></lastBuildDate>
    <?php endif; ?>

    <?php foreach ($posts as $post): 
        $postUrl = get_post_url($post);
        $pubDate = date(DATE_RSS, strtotime($post['published_at']));
        $postImages = get_post_images((int)$post['id'], $post['image_path'] ?? null);
        $imgTags = '';
        foreach ($postImages as $pImg) {
            $u = rtrim(BASE_URL, '/') . '/uploads/' . rawurlencode(basename($pImg));
            $imgTags .= '<p><img src="' . htmlspecialchars($u) . '" alt="' . htmlspecialchars($post['title']) . '" /></p>';
        }
        $desc = $imgTags . render_content($post['content']);
        $exifStr = format_exif($post);
        if (!empty($exifStr)) {
            $desc .= '<p><small>⌀ ' . htmlspecialchars($exifStr) . '</small></p>';
        }
        if (!empty($post['location'])) {
            $desc .= '<p><em>⌖ ' . htmlspecialchars($post['location']) . '</em></p>';
        }
        $postTags = get_post_tags((int)$post['id']);
        $primaryFile = !empty($postImages[0]) ? UPLOADS_DIR . '/' . basename($postImages[0]) : '';
        $primaryUrl = !empty($postImages[0]) ? rtrim(BASE_URL, '/') . '/uploads/' . rawurlencode(basename($postImages[0])) : '';
        $enclosureMime = 'image/jpeg';
        $enclosureLen = 0;
        if ($primaryFile && is_file($primaryFile)) {
            $enclosureLen = (int)filesize($primaryFile);
            $imgInfo = @getimagesize($primaryFile);
            if (!empty($imgInfo['mime'])) {
                $enclosureMime = $imgInfo['mime'];
            }
        }
    ?>
      <item>
        <title><![CDATA[<?= format_analog_header($post['published_at'], $post['location'] ?? null) . ' ' . $post['title'] ?>]]></title>
        <link><?= htmlspecialchars($postUrl) ?></link>
        <guid isPermaLink="true"><?= htmlspecialchars($postUrl) ?></guid>
        <pubDate><?= $pubDate ?></pubDate>
        <author><?= htmlspecialchars(SITE_AUTHOR) ?></author>
        <?php foreach ($postTags as $t): ?>
          <category><![CDATA[<?= $t['name'] ?>]]></category>
        <?php endforeach; ?>
        <description><![CDATA[<?= $desc ?>]]></description>
        <?php if ($primaryUrl && $enclosureLen > 0): ?>
          <enclosure url="<?= htmlspecialchars($primaryUrl) ?>" length="<?= $enclosureLen ?>" type="<?= htmlspecialchars($enclosureMime) ?>" />
        <?php endif; ?>
      </item>
    <?php endforeach; ?>
  </channel>
</rss>
