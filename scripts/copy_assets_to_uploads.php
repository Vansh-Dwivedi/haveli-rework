<?php
require_once __DIR__ . '/../db_config.php';

// Map source asset -> destination in uploads/blog
$map = [
    'assets/slide1.jpg' => 'uploads/blog/slide1.jpg',
    'assets/slide2.jpg' => 'uploads/blog/slide2.jpg',
    'assets/slide3.jpg' => 'uploads/blog/slide3.jpg',
    'assets/slide4.jpg' => 'uploads/blog/slide4.jpg',
    'assets/food.mp4'   => 'uploads/blog/food1.mp4'
];

$uploaddir = __DIR__ . '/../uploads/blog/';
if (!is_dir($uploaddir)) mkdir($uploaddir, 0755, true);

foreach ($map as $src => $dest) {
    $srcPath = __DIR__ . '/../' . $src;
    $destPath = __DIR__ . '/../' . $dest;
    if (!file_exists($srcPath)) {
        echo "Source missing: $srcPath\n";
        continue;
    }
    if (copy($srcPath, $destPath)) {
        echo "Copied $src -> $dest\n";
    } else {
        echo "Failed to copy $src\n";
    }
}

// Update DB to point posts to uploads/blog paths and switch video_url to local MP4 for two posts
try {
    $pdo = getDBConnection();

    $updates = [
        'saffron-butter-chicken' => ['featured_image' => 'uploads/blog/slide1.jpg', 'video' => ''],
        'street-style-paneer-tikka' => ['featured_image' => 'uploads/blog/slide2.jpg', 'video' => 'uploads/blog/food1.mp4'],
        'the-art-of-biryani' => ['featured_image' => 'uploads/blog/slide3.jpg', 'video' => ''],
        'tandoori-secrets-marinate-and-heat' => ['featured_image' => 'uploads/blog/slide4.jpg', 'video' => 'uploads/blog/food1.mp4'],
        'kulfi-and-dessert-pairings' => ['featured_image' => 'uploads/blog/slide1.jpg', 'video' => '']
    ];

    $stmt = $pdo->prepare('UPDATE posts SET featured_image = ?, video_url = ? WHERE slug = ?');
    foreach ($updates as $slug => $data) {
        $stmt->execute([$data['featured_image'], $data['video'], $slug]);
        echo "Updated DB for $slug\n";
    }
} catch (Exception $e) {
    echo "DB error: " . $e->getMessage() . "\n";
}

// Regenerate sitemap
@include_once __DIR__ . '/../generate_sitemap.php';

echo "Done.\n";

?>
