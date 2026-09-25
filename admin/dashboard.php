<?php
session_start();
require_once '../includes/auth_check.php';
require_once '../includes/db_helpers.php';
requireRole('admin');

$pageTitle  = 'Dashboard';
$activePage = 'dashboard';


$s        = getDashboardStats();
$revenue  = getYearlyRevenue((int)date('Y'));
$apts     = getApartments();
$recent_p = getPayments(['year' => (int)date('Y'), 'month' => date('F')]);
$recent_m = getMaintenanceRequests();
$announcements = getAllAnnouncements();


$chartMonths    = array_column($revenue, 'month');
$chartCollected = array_column($revenue, 'collected');
$chartPending   = array_column($revenue, 'pending');
$chartOverdue   = array_column($revenue, 'overdue');


$notifCount = getUnreadNotificationCount($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard — Rentisha Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="../assets/css/style.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
</head>
<body>
<div class="rms-shell">

  <div class="sidebar-overlay" id="sidebarOverlay"></div>
  <?php include '../includes/sidebar_admin.php'; ?>

  <div class="main-content" id="mainContent">
    <?php include '../includes/topnav.php'; ?>

    <div class="page-content">

      
      <div class="page-header">
        <div>
          <h1>Dashboard Overview</h1>
          <p>Welcome back, <strong><?= htmlspecialchars(explode(' ', $_SESSION['name'])[0]) ?></strong>!
             Here's what's happening across your properties today —
             <strong><?= date('l, j F Y') ?></strong>.
          </p>
        </div>
        <div class="d-flex gap-2">
          <a href="apartments.php?action=add" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg"></i> Add Apartment
          </a>
        </div>
      </div>

      <?php if (isset($_GET['success'])): ?>
      <div class="alert alert-success mb-4">
        <i class="bi bi-check-circle-fill"></i>
        <div><?= htmlspecialchars($_GET['success']) ?></div>
      </div>
      <?php endif; ?>

     
      <div class="stats-grid">

        
        <div class="stat-card blue">
          <div class="stat-header">
            <div class="stat-icon"><i class="bi bi-building"></i></div>
          </div>
          <div class="stat-value" data-count="<?= $s['total_apartments'] ?>"><?= $s['total_apartments'] ?></div>
          <div class="stat-label">Total Apartments</div>
          <div class="stat-sub"><i class="bi bi-geo-alt"></i> Active properties</div>
        </div>

        
        <div class="stat-card">
          <div class="stat-header">
            <div class="stat-icon"><i class="bi bi-door-open"></i></div>
          </div>
          <div class="stat-value" data-count="<?= $s['total_units'] ?>"><?= $s['total_units'] ?></div>
          <div class="stat-label">Total Units</div>
          <div class="stat-sub">
            <i class="bi bi-pie-chart"></i>
            <?= $s['occupancy_rate'] ?>% occupancy rate
          </div>
        </div>

        
        <div class="stat-card green">
          <div class="stat-header">
            <div class="stat-icon"><i class="bi bi-check-circle"></i></div>
          </div>
          <div class="stat-value" data-count="<?= $s['occupied_units'] ?>"><?= $s['occupied_units'] ?></div>
          <div class="stat-label">Occupied Units</div>
          <div class="stat-sub"><i class="bi bi-people"></i> <?= $s['active_tenants'] ?> active tenants</div>
        </div>

        
        <div class="stat-card orange">
          <div class="stat-header">
            <div class="stat-icon"><i class="bi bi-door-closed"></i></div>
          </div>
          <div class="stat-value" data-count="<?= $s['vacant_units'] ?>"><?= $s['vacant_units'] ?></div>
          <div class="stat-label">Vacant Units</div>
          <div class="stat-sub"><i class="bi bi-info-circle"></i> Available to let</div>
        </div>

        
        <div class="stat-card green">
          <div class="stat-header">
            <div class="stat-icon"><i class="bi bi-cash-stack"></i></div>
          </div>
          <div class="stat-value" style="font-size:1.3rem;" data-count="<?= $s['rent_collected'] ?>">
            <?= formatKES($s['rent_collected']) ?>
          </div>
          <div class="stat-label">Rent Collected</div>
          <div class="stat-sub"><i class="bi bi-calendar-month"></i> <?= date('F Y') ?></div>
        </div>

      
        <div class="stat-card orange">
          <div class="stat-header">
            <div class="stat-icon"><i class="bi bi-clock-history"></i></div>
          </div>
          <div class="stat-value" style="font-size:1.3rem;" data-count="<?= $s['rent_pending'] ?>">
            <?= formatKES($s['rent_pending']) ?>
          </div>
          <div class="stat-label">Pending Rent</div>
          <div class="stat-sub"><i class="bi bi-exclamation-circle"></i> Awaiting payment</div>
        </div>

        
        <div class="stat-card red">
          <div class="stat-header">
            <div class="stat-icon"><i class="bi bi-exclamation-triangle"></i></div>
          </div>
          <div class="stat-value" style="font-size:1.3rem;" data-count="<?= $s['rent_overdue'] ?>">
            <?= formatKES($s['rent_overdue']) ?>
          </div>
          <div class="stat-label">Overdue Rent</div>
          <div class="stat-sub"><i class="bi bi-clock"></i> Requires follow-up</div>
        </div>

  
        <a href="tenants.php" class="stat-card blue" style="text-decoration:none;color:inherit;">
          <div class="stat-header">
            <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
          </div>
          <div class="stat-value" data-count="<?= $s['active_tenants'] ?>"><?= $s['active_tenants'] ?></div>
          <div class="stat-label">Active Tenants</div>
          <div class="stat-sub">
            <i class="bi bi-person-plus"></i>
            <?= $s['prospective_tenants'] ?> prospective
          </div>
        </a>

      
        <a href="tenants.php?status=pending_approval" class="stat-card orange" style="text-decoration:none;color:inherit;">
         <div class="stat-header">
           <div class="stat-icon"><i class="bi bi-person-exclamation"></i></div>
         </div>
         <div class="stat-value" data-count="<?= $s['pending_tenant_applications'] ?>"><?= $s['pending_tenant_applications'] ?></div>
         <div class="stat-label">Pending Applications</div>
         <div class="stat-sub"><i class="bi bi-hourglass-split"></i> Awaiting review</div>
        </a>

        
        <div class="stat-card">
          <div class="stat-header">
            <div class="stat-icon"><i class="bi bi-box-arrow-right"></i></div>
          </div>
          <div class="stat-value" data-count="<?= $s['moved_out_tenants'] ?>"><?= $s['moved_out_tenants'] ?></div>
          <div class="stat-label">Former Tenants</div>
          <div class="stat-sub"><i class="bi bi-archive"></i> History retained</div>
        </div>

        
        <div class="stat-card teal">
          <div class="stat-header">
            <div class="stat-icon"><i class="bi bi-person-badge"></i></div>
          </div>
          <div class="stat-value" data-count="<?= $s['active_caretakers'] ?>"><?= $s['active_caretakers'] ?></div>
          <div class="stat-label">Active Caretakers</div>
          <div class="stat-sub"><i class="bi bi-building-check"></i> Managing properties</div>
        </div>

        
        <div class="stat-card red">
          <div class="stat-header">
            <div class="stat-icon"><i class="bi bi-wrench"></i></div>
            <?php if ($s['maintenance_pending'] > 0): ?>
            <span class="stat-change down"><?= $s['maintenance_pending'] ?> open</span>
            <?php endif; ?>
          </div>
          <div class="stat-value" data-count="<?= $s['maintenance_pending'] ?>"><?= $s['maintenance_pending'] ?></div>
          <div class="stat-label">Pending Maintenance</div>
          <div class="stat-sub">
            <i class="bi bi-play-circle"></i>
            <?= $s['maintenance_inprogress'] ?> in progress
          </div>
        </div>

      
        <div class="stat-card green">
          <div class="stat-header">
            <div class="stat-icon"><i class="bi bi-check2-all"></i></div>
          </div>
          <div class="stat-value" data-count="<?= $s['maintenance_resolved'] ?>"><?= $s['maintenance_resolved'] ?></div>
          <div class="stat-label">Resolved Issues</div>
          <div class="stat-sub"><i class="bi bi-calendar-check"></i> All time</div>
        </div>

      </div>

      
      <div class="grid-2 mb-6">

        
        <div class="card">
          <div class="card-header">
            <h3 class="card-title"><i class="bi bi-bar-chart-line"></i> Monthly Revenue — <?= date('Y') ?></h3>
            <div class="card-actions">
              <span class="badge badge-active" style="font-size:.7rem;">Live Data</span>
            </div>
          </div>
          <div class="card-body">
            <div class="chart-container" style="height:240px;">
              <canvas id="revenueChart"></canvas>
            </div>
            <?php
              $totalCollected = array_sum($chartCollected);
              $totalPending   = array_sum($chartPending);
              $totalOverdue   = array_sum($chartOverdue);
            ?>
            <div class="d-flex gap-3 mt-3" style="flex-wrap:wrap;">
              <div style="flex:1;min-width:110px;padding:10px;background:var(--bg);border-radius:8px;text-align:center;">
                <div style="font-size:1rem;font-weight:800;color:#7ED321;"><?= formatKES($totalCollected) ?></div>
                <div style="font-size:.72rem;color:var(--text-muted);">Total Collected YTD</div>
              </div>
              <div style="flex:1;min-width:110px;padding:10px;background:var(--bg);border-radius:8px;text-align:center;">
                <div style="font-size:1rem;font-weight:800;color:#f59e0b;"><?= formatKES($totalPending) ?></div>
                <div style="font-size:.72rem;color:var(--text-muted);">Pending YTD</div>
              </div>
              <div style="flex:1;min-width:110px;padding:10px;background:var(--bg);border-radius:8px;text-align:center;">
                <div style="font-size:1rem;font-weight:800;color:#ef4444;"><?= formatKES($totalOverdue) ?></div>
                <div style="font-size:.72rem;color:var(--text-muted);">Overdue YTD</div>
              </div>
            </div>
          </div>
        </div>

        
        <div class="card">
          <div class="card-header">
            <h3 class="card-title"><i class="bi bi-pie-chart"></i> Occupancy Rate</h3>
            <span class="badge badge-paid" style="font-size:.7rem;"><?= $s['occupancy_rate'] ?>%</span>
          </div>
          <div class="card-body">
            <div style="display:flex;align-items:center;gap:24px;flex-wrap:wrap;">
              <div style="width:190px;height:190px;flex-shrink:0;">
                <canvas id="occupancyChart"></canvas>
              </div>
              <div style="flex:1;min-width:130px;">
                
                <div class="d-flex justify-between fs-sm mb-1">
                  <span>Occupied (<?= $s['occupied_units'] ?>)</span>
                  <span class="fw-bold" style="color:#7ED321;"><?= $s['total_units'] > 0 ? round($s['occupied_units']/$s['total_units']*100,1) : 0 ?>%</span>
                </div>
                <div class="progress mb-3">
                  <div class="progress-bar success" style="width:<?= $s['total_units'] > 0 ? round($s['occupied_units']/$s['total_units']*100) : 0 ?>%"></div>
                </div>
                
                <div class="d-flex justify-between fs-sm mb-1">
                  <span>Vacant (<?= $s['vacant_units'] ?>)</span>
                  <span class="fw-bold" style="color:#f59e0b;"><?= $s['total_units'] > 0 ? round($s['vacant_units']/$s['total_units']*100,1) : 0 ?>%</span>
                </div>
                <div class="progress mb-3">
                  <div class="progress-bar warning" style="width:<?= $s['total_units'] > 0 ? round($s['vacant_units']/$s['total_units']*100) : 0 ?>%"></div>
                </div>
                
                <?php
                  $totalExpected = $s['rent_collected'] + $s['rent_pending'] + $s['rent_overdue'];
                  $collectionPct = $totalExpected > 0 ? round($s['rent_collected']/$totalExpected*100, 1) : 0;
                ?>
                <div class="d-flex justify-between fs-sm mb-1">
                  <span>Rent Collected</span>
                  <span class="fw-bold" style="color:#007BFF;"><?= $collectionPct ?>%</span>
                </div>
                <div class="progress">
                  <div class="progress-bar" style="width:<?= $collectionPct ?>%"></div>
                </div>
              </div>
            </div>

            <!
            <?php if ($s['total_units'] === 0): ?>
            <div style="text-align:center;padding:20px;color:var(--text-muted);font-size:.85rem;margin-top:10px;">
              <i class="bi bi-building" style="font-size:1.8rem;display:block;margin-bottom:6px;"></i>
              No apartments added yet. <a href="apartments.php?action=add">Add your first apartment</a>.
            </div>
            <?php endif; ?>
          </div>
        </div>

      </div>

      
      <div class="grid-2 mb-6">


        <div class="card">
          <div class="card-header">
            <h3 class="card-title"><i class="bi bi-building"></i> Apartments</h3>
            <div class="card-actions">
              <a href="apartments.php" class="btn btn-secondary btn-sm">View All</a>
              <a href="apartments.php?action=add" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg"></i> Add
              </a>
            </div>
          </div>
          <div class="card-body p-0">
            <?php if (empty($apts)): ?>
            <div style="text-align:center;padding:30px;color:var(--text-muted);">
              <i class="bi bi-building" style="font-size:2rem;display:block;margin-bottom:8px;"></i>
              No apartments yet. <a href="apartments.php?action=add">Add one now</a>.
            </div>
            <?php else: ?>
            <div class="table-wrapper">
              <table class="rms-table">
                <thead>
                  <tr><th>Apartment</th><th>Units</th><th>Occupied</th><th>Caretaker</th><th>Status</th></tr>
                </thead>
                <tbody>
                  <?php foreach (array_slice($apts, 0, 6) as $apt): ?>
                  <tr>
                    <td>
                      <a href="apartments.php?view=<?= $apt['id'] ?>" style="font-weight:600;color:var(--primary);">
                        <?= htmlspecialchars($apt['name']) ?>
                      </a>
                      <div style="font-size:.72rem;color:var(--text-muted);">
                        <i class="bi bi-geo-alt"></i>
                        <?= htmlspecialchars($apt['location']) ?>
                      </div>
                    </td>
                    <td><?= $apt['total_units'] ?></td>
                    <td>
                      <span style="color:#7ED321;font-weight:700;"><?= $apt['occupied'] ?></span>
                      <span style="color:var(--text-muted);font-size:.75rem;"> / <?= $apt['total_units'] ?></span>
                    </td>
                    <td><?= htmlspecialchars($apt['caretaker_name']) ?></td>
                    <td>
                      <span class="badge <?= $apt['status'] === 'Active' ? 'badge-active' : 'badge-inactive' ?>">
                        <span class="dot"></span><?= $apt['status'] ?>
                      </span>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <?php endif; ?>
          </div>
        </div>

        
        <div class="card">
          <div class="card-header">
            <h3 class="card-title"><i class="bi bi-receipt"></i> Recent Payments — <?= date('F Y') ?></h3>
            <a href="payments.php" class="btn btn-secondary btn-sm">View All</a>
          </div>
          <div class="card-body p-0">
            <?php if (empty($recent_p)): ?>
            <div style="text-align:center;padding:30px;color:var(--text-muted);">
              <i class="bi bi-receipt" style="font-size:2rem;display:block;margin-bottom:8px;"></i>
              No payments recorded for <?= date('F Y') ?> yet.
              <br><a href="payments.php?action=record">Record a payment</a>.
            </div>
            <?php else: ?>
            <div class="table-wrapper">
              <table class="rms-table">
                <thead>
                  <tr><th>Tenant</th><th>Unit</th><th>Amount</th><th>Method</th><th>Status</th></tr>
                </thead>
                <tbody>
                  <?php foreach (array_slice($recent_p, 0, 6) as $p): ?>
                  <tr>
                    <td class="fw-bold"><?= htmlspecialchars($p['tenant_name']) ?></td>
                    <td><span class="badge badge-info"><?= htmlspecialchars($p['unit_number'] ?? '—') ?></span></td>
                    <td class="fw-bold"><?= formatKES($p['amount']) ?></td>
                    <td style="font-size:.78rem;"><?= htmlspecialchars($p['payment_method']) ?></td>
                    <td>
                      <span class="badge <?= statusBadgeClass($p['status']) ?>">
                        <?= htmlspecialchars($p['status']) ?>
                      </span>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <?php endif; ?>
          </div>
        </div>

      </div>

      
      <div class="grid-2">

        
        <div class="card">
          <div class="card-header">
            <h3 class="card-title"><i class="bi bi-wrench"></i> Maintenance Requests</h3>
            <a href="maintenance.php" class="btn btn-secondary btn-sm">View All</a>
          </div>
          <div class="card-body">
            <?php if (empty($recent_m)): ?>
            <div style="text-align:center;padding:20px;color:var(--text-muted);">
              <i class="bi bi-check2-all" style="font-size:1.8rem;display:block;margin-bottom:6px;color:#7ED321;"></i>
              No maintenance requests. All clear!
            </div>
            <?php else: ?>
              <?php foreach (array_slice($recent_m, 0, 4) as $m): ?>
              <div class="maintenance-card">
                <div class="mc-header">
                  <span class="mc-title"><?= htmlspecialchars($m['title']) ?></span>
                  <span class="badge <?= statusBadgeClass($m['status']) ?>"><?= $m['status'] ?></span>
                </div>
                <div class="mc-desc"><?= htmlspecialchars(mb_substr($m['description'], 0, 100)) ?>…</div>
                <div class="mc-meta">
                  <span><i class="bi bi-person"></i> <?= htmlspecialchars($m['tenant_name']) ?></span>
                  <span><i class="bi bi-door-open"></i> <?= htmlspecialchars($m['unit_number'] ?? '—') ?></span>
                  <span><i class="bi bi-building"></i> <?= htmlspecialchars($m['apartment_name'] ?? '—') ?></span>
                  <span><i class="bi bi-calendar3"></i> <?= formatDate($m['created_at']) ?></span>
                </div>
              </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>

      
        <div class="card">
          <div class="card-header">
            <h3 class="card-title"><i class="bi bi-megaphone"></i> Announcements</h3>
            <div class="card-actions">
              <a href="announcements.php" class="btn btn-secondary btn-sm">View All</a>
              <button class="btn btn-primary btn-sm" data-modal-open="addAnnouncementModal">
                <i class="bi bi-plus-lg"></i> Post
              </button>
            </div>
          </div>
          <div class="card-body">
            <?php if (empty($announcements)): ?>
            <div style="text-align:center;padding:20px;color:var(--text-muted);">
              <i class="bi bi-megaphone" style="font-size:1.8rem;display:block;margin-bottom:6px;"></i>
              No announcements posted yet.
            </div>
            <?php else: ?>
              <?php foreach (array_slice($announcements, 0, 3) as $ann): ?>
              <div class="announcement-card <?= $ann['type'] === 'Urgent' ? 'urgent' : ($ann['type'] === 'Information' ? 'info' : '') ?>">
                <div class="ann-title"><?= htmlspecialchars($ann['title']) ?></div>
                <div class="ann-date">
                  <?= formatDate($ann['created_at']) ?> · <?= htmlspecialchars($ann['author_name']) ?>
                </div>
                <div class="ann-body"><?= htmlspecialchars(mb_substr($ann['body'], 0, 120)) ?>…</div>
              </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>

      </div>

    </div>
  </div>


<div class="modal-overlay" id="addAnnouncementModal">
  <div class="modal modal-lg">
    <div class="modal-header">
      <h3><i class="bi bi-megaphone-fill"></i> Post Announcement</h3>
      <button class="modal-close" data-modal-close><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="modal-body">
      <form action="../includes/save_announcement.php" method="POST" data-validate>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(rmsCsrfToken()) ?>">
        <div class="form-group">
          <label class="form-label">Title <span class="required">*</span></label>
          <input type="text" name="title" class="form-control" placeholder="Announcement title" required>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Type</label>
            <select name="type" class="form-control">
              <option value="General">General</option>
              <option value="Urgent">Urgent</option>
              <option value="Information">Information</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Target Audience <span class="required">*</span></label>
            <select name="target" id="targetSelect" class="form-control" required>
              <option value="all_tenants">All Tenants</option>
              <option value="all_caretakers">All Caretakers</option>
              <option value="specific_apartment">Specific Apartment</option>
            </select>
          </div>
        </div>
        <div class="form-group" id="apartmentSelectWrap" style="display:none;">
          <label class="form-label">Select Apartment</label>
          <select name="target_id" class="form-control">
            <option value="">— Select —</option>
            <?php foreach ($apts as $apt): ?>
            <option value="<?= $apt['id'] ?>"><?= htmlspecialchars($apt['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Message <span class="required">*</span></label>
          <textarea name="body" class="form-control" rows="4" placeholder="Write your announcement…" required></textarea>
        </div>
        <div class="form-group">
          <label class="form-label">Expires On (optional)</label>
          <input type="date" name="expires_at" class="form-control" min="<?= date('Y-m-d') ?>">
        </div>
        <div class="modal-footer" style="padding:0;margin-top:8px;">
          <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
          <button type="submit" class="btn btn-primary"><i class="bi bi-send"></i> Post Announcement</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="../assets/js/main.js"></script>
<script>

const revenueCtx = document.getElementById('revenueChart').getContext('2d');
new Chart(revenueCtx, {
  type: 'bar',
  data: {
    labels: <?= json_encode(array_map(fn($m) => substr($m,0,3), $chartMonths)) ?>,
    datasets: [
      {
        label: 'Collected (KES)',
        data: <?= json_encode(array_map('floatval', $chartCollected)) ?>,
        backgroundColor: 'rgba(126,211,33,.8)',
        borderRadius: 5,
        borderSkipped: false,
      },
      {
        label: 'Pending (KES)',
        data: <?= json_encode(array_map('floatval', $chartPending)) ?>,
        backgroundColor: 'rgba(245,158,11,.75)',
        borderRadius: 5,
        borderSkipped: false,
      },
      {
        label: 'Overdue (KES)',
        data: <?= json_encode(array_map('floatval', $chartOverdue)) ?>,
        backgroundColor: 'rgba(239,68,68,.75)',
        borderRadius: 5,
        borderSkipped: false,
      }
    ]
  },
  options: {
    responsive: true, maintainAspectRatio: false,
    plugins: { legend: { position: 'top', labels: { font: { size: 11 } } } },
    scales: {
      y: { beginAtZero: true, ticks: { callback: v => 'KES ' + (v >= 1000 ? (v/1000)+'k' : v) } },
      x: { grid: { display: false } }
    }
  }
});


const occCtx = document.getElementById('occupancyChart').getContext('2d');
new Chart(occCtx, {
  type: 'doughnut',
  data: {
    labels: ['Occupied', 'Vacant', 'Reserved'],
    datasets: [{
      data: [
        <?= $s['occupied_units'] ?>,
        <?= $s['vacant_units'] ?>,
        <?= $s['reserved_units'] ?>
      ],
      backgroundColor: ['#7ED321', '#f59e0b', '#007BFF'],
      borderWidth: 0,
      cutout: '72%'
    }]
  },
  options: {
    responsive: true, maintainAspectRatio: false,
    plugins: {
      legend: { position: 'bottom', labels: { font: { size: 11 } } },
      tooltip: { callbacks: { label: ctx => ctx.label + ': ' + ctx.raw + ' units' } }
    }
  }
});

document.getElementById('targetSelect')?.addEventListener('change', function() {
  document.getElementById('apartmentSelectWrap').style.display =
    this.value === 'specific_apartment' ? 'block' : 'none';
});
</script>
</body>
</html>
