<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('HTTP/1.0 403 Forbidden');
    exit;
}

require_once __DIR__ . '/db_config.php';

$pdo = getDBConnection();

function slugify($text) {
    // basic slugify: lower-case, transliterate, replace non-alnum with hyphens
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('/[^A-Za-z0-9]+/', '-', $text);
    $text = trim($text, '-');
    $text = strtolower($text);
    if ($text === '') return 'post-' . time();
    return $text;
}

// Handle actions: create, update, delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save';
    $title = trim($_POST['title'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $excerpt = $_POST['excerpt'] ?? '';
    $content = $_POST['content'] ?? '';
    $status = $_POST['status'] ?? 'draft';
    $video_url = trim($_POST['video_url'] ?? '');
    $meta_title = trim($_POST['meta_title'] ?? '');
    $meta_description = trim($_POST['meta_description'] ?? '');
    $published_at = $_POST['published_at'] ?: null;

    // featured image upload (hardened)
    $featuredImagePath = null;
    if (!empty($_FILES['featured_image']['name'])) {
        $uploaddir = __DIR__ . '/uploads/blog/';
        if (!is_dir($uploaddir)) mkdir($uploaddir, 0755, true);
        $f = $_FILES['featured_image'];
        // Basic size limit
        if ($f['size'] > 5 * 1024 * 1024) {
            // ignore oversized file
        } else {
            // Check MIME using finfo
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($f['tmp_name']);
            $mimeAllowed = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/gif' => 'gif',
                'image/webp' => 'webp'
            ];
            if (isset($mimeAllowed[$mime])) {
                $ext = $mimeAllowed[$mime];
                // sanitize base name using slug
                $basename = slugify(pathinfo($f['name'], PATHINFO_FILENAME));
                $filename = $basename . '-' . time() . '.' . $ext;
                $target = $uploaddir . $filename;

                // validate image using getimagesize
                $imgInfo = @getimagesize($f['tmp_name']);
                if ($imgInfo === false) {
                    // not a valid image
                } else {
                    // Move and attempt to strip EXIF by re-saving via GD if available
                    if (move_uploaded_file($f['tmp_name'], $target)) {
                        // For JPEG, re-save to strip EXIF
                        if ($ext === 'jpg' || $ext === 'jpeg') {
                            if (function_exists('imagecreatefromjpeg') && function_exists('imagejpeg')) {
                                $img = @imagecreatefromjpeg($target);
                                if ($img) {
                                    imagejpeg($img, $target, 90);
                                    imagedestroy($img);
                                }
                            }
                        } elseif ($ext === 'png') {
                            if (function_exists('imagecreatefrompng') && function_exists('imagepng')) {
                                $img = @imagecreatefrompng($target);
                                if ($img) {
                                    imagepng($img, $target);
                                    imagedestroy($img);
                                }
                            }
                        }
                        $featuredImagePath = 'uploads/blog/' . $filename;
                    }
                }
            }
        }
    }

    if ($action === 'delete' && !empty($_POST['id'])) {
        $stmt = $pdo->prepare('DELETE FROM posts WHERE id = ?');
        $stmt->execute([ (int)$_POST['id'] ]);
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Post deleted successfully']);
        exit;
    }

    if (empty($slug)) $slug = slugify($title);

    // Ensure unique slug
    $baseSlug = $slug;
    $i = 1;
    while (true) {
        $stmt = $pdo->prepare('SELECT id FROM posts WHERE slug = ?');
        $stmt->execute([$slug]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row || (isset($_POST['id']) && $row['id'] == $_POST['id'])) break;
        $slug = $baseSlug . '-' . $i; $i++;
    }

        if ($action === 'save' || $action === 'create' || $action === 'update') {
        if (!empty($_POST['id'])) {
            // update
            $id = (int)$_POST['id'];
            $params = [$title, $slug, $excerpt, $content, $featuredImagePath ?: null, $video_url, $meta_title, $meta_description, $status, $published_at, $id];
            $sql = 'UPDATE posts SET title=?, slug=?, excerpt=?, content=?, featured_image=?, video_url=?, meta_title=?, meta_description=?, status=?, published_at=? WHERE id=?';
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
        } else {
            // insert
            $sql = 'INSERT INTO posts (title, slug, excerpt, content, featured_image, video_url, meta_title, meta_description, status, published_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$title, $slug, $excerpt, $content, $featuredImagePath, $video_url, $meta_title, $meta_description, $status, $published_at]);
        }

        // regenerate sitemap after changes
        @include_once __DIR__ . '/generate_sitemap.php';

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Post saved successfully']);
        exit;
    }
}

