<?php
$pageTitle = 'Budgets';
require_once 'includes/layout.php';
updateBudgetSpent($currentUser['id']);
$categories = $db->fetchAll("SELECT * FROM categories WHERE user_id=? AND type='expense' ORDER BY name", [$currentUser['id']]);
$budgets = $db->fetchAll("SELECT b.*,c.name as cat_name,c.color as cat_color FROM budgets b LEFT JOIN categories c ON b.category_id=c.id WHERE b.user_id=? ORDER BY b.created_at DESC", [$currentUser['id']]);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <div></div>
  <button class="btn btn-primary" onclick="document.getElementById('budgetModal').classList.add('show');document.getElementById('budgetModal').style.display='block';">
    <i data-lucide="plus"></i> New Budget
  </button>
</div>

<div class="row g-3" id="budgetsContainer">
<?php if (empty($budgets)): ?>
<div class="col-12">
  <div class="empty-state">
    <div class="empty-icon">🎯</div>
    <div class="empty-title">No budgets yet</div>
    <div class="empty-desc">Create a budget to track your spending limits</div>
    <button class="btn btn-primary mt-3" onclick="document.getElementById('budgetModal').classList.add('show');document.getElementById('budgetModal').style.display='block';">
      Create First Budget
    </button>
  </div>
</div>
<?php else: ?>
<?php foreach ($budgets as $b):
  $pct = $b['amount'] > 0 ? round(($b['spent'] / $b['amount']) * 100, 1) : 0;
  $barColor = $pct >= 90 ? '#ef4444' : ($pct >= 70 ? '#f59e0b' : '#10b981');
  $remaining = $b['amount'] - $b['spent'];
?>
<div class="col-md-6 col-xl-4 fade-in-up">
  <div class="card" style="border-top:3px solid <?= $b['color'] ?>;">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
          <div style="font-weight:700;font-size:16px;color:var(--dark);"><?= htmlspecialchars($b['name']) ?></div>
          <div style="font-size:12px;color:var(--mid);margin-top:2px;"><?= htmlspecialchars($b['cat_name']??'All Categories') ?> · <?= ucfirst($b['period']) ?></div>
        </div>
        <button class="btn btn-sm" style="background:rgba(239,68,68,.1);color:var(--danger);border:none;" onclick="deleteBudget(<?= $b['id'] ?>)">
          <i data-lucide="trash-2" style="width:14px;height:14px;"></i>
        </button>
      </div>
      
      <div class="d-flex justify-content-between mb-2">
        <div>
          <div style="font-size:11px;color:var(--mid);">Spent</div>
          <div style="font-weight:700;font-size:20px;color:<?= $barColor ?>;"><?= $currSymbol.number_format($b['spent'],2) ?></div>
        </div>
        <div style="text-align:right;">
          <div style="font-size:11px;color:var(--mid);">Budget</div>
          <div style="font-weight:700;font-size:20px;color:var(--dark);"><?= $currSymbol.number_format($b['amount'],2) ?></div>
        </div>
      </div>
      
      <div class="budget-bar mb-2" style="height:10px;">
        <div class="budget-fill" style="width:<?= min($pct,100) ?>%;background:<?= $barColor ?>;"></div>
      </div>
      
      <div class="d-flex justify-content-between align-items-center">
        <span style="font-size:13px;color:<?= $remaining >= 0 ? 'var(--secondary)' : 'var(--danger)' ?>;font-weight:600;">
          <?= $remaining >= 0 ? $currSymbol.number_format($remaining,2).' left' : $currSymbol.number_format(abs($remaining),2).' over budget' ?>
        </span>
        <span style="font-size:14px;font-weight:700;color:<?= $barColor ?>;"><?= $pct ?>%</span>
      </div>
      
      <?php if ($pct >= $b['alert_threshold']): ?>
      <div class="alert-item warning mt-3" style="padding:10px 12px;">
        <span class="alert-icon">⚠️</span>
        <span style="font-size:12px;">You've used <?= $pct ?>% of this budget</span>
      </div>
      <?php endif; ?>
      
      <div style="font-size:11px;color:var(--mid);margin-top:10px;"><?= date('M d',strtotime($b['start_date'])) ?> – <?= date('M d, Y',strtotime($b['end_date'])) ?></div>
    </div>
  </div>
