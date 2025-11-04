<?php
require_once __DIR__ . '/../db_config.php';

try {
    $pdo = getDBConnection();

    $now = date('Y-m-d H:i:s');

    $posts = [
        [
            'title' => 'Saffron Butter Chicken: A Classic Reimagined',
            'slug' => 'saffron-butter-chicken',
            'excerpt' => 'Our take on the beloved butter chicken uses saffron and slow-simmered tomatoes for depth.',
            'content' => '<p>We marinate the chicken overnight and simmer gently in a saffron-infused tomato gravy. Served with buttery naan and fragrant rice.</p>',
            'featured_image' => 'assets/slide1.jpg',
            'video_url' => '',
            'meta_title' => 'Saffron Butter Chicken Recipe & Story',
            'meta_description' => 'Discover our saffron butter chicken recipe and the story behind its flavours.',
            'status' => 'published',
            'published_at' => $now
        ],
        [
            'title' => 'Street-Style Paneer Tikka with Charred Peppers',
            'slug' => 'street-style-paneer-tikka',
            'excerpt' => 'Charred, smoky, and marinated with our secret spice blend.',
            'content' => '<p>Paneer cubes skewered and charred over open flame with peppers and a tangy mint chutney.</p>',
            'featured_image' => 'assets/slide2.jpg',
            'video_url' => 'https://www.youtube.com/watch?v=ysz5S6PUM-U',
            'meta_title' => 'Paneer Tikka - Haveli Street Style',
            'meta_description' => 'Learn how we make our paneer tikka with a smoky street-style finish.',
            'status' => 'published',
            'published_at' => $now
        ],
        [
            'title' => 'The Art of Biryani: Layers, Spice & Patience',
            'slug' => 'the-art-of-biryani',
            'excerpt' => 'Biryani is a celebration of technique — here is how we approach it at Haveli.',
            'content' => '<p>From fragrant basmati to perfectly seared meat, timing is everything. We share tips for restaurant-quality biryani.</p>',
            'featured_image' => 'assets/slide3.jpg',
            'video_url' => '',
            'meta_title' => 'Biryani Guide: Layers & Spices',
            'meta_description' => 'A practical guide to making layered biryani with pro tips from our kitchen.',
            'status' => 'published',
            'published_at' => $now
        ],
        [
            'title' => 'Tandoori Secrets: Marinade and High Heat',
            'slug' => 'tandoori-secrets-marinate-and-heat',
            'excerpt' => 'How high heat and the right marinade create a signature tandoori crust.',
            'content' => '<p>We break down the marinade chemistry and the quick high-heat sear that gives tandoori its char.</p>',
            'featured_image' => 'assets/slide4.jpg',
            'video_url' => 'https://www.youtube.com/watch?v=jNQXAC9IVRw',
            'meta_title' => 'Tandoori Secrets Revealed',
            'meta_description' => 'Explained: marinades, heat, and technique for great tandoori.',
            'status' => 'published',
            'published_at' => $now
        ],
        [
            'title' => 'Sweet Notes: Kulfi & Dessert Pairings',
            'slug' => 'kulfi-and-dessert-pairings',
            'excerpt' => 'Traditional kulfi with modern twists and pairing ideas for your meal.',
            'content' => '<p>Rich, dense kulfi with pistachio and saffron, plus modern twists like mango kulfi tartlets.</p>',
            'featured_image' => 'assets/slide1.jpg',
            'video_url' => '',
            'meta_title' => 'Kulfi & Dessert Pairings at Haveli',
            'meta_description' => 'Dessert ideas and kulfi variations to finish your meal perfectly.',
            'status' => 'published',
            'published_at' => $now
        ],
    ];

    $sql = 'INSERT INTO posts (title, slug, excerpt, content, featured_image, video_url, meta_title, meta_description, status, published_at, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
    $stmt = $pdo->prepare($sql);

    foreach ($posts as $p) {
        $stmt->execute([
            $p['title'], $p['slug'], $p['excerpt'], $p['content'], $p['featured_image'], $p['video_url'], $p['meta_title'], $p['meta_description'], $p['status'], $p['published_at'], $now, $now
        ]);
        echo "Inserted: " . $p['slug'] . PHP_EOL;
    }

    echo "Done.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}

?>
