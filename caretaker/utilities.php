<?php
session_start();
require_once '../includes/auth_check.php';
require_once '../includes/db_helpers.php';
requireRole('caretaker');

$pageTitle  = 'Utility Bills';
$activePage = 'utilities';

$apt = getCaretakerApartment($_SESSION['user_id']);
if (!$apt) {
    header('Location: dashboard.php?error=' . urlencode('No apartment assigned.'));
    exit;
}
$aptId = $apt['id'];

$tenants = getTenants('active', $aptId);

$pdo = getDB();
$filterMonth = $_GET['month'] ?? date('F');
$filterYear  = (int)($_GET['year'] ?? date('Y'));

$bills = getUtilityBillsByApartment($aptId);

if ($filterMonth || $filterYear) {
    $bills = array_filter($bills, function($b) use ($filterMonth, $filterYear) {
        $matchMonth = !$filterMonth || $b['billing_month'] === $filterMonth;
        $matchYear  = !$filterYear || $b['billing_year'] == $filterYear;
        return $matchMonth && $matchYear;
    });
}

$success = $_GET['success'] ?? '';
$error   = $_GET['error']   ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Utility Bills — Rentisha Caretaker</title>
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
          <h1>Utility Bills</h1>
          <p>Manage shared utility bills for <strong><?= htmlspecialchars($apt['name']) ?></strong></p>
        </div>
        <button class="btn btn-primary" data-modal-open="billModal">
          <i class="bi bi-plus-lg"></i> Add Utility Bill
        </button>
      </div>

      <!-- Summary Stats -->
      <?php
      $totalBills = count($bills);
      $paidBills = count(array_filter($bills, fn($b) => $b['status'] === 'Paid'));
      $pendingBills = count(array_filter($bills, fn($b) => $b['status'] === 'Pending'));
      $overdueBills = count(array_filter($bills, fn($b) => $b['status'] === 'Overdue'));
      ?>
      <div class="stats-grid" style="grid-template-columns:repeat(auto-fill,minmax(150px,1fr));margin-bottom:20px;">
        <div class="stat-card blue">
          <div class="stat-icon"><i class="bi bi-receipt"></i></div>
          <div class="stat-value"><?= $totalBills ?></div>
          <div class="stat-label">Total Bills</div>
        </div>
        <div class="stat-card green">
          <div class="stat-icon"><i class="bi bi-check-circle"></i></div>
          <div class="stat-value"><?= $paidBills ?></div>
          <div class="stat-label">Paid</div>
        </div>
        <div class="stat-card orange">
          <div class="stat-icon"><i class="bi bi-clock-history"></i></div>
          <div class="stat-value"><?= $pendingBills ?></div>
          <div class="stat-label">Pending</div>
        </div>
        <div class="stat-card red">
          <div class="stat-icon"><i class="bi bi-exclamation-triangle"></i></div>
          <div class="stat-value"><?= $overdueBills ?></div>
          <div class="stat-label">Overdue</div>
        </div>
      </div>

      <!-- Filter Bar -->
      <div class="filter-bar">
        <div class="input-group search-box">
          <i class="bi bi-search input-icon"></i>
          <input type="search" class="form-control" placeholder="Search bills…" data-search-table="billsTable">
        </div>
        <select class="form-control" style="width:auto;" onchange="filterBills()">
          <option value="">All Months</option>
          <?php foreach (['January','February','March','April','May','June','July','August','September','October','November','December'] as $m): ?>
          <option value="<?= $m ?>" <?= $filterMonth===$m ? 'selected' : '' ?>><?= $m ?></option>
          <?php endforeach; ?>
        </select>
        <select class="form-control" style="width:auto;" onchange="filterBills()">
          <option value="">All Years</option>
          <?php for ($y = date('Y'); $y >= date('Y')-3; $y--): ?>
          <option value="<?= $y ?>" <?= $filterYear==$y ? 'selected' : '' ?>><?= $y ?></option>
          <?php endfor; ?>
        </select>
        <select class="form-control" id="statusFilter" style="width:auto;" onchange="filterTable()">
          <option value="">All Statuses</option>
          <option value="Paid">Paid</option>
          <option value="Pending">Pending</option>
          <option value="Overdue">Overdue</option>
        </select>
      </div>

      <!-- Bills Table -->
      <div class="card">
        <div class="card-body p-0">
          <div class="table-wrapper">
            <table class="rms-table" id="billsTable">
              <thead>
                <tr>
                  <th>#</th><th>Tenant</th><th>Unit</th><th>Bill Type</th>
                  <th>Description</th><th>Period</th><th>Amount</th>
                  <th>Due Date</th><th>Status</th><th class="col-actions">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($bills)): ?>
                <tr>
                  <td colspan="10" style="text-align:center;padding:36px;color:var(--text-muted);">
                    <i class="bi bi-lightning-charge" style="font-size:2.5rem;display:block;margin-bottom:8px;"></i>
                    No utility bills yet. <a href="#" data-modal-open="billModal">Add your first bill</a>.
                  </td>
                </tr>
                <?php else: ?>
                  <?php foreach ($bills as $i => $b): ?>
                  <tr data-status="<?= $b['status'] ?>">
                    <td><?= $i + 1 ?></td>
                    <td class="fw-bold"><?= htmlspecialchars($b['tenant_name'] ?? '—') ?></td>
                    <td><span class="badge badge-info"><?= htmlspecialchars($b['unit_number'] ?? '—') ?></span></td>
                    <td>
                      <span style="display:inline-flex;align-items:center;gap:4px;">
                        <?php
                        $icons = [
                          'Water'=>'droplet-fill','Electricity'=>'lightning-charge-fill',
                          'Garbage'=>'trash-fill','Security'=>'shield-fill-check',
                          'Internet'=>'wifi','Other'=>'three-dots'
                        ];
                        $icon = $icons[$b['bill_type']] ?? 'receipt';
                        ?>
                        <i class="bi bi-<?= $icon ?>" style="color:var(--primary);"></i>
                        <?= htmlspecialchars($b['bill_type']) ?>
                      </span>
                    </td>
                    <td style="font-size:.82rem;"><?= htmlspecialchars($b['description'] ?? '—') ?></td>
                    <td><?= $b['billing_month'] . ' ' . $b['billing_year'] ?></td>
                    <td class="fw-bold"><?= formatKES($b['amount']) ?></td>
                    <td><?= formatDate($b['due_date']) ?></td>
                    <td><span class="badge <?= statusBadgeClass($b['status']) ?>"><?= $b['status'] ?></span></td>
                    <td class="col-actions">
                      <div class="d-flex gap-1" style="justify-content:flex-end;">
                        <?php if ($b['status'] !== 'Paid'): ?>
                        <form method="POST" action="../includes/save_utility_bill.php" style="display:inline;">
                          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(rmsCsrfToken()) ?>">
                          <input type="hidden" name="action" value="mark_paid">
                          <input type="hidden" name="bill_id" value="<?= $b['id'] ?>">
                          <button type="submit" class="btn btn-success btn-sm btn-icon"
                            title="Mark as Paid" onclick="return confirm('Mark this bill as Paid?')">
                            <i class="bi bi-check-circle"></i>
                          </button>
                        </form>
                        <button class="btn btn-outline btn-sm btn-icon" title="Edit"
                          onclick='editBill(<?= json_encode($b) ?>)'>
                          <i class="bi bi-pencil"></i>
                        </button>
                        <form method="POST" action="../includes/save_utility_bill.php" style="display:inline;"
                          onsubmit="return confirm('Delete this bill?')">
                          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(rmsCsrfToken()) ?>">
                          <input type="hidden" name="action" value="delete">
                          <input type="hidden" name="bill_id" value="<?= $b['id'] ?>">
                          <button type="submit" class="btn btn-danger btn-sm btn-icon" title="Delete">
                            <i class="bi bi-trash"></i>
                          </button>
                        </form>
                        <?php else: ?>
                        <span class="badge badge-paid"><i class="bi bi-check-circle"></i> Paid</span>
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

    </div><!-- /page-content -->
  </div><!-- /main-content -->
