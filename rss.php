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
        $uid = !empty($post['uid']) ? $post['uid'] : $post['id'];
        $postUrl = BASE_URL . '/post.php?p=' . htmlspecialchars($uid);
        $pubDate = date(DATE_RSS, strtotime($post['published_at']));
        $imgTag = !empty($post['image_path']) ? '<p><img src="' . UPLOADS_URL . '/' . htmlspecialchars($post['image_path']) . '" alt="' . htmlspecialchars($post['title']) . '" /></p>' : '';
        $desc = $imgTag . render_content($post['content']);
        if (!empty($post['location'])) {
            $desc .= '<p><em>Lokation: ' . htmlspecialchars($post['location']) . '</em></p>';
        }
    ?>
      <item>
        <title><![CDATA[<?= format_analog_header($post['published_at'], $post['location'] ?? null) . ' ' . $post['title'] ?>]]></title>
        <link><?= $postUrl ?></link>
        <guid isPermaLink="true"><?= $postUrl ?></guid>
        <pubDate><?= $pubDate ?></pubDate>
        <description><![CDATA[<?= $desc ?>]]></description>
        <?php if (!empty($post['image_path'])): ?>
          <enclosure url="<?= UPLOADS_URL . '/' . htmlspecialchars($post['image_path']) ?>" type="image/jpeg" />
        <?php endif; ?>
      </item>
    <?php endforeach; ?>
  </channel>
</rss>
