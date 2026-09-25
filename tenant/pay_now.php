<?php
session_start();
require_once '../includes/auth_check.php';
require_once '../includes/db_helpers.php';
requireRole('tenant');

$pageTitle  = 'Pay Now';
$activePage = 'pay_now';

$tenantId = (int)$_SESSION['user_id'];
$tenant   = getTenantById($tenantId);
$unitRent = $tenant['unit_rent'] ?? $tenant['assigned_rent'] ?? 0;
$previousRentCredit = 0.0;
$creditStmt = getDB()->prepare(
    "SELECT balance
     FROM payments
     WHERE tenant_id=:tid AND payment_type='Rent' AND balance < 0
       AND (year < :year OR (year=:year AND FIELD(month,
         'January','February','March','April','May','June','July','August',
         'September','October','November','December')
         < FIELD(:month,
         'January','February','March','April','May','June','July','August',
         'September','October','November','December')))
     ORDER BY year DESC, FIELD(month,
       'January','February','March','April','May','June','July','August',
       'September','October','November','December') DESC
     LIMIT 1"
);
$creditStmt->execute([
    ':tid' => $tenantId,
    ':year' => (int)date('Y'),
    ':month' => date('F'),
]);
$creditBalance = $creditStmt->fetchColumn();
if ($creditBalance !== false) {
    $previousRentCredit = abs((float)$creditBalance);
}
$currentRentDue = max(
    0.0,
    rentAmountDue((float)$unitRent, date('F'), (int)date('Y')) - $previousRentCredit
);
$rentPenaltyApplied = $currentRentDue > (float)$unitRent;
$depositAmount = 0;
$depositPaid = false;

