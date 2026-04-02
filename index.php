<?php
$pageTitle = 'Dashboard';
require_once 'includes/layout.php';
$stats = getUserStats($currentUser['id']);
$categories = getSpendingByCategory($currentUser['id']);
$trend = getMonthlyTrend($currentUser['id'], 6);
$recent = $db->fetchAll("SELECT t.*,c.name as category_name,c.color,c.icon FROM transactions t LEFT JOIN categories c ON t.category_id=c.id WHERE t.user_id=? ORDER BY t.date DESC,t.id DESC LIMIT 8", [$currentUser['id']]);
updateBudgetSpent($currentUser['id']);
$budgets = $db->fetchAll("SELECT b.*,c.name as cat_name FROM budgets b LEFT JOIN categories c ON b.category_id=c.id WHERE b.user_id=? AND b.end_date >= CURDATE() ORDER BY b.created_at DESC LIMIT 4", [$currentUser['id']]);
$savings = $db->fetchAll("SELECT * FROM savings_goals WHERE user_id=? ORDER BY created_at DESC LIMIT 3", [$currentUser['id']]);
$userCurrency = $currentUser['currency'];
?>

<!-- Currency Converter Banner -->
<div id="converterBanner" style="display:none;background:linear-gradient(135deg,#1e293b,#0f172a);border-radius:14px;padding:16px 24px;margin-bottom:20px;color:white;position:relative;overflow:hidden;">
  <div style="position:absolute;top:-20px;right:-20px;width:120px;height:120px;background:rgba(99,102,241,.2);border-radius:50%;"></div>
  <div style="position:absolute;bottom:-30px;left:100px;width:80px;height:80px;background:rgba(16,185,129,.15);border-radius:50%;"></div>
  <div class="d-flex align-items-center justify-content-between flex-wrap gap-3" style="position:relative;z-index:1;">
    <div class="d-flex align-items-center gap-16">
      <div style="font-size:28px;">💱</div>
      <div style="margin-left:12px;">
        <div style="font-weight:700;font-size:15px;">Live Currency Converter</div>
        <div style="font-size:13px;color:rgba(255,255,255,.6);margin-top:2px;">
          Viewing in <span id="bannerCurrName" style="color:#a5b4fc;font-weight:600;">USD</span>
          &nbsp;·&nbsp; Rate: <span id="bannerRate" style="color:#6ee7b7;font-weight:600;">Loading...</span>
        </div>
      </div>
    </div>
    <div style="display:flex;align-items:center;gap:12px;background:rgba(255,255,255,.08);padding:10px 16px;border-radius:10px;">
      <div style="text-align:center;">
        <div style="font-size:11px;color:rgba(255,255,255,.5);">1 <?= $userCurrency ?></div>
        <div style="font-weight:700;font-size:16px;" id="rateFrom">—</div>
      </div>
      <div style="color:rgba(255,255,255,.4);font-size:18px;">⇄</div>
      <div style="text-align:center;">
        <div style="font-size:11px;color:rgba(255,255,255,.5);" id="rateToCurr">USD</div>
        <div style="font-weight:700;font-size:16px;" id="rateTo">—</div>
      </div>
    </div>
    <div id="rateLastUpdated" style="font-size:11px;color:rgba(255,255,255,.4);"></div>
  </div>
</div>

<!-- Stats Row -->
<div class="row g-3 mb-4">
  <?php
  $statCards = [
    ['label'=>'Total Income',   'rawKey'=>'income',       'type'=>'income',   'icon'=>'trending-up',   'change'=>'+12.5% from last month'],
    ['label'=>'Total Expenses', 'rawKey'=>'expenses',     'type'=>'expense',  'icon'=>'trending-down', 'change'=>'-3.2% from last month'],
    ['label'=>'Net Balance',    'rawKey'=>'balance',      'type'=>'balance',  'icon'=>'wallet',        'change'=>$stats['balance']>=0?'Positive balance':'Negative balance'],
    ['label'=>'Savings Rate',   'rawKey'=>'savings_rate', 'type'=>'savings',  'icon'=>'percent',       'change'=>'Of total income'],
  ];
  foreach ($statCards as $i => $s):
  $rawVal = $stats[$s['rawKey']];
  ?>
  <div class="col-md-6 col-xl-3 fade-in-up fade-in-up-<?= $i+1 ?>">
    <div class="stat-card <?= $s['type'] ?>">
      <div class="stat-icon <?= $s['type'] ?>">
        <i data-lucide="<?= $s['icon'] ?>"></i>
      </div>
      <div class="stat-label"><?= $s['label'] ?></div>
      <?php if ($s['rawKey'] === 'savings_rate'): ?>
        <div class="stat-value" id="statVal_<?= $i ?>"><?= $rawVal ?>%</div>
      <?php else: ?>
        <div class="stat-value" id="statVal_<?= $i ?>"
             data-raw="<?= $rawVal ?>"
             data-currency="<?= $userCurrency ?>">
          <?= $currSymbol . number_format(abs($rawVal), 2) ?>
        </div>
      <?php endif; ?>
      <div class="stat-change <?= str_contains($s['change'],'+') || str_contains($s['change'],'Positive') ? 'positive' : (str_contains($s['change'],'-') || str_contains($s['change'],'Negative') ? 'negative' : '') ?>">
        <?= $s['change'] ?>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Charts Row -->
