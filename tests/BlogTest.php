<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';

class BlogTest extends TestCase {
    public function testPublicListAndPostRender() {
        $pdo = getDBConnection();

        // Insert a published post
        $now = date('Y-m-d H:i:s');
        $stmt = $pdo->prepare('INSERT INTO posts (title, slug, excerpt, content, status, published_at, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute(['Test Post','test-post','Excerpt','<p>Hello</p>','published',$now,$now,$now]);

        // Simulate visiting blog.php
        $_GET = [];
        ob_start();
        include __DIR__ . '/../blog.php';
        $output = ob_get_clean();
        $this->assertStringContainsString('Test Post', $output);

        // Simulate visiting single post
        $_GET = ['slug' => 'test-post'];
        ob_start();
        include __DIR__ . '/../blog_post.php';
        $postOutput = ob_get_clean();
        $this->assertStringContainsString('Hello', $postOutput);
    }

    public function testSitemapGeneratorCreatesFile() {
        $pdo = getDBConnection();
        $now = date('Y-m-d H:i:s');
        $pdo->exec("INSERT INTO posts (title, slug, status, published_at, created_at, updated_at) VALUES ('S1','s1','published','$now','$now','$now')");

        // Run generator
        include __DIR__ . '/../generate_sitemap.php';
        $this->assertFileExists(__DIR__ . '/../sitemap.xml');
        $xml = file_get_contents(__DIR__ . '/../sitemap.xml');
        $this->assertStringContainsString('s1', $xml);
    }
}
