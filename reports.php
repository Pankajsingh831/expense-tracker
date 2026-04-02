<?php
$pageTitle = 'Reports';
require_once 'includes/layout.php';
?>

<!-- Report Header -->
<div class="report-header mb-4 fade-in-up">
  <div class="row align-items-center">
    <div class="col-md-8">
      <h2 style="font-family:var(--font-display);font-weight:700;margin:0;">Financial Reports</h2>
      <p style="color:rgba(255,255,255,.7);margin-top:6px;font-size:14px;">Detailed insights into your financial health</p>
    </div>
    <div class="col-md-4 text-md-end mt-3 mt-md-0">
      <div class="d-flex gap-2 justify-content-md-end flex-wrap">
        <select class="form-select form-select-sm" id="reportMonth" style="width:110px;background:rgba(255,255,255,.15);border-color:rgba(255,255,255,.2);color:white;">
          <?php for($i=1;$i<=12;$i++): ?>
          <option value="<?= $i ?>" <?= $i==date('m')?'selected':'' ?>><?= date('F',mktime(0,0,0,$i,1)) ?></option>
          <?php endfor; ?>
        </select>
        <select class="form-select form-select-sm" id="reportYear" style="width:90px;background:rgba(255,255,255,.15);border-color:rgba(255,255,255,.2);color:white;">
          <?php for($y=date('Y');$y>=date('Y')-3;$y--): ?>
          <option value="<?= $y ?>"><?= $y ?></option>
          <?php endfor; ?>
        </select>
        <a id="exportBtn" href="#" class="btn btn-sm" style="background:white;color:var(--dark);font-weight:600;" onclick="exportCSV()">
          <i data-lucide="download"></i> Export CSV
        </a>
      </div>
    </div>
  </div>
</div>

<!-- Stats Row -->
<div class="row g-3 mb-4 fade-in-up" id="reportStats">
  <div class="col-12 text-center py-4"><span class="spinner"></span> Loading...</div>
</div>

<!-- Charts Row 1 -->
<div class="row g-3 mb-4 fade-in-up">
  <div class="col-lg-6">
    <div class="card">
      <div class="card-header"><span class="card-title">Spending by Category</span></div>
      <div class="card-body"><div style="height:300px;"><canvas id="catDonut"></canvas></div></div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="card">
      <div class="card-header"><span class="card-title">Daily Spending</span></div>
      <div class="card-body"><div style="height:300px;"><canvas id="dailyBar"></canvas></div></div>
    </div>
  </div>
</div>

<!-- Charts Row 2 -->
<div class="row g-3 mb-4 fade-in-up">
  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <span class="card-title">6-Month Trend</span>
        <select class="form-select form-select-sm" id="trendMonths" style="width:120px;" onchange="loadTrend()">
          <option value="6">6 Months</option>
          <option value="12">12 Months</option>
        </select>
      </div>
      <div class="card-body"><div class="chart-wrapper"><canvas id="trendLine"></canvas></div></div>
    </div>
  </div>
</div>

<!-- Category Breakdown Table -->
<div class="card fade-in-up">
  <div class="card-header"><span class="card-title">Category Breakdown</span></div>
  <div class="card-body p-0">
    <table class="table-custom">
      <thead>
        <tr>
          <th>Category</th>
          <th style="text-align:right;">Amount Spent</th>
          <th style="text-align:right;">% of Total</th>
          <th>Distribution</th>
        </tr>
      </thead>
      <tbody id="catTable"><tr><td colspan="4" style="text-align:center;padding:24px;"><span class="spinner"></span></td></tr></tbody>
    </table>
  </div>
</div>

<script>
const CURR = '<?= $currSymbol ?>';
let catChart, dailyChart, trendChart;

