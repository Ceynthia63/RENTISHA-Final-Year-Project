<?php
session_start();
require_once '../includes/auth_check.php';
require_once '../includes/db_helpers.php';
requireRole('tenant');

$pageTitle  = 'Rent & Payments';
$activePage = 'payments';

$tenantId    = (int)$_SESSION['user_id'];
$tenant      = getTenantById($tenantId);
$curMonth    = date('F');
$curYear     = (int)date('Y');
$yearly      = getTenantYearlyPayments($tenantId, $curYear);
$history     = getTenantPaymentHistory($tenantId);
$outstanding = getTenantOutstanding($tenantId);
$currentOutstanding = null;
foreach ($outstanding['rows'] as $outstandingRow) {
    if ($outstandingRow['month'] === $curMonth && (int)$outstandingRow['year'] === $curYear) {
        $currentOutstanding = (float)$outstandingRow['amount'];
        break;
    }
}
if ($currentOutstanding !== null && !$yearly[$curMonth]['status']) {
    $yearly[$curMonth]['amount'] = $currentOutstanding;
    $yearly[$curMonth]['status'] = 'Pending';
}
$paybill     = getSetting('paybill_number');

$receiptPayId = (int)($_GET['receipt'] ?? 0);
$viewReceipt  = null;
if ($receiptPayId) {
    $pdo = getDB();
    $rs  = $pdo->prepare(
        "SELECT p.*, u.full_name AS tenant_name, u.phone AS tenant_phone,
                un.unit_number, a.name AS apartment_name, a.location,
                rb.full_name AS recorded_by_name
         FROM payments p
         JOIN users u ON u.id=p.tenant_id
         LEFT JOIN units un ON un.id=p.unit_id
         LEFT JOIN apartments a ON a.id=p.apartment_id
         LEFT JOIN users rb ON rb.id=p.recorded_by
         WHERE p.id=:id AND p.tenant_id=:tid LIMIT 1"
    );
    $rs->execute([':id' => $receiptPayId, ':tid' => $tenantId]);
    $viewReceipt = $rs->fetch(PDO::FETCH_ASSOC);
}

