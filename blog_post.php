<?php
$path = __DIR__ . '/lib/sanitize.php';
if (file_exists($path)) require_once $path;
require_once __DIR__ . '/db_config.php';
$pdo = getDBConnection();

$slug = $_GET['slug'] ?? '';
if (!$slug) {
    header('Location: blog.php'); exit;
}

$stmt = $pdo->prepare('SELECT * FROM posts WHERE slug = ? AND status = "published" AND (published_at IS NULL OR published_at <= NOW()) LIMIT 1');
$stmt->execute([$slug]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$post) {
    http_response_code(404);
    echo "Post not found"; exit;
}

// Simple YouTube embed helper
function youtube_embed_html($url) {
    if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([A-Za-z0-9_-]+)/', $url, $m)) {
        $id = $m[1];
        return '<iframe width="100%" height="400" src="https://www.youtube.com/embed/' . htmlspecialchars($id) . '" frameborder="0" allowfullscreen></iframe>';
    }
    return '';
}

?>
<?php
// Sanitize content for safe output
 $safeContent = isset($post['content']) ? (function_exists('sanitize_html') ? sanitize_html($post['content']) : htmlspecialchars($post['content'])) : '';

// Build absolute URLs for social metadata (useful for Facebook/Twitter scrapers)
$scheme = isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http');
$pageUrl = $scheme . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/blog_post.php?slug=' . urlencode($post['slug']);
$absImage = '';
if (!empty($post['featured_image'])) {
    if (preg_match('#^https?://#i', $post['featured_image'])) {
        $absImage = $post['featured_image'];
    } else {
        $absImage = $scheme . '://' . $_SERVER['HTTP_HOST'] . '/' . ltrim($post['featured_image'], '/');
    }
}
?>
<!doctype html>
<html prefix="og: http://ogp.me/ns#">
<head>
    <meta charset="utf-8">
    <title><?php echo htmlspecialchars($post['meta_title'] ?: $post['title']); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($post['meta_description'] ?: substr(strip_tags($post['excerpt'] ?: $post['content']), 0, 160)); ?>">
    <link rel="canonical" href="<?php echo htmlspecialchars($pageUrl); ?>">
        <link rel="stylesheet" href="style.css">
        <link rel="stylesheet" href="css/blog.css">
        <meta property="og:title" content="<?php echo htmlspecialchars($post['title']); ?>">
        <meta property="og:description" content="<?php echo htmlspecialchars($post['meta_description'] ?: substr(strip_tags($post['excerpt'] ?: $post['content']), 0, 160)); ?>">
        <meta property="og:url" content="<?php echo htmlspecialchars($pageUrl); ?>">
        <meta property="og:type" content="article">
        <meta property="og:site_name" content="Haveli">
        <?php if (!empty($absImage)): ?>
            <meta property="og:image" content="<?php echo htmlspecialchars($absImage); ?>">
            <meta property="og:image:width" content="1200">
            <meta property="og:image:height" content="630">
        <?php endif; ?>
        <meta name="twitter:card" content="<?php echo !empty($absImage) ? 'summary_large_image' : 'summary'; ?>">
        <meta name="twitter:title" content="<?php echo htmlspecialchars($post['title']); ?>">
        <meta name="twitter:description" content="<?php echo htmlspecialchars($post['meta_description'] ?: substr(strip_tags($post['excerpt'] ?: $post['content']), 0, 160)); ?>">
        <?php if (!empty($absImage)): ?><meta name="twitter:image" content="<?php echo htmlspecialchars($absImage); ?>"><?php endif; ?>
        <style>
            /* Article page overrides for a clean, readable blog layout */
            body { background: #6a3038; }
            .article-shell { max-width: 900px; margin: 34px auto; padding: 0 16px; }
            .article-card { background: #fff; border-radius: 12px; padding: 28px; box-shadow: 0 14px 40px rgba(16,24,40,0.08); color:#111 }
            .post-title { font-size: 28px; margin: 0 0 8px; line-height:1.15; }
            .post-meta { color:#666; font-size:14px; margin-bottom:16px; display:flex; gap:12px; align-items:center }
            .featured { width:100%; height:auto; display:block; border-radius:8px; margin: 10px 0 20px; object-fit:cover }
            .post-content { font-size:17px; line-height:1.75; color:#2b2b2b }
            .author-box { display:flex; gap:12px; align-items:center; margin-top:18px; padding-top:18px; border-top:1px dashed #eee }
            .author-avatar { width:56px; height:56px; border-radius:50%; background:#eee; display:inline-block; flex:0 0 56px }
            .share-row { margin-top:18px; display:flex; gap:10px; align-items:center }
            .share-btn { display:inline-flex; align-items:center; gap:8px; padding:8px 12px; border-radius:8px; background:#f3f4f6; color:#111; text-decoration:none; border:1px solid #e6e6e6 }
            .related-posts { margin-top:26px }
            .related-grid { display:flex; gap:12px; flex-wrap:wrap }
            .related-item { background:#fafafa; padding:10px; border-radius:8px; flex:1 1 30%; min-width:160px; box-shadow:0 6px 18px rgba(2,6,23,0.04); text-decoration:none; color:inherit }
            .related-item div { color:inherit }
            @media (max-width:720px) { .article-card { padding:18px } .post-title { font-size:22px } }
        </style>
</head>
<body>
        <main class="article-shell">
            <div class="article-card">
                <article>
                    <h1 class="post-title"><?php echo htmlspecialchars($post['title']); ?></h1>
                    <div class="post-meta">
                        <time datetime="<?php echo htmlspecialchars($post['published_at']); ?>">Published: <?php echo htmlspecialchars(date('F j, Y', strtotime($post['published_at']))); ?></time>
                        <span>&middot;</span>
                        <span><?php echo htmlspecialchars($post['reading_time'] ?? '3 min read'); ?></span>
                    </div>

                    <?php if (!empty($post['featured_image'])): ?>
                        <img class="featured" src="<?php echo htmlspecialchars($absImage); ?>" alt="<?php echo htmlspecialchars($post['title']); ?>">
                    <?php endif; ?>

                    <?php if (!empty($post['video_url'])): ?>
                        <div style="margin:16px 0;">
                            <?php
                                $video = trim($post['video_url']);
                                if (preg_match('/\.(mp4|webm|ogg)(\?.*)?$/i', $video) || strpos($video, 'uploads/') === 0 || strpos($video, '/uploads/') === 0) {
                                    $src = htmlspecialchars($video);
                                    echo "<video controls style=\"max-width:100%;height:auto;\">";
                                    echo "<source src=\"$src\" type=\"video/mp4\">";
                                    echo "Your browser does not support the video tag.";
                                    echo "</video>";
                                } else {
                                    $embed = youtube_embed_html($video);
                                    if ($embed) echo $embed;
                                    else echo '<a href="' . htmlspecialchars($video) . '" target="_blank" rel="noopener">Watch video</a>';
                                }
                            ?>
                        </div>
                    <?php endif; ?>

                    <div class="post-content"><?php echo $safeContent; ?></div>

                    <div class="author-box">
                        <div class="author-avatar" aria-hidden="true"></div>
                        <div>
                            <div style="font-weight:600"><?php echo htmlspecialchars($post['author_name'] ?? 'Haveli Team'); ?></div>
                            <div style="color:#666;font-size:13px">Author • Haveli Cafe</div>
                        </div>
                    </div>

                    <div class="share-row" aria-label="Share this post">
                        <?php
                            $shareable_url = htmlspecialchars_decode($pageUrl);
                            $tweet = htmlspecialchars($post['title']) . ' — ' . $shareable_url;
                        ?>
                        <a class="share-btn" href="https://twitter.com/intent/tweet?text=<?php echo urlencode($tweet); ?>" target="_blank" rel="noopener">Twitter</a>
                        <a class="share-btn" onclick="window.open('https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode($shareable_url); ?>', 'facebook-share-dialog', 'width=626,height=436'); return false;" href="#">Facebook</a>
                        <a class="share-btn" href="mailto:?subject=<?php echo urlencode($post['title']); ?>&body=Check out this article: <?php echo urlencode($shareable_url); ?>">Email</a>
                    </div>

                    <div class="related-posts">
                        <h3>Related posts</h3>
                        <div class="related-grid">
                            <?php
                                try {
                                    $rstmt = $pdo->prepare('SELECT id,title,slug,featured_image FROM posts WHERE status = "published" AND id != ? ORDER BY published_at DESC LIMIT 3');
                                    $rstmt->execute([$post['id']]);
                                    $related = $rstmt->fetchAll(PDO::FETCH_ASSOC);
                                } catch (Exception $e) { $related = []; }
                                foreach ($related as $r) {
                                    $rimg = !empty($r['featured_image']) ? $r['featured_image'] : 'assets/slide1.jpg';
                                    echo '<a class="related-item" href="blog_post.php?slug=' . urlencode($r['slug']) . '">';
                                    echo '<div style="height:90px;background-image:url(' . htmlspecialchars($rimg) . ');background-size:cover;background-position:center;border-radius:6px;margin-bottom:8px"></div>';
                                    echo '<div style="font-weight:600">' . htmlspecialchars($r['title']) . '</div>';
                                    echo '</a>';
                                }
                            ?>
                        </div>
                    </div>

                </article>
            </div>
        </main>
</body>
</html>