<div class="row g-3 mb-4">
  <div class="col-lg-8 fade-in-up fade-in-up-1">
    <div class="card">
      <div class="card-header">
        <span class="card-title">Income vs Expenses (6 Months)</span>
        <div class="d-flex gap-2 align-items-center">
          <span id="chartCurrBadge" style="font-size:11px;font-weight:700;padding:3px 8px;border-radius:6px;background:rgba(99,102,241,.1);color:var(--primary);"><?= $userCurrency ?></span>
          <span class="filter-pill active" id="chartToggle" onclick="toggleChartType()">Bar</span>
        </div>
      </div>
      <div class="card-body">
        <div class="chart-wrapper"><canvas id="trendChart"></canvas></div>
      </div>
    </div>
  </div>
  <div class="col-lg-4 fade-in-up fade-in-up-2">
    <div class="card h-100">
      <div class="card-header">
        <span class="card-title">Spending by Category</span>
        <a href="reports.php" style="font-size:13px;color:var(--primary);text-decoration:none;">View all →</a>
      </div>
      <div class="card-body">
        <?php if (array_sum(array_column($categories, 'total')) > 0): ?>
        <div class="donut-wrapper"><canvas id="categoryChart"></canvas></div>
        <?php else: ?>
        <div class="empty-state"><div class="empty-icon">📊</div><div class="empty-title">No data yet</div><div class="empty-desc">Add some expenses to see the chart</div></div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- Recent + Budget Row -->
