<?php
session_start();
require_once '../includes/auth_check.php';
require_once '../includes/db_helpers.php';
requireRole('admin');

$pageTitle  = 'Reports';
$activePage = 'reports';

$year     = (int)($_GET['year'] ?? date('Y'));
$revenue  = getYearlyRevenue($year);
$apts     = getApartments();
$s        = getDashboardStats();
$allPayments = getPayments(['year' => $year]);
$overdue  = getPayments(['status' => 'Overdue', 'year' => $year]);
$tenants  = getTenants();
$movedOut = array_filter($tenants, fn($t) => $t['status']==='moved_out');

$chartMonths    = array_map(fn($r) => substr($r['month'],0,3), $revenue);
$chartCollected = array_map(fn($r) => (float)$r['collected'], $revenue);
$chartPending   = array_map(fn($r) => (float)$r['pending'],   $revenue);
$chartOverdue   = array_map(fn($r) => (float)$r['overdue'],   $revenue);

$totalCollected = array_sum($chartCollected);
$totalPending   = array_sum($chartPending);
$totalOverdue2  = array_sum($chartOverdue);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Reports — Rentisha Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="../assets/css/style.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
  <style>@media print{.sidebar,.topnav,.tabs,.filter-bar,.no-print{display:none!important;}.main-content{margin:0!important;}}</style>
