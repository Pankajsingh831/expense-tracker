<?php
$pageTitle = 'Settings';
require_once 'includes/layout.php';
$symbols = json_decode(CURRENCY_SYMBOLS, true);
?>

<div class="row g-4">
  <!-- Profile Settings -->
  <div class="col-lg-6">
    <div class="card fade-in-up">
      <div class="card-header"><span class="card-title">👤 Profile Settings</span></div>
      <div class="card-body">
        <div class="text-center mb-4">
          <div style="width:80px;height:80px;border-radius:50%;background:linear-gradient(135deg,var(--primary),#8b5cf6);display:flex;align-items:center;justify-content:center;font-size:28px;font-weight:700;color:white;margin:0 auto 12px;">
            <?= strtoupper(substr($currentUser['name'],0,1)) ?>
          </div>
          <div style="font-weight:700;font-size:18px;"><?= htmlspecialchars($currentUser['name']) ?></div>
          <div style="font-size:13px;color:var(--mid);"><?= htmlspecialchars($currentUser['email']) ?></div>
        </div>
        <div class="form-group">
          <label class="form-label">Full Name</label>
          <input type="text" class="form-control" id="settingName" value="<?= htmlspecialchars($currentUser['name']) ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Email Address</label>
          <input type="email" class="form-control" value="<?= htmlspecialchars($currentUser['email']) ?>" disabled style="background:var(--lightest);">
          <div style="font-size:11px;color:var(--mid);margin-top:4px;">Email cannot be changed</div>
        </div>
        <div class="row g-3">
          <div class="col-6">
            <label class="form-label">Currency</label>
            <select class="form-select" id="settingCurrency">
              <?php foreach ($symbols as $code => $sym): ?>
              <option value="<?= $code ?>" <?= $currentUser['currency'] === $code ? 'selected' : '' ?>><?= $code ?> (<?= $sym ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-6">
            <label class="form-label">Monthly Income</label>
            <input type="number" class="form-control" id="settingIncome" value="<?= $currentUser['monthly_income'] ?>" placeholder="0.00">
          </div>
        </div>
        <button class="btn btn-primary w-100 mt-3" onclick="saveSettings()">
          <i data-lucide="save"></i> Save Profile
        </button>
      </div>
    </div>
  </div>

  <!-- Password & Notifications -->
  <div class="col-lg-6">
    <div class="card fade-in-up fade-in-up-2 mb-4">
      <div class="card-header"><span class="card-title">🔒 Change Password</span></div>
      <div class="card-body">
        <div class="form-group">
          <label class="form-label">Current Password</label>
          <input type="password" class="form-control" id="currentPw" placeholder="••••••••">
        </div>
        <div class="form-group">
          <label class="form-label">New Password</label>
          <input type="password" class="form-control" id="newPw" placeholder="Min. 6 characters">
        </div>
        <div class="form-group">
          <label class="form-label">Confirm New Password</label>
          <input type="password" class="form-control" id="confirmPw" placeholder="••••••••">
        </div>
        <button class="btn btn-primary w-100" onclick="changePassword()">
          <i data-lucide="lock"></i> Update Password
        </button>
      </div>
    </div>

    <div class="card fade-in-up fade-in-up-3">
      <div class="card-header"><span class="card-title">📧 Email Reminders</span></div>
      <div class="card-body">
        <div class="d-flex align-items-center justify-content-between mb-3">
          <div>
            <div style="font-weight:600;font-size:14px;">Weekly Report Email</div>
            <div style="font-size:12px;color:var(--mid);">Receive a weekly summary of your finances</div>
          </div>
          <div class="form-check form-switch mb-0">
            <input class="form-check-input" type="checkbox" id="emailReminders" <?= $currentUser['email_reminders'] ? 'checked' : '' ?>>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Send reminder on day</label>
          <select class="form-select" id="reminderDay">
            <?php for ($d=1;$d<=28;$d++): ?>
            <option value="<?= $d ?>" <?= $currentUser['reminder_day'] == $d ? 'selected':'' ?>><?= $d ?><?= $d==1?'st':($d==2?'nd':($d==3?'rd':'th')) ?> of each month</option>
            <?php endfor; ?>
          </select>
        </div>
        <button class="btn btn-primary w-100" onclick="saveSettings()">
          <i data-lucide="bell"></i> Save Preferences
        </button>
      </div>
    </div>
  </div>

  <!-- Data Management -->
  <div class="col-12">
    <div class="card fade-in-up">
      <div class="card-header"><span class="card-title">📊 Data Management</span></div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-4">
            <div style="padding:20px;border:1px solid var(--border);border-radius:12px;text-align:center;">
              <div style="font-size:28px;margin-bottom:8px;">📥</div>
              <div style="font-weight:600;margin-bottom:4px;">Export All Data</div>
              <div style="font-size:12px;color:var(--mid);margin-bottom:12px;">Download all your transactions as CSV</div>
              <a href="api/reports.php?type=export_csv&date_from=2020-01-01&date_to=<?= date('Y-12-31') ?>" class="btn btn-outline btn-sm">
                <i data-lucide="download"></i> Export CSV
              </a>
            </div>
          </div>
          <div class="col-md-4">
            <div style="padding:20px;border:1px solid var(--border);border-radius:12px;text-align:center;">
              <div style="font-size:28px;margin-bottom:8px;">🗓️</div>
              <div style="font-weight:600;margin-bottom:4px;">This Month's Report</div>
              <div style="font-size:12px;color:var(--mid);margin-bottom:12px;">Export current month transactions</div>
              <a href="api/reports.php?type=export_csv&date_from=<?= date('Y-m-01') ?>&date_to=<?= date('Y-m-t') ?>" class="btn btn-outline btn-sm">
                <i data-lucide="file-text"></i> Export Month
              </a>
            </div>
          </div>
          <div class="col-md-4">
            <div style="padding:20px;border:1px dashed #fca5a5;border-radius:12px;text-align:center;background:rgba(239,68,68,.03);">
              <div style="font-size:28px;margin-bottom:8px;">⚠️</div>
              <div style="font-weight:600;margin-bottom:4px;color:var(--danger);">Delete All Data</div>
              <div style="font-size:12px;color:var(--mid);margin-bottom:12px;">Permanently delete all transactions</div>
              <button class="btn btn-sm" style="background:rgba(239,68,68,.1);color:var(--danger);border:none;font-weight:600;" onclick="confirmDeleteAll()">
                <i data-lucide="trash-2"></i> Delete Data
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
async function saveSettings() {
  const payload = {
    action: 'update_settings',
    name: document.getElementById('settingName').value.trim(),
    currency: document.getElementById('settingCurrency').value,
    monthly_income: document.getElementById('settingIncome').value,
    email_reminders: document.getElementById('emailReminders').checked ? 1 : 0,
    reminder_day: document.getElementById('reminderDay').value
  };
  const res = await fetch('api/auth.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload) });
  const data = await res.json();
  if (data.success) showToast('Settings saved! ✅', 'success');
  else showToast(data.message || 'Error saving', 'danger');
}

async function changePassword() {
  const current = document.getElementById('currentPw').value;
  const newPw = document.getElementById('newPw').value;
  const confirm = document.getElementById('confirmPw').value;
  if (!current || !newPw) { showToast('Fill all password fields', 'warning'); return; }
  if (newPw !== confirm) { showToast('New passwords do not match', 'danger'); return; }
  if (newPw.length < 6) { showToast('Password must be at least 6 characters', 'warning'); return; }
  const res = await fetch('api/auth.php', { method:'POST', headers:{'Content-Type':'application/json'},
    body: JSON.stringify({ action:'change_password', current_password: current, new_password: newPw }) });
  const data = await res.json();
  if (data.success) { showToast('Password updated! 🔒', 'success'); document.getElementById('currentPw').value=''; document.getElementById('newPw').value=''; document.getElementById('confirmPw').value=''; }
  else showToast(data.message || 'Error', 'danger');
}

function confirmDeleteAll() {
  const msg = prompt('Type "DELETE" to confirm removing all transactions:');
  if (msg === 'DELETE') {
    fetch('api/transactions.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({action:'delete_all'}) })
      .then(() => { showToast('All transactions deleted', 'success'); });
  }
}
</script>
<?php require_once 'includes/layout_footer.php'; ?>
