<?php
session_start();
require_once '../includes/auth_check.php';
require_once '../includes/db_helpers.php';
requireRole('admin');

$pageTitle  = 'Payments';
$activePage = 'payments';

$filterMonth  = $_GET['month']  ?? '';
$filterYear   = (int)($_GET['year']   ?? date('Y'));
$filterStatus = $_GET['status'] ?? '';
$filterApt    = (int)($_GET['apt']    ?? 0) ?: null;
$filterTenant = (int)($_GET['tenant'] ?? 0) ?: null;


$pdo = getDB();


$columns = $pdo->query("SHOW COLUMNS FROM payments")->fetchAll(PDO::FETCH_COLUMN);
$hasPaymentType = in_array('payment_type', $columns);


if ($hasPaymentType) {
    
    $rentPayments = getPayments([
        'month'        => $filterMonth  ?: null,
        'year'         => $filterYear,
        'status'       => $filterStatus ?: null,
        'apartment_id' => $filterApt,
        'tenant_id'    => $filterTenant,
        'payment_type' => ['Rent', 'Deposit'], 
    ]);
} else {
    
    $rentPayments = getPayments([
        'month'        => $filterMonth  ?: null,
        'year'         => $filterYear,
        'status'       => $filterStatus ?: null,
        'apartment_id' => $filterApt,
        'tenant_id'    => $filterTenant,
    ]);
}


$utilityBills = [];
$hasUtilityTable = $pdo->query("SHOW TABLES LIKE 'utility_bills'")->fetchColumn();

if ($hasUtilityTable) {
    
    $utilityQuery = "
        SELECT 
            ub.*,
            u.full_name AS tenant_name,
            un.unit_number,
            a.name AS apartment_name
        FROM utility_bills ub
        JOIN users u ON u.id = ub.tenant_id
        LEFT JOIN units un ON un.id = ub.unit_id
        LEFT JOIN apartments a ON a.id = ub.apartment_id
        WHERE 1=1
    ";
    
    $params = [];
    
    if ($filterMonth) {
        $utilityQuery .= " AND ub.billing_month = :month";
        $params[':month'] = $filterMonth;
    }
    if ($filterYear) {
        $utilityQuery .= " AND ub.billing_year = :year";
        $params[':year'] = $filterYear;
    }
    if ($filterStatus) {
        $utilityQuery .= " AND ub.status = :status";
        $params[':status'] = $filterStatus;
    }
    if ($filterApt) {
        $utilityQuery .= " AND ub.apartment_id = :apt";
        $params[':apt'] = $filterApt;
    }
    if ($filterTenant) {
        $utilityQuery .= " AND ub.tenant_id = :tid";
        $params[':tid'] = $filterTenant;
    }
    
    $utilityQuery .= " ORDER BY ub.created_at DESC";
    
    $stmt = $pdo->prepare($utilityQuery);
    $stmt->execute($params);
    $utilityBills = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} elseif ($hasPaymentType) {
    
    $utilityBills = getPayments([
        'month'        => $filterMonth  ?: null,
        'year'         => $filterYear,
        'status'       => $filterStatus ?: null,
        'apartment_id' => $filterApt,
        'tenant_id'    => $filterTenant,
        'payment_type' => ['Utility'],
    ]);
}


$payments = $rentPayments;

$overdue  = getPayments(['status' => 'Overdue',  'year' => $filterYear]);
$pending  = getPayments(['status' => 'Pending',  'year' => $filterYear]);
$totals   = getMonthlyTotals($filterApt);
$apartments = getApartments();
$activeTenants = getTenants('active');


$revenue = getYearlyRevenue($filterYear);
$viewPayment = null;
if (!empty($_GET['receipt'])) {
    $pdo  = getDB();
    $stmt = $pdo->prepare(
        "SELECT p.*, u.full_name AS tenant_name, u.phone AS tenant_phone, u.email AS tenant_email,
                un.unit_number, a.name AS apartment_name, a.location,
                rb.full_name AS recorded_by_name
         FROM payments p
         JOIN users u       ON u.id = p.tenant_id
         LEFT JOIN units un ON un.id = p.unit_id
         LEFT JOIN apartments a ON a.id = p.apartment_id
         LEFT JOIN users rb  ON rb.id = p.recorded_by
         WHERE p.id = :id LIMIT 1"
    );
    $stmt->execute([':id' => (int)$_GET['receipt']]);
    $viewPayment = $stmt->fetch(PDO::FETCH_ASSOC);
}

