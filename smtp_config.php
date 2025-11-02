<?php
/**
 * SMTP Configuration (Admin-only)
 * Lets an authenticated admin set the Zoho SMTP App Password safely.
 * Writes to smtp_password.php (ignored by git).
 */

session_start();

// Admin guard
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_access.php');
    exit;
}

$saveMessage = '';
$hasPassword = false;
$masked = '';

// Load existing password state (do not display value)
if (file_exists(__DIR__ . '/smtp_password.php')) {
    $hasPassword = true;
    $masked = str_repeat('•', 12) . ' (set)';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pwd = isset($_POST['smtp_password']) ? trim($_POST['smtp_password']) : '';

    if ($pwd === '') {
        $saveMessage = '<span style="color:#b91c1c">Please provide a password</span>';
    } else {
        // Persist to file using var_export to avoid quoting issues
        $content = "<?php\n$" . "SMTP_PASSWORD = " . var_export($pwd, true) . ";\n";
        $ok = @file_put_contents(__DIR__ . '/smtp_password.php', $content);
        if ($ok !== false) {
            // Best-effort file permission tightening (ignored on Windows)
            @chmod(__DIR__ . '/smtp_password.php', 0600);
            $saveMessage = '<span style="color:#065f46">Saved successfully.</span>';
            $hasPassword = true;
            $masked = str_repeat('•', 12) . ' (set)';
        } else {
            $saveMessage = '<span style="color:#b91c1c">Failed to save file. Check write permissions.</span>';
        }
    }
}

?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>SMTP Configuration • Haveli</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <link href="admin-dashboard.css" rel="stylesheet">
  <style>
    .wrap { max-width: 720px; margin: 40px auto; background:#fff; border:1px solid #e5e7eb; border-radius: 12px; padding: 24px; }
    .row { margin-bottom: 16px; }
    label { display:block; font-weight:600; margin-bottom: 6px; }
    input[type=password], input[type=text] { width:100%; padding:10px 12px; border:1px solid #d1d5db; border-radius:8px; }
    .btn { display:inline-flex; align-items:center; gap:8px; padding:10px 14px; border-radius:8px; border:1px solid #d1d5db; cursor:pointer; background:#fff; }
    .btn-primary { background: #2563eb; border-color:#2563eb; color:#fff; }
    .muted { color:#6b7280; }
    .row .inline { display:flex; gap:12px; align-items:center; }
    .pill { display:inline-flex; gap:8px; align-items:center; padding:6px 10px; border-radius:999px; background:#10b9811a; color:#065f46; border:1px solid #10b98140; }
  </style>
</head>
<body>
  <div class="wrap">
    <h1><i class="fas fa-key"></i> SMTP App Password</h1>
    <p class="muted">Set the Zoho SMTP App Password used for sending emails from info@haveli.co.uk.</p>

    <?php if ($saveMessage): ?>
      <p><?php echo $saveMessage; ?></p>
    <?php endif; ?>

    <form method="post">
      <div class="row">
        <label for="smtp_password">Zoho App Password</label>
        <input id="smtp_password" name="smtp_password" type="password" autocomplete="new-password" placeholder="Paste app password here" />
      </div>
      <div class="row inline">
        <button class="btn btn-primary" type="submit"><i class="fas fa-save"></i> Save Password</button>
        <a class="btn" href="admin_dashboard.php"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
      </div>
    </form>

    <div class="row">
      <p><strong>Status:</strong> <?php echo $hasPassword ? '<span class="pill"><i class="fas fa-check-circle"></i> Configured</span>' : '<span class="muted">Not set</span>'; ?></p>
      <?php if ($hasPassword): ?>
        <p class="muted">Stored as: smtp_password.php • Value: <?php echo $masked; ?></p>
        <p>
          <a class="btn" href="test_email_providers.php?to=kalakaarstudios@gmail.com"><i class="fas fa-paper-plane"></i> Send Test to kalakaarstudios@gmail.com</a>
        </p>
      <?php endif; ?>
    </div>

    <hr />
    <div class="row">
      <h3><i class="fas fa-info-circle"></i> Tips</h3>
      <ul class="muted">
        <li>Use a Zoho <em>App Password</em> (Account Security → App Passwords), not your login password.</li>
        <li>File smtp_password.php is ignored by git (.gitignore) and readable only by the server.</li>
        <li>You can also set an environment variable instead: <code>HAVELI_SMTP_PASS</code>.</li>
      </ul>
    </div>
  </div>
</body>
</html>