<div class="row g-3 mb-4">
  <div class="col-lg-7 fade-in-up fade-in-up-1">
    <div class="card">
      <div class="card-header">
        <span class="card-title">Recent Transactions</span>
        <a href="transactions.php" style="font-size:13px;color:var(--primary);text-decoration:none;">View all →</a>
      </div>
      <div class="card-body">
        <?php if (empty($recent)): ?>
        <div class="empty-state">
          <div class="empty-icon">💳</div>
          <div class="empty-title">No transactions yet</div>
          <div class="empty-desc">Start by adding your first transaction</div>
          <button class="btn btn-primary btn-sm mt-3" onclick="openAddTransaction()"><i data-lucide="plus"></i> Add Transaction</button>
        </div>
        <?php else: ?>
        <?php foreach ($recent as $tx):
          $color = $tx['color'] ?? '#6366f1';
          $icon  = $tx['icon']  ?? 'tag';
        ?>
        <div class="transaction-item">
          <div class="transaction-icon" style="background:<?= $color ?>22;color:<?= $color ?>;">
            <i data-lucide="<?= htmlspecialchars($icon) ?>"></i>
          </div>
          <div class="transaction-info">
            <div class="transaction-title"><?= htmlspecialchars($tx['title']) ?></div>
            <div class="transaction-meta"><?= htmlspecialchars($tx['category_name'] ?? 'Uncategorized') ?> · <?= ucfirst($tx['payment_method']) ?></div>
          </div>
          <div>
            <div class="transaction-amount <?= $tx['type'] ?>"
                 data-raw="<?= $tx['amount'] ?>"
                 data-type="<?= $tx['type'] ?>">
              <?= $tx['type'] === 'income' ? '+' : '-' ?><?= $currSymbol . number_format($tx['amount'], 2) ?>
            </div>
            <div class="transaction-date"><?= date('M d', strtotime($tx['date'])) ?></div>
          </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="col-lg-5 fade-in-up fade-in-up-2">
    <div class="card mb-3">
      <div class="card-header">
        <span class="card-title">Budget Overview</span>
        <a href="budgets.php" style="font-size:13px;color:var(--primary);text-decoration:none;">Manage →</a>
      </div>
      <div class="card-body">
        <?php if (empty($budgets)): ?>
        <div class="empty-state" style="padding:24px;">
          <div class="empty-icon">🎯</div>
          <div class="empty-title">No budgets set</div>
          <a href="budgets.php" class="btn btn-primary btn-sm mt-3">Set Budget</a>
        </div>
        <?php else: ?>
        <?php foreach ($budgets as $b):
          $pct   = $b['amount'] > 0 ? round(($b['spent'] / $b['amount']) * 100, 1) : 0;
          $color = $pct >= 90 ? '#ef4444' : ($pct >= 70 ? '#f59e0b' : '#10b981');
        ?>
        <div class="budget-item">
          <div class="budget-header">
            <span class="budget-name"><?= htmlspecialchars($b['name']) ?></span>
            <span class="budget-amounts"
                  data-spent="<?= $b['spent'] ?>"
                  data-amount="<?= $b['amount'] ?>">
              <?= $currSymbol . number_format($b['spent'], 0) ?> / <?= $currSymbol . number_format($b['amount'], 0) ?>
            </span>
          </div>
          <div class="budget-bar">
            <div class="budget-fill" style="width:<?= min($pct,100) ?>%;background:<?= $color ?>;"></div>
          </div>
          <div class="budget-percentage" style="color:<?= $color ?>;"><?= $pct ?>%</div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

    <?php if (!empty($savings)): ?>
    <div class="card">
      <div class="card-header">
        <span class="card-title">Savings Goals</span>
        <a href="savings.php" style="font-size:13px;color:var(--primary);text-decoration:none;">View all →</a>
      </div>
      <div class="card-body">
        <?php foreach ($savings as $goal):
          $pct = $goal['target_amount'] > 0 ? round(($goal['current_amount'] / $goal['target_amount']) * 100, 1) : 0;
        ?>
        <div class="budget-item">
          <div class="budget-header">
            <span class="budget-name"><?= htmlspecialchars($goal['title']) ?></span>
            <span style="font-size:12px;color:var(--mid);"><?= $pct ?>%</span>
          </div>
          <div class="budget-bar">
            <div class="budget-fill" style="width:<?= min($pct,100) ?>%;background:<?= $goal['color'] ?>;"></div>
          </div>
          <div class="budget-amounts"
               data-current="<?= $goal['current_amount'] ?>"
               data-target="<?= $goal['target_amount'] ?>"
               style="font-size:12px;">
            <?= $currSymbol . number_format($goal['current_amount'], 0) ?> of <?= $currSymbol . number_format($goal['target_amount'], 0) ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>

<script>
// ===== RAW DATA =====
const RAW_TREND = <?= json_encode($trend) ?>;
const RAW_CATS  = <?= json_encode(array_values(array_filter($categories, fn($c) => $c['total'] > 0))) ?>;
const USER_CURRENCY = '<?= $userCurrency ?>';
const USER_SYMBOL   = '<?= $currSymbol ?>';

// ===== STATE =====
let isConverted   = false;   // false = native, true = USD
let convRate      = 1;       // native → USD
let convSymbol    = '$';
let convCurrency  = 'USD';
let trendChart, catChart, isBar = true;
let ratesFetched  = false;

