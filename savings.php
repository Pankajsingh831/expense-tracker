<?php
$pageTitle = 'Savings Goals';
require_once 'includes/layout.php';
$goals = $db->fetchAll("SELECT * FROM savings_goals WHERE user_id=? ORDER BY created_at DESC", [$currentUser['id']]);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <div></div>
  <button class="btn btn-primary" onclick="openSavingsModal()">
    <i data-lucide="plus"></i> New Goal
  </button>
</div>

<?php if (empty($goals)): ?>
<div class="card"><div class="card-body">
  <div class="empty-state">
    <div class="empty-icon">🐖</div>
    <div class="empty-title">No savings goals yet</div>
    <div class="empty-desc">Set a goal to start saving towards something you love</div>
    <button class="btn btn-primary mt-3" onclick="openSavingsModal()">Create First Goal</button>
  </div>
</div></div>
<?php else: ?>
<div class="row g-3">
<?php foreach ($goals as $g):
  $pct = $g['target_amount'] > 0 ? round(($g['current_amount'] / $g['target_amount']) * 100, 1) : 0;
  $remaining = $g['target_amount'] - $g['current_amount'];
  $daysLeft = $g['deadline'] ? ceil((strtotime($g['deadline']) - time()) / 86400) : null;
  $monthlyNeeded = ($daysLeft && $daysLeft > 0) ? $remaining / max(1, $daysLeft / 30) : 0;
?>
<div class="col-md-6 col-xl-4 fade-in-up">
  <div class="goal-card" style="border-top:4px solid <?= $g['color'] ?>;">
    <div class="d-flex justify-content-between align-items-start">
      <div class="goal-icon" style="background:<?= $g['color'] ?>22;color:<?= $g['color'] ?>;">
        <i data-lucide="<?= htmlspecialchars($g['icon']) ?>"></i>
      </div>
      <div class="d-flex gap-2">
        <button class="btn btn-sm btn-outline" onclick="addFunds(<?= $g['id'] ?>, '<?= htmlspecialchars($g['title']) ?>')" title="Add Funds">
          <i data-lucide="plus-circle" style="width:12px;height:12px;"></i>
        </button>
        <button class="btn btn-sm" style="background:rgba(239,68,68,.1);color:var(--danger);border:none;" onclick="deleteGoal(<?= $g['id'] ?>)" title="Delete">
          <i data-lucide="trash-2" style="width:12px;height:12px;"></i>
        </button>
      </div>
    </div>
    <div class="goal-title"><?= htmlspecialchars($g['title']) ?></div>
    <?php if ($g['deadline']): ?>
    <div class="goal-deadline">
      🗓️ <?= date('M d, Y', strtotime($g['deadline'])) ?>
      <?php if ($daysLeft !== null): ?>
        &nbsp;·&nbsp;
        <?php if ($daysLeft > 0): ?>
          <span style="color:var(--warning);font-weight:600;"><?= $daysLeft ?> days left</span>
        <?php elseif ($daysLeft == 0): ?>
          <span style="color:var(--danger);font-weight:600;">Due today!</span>
        <?php else: ?>
          <span style="color:var(--danger);font-weight:600;">Overdue</span>
        <?php endif; ?>
      <?php endif; ?>
    </div>
    <?php endif; ?>
    <div class="goal-progress">
      <div class="goal-fill" style="width:<?= min($pct, 100) ?>%;background:<?= $pct >= 100 ? '#10b981' : $g['color'] ?>;"></div>
    </div>
    <div class="goal-amounts">
      <span class="goal-current"><?= $currSymbol . number_format($g['current_amount'], 2) ?></span>
      <span class="goal-target">of <?= $currSymbol . number_format($g['target_amount'], 2) ?></span>
    </div>
    <div class="mt-3 pt-3" style="border-top:1px solid var(--border);">
      <div class="d-flex justify-content-between align-items-center">
        <div style="text-align:center;flex:1;">
          <div style="font-size:20px;font-weight:700;font-family:var(--font-display);color:<?= $pct>=100?'var(--secondary)':'var(--dark)' ?>;"><?= $pct ?>%</div>
          <div style="font-size:11px;color:var(--mid);">Progress</div>
        </div>
        <div style="width:1px;height:36px;background:var(--border);"></div>
        <div style="text-align:center;flex:1;">
          <div style="font-size:16px;font-weight:700;font-family:var(--font-display);color:var(--dark);"><?= $currSymbol . number_format($remaining, 2) ?></div>
          <div style="font-size:11px;color:var(--mid);">Remaining</div>
        </div>
        <?php if ($monthlyNeeded > 0): ?>
        <div style="width:1px;height:36px;background:var(--border);"></div>
        <div style="text-align:center;flex:1;">
          <div style="font-size:16px;font-weight:700;font-family:var(--font-display);color:var(--primary);"><?= $currSymbol . number_format($monthlyNeeded, 0) ?></div>
          <div style="font-size:11px;color:var(--mid);">/month needed</div>
        </div>
        <?php endif; ?>
      </div>
    </div>
    <?php if ($pct >= 100): ?>
    <div class="alert-item success mt-3" style="padding:10px 12px;justify-content:center;">
      <span style="font-size:13px;font-weight:600;">🎉 Goal Achieved!</span>
    </div>
    <?php endif; ?>
  </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Create Goal Modal -->
