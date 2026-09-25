<?php
session_start();
require_once '../includes/auth_check.php';
require_once '../includes/db_helpers.php';
requireRole('caretaker');

$pageTitle  = 'Rent Payments';
$activePage = 'rent';

$aptId = (int)($_SESSION['apartment_id'] ?? 0);
if (!$aptId) { header('Location: dashboard.php'); exit; }

$filterMonth = $_GET['month'] ?? date('F');
$filterYear  = (int)($_GET['year'] ?? date('Y'));
$filterStatus= $_GET['status'] ?? '';

$payments = getPayments([
    'apartment_id' => $aptId,
    'month'        => $filterMonth ?: null,
    'year'         => $filterYear,
    'status'       => $filterStatus ?: null,
    'payment_type' => ['Rent', 'Deposit'],
]);

$pending = getPayments([
    'apartment_id' => $aptId,
    'status'       => 'Pending',
    'year'         => $filterYear,
    'payment_type' => ['Rent', 'Deposit'],
]);

$totals  = getMonthlyTotals($aptId);
$tenants = getTenants('active', $aptId);

$success = $_GET['success'] ?? '';
$error   = $_GET['error']   ?? '';
$months  = ['January','February','March','April','May','June',
            'July','August','September','October','November','December'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Rent — Rentisha Caretaker</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="rms-shell">
  <div class="sidebar-overlay" id="sidebarOverlay"></div>
  <?php include '../includes/sidebar_caretaker.php'; ?>
  <div class="main-content" id="mainContent">
    <?php include '../includes/topnav.php'; ?>
    <div class="page-content">

      <?php if ($success): ?>
      <div class="alert alert-success mb-4"><i class="bi bi-check-circle-fill"></i><div><?= htmlspecialchars($success) ?></div></div>
      <?php endif; ?>
      <?php if ($error): ?>
      <div class="alert alert-danger mb-4"><i class="bi bi-exclamation-circle-fill"></i><div><?= htmlspecialchars($error) ?></div></div>
      <?php endif; ?>

      <div class="page-header">
        <div>
          <h1>Rent Payments</h1>
          <p><?= htmlspecialchars($_SESSION['apartment'] ?? '') ?></p>
        </div>
        <div class="d-flex gap-2">
          <button class="btn btn-primary" data-modal-open="recordRentModal">
            <i class="bi bi-plus-lg"></i> Record Payment
          </button>
        </div>
      </div>

      <!-- Stats -->
      <div class="stats-grid" style="grid-template-columns:repeat(auto-fill,minmax(140px,1fr));margin-bottom:18px;">
        <div class="stat-card green">
          <div class="stat-icon"><i class="bi bi-cash-stack"></i></div>
          <div class="stat-value" style="font-size:1rem;"><?= formatKES($totals['collected'] ?? 0) ?></div>
          <div class="stat-label">Collected <?= date('M Y') ?></div>
        </div>
        <div class="stat-card orange">
          <div class="stat-icon"><i class="bi bi-clock-history"></i></div>
          <div class="stat-value" style="font-size:1rem;"><?= formatKES($totals['pending'] ?? 0) ?></div>
          <div class="stat-label">Pending <?= date('M Y') ?></div>
        </div>
        <div class="stat-card red">
          <div class="stat-icon"><i class="bi bi-exclamation-triangle"></i></div>
          <div class="stat-value" style="font-size:1rem;"><?= formatKES($totals['overdue'] ?? 0) ?></div>
          <div class="stat-label">Overdue <?= date('M Y') ?></div>
        </div>
        <div class="stat-card blue">
          <div class="stat-icon"><i class="bi bi-check-circle"></i></div>
          <div class="stat-value" data-count="<?= $totals['paid_count'] ?? 0 ?>"><?= $totals['paid_count'] ?? 0 ?></div>
          <div class="stat-label">Paid This Month</div>
        </div>
        <div class="stat-card orange">
          <div class="stat-icon"><i class="bi bi-hourglass-split"></i></div>
          <div class="stat-value" data-count="<?= count($pending) ?>"><?= count($pending) ?></div>
          <div class="stat-label">Pending (<?= $filterYear ?>)</div>
        </div>
      </div>

      <!-- Tabs -->
      <div class="tabs" role="tablist">
        <div class="tab-item active" data-tab="tab-all" role="tab"><i class="bi bi-list-ul"></i> All Payments</div>
        <div class="tab-item" data-tab="tab-pending" role="tab">
          <i class="bi bi-clock-history"></i> Pending
          <?php if (count($pending)): ?>
          <span style="background:var(--warning);color:#fff;font-size:.62rem;padding:1px 6px;border-radius:10px;"><?= count($pending) ?></span>
          <?php endif; ?>
        </div>
      </div>

      <!-- ── ALL PAYMENTS TAB ── -->
      <div id="tab-all" class="tab-panel active">
        <!-- Filters -->
        <div class="filter-bar">
          <div class="input-group search-box">
            <i class="bi bi-search input-icon"></i>
            <input type="search" class="form-control" placeholder="Search tenant, unit…" data-search-table="ctRentTable">
          </div>
          <form method="GET" style="display:contents;">
            <select name="month" class="form-control" style="width:auto;" onchange="this.form.submit()">
              <option value="">All Months</option>
              <?php foreach ($months as $m): ?>
              <option value="<?= $m ?>" <?= $filterMonth===$m?'selected':'' ?>><?= $m ?></option>
              <?php endforeach; ?>
            </select>
            <select name="year" class="form-control" style="width:80px;" onchange="this.form.submit()">
              <?php for ($y=(int)date('Y'); $y>=(int)date('Y')-2; $y--): ?>
              <option value="<?= $y ?>" <?= $filterYear===$y?'selected':'' ?>><?= $y ?></option>
              <?php endfor; ?>
            </select>
            <select name="status" class="form-control" style="width:auto;" onchange="this.form.submit()">
              <option value="">All Statuses</option>
              <option value="Paid"    <?= $filterStatus==='Paid'?'selected':'' ?>>Paid</option>
              <option value="Pending" <?= $filterStatus==='Pending'?'selected':'' ?>>Pending</option>
              <option value="Overdue" <?= $filterStatus==='Overdue'?'selected':'' ?>>Overdue</option>
            </select>
          </form>
        </div>

        <div class="card">
          <div class="card-body p-0">
            <div class="table-wrapper">
              <table class="rms-table" id="ctRentTable">
                <thead>
                  <tr>
                    <th>#</th><th>Tenant</th><th>Unit</th><th>Period</th>
                    <th>Amount</th><th>Method</th><th>Date Paid</th>
                    <th>Reference</th><th>Status</th><th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($payments)): ?>
                  <tr><td colspan="10" style="text-align:center;padding:32px;color:var(--text-muted);">
                    No payments found for <?= $filterMonth ?: 'this period' ?> <?= $filterYear ?>.
                    <a href="#" data-modal-open="recordRentModal">Record one</a>.
                  </td></tr>
                  <?php else: ?>
                    <?php foreach ($payments as $i => $p): ?>
                    <tr>
                      <td><?= $i+1 ?></td>
                      <td class="fw-bold"><?= htmlspecialchars($p['tenant_name']) ?></td>
                      <td><span class="badge badge-info"><?= htmlspecialchars($p['unit_number'] ?? '—') ?></span></td>
                      <td><?= $p['month'].' '.$p['year'] ?></td>
                      <td class="fw-bold"><?= formatKES($p['amount']) ?></td>
                      <td style="font-size:.78rem;"><?= htmlspecialchars($p['payment_method']) ?></td>
                      <td style="font-size:.78rem;"><?= $p['payment_date'] ? formatDate($p['payment_date']) : '—' ?></td>
                      <td><code style="font-size:.72rem;"><?= htmlspecialchars($p['reference_number'] ?? '—') ?></code></td>
                      <td>
                        <span class="badge <?= statusBadgeClass($p['status']) ?>"><?= $p['status'] ?></span>
                        <?php if ($p['payment_method'] === 'Cheque' && !empty($p['cheque_status'])): ?>
                        <br><span class="badge <?= $p['cheque_status']==='Cleared'?'badge-paid':($p['cheque_status']==='Bounced'?'badge-overdue':'badge-pending') ?>" style="font-size:.62rem;margin-top:2px;">
                          Cheque: <?= $p['cheque_status'] ?>
                        </span>
                        <?php endif; ?>
                      </td>
                      <td>
                        <div class="d-flex gap-1">
                          <a href="../admin/payments.php?receipt=<?= $p['id'] ?>"
                             class="btn btn-secondary btn-sm btn-icon" title="Receipt">
                            <i class="bi bi-receipt"></i>
                          </a>
                          <?php if ($p['status'] !== 'Paid'): ?>
                          <form method="POST" action="../includes/save_payment.php" style="display:inline;">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(rmsCsrfToken()) ?>">
                            <input type="hidden" name="action"     value="confirm">
                            <input type="hidden" name="payment_id" value="<?= $p['id'] ?>">
                            <button type="submit" class="btn btn-success btn-sm btn-icon"
                              title="Confirm as Paid"
                              onclick="return confirm('Mark this payment as Paid?')">
                              <i class="bi bi-check-circle"></i>
                            </button>
                          </form>
                          <?php endif; ?>
                        </div>
                      </td>
                    </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div><!-- /tab-all -->

      <!-- ── PENDING TAB ── -->
      <div id="tab-pending" class="tab-panel">
        <?php if (empty($pending)): ?>
        <div style="text-align:center;padding:40px;color:var(--text-muted);">
          <i class="bi bi-check2-all" style="font-size:2rem;display:block;margin-bottom:8px;color:#7ED321;"></i>
          No pending payments for <?= $filterYear ?>. All clear!
        </div>
        <?php else: ?>
        <div class="card">
          <div class="card-body p-0">
            <div class="table-wrapper">
              <table class="rms-table">
                <thead>
                  <tr><th>Tenant</th><th>Unit</th><th>Period</th><th>Amount</th><th>Method</th><th>Cheque Status</th><th>Actions</th></tr>
                </thead>
                <tbody>
                  <?php foreach ($pending as $p): ?>
                  <tr>
                    <td class="fw-bold"><?= htmlspecialchars($p['tenant_name']) ?></td>
                    <td><span class="badge badge-info"><?= htmlspecialchars($p['unit_number'] ?? '—') ?></span></td>
                    <td><?= $p['month'].' '.$p['year'] ?></td>
                    <td class="fw-bold"><?= formatKES($p['amount']) ?></td>
                    <td style="font-size:.8rem;"><?= htmlspecialchars($p['payment_method']) ?></td>
                    <td>
                      <?php if ($p['payment_method'] === 'Cheque' && !empty($p['cheque_status'])): ?>
                        <span class="badge <?= $p['cheque_status']==='Cleared'?'badge-paid':($p['cheque_status']==='Bounced'?'badge-overdue':'badge-pending') ?>">
                          <?= $p['cheque_status'] ?>
                        </span>
                      <?php else: ?>
                        <span style="color:var(--text-muted);font-size:.78rem;">—</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <div class="d-flex gap-2">
                        <form method="POST" action="../includes/save_payment.php" style="display:inline;">
                          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(rmsCsrfToken()) ?>">
                          <input type="hidden" name="action"     value="confirm">
                          <input type="hidden" name="payment_id" value="<?= $p['id'] ?>">
                          <button type="submit" class="btn btn-success btn-sm"
                            onclick="return confirm('Mark as Paid?')">
                            <i class="bi bi-check-circle"></i> Confirm Paid
                          </button>
                        </form>
                        <a href="../admin/payments.php?receipt=<?= $p['id'] ?>"
                           class="btn btn-secondary btn-sm btn-icon" title="Receipt">
                          <i class="bi bi-receipt"></i>
                        </a>
                      </div>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
        <?php endif; ?>
      </div><!-- /tab-pending -->

    </div><!-- /page-content -->
  </div><!-- /main-content -->