</head>
<body>
<div class="rms-shell">
  <div class="sidebar-overlay" id="sidebarOverlay"></div>
  <?php include '../includes/sidebar_admin.php'; ?>
  <div class="main-content" id="mainContent">
    <?php include '../includes/topnav.php'; ?>
    <div class="page-content">

      <div class="page-header">
        <div><h1>Financial &amp; Property Reports</h1><p>Live data from database — <?= $year ?></p></div>
        <div class="d-flex gap-2 no-print">
          <form method="GET" style="display:flex;gap:8px;">
            <select name="year" class="form-control" style="width:90px;" onchange="this.form.submit()">
              <?php for ($y=(int)date('Y'); $y>=(int)date('Y')-3; $y--): ?>
              <option value="<?= $y ?>" <?= $y===$year?'selected':'' ?>><?= $y ?></option>
              <?php endfor; ?>
            </select>
          </form>
          <button class="btn btn-outline btn-sm" onclick="window.print()"><i class="bi bi-printer"></i> Print</button>
        </div>
      
      <div class="stats-grid" style="grid-template-columns:repeat(auto-fill,minmax(160px,1fr));margin-bottom:20px;">
        <div class="stat-card green">
          <div class="stat-icon"><i class="bi bi-cash-stack"></i></div>
          <div class="stat-value" style="font-size:1rem;"><?= formatKES($totalCollected) ?></div>
          <div class="stat-label">Collected <?= $year ?></div>
        </div>
        <div class="stat-card orange">
          <div class="stat-icon"><i class="bi bi-clock-history"></i></div>
          <div class="stat-value" style="font-size:1rem;"><?= formatKES($totalPending) ?></div>
          <div class="stat-label">Pending <?= $year ?></div>
        </div>
        <div class="stat-card red">
          <div class="stat-icon"><i class="bi bi-exclamation-triangle"></i></div>
          <div class="stat-value" style="font-size:1rem;"><?= formatKES($totalOverdue2) ?></div>
          <div class="stat-label">Overdue <?= $year ?></div>
        </div>
        <div class="stat-card blue">
          <div class="stat-icon"><i class="bi bi-building"></i></div>
          <div class="stat-value" data-count="<?= $s['total_apartments'] ?>"><?= $s['total_apartments'] ?></div>
          <div class="stat-label">Apartments</div>
        </div>
        <div class="stat-card green">
          <div class="stat-icon"><i class="bi bi-check-circle"></i></div>
          <div class="stat-value" data-count="<?= $s['occupied_units'] ?>"><?= $s['occupied_units'] ?></div>
          <div class="stat-label">Occupied Units</div>
        </div>
        <div class="stat-card">
          <div class="stat-icon"><i class="bi bi-bar-chart-line"></i></div>
          <div class="stat-value"><?= $s['occupancy_rate'] ?>%</div>
          <div class="stat-label">Occupancy Rate</div>
        </div>
      </div>

      
      <div class="tabs no-print" role="tablist">
        <div class="tab-item active" data-tab="rpt-financial" role="tab"><i class="bi bi-graph-up-arrow"></i> Financial</div>
        <div class="tab-item" data-tab="rpt-occupancy"  role="tab"><i class="bi bi-building"></i> Occupancy</div>
        <div class="tab-item" data-tab="rpt-outstanding" role="tab">
          <i class="bi bi-exclamation-circle"></i> Outstanding
          <?php if (count($overdue)): ?>
          <span style="background:var(--danger);color:#fff;font-size:.62rem;padding:1px 6px;border-radius:10px;"><?= count($overdue) ?></span>
          <?php endif; ?>
        </div>
        <div class="tab-item" data-tab="rpt-tenants" role="tab"><i class="bi bi-people"></i> Tenants</div>
      </div>

      
      <div id="rpt-financial" class="tab-panel active">
        <div class="card mb-4">
          <div class="card-header"><h3 class="card-title"><i class="bi bi-bar-chart-line"></i> Monthly Revenue — <?= $year ?></h3></div>
          <div class="card-body">
            <div class="chart-container" style="height:260px;"><canvas id="revChart"></canvas></div>
          </div>
        </div>
        <div class="card">
          <div class="card-header"><h3 class="card-title"><i class="bi bi-building"></i> Revenue by Apartment — <?= date('F Y') ?></h3></div>
          <div class="card-body p-0">
            <div class="table-wrapper">
              <table class="rms-table">
                <thead><tr><th>Apartment</th><th>Location</th><th>Units</th><th>Occupied</th><th>Caretaker</th><th>Occupancy</th></tr></thead>
                <tbody>
                  <?php foreach ($apts as $apt):
                    $occ = $apt['total_units'] > 0 ? round($apt['occupied']/$apt['total_units']*100) : 0;
                  ?>
                  <tr>
                    <td><a href="apartments.php?view=<?= $apt['id'] ?>" style="font-weight:700;color:var(--primary);"><?= htmlspecialchars($apt['name']) ?></a></td>
                    <td><i class="bi bi-geo-alt text-primary"></i> <?= htmlspecialchars($apt['location']) ?></td>
                    <td><?= $apt['total_units'] ?></td>
                    <td><span style="color:#7ED321;font-weight:700;"><?= $apt['occupied'] ?></span> / <?= $apt['total_units'] ?></td>
                    <td><?= htmlspecialchars($apt['caretaker_name']) ?></td>
                    <td>
                      <div style="display:flex;align-items:center;gap:8px;">
                        <div class="progress" style="width:80px;">
                          <div class="progress-bar <?= $occ>=80?'success':($occ>=50?'warning':'danger') ?>" style="width:<?= $occ ?>%"></div>
                        </div>
                        <span style="font-size:.75rem;font-weight:700;"><?= $occ ?>%</span>
                      </div>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                  <?php if (empty($apts)): ?>
                  <tr><td colspan="6" style="text-align:center;padding:24px;color:var(--text-muted);">No apartments yet.</td></tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      
      <div id="rpt-occupancy" class="tab-panel">
        <div class="grid-2 mb-4">
          <div class="card">
            <div class="card-header"><h3 class="card-title"><i class="bi bi-pie-chart"></i> Overall Occupancy</h3></div>
            <div class="card-body" style="display:flex;align-items:center;gap:22px;flex-wrap:wrap;">
              <div style="width:180px;height:180px;flex-shrink:0;"><canvas id="occChart"></canvas></div>
              <div style="flex:1;min-width:130px;">
                <div class="d-flex justify-between fs-sm mb-1"><span>Occupied (<?= $s['occupied_units'] ?>)</span><span class="fw-bold" style="color:#7ED321;"><?= $s['occupancy_rate'] ?>%</span></div>
                <div class="progress mb-3"><div class="progress-bar success" style="width:<?= $s['occupancy_rate'] ?>%"></div></div>
                <div class="d-flex justify-between fs-sm mb-1"><span>Vacant (<?= $s['vacant_units'] ?>)</span><span class="fw-bold" style="color:#f59e0b;"><?= 100-$s['occupancy_rate'] ?>%</span></div>
                <div class="progress"><div class="progress-bar warning" style="width:<?= 100-$s['occupancy_rate'] ?>%"></div></div>
                <div style="margin-top:14px;font-size:.82rem;color:var(--text-muted);">Total: <strong><?= $s['total_units'] ?></strong> units across <strong><?= $s['total_apartments'] ?></strong> apartments</div>
              </div>
            </div>
          </div>
          <div class="card">
            <div class="card-header"><h3 class="card-title"><i class="bi bi-people"></i> Tenant Summary</h3></div>
            <div class="card-body">
              <?php foreach ([
                ['Active Tenants',     $s['active_tenants'],     'badge-active'],
                ['Prospective',        $s['prospective_tenants'],'badge-prospective'],
                ['Former (Moved Out)', $s['moved_out_tenants'],  'badge-inactive'],
                ['Active Caretakers',  $s['active_caretakers'],  'badge-info'],
              ] as [$label,$count,$cls]): ?>
              <div style="display:flex;justify-content:space-between;align-items:center;padding:9px 0;border-bottom:1px solid var(--border);">
                <span style="font-size:.875rem;"><?= $label ?></span>
                <span class="badge <?= $cls ?>"><?= $count ?></span>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>

      
      <div id="rpt-outstanding" class="tab-panel">
        <?php if (empty($overdue)): ?>
        <div style="text-align:center;padding:40px;color:var(--text-muted);">
          <i class="bi bi-check2-all" style="font-size:2.5rem;display:block;margin-bottom:8px;color:#7ED321;"></i>
          No overdue payments for <?= $year ?>!
        </div>
        <?php else: ?>
        <div class="alert alert-danger no-print">
          <i class="bi bi-exclamation-triangle-fill"></i>
          <div><strong><?= count($overdue) ?> overdue payment(s)</strong> totalling <strong><?= formatKES(array_sum(array_column($overdue,'amount'))) ?></strong>.</div>
        </div>
        <div class="card">
          <div class="card-body p-0">
            <div class="table-wrapper">
              <table class="rms-table">
                <thead><tr><th>Tenant</th><th>Unit</th><th>Apartment</th><th>Period</th><th>Amount</th><th>Action</th></tr></thead>
                <tbody>
                  <?php foreach ($overdue as $p): ?>
                  <tr>
                    <td class="fw-bold"><?= htmlspecialchars($p['tenant_name']) ?></td>
                    <td><span class="badge badge-info"><?= htmlspecialchars($p['unit_number']??'—') ?></span></td>
                    <td><?= htmlspecialchars($p['apartment_name']??'—') ?></td>
                    <td><?= $p['month'].' '.$p['year'] ?></td>
                    <td class="fw-bold text-danger"><?= formatKES($p['amount']) ?></td>
                    <td>
                      <form method="POST" action="../includes/save_payment.php" style="display:inline;">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(rmsCsrfToken()) ?>">
                        <input type="hidden" name="action"     value="confirm">
                        <input type="hidden" name="payment_id" value="<?= $p['id'] ?>">
                        <button type="submit" class="btn btn-success btn-sm"
                          onclick="return confirm('Mark as Paid?')">
                          <i class="bi bi-check-circle"></i> Confirm
                        </button>
                      </form>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
        <?php endif; ?>
      </div>

      
      <div id="rpt-tenants" class="tab-panel">
        <div class="card">
          <div class="card-header">
            <h3 class="card-title"><i class="bi bi-people"></i> All Tenants</h3>
            <span style="font-size:.8rem;color:var(--text-muted);"><?= count($tenants) ?> total</span>
          </div>
          <div class="card-body p-0">
            <div class="table-wrapper">
              <table class="rms-table">
                <thead><tr><th>Tenant</th><th>Unit</th><th>Apartment</th><th>Move-in</th><th>Move-out</th><th>Status</th></tr></thead>
                <tbody>
                  <?php foreach ($tenants as $t): ?>
                  <tr>
                    <td>
                      <a href="tenants.php?view=<?= $t['id'] ?>" style="font-weight:600;color:var(--primary);"><?= htmlspecialchars($t['full_name']) ?></a>
                      <div style="font-size:.72rem;color:var(--text-muted);"><?= htmlspecialchars($t['phone']??'') ?></div>
                    </td>
                    <td><?= $t['unit_number'] ? '<span class="badge badge-info">'.htmlspecialchars($t['unit_number']).'</span>' : '—' ?></td>
                    <td><?= htmlspecialchars($t['apartment_name']??'—') ?></td>
                    <td><?= formatDate($t['move_in_date']) ?></td>
                    <td><?= formatDate($t['move_out_date'] ?? null) ?></td>
                    <td><span class="badge <?= statusBadgeClass($t['status']) ?>"><?= ucfirst($t['status']) ?></span></td>
                  </tr>
                  <?php endforeach; ?>
                  <?php if (empty($tenants)): ?>
                  <tr><td colspan="6" style="text-align:center;padding:24px;color:var(--text-muted);">No tenants yet.</td></tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