// Fetch posts for admin list
$stmt = $pdo->query('SELECT * FROM posts ORDER BY created_at DESC');
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Build public base URL for preview/share links
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$publicBase = $protocol . '://' . $host;

// If editing - check both GET and referer for edit parameter
$editing = null;
$editId = null;

// Check GET parameters first
if (isset($_GET['action']) && $_GET['action'] === 'edit' && !empty($_GET['id'])) {
    $editId = (int)$_GET['id'];
}
// If not in GET, check if we were referred from dashboard with edit in hash
if (!$editId && isset($_SERVER['HTTP_REFERER'])) {
    $referer = $_SERVER['HTTP_REFERER'];
    if (strpos($referer, '#blog') !== false && strpos($referer, 'edit=') !== false) {
        // Extract edit ID from URL
        preg_match('/edit=(\d+)/', $referer, $matches);
        if (isset($matches[1])) {
            $editId = (int)$matches[1];
        }
    }
}

if ($editId) {
    $stmt = $pdo->prepare('SELECT * FROM posts WHERE id = ?');
    $stmt->execute([$editId]);
    $editing = $stmt->fetch(PDO::FETCH_ASSOC);
}

?>
<div class="blog-admin-content">
    <h1>Blog - Admin</h1>
    <p><a href="admin_dashboard_simple.php" onclick="showSection('dashboard')">← Back to Dashboard</a> | <a href="blog.php" target="_blank">View public blog</a></p>
    <button id="regen-sitemap" class="btn btn-success btn-sm" style="margin-left:12px;">
        <i class="fas fa-sync-alt"></i> Regenerate Sitemap
    </button>

    <section class="post-list">
        <h2>Posts</h2>
        <?php foreach ($posts as $p): ?>
            <div class="post-item">
                <div>
                    <strong><?php echo htmlspecialchars($p['title']); ?></strong>
                    <div style="font-size:12px;color:#666"><?php echo htmlspecialchars($p['slug']); ?> — <?php echo htmlspecialchars($p['status']); ?> — <?php echo $p['published_at']; ?></div>
                </div>
                <div>
                    <button class="btn btn-primary btn-sm" onclick="editPost(<?php echo $p['id']; ?>)">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                    <button class="btn btn-danger btn-sm" onclick="deletePost(<?php echo $p['id']; ?>)">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                    <?php
                        // absolute public URL for this post
                        $publicUrl = $publicBase . '/blog_post.php?slug=' . urlencode($p['slug']);
                    ?>
                    <a class="btn btn-ghost btn-sm" href="<?php echo htmlspecialchars($publicUrl); ?>" target="_blank">
                        <i class="fas fa-eye"></i> Preview
                    </a>
                    <a class="btn btn-primary btn-sm" href="#" onclick="window.open('https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode($publicUrl); ?>','fbshare','width=626,height=436'); return false;">
                        <i class="fab fa-facebook-f"></i> Share
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </section>

    <section class="post-form">
    <h2><?php echo $editing ? 'Edit Post' : 'Create Post'; ?></h2>
    <form id="blog-form" method="post" enctype="multipart/form-data">
            <?php if ($editing): ?><input type="hidden" name="id" value="<?php echo $editing['id']; ?>"><?php endif; ?>
            <input type="hidden" name="action" value="save">
            <label>Title
                <input type="text" name="title" value="<?php echo htmlspecialchars($editing['title'] ?? ''); ?>" required>
            </label>
            <label>Slug (optional)
                <input type="text" name="slug" value="<?php echo htmlspecialchars($editing['slug'] ?? ''); ?>">
            </label>
            <label>Excerpt
                <textarea name="excerpt" rows="3"><?php echo htmlspecialchars($editing['excerpt'] ?? ''); ?></textarea>
            </label>
            <label>Featured Image
                <input type="file" name="featured_image" accept="image/*">
                <?php if (!empty($editing['featured_image'])): ?><div><img src="<?php echo htmlspecialchars($editing['featured_image']); ?>" style="max-width:200px;margin-top:8px"></div><?php endif; ?>
            </label>
            <label>Video URL (YouTube embed allowed)
                <input type="text" name="video_url" value="<?php echo htmlspecialchars($editing['video_url'] ?? ''); ?>">
            </label>
            <label>Meta title
                <input type="text" name="meta_title" value="<?php echo htmlspecialchars($editing['meta_title'] ?? ''); ?>">
            </label>
            <label>Meta description
                <input type="text" name="meta_description" value="<?php echo htmlspecialchars($editing['meta_description'] ?? ''); ?>">
            </label>
            <label>Status
                <select name="status"><option value="draft">Draft</option><option value="published">Published</option></select>
            </label>
            <label>Publish date
                <input type="datetime-local" name="published_at" value="<?php echo !empty($editing['published_at']) ? date('Y-m-d\TH:i', strtotime($editing['published_at'])) : ''; ?>">
            </label>
            <div style="margin-top:12px">
                <button class="btn btn-primary" type="submit">
                    <i class="fas fa-save"></i> Save
                </button>
                <?php if ($editing): ?>
                    <?php $editPublicUrl = $publicBase . '/blog_post.php?slug=' . urlencode($editing['slug']); ?>
                    <a class="btn btn-ghost btn-sm" href="<?php echo htmlspecialchars($editPublicUrl); ?>" target="_blank" style="margin-left:8px">
                        <i class="fas fa-eye"></i> Preview
                    </a>
                    <a class="btn btn-primary btn-sm" href="#" onclick="window.open('https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode($editPublicUrl); ?>','fbshare','width=626,height=436'); return false;" style="margin-left:6px">
                        <i class="fab fa-facebook-f"></i> Share
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </section>
</div>