</div>
<?php endforeach; ?>
<?php endif; ?>
</div>

<!-- Budget Modal -->
<div class="modal fade" id="budgetModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Create Budget</h5>
        <button type="button" class="btn-close" onclick="closeBudgetModal()"></button>
      </div>
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Budget Name</label>
          <input type="text" class="form-control" id="bName" placeholder="e.g. Monthly Food Budget">
        </div>
        <div class="row g-3">
          <div class="col-6">
            <label class="form-label">Budget Amount</label>
            <input type="number" class="form-control" id="bAmount" placeholder="0.00" min="0">
          </div>
          <div class="col-6">
            <label class="form-label">Period</label>
            <select class="form-select" id="bPeriod" onchange="updateDates()">
              <option value="weekly">Weekly</option>
              <option value="monthly" selected>Monthly</option>
              <option value="yearly">Yearly</option>
            </select>
          </div>
        </div>
        <div class="form-group mt-3">
          <label class="form-label">Category (optional)</label>
          <select class="form-select" id="bCategory">
            <option value="">All Expense Categories</option>
            <?php foreach ($categories as $c): ?>
            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="row g-3">
          <div class="col-6">
            <label class="form-label">Start Date</label>
            <input type="date" class="form-control" id="bStart">
          </div>
          <div class="col-6">
            <label class="form-label">End Date</label>
            <input type="date" class="form-control" id="bEnd">
          </div>
        </div>
        <div class="row g-3 mt-1">
          <div class="col-6">
            <label class="form-label">Color</label>
            <input type="color" class="form-control form-control-color w-100" id="bColor" value="#6366f1">
          </div>
          <div class="col-6">
            <label class="form-label">Alert at (%)</label>
            <input type="number" class="form-control" id="bThreshold" value="80" min="1" max="100">
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline" onclick="closeBudgetModal()">Cancel</button>
        <button class="btn btn-primary" onclick="saveBudget()"><i data-lucide="save"></i> Save Budget</button>
      </div>
    </div>
  </div>
</div>
<div class="modal-backdrop fade" id="budgetBackdrop" style="display:none;"></div>

<script>
function updateDates() {
    const period = document.getElementById('bPeriod').value;
    const now = new Date();
    let start, end;
    if (period === 'weekly') {
        const day = now.getDay(), diff = now.getDate() - day + (day === 0 ? -6 : 1);
        start = new Date(now.setDate(diff));
        end = new Date(start); end.setDate(end.getDate() + 6);
    } else if (period === 'monthly') {
        start = new Date(now.getFullYear(), now.getMonth(), 1);
        end = new Date(now.getFullYear(), now.getMonth() + 1, 0);
    } else {
        start = new Date(now.getFullYear(), 0, 1);
        end = new Date(now.getFullYear(), 11, 31);
    }
    document.getElementById('bStart').value = start.toISOString().split('T')[0];
    document.getElementById('bEnd').value = end.toISOString().split('T')[0];
}
updateDates();

function closeBudgetModal() {
    document.getElementById('budgetModal').classList.remove('show');
    document.getElementById('budgetModal').style.display = 'none';
}

async function saveBudget() {
    const name = document.getElementById('bName').value.trim();
    const amount = document.getElementById('bAmount').value;
    if (!name || !amount) { showToast('Please fill required fields', 'warning'); return; }
    const res = await fetch('api/budgets.php', { method: 'POST', headers: {'Content-Type':'application/json'},
        body: JSON.stringify({ action:'create', name, amount, category_id: document.getElementById('bCategory').value,
            period: document.getElementById('bPeriod').value, start_date: document.getElementById('bStart').value,
            end_date: document.getElementById('bEnd').value, color: document.getElementById('bColor').value,
            alert_threshold: document.getElementById('bThreshold').value }) });
    const data = await res.json();
    if (data.success) { showToast('Budget created!', 'success'); setTimeout(() => location.reload(), 600); closeBudgetModal(); }
    else showToast(data.message || 'Error', 'danger');
}

async function deleteBudget(id) {
    if (!confirm('Delete this budget?')) return;
    const res = await fetch('api/budgets.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({action:'delete',id}) });
    const data = await res.json();
    if (data.success) { showToast('Deleted','success'); setTimeout(()=>location.reload(),400); }
}
</script>

<?php require_once 'includes/layout_footer.php'; ?>
