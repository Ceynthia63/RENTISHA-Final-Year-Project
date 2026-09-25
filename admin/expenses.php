<?php

session_start();
require_once '../includes/auth_check.php';
require_once '../includes/db_helpers.php';
requireRole('admin');

$pageTitle  = 'Maintenance Expenses';
$activePage = 'expenses';

$pdo = getDB();

$filterType   = trim($_GET['type']   ?? '');
$filterApt    = (int)($_GET['apt']   ?? 0) ?: null;
$filterStatus = trim($_GET['status'] ?? '');
$filterYear   = (int)($_GET['year']  ?? date('Y'));
$filterMonth  = trim($_GET['month']  ?? '');

$expenseTypes = ['Plumbing','Electrical','Structural','Appliances',
                 'Security','Cleaning','Pest Control','Locks & Keys',
                 'Painting','General Maintenance','Other'];
$costCategories = ['Labour','Materials','Equipment','Contractor','Permit','Other'];
$allMonths = ['January','February','March','April','May','June',
              'July','August','September','October','November','December'];

$where      = ['1=1'];
$positional = [];

if ($filterType)   { $where[] = 'se.expense_type=?';   $positional[] = $filterType; }
if ($filterApt)    { $where[] = 'se.apartment_id=?';   $positional[] = $filterApt; }
if ($filterStatus) { $where[] = 'se.status=?';         $positional[] = $filterStatus; }
if ($filterYear)   { $where[] = 'se.year=?';           $positional[] = $filterYear; }
if ($filterMonth)  { $where[] = 'se.month=?';          $positional[] = $filterMonth; }

$sql = "SELECT se.*,
               a.name       AS apartment_name,
               pb.full_name AS paid_by_name
        FROM shared_expenses se
        LEFT JOIN apartments a  ON a.id  = se.apartment_id
        LEFT JOIN users      pb ON pb.id = se.paid_by
        WHERE " . implode(' AND ', $where) . "
        ORDER BY
          COALESCE(se.expense_date, se.created_at) DESC,
          se.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($positional);
$expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);


$statsStmt = $pdo->prepare(
    "SELECT
       COALESCE(SUM(amount),0)                                          AS total_spent,
       COALESCE(SUM(CASE WHEN status='Pending'   THEN amount END),0)   AS pending,
       COALESCE(SUM(CASE WHEN status='Paid'      THEN amount END),0)   AS paid,
       COUNT(*)                                                          AS total_entries,
       COUNT(DISTINCT apartment_id)                                      AS apartments_affected
     FROM shared_expenses
     WHERE year = ?"
);
$statsStmt->execute([$filterYear]);
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);


$topStmt = $pdo->prepare(
    "SELECT expense_type, SUM(amount) AS total
     FROM shared_expenses WHERE year=? GROUP BY expense_type ORDER BY total DESC LIMIT 1"
);
$topStmt->execute([$filterYear]);
$topCategory = $topStmt->fetch(PDO::FETCH_ASSOC);


$monthlyStmt = $pdo->prepare(
    "SELECT month, COALESCE(SUM(amount),0) AS total
     FROM shared_expenses WHERE year=? GROUP BY month"
);
$monthlyStmt->execute([$filterYear]);
$monthlyRaw = $monthlyStmt->fetchAll(PDO::FETCH_KEY_PAIR);
$monthlyData = array_map(fn($m) => (float)($monthlyRaw[$m] ?? 0), $allMonths);


$apartments = getApartments('Active');
$allAdmins  = $pdo->query(
    "SELECT id, full_name FROM users WHERE role IN ('admin','caretaker') AND status='active' ORDER BY full_name"
)->fetchAll(PDO::FETCH_ASSOC);

$success = $_GET['success'] ?? '';
$error   = $_GET['error']   ?? '';

