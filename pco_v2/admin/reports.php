<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_auth('admin');
$user = current_user();

// Date range
$from = clean($_GET['from'] ?? date('Y-m-d', strtotime('-30 days')));
$to   = clean($_GET['to']   ?? date('Y-m-d'));

// Summary stats
$stats = [
    'consultations' => Database::fetchOne("SELECT COUNT(*) c FROM consultations WHERE DATE(created_at) BETWEEN ? AND ?",[$from,$to])['c'],
    'approved'      => Database::fetchOne("SELECT COUNT(*) c FROM consultations WHERE status='approved' AND DATE(created_at) BETWEEN ? AND ?",[$from,$to])['c'],
    'rejected'      => Database::fetchOne("SELECT COUNT(*) c FROM consultations WHERE status='rejected' AND DATE(created_at) BETWEEN ? AND ?",[$from,$to])['c'],
    'prescriptions' => Database::fetchOne("SELECT COUNT(*) c FROM prescriptions WHERE DATE(created_at) BETWEEN ? AND ?",[$from,$to])['c'],
    'orders'        => Database::fetchOne("SELECT COUNT(*) c FROM orders WHERE DATE(created_at) BETWEEN ? AND ?",[$from,$to])['c'],
    'revenue'       => Database::fetchOne("SELECT COALESCE(SUM(total_amount),0) v FROM orders WHERE payment_status='paid' AND DATE(created_at) BETWEEN ? AND ?",[$from,$to])['v'],
    'new_patients'  => Database::fetchOne("SELECT COUNT(*) c FROM users WHERE role='patient' AND DATE(created_at) BETWEEN ? AND ?",[$from,$to])['c'],
];

// Consultations by condition
$byCondition = Database::fetchAll(
    "SELECT cn.name cond_name, COUNT(*) total,
            SUM(CASE WHEN c.status='approved' THEN 1 ELSE 0 END) approved,
            SUM(CASE WHEN c.status='rejected' THEN 1 ELSE 0 END) rejected
     FROM consultations c JOIN conditions cn ON cn.id=c.condition_id
     WHERE DATE(c.created_at) BETWEEN ? AND ?
     GROUP BY cn.id ORDER BY total DESC", [$from,$to]
);

// Daily consultations (last 30 days for chart)
$dailyConsults = Database::fetchAll(
    "SELECT DATE(created_at) d, COUNT(*) c FROM consultations
     WHERE DATE(created_at) BETWEEN ? AND ? GROUP BY DATE(created_at) ORDER BY d", [$from,$to]
);
$dailyOrders = Database::fetchAll(
    "SELECT DATE(created_at) d, SUM(total_amount) v FROM orders
     WHERE DATE(created_at) BETWEEN ? AND ? GROUP BY DATE(created_at) ORDER BY d", [$from,$to]
);

// Fill missing dates
function fill_dates(array $rows, string $from, string $to, string $valueKey): array {
    $map = array_column($rows, $valueKey, 'd');
    $result = [];
    $cur = new DateTime($from);
    $end = new DateTime($to);
    while ($cur <= $end) {
        $d = $cur->format('Y-m-d');
        $result[] = ['d'=>$d, 'v'=>(float)($map[$d]??0)];
        $cur->modify('+1 day');
    }
    return $result;
}
$dailyC = fill_dates($dailyConsults, $from, $to, 'c');
$dailyR = fill_dates($dailyOrders,   $from, $to, 'v');

// Top products
$topProducts = Database::fetchAll(
    "SELECT p.name, p.strength, SUM(oi.quantity) units, SUM(oi.line_total) revenue
     FROM order_items oi JOIN products p ON p.id=oi.product_id
     JOIN orders o ON o.id=oi.order_id
     WHERE DATE(o.created_at) BETWEEN ? AND ?
     GROUP BY p.id ORDER BY units DESC LIMIT 8", [$from,$to]
);