// ===== FETCH LIVE RATE =====
async function fetchRate() {
  if (USER_CURRENCY === 'USD') { convRate = 1; convSymbol = '$'; convCurrency = 'USD'; ratesFetched = true; return; }
  try {
    const res  = await fetch(`https://open.er-api.com/v6/latest/${USER_CURRENCY}`);
    const data = await res.json();
    if (data && data.rates && data.rates['USD']) {
      convRate     = data.rates['USD'];
      convSymbol   = '$';
      convCurrency = 'USD';
      ratesFetched = true;
      document.getElementById('bannerRate').textContent = `1 ${USER_CURRENCY} = $${convRate.toFixed(4)} USD`;
      document.getElementById('rateFrom').textContent   = `1.00`;
      document.getElementById('rateTo').textContent     = convRate.toFixed(4);
      document.getElementById('rateToCurr').textContent = 'USD';
      document.getElementById('rateLastUpdated').textContent = 'Live rate · ' + new Date().toLocaleTimeString();
      document.getElementById('liveRateTag').style.display = 'inline';
    }
  } catch(e) {
    // fallback hardcoded rate if API fails
    convRate     = 0.012;
    convSymbol   = '$';
    convCurrency = 'USD';
    ratesFetched = true;
    document.getElementById('bannerRate').textContent = `1 ${USER_CURRENCY} ≈ $${convRate.toFixed(4)} USD (fallback)`;
    document.getElementById('rateFrom').textContent   = `1.00`;
    document.getElementById('rateTo').textContent     = convRate.toFixed(4);
    document.getElementById('rateLastUpdated').textContent = 'Fallback rate (offline)';
  }
}

// ===== TOGGLE CURRENCY =====
async function toggleCurrency() {
  if (!ratesFetched) { await fetchRate(); }
  isConverted = !isConverted;

  const track = document.getElementById('toggleTrack');
  const thumb = document.getElementById('toggleThumb');
  const badge = document.getElementById('chartCurrBadge');
  const banner = document.getElementById('converterBanner');
  const bannerName = document.getElementById('bannerCurrName');

  if (isConverted) {
    track.style.background = '#6366f1';
    thumb.style.left = '21px';
    badge.textContent = convCurrency;
    badge.style.background = 'rgba(16,185,129,.1)';
    badge.style.color = '#10b981';
    banner.style.display = 'block';
    bannerName.textContent = convCurrency;
  } else {
    track.style.background = '#e2e8f0';
    thumb.style.left = '3px';
    badge.textContent = USER_CURRENCY;
    badge.style.background = 'rgba(99,102,241,.1)';
    badge.style.color = 'var(--primary)';
    banner.style.display = 'none';
  }

  updateAllValues();
}

