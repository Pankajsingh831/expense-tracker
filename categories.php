<?php
$pageTitle = 'Categories';
require_once 'includes/layout.php';
$expenseCats = $db->fetchAll("SELECT c.*, COUNT(t.id) as tx_count, COALESCE(SUM(t.amount),0) as total FROM categories c LEFT JOIN transactions t ON t.category_id=c.id AND MONTH(t.date)=MONTH(CURDATE()) WHERE c.user_id=? AND c.type='expense' GROUP BY c.id ORDER BY c.name", [$currentUser['id']]);
$incomeCats  = $db->fetchAll("SELECT c.*, COUNT(t.id) as tx_count, COALESCE(SUM(t.amount),0) as total FROM categories c LEFT JOIN transactions t ON t.category_id=c.id AND MONTH(t.date)=MONTH(CURDATE()) WHERE c.user_id=? AND c.type='income'  GROUP BY c.id ORDER BY c.name", [$currentUser['id']]);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <div></div>
  <button class="btn btn-primary" onclick="openCatModal('expense')">
    <i data-lucide="plus"></i> New Category
  </button>
</div>

<div class="row g-4">
  <!-- Expense Categories -->
  <div class="col-lg-6">
    <div class="card fade-in-up">
      <div class="card-header">
        <span class="card-title">Expense Categories</span>
        <span style="background:rgba(239,68,68,.1);color:var(--danger);padding:4px 10px;border-radius:6px;font-size:12px;font-weight:600;"><?= count($expenseCats) ?> categories</span>
      </div>
      <div class="card-body p-0">
        <?php if (empty($expenseCats)): ?>
        <div class="empty-state"><div class="empty-icon">🏷️</div><div class="empty-title">No expense categories</div></div>
        <?php else: ?>
        <?php foreach ($expenseCats as $c): ?>
        <div style="display:flex;align-items:center;gap:12px;padding:14px 24px;border-bottom:1px solid var(--border);transition:var(--transition);" onmouseover="this.style.background='var(--lightest)'" onmouseout="this.style.background=''">
          <div style="width:40px;height:40px;border-radius:10px;background:<?= $c['color'] ?>22;color:<?= $c['color'] ?>;display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0;">
            <i data-lucide="<?= htmlspecialchars($c['icon']) ?>"></i>
          </div>
          <div style="flex:1;">
            <div style="font-weight:600;font-size:14px;"><?= htmlspecialchars($c['name']) ?></div>
            <div style="font-size:12px;color:var(--mid);"><?= $c['tx_count'] ?> transactions this month</div>
          </div>
          <div style="text-align:right;margin-right:12px;">
            <div style="font-weight:700;font-family:var(--font-display);color:var(--danger);"><?= $c['total'] > 0 ? $currSymbol.number_format($c['total'],2) : '—' ?></div>
          </div>
          <div style="display:flex;gap:6px;">
            <button class="btn btn-outline btn-sm" onclick="editCat(<?= $c['id'] ?>, '<?= htmlspecialchars($c['name'],ENT_QUOTES) ?>', '<?= $c['icon'] ?>', '<?= $c['color'] ?>')">
              <i data-lucide="pencil" style="width:12px;height:12px;"></i>
            </button>
            <?php if (!$c['is_default']): ?>
            <button class="btn btn-sm" style="background:rgba(239,68,68,.1);color:var(--danger);border:none;" onclick="deleteCat(<?= $c['id'] ?>)">
              <i data-lucide="trash-2" style="width:12px;height:12px;"></i>
            </button>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
        <div style="padding:14px 24px;">
          <button class="btn btn-outline btn-sm w-100" onclick="openCatModal('expense')">
            <i data-lucide="plus"></i> Add Expense Category
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Income Categories -->
  <div class="col-lg-6">
    <div class="card fade-in-up fade-in-up-2">
      <div class="card-header">
        <span class="card-title">Income Categories</span>
        <span style="background:rgba(16,185,129,.1);color:var(--secondary);padding:4px 10px;border-radius:6px;font-size:12px;font-weight:600;"><?= count($incomeCats) ?> categories</span>
      </div>
      <div class="card-body p-0">
        <?php if (empty($incomeCats)): ?>
        <div class="empty-state"><div class="empty-icon">💰</div><div class="empty-title">No income categories</div></div>
        <?php else: ?>
        <?php foreach ($incomeCats as $c): ?>
        <div style="display:flex;align-items:center;gap:12px;padding:14px 24px;border-bottom:1px solid var(--border);transition:var(--transition);" onmouseover="this.style.background='var(--lightest)'" onmouseout="this.style.background=''">
          <div style="width:40px;height:40px;border-radius:10px;background:<?= $c['color'] ?>22;color:<?= $c['color'] ?>;display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0;">
            <i data-lucide="<?= htmlspecialchars($c['icon']) ?>"></i>
          </div>
          <div style="flex:1;">
            <div style="font-weight:600;font-size:14px;"><?= htmlspecialchars($c['name']) ?></div>
            <div style="font-size:12px;color:var(--mid);"><?= $c['tx_count'] ?> transactions this month</div>
          </div>
          <div style="text-align:right;margin-right:12px;">
            <div style="font-weight:700;font-family:var(--font-display);color:var(--secondary);"><?= $c['total'] > 0 ? $currSymbol.number_format($c['total'],2) : '—' ?></div>
          </div>
          <div style="display:flex;gap:6px;">
            <button class="btn btn-outline btn-sm" onclick="editCat(<?= $c['id'] ?>, '<?= htmlspecialchars($c['name'],ENT_QUOTES) ?>', '<?= $c['icon'] ?>', '<?= $c['color'] ?>')">
              <i data-lucide="pencil" style="width:12px;height:12px;"></i>
            </button>
            <?php if (!$c['is_default']): ?>
            <button class="btn btn-sm" style="background:rgba(239,68,68,.1);color:var(--danger);border:none;" onclick="deleteCat(<?= $c['id'] ?>)">
              <i data-lucide="trash-2" style="width:12px;height:12px;"></i>
            </button>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
        <div style="padding:14px 24px;">
          <button class="btn btn-outline btn-sm w-100" onclick="openCatModal('income')">
            <i data-lucide="plus"></i> Add Income Category
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Category Modal -->
<div class="modal fade" id="catModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="catModalTitle">Add Category</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="catId">
        <input type="hidden" id="catType">
        <div class="form-group">
          <label class="form-label">Category Name</label>
          <input type="text" class="form-control" id="catName" placeholder="e.g. Coffee & Cafes">
        </div>
        <div class="row g-3">
          <div class="col-8">
            <label class="form-label">Icon (Lucide icon name)</label>
            <input type="text" class="form-control" id="catIcon" placeholder="e.g. coffee, car, home" oninput="previewIcon()">
            <div style="font-size:11px;color:var(--mid);margin-top:4px;">Browse icons at <a href="https://lucide.dev/icons" target="_blank">lucide.dev/icons</a></div>
          </div>
          <div class="col-4">
            <label class="form-label">Preview</label>
            <div id="iconPreview" style="width:100%;height:40px;border-radius:10px;background:#f1f5f9;display:flex;align-items:center;justify-content:center;font-size:20px;"></div>
          </div>
        </div>
        <div class="form-group mt-3">
          <label class="form-label">Color</label>
          <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:8px;">
            <?php foreach(['#f97316','#3b82f6','#ec4899','#8b5cf6','#ef4444','#06b6d4','#f59e0b','#10b981','#64748b','#6366f1'] as $clr): ?>
            <div onclick="document.getElementById('catColor').value='<?= $clr ?>'" style="width:28px;height:28px;border-radius:6px;background:<?= $clr ?>;cursor:pointer;border:2px solid transparent;" onmouseover="this.style.borderColor='#000'" onmouseout="this.style.borderColor='transparent'"></div>
            <?php endforeach; ?>
            <input type="color" class="form-control form-control-color" id="catColor" value="#6366f1" style="width:28px;height:28px;padding:2px;border-radius:6px;cursor:pointer;">
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary" onclick="saveCat()"><i data-lucide="save"></i> Save</button>
      </div>
    </div>
  </div>