<script>
// Blog admin JavaScript
document.getElementById('regen-sitemap').addEventListener('click', function() {
    this.disabled = true;
    fetch('generate_sitemap.php').then(r => r.text()).then(t => {
        alert('Sitemap regeneration: ' + t);
        this.disabled = false;
    }).catch(err => { alert('Error regenerating sitemap'); this.disabled = false; });
});

// Handle form submission
document.getElementById('blog-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('admin_blog_content.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            reloadBlogContent();
        } else {
            alert('Error: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error submitting form');
    });
});

// Edit post function
function editPost(id) {
    // Reload blog content with edit parameter
    fetch('admin_blog_content.php?edit=' + id)
        .then(response => response.text())
        .then(html => {
            const blogContent = document.getElementById('blog-content');
            if (blogContent) {
                blogContent.innerHTML = html;
                
                // Execute any scripts in the loaded content
                const scripts = blogContent.querySelectorAll('script');
                scripts.forEach(script => {
                    const newScript = document.createElement('script');
                    Array.from(script.attributes).forEach(attr => {
                        newScript.setAttribute(attr.name, attr.value);
                    });
                    newScript.textContent = script.textContent;
                    blogContent.appendChild(newScript);
                });
            }
        })
        .catch(error => {
            console.error('Error loading edit form:', error);
            alert('Error loading edit form');
        });
}

// Delete post function
function deletePost(id) {
    if (confirm('Are you sure you want to delete this post?')) {
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('id', id);
        
        fetch('admin_blog_content.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                reloadBlogContent();
            } else {
                alert('Error: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error deleting post');
        });
    }
}

// Reload blog content function
function reloadBlogContent() {
    fetch('admin_blog_content.php')
        .then(response => response.text())
        .then(html => {
            const blogContent = document.getElementById('blog-content');
            if (blogContent) {
                blogContent.innerHTML = html;
                
                // Execute any scripts in the loaded content
                const scripts = blogContent.querySelectorAll('script');
                scripts.forEach(script => {
                    const newScript = document.createElement('script');
                    Array.from(script.attributes).forEach(attr => {
                        newScript.setAttribute(attr.name, attr.value);
                    });
                    newScript.textContent = script.textContent;
                    blogContent.appendChild(newScript);
                });
            }
        })
        .catch(error => {
            console.error('Error reloading blog content:', error);
            alert('Error reloading blog content');
        });
}
</script>