</div><!-- /rms-shell -->

<!-- ═══════════ RECORD PAYMENT MODAL ═══════════ -->
<div class="modal-overlay" id="recordRentModal">
  <div class="modal modal-lg">
    <div class="modal-header">
      <h3><i class="bi bi-cash-coin"></i> Record Rent Payment</h3>
      <button class="modal-close" data-modal-close><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="modal-body">
      <form action="../includes/save_payment.php" method="POST" data-validate id="ctPaymentForm">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(rmsCsrfToken()) ?>">
        <input type="hidden" name="action" value="record">

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Tenant <span class="required">*</span></label>
            <select name="tenant_id" id="ctPayTenantId" class="form-control" required onchange="ctLoadTenantRent(this)">
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
              <input type="number" name="amount" id="ctPayAmount" class="form-control"
                     placeholder="Monthly rent amount" required min="1">
            </div>
            <div class="form-hint" id="ctRentHint"></div>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Month <span class="required">*</span></label>
            <select name="month" class="form-control" required>
              <?php foreach ($months as $m): ?>
              <option value="<?= $m ?>" <?= $m===date('F')?'selected':'' ?>><?= $m ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Year <span class="required">*</span></label>
            <select name="year" class="form-control" required>
              <?php for ($y=(int)date('Y'); $y>=(int)date('Y')-2; $y--): ?>
              <option value="<?= $y ?>" <?= $y===(int)date('Y')?'selected':'' ?>><?= $y ?></option>
              <?php endfor; ?>
            </select>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Payment Method <span class="required">*</span></label>
            <select name="payment_method" id="ctPayMethod" class="form-control" required
                    onchange="ctToggleMethodFields(this.value)">
              <option value="M-PESA Paybill">M-PESA Paybill</option>
              <option value="Airtel Money">Airtel Money</option>
              <option value="PDQ/POS">PDQ / POS Machine</option>
              <option value="Cash">Cash</option>
              <option value="Cheque">Cheque</option>
              <option value="Bank Transfer">Bank Transfer</option>
              <option value="Other">Other</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Transaction / Reference #</label>
            <input type="text" name="reference_number" class="form-control" placeholder="e.g. QAB123456">
          </div>
        </div>

        <!-- Cheque fields -->
        <div id="ctChequeFields" style="display:none;">
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Cheque Number</label>
              <input type="text" name="cheque_number" class="form-control" placeholder="Cheque #">
            </div>
            <div class="form-group">
              <label class="form-label">Bank</label>
              <input type="text" name="cheque_bank" class="form-control" placeholder="Bank name">
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Cheque Status</label>
            <select name="cheque_status" class="form-control">
              <option value="Pending">Pending (not yet cleared)</option>
              <option value="Cleared">Cleared</option>
              <option value="Bounced">Bounced</option>
            </select>
          </div>
          <div class="alert alert-warning" style="font-size:.82rem;">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <div>Only <strong>Cleared</strong> cheques count as fully paid.</div>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Payment Date</label>
            <input type="date" name="payment_date" class="form-control" value="<?= date('Y-m-d') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Status</label>
            <select name="status" class="form-control">
              <option value="Paid">Paid</option>
              <option value="Pending">Pending</option>
              <option value="Partial">Partial</option>
            </select>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Notes (optional)</label>
          <textarea name="notes" class="form-control" rows="2" placeholder="Any notes…"></textarea>
        </div>

        <div class="modal-footer" style="padding:0;margin-top:8px;">
          <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-lg"></i> Save Payment
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="../assets/js/main.js"></script>
<script>
function ctToggleMethodFields(method) {
  document.getElementById('ctChequeFields').style.display = method === 'Cheque' ? 'block' : 'none';
}

function ctLoadTenantRent(sel) {
  const opt  = sel.options[sel.selectedIndex];
  const rent = opt.dataset.rent;
  const unit = opt.dataset.unit;
  if (rent) {
    document.getElementById('ctPayAmount').value = rent;
    document.getElementById('ctRentHint').textContent =
      'Monthly rent' + (unit ? ' for unit ' + unit : '') + ': KES ' + parseInt(rent).toLocaleString();
  }
}
</script>
</body>
</html>
