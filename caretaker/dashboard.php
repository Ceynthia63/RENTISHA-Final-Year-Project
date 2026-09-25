<?php
session_start();
require_once '../includes/auth_check.php';
require_once '../includes/db_helpers.php';
requireRole('caretaker');

$pageTitle  = 'Dashboard';
$activePage = 'dashboard';

$aptId = (int)($_SESSION['apartment_id'] ?? 0);
if (!$aptId) {
    $noApt = true;
} else {
    $noApt = false;
    $s     = getCaretakerDashboardStats($aptId);
    $apt   = $s['apartment'];
    $tenants     = getTenants('active', $aptId);
    $pendingRent = array_filter($tenants, fn($t) => in_array($t['rent_status'], ['Pending','Overdue', null]));
    $maintenance = getMaintenanceRequests(['apartment_id' => $aptId]);
    $announcements = getAnnouncementsForCaretaker($aptId);
    $revenue     = getYearlyRevenue((int)date('Y'));
    $pdo = getDB();
    $revStmt = $pdo->prepare(
        "SELECT month,
           COALESCE(SUM(CASE WHEN status='Paid'    THEN amount ELSE 0 END),0) AS collected,
           COALESCE(SUM(CASE WHEN status='Pending' THEN amount ELSE 0 END),0) AS pending,
           COALESCE(SUM(CASE WHEN status='Overdue' THEN amount ELSE 0 END),0) AS overdue
         FROM payments WHERE apartment_id=:aid AND year=:yr GROUP BY month"
    );
    $revStmt->execute([':aid' => $aptId, ':yr' => (int)date('Y')]);
    $revRows = [];
    foreach ($revStmt->fetchAll(PDO::FETCH_ASSOC) as $r) { $revRows[$r['month']] = $r; }
    $chartMonths = $chartCollected = $chartPending = [];
    foreach (['January','February','March','April','May','June',
              'July','August','September','October','November','December'] as $m) {
        $chartMonths[]    = substr($m,0,3);
        $chartCollected[] = (float)($revRows[$m]['collected'] ?? 0);
        $chartPending[]   = (float)($revRows[$m]['pending']   ?? 0);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Caretaker Dashboard — Rentisha</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="../assets/css/style.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
</head>
<body>
<div class="rms-shell">
  <div class="sidebar-overlay" id="sidebarOverlay"></div>
  <?php include '../includes/sidebar_caretaker.php'; ?>
  <div class="main-content" id="mainContent">
    <?php include '../includes/topnav.php'; ?>
    <div class="page-content">

    <?php if ($noApt): ?>
      <!-- Not assigned yet -->
      <div style="text-align:center;padding:60px 20px;color:var(--text-muted);">
        <i class="bi bi-building-slash" style="font-size:4rem;display:block;margin-bottom:16px;color:var(--primary);"></i>
        <h2>No Apartment Assigned</h2>
        <p style="margin-top:8px;">You have not been assigned to any apartment yet.<br>Please contact the administrator.</p>
      </div>
    <?php else: ?>

      <div class="page-header">
        <div>
          <h1><?= htmlspecialchars($apt['name'] ?? 'My Apartment') ?></h1>
          <p>
            <i class="bi bi-geo-alt"></i> <?= htmlspecialchars($apt['location'] ?? '') ?>
            &nbsp;·&nbsp; <?= date('l, j F Y') ?>
          </p>
        </div>
        <div class="d-flex gap-2">
          <button class="btn btn-outline btn-sm" data-modal-open="reportExpenseModal">
            <i class="bi bi-receipt"></i> Report Expense
          </button>
          <button class="btn btn-primary btn-sm" data-modal-open="recordRentModal">
            <i class="bi bi-cash-coin"></i> Record Rent
          </button>
        </div>
      </div>

      <?php if (isset($_GET['success'])): ?>
      <div class="alert alert-success mb-4"><i class="bi bi-check-circle-fill"></i><div><?= htmlspecialchars($_GET['success']) ?></div></div>
      <?php endif; ?>

      <!-- Stats -->
      <div class="stats-grid">
        <div class="stat-card blue">
          <div class="stat-header"><div class="stat-icon"><i class="bi bi-door-open"></i></div></div>
          <div class="stat-value" data-count="<?= $s['total_units'] ?>"><?= $s['total_units'] ?></div>
          <div class="stat-label">Total Units</div>
          <div class="stat-sub"><i class="bi bi-building"></i> <?= htmlspecialchars($apt['name'] ?? '') ?></div>
        </div>
        <div class="stat-card green">
          <div class="stat-header"><div class="stat-icon"><i class="bi bi-check-circle"></i></div></div>
          <div class="stat-value" data-count="<?= $s['occupied_units'] ?>"><?= $s['occupied_units'] ?></div>
          <div class="stat-label">Occupied</div>
          <div class="stat-sub"><i class="bi bi-people"></i> <?= $s['active_tenants'] ?> tenants</div>
        </div>
        <div class="stat-card orange">
          <div class="stat-header"><div class="stat-icon"><i class="bi bi-door-closed"></i></div></div>
          <div class="stat-value" data-count="<?= $s['vacant_units'] ?>"><?= $s['vacant_units'] ?></div>
          <div class="stat-label">Vacant</div>
        </div>
        <div class="stat-card green">
          <div class="stat-header"><div class="stat-icon"><i class="bi bi-cash-stack"></i></div></div>
          <div class="stat-value" style="font-size:1.1rem;"><?= formatKES($s['rent_collected']) ?></div>
          <div class="stat-label">Collected <?= date('M Y') ?></div>
        </div>
        <div class="stat-card orange">
          <div class="stat-header"><div class="stat-icon"><i class="bi bi-clock-history"></i></div></div>
          <div class="stat-value" style="font-size:1.1rem;"><?= formatKES($s['rent_pending']) ?></div>
          <div class="stat-label">Pending <?= date('M Y') ?></div>
        </div>
        <div class="stat-card red">
          <div class="stat-header"><div class="stat-icon"><i class="bi bi-exclamation-triangle"></i></div></div>
          <div class="stat-value" style="font-size:1.1rem;"><?= formatKES($s['rent_overdue']) ?></div>
          <div class="stat-label">Overdue <?= date('M Y') ?></div>
        </div>
        <div class="stat-card red">
          <div class="stat-header"><div class="stat-icon"><i class="bi bi-wrench"></i></div>
            <?php if ($s['pending_maint'] > 0): ?>
            <span class="stat-change down"><?= $s['pending_maint'] ?> new</span>
            <?php endif; ?>
          </div>
          <div class="stat-value" data-count="<?= $s['pending_maint'] ?>"><?= $s['pending_maint'] ?></div>
          <div class="stat-label">Pending Maint.</div>
          <div class="stat-sub"><i class="bi bi-play-circle"></i> <?= $s['inprogress_maint'] ?> in progress</div>
        </div>
        <div class="stat-card green">
          <div class="stat-header"><div class="stat-icon"><i class="bi bi-check2-all"></i></div></div>
          <div class="stat-value" data-count="<?= $s['resolved_maint'] ?>"><?= $s['resolved_maint'] ?></div>
          <div class="stat-label">Resolved Maint.</div>
        </div>
      </div>

      <!-- Charts row -->
      <div class="grid-2 mb-6">
        <!-- Revenue chart -->
        <div class="card">
          <div class="card-header">
            <h3 class="card-title"><i class="bi bi-bar-chart-line"></i> Rent Collection — <?= date('Y') ?></h3>
          </div>
          <div class="card-body">
            <div class="chart-container" style="height:210px;">
              <canvas id="ctRevChart"></canvas>
            </div>
          </div>
        </div>
        <!-- Occupancy donut -->
        <div class="card">
          <div class="card-header">
            <h3 class="card-title"><i class="bi bi-pie-chart"></i> Occupancy</h3>
            <?php
              $occPct = $s['total_units'] > 0 ? round($s['occupied_units']/$s['total_units']*100,1) : 0;
            ?>
            <span class="badge badge-paid"><?= $occPct ?>%</span>
          </div>
          <div class="card-body" style="display:flex;align-items:center;gap:20px;flex-wrap:wrap;">
            <div style="width:170px;height:170px;flex-shrink:0;">
              <canvas id="ctOccChart"></canvas>
            </div>
            <div style="flex:1;min-width:130px;">
              <div class="d-flex justify-between fs-sm mb-1">
                <span>Occupied (<?= $s['occupied_units'] ?>)</span>
                <span class="fw-bold" style="color:#7ED321;"><?= $occPct ?>%</span>
              </div>
              <div class="progress mb-3">
                <div class="progress-bar success" style="width:<?= $occPct ?>%"></div>
              </div>
              <div class="d-flex justify-between fs-sm mb-1">
                <span>Vacant (<?= $s['vacant_units'] ?>)</span>
                <span class="fw-bold" style="color:#f59e0b;"><?= 100-$occPct ?>%</span>
              </div>
              <div class="progress">
                <div class="progress-bar warning" style="width:<?= 100-$occPct ?>%"></div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Pending rent + Maintenance -->
      <div class="grid-2 mb-6">
        <!-- Pending/Overdue rent tenants -->
        <div class="card">
          <div class="card-header">
            <h3 class="card-title"><i class="bi bi-exclamation-circle"></i> Pending / Overdue Rent</h3>
            <a href="rent.php" class="btn btn-secondary btn-sm">View All</a>
          </div>
          <div class="card-body p-0">
            <?php $pr = array_values($pendingRent); ?>
            <?php if (empty($pr)): ?>
            <div style="text-align:center;padding:24px;color:var(--text-muted);">
              <i class="bi bi-check2-all" style="font-size:1.8rem;display:block;margin-bottom:6px;color:#7ED321;"></i>
              All tenants are paid up!
            </div>
            <?php else: ?>
            <div class="table-wrapper">
              <table class="rms-table">
                <thead><tr><th>Tenant</th><th>Unit</th><th>Rent</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                  <?php foreach (array_slice($pr,0,5) as $t): ?>
                  <tr>
                    <td class="fw-bold"><?= htmlspecialchars($t['full_name']) ?></td>
                    <td><span class="badge badge-info"><?= htmlspecialchars($t['unit_number'] ?? '—') ?></span></td>
                    <td><?= formatKES($t['unit_rent'] ?? $t['assigned_rent'] ?? 0) ?></td>
                    <td>
                      <span class="badge <?= $t['rent_status'] ? statusBadgeClass($t['rent_status']) : 'badge-pending' ?>">
                        <?= $t['rent_status'] ?? 'No Record' ?>
                      </span>
                    </td>
                    <td>
                      <button class="btn btn-warning btn-sm btn-icon" title="Remind"
                        onclick="showToast('Reminder sent to <?= htmlspecialchars(addslashes($t['full_name'])) ?>','success')">
                        <i class="bi bi-send"></i>
                      </button>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Maintenance -->
        <div class="card">
          <div class="card-header">
            <h3 class="card-title"><i class="bi bi-wrench"></i> Maintenance Requests</h3>
            <a href="maintenance.php" class="btn btn-secondary btn-sm">View All</a>
          </div>
          <div class="card-body">
            <?php
              $openMaint = array_filter($maintenance, fn($m) => in_array($m['status'],['Pending','In Progress']));
              $openMaint = array_values($openMaint);
            ?>
            <?php if (empty($openMaint)): ?>
            <div style="text-align:center;padding:24px;color:var(--text-muted);">
              <i class="bi bi-check2-all" style="font-size:1.8rem;display:block;margin-bottom:6px;color:#7ED321;"></i>
              No open maintenance requests.
            </div>
            <?php else: ?>
              <?php foreach (array_slice($openMaint,0,3) as $m): ?>
              <div class="maintenance-card">
                <div class="mc-header">
                  <span class="mc-title"><?= htmlspecialchars($m['title']) ?></span>
                  <span class="badge <?= statusBadgeClass($m['status']) ?>"><?= $m['status'] ?></span>
                </div>
                <div class="mc-desc"><?= htmlspecialchars(mb_substr($m['description'],0,90)) ?>…</div>
                <div class="mc-meta">
                  <span><i class="bi bi-person"></i> <?= htmlspecialchars($m['tenant_name']) ?></span>
                  <span><i class="bi bi-door-open"></i> <?= htmlspecialchars($m['unit_number'] ?? '—') ?></span>
                  <span><i class="bi bi-calendar3"></i> <?= formatDate($m['created_at']) ?></span>
                </div>
                <div class="d-flex gap-2 mt-2">
                  <?php if ($m['status'] === 'Pending'): ?>
                  <form method="POST" action="../includes/save_maintenance.php" style="display:inline;">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(rmsCsrfToken()) ?>">
                    <input type="hidden" name="edit_id"     value="<?= $m['id'] ?>">
                    <input type="hidden" name="new_status"  value="In Progress">
                    <input type="hidden" name="notes"       value="Started work.">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-play-circle"></i> Start</button>
                  </form>
                  <?php else: ?>
                  <form method="POST" action="../includes/save_maintenance.php" style="display:inline;">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(rmsCsrfToken()) ?>">
                    <input type="hidden" name="edit_id"    value="<?= $m['id'] ?>">
                    <input type="hidden" name="new_status" value="Resolved">
                    <input type="hidden" name="notes"      value="Issue resolved.">
                    <button type="submit" class="btn btn-success btn-sm"><i class="bi bi-check-circle"></i> Resolve</button>
                  </form>
                  <?php endif; ?>
                  <a href="maintenance.php?update=<?= $m['id'] ?>" class="btn btn-outline btn-sm">
                    <i class="bi bi-pencil"></i> Update
                  </a>
                </div>
              </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Announcements + Recent Expenses row -->
      <div class="grid-2 mb-6">
        <!-- Announcements -->
        <div class="card">
          <div class="card-header">
            <h3 class="card-title"><i class="bi bi-megaphone"></i> Announcements</h3>
            <a href="announcements.php" class="btn btn-secondary btn-sm">View All</a>
          </div>
          <div class="card-body">
            <?php if (empty($announcements)): ?>
            <div style="text-align:center;padding:18px;color:var(--text-muted);">No announcements.</div>
            <?php else: ?>
              <?php foreach (array_slice($announcements,0,3) as $ann): ?>
              <div class="announcement-card <?= $ann['type']==='Urgent'?'urgent':($ann['type']==='Information'?'info':'') ?>">
                <div class="ann-title"><?= htmlspecialchars($ann['title']) ?></div>
                <div class="ann-date"><?= formatDate($ann['created_at']) ?> · <?= htmlspecialchars($ann['author_name']) ?></div>
                <div class="ann-body"><?= htmlspecialchars(mb_substr($ann['body'],0,140)) ?>…</div>
              </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>

        <!-- Recent Maintenance Expenses -->
        <div class="card">
          <div class="card-header">
            <h3 class="card-title"><i class="bi bi-wrench-adjustable-circle"></i> Recent Expenses</h3>
            <div class="d-flex gap-2">
              <button class="btn btn-primary btn-sm" data-modal-open="reportExpenseModal">
                <i class="bi bi-plus-lg"></i> Report
              </button>
              <a href="expenses.php" class="btn btn-secondary btn-sm">View All</a>
            </div>
          </div>
          <div class="card-body p-0">
            <?php
            try {
              $expStmt = $pdo->prepare(
                "SELECT expense_type, description, amount, expense_date, month, year, status, vendor
                 FROM shared_expenses
                 WHERE apartment_id = :aid
                 ORDER BY COALESCE(expense_date, created_at) DESC
                 LIMIT 4"
              );
              $expStmt->execute([':aid' => $aptId]);
              $recentExp = $expStmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) { $recentExp = []; }
            $typeIcons = ['Plumbing'=>'bi-droplet-fill','Electrical'=>'bi-lightning-fill',
                          'Locks & Keys'=>'bi-key-fill','Painting'=>'bi-brush-fill',
                          'Security'=>'bi-shield-fill','Cleaning'=>'bi-stars',
                          'Pest Control'=>'bi-bug-fill','Appliances'=>'bi-tools',
                          'General Maintenance'=>'bi-wrench-adjustable-circle-fill',
                          'Structural'=>'bi-building','Other'=>'bi-three-dots'];
            ?>
            <?php if (empty($recentExp)): ?>
            <div style="text-align:center;padding:24px;color:var(--text-muted);">
              <i class="bi bi-receipt" style="font-size:1.8rem;display:block;margin-bottom:6px;"></i>
              No expenses reported yet.
              <br><a href="#" data-modal-open="reportExpenseModal" style="color:var(--primary);">Report one now</a>
            </div>
            <?php else: ?>
            <div class="table-wrapper">
              <table class="rms-table">
                <thead><tr><th>Type</th><th>Description</th><th>Amount</th><th>Date</th><th>Status</th></tr></thead>
                <tbody>
                  <?php foreach ($recentExp as $ex):
                    $ico = $typeIcons[$ex['expense_type']] ?? 'bi-tools';
                  ?>
                  <tr>
                    <td>
                      <i class="bi <?= $ico ?>" style="color:var(--primary);"></i>
                      <span style="font-size:.8rem;"><?= htmlspecialchars($ex['expense_type']) ?></span>
                    </td>
                    <td style="font-size:.8rem;max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                      <?= htmlspecialchars($ex['description'] ?? '—') ?>
                    </td>
                    <td class="fw-bold" style="color:var(--danger);font-size:.85rem;"><?= formatKES($ex['amount']) ?></td>
                    <td style="font-size:.78rem;">
                      <?= $ex['expense_date'] ? formatDate($ex['expense_date']) : ($ex['month'].' '.$ex['year']) ?>
                    </td>
                    <td>
                      <span class="badge <?= $ex['status']==='Paid'?'badge-paid':($ex['status']==='Pending'?'badge-pending':'badge-secondary') ?>" style="font-size:.65rem;">
                        <?= $ex['status'] ?>
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
      </div>        </div>
      </div>

    <?php endif; ?>
    </div>
  </div>
</div>

<!-- Report Expense Modal -->
<?php if (!$noApt): ?>
<div class="modal-overlay" id="reportExpenseModal">
  <div class="modal modal-lg">
    <div class="modal-header">
      <h3><i class="bi bi-wrench-adjustable"></i> Report Maintenance Expense</h3>
      <button class="modal-close" data-modal-close><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="modal-body">
      <div class="alert alert-info" style="font-size:.83rem;">
        <i class="bi bi-info-circle-fill"></i>
        <div>Record money spent on repairs, materials or labour. Admin will be notified.</div>
      </div>
      <form action="../includes/save_expense.php" method="POST" data-validate>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(rmsCsrfToken()) ?>">
        <input type="hidden" name="apartment_id" value="<?= $aptId ?>">
        <input type="hidden" name="paid_by"      value="<?= (int)$_SESSION['user_id'] ?>">
        <input type="hidden" name="status"       value="Paid">

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Expense Type <span class="required">*</span></label>
            <select name="expense_type" class="form-control" required>
              <option value="">— Select —</option>
              <?php foreach (['Plumbing','Electrical','Structural','Appliances','Security',
                              'Cleaning','Pest Control','Locks & Keys','Painting',
                              'General Maintenance','Other'] as $t): ?>
              <option value="<?= $t ?>"><?= $t ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Category <span class="required">*</span></label>
            <select name="cost_category" class="form-control" required>
              <?php foreach (['Labour','Materials','Equipment','Contractor','Permit','Other'] as $c): ?>
              <option value="<?= $c ?>"><?= $c ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">What Was Done <span class="required">*</span></label>
          <textarea name="description" class="form-control" rows="2" required
            placeholder="e.g. Replaced broken lock on unit 7, bought deadbolt from Kariuki Hardware"></textarea>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Amount (KES) <span class="required">*</span></label>
            <div class="input-group">
              <i class="bi bi-currency-exchange input-icon"></i>
              <input type="number" name="amount" class="form-control" placeholder="e.g. 3500" required min="1" step="0.01">
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Date Spent <span class="required">*</span></label>
            <input type="date" name="expense_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Vendor / Technician</label>
            <input type="text" name="vendor" class="form-control" placeholder="e.g. John the Plumber">
          </div>
          <div class="form-group">
            <label class="form-label">Receipt #</label>
            <input type="text" name="receipt_ref" class="form-control" placeholder="e.g. RCP-00123">
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Month</label>
            <select name="month" class="form-control">
              <?php foreach (['January','February','March','April','May','June','July',
                              'August','September','October','November','December'] as $m): ?>
              <option value="<?= $m ?>" <?= $m===date('F')?'selected':'' ?>><?= $m ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Year</label>
            <select name="year" class="form-control">
              <option value="<?= date('Y') ?>"><?= date('Y') ?></option>
              <option value="<?= date('Y')-1 ?>"><?= date('Y')-1 ?></option>
            </select>
          </div>
        </div>

        <div class="modal-footer" style="padding:0;margin-top:8px;">
          <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-send-fill"></i> Report to Admin
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- Record Rent Modal -->
<?php if (!$noApt): ?>
<div class="modal-overlay" id="recordRentModal">
  <div class="modal modal-lg">
    <div class="modal-header">
      <h3><i class="bi bi-cash-coin"></i> Record Rent Payment</h3>
      <button class="modal-close" data-modal-close><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="modal-body">
      <form action="../includes/save_payment.php" method="POST" data-validate>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(rmsCsrfToken()) ?>">
        <input type="hidden" name="action" value="record">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Tenant <span class="required">*</span></label>
            <select name="tenant_id" class="form-control" required onchange="loadTenantRent(this)">
              <option value="">— Select Tenant —</option>
              <?php foreach ($tenants as $t): ?>
              <option value="<?= $t['id'] ?>"
                      data-rent="<?= $t['unit_rent'] ?? $t['assigned_rent'] ?? 0 ?>"
                      data-unit="<?= htmlspecialchars($t['unit_number'] ?? '') ?>">
                <?= htmlspecialchars($t['full_name']) ?>
                <?= $t['unit_number'] ? ' — '.$t['unit_number'] : '' ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Amount (KES) <span class="required">*</span></label>
            <div class="input-group">
              <i class="bi bi-currency-exchange input-icon"></i>
              <input type="number" name="amount" id="ctPayAmt" class="form-control" placeholder="Monthly rent" required min="1">
            </div>
            <div class="form-hint" id="ctRentHint"></div>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Month <span class="required">*</span></label>
            <select name="month" class="form-control" required>
              <?php foreach (['January','February','March','April','May','June','July','August','September','October','November','December'] as $m): ?>
              <option value="<?= $m ?>" <?= $m===date('F')?'selected':'' ?>><?= $m ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Year</label>
            <select name="year" class="form-control">
              <option value="<?= date('Y') ?>"><?= date('Y') ?></option>
              <option value="<?= date('Y')-1 ?>"><?= date('Y')-1 ?></option>
            </select>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Payment Method <span class="required">*</span></label>
            <select name="payment_method" class="form-control" required>
              <option value="M-PESA Paybill">M-PESA Paybill</option>
              <option value="Airtel Money">Airtel Money</option>
              <option value="Cash">Cash</option>
              <option value="PDQ/POS">PDQ / POS</option>
              <option value="Cheque">Cheque</option>
              <option value="Other">Other</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Reference / Transaction #</label>
            <input type="text" name="reference_number" class="form-control" placeholder="e.g. QAB123456">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Date Paid</label>
            <input type="date" name="payment_date" class="form-control" value="<?= date('Y-m-d') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Status</label>
            <select name="status" class="form-control">
              <option value="Paid">Paid</option>
              <option value="Pending">Pending</option>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Notes</label>
          <textarea name="notes" class="form-control" rows="2" placeholder="Optional…"></textarea>
        </div>
        <div class="modal-footer" style="padding:0;margin-top:8px;">
          <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
          <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Save Payment</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<script src="../assets/js/main.js"></script>
<script>
<?php if (!$noApt): ?>
new Chart(document.getElementById('ctRevChart').getContext('2d'), {
  type:'bar',
  data:{
    labels: <?= json_encode($chartMonths) ?>,
    datasets:[
      {label:'Collected',data:<?= json_encode($chartCollected) ?>,backgroundColor:'rgba(126,211,33,.8)',borderRadius:5},
      {label:'Pending',  data:<?= json_encode($chartPending) ?>,  backgroundColor:'rgba(245,158,11,.75)',borderRadius:5}
    ]
  },
  options:{responsive:true,maintainAspectRatio:false,
    plugins:{legend:{position:'top'}},
    scales:{y:{beginAtZero:true,ticks:{callback:v=>'KES '+(v>=1000?(v/1000)+'k':v)}},x:{grid:{display:false}}}}
});

new Chart(document.getElementById('ctOccChart').getContext('2d'), {
  type:'doughnut',
  data:{labels:['Occupied','Vacant'],
    datasets:[{data:[<?= $s['occupied_units'] ?>,<?= $s['vacant_units'] ?>],
      backgroundColor:['#7ED321','#f59e0b'],borderWidth:0,cutout:'72%'}]},
  options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'bottom'}}}
});

function loadTenantRent(sel) {
  const opt  = sel.options[sel.selectedIndex];
  const rent = opt.dataset.rent;
  const unit = opt.dataset.unit;
  if (rent) {
    document.getElementById('ctPayAmt').value  = rent;
    document.getElementById('ctRentHint').textContent =
      'Monthly rent for ' + (unit||'this tenant') + ': KES ' + parseInt(rent).toLocaleString();
  }
}
<?php endif; ?>
</script>
</body>
</html>