<script src="../assets/js/main.js"></script>
<script>
new Chart(document.getElementById('revChart').getContext('2d'),{
  type:'bar',
  data:{
    labels:<?= json_encode($chartMonths) ?>,
    datasets:[
      {label:'Collected',data:<?= json_encode($chartCollected) ?>,backgroundColor:'rgba(126,211,33,.8)',borderRadius:5},
      {label:'Pending',  data:<?= json_encode($chartPending) ?>,  backgroundColor:'rgba(245,158,11,.75)',borderRadius:5},
      {label:'Overdue',  data:<?= json_encode($chartOverdue) ?>,  backgroundColor:'rgba(239,68,68,.75)', borderRadius:5}
    ]
  },
  options:{responsive:true,maintainAspectRatio:false,
    plugins:{legend:{position:'top'}},
    scales:{y:{beginAtZero:true,ticks:{callback:v=>'KES '+(v>=1000?(v/1000)+'k':v)}},x:{grid:{display:false}}}}
});
new Chart(document.getElementById('occChart').getContext('2d'),{
  type:'doughnut',
  data:{labels:['Occupied','Vacant','Reserved'],
    datasets:[{data:[<?= $s['occupied_units'] ?>,<?= $s['vacant_units'] ?>,<?= $s['reserved_units'] ?>],
      backgroundColor:['#7ED321','#f59e0b','#007BFF'],borderWidth:0,cutout:'72%'}]},
  options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'bottom'}}}
});
</script>
</body>
</html>