$typeIcons = [
    'Plumbing'            => 'bi-droplet-fill',
    'Electrical'          => 'bi-lightning-fill',
    'Structural'          => 'bi-building',
    'Appliances'          => 'bi-tools',
    'Security'            => 'bi-shield-fill',
    'Cleaning'            => 'bi-stars',
    'Pest Control'        => 'bi-bug-fill',
    'Locks & Keys'        => 'bi-key-fill',
    'Painting'            => 'bi-brush-fill',
    'General Maintenance' => 'bi-wrench-adjustable-circle-fill',
    'Other'               => 'bi-three-dots',
];
$statusClass = ['Paid'=>'badge-paid','Pending'=>'badge-pending','Cancelled'=>'badge-secondary'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Maintenance Expenses — Rentisha Admin</title>
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

      <?php if ($success): ?>
      <div class="alert alert-success mb-4"><i class="bi bi-check-circle-fill"></i><div><?= htmlspecialchars($success) ?></div></div>
      <?php endif; ?>
      <?php if ($error): ?>
      <div class="alert alert-danger mb-4"><i class="bi bi-exclamation-circle-fill"></i><div><?= htmlspecialchars($error) ?></div></div>
      <?php endif; ?>

      <div class="page-header">
        <div>
          <h1><i class="bi bi-wrench-adjustable-circle"></i> Maintenance Expenses</h1>
          <p>Track all money spent on repairs, materials, labour and contractors — <?= $filterYear ?></p>
        </div>
        <div class="d-flex gap-2">
          <button class="btn btn-outline btn-sm" onclick="window.print()">
            <i class="bi bi-printer"></i> Print
          </button>
          <button class="btn btn-primary" data-modal-open="addExpenseModal">
            <i class="bi bi-plus-lg"></i> Log Expense
          </button>
        </div>
      </div>

      <!-- Stats -->
      <div class="stats-grid" style="grid-template-columns:repeat(auto-fill,minmax(160px,1fr));margin-bottom:20px;">
        <div class="stat-card red">
          <div class="stat-icon"><i class="bi bi-cash-coin"></i></div>
          <div class="stat-value" style="font-size:1rem;"><?= formatKES($stats['total_spent'] ?? 0) ?></div>
          <div class="stat-label">Total Spent <?= $filterYear ?></div>
        </div>
        <div class="stat-card orange">
          <div class="stat-icon"><i class="bi bi-hourglass-split"></i></div>
          <div class="stat-value" style="font-size:1rem;"><?= formatKES($stats['pending'] ?? 0) ?></div>
          <div class="stat-label">Awaiting Payment</div>
        </div>
        <div class="stat-card green">
          <div class="stat-icon"><i class="bi bi-check-circle"></i></div>
          <div class="stat-value" style="font-size:1rem;"><?= formatKES($stats['paid'] ?? 0) ?></div>
          <div class="stat-label">Fully Paid Out</div>
        </div>
        <div class="stat-card blue">
          <div class="stat-icon"><i class="bi bi-receipt"></i></div>
          <div class="stat-value" data-count="<?= $stats['total_entries'] ?? 0 ?>"><?= $stats['total_entries'] ?? 0 ?></div>
          <div class="stat-label">Expense Records</div>
        </div>
        <?php if ($topCategory): ?>
        <div class="stat-card">
          <div class="stat-icon"><i class="bi bi-bar-chart-fill"></i></div>
          <div class="stat-value" style="font-size:.85rem;font-weight:700;"><?= htmlspecialchars($topCategory['expense_type']) ?></div>
          <div class="stat-label">Top Category <?= $filterYear ?></div>
          <div style="font-size:.75rem;color:var(--text-muted);"><?= formatKES($topCategory['total']) ?></div>
        </div>
        <?php endif; ?>
      </div>

      
      <div class="card mb-4 no-print">
        <div class="card-header">
          <h3 class="card-title"><i class="bi bi-bar-chart-line"></i> Monthly Spend — <?= $filterYear ?></h3>
          <form method="GET" class="card-actions" style="display:flex;gap:8px;">
            <select name="year" class="form-control" style="width:90px;" onchange="this.form.submit()">
              <?php for ($y=(int)date('Y'); $y>=(int)date('Y')-3; $y--): ?>
              <option value="<?= $y ?>" <?= $y===$filterYear?'selected':'' ?>><?= $y ?></option>
              <?php endfor; ?>
            </select>
            <?php foreach (['type'=>$filterType,'apt'=>$filterApt,'status'=>$filterStatus,'month'=>$filterMonth] as $k=>$v): ?>
            <?php if ($v): ?><input type="hidden" name="<?= $k ?>" value="<?= htmlspecialchars($v) ?>"><?php endif; ?>
            <?php endforeach; ?>
          </form>
        </div>
        <div class="card-body">
          <div class="chart-container" style="height:180px;">
            <canvas id="expChart"></canvas>
          </div>
        </div>
      </div>

    
      <div class="filter-bar no-print">
        <div class="input-group search-box">
          <i class="bi bi-search input-icon"></i>
          <input type="search" class="form-control" placeholder="Search description, vendor, apartment…" data-search-table="expTable">
        </div>
        <form method="GET" style="display:contents;">
          <input type="hidden" name="year" value="<?= $filterYear ?>">
          <select name="type" class="form-control" style="width:auto;" onchange="this.form.submit()">
            <option value="">All Types</option>
            <?php foreach ($expenseTypes as $t): ?>
            <option value="<?= $t ?>" <?= $filterType===$t?'selected':'' ?>><?= $t ?></option>
            <?php endforeach; ?>
          </select>
          <select name="month" class="form-control" style="width:auto;" onchange="this.form.submit()">
            <option value="">All Months</option>
            <?php foreach ($allMonths as $m): ?>
            <option value="<?= $m ?>" <?= $filterMonth===$m?'selected':'' ?>><?= $m ?></option>
            <?php endforeach; ?>
          </select>
          <select name="apt" class="form-control" style="width:auto;" onchange="this.form.submit()">
            <option value="">All Properties</option>
            <?php foreach ($apartments as $a): ?>
            <option value="<?= $a['id'] ?>" <?= $filterApt===$a['id']?'selected':'' ?>><?= htmlspecialchars($a['name']) ?></option>
            <?php endforeach; ?>
          </select>
          <select name="status" class="form-control" style="width:auto;" onchange="this.form.submit()">
            <option value="">All Statuses</option>
            <option value="Paid"      <?= $filterStatus==='Paid'?'selected':'' ?>>Paid</option>
            <option value="Pending"   <?= $filterStatus==='Pending'?'selected':'' ?>>Pending</option>
            <option value="Cancelled" <?= $filterStatus==='Cancelled'?'selected':'' ?>>Cancelled</option>
          </select>
          <?php if ($filterType||$filterMonth||$filterApt||$filterStatus): ?>
          <a href="expenses.php?year=<?= $filterYear ?>" class="btn btn-outline btn-sm">
            <i class="bi bi-x-lg"></i> Clear
          </a>
          <?php endif; ?>
        </form>
      </div>

  
      <div class="card">
        <div class="card-body p-0">
          <div class="table-wrapper">
            <table class="rms-table" id="expTable">
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Type</th>
                  <th>What Was Done</th>
                  <th>Category</th>
                  <th>Vendor / Person</th>
                  <th>Property</th>
                  <th>Amount</th>
                  <th>Paid By</th>
                  <th>Receipt #</th>
                  <th>Status</th>
                  <th class="col-actions no-print">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($expenses)): ?>
                <tr>
                  <td colspan="11" style="text-align:center;padding:48px;color:var(--text-muted);">
                    <i class="bi bi-wrench" style="font-size:2.5rem;display:block;margin-bottom:10px;"></i>
                    No expenses recorded<?= $filterMonth ? ' for '.$filterMonth.' '.$filterYear : ' for '.$filterYear ?>.
                    <br><a href="#" data-modal-open="addExpenseModal">Log the first one</a>.
                  </td>
                </tr>
                <?php else: ?>
                  <?php
                  $grandTotal = 0;
                  foreach ($expenses as $e):
                    $grandTotal += (float)$e['amount'];
                    $icon = $typeIcons[$e['expense_type']] ?? 'bi-tools';
                  ?>
                  <tr>
                    <td style="white-space:nowrap;font-size:.82rem;">
                      <?= $e['expense_date'] ? formatDate($e['expense_date']) : ($e['month'].' '.$e['year']) ?>
                    </td>
                    <td>
                      <span style="display:flex;align-items:center;gap:5px;font-weight:600;font-size:.83rem;">
                        <i class="bi <?= $icon ?>" style="color:var(--primary);"></i>
                        <?= htmlspecialchars($e['expense_type']) ?>
                      </span>
                    </td>
                    <td style="max-width:220px;">
                      <?= htmlspecialchars($e['description'] ?? '—') ?>
                    </td>
                    <td>
                      <span class="badge badge-info" style="font-size:.72rem;">
                        <?= htmlspecialchars($e['cost_category'] ?? 'Other') ?>
                      </span>
                    </td>
                    <td style="font-size:.83rem;"><?= htmlspecialchars($e['vendor'] ?? '—') ?></td>
                    <td style="font-size:.82rem;"><?= htmlspecialchars($e['apartment_name'] ?? '—') ?></td>
                    <td class="fw-bold" style="color:var(--danger);"><?= formatKES($e['amount']) ?></td>
                    <td style="font-size:.82rem;"><?= htmlspecialchars($e['paid_by_name'] ?? '—') ?></td>
                    <td>
                      <?php if ($e['receipt_ref']): ?>
                      <code style="font-size:.72rem;"><?= htmlspecialchars($e['receipt_ref']) ?></code>
                      <?php else: ?>
                      <span style="color:var(--text-muted);font-size:.75rem;">—</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <span class="badge <?= $statusClass[$e['status']] ?? 'badge-secondary' ?>">
                        <?= $e['status'] ?>
                      </span>
                    </td>
                    <td class="col-actions no-print">
                      <div class="d-flex gap-1" style="justify-content:flex-end;">
                        <button type="button" class="btn btn-outline btn-sm btn-icon" title="Edit"
                          onclick="openEditExpense(<?= htmlspecialchars(json_encode($e)) ?>)">
                          <i class="bi bi-pencil"></i>
                        </button>
                        <form method="POST" action="../includes/save_expense.php" style="display:inline;"
                              onsubmit="return confirm('Delete this expense record?')">
                          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(rmsCsrfToken()) ?>">
                          <input type="hidden" name="delete_id" value="<?= $e['id'] ?>">
                          <button type="submit" class="btn btn-danger btn-sm btn-icon" title="Delete">
                            <i class="bi bi-trash"></i>
                          </button>
                        </form>
                      </div>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                  <tr style="background:var(--bg-secondary);font-weight:700;">
                    <td colspan="6" style="text-align:right;padding:10px 12px;">TOTAL</td>
                    <td style="color:var(--danger);"><?= formatKES($grandTotal) ?></td>
                    <td colspan="4"></td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>