// ===== FORMAT =====
function fmt(val) {
  const v = isConverted ? val * convRate : val;
  const sym = isConverted ? convSymbol : USER_SYMBOL;
  return sym + Math.abs(v).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function fmtShort(val) {
  const v = isConverted ? val * convRate : val;
  const sym = isConverted ? convSymbol : USER_SYMBOL;
  if (Math.abs(v) >= 1000000) return sym + (v/1000000).toFixed(1) + 'M';
  if (Math.abs(v) >= 1000) return sym + (v/1000).toFixed(1) + 'K';
  return sym + v.toFixed(0);
}

// ===== UPDATE ALL VALUES =====
function updateAllValues() {
  // Stat cards
  document.querySelectorAll('[data-raw][data-currency]').forEach(el => {
    const raw = parseFloat(el.dataset.raw);
    el.textContent = fmt(raw);
  });

  // Transaction amounts
  document.querySelectorAll('.transaction-amount[data-raw]').forEach(el => {
    const raw = parseFloat(el.dataset.raw);
    const type = el.dataset.type;
    el.textContent = (type === 'income' ? '+' : '-') + fmt(raw);
  });

  // Budget amounts
  document.querySelectorAll('.budget-amounts[data-spent]').forEach(el => {
    const spent  = parseFloat(el.dataset.spent);
    const amount = parseFloat(el.dataset.amount);
    const sym = isConverted ? convSymbol : USER_SYMBOL;
    const s = isConverted ? spent * convRate : spent;
    const a = isConverted ? amount * convRate : amount;
    el.textContent = sym + Math.round(s).toLocaleString() + ' / ' + sym + Math.round(a).toLocaleString();
  });

  // Savings goal amounts
  document.querySelectorAll('.budget-amounts[data-current]').forEach(el => {
    const cur    = parseFloat(el.dataset.current);
    const target = parseFloat(el.dataset.target);
    const sym = isConverted ? convSymbol : USER_SYMBOL;
    const c = isConverted ? cur * convRate : cur;
    const t = isConverted ? target * convRate : target;
    el.textContent = sym + Math.round(c).toLocaleString() + ' of ' + sym + Math.round(t).toLocaleString();
  });

  // Rebuild charts
  rebuildCharts();
}

// ===== CHARTS =====
function buildChartData() {
  const labels  = RAW_TREND.map(d => d.month);
  const incomes = RAW_TREND.map(d => isConverted ? parseFloat(d.income) * convRate : parseFloat(d.income));
  const expenses= RAW_TREND.map(d => isConverted ? parseFloat(d.expense) * convRate : parseFloat(d.expense));
  return { labels, incomes, expenses };
}

function chartTickCallback(v) { return fmtShort(isConverted ? v : v); }

function rebuildCharts() {
  const sym = isConverted ? convSymbol : USER_SYMBOL;
  const { labels, incomes, expenses } = buildChartData();

  if (trendChart) trendChart.destroy();
  if (isBar) {
    trendChart = new Chart(document.getElementById('trendChart').getContext('2d'), {
      type: 'bar',
      data: { labels, datasets: [
        { label: 'Income',   data: incomes,  backgroundColor: 'rgba(16,185,129,.8)', borderRadius: 6, borderSkipped: false },
        { label: 'Expenses', data: expenses, backgroundColor: 'rgba(239,68,68,.8)',  borderRadius: 6, borderSkipped: false }
      ]},
      options: chartOptions(sym)
    });
  } else {
    trendChart = new Chart(document.getElementById('trendChart').getContext('2d'), {
      type: 'line',
      data: { labels, datasets: [
        { label: 'Income',   data: incomes,  borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,.1)', fill: true, tension: 0.4, pointRadius: 5, borderWidth: 2.5 },
        { label: 'Expenses', data: expenses, borderColor: '#ef4444', backgroundColor: 'rgba(239,68,68,.1)',  fill: true, tension: 0.4, pointRadius: 5, borderWidth: 2.5 }
      ]},
      options: chartOptions(sym)
    });
  }

  // Donut — values change with rate but labels stay
  if (catChart) catChart.destroy();
  if (RAW_CATS.length > 0) {
    const vals = RAW_CATS.map(c => isConverted ? parseFloat(c.total) * convRate : parseFloat(c.total));
    catChart = new Chart(document.getElementById('categoryChart').getContext('2d'), {
      type: 'doughnut',
      data: { labels: RAW_CATS.map(c=>c.name), datasets: [{ data: vals, backgroundColor: RAW_CATS.map(c=>c.color), borderWidth: 2, borderColor: '#fff', hoverOffset: 6 }] },
      options: { responsive: true, maintainAspectRatio: false, cutout: '68%',
        plugins: { legend: { position: 'right', labels: { usePointStyle: true, padding: 12 } },
          tooltip: { backgroundColor: '#0f172a', padding: 12, cornerRadius: 8,
            callbacks: { label: ctx => ` ${ctx.label}: ${sym}${ctx.parsed.toLocaleString('en-US',{minimumFractionDigits:2})}` } } } }
    });
  }
}

function chartOptions(sym) {
  return {
    responsive: true, maintainAspectRatio: false,
    interaction: { mode: 'index', intersect: false },
    plugins: {
      legend: { position: 'top', labels: { usePointStyle: true, padding: 16 } },
      tooltip: { backgroundColor: '#0f172a', padding: 12, cornerRadius: 8,
        callbacks: { label: ctx => ` ${ctx.dataset.label}: ${sym}${ctx.parsed.y.toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2})}` } }
    },
    scales: {
      x: { grid: { display: false }, ticks: { color: '#94a3b8' } },
      y: { grid: { color: '#f1f5f9' }, ticks: { color: '#94a3b8', callback: v => sym + (v >= 1000 ? (v/1000).toFixed(0)+'K' : v) } }
    }
  };
}

function toggleChartType() {
  const btn = document.getElementById('chartToggle');
  isBar = !isBar;
  btn.textContent = isBar ? 'Bar' : 'Line';
  rebuildCharts();
}

// ===== INIT =====
window.addEventListener('load', () => {
  fetchRate();   // fetch in background — toggle activates it
  rebuildCharts();

  // Set initial toggle label
  if (USER_CURRENCY === 'USD') {
    document.getElementById('currLabelLeft').textContent = 'USD';
    document.getElementById('currLabelRight').textContent = 'USD';
    document.getElementById('currencyToggleWrap').style.display = 'none'; // hide if already USD
  } else {
    document.getElementById('currLabelLeft').textContent  = USER_CURRENCY;
    document.getElementById('currLabelRight').textContent = 'USD';
  }
});
</script>

<?php require_once 'includes/layout_footer.php'; ?>
