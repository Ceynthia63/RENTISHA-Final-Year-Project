<?php
session_start();
require_once '../includes/auth_check.php';
require_once '../includes/db_helpers.php';
requireRole('tenant');

$pageTitle  = 'My Dashboard';
$activePage = 'dashboard';

$tenantId = (int)$_SESSION['user_id'];
$data     = getTenantDashboardData($tenantId);

$curMonth = date('F');
$curYear  = (int)date('Y');
$curPay   = $data['current_month_payment'];
$outstanding = $data['outstanding'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>My Dashboard — Rentisha</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="../assets/css/style.css">
  <style>
    .rent-hero {
      background: linear-gradient(135deg, #001A3D, #007BFF);
      border-radius: var(--radius-xl);
      padding: 26px 28px;
      color: #fff;
      position: relative;
      overflow: hidden;
      margin-bottom: 24px;
    }
    .rent-hero::before {
      content:'';position:absolute;width:220px;height:220px;
      background:rgba(255,255,255,.07);border-radius:50%;
      top:-70px;right:-50px;
    }
    .rent-hero::after {
      content:'';position:absolute;width:140px;height:140px;
      background:rgba(255,255,255,.04);border-radius:50%;
      bottom:-40px;right:100px;
    }
    .rent-hero .hero-label { font-size:.75rem;opacity:.7;letter-spacing:.8px;text-transform:uppercase; }
    .rent-hero .hero-amount { font-size:2.2rem;font-weight:900;margin:4px 0; }
    .rent-hero .hero-sub    { font-size:.82rem;opacity:.75; }
    .status-pill {
      display:inline-flex;align-items:center;gap:6px;
      background:rgba(255,255,255,.15);padding:4px 12px;
      border-radius:20px;font-size:.78rem;font-weight:700;margin-top:12px;
    }
    .quick-grid {
      display:grid;grid-template-columns:repeat(auto-fill,minmax(120px,1fr));
      gap:12px;margin-bottom:24px;
    }
    .quick-btn {
      background:var(--bg-card);border:1.5px solid var(--border);
      border-radius:var(--radius-lg);padding:16px 10px;text-align:center;
      cursor:pointer;transition:var(--transition);text-decoration:none;
      display:flex;flex-direction:column;align-items:center;gap:8px;
    }
    .quick-btn:hover {
      border-color:var(--primary);background:var(--primary-pale);
      transform:translateY(-2px);box-shadow:var(--shadow-md);
    }
    .quick-btn i  { font-size:1.5rem;color:var(--primary); }
    .quick-btn span { font-size:.75rem;font-weight:600;color:var(--text-secondary); }
  </style>
</head>
<body>
<div class="rms-shell">
  <div class="sidebar-overlay" id="sidebarOverlay"></div>
  <?php include '../includes/sidebar_tenant.php'; ?>
  <div class="main-content" id="mainContent">
    <?php include '../includes/topnav.php'; ?>
    <div class="page-content">

      <?php if (isset($_GET['success'])): ?>
      <div class="alert alert-success mb-4"><i class="bi bi-check-circle-fill"></i><div><?= htmlspecialchars($_GET['success']) ?></div></div>
      <?php endif; ?>

      <!-- Rent hero banner -->
      <div class="rent-hero">
        <div class="hero-label">Hello, <?= htmlspecialchars(explode(' ', $_SESSION['name'])[0]) ?> 👋</div>
        <?php if ($data['unit_id']): ?>
          <div class="hero-amount"><?= formatKES($data['unit_rent'] ?? $data['assigned_rent'] ?? 0) ?></div>
          <div class="hero-sub">
            <i class="bi bi-building"></i> <?= htmlspecialchars($data['apartment_name'] ?? '') ?>
            &nbsp;·&nbsp;
            <i class="bi bi-door-open"></i> Unit <?= htmlspecialchars($data['unit_number'] ?? '') ?>
            &nbsp;·&nbsp;
            Due: 5th <?= date('F Y') ?>
          </div>
          <?php
            $heroStatus = isset($curPay['status']) ? $curPay['status'] : 'No Record';
            $heroIconMap = array('Paid'=>'bi-check-circle-fill','Overdue'=>'bi-exclamation-triangle-fill');
            $heroCls = isset($heroIconMap[$heroStatus]) ? $heroIconMap[$heroStatus] : 'bi-clock-fill';
          ?>
          <div>
            <span class="status-pill">
              <i class="bi <?= $heroCls ?>"></i>
              <?= $curMonth ?> <?= $curYear ?>: <?= $heroStatus ?>
            </span>
            <?php if ($outstanding['total'] > 0): ?>
            <span class="status-pill" style="background:rgba(239,68,68,.3);margin-left:6px;">
              <i class="bi bi-exclamation-circle-fill"></i>
              Outstanding: <?= formatKES($outstanding['total']) ?>
            </span>
            <?php endif; ?>
          </div>
        <?php else: ?>
          <div class="hero-amount" style="font-size:1.2rem;">No Unit Assigned</div>
          <div class="hero-sub">Contact your caretaker or administrator for unit assignment.</div>
        <?php endif; ?>
      </div>

      <!-- Quick actions -->
      <div class="quick-grid">
        <a href="pay_now.php" class="quick-btn"><i class="bi bi-cash-coin"></i><span>Pay Rent</span></a>
        <a href="payments.php#history" class="quick-btn"><i class="bi bi-receipt"></i><span>Receipts</span></a>
        <a href="maintenance.php?action=new" class="quick-btn"><i class="bi bi-wrench-adjustable"></i><span>Report Issue</span></a>
        <a href="announcements.php" class="quick-btn">
          <i class="bi bi-megaphone"></i><span>Notices
            <?php
              $newAnns = 0;
              $annList = isset($data['announcements']) ? $data['announcements'] : array();
              foreach ($annList as $__a) {
                  if (strtotime($__a['created_at']) > strtotime('-7 days')) $newAnns++;
              }
              if ($newAnns): ?><span style="background:var(--danger);color:#fff;font-size:.6rem;border-radius:10px;padding:1px 5px;"><?= $newAnns ?></span><?php endif;
            ?>
          </span>
        </a>
        <a href="expenses.php" class="quick-btn"><i class="bi bi-lightning-charge"></i><span>Bills</span></a>
        <a href="my_unit.php" class="quick-btn"><i class="bi bi-house-fill"></i><span>My Unit</span></a>
        <a href="profile.php" class="quick-btn"><i class="bi bi-person-circle"></i><span>Profile</span></a>
      </div>

      <!-- Stats row -->
      <div class="stats-grid" style="grid-template-columns:repeat(auto-fill,minmax(155px,1fr));margin-bottom:24px;">
        <?php
          $paidCount    = 0; $pendingCount = 0; $totalPaid = 0.0; $openMaint = 0;
          foreach ($data['payments'] as $__p) {
              if ($__p['status']==='Paid') { $paidCount++; $totalPaid += (float)$__p['amount']; }
              elseif (in_array($__p['status'],array('Pending','Overdue'))) $pendingCount++;
          }
          foreach ($data['maintenance'] as $__m) {
              if (in_array($__m['status'],array('Pending','In Progress'))) $openMaint++;
          }
          
          $utilityPending = 0;
          $utilityAmount = 0.0;
          foreach ($data['bills'] as $__bill) {
              if (in_array($__bill['status'], array('Pending', 'Overdue'), true)) {
                  $utilityPending++;
                  $utilityAmount += (float)$__bill['amount'];
              }
          }
        ?>
        <div class="stat-card green">
          <div class="stat-icon"><i class="bi bi-check-circle"></i></div>
          <div class="stat-value" data-count="<?= $paidCount ?>"><?= $paidCount ?></div>
          <div class="stat-label">Months Paid <?= $curYear ?></div>
        </div>
        <div class="stat-card orange">
          <div class="stat-icon"><i class="bi bi-clock-history"></i></div>
          <div class="stat-value" data-count="<?= $pendingCount ?>"><?= $pendingCount ?></div>
          <div class="stat-label">Pending / Overdue</div>
        </div>
        <div class="stat-card blue">
          <div class="stat-icon"><i class="bi bi-cash-stack"></i></div>
          <div class="stat-value" style="font-size:1rem;"><?= formatKES($totalPaid) ?></div>
          <div class="stat-label">Total Paid <?= $curYear ?></div>
        </div>
        <div class="stat-card <?= $openMaint>0?'red':'' ?>">
          <div class="stat-icon"><i class="bi bi-wrench"></i></div>
          <div class="stat-value" data-count="<?= $openMaint ?>"><?= $openMaint ?></div>
          <div class="stat-label">Open Requests</div>
        </div>
        <div class="stat-card <?= $utilityPending>0?'orange':'' ?>">
          <div class="stat-icon"><i class="bi bi-lightning-charge"></i></div>
          <div class="stat-value" style="font-size:0.9rem;"><?= $utilityPending > 0 ? formatKES($utilityAmount) : '0' ?></div>
          <div class="stat-label">Utility Bills Pending</div>
        </div>
      </div>

      <!-- Payment calendar + right column -->
      <div class="grid-2 mb-6">

        <!-- Yearly payment grid -->
        <div class="card">
          <div class="card-header">
            <h3 class="card-title"><i class="bi bi-calendar3"></i> <?= $curYear ?> Payment Summary</h3>
            <a href="payments.php" class="btn btn-secondary btn-sm">Full History</a>
          </div>
          <div class="card-body">
            <div class="months-grid">
              <?php foreach ($data['payments'] as $month => $p): ?>
              <div class="month-cell <?= $p['status'] ? strtolower($p['status']) : '' ?>"
                   title="<?= $month ?>: <?= $p['amount'] ? formatKES($p['amount']) : '—' ?>">
                <div class="month-name"><?= substr($month,0,3) ?></div>
                <div class="month-amount" style="font-size:.75rem;">
                  <?= $p['amount'] ? formatKES($p['amount']) : '—' ?>
                </div>
                <div class="month-status" style="margin-top:4px;">
                  <?php if ($p['status']==='Paid'): ?><span class="badge badge-paid" style="font-size:.58rem;">Paid</span>
                  <?php elseif ($p['status']==='Overdue'): ?><span class="badge badge-overdue" style="font-size:.58rem;">Late</span>
                  <?php elseif ($p['status']==='Pending'): ?><span class="badge badge-pending" style="font-size:.58rem;">Due</span>
                  <?php else: ?><span style="color:var(--text-muted);font-size:.65rem;">—</span>
                  <?php endif; ?>
                </div>
              </div>
              <?php endforeach; ?>
            </div>

            <?php if ($outstanding['total'] > 0): ?>
            <div class="alert alert-warning mt-4" style="margin-bottom:0;">
              <i class="bi bi-exclamation-triangle-fill"></i>
              <div>
                <strong>Outstanding: <?= formatKES($outstanding['total']) ?></strong>
                <?php if ($outstanding['earliest']): ?>
                — Earliest: <?= $outstanding['earliest']['month'] ?> <?= $outstanding['earliest']['year'] ?>
                <?php endif; ?>
                <a href="payments.php" class="btn btn-primary btn-sm" style="margin-left:10px;">
                  <i class="bi bi-cash-coin"></i> View Payments
                </a>
              </div>
            </div>
            <?php elseif ($curPay && $curPay['status'] !== 'Paid'): ?>
            <div class="alert alert-info mt-4" style="margin-bottom:0;">
              <i class="bi bi-info-circle-fill"></i>
              <div>
                <?= $curMonth ?> <?= $curYear ?> rent is due.
                <a href="payments.php" class="btn btn-primary btn-sm" style="margin-left:8px;">
                  <i class="bi bi-cash-coin"></i> Pay Now
                </a>
              </div>
            </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Right column: Maintenance + Announcements -->
        <div>
          <!-- Active maintenance -->
          <div class="card mb-4">
            <div class="card-header">
              <h3 class="card-title"><i class="bi bi-wrench"></i> My Maintenance</h3>
              <div class="d-flex gap-2">
                <a href="maintenance.php?action=new" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> New</a>
                <a href="maintenance.php" class="btn btn-secondary btn-sm">All</a>
              </div>
            </div>
            <div class="card-body">
              <?php
                $activeMaint = array();
                foreach ($data['maintenance'] as $__m) {
                    if (in_array($__m['status'],array('Pending','In Progress'))) $activeMaint[] = $__m;
                }
              ?>
              <?php if (empty($activeMaint)): ?>
              <div style="text-align:center;padding:16px;color:var(--text-muted);font-size:.85rem;">
                <i class="bi bi-check2-all" style="color:#7ED321;display:block;font-size:1.5rem;margin-bottom:4px;"></i>
                No open requests.
              </div>
              <?php else: ?>
                <?php foreach (array_slice($activeMaint,0,3) as $m): ?>
                <div style="display:flex;justify-content:space-between;align-items:center;padding:9px 0;border-bottom:1px solid var(--border);font-size:.83rem;">
                  <div>
                    <div class="fw-bold"><?= htmlspecialchars($m['title']) ?></div>
                    <div style="font-size:.72rem;color:var(--text-muted);"><?= formatDate($m['created_at']) ?></div>
                  </div>
                  <span class="badge <?= statusBadgeClass($m['status']) ?>"><?= $m['status'] ?></span>
                </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>

          <!-- Latest announcements -->
          <div class="card">
            <div class="card-header">
              <h3 class="card-title"><i class="bi bi-megaphone"></i> Notices</h3>
              <a href="announcements.php" class="btn btn-secondary btn-sm">All</a>
            </div>
            <div class="card-body">
              <?php if (empty($data['announcements'])): ?>
              <div style="text-align:center;padding:16px;color:var(--text-muted);font-size:.85rem;">No announcements.</div>
              <?php else: ?>
                <?php foreach (array_slice($data['announcements'],0,3) as $ann): ?>
                <div class="announcement-card <?= $ann['type']==='Urgent'?'urgent':($ann['type']==='Information'?'info':'') ?>"
                     style="margin-bottom:10px;">
                  <div class="ann-title"><?= htmlspecialchars($ann['title']) ?></div>
                  <div class="ann-date"><?= formatDate($ann['created_at']) ?></div>
                  <div class="ann-body"><?= htmlspecialchars(mb_substr($ann['body'],0,100)) ?>…</div>
                </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>
<script src="../assets/js/main.js"></script>
</body>
</html>