$page_title = 'Reports';
include APP_PATH . '/includes/header.php';
?>
<div class="pco-dash"><div class="grid-container"><div class="grid-x grid-margin-x">
<?php include APP_PATH . '/includes/admin-sidebar.php'; ?>
<div class="cell large-9 medium-8">

  <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;margin-bottom:1.5rem;">
    <h1 style="font-size:1.55rem;margin:0;">Reports</h1>
    <form method="GET" style="display:flex;gap:.4rem;align-items:center;flex-wrap:wrap;">
      <input type="date" name="from" value="<?= e($from) ?>" style="font-size:.845rem;padding:.35rem .6rem;">
      <span style="color:var(--pco-grey-500);font-size:.845rem;">to</span>
      <input type="date" name="to"   value="<?= e($to) ?>"   style="font-size:.845rem;padding:.35rem .6rem;">
      <button type="submit" class="pco-btn pco-btn--primary pco-btn--sm">Apply</button>
      <?php foreach ([7=>'7d',30=>'30d',90=>'90d'] as $days=>$lbl): ?>
      <a href="?from=<?= date('Y-m-d',strtotime("-{$days} days")) ?>&to=<?= date('Y-m-d') ?>"
         class="pco-btn pco-btn--ghost pco-btn--sm"><?= $lbl ?></a>
      <?php endforeach; ?>
    </form>
  </div>

  <!-- Summary stats -->
  <div class="grid-x grid-margin-x" style="margin-bottom:1.5rem;">
    <?php foreach ([
      ['clipboard-list','purple', $stats['consultations'], 'Consultations'],
      ['check-circle',  'green',  $stats['approved'],      'Approved'],
      ['xmark-circle',  'red',    $stats['rejected'],      'Rejected'],
      ['file-prescription','purple',$stats['prescriptions'],'Prescriptions'],
      ['box',           'amber',  $stats['orders'],        'Orders'],
      ['sterling-sign', 'green',  money($stats['revenue']),'Revenue (paid)'],
      ['user-plus',     'black',  $stats['new_patients'],  'New Patients'],
    ] as [$ico,$mod,$val,$lbl]): ?>
    <div class="cell large-3 medium-4 small-6" style="margin-bottom:1rem;">
      <div class="pco-stat">
        <div class="pco-stat__icon pco-stat__icon--<?= $mod ?>"><i class="fa-solid fa-<?= $ico ?>"></i></div>
        <div><div class="pco-stat__val" style="font-size:1.4rem;"><?= $val ?></div><div class="pco-stat__label"><?= $lbl ?></div></div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Charts -->
  <div class="pco-card" style="margin-bottom:1.5rem;">
    <div class="pco-card__head"><h3>Daily Consultations</h3></div>
    <div class="pco-card__body"><canvas id="consultChart" height="80"></canvas></div>
  </div>

  <div class="pco-card" style="margin-bottom:1.5rem;">
    <div class="pco-card__head"><h3>Daily Revenue (£)</h3></div>
    <div class="pco-card__body"><canvas id="revenueChart" height="80"></canvas></div>
  </div>

  <!-- By condition -->
  <div class="pco-card" style="margin-bottom:1.5rem;">
    <div class="pco-card__head"><h3>Consultations by Condition</h3></div>
    <?php if (empty($byCondition)): ?>
    <div class="pco-card__body"><p style="color:var(--pco-grey-500);">No data for this period.</p></div>
    <?php else: ?>
    <div class="pco-table-wrap">
      <table class="pco-table">
        <thead><tr><th>Condition</th><th>Total</th><th>Approved</th><th>Rejected</th><th>Approval Rate</th></tr></thead>
        <tbody>
          <?php foreach ($byCondition as $r):
            $rate = $r['total'] > 0 ? round($r['approved']/$r['total']*100) : 0;
          ?>
          <tr>
            <td><strong><?= e($r['cond_name']) ?></strong></td>
            <td><?= $r['total'] ?></td>
            <td style="color:var(--pco-green);font-weight:600;"><?= $r['approved'] ?></td>
            <td style="color:var(--pco-red);"><?= $r['rejected'] ?></td>
            <td>
              <div style="display:flex;align-items:center;gap:.5rem;">
                <div style="flex:1;height:6px;background:var(--pco-grey-200);border-radius:3px;overflow:hidden;">
                  <div style="width:<?= $rate ?>%;height:100%;background:var(--pco-green);border-radius:3px;"></div>
                </div>
                <span style="font-size:.8rem;font-weight:600;"><?= $rate ?>%</span>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>

  <!-- Top products -->
  <?php if (!empty($topProducts)): ?>
  <div class="pco-card">
    <div class="pco-card__head"><h3>Top Products by Units Ordered</h3></div>
    <div class="pco-table-wrap">
      <table class="pco-table">
        <thead><tr><th>Product</th><th>Units Ordered</th><th>Revenue</th></tr></thead>
        <tbody>
          <?php foreach ($topProducts as $p): ?>
          <tr>
            <td><strong><?= e($p['name']) ?></strong> <span style="color:var(--pco-grey-500);font-size:.82rem;"><?= e($p['strength']) ?></span></td>
            <td><?= $p['units'] ?></td>
            <td style="font-weight:600;"><?= money($p['revenue']) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>

</div></div></div></div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
const labels  = <?= json_encode(array_column($dailyC,'d')) ?>;
const consult = <?= json_encode(array_column($dailyC,'v')) ?>;
const revenue = <?= json_encode(array_column($dailyR,'v')) ?>;

const commonOpts = {
  responsive: true,
  plugins: { legend: { display: false } },
  scales: {
    x: { grid: { display:false }, ticks: { maxTicksLimit:10, font:{size:11} } },
    y: { grid: { color:'rgba(0,0,0,.05)' }, ticks: { font:{size:11} }, beginAtZero:true }
  }
};

new Chart(document.getElementById('consultChart'), {
  type: 'bar',
  data: { labels, datasets: [{ data:consult, backgroundColor:'rgba(123,94,167,.7)', borderRadius:4 }] },
  options: commonOpts
});
new Chart(document.getElementById('revenueChart'), {
  type: 'line',
  data: { labels, datasets: [{ data:revenue, borderColor:'#7B5EA7', backgroundColor:'rgba(196,168,224,.15)', fill:true, tension:.4, pointRadius:3 }] },
  options: commonOpts
});
</script>

<?php include APP_PATH . '/includes/footer.php'; ?>