async function loadReport() {
  const month = document.getElementById('reportMonth').value;
  const year = document.getElementById('reportYear').value;
  const res = await fetch(`api/reports.php?type=monthly&month=${month}&year=${year}`);
  const data = await res.json();
  if (!data.success) return;

  // Stats
  const s = data.stats;
  document.getElementById('reportStats').innerHTML = `
    <div class="col-md-3">
      <div class="stat-card income"><div class="stat-icon income"><i data-lucide="trending-up"></i></div>
        <div class="stat-label">Total Income</div><div class="stat-value">${CURR}${parseFloat(s.income).toFixed(2)}</div></div>
    </div>
    <div class="col-md-3">
      <div class="stat-card expense"><div class="stat-icon expense"><i data-lucide="trending-down"></i></div>
        <div class="stat-label">Total Expenses</div><div class="stat-value">${CURR}${parseFloat(s.expenses).toFixed(2)}</div></div>
    </div>
    <div class="col-md-3">
      <div class="stat-card balance"><div class="stat-icon balance"><i data-lucide="wallet"></i></div>
        <div class="stat-label">Net Balance</div><div class="stat-value" style="color:${parseFloat(s.balance)>=0?'var(--secondary)':'var(--danger)'}">${parseFloat(s.balance)>=0?'+':''}${CURR}${Math.abs(s.balance).toFixed(2)}</div></div>
    </div>
    <div class="col-md-3">
      <div class="stat-card savings"><div class="stat-icon savings"><i data-lucide="percent"></i></div>
        <div class="stat-label">Savings Rate</div><div class="stat-value">${s.savings_rate}%</div></div>
    </div>`;
  lucide.createIcons();

  // Category donut
  const cats = data.category_spending.filter(c => parseFloat(c.total) > 0);
  if (catChart) catChart.destroy();
  if (cats.length > 0) {
    catChart = createDonutChart('catDonut', cats.map(c=>c.name), cats.map(c=>parseFloat(c.total)), cats.map(c=>c.color));
  }

  // Daily spending bar
  const daily = data.daily || [];
  if (dailyChart) dailyChart.destroy();
  dailyChart = createBarChart('dailyBar',
    daily.map(d => new Date(d.day).getDate()),
    [{ label:'Daily Spending', data: daily.map(d=>parseFloat(d.total)), backgroundColor:'rgba(99,102,241,.7)' }]
  );

  // Category table
  const totalExp = cats.reduce((s,c)=>s+parseFloat(c.total),0);
  document.getElementById('catTable').innerHTML = cats.length ? cats.map(c => {
    const pct = totalExp > 0 ? (parseFloat(c.total)/totalExp*100).toFixed(1) : 0;
    return `<tr>
      <td><div style="display:flex;align-items:center;gap:10px;">
        <div style="width:32px;height:32px;border-radius:8px;background:${c.color}22;color:${c.color};display:flex;align-items:center;justify-content:center;">
          <i data-lucide="${c.icon||'tag'}" style="width:14px;height:14px;"></i></div>
        <span style="font-weight:600;">${c.name}</span></div></td>
      <td style="text-align:right;font-weight:700;font-family:var(--font-display);">${CURR}${parseFloat(c.total).toFixed(2)}</td>
      <td style="text-align:right;color:var(--mid);">${pct}%</td>
      <td style="padding-right:24px;">
        <div style="height:6px;background:var(--lightest);border-radius:999px;overflow:hidden;">
          <div style="width:${pct}%;height:100%;background:${c.color};border-radius:999px;"></div></div></td>
    </tr>`;
  }).join('') : '<tr><td colspan="4" style="text-align:center;padding:24px;color:var(--mid);">No expense data for this period</td></tr>';
  lucide.createIcons();
}

async function loadTrend() {
  const months = document.getElementById('trendMonths').value;
  const res = await fetch(`api/reports.php?type=trend&months=${months}`);
  const data = await res.json();
  if (!data.success) return;
  const trend = data.trend;
  if (trendChart) trendChart.destroy();
  trendChart = createLineChart('trendLine', trend.map(d=>d.month), [
    { label:'Income', data: trend.map(d=>parseFloat(d.income)), borderColor:'#10b981', backgroundColor:'rgba(16,185,129,.1)' },
    { label:'Expenses', data: trend.map(d=>parseFloat(d.expense)), borderColor:'#ef4444', backgroundColor:'rgba(239,68,68,.1)' }
  ]);
}

function exportCSV() {
  const month = document.getElementById('reportMonth').value.toString().padStart(2,'0');
  const year = document.getElementById('reportYear').value;
  const days = new Date(year, month, 0).getDate();
  window.location.href = `api/reports.php?type=export_csv&date_from=${year}-${month}-01&date_to=${year}-${month}-${days}`;
}

document.getElementById('reportMonth').addEventListener('change', loadReport);
document.getElementById('reportYear').addEventListener('change', loadReport);

loadReport();
loadTrend();
</script>

<?php require_once 'includes/layout_footer.php'; ?>