</div>

<script>
let catModal;
document.addEventListener('DOMContentLoaded', () => { catModal = new bootstrap.Modal(document.getElementById('catModal')); });

function openCatModal(type) {
  document.getElementById('catId').value = '';
  document.getElementById('catType').value = type;
  document.getElementById('catName').value = '';
  document.getElementById('catIcon').value = '';
  document.getElementById('iconPreview').innerHTML = '';
  document.getElementById('catModalTitle').textContent = 'Add ' + (type === 'expense' ? 'Expense' : 'Income') + ' Category';
  catModal.show();
}

function editCat(id, name, icon, color) {
  document.getElementById('catId').value = id;
  document.getElementById('catName').value = name;
  document.getElementById('catIcon').value = icon;
  document.getElementById('catColor').value = color;
  document.getElementById('catModalTitle').textContent = 'Edit Category';
  previewIcon();
  catModal.show();
}

function previewIcon() {
  const icon = document.getElementById('catIcon').value;
  const color = document.getElementById('catColor').value;
  const preview = document.getElementById('iconPreview');
  preview.innerHTML = `<i data-lucide="${icon}" style="color:${color};width:20px;height:20px;"></i>`;
  lucide.createIcons();
}

async function saveCat() {
  const id = document.getElementById('catId').value;
  const name = document.getElementById('catName').value.trim();
  const icon = document.getElementById('catIcon').value || 'tag';
  const color = document.getElementById('catColor').value;
  const type = document.getElementById('catType').value || 'expense';
  if (!name) { showToast('Category name required', 'warning'); return; }
  const payload = id ? { action:'update', id, name, icon, color } : { action:'create', name, icon, color, type };
  const res = await fetch('api/categories.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload) });
  const data = await res.json();
  if (data.success) { catModal.hide(); showToast('Category saved!', 'success'); setTimeout(()=>location.reload(),600); }
  else showToast(data.message || 'Error', 'danger');
}

async function deleteCat(id) {
  if (!confirm('Delete this category? Transactions will become uncategorized.')) return;
  const res = await fetch('api/categories.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ action:'delete', id }) });
  const data = await res.json();
  if (data.success) { showToast('Deleted', 'success'); setTimeout(()=>location.reload(),400); }
}
</script>
<?php require_once 'includes/layout_footer.php'; ?>
