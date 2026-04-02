<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

$auth = new Auth();
if ($auth->isLoggedIn()) { header('Location: index.php'); exit; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login – ExpenseFlow</title>
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>💸</text></svg>">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="auth-wrapper">
  <div class="auth-card fade-in-up">
    <div class="auth-logo">
      <div class="logo-icon" style="padding:0;overflow:hidden;">
        <img src="assets/logo.svg" width="56" height="56" style="border-radius:16px;display:block;">
      </div>
      <h1 class="auth-title">Welcome back</h1>
      <p class="auth-subtitle">Sign in to your ExpenseFlow account</p>
    </div>
    <div id="alertBox"></div>
    <div class="form-group">
      <label class="form-label">Email Address</label>
      <input type="email" class="form-control" id="loginEmail" placeholder="you@example.com" autofocus>
    </div>
    <div class="form-group">
      <label class="form-label">Password</label>
      <div style="position:relative;">
        <input type="password" class="form-control" id="loginPassword" placeholder="••••••••" style="padding-right:44px;">
        <button onclick="togglePw('loginPassword',this)" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);border:none;background:none;cursor:pointer;color:#94a3b8;font-size:13px;">Show</button>
      </div>
    </div>
    <button class="btn btn-primary w-100 btn-lg" onclick="doLogin()" id="loginBtn">
      Sign In
    </button>
    <div class="divider">or</div>
    <div class="text-center" style="font-size:14px;color:#64748b;">
      Don't have an account? <a href="register.php" style="color:var(--primary);font-weight:600;text-decoration:none;">Create account</a>
    </div>
    <div class="mt-4 p-3 rounded-3" style="background:#f8fafc;border:1px solid #e2e8f0;">
      <p style="font-size:12px;color:#64748b;margin:0;text-align:center;">
        <strong>Demo:</strong> demo@expenseflow.com / password
      </p>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('loginPassword').addEventListener('keydown', e => { if (e.key === 'Enter') doLogin(); });
document.getElementById('loginEmail').addEventListener('keydown', e => { if (e.key === 'Enter') document.getElementById('loginPassword').focus(); });

function togglePw(id, btn) {
    const inp = document.getElementById(id);
    inp.type = inp.type === 'password' ? 'text' : 'password';
    btn.textContent = inp.type === 'password' ? 'Show' : 'Hide';
}

async function doLogin() {
    const email = document.getElementById('loginEmail').value.trim();
    const password = document.getElementById('loginPassword').value;
    const btn = document.getElementById('loginBtn');
    if (!email || !password) { showAlert('Please fill in all fields', 'danger'); return; }
    btn.innerHTML = '<span class="spinner me-2" style="display:inline-block;width:16px;height:16px;border:2px solid rgba(255,255,255,.3);border-top-color:white;border-radius:50%;animation:spin .6s linear infinite;"></span> Signing in...';
    btn.disabled = true;
    try {
        const res = await fetch('api/auth.php', { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify({ action: 'login', email, password }) });
        const data = await res.json();
        if (data.success) { window.location.href = 'index.php'; }
        else { showAlert(data.message || 'Login failed', 'danger'); btn.innerHTML = 'Sign In'; btn.disabled = false; }
    } catch(e) { showAlert('Connection error', 'danger'); btn.innerHTML = 'Sign In'; btn.disabled = false; }
}

function showAlert(msg, type) {
    document.getElementById('alertBox').innerHTML = `<div class="alert alert-${type} py-2 mb-3" style="font-size:13px;border-radius:10px;">${msg}</div>`;
}
</script>
<style>
@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
</style>
</body>
</html>
