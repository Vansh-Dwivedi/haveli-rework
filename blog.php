<?php
require_once __DIR__ . '/db_config.php';
$pdo = getDBConnection();

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

$stmt = $pdo->prepare('SELECT SQL_CALC_FOUND_ROWS id, title, slug, excerpt, featured_image, published_at FROM posts WHERE status = "published" AND (published_at IS NULL OR published_at <= NOW()) ORDER BY published_at DESC, created_at DESC LIMIT ? OFFSET ?');
$stmt->bindValue(1, $perPage, PDO::PARAM_INT);
$stmt->bindValue(2, $offset, PDO::PARAM_INT);
$stmt->execute();
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalStmt = $pdo->query('SELECT FOUND_ROWS()');
$total = (int)$totalStmt->fetchColumn();
$totalPages = (int)ceil($total / $perPage);

?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Blog - Haveli</title>
    <meta name="description" content="Latest articles from Haveli restaurant.">
        <link rel="stylesheet" href="style.css">
        <link rel="stylesheet" href="css/blog.css">
        <style>
            body { background: #6a3038; }
            .article-shell { max-width:900px; margin:34px auto; padding:0 16px; }
            .article-card { background:#fff; border-radius:12px; padding:22px; box-shadow:0 12px 32px rgba(16,24,40,0.06); }
            .list-item { padding:18px 0; border-bottom:1px solid #eee; display:flex; gap:18px; align-items:flex-start }
            .list-item:last-child { border-bottom:none }
            .list-thumb { width:320px; max-width:40%; border-radius:8px; object-fit:cover; display:block }
            .list-content { flex:1 }
            .list-title { font-size:20px; margin:0 0 6px }
            .list-meta { color:#666; font-size:13px; margin-bottom:8px }
            .list-excerpt { color:#333; line-height:1.6 }
            @media (max-width:720px) { .list-item { flex-direction:column } .list-thumb { width:100%; max-width:100% } }
        </style>
</head>
<body>
        <main class="article-shell">
            <div class="article-card">
                <h1>Blog</h1>
                <?php foreach ($posts as $p): ?>
                    <article class="list-item">
                        <?php if (!empty($p['featured_image'])): ?>
                            <a href="blog_post.php?slug=<?php echo urlencode($p['slug']); ?>"><img class="list-thumb" src="<?php echo htmlspecialchars($p['featured_image']); ?>" alt="<?php echo htmlspecialchars($p['title']); ?>"></a>
                        <?php endif; ?>
                        <div class="list-content">
                            <h2 class="list-title"><a href="blog_post.php?slug=<?php echo urlencode($p['slug']); ?>"><?php echo htmlspecialchars($p['title']); ?></a></h2>
                            <div class="list-meta">Published: <?php echo htmlspecialchars($p['published_at']); ?></div>
                            <div class="list-excerpt"><?php echo nl2br(htmlspecialchars($p['excerpt'])); ?></div>
                            <div style="margin-top:10px"><a href="blog_post.php?slug=<?php echo urlencode($p['slug']); ?>">Read more →</a></div>
                        </div>
                    </article>
                <?php endforeach; ?>

                <nav style="margin-top:16px;">
                        <?php if ($page > 1): ?><a href="blog.php?page=<?php echo $page-1; ?>">← Prev</a><?php endif; ?>
                        <?php if ($page < $totalPages): ?> <a href="blog.php?page=<?php echo $page+1; ?>">Next →</a><?php endif; ?>
                </nav>
            </div>
        </main>
</body>
</html>
