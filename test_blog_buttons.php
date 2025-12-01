<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Blog Button Test</title>
    <link rel="stylesheet" href="css/admin-blog.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { padding: 20px; background: #f6f8fb; }
        .button-test { margin: 20px 0; padding: 20px; background: white; border-radius: 10px; }
        .button-test h3 { margin-top: 0; }
        .button-row { margin: 10px 0; }
    </style>
</head>
<body>
    <h1>Blog Admin Button Test</h1>
    
    <div class="button-test">
        <h3>Primary Buttons</h3>
        <div class="button-row">
            <button class="btn btn-primary">
                <i class="fas fa-save"></i> Save
            </button>
            <button class="btn btn-primary btn-sm">
                <i class="fas fa-edit"></i> Edit
            </button>
            <a href="#" class="btn btn-primary">
                <i class="fab fa-facebook-f"></i> Share
            </a>
        </div>
    </div>
    
    <div class="button-test">
        <h3>Danger & Success Buttons</h3>
        <div class="button-row">
            <button class="btn btn-danger">
                <i class="fas fa-trash"></i> Delete
            </button>
            <button class="btn btn-danger btn-sm">
                <i class="fas fa-times"></i> Cancel
            </button>
            <button class="btn btn-success">
                <i class="fas fa-sync-alt"></i> Regenerate Sitemap
            </button>
        </div>
    </div>
    
    <div class="button-test">
        <h3>Ghost Buttons</h3>
        <div class="button-row">
            <a href="#" class="btn btn-ghost">
                <i class="fas fa-eye"></i> Preview
            </a>
            <a href="#" class="btn btn-ghost btn-sm">
                <i class="fas fa-external-link-alt"></i> View
            </a>
        </div>
    </div>
    
    <div class="button-test">
        <h3>Warning Buttons</h3>
        <div class="button-row">
            <button class="btn btn-warning">
                <i class="fas fa-exclamation-triangle"></i> Warning
            </button>
            <button class="btn btn-warning btn-sm">
                <i class="fas fa-bell"></i> Notify
            </button>
        </div>
    </div>
</body>
</html>