</div><!-- /rms-shell -->

<!-- ═══════════ ADD / EDIT BILL MODAL ═══════════ -->
<div class="modal-overlay" id="billModal">
  <div class="modal modal-lg">
    <div class="modal-header">
      <h3 id="billModalTitle"><i class="bi bi-lightning-charge"></i> Add Utility Bill</h3>
      <button class="modal-close" data-modal-close><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="modal-body">
      <form action="../includes/save_utility_bill.php" method="POST" data-validate id="billForm">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(rmsCsrfToken()) ?>">
        <input type="hidden" name="action" value="create">
        <input type="hidden" name="bill_id" id="billId" value="">
        <input type="hidden" name="apartment_id" value="<?= $aptId ?>">

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Tenant <span class="required">*</span></label>
            <select name="tenant_id" id="billTenant" class="form-control" required onchange="updateUnitFromTenant()">
              <option value="">— Select Tenant —</option>
              <?php foreach ($tenants as $t): ?>
              <option value="<?= $t['id'] ?>" data-unit="<?= $t['unit_id'] ?>"><?= htmlspecialchars($t['full_name']) ?> - Unit <?= htmlspecialchars($t['unit_number']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Bill Type <span class="required">*</span></label>
            <select name="bill_type" id="billType" class="form-control" required>
              <option value="Water">💧 Water</option>
              <option value="Electricity">⚡ Electricity</option>
              <option value="Garbage">🗑️ Garbage Collection</option>
              <option value="Security">🛡️ Security</option>
              <option value="Internet">📡 Internet</option>
              <option value="Other">📋 Other</option>
            </select>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Description</label>
          <input type="text" name="description" id="billDesc" class="form-control" 
            placeholder="e.g., Water usage for January 2024">
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Amount (KES) <span class="required">*</span></label>
            <input type="number" name="amount" id="billAmount" class="form-control" 
              placeholder="e.g., 500" required min="1" step="0.01">
          </div>
          <div class="form-group">
            <label class="form-label">Due Date <span class="required">*</span></label>
            <input type="date" name="due_date" id="billDue" class="form-control" required>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Billing Month <span class="required">*</span></label>
            <select name="billing_month" id="billMonth" class="form-control" required>
              <?php foreach (['January','February','March','April','May','June','July','August','September','October','November','December'] as $m): ?>
              <option value="<?= $m ?>" <?= $m===date('F') ? 'selected' : '' ?>><?= $m ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Billing Year <span class="required">*</span></label>
            <select name="billing_year" id="billYear" class="form-control" required>
              <?php for ($y = date('Y')+1; $y >= date('Y')-2; $y--): ?>
              <option value="<?= $y ?>" <?= $y==date('Y') ? 'selected' : '' ?>><?= $y ?></option>
              <?php endfor; ?>
            </select>
          </div>
        </div>

        <div class="modal-footer" style="padding:0;margin-top:8px;">
          <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
          <button type="submit" class="btn btn-primary" id="billSubmitBtn">
            <i class="bi bi-check-lg"></i> Save Bill
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="../assets/js/main.js"></script>
<script>
function updateUnitFromTenant() {
  const sel = document.getElementById('billTenant');
  const opt = sel.options[sel.selectedIndex];
}

function editBill(bill) {
  document.getElementById('billModalTitle').innerHTML = '<i class="bi bi-pencil-square"></i> Edit Utility Bill';
  document.getElementById('billSubmitBtn').innerHTML  = '<i class="bi bi-check-lg"></i> Update Bill';
  document.querySelector('[name="action"]').value = 'update';
  document.getElementById('billId').value = bill.id;
  document.getElementById('billTenant').value = bill.tenant_id || '';
  document.getElementById('billType').value = bill.bill_type || 'Water';
  document.getElementById('billDesc').value = bill.description || '';
  document.getElementById('billAmount').value = bill.amount || '';
  document.getElementById('billDue').value = bill.due_date || '';
  document.getElementById('billMonth').value = bill.billing_month || '<?= date('F') ?>';
  document.getElementById('billYear').value = bill.billing_year || '<?= date('Y') ?>';
  openModal('billModal');
}

document.querySelector('[data-modal-open="billModal"]')?.addEventListener('click', function() {
  document.getElementById('billForm').reset();
  document.getElementById('billId').value = '';
  document.querySelector('[name="action"]').value = 'create';
  document.getElementById('billModalTitle').innerHTML = '<i class="bi bi-lightning-charge"></i> Add Utility Bill';
  document.getElementById('billSubmitBtn').innerHTML = '<i class="bi bi-check-lg"></i> Save Bill';
});

function filterTable() {
  const val = document.getElementById('statusFilter').value.toLowerCase();
  document.querySelectorAll('#billsTable tbody tr[data-status]').forEach(row => {
    row.style.display = (!val || row.dataset.status.toLowerCase() === val) ? '' : 'none';
  });
}

function filterBills() {
  const month = document.querySelector('select[onchange="filterBills()"]').value;
  const year = document.querySelectorAll('select[onchange="filterBills()"]')[1].value;
  window.location.href = 'utilities.php?month=' + month + '&year=' + year;
}
</script>
</body>
</html>
