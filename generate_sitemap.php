<?php
// Simple sitemap generator for blog posts
require_once __DIR__ . '/db_config.php';
$pdo = getDBConnection();

$baseUrl = (isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? ($_ENV['HOSTNAME'] ?? 'localhost'));

$stmt = $pdo->prepare('SELECT slug, updated_at, published_at FROM posts WHERE status = "published" AND (published_at IS NULL OR published_at <= NOW())');
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$items = [];
foreach ($rows as $r) {
    $loc = $baseUrl . '/blog_post.php?slug=' . rawurlencode($r['slug']);
    $lastmod = $r['updated_at'] ?? $r['published_at'] ?? date('c');
    $items[] = "  <url>\n    <loc>" . htmlspecialchars($loc) . "</loc>\n    <lastmod>" . date('c', strtotime($lastmod)) . "</lastmod>\n  </url>";
}

$xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n" . implode("\n", $items) . "\n</urlset>";

file_put_contents(__DIR__ . '/sitemap.xml', $xml);
echo "OK";

?>