<div class="modal fade" id="savingsModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Create Savings Goal</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Goal Title</label>
          <input type="text" class="form-control" id="gTitle" placeholder="e.g. Vacation to Japan">
        </div>
        <div class="row g-3">
          <div class="col-6">
            <label class="form-label">Target Amount</label>
            <input type="number" class="form-control" id="gTarget" placeholder="0.00" min="0">
          </div>
          <div class="col-6">
            <label class="form-label">Current Amount</label>
            <input type="number" class="form-control" id="gCurrent" placeholder="0.00" min="0">
          </div>
        </div>
        <div class="form-group mt-3">
          <label class="form-label">Deadline (optional)</label>
          <input type="date" class="form-control" id="gDeadline">
        </div>
        <div class="row g-3">
          <div class="col-6">
            <label class="form-label">Icon</label>
            <select class="form-select" id="gIcon">
              <option value="piggy-bank">🐖 Piggy Bank</option>
              <option value="plane">✈️ Travel</option>
              <option value="home">🏠 House</option>
              <option value="car">🚗 Car</option>
              <option value="laptop">💻 Laptop</option>
              <option value="heart">❤️ Health</option>
              <option value="graduation-cap">🎓 Education</option>
              <option value="shield">🛡️ Emergency</option>
              <option value="gift">🎁 Gift</option>
              <option value="star">⭐ Other</option>
            </select>
          </div>
          <div class="col-6">
            <label class="form-label">Color</label>
            <input type="color" class="form-control form-control-color w-100" id="gColor" value="#10b981">
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary" onclick="saveGoal()"><i data-lucide="save"></i> Create Goal</button>
      </div>
    </div>
  </div>
</div>

<!-- Add Funds Modal -->
<div class="modal fade" id="addFundsModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add Funds</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p style="font-size:13px;color:var(--mid);" id="addFundsTitle"></p>
        <input type="hidden" id="addFundsId">
        <label class="form-label">Amount to Add</label>
        <input type="number" class="form-control" id="addFundsAmount" placeholder="0.00" min="0">
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-success" onclick="submitFunds()"><i data-lucide="plus-circle"></i> Add Funds</button>
      </div>
    </div>
  </div>
</div>

<script>
let savingsModal, addFundsModal;
document.addEventListener('DOMContentLoaded', () => {
  savingsModal = new bootstrap.Modal(document.getElementById('savingsModal'));
  addFundsModal = new bootstrap.Modal(document.getElementById('addFundsModal'));
});

function openSavingsModal() { savingsModal.show(); }

async function saveGoal() {
  const title = document.getElementById('gTitle').value.trim();
  const target = document.getElementById('gTarget').value;
  if (!title || !target) { showToast('Fill required fields', 'warning'); return; }
  const res = await fetch('api/savings.php', { method:'POST', headers:{'Content-Type':'application/json'},
    body: JSON.stringify({ action:'create', title, target_amount: target,
      current_amount: document.getElementById('gCurrent').value || 0,
      deadline: document.getElementById('gDeadline').value || null,
      icon: document.getElementById('gIcon').value,
      color: document.getElementById('gColor').value }) });
  const data = await res.json();
  if (data.success) { savingsModal.hide(); showToast('Goal created! 🎯', 'success'); setTimeout(()=>location.reload(),600); }
  else showToast(data.message || 'Error', 'danger');
}

function addFunds(id, title) {
  document.getElementById('addFundsId').value = id;
  document.getElementById('addFundsTitle').textContent = 'Adding funds to: ' + title;
  document.getElementById('addFundsAmount').value = '';
  addFundsModal.show();
}

async function submitFunds() {
  const id = document.getElementById('addFundsId').value;
  const amount = document.getElementById('addFundsAmount').value;
  if (!amount || amount <= 0) { showToast('Enter a valid amount', 'warning'); return; }
  const res = await fetch('api/savings.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ action:'add_funds', id, amount }) });
  const data = await res.json();
  if (data.success) { addFundsModal.hide(); showToast('Funds added! 💰', 'success'); setTimeout(()=>location.reload(),600); }
}

async function deleteGoal(id) {
  if (!confirm('Delete this savings goal?')) return;
  const res = await fetch('api/savings.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ action:'delete', id }) });
  const data = await res.json();
  if (data.success) { showToast('Deleted', 'success'); setTimeout(()=>location.reload(),400); }
}
</script>
<?php require_once 'includes/layout_footer.php'; ?>
