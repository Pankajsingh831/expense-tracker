<?php
$pageTitle = 'Transactions';
require_once 'includes/layout.php';
$categories = $db->fetchAll("SELECT * FROM categories WHERE user_id=? ORDER BY type,name", [$currentUser['id']]);
?>

<div class="card fade-in-up">
  <div class="card-header flex-wrap gap-3">
    <span class="card-title">All Transactions</span>
    <div class="d-flex gap-2 flex-wrap">
      <div class="search-box" style="max-width:220px;">
        <i data-lucide="search"></i>
        <input type="text" id="searchInput" placeholder="Search..." oninput="filterTransactions()">
      </div>
      <select class="form-select form-select-sm" id="typeFilter" style="width:120px;" onchange="filterTransactions()">
        <option value="">All Types</option>
        <option value="expense">Expense</option>
        <option value="income">Income</option>
      </select>
      <select class="form-select form-select-sm" id="catFilter" style="width:150px;" onchange="filterTransactions()">
        <option value="">All Categories</option>
        <?php foreach ($categories as $c): ?>
        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
        <?php endforeach; ?>
      </select>
      <input type="month" class="form-control form-control-sm" id="monthFilter" style="width:150px;" value="<?= date('Y-m') ?>" onchange="filterTransactions()">
      <a href="api/reports.php?type=export_csv&date_from=<?= date('Y-01-01') ?>&date_to=<?= date('Y-12-31') ?>" class="btn btn-outline btn-sm">
        <i data-lucide="download"></i> Export CSV
      </a>
    </div>
  </div>

  <!-- Summary pills -->
  <div style="padding:12px 24px;background:var(--lightest);border-bottom:1px solid var(--border);display:flex;gap:20px;flex-wrap:wrap;">
    <span style="font-size:13px;" id="summaryTotal">Total: 0 transactions</span>
    <span style="font-size:13px;color:var(--secondary);" id="summaryIncome">Income: <?= $currSymbol ?>0</span>
    <span style="font-size:13px;color:var(--danger);" id="summaryExpense">Expenses: <?= $currSymbol ?>0</span>
  </div>

  <div id="txTableWrapper">
    <table class="table-custom w-100">
      <thead>
        <tr>
          <th>Transaction</th>
          <th>Category</th>
          <th>Date</th>
          <th>Payment</th>
          <th style="text-align:right;">Amount</th>
          <th style="text-align:center;">Actions</th>
        </tr>
      </thead>
      <tbody id="txBody">
        <tr><td colspan="6" style="text-align:center;padding:40px;color:var(--mid);"><span class="spinner" style="display:inline-block;margin-right:8px;"></span> Loading...</td></tr>
      </tbody>
    </table>
  </div>

  <div style="padding:16px 24px;display:flex;justify-content:space-between;align-items:center;border-top:1px solid var(--border);">
    <span style="font-size:13px;color:var(--mid);" id="paginationInfo"></span>
    <div class="d-flex gap-2" id="paginationBtns"></div>
  </div>
</div>

<script>
const CURR = '<?= $currSymbol ?>';
let currentPage = 1, totalPages = 1;

const payIcons = { cash:'💵', card:'💳', bank:'🏦', upi:'📱', crypto:'₿' };

async function filterTransactions(page=1) {
    currentPage = page;
    const search = document.getElementById('searchInput').value;
    const type = document.getElementById('typeFilter').value;
    const cat = document.getElementById('catFilter').value;
    const month = document.getElementById('monthFilter').value;
    const [year, mo] = month.split('-');
    const dateFrom = `${year}-${mo}-01`;
    const dateTo = new Date(year, mo, 0).toISOString().split('T')[0];

    const params = new URLSearchParams({ action:'list', page, limit:15, search, type, category:cat, date_from:dateFrom, date_to:dateTo });
    const res = await fetch(`api/transactions.php?${params}`);
    const data = await res.json();

    totalPages = data.pages || 1;
    renderTable(data.transactions || []);
    renderPagination(data.total || 0, currentPage, totalPages);
    updateSummary(data.transactions || []);
}