<div class="modal-overlay" id="addExpenseModal">
  <div class="modal modal-lg">
    <div class="modal-header">
      <h3 id="expModalTitle"><i class="bi bi-wrench-adjustable"></i> Log Maintenance Expense</h3>
      <button class="modal-close" data-modal-close><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="modal-body">
      <form action="../includes/save_expense.php" method="POST" data-validate id="expenseForm">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(rmsCsrfToken()) ?>">
        <input type="hidden" name="edit_id" id="expEditId" value="">

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Expense Type <span class="required">*</span></label>
            <select name="expense_type" id="expType" class="form-control" required>
              <option value="">— Select —</option>
              <?php foreach ($expenseTypes as $t): ?>
              <option value="<?= $t ?>"><?= $t ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Cost Category <span class="required">*</span></label>
            <select name="cost_category" id="expCostCat" class="form-control" required>
              <?php foreach ($costCategories as $c): ?>
              <option value="<?= $c ?>"><?= $c ?></option>
              <?php endforeach; ?>
            </select>
            <div class="form-hint">What the money was spent on</div>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">What Was Done <span class="required">*</span></label>
          <textarea name="description" id="expDesc" class="form-control" rows="2" required
            placeholder="e.g. Replaced broken door lock in unit 5, bought 2 deadbolts from Hardware Plus"></textarea>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Amount (KES) <span class="required">*</span></label>
            <div class="input-group">
              <i class="bi bi-currency-exchange input-icon"></i>
              <input type="number" name="amount" id="expAmount" class="form-control"
                     placeholder="e.g. 3500" required min="1" step="0.01">
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Date Spent <span class="required">*</span></label>
            <input type="date" name="expense_date" id="expDate" class="form-control"
                   value="<?= date('Y-m-d') ?>" required>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Vendor / Technician / Contractor</label>
            <input type="text" name="vendor" id="expVendor" class="form-control"
              placeholder="e.g. Kariuki Hardware, John the Plumber">
            <div class="form-hint">Who supplied goods or did the work</div>
          </div>
          <div class="form-group">
            <label class="form-label">Receipt / Invoice #</label>
            <input type="text" name="receipt_ref" id="expReceipt" class="form-control"
              placeholder="e.g. RCP-00123">
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Property (Apartment)</label>
            <select name="apartment_id" id="expApt" class="form-control">
              <option value="">— Select —</option>
              <?php foreach ($apartments as $a): ?>
              <option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Paid By <span class="required">*</span></label>
            <select name="paid_by" id="expPaidBy" class="form-control" required>
              <option value="">— Select —</option>
              <?php foreach ($allAdmins as $adm): ?>
              <option value="<?= $adm['id'] ?>"
                <?= $adm['id'] == $_SESSION['user_id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($adm['full_name']) ?>
              </option>
              <?php endforeach; ?>
            </select>
            <div class="form-hint">Admin or caretaker who made the payment</div>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Month</label>
            <select name="month" id="expMonth" class="form-control">
              <?php foreach ($allMonths as $m): ?>
              <option value="<?= $m ?>" <?= $m===date('F')?'selected':'' ?>><?= $m ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Year</label>
            <select name="year" id="expYear" class="form-control">
              <?php for ($y=(int)date('Y'); $y>=(int)date('Y')-2; $y--): ?>
              <option value="<?= $y ?>" <?= $y===(int)date('Y')?'selected':'' ?>><?= $y ?></option>
              <?php endfor; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Status</label>
            <select name="status" id="expStatus" class="form-control">
              <option value="Paid">Paid</option>
              <option value="Pending">Pending</option>
              <option value="Cancelled">Cancelled</option>
            </select>
          </div>
        </div>

        <div class="modal-footer" style="padding:0;margin-top:8px;">
          <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
          <button type="submit" class="btn btn-primary" id="expSubmitBtn">
            <i class="bi bi-check-lg"></i> Save Expense
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="../assets/js/main.js"></script>
<script>

const ctx = document.getElementById('expChart')?.getContext('2d');
if (ctx) {
  new Chart(ctx, {
    type: 'bar',
    data: {
      labels: <?= json_encode(array_map(fn($m) => substr($m,0,3), $allMonths)) ?>,
      datasets: [{
        label: 'KES Spent',
        data: <?= json_encode($monthlyData) ?>,
        backgroundColor: 'rgba(239,68,68,.75)',
        borderRadius: 5
      }]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: {
        y: { beginAtZero: true, ticks: { callback: v => 'KES '+(v>=1000?(v/1000)+'k':v) } },
        x: { grid: { display: false } }
      }
    }
  });
}


function openEditExpense(e) {
  document.getElementById('expModalTitle').innerHTML = '<i class="bi bi-pencil-square"></i> Edit Expense';
  document.getElementById('expSubmitBtn').innerHTML  = '<i class="bi bi-check-lg"></i> Update Expense';
  document.getElementById('expEditId').value    = e.id;
  document.getElementById('expType').value      = e.expense_type   || '';
  document.getElementById('expCostCat').value   = e.cost_category  || 'Other';
  document.getElementById('expDesc').value      = e.description    || '';
  document.getElementById('expAmount').value    = e.amount         || '';
  document.getElementById('expDate').value      = e.expense_date   || '';
  document.getElementById('expVendor').value    = e.vendor         || '';
  document.getElementById('expReceipt').value   = e.receipt_ref    || '';
  document.getElementById('expApt').value       = e.apartment_id   || '';
  document.getElementById('expPaidBy').value    = e.paid_by        || '';
  document.getElementById('expMonth').value     = e.month          || '';
  document.getElementById('expYear').value      = e.year           || '';
  document.getElementById('expStatus').value    = e.status         || 'Paid';
  openModal('addExpenseModal');
}


document.querySelector('[data-modal-open="addExpenseModal"]')?.addEventListener('click', function() {
  document.getElementById('expenseForm').reset();
  document.getElementById('expEditId').value   = '';
  document.getElementById('expModalTitle').innerHTML = '<i class="bi bi-wrench-adjustable"></i> Log Maintenance Expense';
  document.getElementById('expSubmitBtn').innerHTML  = '<i class="bi bi-check-lg"></i> Save Expense';
  
  document.getElementById('expPaidBy').value = '<?= (int)$_SESSION['user_id'] ?>';
});
</script>
</body>
</html>