$pdo = getDB();
try {
    $cols = $pdo->query("SHOW COLUMNS FROM tenant_units")->fetchAll(PDO::FETCH_COLUMN);
    if (in_array('deposit_amount', $cols)) {
        $depositStmt = $pdo->prepare("
            SELECT deposit_amount, deposit_paid 
            FROM tenant_units 
            WHERE tenant_id = :tid AND status = 'active' 
            LIMIT 1
        ");
        $depositStmt->execute([':tid' => $tenantId]);
        $depositInfo = $depositStmt->fetch(PDO::FETCH_ASSOC);
        if ($depositInfo) {
            $depositAmount = (float)($depositInfo['deposit_amount'] ?? 0);
            $depositPaid = (bool)($depositInfo['deposit_paid'] ?? 0);
        }
    }
} catch (Exception $e) {
}

$paybill = getSetting('paybill_number', '123456');
$acceptedPaymentMethods = json_decode(getSetting('payment_methods', '[]'), true);
if (!is_array($acceptedPaymentMethods) || !$acceptedPaymentMethods) {
    $acceptedPaymentMethods = ['M-PESA Paybill','Airtel Money','Cash','PDQ/POS','Cheque','Bank Transfer'];
}
$accountNo = ($tenant['apartment_name'] && $tenant['unit_number']) 
    ? $tenant['apartment_name'] . '-' . $tenant['unit_number']
    : 'RENT';
    
$bankName = getSetting('bank_name', 'KCB Bank');
$bankAccount = getSetting('bank_account_number', '1234567890');
$bankBranch = getSetting('bank_branch', 'Nairobi');

$allMonths = ['January','February','March','April','May','June',
              'July','August','September','October','November','December'];
$nextUnpaidMonth = date('F');
$nextUnpaidYear  = (int)date('Y');
$paidSet  = [];
$paidRows = [];

try {
    $hasPtCol = _hasCol('payments', 'payment_type');
    $ptFilter = $hasPtCol ? "AND payment_type IN ('Rent','Other')" : "";
    $paidStmt = $pdo->prepare(
        "SELECT month, year FROM payments
         WHERE tenant_id = :tid {$ptFilter}
           AND status IN ('Paid','Pending','Partial')
         ORDER BY year, FIELD(month,
           'January','February','March','April','May','June',
           'July','August','September','October','November','December')"
    );
    $paidStmt->execute([':tid' => $tenantId]);
    $paidRows = $paidStmt->fetchAll(PDO::FETCH_ASSOC);

    $paidSet = [];
    foreach ($paidRows as $r) {
        $paidSet[$r['month'] . '-' . $r['year']] = true;
    }

    $checkYear  = (int)date('Y');
    $checkMIdx  = (int)date('n') - 1;
    for ($i = 0; $i < 24; $i++) {
        $checkMonth = $allMonths[$checkMIdx];
        if (!isset($paidSet[$checkMonth . '-' . $checkYear])) {
            $nextUnpaidMonth = $checkMonth;
            $nextUnpaidYear  = $checkYear;
            break;
        }
        $checkMIdx++;
        if ($checkMIdx >= 12) { $checkMIdx = 0; $checkYear++; }
    }
} catch (Exception $e) {
}

$success = $_GET['success'] ?? '';
$error   = $_GET['error']   ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Pay Now — Rentisha</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="../assets/css/style.css">
  <style>
    .payment-method {
      border: 2px solid var(--border);
      border-radius: var(--radius-lg);
      padding: 20px;
      cursor: pointer;
      transition: all 0.3s;
      position: relative;
    }
    .payment-method:hover { border-color: var(--primary); transform: translateY(-2px); box-shadow: var(--shadow-md); }
    .payment-method.active { border-color: var(--primary); background: var(--primary-pale); }
    .payment-method input[type="radio"] { position: absolute; opacity: 0; }
    .payment-method .icon { font-size: 2.5rem; color: var(--primary); margin-bottom: 10px; }
    .payment-method .title { font-weight: 700; font-size: 1.1rem; margin-bottom: 4px; }
    .payment-method .desc { font-size: 0.85rem; color: var(--text-muted); }
    .paybill-box {
      background: linear-gradient(135deg, #10b981 0%, #059669 100%);
      color: white;
      padding: 20px;
      border-radius: var(--radius-lg);
      text-align: center;
      margin: 15px 0;
    }
    .paybill-box .label { font-size: 0.7rem; opacity: 0.8; letter-spacing: 1px; margin-bottom: 4px; }
    .paybill-box .value { font-size: 1.8rem; font-weight: 900; margin: 8px 0; letter-spacing: 1px; }
  </style>
</head>
<body>
<div class="rms-shell">
  <div class="sidebar-overlay" id="sidebarOverlay"></div>
  <?php include '../includes/sidebar_tenant.php'; ?>

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
          <h1>💰 Pay Now</h1>
          <p>Submit your rent or deposit payment confirmation</p>
        </div>
        <a href="payments.php" class="btn btn-outline btn-sm">
          <i class="bi bi-arrow-left"></i> Back to Payments
        </a>
      </div>

      <?php if (!$tenant['unit_id']): ?>
      <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <div><strong>No Unit Assigned</strong><br>You need to be assigned a unit before making payments. Contact your caretaker or administrator.</div>
      </div>
      <?php else: ?>

      <div class="grid-2 mb-4">
        <!-- Unit Info Card -->
        <div class="card">
          <div class="card-header">
            <h3 class="card-title"><i class="bi bi-house-fill"></i> Your Unit</h3>
          </div>
          <div class="card-body">
            <table style="width:100%;font-size:0.9rem;">
              <tr><td style="padding:8px 0;color:var(--text-muted);">Apartment</td><td style="padding:8px 0;font-weight:700;"><?= htmlspecialchars($tenant['apartment_name']) ?></td></tr>
              <tr><td style="padding:8px 0;color:var(--text-muted);">Unit Number</td><td style="padding:8px 0;font-weight:700;"><?= htmlspecialchars($tenant['unit_number']) ?></td></tr>
              <tr><td style="padding:8px 0;color:var(--text-muted);">Monthly Rent</td><td style="padding:8px 0;font-weight:700;color:var(--primary);"><?= formatKES($currentRentDue) ?></td></tr>
              <?php if ($rentPenaltyApplied): ?>
              <tr><td style="padding:8px 0;color:var(--danger);">Late payment penalty</td><td style="padding:8px 0;font-weight:700;color:var(--danger);"><?= formatKES($currentRentDue - (float)$unitRent) ?></td></tr>
              <?php endif; ?>
              <?php if ($depositAmount > 0): ?>
              <tr><td style="padding:8px 0;color:var(--text-muted);">Deposit</td><td style="padding:8px 0;font-weight:700;"><?= formatKES($depositAmount) ?> <?= $depositPaid ? '<span class="badge badge-paid" style="font-size:0.7rem;">Paid</span>' : '<span class="badge badge-pending" style="font-size:0.7rem;">Not Paid</span>' ?></td></tr>
              <?php endif; ?>
            </table>
          </div>
        </div>

        <!-- Payment Instructions -->
        <div class="card">
          <div class="card-header">
            <h3 class="card-title"><i class="bi bi-credit-card"></i> Payment Methods</h3>
          </div>
          <div class="card-body">
            <?php if (in_array('M-PESA Paybill', $acceptedPaymentMethods, true)): ?>
            <!-- M-PESA -->
            <div style="margin-bottom:20px;">
              <div style="font-weight:700;margin-bottom:8px;"><i class="bi bi-phone-fill"></i> M-PESA Paybill</div>
              <div class="paybill-box">
                <div class="label">PAYBILL NUMBER</div>
                <div class="value"><?= htmlspecialchars($paybill) ?></div>
                <div class="label">ACCOUNT NUMBER</div>
                <div class="value" style="font-size:1.3rem;"><?= htmlspecialchars($accountNo) ?></div>
              </div>
            </div>
            <?php endif; ?>
            
            <?php if (in_array('Bank Transfer', $acceptedPaymentMethods, true)): ?>
            <!-- Bank Transfer -->
            <div style="border-top:2px dashed #e5e7eb;padding-top:20px;">
              <div style="font-weight:700;margin-bottom:8px;"><i class="bi bi-bank"></i> Bank Transfer</div>
              <table style="width:100%;font-size:0.9rem;">
                <tr><td style="padding:6px 0;color:#666;width:40%;">Bank Name:</td><td style="font-weight:700;"><?= htmlspecialchars($bankName) ?></td></tr>
                <tr><td style="padding:6px 0;color:#666;">Account Number:</td><td style="font-weight:700;color:#667eea;"><?= htmlspecialchars($bankAccount) ?></td></tr>
                <tr><td style="padding:6px 0;color:#666;">Branch:</td><td style="font-weight:700;"><?= htmlspecialchars($bankBranch) ?></td></tr>
                <tr><td style="padding:6px 0;color:#666;">Account Name:</td><td style="font-weight:700;"><?= htmlspecialchars($accountNo) ?></td></tr>
              </table>
            </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Payment Form -->
      <div class="card">
        <div class="card-header">
          <h3 class="card-title"><i class="bi bi-credit-card-fill"></i> Submit Payment Confirmation</h3>
        </div>
        <div class="card-body">
          <form action="../includes/submit_tenant_payment.php" method="POST" data-validate id="paymentForm" onsubmit="return handlePaymentSubmit()">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(rmsCsrfToken()) ?>">

            <!-- Step 1: Select Payment Type -->
            <h4 style="margin-bottom:16px;">Step 1: Select Payment Type</h4>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-bottom:24px;">
              <label class="payment-method">
                <input type="radio" name="payment_type" value="Rent" required onchange="updatePaymentType(this)">
                <div class="icon"><i class="bi bi-house-fill"></i></div>
                <div class="title">Monthly Rent</div>
                <div class="desc"><?= formatKES($currentRentDue) ?><?= $rentPenaltyApplied ? ' (including late penalty)' : '' ?></div>
              </label>

              <?php if ($depositAmount > 0 && !$depositPaid): ?>
              <label class="payment-method">
                <input type="radio" name="payment_type" value="Deposit" required onchange="updatePaymentType(this)">
                <div class="icon"><i class="bi bi-shield-check-fill"></i></div>
                <div class="title">Security Deposit</div>
                <div class="desc">Enter any amount</div>
              </label>
              <?php endif; ?>
              
              <label class="payment-method">
                <input type="radio" name="payment_type" value="Utility" required onchange="updatePaymentType(this)">
                <div class="icon"><i class="bi bi-lightning-charge-fill"></i></div>
                <div class="title">Utility Bill</div>
                <div class="desc">Water, Electricity, etc.</div>
              </label>
            </div>

            <!-- Step 2: Payment Details -->
            <h4 style="margin-bottom:16px;">Step 2: Enter Payment Details</h4>
            
            <?php if (!empty($paidSet)): ?>
            <div class="alert alert-info" style="font-size:.83rem;margin-bottom:16px;">
              <i class="bi bi-calendar-check-fill"></i>
              <div>
                <strong>Already submitted / paid:</strong>
                <?php
                  $paidLabels = [];
                  foreach ($paidRows as $r) {
                      $paidLabels[] = substr($r['month'], 0, 3) . ' ' . $r['year'];
                  }
                  echo htmlspecialchars(implode(', ', $paidLabels));
                ?>
                <br>
                <strong>Next due:</strong> <?= $nextUnpaidMonth ?> <?= $nextUnpaidYear ?> — pre-selected below.
              </div>
            </div>
            <?php endif; ?>

            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Amount Paid (KES) <span class="required">*</span></label>
                <input type="number" name="amount" id="amountInput" class="form-control" 
                  placeholder="Enter amount" required min="1" step="0.01"                   value="<?= $currentRentDue ?>">
                <div class="form-hint" id="amountHint">Enter the amount you are paying</div>
              </div>
              
              <div class="form-group" id="monthField">
                <label class="form-label">Month <span class="required">*</span></label>
                <select name="month" id="monthSelect" class="form-control" required>
                  <?php foreach ($allMonths as $m): ?>
                  <option value="<?= $m ?>" <?= $m === $nextUnpaidMonth ? 'selected' : '' ?>><?= $m ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              
              <div class="form-group">
                <label class="form-label">Year <span class="required">*</span></label>
                <select name="year" id="yearSelect" class="form-control" required>
                  <?php
                    $minYear = (int)date('Y') - 1;
                    $maxYear = (int)date('Y') + 2;
                    for ($y = $minYear; $y <= $maxYear; $y++):
                  ?>
                  <option value="<?= $y ?>" <?= $y === $nextUnpaidYear ? 'selected' : '' ?>>
                    <?= $y ?><?= $y === (int)date('Y') ? ' (current)' : ($y > (int)date('Y') ? ' ↑ forward' : '') ?>
                  </option>
                  <?php endfor; ?>
                </select>
                <div class="form-hint">You can pay ahead for future months</div>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Payment Method <span class="required">*</span></label>
                <select name="payment_method" id="paymentMethod" class="form-control" required onchange="togglePayMethodFields()">
                  <?php foreach ($acceptedPaymentMethods as $method): ?>
                  <option value="<?= htmlspecialchars($method) ?>"><?= htmlspecialchars($method) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              
              <div class="form-group" id="referenceField">
                <label class="form-label">Transaction Reference (Optional)</label>
                <input type="text" name="reference" class="form-control" 
                  placeholder="e.g., SH7G4K2M9P">
                <div class="form-hint">M-PESA code or reference number</div>
              </div>
              
              <div class="form-group" id="bankAccountField" style="display:none;">
                <label class="form-label">Your Bank Account Number</label>
                <input type="text" name="bank_account" class="form-control" 
                  placeholder="Account you paid from">
                <div class="form-hint">For verification purposes</div>
              </div>
            </div>

            <!-- Cheque fields (shown only when Cheque is selected) -->
            <div id="chequeFields" style="display:none;">
              <div class="form-row">
                <div class="form-group">
                  <label class="form-label">Cheque Number <span class="required">*</span></label>
                  <input type="text" name="cheque_number" id="chequeNumberInput" class="form-control"
                    placeholder="e.g., 000123">
                </div>
                <div class="form-group">
                  <label class="form-label">Bank Name <span class="required">*</span></label>
                  <input type="text" name="cheque_bank" class="form-control"
                    placeholder="e.g., KCB, Equity, Co-op">
                </div>
              </div>
              <div class="alert alert-warning" style="font-size:.83rem;">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <div>Cheque payments are marked <strong>Pending</strong> until management verifies clearance. You'll be notified once cleared.</div>
              </div>
            </div>
            
            <div class="form-row" id="utilityFields" style="display:none;">
              <div class="form-group">
                <label class="form-label">Utility Bill Type</label>
                <select name="utility_type" class="form-control">
                  <option value="Water">💧 Water</option>
                  <option value="Electricity">⚡ Electricity</option>
                  <option value="Garbage">🗑️ Garbage</option>
                  <option value="Security">🛡️ Security</option>
                  <option value="Internet">📡 Internet</option>
                  <option value="Other">📋 Other</option>
                </select>
              </div>
            </div>

            <div class="form-group">
              <label class="form-label">Payment Date <span class="required">*</span></label>
              <input type="date" name="payment_date" class="form-control" 
                value="<?= date('Y-m-d') ?>" required>
              <div class="form-hint">Date you made the actual payment</div>
            </div>

            <div class="form-group">
              <label class="form-label">Additional Notes (Optional)</label>
              <textarea name="notes" class="form-control" rows="3" 
                placeholder="Any additional information about this payment..."></textarea>
            </div>

            <!-- How it Works -->
            <div class="alert alert-info">
              <strong><i class="bi bi-info-circle-fill"></i> How It Works:</strong><br>
              1. Make payment via M-PESA, Bank, or Cheque<br>
              2. Fill this form — you can pay <strong>ahead for future months</strong><br>
              3. Your caretaker/admin will verify<br>
              4. You'll receive a confirmation notification
            </div>

            <div class="modal-footer" style="padding:0;margin-top:16px;">
              <a href="payments.php" class="btn btn-outline">Cancel</a>
              <button type="submit" class="btn btn-primary">
                <i class="bi bi-send-fill"></i> Submit Payment
              </button>
            </div>
          </form>
        </div>
      </div>

      <?php endif; ?>

    </div><!-- /page-content -->
  </div><!-- /main-content -->
</div><!-- /rms-shell -->

<script src="../assets/js/main.js"></script>
<script>
function handlePaymentSubmit() {
  const paymentType = document.querySelector('input[name="payment_type"]:checked')?.value;
  const form = document.getElementById('paymentForm');
  
  if (paymentType === 'Utility') {
    form.action = '../includes/submit_utility_bill.php';
  } else {
    form.action = '../includes/submit_tenant_payment.php';
  }
  
  return true;
}

function updatePaymentType(radio) {
  const amountInput  = document.getElementById('amountInput');
  const amountHint   = document.getElementById('amountHint');
  const monthField   = document.getElementById('monthField');
  const utilityFields= document.getElementById('utilityFields');

  document.querySelectorAll('.payment-method').forEach(el => el.classList.remove('active'));
  radio.closest('.payment-method').classList.add('active');

  if (radio.value === 'Rent') {
    amountInput.value = <?= (float)$currentRentDue ?>;
    amountHint.textContent = 'Monthly rent — you can select any future month above';
    monthField.style.display = '';
    utilityFields.style.display = 'none';
    checkMonthAlreadyPaid();
  } else if (radio.value === 'Deposit') {
    amountInput.value = <?= (float)$depositAmount ?>;
    amountHint.textContent = 'Security deposit amount';
    monthField.style.display = 'none';
    utilityFields.style.display = 'none';
    document.getElementById('amountHint').textContent = 'Security deposit amount';
  } else if (radio.value === 'Utility') {
    amountInput.value = '';
    amountHint.textContent = 'Enter the utility bill amount';
    monthField.style.display = '';
    utilityFields.style.display = '';
    document.getElementById('amountHint').textContent = 'Enter the utility bill amount';
  }
}

const paidSet = <?= json_encode(array_keys($paidSet ?? [])) ?>;
const monthNames = ['January','February','March','April','May','June','July','August','September','October','November','December'];
const baseRent = <?= (float)$unitRent ?>;
const rentDueDay = <?= (int)getSetting('rent_due_day', '5') ?>;
const latePenaltyPercent = <?= (float)getSetting('penalty_percent', '10') ?>;
function checkMonthAlreadyPaid() {
  const paymentType = document.querySelector('input[name="payment_type"]:checked')?.value;
  if (paymentType !== 'Rent') return;

  const month = document.getElementById('monthSelect')?.value;
  const year  = document.getElementById('yearSelect')?.value;
  const hint  = document.getElementById('amountHint');
  if (!month || !year || !hint) return;
  const key = month + '-' + year;
  const monthIndex = monthNames.indexOf(month);
  const dueDate = new Date(Number(year), monthIndex, rentDueDay);
  const today = new Date();
  const dueAmount = dueDate < new Date(today.getFullYear(), today.getMonth(), today.getDate())
    ? baseRent * (1 + latePenaltyPercent / 100)
    : baseRent;
  const amountInput = document.getElementById('amountInput');
  if (amountInput && !paidSet.includes(key)) amountInput.value = dueAmount.toFixed(2);
  if (paidSet.includes(key)) {
    hint.innerHTML = '<span style="color:#dc2626;font-weight:600;"><i class="bi bi-exclamation-triangle-fill"></i> A rent payment for ' + month + ' ' + year + ' was already submitted. Check your payment history before submitting again.</span>';
  } else {
    const mIdx    = monthNames.indexOf(month);
    const curMIdx = <?= (int)date('n') - 1 ?>;
    const curYear = <?= (int)date('Y') ?>;
    const isForward = parseInt(year) > curYear || (parseInt(year) === curYear && mIdx > curMIdx);
    if (isForward) {
      hint.innerHTML = '<span style="color:#10b981;font-weight:600;"><i class="bi bi-calendar-plus-fill"></i> Forward payment — ' + month + ' ' + year + ' is not yet due. Great!</span>';
    } else {
      hint.textContent = 'Monthly rent amount';
    }
  }
}

function togglePayMethodFields() {
  const method = document.getElementById('paymentMethod').value;
  const bankField   = document.getElementById('bankAccountField');
  const refField    = document.getElementById('referenceField');
  const chequeFields= document.getElementById('chequeFields');
  const chequeInput = document.getElementById('chequeNumberInput');

  bankField.style.display    = 'none';
  refField.style.display     = '';
  chequeFields.style.display = 'none';
  chequeInput.required       = false;

  if (method === 'Bank Transfer') {
    bankField.style.display = '';
    refField.style.display  = 'none';
  } else if (method === 'Cheque') {
    chequeFields.style.display = '';
    refField.style.display     = 'none';
    chequeInput.required       = true;
  }
}

document.addEventListener('DOMContentLoaded', function() {
  const monthSel = document.getElementById('monthSelect');
  const yearSel  = document.getElementById('yearSelect');
  if (monthSel) monthSel.addEventListener('change', checkMonthAlreadyPaid);
  if (yearSel)  yearSel.addEventListener('change',  checkMonthAlreadyPaid);

  const firstRadio = document.querySelector('input[name="payment_type"]');
  if (firstRadio) {
    firstRadio.checked = true;
    updatePaymentType(firstRadio);
  }
});
</script>
</body>
</html>