$months = ['January','February','March','April','May','June',
           'July','August','September','October','November','December'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Payments — Rentisha</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="../assets/css/style.css">
  <style>
    .method-card {
      border:2px solid var(--border);border-radius:var(--radius-lg);
      padding:16px;cursor:pointer;transition:var(--transition);text-align:center;
    }
    .method-card:hover,.method-card.selected{border-color:var(--primary);background:var(--primary-pale);}
    .method-card i{font-size:1.8rem;display:block;margin-bottom:6px;}
    @media print{.rms-shell>:not(.main-content),.topnav,.tabs,.filter-bar,.no-print{display:none!important;}
    #receiptCard{box-shadow:none!important;border:1px solid #ccc!important;}}
  </style>
</head>
<body>
<div class="rms-shell">
  <div class="sidebar-overlay" id="sidebarOverlay"></div>
  <?php include '../includes/sidebar_tenant.php'; ?>
  <div class="main-content" id="mainContent">
    <?php include '../includes/topnav.php'; ?>
    <div class="page-content">

      <?php if (!empty($_GET['success'])): ?>
      <div class="alert alert-success mb-4"><i class="bi bi-check-circle-fill"></i><div><?= htmlspecialchars($_GET['success']) ?></div></div>
      <?php endif; ?>
      <?php if (!empty($_GET['error'])): ?>
      <div class="alert alert-danger mb-4"><i class="bi bi-exclamation-circle-fill"></i><div><?= htmlspecialchars($_GET['error']) ?></div></div>
      <?php endif; ?>

      <?php if ($viewReceipt): ?>
      <!-- RECEIPT VIEW -->
      <div class="page-header no-print">
        <div><h1>Payment Receipt</h1></div>
        <div class="d-flex gap-2">
          <a href="payments.php" class="btn btn-outline btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
          <button class="btn btn-primary btn-sm" onclick="window.print()"><i class="bi bi-printer"></i> Print</button>
        </div>
      </div>
      <div class="card" id="receiptCard" style="max-width:580px;margin:0 auto;">
        <div style="background:linear-gradient(135deg,#001A3D,#007BFF);padding:26px;color:#fff;text-align:center;border-radius:var(--radius-xl) var(--radius-xl) 0 0;">
          <div style="font-size:1.7rem;font-weight:900;letter-spacing:2px;">RENTISHA</div>
          <div style="font-size:.75rem;opacity:.7;margin-top:2px;">RENTAL MANAGEMENT SYSTEM</div>
          <div style="margin-top:12px;font-size:1rem;font-weight:700;">OFFICIAL PAYMENT RECEIPT</div>
        </div>
        <div class="card-body">
          <div style="display:flex;justify-content:space-between;margin-bottom:14px;">
            <div><div style="font-size:.7rem;color:var(--text-muted);">RECEIPT NO.</div>
              <div style="font-weight:800;color:var(--primary);"><?= htmlspecialchars($viewReceipt['receipt_number'] ?? 'RCP-'.str_pad($viewReceipt['id'],6,'0',STR_PAD_LEFT)) ?></div></div>
            <div style="text-align:right;"><div style="font-size:.7rem;color:var(--text-muted);">DATE</div>
              <div style="font-weight:700;"><?= formatDate($viewReceipt['payment_date'] ?? $viewReceipt['created_at']) ?></div></div>
          </div>
          <div style="border-top:2px dashed var(--border);margin:12px 0;"></div>
          <table style="width:100%;border-collapse:collapse;font-size:.875rem;">
            <?php foreach ([
              ['Tenant',     $viewReceipt['tenant_name']],
              ['Apartment',  $viewReceipt['apartment_name']],
              ['Unit',       $viewReceipt['unit_number'] ?? '—'],
              ['Period',     $viewReceipt['month'].' '.$viewReceipt['year']],
              ['Method',     $viewReceipt['payment_method']],
              ['Reference',  $viewReceipt['reference_number'] ?: '—'],
              ['Date Paid',  formatDate($viewReceipt['payment_date'])],
              ['Confirmed by',$viewReceipt['recorded_by_name'] ?? 'Admin'],
              ['Status',     $viewReceipt['status']],
            ] as [$l,$v]): ?>
            <tr style="border-bottom:1px solid var(--border);">
              <td style="padding:7px 0;color:var(--text-muted);width:42%;"><?= $l ?></td>
              <td style="padding:7px 0;font-weight:600;"><?= htmlspecialchars($v ?? '—') ?></td>
            </tr>
            <?php endforeach; ?>
          </table>
          <div style="background:linear-gradient(135deg,#001A3D,#007BFF);border-radius:10px;padding:14px 18px;color:#fff;display:flex;justify-content:space-between;align-items:center;margin:16px 0 10px;">
            <div><div style="font-size:.7rem;opacity:.7;">AMOUNT PAID</div>
              <div style="font-size:1.7rem;font-weight:900;"><?= formatKES($viewReceipt['amount']) ?></div></div>
            <span class="badge <?= statusBadgeClass($viewReceipt['status']) ?>" style="font-size:.82rem;padding:5px 12px;">
              <?= strtoupper($viewReceipt['status']) ?>
            </span>
          </div>
          <div style="text-align:center;font-size:.72rem;color:var(--text-muted);border-top:1px dashed var(--border);padding-top:10px;">
            Official receipt from Rentisha RMS. Thank you for your payment.
          </div>
        </div>
      </div>

      <?php else: ?>
      <!-- PAYMENTS PAGE -->
      <div class="page-header no-print">
        <div><h1>Rent &amp; Payments</h1><p>Pay rent, view history and download receipts</p></div>
      </div>

      <?php if ($outstanding['total'] > 0): ?>
      <div class="alert alert-danger no-print">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <div>
          <strong>Outstanding balance: <?= formatKES($outstanding['total']) ?></strong>
          <?php if ($outstanding['earliest']): ?>
          — Earliest unpaid: <strong><?= $outstanding['earliest']['month'] ?> <?= $outstanding['earliest']['year'] ?></strong>.
          Please clear in order.
          <?php endif; ?>
        </div>
      </div>
      <?php endif; ?>

      <!-- Tabs -->
      <div class="tabs no-print" role="tablist">
        <div class="tab-item active" data-tab="tp-summary"  role="tab"><i class="bi bi-calendar3"></i> <?= $curYear ?> Summary</div>
        <div class="tab-item"        data-tab="tp-history"  role="tab"><i class="bi bi-clock-history"></i> History</div>
        <div class="tab-item"        data-tab="tp-methods"  role="tab"><i class="bi bi-credit-card"></i> Payment Methods</div>
      </div>

      <!-- Summary tab -->
      <div id="tp-summary" class="tab-panel active">
        <div class="card">
          <div class="card-header">
            <h3 class="card-title"><i class="bi bi-calendar3"></i> <?= $curYear ?> Rent Records</h3>
            <div>Monthly rent: <strong><?= formatKES($tenant['unit_rent'] ?? $tenant['assigned_rent'] ?? 0) ?></strong></div>
          </div>
          <div class="card-body">
            <div class="months-grid">
              <?php foreach ($yearly as $month => $p): ?>
              <div class="month-cell <?= $p['status'] ? strtolower($p['status']) : '' ?>">
                <div class="month-name"><?= substr($month,0,3) ?></div>
                <div class="month-amount" style="font-size:.75rem;"><?= $p['amount'] ? formatKES($p['amount']) : '—' ?></div>
                <div class="month-status" style="margin-top:4px;">
                  <?php if ($p['status']==='Paid'): ?><span class="badge badge-paid" style="font-size:.58rem;">Paid</span>
                  <?php elseif ($p['status']==='Overdue'): ?><span class="badge badge-overdue" style="font-size:.58rem;">Late</span>
                  <?php elseif ($p['status']==='Pending'): ?><span class="badge badge-pending" style="font-size:.58rem;">Due</span>
                  <?php else: ?><span style="color:var(--text-muted);font-size:.65rem;">—</span>
                  <?php endif; ?>
                </div>
                <?php if ($p['receipt_number'] && $p['status']==='Paid'): ?>
                <div style="margin-top:4px;">
                  <a href="?receipt=<?php
                    $pdo3 = getDB();
                    $pid = $pdo3->prepare("SELECT id FROM payments WHERE tenant_id=:tid AND month=:m AND year=:y LIMIT 1");
                    $pid->execute([':tid'=>$tenantId,':m'=>$month,':y'=>$curYear]);
                    echo (int)$pid->fetchColumn();
                  ?>" style="font-size:.6rem;color:var(--primary);">Receipt</a>
                </div>
                <?php endif; ?>
              </div>
              <?php endforeach; ?>
            </div>
            <!-- Totals -->
            <?php
              $paid   = array_sum(array_column(array_filter($yearly, fn($p) => $p['status']==='Paid'), 'amount'));
              $unpaid = (float)$outstanding['total'];
              $paidMo = count(array_filter($yearly, fn($p) => $p['status']==='Paid'));
            ?>
            <div class="d-flex gap-3 mt-4" style="flex-wrap:wrap;">
              <div style="flex:1;min-width:120px;padding:12px;background:var(--bg);border-radius:8px;text-align:center;">
                <div style="font-size:1rem;font-weight:800;color:#7ED321;"><?= formatKES($paid) ?></div>
                <div style="font-size:.72rem;color:var(--text-muted);">Total Paid <?= $curYear ?></div>
              </div>
              <div style="flex:1;min-width:120px;padding:12px;background:var(--bg);border-radius:8px;text-align:center;">
                <div style="font-size:1rem;font-weight:800;color:#f59e0b;"><?= formatKES($unpaid) ?></div>
                <div style="font-size:.72rem;color:var(--text-muted);">Outstanding</div>
              </div>
              <div style="flex:1;min-width:120px;padding:12px;background:var(--bg);border-radius:8px;text-align:center;">
                <div style="font-size:1rem;font-weight:800;"><?= $paidMo ?> / 12</div>
                <div style="font-size:.72rem;color:var(--text-muted);">Months Paid</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- History tab -->
      <div id="tp-history" class="tab-panel">
        <div class="card">
          <div class="card-body p-0">
            <div class="table-wrapper">
              <table class="rms-table" id="payHistTable">
                <thead>
                  <tr><th>Period</th><th>Expected</th><th>Paid</th><th>Balance</th><th>Method</th><th>Date Paid</th><th>Status</th><th>Receipt</th></tr>
                </thead>
                <tbody>
                  <?php if (empty($history)): ?>
                  <tr><td colspan="8" style="text-align:center;padding:28px;color:var(--text-muted);">No payment history yet.</td></tr>
                  <?php else: ?>
                    <?php foreach ($history as $p): ?>
                    <tr>
                      <td class="fw-bold">
                        <?php if (($p['payment_type'] ?? 'Rent') === 'Deposit'): ?>
                          <span class="badge badge-warning">Deposit</span>
                        <?php else: ?>
                          <?= $p['month'].' '.$p['year'] ?>
                        <?php endif; ?>
                      </td>
                      <td style="font-size:.82rem;color:var(--text-muted);">
                        <?= $p['expected_amount'] ? formatKES($p['expected_amount']) : '—' ?>
                      </td>
                      <td class="fw-bold <?= $p['status']==='Paid'?'text-success':'' ?>"><?= formatKES($p['amount']) ?></td>
                      <td>
                        <?php if ($p['balance'] !== null): ?>
                          <?php if ($p['balance'] < 0): ?>
                            <span style="color:#10b981;font-weight:700;font-size:.82rem;">
                              Credit: <?= formatKES(abs($p['balance'])) ?>
                            </span>
                          <?php elseif ($p['balance'] > 0): ?>
                            <span style="color:#f59e0b;font-weight:700;font-size:.82rem;">
                              Owed: <?= formatKES($p['balance']) ?>
                            </span>
                          <?php else: ?>
                            <span style="color:var(--text-muted);font-size:.75rem;">—</span>
                          <?php endif; ?>
                        <?php else: ?>
                          <span style="color:var(--text-muted);font-size:.75rem;">—</span>
                        <?php endif; ?>
                      </td>
                      <td style="font-size:.78rem;"><?= htmlspecialchars($p['payment_method']) ?></td>
                      <td><?= $p['payment_date'] ? formatDate($p['payment_date']) : '—' ?></td>
                      <td><span class="badge <?= statusBadgeClass($p['status']) ?>"><?= $p['status'] ?></span></td>
                      <td>
                        <?php if ($p['status']==='Paid'): ?>
                        <a href="?receipt=<?= $p['id'] ?>" class="btn btn-secondary btn-sm">
                          <i class="bi bi-receipt"></i> View
                        </a>
                        <?php else: ?>—<?php endif; ?>
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

      <!-- Payment methods tab -->
      <div id="tp-methods" class="tab-panel">
        <div class="grid-2">
          <!-- M-PESA info -->
          <div class="card">
            <div class="card-header">
              <h3 class="card-title" style="color:#10b981;"><i class="bi bi-phone-fill"></i> M-PESA Paybill</h3>
            </div>
            <div class="card-body">
              <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:var(--radius-md);padding:20px;text-align:center;margin-bottom:16px;">
                <?php if ($paybill): ?>
                <div style="font-size:.75rem;color:#6b7280;margin-bottom:4px;">PAYBILL NUMBER</div>
                <div style="font-size:2rem;font-weight:900;color:#10b981;letter-spacing:2px;"><?= htmlspecialchars($paybill) ?></div>
                <div style="font-size:.78rem;color:#6b7280;margin-top:8px;">
                  Account: <strong><?= htmlspecialchars($tenant['unit_number'] ?? 'Unit Number') ?></strong>
                </div>
                <?php else: ?>
                <div style="color:var(--text-muted);font-size:.85rem;">Paybill not configured yet.<br>Contact administrator.</div>
                <?php endif; ?>
              </div>
              <div style="font-size:.83rem;color:var(--text-secondary);line-height:1.8;">
                <strong>How to Pay:</strong><br>
                1. Go to M-PESA → Lipa na M-PESA → Paybill<br>
                2. Enter Business No: <strong><?= htmlspecialchars($paybill ?: 'See admin') ?></strong><br>
                3. Account No: <strong><?= htmlspecialchars($tenant['unit_number'] ?? 'Your unit number') ?></strong><br>
                4. Enter amount and your M-PESA PIN<br>
                5. Keep your transaction code (e.g. QAB123456)
              </div>
            </div>
          </div>
          <!-- Other methods -->
          <div class="card">
            <div class="card-header"><h3 class="card-title"><i class="bi bi-wallet2"></i> Other Payment Methods</h3></div>
            <div class="card-body">
              <?php foreach ([
                ['bi-phone text-danger','Airtel Money','Pay via Airtel Money to the number provided by your caretaker.'],
                ['bi-bank text-primary','Bank Transfer','Transfer to the account details provided by your landlord.'],
                ['bi-file-earmark-text text-warning','Cheque','Payable to the property owner. Confirm clearance with admin.'],
              ] as [$ic,$method,$note]): ?>
              <div style="display:flex;align-items:flex-start;gap:12px;padding:12px 0;border-bottom:1px solid var(--border);">
                <i class="bi <?= $ic ?>" style="font-size:1.3rem;flex-shrink:0;margin-top:2px;"></i>
                <div>
                  <div style="font-weight:700;font-size:.875rem;"><?= $method ?></div>
                  <div style="font-size:.78rem;color:var(--text-muted);"><?= $note ?></div>
                </div>
              </div>
              <?php endforeach; ?>
              <div class="alert alert-info mt-4" style="font-size:.82rem;margin-bottom:0;">
                <i class="bi bi-info-circle-fill"></i>
                <div>All payments must be confirmed by the administrator before appearing as <strong>Paid</strong>.</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <?php endif; // end receipt/list ?>

    </div>
  </div>
</div>
<script src="../assets/js/main.js"></script>
</body>
</html>
