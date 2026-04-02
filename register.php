<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
$auth = new Auth();
if ($auth->isLoggedIn()) { header('Location: index.php'); exit; }
$symbols = json_decode(CURRENCY_SYMBOLS, true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register – ExpenseFlow</title>
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>💸</text></svg>">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="auth-wrapper">
  <div class="auth-card fade-in-up" style="max-width:480px;">
    <div class="auth-logo">
      <div class="logo-icon">💸</div>
      <h1 class="auth-title">Create account</h1>
      <p class="auth-subtitle">Start tracking your finances today</p>
    </div>
    <div id="alertBox"></div>
    <div class="row g-3">
      <div class="col-12">
        <label class="form-label">Full Name</label>
        <input type="text" class="form-control" id="regName" placeholder="John Doe">
      </div>
      <div class="col-12">
        <label class="form-label">Email Address</label>
        <input type="email" class="form-control" id="regEmail" placeholder="you@example.com">
      </div>
      <div class="col-md-7">
        <label class="form-label">Password</label>
        <input type="password" class="form-control" id="regPassword" placeholder="Min. 6 characters">
      </div>
      <div class="col-md-5">
        <label class="form-label">Currency</label>
        <select class="form-select" id="regCurrency">
          <?php foreach ($symbols as $code => $sym): ?>
            <option value="<?= $code ?>" <?= $code === 'USD' ? 'selected' : '' ?>><?= $code ?> (<?= $sym ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <button class="btn btn-primary w-100 btn-lg mt-4" onclick="doRegister()" id="regBtn">Create Account</button>
    <div class="divider">or</div>
    <div class="text-center" style="font-size:14px;color:#64748b;">
      Already have an account? <a href="login.php" style="color:var(--primary);font-weight:600;text-decoration:none;">Sign in</a>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
async function doRegister() {
    const name = document.getElementById('regName').value.trim();
    const email = document.getElementById('regEmail').value.trim();
    const password = document.getElementById('regPassword').value;
    const currency = document.getElementById('regCurrency').value;
    const btn = document.getElementById('regBtn');
    if (!name || !email || !password) { showAlert('Please fill in all fields', 'danger'); return; }
    btn.textContent = 'Creating account...'; btn.disabled = true;
    try {
        const res = await fetch('api/auth.php', { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify({ action: 'register', name, email, password, currency }) });
        const data = await res.json();
        if (data.success) window.location.href = 'index.php';
        else { showAlert(data.message, 'danger'); btn.textContent = 'Create Account'; btn.disabled = false; }
    } catch(e) { showAlert('Connection error', 'danger'); btn.textContent = 'Create Account'; btn.disabled = false; }
}
function showAlert(msg, type) {
    document.getElementById('alertBox').innerHTML = `<div class="alert alert-${type} py-2 mb-3" style="font-size:13px;border-radius:10px;">${msg}</div>`;
}
</script>
</body>
</html>