$success = $_GET['success'] ?? '';
$error   = $_GET['error']   ?? '';
$months  = ['January','February','March','April','May','June',
            'July','August','September','October','November','December'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Payments — Rentisha Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="../assets/css/style.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
  <style>
    
    @media print {
      .rms-shell > :not(.main-content),
      .topnav, .page-header .d-flex, .filter-bar,
      .card:not(#receiptCard), .tabs, .no-print { display:none !important; }
      #receiptCard { box-shadow:none !important; border:1px solid #ccc !important; }
      body { background:#fff !important; }
    }
  </style>
</head>
<body>
<div class="rms-shell">
  <div class="sidebar-overlay" id="sidebarOverlay"></div>
  <?php include '../includes/sidebar_admin.php'; ?>

  <div class="main-content" id="mainContent">
    <?php include '../includes/topnav.php'; ?>
    <div class="page-content">

      <?php if ($success): ?>
      <div class="alert alert-success mb-4 no-print"><i class="bi bi-check-circle-fill"></i><div><?= htmlspecialchars($success) ?></div></div>
      <?php endif; ?>
      <?php if ($error): ?>
      <div class="alert alert-danger mb-4 no-print"><i class="bi bi-exclamation-circle-fill"></i><div><?= htmlspecialchars($error) ?></div></div>
      <?php endif; ?>

      <?php if ($viewPayment): ?>
      
      <div class="page-header no-print">
        <div><h1>Payment Receipt</h1><p>View and print tenant payment receipt</p></div>
        <div class="d-flex gap-2">
          <a href="payments.php" class="btn btn-outline btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
          <button class="btn btn-primary btn-sm" onclick="window.print()"><i class="bi bi-printer"></i> Print Receipt</button>
        </div>
      </div>

      <div class="card" id="receiptCard" style="max-width:620px;margin:0 auto;">
        <div style="background:linear-gradient(135deg,#001A3D,#007BFF);padding:28px 28px 20px;color:#fff;text-align:center;">
          <div style="font-size:1.8rem;font-weight:900;letter-spacing:2px;margin-bottom:4px;">RENTISHA</div>
          <div style="font-size:.8rem;opacity:.75;letter-spacing:1px;">RENTAL MANAGEMENT SYSTEM</div>
          <div style="margin-top:14px;font-size:1.1rem;font-weight:700;">PAYMENT RECEIPT</div>
          <div style="font-size:.85rem;opacity:.8;margin-top:3px;"><?= htmlspecialchars($viewPayment['apartment_name']) ?> — <?= htmlspecialchars($viewPayment['location'] ?? '') ?></div>
        </div>

        <div class="card-body">
          
          <div style="display:flex;justify-content:space-between;margin-bottom:18px;flex-wrap:wrap;gap:8px;">
            <div>
              <div style="font-size:.72rem;color:var(--text-muted);">RECEIPT NO.</div>
              <div style="font-weight:800;color:var(--primary);font-size:1rem;"><?= htmlspecialchars($viewPayment['receipt_number'] ?? 'RCP-' . str_pad($viewPayment['id'],6,'0',STR_PAD_LEFT)) ?></div>
            </div>
            <div style="text-align:right;">
              <div style="font-size:.72rem;color:var(--text-muted);">DATE ISSUED</div>
              <div style="font-weight:700;"><?= formatDate($viewPayment['payment_date'] ?? $viewPayment['created_at']) ?></div>
            </div>
          </div>

          <
          <div style="border-top:2px dashed var(--border);margin:14px 0;"></div>

          
          <div style="margin-bottom:14px;">
            <div style="font-size:.72rem;color:var(--text-muted);font-weight:700;letter-spacing:.8px;margin-bottom:6px;">RECEIVED FROM</div>
            <div style="font-size:1.05rem;font-weight:700;"><?= htmlspecialchars($viewPayment['tenant_name']) ?></div>
            <div style="font-size:.83rem;color:var(--text-secondary);">
              <?= htmlspecialchars($viewPayment['tenant_phone'] ?? '') ?>
              <?= $viewPayment['tenant_email'] ? ' · ' . htmlspecialchars($viewPayment['tenant_email']) : '' ?>
            </div>
          </div>

          
          <table style="width:100%;border-collapse:collapse;font-size:.875rem;margin-bottom:14px;">
            <?php $receiptRows = [
              ['Apartment',       $viewPayment['apartment_name']],
              ['Unit',            $viewPayment['unit_number'] ?? '—'],
              ['Payment For',     $viewPayment['month'] . ' ' . $viewPayment['year']],
              ['Payment Method',  $viewPayment['payment_method']],
              ['Reference / Txn', $viewPayment['reference_number'] ?: ($viewPayment['cheque_number'] ? 'Cheque #'.$viewPayment['cheque_number'] : '—')],
              ['Payment Date',    formatDate($viewPayment['payment_date'])],
              ['Recorded By',     $viewPayment['recorded_by_name'] ?? 'Admin'],
              ['Status',          $viewPayment['status']],
            ]; ?>
            <?php foreach ($receiptRows as [$label, $val]): ?>
            <tr style="border-bottom:1px solid var(--border);">
              <td style="padding:8px 0;color:var(--text-muted);width:40%;"><?= $label ?></td>
              <td style="padding:8px 0;font-weight:600;"><?= htmlspecialchars($val ?? '—') ?></td>
            </tr>
            <?php endforeach; ?>
          </table>

          
          <div style="background:linear-gradient(135deg,#001A3D,#007BFF);border-radius:10px;padding:16px 20px;color:#fff;display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
            <div>
              <div style="font-size:.72rem;opacity:.75;letter-spacing:.8px;">AMOUNT PAID</div>
              <div style="font-size:1.7rem;font-weight:900;"><?= formatKES($viewPayment['amount']) ?></div>
            </div>
            <div style="text-align:right;">
              <span class="badge <?= statusBadgeClass($viewPayment['status']) ?>" style="font-size:.85rem;padding:5px 14px;">
                <?= strtoupper($viewPayment['status']) ?>
              </span>
            </div>
          </div>

          <?php if ($viewPayment['cheque_number']): ?>
          <div class="alert alert-info" style="font-size:.82rem;">
            <i class="bi bi-file-earmark-check"></i>
            <div>Cheque #<?= htmlspecialchars($viewPayment['cheque_number']) ?>
              — <?= htmlspecialchars($viewPayment['cheque_bank'] ?? '') ?>
              — Status: <strong><?= htmlspecialchars($viewPayment['cheque_status'] ?? 'Pending') ?></strong>
            </div>
          </div>
          <?php endif; ?>

        
          <div style="border-top:1px dashed var(--border);margin-top:14px;padding-top:12px;text-align:center;">
            <div style="font-size:.75rem;color:var(--text-muted);">
              This is an official receipt generated by Rentisha RMS.<br>
              Thank you for your payment. — <?= htmlspecialchars(getSetting('system_name','Rentisha')) ?>
            </div>
          </div>
        </div>
      </div>

      <?php else: ?>
      
      <div class="page-header no-print">
        <div>
          <h1>Payment Records</h1>
          <p>Record, confirm and manage all rent payments</p>
        </div>
        <div class="d-flex gap-2">
          <button class="btn btn-outline btn-sm" onclick="window.print()"><i class="bi bi-printer"></i> Print</button>
          <button class="btn btn-primary" data-modal-open="recordPaymentModal">
            <i class="bi bi-plus-lg"></i> Record Payment
          </button>
        </div>
      </div>

      
      <div class="stats-grid" style="grid-template-columns:repeat(auto-fill,minmax(150px,1fr));margin-bottom:18px;">
        <div class="stat-card green">
          <div class="stat-icon"><i class="bi bi-cash-stack"></i></div>
          <div class="stat-value" style="font-size:1.1rem;"><?= formatKES($totals['collected'] ?? 0) ?></div>
          <div class="stat-label">Collected <?= date('M Y') ?></div>
        </div>
        <div class="stat-card orange">
          <div class="stat-icon"><i class="bi bi-clock-history"></i></div>
          <div class="stat-value" style="font-size:1.1rem;"><?= formatKES($totals['pending'] ?? 0) ?></div>
          <div class="stat-label">Pending <?= date('M Y') ?></div>
        </div>
        <div class="stat-card red">
          <div class="stat-icon"><i class="bi bi-exclamation-triangle"></i></div>
          <div class="stat-value" style="font-size:1.1rem;"><?= formatKES($totals['overdue'] ?? 0) ?></div>
          <div class="stat-label">Overdue <?= date('M Y') ?></div>
        </div>
        <div class="stat-card blue">
          <div class="stat-icon"><i class="bi bi-receipt"></i></div>
          <div class="stat-value" data-count="<?= $totals['paid_count'] ?? 0 ?>"><?= $totals['paid_count'] ?? 0 ?></div>
          <div class="stat-label">Paid This Month</div>
        </div>
        <div class="stat-card orange">
          <div class="stat-icon"><i class="bi bi-hourglass-split"></i></div>
          <div class="stat-value" data-count="<?= count($pending) ?>"><?= count($pending) ?></div>
          <div class="stat-label">Pending (<?= $filterYear ?>)</div>
        </div>
        <div class="stat-card red">
          <div class="stat-icon"><i class="bi bi-fire"></i></div>
          <div class="stat-value" data-count="<?= count($overdue) ?>"><?= count($overdue) ?></div>
          <div class="stat-label">Overdue (<?= $filterYear ?>)</div>
        </div>
      </div>

      
      <div class="card mb-4 no-print">
        <div class="card-header">
          <h3 class="card-title"><i class="bi bi-bar-chart-line"></i> Monthly Revenue — <?= $filterYear ?></h3>
          <form method="GET" style="display:flex;gap:8px;" class="card-actions">
            <select name="year" class="form-control" style="width:90px;" onchange="this.form.submit()">
              <?php for ($y = (int)date('Y'); $y >= (int)date('Y')-3; $y--): ?>
              <option value="<?= $y ?>" <?= $y===$filterYear?'selected':'' ?>><?= $y ?></option>
              <?php endfor; ?>
            </select>
          </form>
        </div>
        <div class="card-body">
          <div class="chart-container" style="height:220px;">
            <canvas id="revenueChart"></canvas>
          </div>
        </div>
      </div>

      
      <div class="tabs no-print" role="tablist">
        <div class="tab-item active" data-tab="tab-rent" role="tab"><i class="bi bi-house-fill"></i> Rent Payments</div>
        <div class="tab-item" data-tab="tab-utilities" role="tab">
          <i class="bi bi-lightning-charge-fill"></i> Utility Bills
          <?php if (count($utilityBills)): ?>
          <span style="background:var(--info);color:#fff;font-size:.62rem;padding:1px 6px;border-radius:10px;"><?= count($utilityBills) ?></span>
          <?php endif; ?>
        </div>
        <div class="tab-item" data-tab="tab-pending" role="tab">
          <i class="bi bi-clock-history"></i> Pending
          <?php if (count($pending)): ?>
          <span style="background:var(--warning);color:#fff;font-size:.62rem;padding:1px 6px;border-radius:10px;"><?= count($pending) ?></span>
          <?php endif; ?>
        </div>
        <div class="tab-item" data-tab="tab-overdue" role="tab">
          <i class="bi bi-exclamation-triangle"></i> Overdue
          <?php if (count($overdue)): ?>
          <span style="background:var(--danger);color:#fff;font-size:.62rem;padding:1px 6px;border-radius:10px;"><?= count($overdue) ?></span>
          <?php endif; ?>
        </div>
      </div>

      
      <div id="tab-rent" class="tab-panel active">
        
        <div class="filter-bar no-print">
          <div class="input-group search-box">
            <i class="bi bi-search input-icon"></i>
            <input type="search" class="form-control" placeholder="Search tenant, unit, reference…" data-search-table="paymentsTable">
          </div>
          <form method="GET" style="display:contents;">
            <select name="month" class="form-control" style="width:auto;" onchange="this.form.submit()">
              <option value="">All Months</option>
              <?php foreach ($months as $m): ?>
              <option value="<?= $m ?>" <?= $filterMonth===$m?'selected':'' ?>><?= $m ?></option>
              <?php endforeach; ?>
            </select>
            <select name="year" class="form-control" style="width:80px;" onchange="this.form.submit()">
              <?php for ($y = (int)date('Y'); $y >= (int)date('Y')-3; $y--): ?>
              <option value="<?= $y ?>" <?= $y===$filterYear?'selected':'' ?>><?= $y ?></option>
              <?php endfor; ?>
            </select>
            <select name="apt" class="form-control" style="width:auto;" onchange="this.form.submit()">
              <option value="">All Apartments</option>
              <?php foreach ($apartments as $apt): ?>
              <option value="<?= $apt['id'] ?>" <?= $filterApt===$apt['id']?'selected':'' ?>>
                <?= htmlspecialchars($apt['name']) ?>
              </option>
              <?php endforeach; ?>
            </select>
            <select name="status" class="form-control" style="width:auto;" onchange="this.form.submit()">
              <option value="">All Statuses</option>
              <option value="Paid"    <?= $filterStatus==='Paid'?'selected':'' ?>>Paid</option>
              <option value="Pending" <?= $filterStatus==='Pending'?'selected':'' ?>>Pending</option>
              <option value="Overdue" <?= $filterStatus==='Overdue'?'selected':'' ?>>Overdue</option>
            </select>
            <input type="hidden" name="tab" value="all">
          </form>
        </div>

        <div class="card">
          <div class="card-body p-0">
            <div class="table-wrapper">
              <table class="rms-table" id="paymentsTable">
                <thead>
                  <tr>
                    <th>#</th><th>Tenant</th><th>Unit</th><th>Apartment</th>
                    <th>Type</th><th>Period</th><th>Expected</th><th>Paid</th><th>Balance</th>
                    <th>Method</th><th>Date</th><th>Status</th>
                    <th class="col-actions">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($payments)): ?>
                  <tr>
                    <td colspan="13" style="text-align:center;padding:36px;color:var(--text-muted);">
                      <i class="bi bi-receipt" style="font-size:2.5rem;display:block;margin-bottom:8px;"></i>
                      No payments found<?= $filterMonth ? ' for '.$filterMonth.' '.$filterYear : '' ?>.
                      <br><a href="#" data-modal-open="recordPaymentModal">Record a payment</a>.
                    </td>
                  </tr>
                  <?php else: ?>
                    <?php foreach ($payments as $i => $p): ?>
                    <tr>
                      <td><?= $i + 1 ?></td>
                      <td class="fw-bold"><?= htmlspecialchars($p['tenant_name']) ?></td>
                      <td><span class="badge badge-info"><?= htmlspecialchars($p['unit_number'] ?? '—') ?></span></td>
                      <td><?= htmlspecialchars($p['apartment_name'] ?? '—') ?></td>
                      <td><span class="badge <?= ($p['payment_type'] ?? 'Rent') === 'Deposit' ? 'badge-warning' : 'badge-info' ?>">
                        <?= htmlspecialchars($p['payment_type'] ?? 'Rent') ?>
                      </span></td>
                      <td><?= $p['month'] . ' ' . $p['year'] ?></td>
                      <td style="font-size:.82rem;color:var(--text-muted);">
                        <?= $p['expected_amount'] ? formatKES($p['expected_amount']) : '—' ?>
                      </td>
                      <td class="fw-bold"><?= formatKES($p['amount']) ?></td>
                      <td>
                        <?php if ($p['balance'] !== null): ?>
                          <?php if ($p['balance'] < 0): ?>
                            <span style="color:#10b981;font-weight:700;font-size:.8rem;" title="Credit for next month">
                              +<?= formatKES(abs($p['balance'])) ?>
                            </span>
                          <?php elseif ($p['balance'] > 0): ?>
                            <span style="color:#f59e0b;font-weight:700;font-size:.8rem;" title="Remaining balance">
                              -<?= formatKES($p['balance']) ?>
                            </span>
                          <?php else: ?>
                            <span style="color:var(--text-muted);font-size:.75rem;">Full</span>
                          <?php endif; ?>
                        <?php else: ?>
                          <span style="color:var(--text-muted);font-size:.75rem;">—</span>
                        <?php endif; ?>
                      </td>
                      <td style="font-size:.78rem;"><?= htmlspecialchars($p['payment_method']) ?></td>
                      <td style="font-size:.78rem;"><?= $p['payment_date'] ? formatDate($p['payment_date']) : '—' ?></td>
                      <td>
                        <span class="badge <?= statusBadgeClass($p['status']) ?>"><?= $p['status'] ?></span>
                        <?php if ($p['payment_method'] === 'Cheque' && !empty($p['cheque_status'])): ?>
                        <br><span class="badge <?= $p['cheque_status']==='Cleared' ? 'badge-paid' : ($p['cheque_status']==='Bounced' ? 'badge-overdue' : 'badge-pending') ?>" style="font-size:.62rem;margin-top:2px;">
                          Cheque: <?= $p['cheque_status'] ?>
                        </span>
                        <?php endif; ?>
                      </td>
                      <td class="col-actions">
                        <div class="d-flex gap-1" style="justify-content:flex-end;">
                          <a href="payments.php?receipt=<?= $p['id'] ?>" class="btn btn-secondary btn-sm btn-icon" title="Receipt">
                            <i class="bi bi-receipt"></i>
                          </a>
                          <?php if ($p['status'] !== 'Paid'): ?>
                          <form method="POST" action="../includes/save_payment.php" style="display:inline;">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(rmsCsrfToken()) ?>">
                            <input type="hidden" name="action"     value="confirm">
                            <input type="hidden" name="payment_id" value="<?= $p['id'] ?>">
                            <button type="submit" class="btn btn-success btn-sm btn-icon"
                              title="<?= ($p['payment_type'] ?? 'Rent') === 'Deposit' ? 'Verify deposit' : 'Confirm as Paid' ?>"
                              onclick="return confirm('<?= ($p['payment_type'] ?? 'Rent') === 'Deposit' ? 'Verify this deposit payment and activate the tenant account?' : 'Mark this payment as Paid?' ?>')">
                              <i class="bi bi-check-circle"></i>
                            </button>
                          </form>
                          <?php endif; ?>
                          <?php if ($p['payment_method'] === 'Cheque' && !empty($p['cheque_status']) && $p['cheque_status'] !== 'Cleared'): ?>
                          <button type="button" class="btn btn-info btn-sm btn-icon"
                            title="Update Cheque Status"
                            onclick="openChequeModal(<?= $p['id'] ?>, '<?= htmlspecialchars($p['tenant_name'],ENT_QUOTES) ?>', '<?= $p['month'].' '.$p['year'] ?>', '<?= $p['cheque_status'] ?>')">
                            <i class="bi bi-file-earmark-check"></i>
                          </button>
                          <?php endif; ?>
                          <?php if ($p['status'] !== 'Paid'): ?>
                          <a href="payments.php?remind=<?= $p['id'] ?>" class="btn btn-warning btn-sm btn-icon" title="Send Reminder">
                            <i class="bi bi-send"></i>
                          </a>
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
      </div>

      
      <div id="tab-utilities" class="tab-panel">
        <div class="card">
          <div class="card-header">
            <h3 class="card-title"><i class="bi bi-lightning-charge-fill"></i> Utility Bills</h3>
            <div class="card-actions">
              <span class="badge badge-info"><?= count($utilityBills) ?> bills</span>
            </div>
          </div>
          <div class="card-body p-0">
            <div class="table-wrapper">
              <table class="rms-table" id="utilityBillsTable">
                <thead>
                  <tr>
                    <th>#</th><th>Tenant</th><th>Unit</th><th>Utility Type</th>
                    <th>Period</th><th>Amount</th><th>Method</th><th>Date</th>
                    <th>Status</th><th class="col-actions">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($utilityBills)): ?>
                  <tr>
                    <td colspan="10" style="text-align:center;padding:36px;color:var(--text-muted);">
                      <i class="bi bi-lightning-charge" style="font-size:2.5rem;display:block;margin-bottom:8px;"></i>
                      No utility bills found<?= $filterMonth ? ' for '.$filterMonth.' '.$filterYear : '' ?>.
                    </td>
                  </tr>
                  <?php else: ?>
                    <?php foreach ($utilityBills as $i => $ub): ?>
                    <tr>
                      <td><?= $i + 1 ?></td>
                      <td class="fw-bold"><?= htmlspecialchars($ub['tenant_name']) ?></td>
                      <td><span class="badge badge-info"><?= htmlspecialchars($ub['unit_number'] ?? '—') ?></span></td>
                      <td>
                        <span class="badge" style="background:var(--info);color:#fff;font-size:0.75rem;">
                          <?php 
                          $utilType = $ub['bill_type'] ?? $ub['utility_type'] ?? 'Other';
                          $icons = [
                            'Water' => '💧',
                            'Electricity' => '⚡',
                            'Garbage' => '🗑️',
                            'Security' => '🛡️',
                            'Internet' => '📡',
                            'Gas' => '🔥'
                          ];
                          echo ($icons[$utilType] ?? '📋') . ' ' . htmlspecialchars($utilType);
                          ?>
                        </span>
                      </td>
                      <td><?= htmlspecialchars($ub['billing_month'] ?? $ub['month'] ?? '') . ' ' . ($ub['billing_year'] ?? $ub['year'] ?? '') ?></td>
                      <td class="fw-bold"><?= formatKES($ub['amount']) ?></td>
                      <td style="font-size:.78rem;"><?= htmlspecialchars($ub['payment_method'] ?? '—') ?></td>
                      <td style="font-size:.78rem;"><?= !empty($ub['payment_date']) ? formatDate($ub['payment_date']) : '—' ?></td>
                      <td>
                        <span class="badge <?= statusBadgeClass($ub['status']) ?>"><?= $ub['status'] ?></span>
                      </td>
                      <td class="col-actions">
                        <div class="d-flex gap-1" style="justify-content:flex-end;">
                          <?php if ($ub['status'] !== 'Paid' && $hasUtilityTable): ?>
                          <form method="POST" action="../includes/save_utility_bill.php" style="display:inline;">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(rmsCsrfToken()) ?>">
                            <input type="hidden" name="action" value="mark_paid">
                            <input type="hidden" name="bill_id" value="<?= $ub['id'] ?>">
                            <button type="submit" class="btn btn-success btn-sm btn-icon"
                              title="Confirm as Paid"
                              onclick="return confirm('Mark this utility bill as Paid?')">
                              <i class="bi bi-check-circle"></i>
                            </button>
                          </form>
                          <?php elseif ($ub['status'] !== 'Paid'): ?>
                          <form method="POST" action="../includes/save_payment.php" style="display:inline;">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(rmsCsrfToken()) ?>">
                            <input type="hidden" name="action"     value="confirm">
                            <input type="hidden" name="payment_id" value="<?= $ub['id'] ?>">
                            <button type="submit" class="btn btn-success btn-sm btn-icon"
                              title="Confirm as Paid"
                              onclick="return confirm('Mark this utility bill as Paid?')">
                              <i class="bi bi-check-circle"></i>
                            </button>
                          </form>
                          <?php endif; ?>
                          <button class="btn btn-secondary btn-sm btn-icon" title="View Details"
                            onclick="alert('Utility Type: <?= htmlspecialchars($utilType) ?>\nAmount: <?= formatKES($ub['amount']) ?>\nReference: <?= htmlspecialchars($ub['reference_number'] ?? 'N/A') ?>\nNotes: <?= htmlspecialchars($ub['notes'] ?? 'None') ?>')">
                            <i class="bi bi-info-circle"></i>
                          </button>
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
      </div>


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
                <thead><tr><th>Tenant</th><th>Type</th><th>Unit</th><th>Period</th><th>Amount</th><th>Method</th><th>Cheque Status</th><th>Actions</th></tr></thead>
                <tbody>
                  <?php foreach ($pending as $p): ?>
                  <tr>
                    <td class="fw-bold"><?= htmlspecialchars($p['tenant_name']) ?></td>
                    <td>
                      <?php if (($p['payment_type'] ?? 'Rent') === 'Deposit'): ?>
                        <span class="badge badge-warning">Deposit</span>
                      <?php else: ?>
                        <span class="badge badge-info"><?= htmlspecialchars($p['payment_type'] ?? 'Rent') ?></span>
                      <?php endif; ?>
                    </td>
                    <td><span class="badge badge-info"><?= htmlspecialchars($p['unit_number'] ?? '—') ?></span></td>
                    <td><?= $p['month'] . ' ' . $p['year'] ?></td>
                    <td class="fw-bold"><?= formatKES($p['amount']) ?></td>
                    <td style="font-size:.8rem;"><?= htmlspecialchars($p['payment_method']) ?></td>
                    <td>
                      <?php if ($p['payment_method'] === 'Cheque' && !empty($p['cheque_status'])): ?>
                        <span class="badge <?= $p['cheque_status']==='Cleared' ? 'badge-paid' : ($p['cheque_status']==='Bounced' ? 'badge-overdue' : 'badge-pending') ?>">
                          <?= $p['cheque_status'] ?>
                        </span>
                      <?php else: ?>
                        <span style="color:var(--text-muted);font-size:.78rem;">—</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <div class="d-flex gap-2">
                        <?php if ($p['payment_method'] === 'Cheque' && !empty($p['cheque_status']) && $p['cheque_status'] !== 'Cleared'): ?>
                        <
                        <button type="button" class="btn btn-info btn-sm"
                          onclick="openChequeModal(<?= $p['id'] ?>, '<?= htmlspecialchars($p['tenant_name'],ENT_QUOTES) ?>', '<?= $p['month'].' '.$p['year'] ?>', '<?= $p['cheque_status'] ?>')">
                          <i class="bi bi-file-earmark-check"></i> Update Cheque
                        </button>
                        <?php else: ?>
                        <form method="POST" action="../includes/save_payment.php" style="display:inline;">
                          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(rmsCsrfToken()) ?>">
                          <input type="hidden" name="action"     value="confirm">
                          <input type="hidden" name="payment_id" value="<?= $p['id'] ?>">
                          <button type="submit" class="btn btn-success btn-sm"
                            onclick="return confirm('Mark as Paid?')">
                            <i class="bi bi-check-circle"></i> Confirm Paid
                          </button>
                        </form>
                        <?php endif; ?>
                        <a href="payments.php?receipt=<?= $p['id'] ?>" class="btn btn-secondary btn-sm btn-icon" title="Receipt">
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
      </div>

      
      <div id="tab-overdue" class="tab-panel">
        <?php if (empty($overdue)): ?>
        <div style="text-align:center;padding:40px;color:var(--text-muted);">
          <i class="bi bi-check2-all" style="font-size:2rem;display:block;margin-bottom:8px;color:#7ED321;"></i>
          No overdue payments for <?= $filterYear ?>!
        </div>
        <?php else: ?>
        <div class="alert alert-danger no-print">
          <i class="bi bi-exclamation-triangle-fill"></i>
          <div><strong><?= count($overdue) ?> overdue payment(s)</strong> require immediate follow-up.</div>
        </div>
        <div class="card">
          <div class="card-body p-0">
            <div class="table-wrapper">
              <table class="rms-table">
                <thead><tr><th>Tenant</th><th>Unit</th><th>Apartment</th><th>Period</th><th>Amount</th><th>Actions</th></tr></thead>
                <tbody>
                  <?php foreach ($overdue as $p): ?>
                  <tr>
                    <td>
                      <a href="tenants.php?view=<?= $p['tenant_id'] ?? '' ?>" style="font-weight:600;color:var(--primary);">
                        <?= htmlspecialchars($p['tenant_name']) ?>
                      </a>
                    </td>
                    <td><span class="badge badge-info"><?= htmlspecialchars($p['unit_number'] ?? '—') ?></span></td>
                    <td><?= htmlspecialchars($p['apartment_name'] ?? '—') ?></td>
                    <td><?= $p['month'] . ' ' . $p['year'] ?></td>
                    <td class="fw-bold text-danger"><?= formatKES($p['amount']) ?></td>
                    <td>
                      <div class="d-flex gap-2">
                        <form method="POST" action="../includes/save_payment.php" style="display:inline;">
                          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(rmsCsrfToken()) ?>">
                          <input type="hidden" name="action"     value="confirm">
                          <input type="hidden" name="payment_id" value="<?= $p['id'] ?>">
                          <button type="submit" class="btn btn-success btn-sm"
                            onclick="return confirm('Mark as Paid?')">
                            <i class="bi bi-check-circle"></i> Confirm
                          </button>
                        </form>
                        <a href="payments.php?remind=<?= $p['id'] ?>"
                           class="btn btn-warning btn-sm">
                          <i class="bi bi-send"></i> Remind
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
      </div>

      <?php endif;  ?>

    </div>
  </div>
</div>


<div class="modal-overlay" id="recordPaymentModal">
  <div class="modal modal-lg">
    <div class="modal-header">
      <h3><i class="bi bi-cash-coin"></i> Record Rent Payment</h3>
      <button class="modal-close" data-modal-close><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="modal-body">
      <form action="../includes/save_payment.php" method="POST" data-validate id="paymentForm">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(rmsCsrfToken()) ?>">
        <input type="hidden" name="action" value="record">

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Tenant <span class="required">*</span></label>
            <select name="tenant_id" id="payTenantId" class="form-control" required onchange="loadTenantRent(this)">
              <option value="">— Select Tenant —</option>
              <?php foreach ($activeTenants as $t): ?>
              <option value="<?= $t['id'] ?>"
                      data-unit="<?= htmlspecialchars($t['unit_number'] ?? '') ?>"
                      data-rent="<?= $t['unit_rent'] ?? $t['assigned_rent'] ?? 0 ?>"
                      data-apt="<?= htmlspecialchars($t['apartment_name'] ?? '') ?>">
                <?= htmlspecialchars($t['full_name']) ?>
                <?= $t['unit_number'] ? ' — '.$t['unit_number'] : '' ?>
                <?= $t['apartment_name'] ? ' ('.$t['apartment_name'].')' : '' ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Amount (KES) <span class="required">*</span></label>
            <div class="input-group">
              <i class="bi bi-currency-exchange input-icon"></i>
              <input type="number" name="amount" id="payAmount" class="form-control"
                     placeholder="Monthly rent amount" required min="1">
            </div>
            <div class="form-hint" id="rentHint"></div>
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
            <select name="payment_method" id="payMethod" class="form-control" required onchange="toggleMethodFields(this.value)">
              <option value="M-PESA Paybill">M-PESA Paybill</option>
              <option value="Airtel Money">Airtel Money</option>
              <option value="PDQ/POS">PDQ / POS Machine</option>
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

        
        <div id="chequeFields" style="display:none;">
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

        
        <div id="mpesaInfo" style="display:block;">
          <?php $paybill = getSetting('paybill_number'); if ($paybill): ?>
          <div class="alert alert-info" style="font-size:.82rem;">
            <i class="bi bi-phone-fill"></i>
            <div>Paybill: <strong><?= htmlspecialchars($paybill) ?></strong> — Account: Unit Number</div>
          </div>
          <?php endif; ?>
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

<?php if (!$viewPayment): ?>
const months    = <?= json_encode(array_map(fn($r) => substr($r['month'],0,3), $revenue)) ?>;
const collected = <?= json_encode(array_map(fn($r) => (float)$r['collected'], $revenue)) ?>;
const pending_d = <?= json_encode(array_map(fn($r) => (float)$r['pending'],   $revenue)) ?>;
const overdue_d = <?= json_encode(array_map(fn($r) => (float)$r['overdue'],   $revenue)) ?>;

const ctx = document.getElementById('revenueChart')?.getContext('2d');
if (ctx) {
  new Chart(ctx, {
    type: 'bar',
    data: {
      labels: months,
      datasets: [
        { label: 'Collected', data: collected, backgroundColor: 'rgba(126,211,33,.8)',  borderRadius: 5 },
        { label: 'Pending',   data: pending_d, backgroundColor: 'rgba(245,158,11,.75)', borderRadius: 5 },
        { label: 'Overdue',   data: overdue_d, backgroundColor: 'rgba(239,68,68,.75)',  borderRadius: 5 },
      ]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: { legend: { position: 'top' } },
      scales: {
        y: { beginAtZero: true, ticks: { callback: v => 'KES ' + (v>=1000?(v/1000)+'k':v) } },
        x: { grid: { display: false } }
      }
    }
  });
}
<?php endif; ?>


function toggleMethodFields(method) {
  document.getElementById('chequeFields').style.display = method === 'Cheque' ? 'block' : 'none';
  document.getElementById('mpesaInfo').style.display    = method === 'M-PESA Paybill' ? 'block' : 'none';
}


function loadTenantRent(sel) {
  const opt  = sel.options[sel.selectedIndex];
  const rent = opt.dataset.rent;
  const unit = opt.dataset.unit;
  const apt  = opt.dataset.apt;
  if (rent) {
    document.getElementById('payAmount').value = rent;
    document.getElementById('rentHint').textContent =
      'Monthly rent for ' + (unit||'this tenant') + (apt?' — '+apt:'') + ': KES ' + parseInt(rent).toLocaleString();
  }
}

function openChequeModal(paymentId, tenantName, period, currentStatus) {
  document.getElementById('chequePaymentId').value   = paymentId;
  document.getElementById('chequeModalTitle').textContent =
    'Update Cheque — ' + tenantName + ' (' + period + ')';
  const sel = document.getElementById('chequeStatusSelect');
  sel.value = currentStatus || 'Pending';
  document.getElementById('chequeNotes').value = '';
  toggleChequeBounceWarning(sel.value);
  openModal('updateChequeModal');
}
function toggleChequeBounceWarning(val) {
  const warn = document.getElementById('chequeBouncedWarning');
  if (warn) warn.style.display = val === 'Bounced' ? '' : 'none';
}


<?php if (isset($_GET['action']) && $_GET['action']==='record'): ?>
openModal('recordPaymentModal');
<?php endif; ?>
</script>


<div class="modal-overlay" id="updateChequeModal">
  <div class="modal" style="max-width:480px;">
    <div class="modal-header">
      <h3><i class="bi bi-file-earmark-check"></i> <span id="chequeModalTitle">Update Cheque Status</span></h3>
      <button class="modal-close" data-modal-close><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="modal-body">
      <form action="../includes/save_payment.php" method="POST" data-validate>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(rmsCsrfToken()) ?>">
        <input type="hidden" name="action"     value="update_cheque">
        <input type="hidden" name="payment_id" id="chequePaymentId" value="">

        <div class="form-group">
          <label class="form-label">New Cheque Status <span class="required">*</span></label>
          <select name="cheque_status" id="chequeStatusSelect" class="form-control" required
                  onchange="toggleChequeBounceWarning(this.value)">
            <option value="Pending">Pending — awaiting clearance</option>
            <option value="Cleared">Cleared — cheque has cleared ✔</option>
            <option value="Bounced">Bounced — cheque returned ✘</option>
          </select>
          <div class="form-hint">Setting to <strong>Cleared</strong> will automatically mark the payment as <strong>Paid</strong>.</div>
        </div>

        <div id="chequeBouncedWarning" class="alert alert-danger" style="display:none;font-size:.83rem;">
          <i class="bi bi-exclamation-triangle-fill"></i>
          <div><strong>Bounced cheque:</strong> The payment will be reverted to <strong>Pending</strong> and the tenant will be notified to settle the amount immediately.</div>
        </div>

        <div class="form-group">
          <label class="form-label">Admin Notes (optional)</label>
          <textarea name="notes" id="chequeNotes" class="form-control" rows="2"
            placeholder="e.g. Cheque cleared on bank statement dated…"></textarea>
        </div>

        <div class="modal-footer" style="padding:0;margin-top:12px;">
          <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-save"></i> Update Status
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

</body>
</html>