function renderTable(txs) {
    const tbody = document.getElementById('txBody');
    if (!txs.length) { tbody.innerHTML = '<tr><td colspan="6"><div class="empty-state"><div class="empty-icon">🔍</div><div class="empty-title">No transactions found</div></div></td></tr>'; return; }
    tbody.innerHTML = txs.map(t => `
        <tr>
            <td>
                <div style="display:flex;align-items:center;gap:10px;">
                    <div style="width:36px;height:36px;border-radius:10px;background:${t.color||'#6366f1'}22;color:${t.color||'#6366f1'};display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:14px;">
                        <i data-lucide="${t.icon||'tag'}"></i>
                    </div>
                    <div>
                        <div style="font-weight:600;font-size:14px;">${t.title}</div>
                        ${t.tags ? `<div style="font-size:11px;color:var(--mid);">#${t.tags}</div>` : ''}
                    </div>
                </div>
            </td>
            <td>
                <span style="background:${t.color||'#6366f1'}22;color:${t.color||'#6366f1'};padding:3px 8px;border-radius:6px;font-size:12px;font-weight:600;">
                    ${t.category_name||'Uncategorized'}
                </span>
            </td>
            <td style="font-size:13px;color:var(--mid);">${new Date(t.date).toLocaleDateString('en-US',{month:'short',day:'numeric',year:'numeric'})}</td>
            <td style="font-size:13px;">${payIcons[t.payment_method]||'💳'} ${(t.payment_method||'cash').charAt(0).toUpperCase()+(t.payment_method||'cash').slice(1)}</td>
            <td style="text-align:right;font-family:var(--font-display);font-weight:700;color:${t.type==='income'?'#10b981':'#ef4444'};">
                ${t.type==='income'?'+':'-'}${CURR}${parseFloat(t.amount).toFixed(2)}
            </td>
            <td style="text-align:center;">
                <div style="display:flex;justify-content:center;gap:6px;">
                    <button class="btn btn-outline btn-sm" onclick="editTransaction(${t.id})" title="Edit"><i data-lucide="pencil" style="width:12px;height:12px;"></i></button>
                    <button class="btn btn-sm" style="background:rgba(239,68,68,.1);color:var(--danger);border:none;" onclick="deleteTransaction(${t.id})" title="Delete"><i data-lucide="trash-2" style="width:12px;height:12px;"></i></button>
                </div>
            </td>
        </tr>`).join('');
    lucide.createIcons();
}

function updateSummary(txs) {
    const income = txs.filter(t=>t.type==='income').reduce((s,t)=>s+parseFloat(t.amount),0);
    const expense = txs.filter(t=>t.type==='expense').reduce((s,t)=>s+parseFloat(t.amount),0);
    document.getElementById('summaryTotal').textContent = `${txs.length} transactions`;
    document.getElementById('summaryIncome').textContent = `Income: ${CURR}${income.toFixed(2)}`;
    document.getElementById('summaryExpense').textContent = `Expenses: ${CURR}${expense.toFixed(2)}`;
}

function renderPagination(total, page, pages) {
    document.getElementById('paginationInfo').textContent = `Showing page ${page} of ${pages} (${total} total)`;
    const cont = document.getElementById('paginationBtns');
    cont.innerHTML = '';
    if (pages <= 1) return;
    const prev = document.createElement('button');
    prev.className = 'btn btn-outline btn-sm'; prev.textContent = '← Prev';
    prev.disabled = page <= 1; prev.onclick = () => filterTransactions(page-1);
    const next = document.createElement('button');
    next.className = 'btn btn-outline btn-sm'; next.textContent = 'Next →';
    next.disabled = page >= pages; next.onclick = () => filterTransactions(page+1);
    cont.append(prev, next);
}

filterTransactions();
</script>

<?php require_once 'includes/layout_footer.php'; ?>
