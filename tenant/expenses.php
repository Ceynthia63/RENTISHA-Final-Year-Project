<?php
session_start();
require_once '../includes/auth_check.php';
require_once '../includes/db_helpers.php';
requireActiveTenant();

$pageTitle  = 'Shared Expenses';
$activePage = 'expenses';

$tenantId = (int)$_SESSION['user_id'];
$bills    = getUtilityBillsForTenant($tenantId);

$pending = array_filter($bills, fn($b) => $b['status']==='Pending' || $b['status']==='Overdue');
$totalOwed = array_sum(array_column($pending, 'amount'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Shared Expenses — Rentisha</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="rms-shell">
  <div class="sidebar-overlay" id="sidebarOverlay"></div>
  <?php include '../includes/sidebar_tenant.php'; ?>
  <div class="main-content" id="mainContent">
    <?php include '../includes/topnav.php'; ?>
    <div class="page-content">

      <div class="page-header">
        <div><h1>Shared Expenses & Bills</h1><p>Water, electricity, garbage and other utility charges</p></div>
      </div>

      <?php if ($totalOwed > 0): ?>
      <div class="alert alert-warning mb-4">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <div><strong>Total outstanding: <?= formatKES($totalOwed) ?></strong> — please pay pending bills promptly.</div>
      </div>
      <?php endif; ?>

      <!-- Stats -->
      <?php
        $typeIcons = ['Water'=>'bi-droplet-fill','Electricity'=>'bi-lightning-fill',
                      'Garbage'=>'bi-trash3-fill','Security'=>'bi-shield-fill',
                      'Cleaning'=>'bi-stars','Other'=>'bi-receipt'];
        $groupedPending = [];
        foreach ($pending as $b) {
            $groupedPending[$b['bill_type']] = ($groupedPending[$b['bill_type']] ?? 0) + $b['amount'];
        }
      ?>
      <?php if (!empty($groupedPending)): ?>
      <div class="stats-grid" style="grid-template-columns:repeat(auto-fill,minmax(140px,1fr));margin-bottom:18px;">
        <?php foreach ($groupedPending as $type => $amt): ?>
        <div class="stat-card orange">
          <div class="stat-icon"><i class="bi <?= $typeIcons[$type] ?? 'bi-receipt' ?>"></i></div>
          <div class="stat-value" style="font-size:1rem;"><?= formatKES($amt) ?></div>
          <div class="stat-label"><?= $type ?> Pending</div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <div class="card">
        <div class="card-header">
          <h3 class="card-title"><i class="bi bi-receipt-cutoff"></i> Utility Bills</h3>
        </div>
        <div class="card-body p-0">
          <?php if (empty($bills)): ?>
          <div style="text-align:center;padding:36px;color:var(--text-muted);">
            <i class="bi bi-lightning-charge" style="font-size:2.5rem;display:block;margin-bottom:8px;"></i>
            No utility bills issued yet.
          </div>
          <?php else: ?>
          <div class="table-wrapper">
            <table class="rms-table">
              <thead>
                <tr><th>Type</th><th>Description</th><th>Period</th><th>Amount</th><th>Due Date</th><th>Paid Date</th><th>Status</th></tr>
              </thead>
              <tbody>
                <?php foreach ($bills as $b): 
                  $iconMap = [
                    'Water'=>'droplet-fill', 'Electricity'=>'lightning-charge-fill',
                    'Garbage'=>'trash-fill', 'Security'=>'shield-fill-check',
                    'Internet'=>'wifi', 'Other'=>'receipt'
                  ];
                  $icon = $iconMap[$b['bill_type']] ?? 'receipt';
                  $isOverdue = ($b['status'] === 'Overdue' || ($b['status'] === 'Pending' && $b['due_date'] < date('Y-m-d')));
                ?>
                <tr style="<?= $isOverdue ? 'background:#fff5f5;' : '' ?>">
                  <td>
                    <span style="display:inline-flex;align-items:center;gap:6px;">
                      <i class="bi bi-<?= $icon ?>" style="font-size:1.1rem;color:var(--primary);"></i>
                      <strong><?= htmlspecialchars($b['bill_type']) ?></strong>
                    </span>
                  </td>
                  <td style="font-size:.85rem;"><?= htmlspecialchars($b['description'] ?? '—') ?></td>
                  <td><?= $b['billing_month'].' '.$b['billing_year'] ?></td>
                  <td class="fw-bold" style="<?= $b['status']==='Paid' ? 'color:#10b981;' : 'color:#f59e0b;' ?>">
                    <?= formatKES($b['amount']) ?>
                  </td>
                  <td><?= formatDate($b['due_date']) ?></td>
                  <td><?= $b['paid_date'] ? formatDate($b['paid_date']) : '—' ?></td>
                  <td>
                    <span class="badge <?= statusBadgeClass($b['status']) ?>">
                      <?= $b['status'] ?>
                    </span>
                    <?php if ($isOverdue && $b['status'] !== 'Paid'): ?>
                      <span class="badge badge-overdue" style="font-size:.7rem;margin-left:4px;">⚠️ Overdue</span>
                    <?php endif; ?>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
              </thead>
              <tbody>
                <?php foreach ($bills as $b): ?>
                <tr>
                  <td>
                    <i class="bi <?= $typeIcons[$b['bill_type']] ?? 'bi-receipt' ?>" style="margin-right:5px;color:var(--primary);"></i>
                    <strong><?= htmlspecialchars($b['bill_type']) ?></strong>
                  </td>
                  <td style="font-size:.8rem;color:var(--text-muted);"><?= htmlspecialchars($b['description'] ?? '—') ?></td>
                  <td><?= htmlspecialchars($b['billing_month']).' '.$b['billing_year'] ?></td>
                  <td class="fw-bold"><?= formatKES($b['amount']) ?></td>
                  <td><?= $b['due_date'] ? formatDate($b['due_date']) : '—' ?></td>
                  <td><span class="badge <?= statusBadgeClass($b['status']) ?>"><?= $b['status'] ?></span></td>
                  <td>
                    <?php if ($b['status']==='Paid'): ?>
                    <span class="badge badge-paid" style="font-size:.68rem;"><i class="bi bi-check-circle"></i> Paid <?= $b['paid_date'] ? formatDate($b['paid_date']) : '' ?></span>
                    <?php else: ?><span style="font-size:.75rem;color:var(--text-muted);">Not paid</span><?php endif; ?>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?php endif; ?>
        </div>
      </div>

      <div class="alert alert-info mt-4">
        <i class="bi bi-info-circle-fill"></i>
        <div>Utility bills are issued by your caretaker. Contact them for payment arrangements or queries.</div>
      </div>

    </div>
  </div>
</div>
<script src="../assets/js/main.js"></script>
</body>
</html>
