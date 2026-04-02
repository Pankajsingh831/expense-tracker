<?php
// One-click database setup
define('DB_HOST', '127.0.0.1:3307');
define('DB_USER', 'root');
define('DB_PASS', '');
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$sql = file_get_contents('database.sql');
// Split by semicolon and run each statement
$statements = array_filter(array_map('trim', explode(';', $sql)));

$success = 0; $errors = [];
foreach ($statements as $stmt) {
    if (empty($stmt)) continue;
    if (!$conn->query($stmt)) { $errors[] = $conn->error . " in: " . substr($stmt,0,60); }
    else $success++;
}
?>
<!DOCTYPE html>
<html>
<head>
<title>ExpenseFlow Setup</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="assets/css/style.css" rel="stylesheet">
</head>
<body style="background:var(--body-bg);padding:40px;">
<div style="max-width:600px;margin:0 auto;">
  <div style="text-align:center;margin-bottom:32px;">
    <div style="font-size:48px;margin-bottom:12px;">💸</div>
    <h1 style="font-family:var(--font-display);font-size:28px;">ExpenseFlow Setup</h1>
  </div>
  <?php if (empty($errors)): ?>
  <div class="alert alert-success rounded-3">
    <h5>✅ Setup Complete!</h5>
    <p>Database created successfully. <?= $success ?> statements executed.</p>
    <p><strong>Demo login:</strong> demo@expenseflow.com / password</p>
    <a href="login.php" class="btn btn-success mt-2">Go to Login →</a>
  </div>
  <?php else: ?>
  <div class="alert alert-warning rounded-3">
    <h5>⚠️ Setup completed with some notes:</h5>
    <p><?= $success ?> statements executed.</p>
    <?php if (in_array($err = implode('', $errors), ['']) || strpos(implode(' ',$errors),'already exists') !== false): ?>
    <p>Tables may already exist. <a href="login.php" class="btn btn-primary btn-sm">Try Login →</a></p>
    <?php else: ?>
    <ul><?php foreach ($errors as $e): ?><li style="font-size:13px;"><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
    <?php endif; ?>
  </div>
  <?php endif; ?>
  <div class="card mt-3">
    <div class="card-body">
      <h6>Next Steps:</h6>
      <ol style="font-size:14px;padding-left:20px;margin:0;">
        <li>Update <code>includes/config.php</code> with your DB credentials</li>
        <li>Configure SMTP settings for email reminders</li>
        <li>Set up a cron job: <code>0 9 * * * php /path/to/api/email_reminder.php</code></li>
        <li><a href="login.php">Login with demo account</a> or <a href="register.php">register</a></li>
      </ol>
    </div>
  </div>
</div>
</body>
</html>
