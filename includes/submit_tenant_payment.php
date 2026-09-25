<?php
/**
 * Tenant Self-Service Payment Submission
 * Allows tenants to submit payment confirmations for verification
 */
session_start();
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/db_helpers.php';
requireRole('tenant');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../tenant/pay_now.php');
    exit;
}
requireCsrfToken();

$tenantId     = (int)$_SESSION['user_id'];
$paymentType  = trim($_POST['payment_type'] ?? 'Rent');
$amount       = (float)($_POST['amount'] ?? 0);
$month        = trim($_POST['month'] ?? date('F'));
$year         = (int)($_POST['year'] ?? date('Y'));
$method       = trim($_POST['payment_method'] ?? '');
$reference    = trim($_POST['reference'] ?? '');
$bankAccount  = trim($_POST['bank_account'] ?? '');
$payDate      = trim($_POST['payment_date'] ?? date('Y-m-d'));
$notes        = trim($_POST['notes'] ?? '');
$utilityType  = trim($_POST['utility_type'] ?? '');
$chequeNo     = trim($_POST['cheque_number'] ?? '');
$chequeBank   = trim($_POST['cheque_bank']   ?? '');

$parsedPayDate = DateTime::createFromFormat('!Y-m-d', $payDate);
if (!$parsedPayDate || $parsedPayDate->format('Y-m-d') !== $payDate) {
    header('Location: ../tenant/pay_now.php?error=' . urlencode('Enter a valid payment date.'));
    exit;
}
if ($paymentType === 'Deposit') {
    $month = $parsedPayDate->format('F');
    $year  = (int)$parsedPayDate->format('Y');
}

$validMethods = ['M-PESA','M-PESA Paybill','Airtel Money','Bank Transfer','Cheque','Other'];
if (!in_array($method, $validMethods)) { $method = 'Other'; }

// Build reference/account info based on method
if ($method === 'Bank Transfer' && $bankAccount) {
    $reference = 'Bank: ' . $bankAccount;
} elseif ($method === 'Cheque' && $chequeNo) {
    $reference = 'Cheque #' . $chequeNo . ($chequeBank ? ' — ' . $chequeBank : '');
} elseif (empty($reference)) {
    $reference = $method;
}

// Validation
if ($amount <= 0 || empty($method)) {
    header('Location: ../tenant/pay_now.php?error=' . urlencode('Amount and payment method are required.'));
    exit;
}

try {
    $pdo = getDB();

    $tuRow = $pdo->prepare(
        "SELECT tu.unit_id, u.apartment_id, u.monthly_rent,
                tu.monthly_rent AS assigned_rent, tu.deposit_amount, tu.deposit_paid
         FROM tenant_units tu
         JOIN units u ON u.id = tu.unit_id
         WHERE tu.tenant_id = :tid AND tu.status = 'active'
         LIMIT 1"
    );
    $tuRow->execute([':tid' => $tenantId]);
    $tuData = $tuRow->fetch(PDO::FETCH_ASSOC);

    if (!$tuData) {
        header('Location: ../tenant/pay_now.php?error=' . urlencode('No active unit assignment found.'));
        exit;
    }

    $unitId = $tuData['unit_id'];
    $aptId  = $tuData['apartment_id'];
    if ($paymentType === 'Rent') {
        $previousCreditStmt = $pdo->prepare(
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
        $previousCreditStmt->execute([
            ':tid' => $tenantId,
            ':year' => $year,
            ':month' => $month,
        ]);
        $previousCredit = $previousCreditStmt->fetchColumn();
        $previousCredit = $previousCredit === false ? 0.0 : abs((float)$previousCredit);
        $minimumRent = max(0.0, rentAmountDue(
            (float)($tuData['assigned_rent'] ?: $tuData['monthly_rent']),
            $month,
            $year
        ) - $previousCredit);
        if ($amount < $minimumRent) {
            header('Location: ../tenant/pay_now.php?error=' . urlencode(
                'The rent amount for ' . $month . ' ' . $year . ' is ' . formatKES($minimumRent) . ', including any applicable late penalty.'
            ));
            exit;
        }
    }

    // Check if payment_type column exists
    $columns = $pdo->query("SHOW COLUMNS FROM payments")->fetchAll(PDO::FETCH_COLUMN);
    $hasPaymentType = in_array('payment_type', $columns);

    // For deposit payments, check if already paid
    if ($paymentType === 'Deposit') {
        if (!empty($tuData['deposit_paid'])) {
            header('Location: ../tenant/pay_now.php?error=' . urlencode('Deposit already paid.'));
            exit;
        }
        $existingDeposit = $pdo->prepare(
            "SELECT status FROM payments
             WHERE tenant_id=:tid AND payment_type='Deposit'
             LIMIT 1"
        );
        $existingDeposit->execute([':tid' => $tenantId]);
        $depositStatus = $existingDeposit->fetchColumn();
        if ($depositStatus) {
            $depositMessage = $depositStatus === 'Paid'
                ? 'Deposit already paid.'
                : 'Your deposit payment is already submitted and awaiting administrator verification.';
            header('Location: ../tenant/pay_now.php?success=' . urlencode($depositMessage));
            exit;
        }
        // Accept any positive deposit payment amount. The administrator
        // verifies the submitted amount before marking the deposit paid.
    }

    // For rent payments, check for duplicate
    if ($paymentType === 'Rent') {
        if ($hasPaymentType) {
            $dupCheck = $pdo->prepare(
                "SELECT id FROM payments 
                 WHERE tenant_id = :tid AND month = :month AND year = :year AND payment_type = 'Rent'
                 LIMIT 1"
            );
        } else {
            $dupCheck = $pdo->prepare(
                "SELECT id FROM payments 
                 WHERE tenant_id = :tid AND month = :month AND year = :year
                 LIMIT 1"
            );
        }
        $dupCheck->execute([':tid' => $tenantId, ':month' => $month, ':year' => $year]);
        if ($dupCheck->fetchColumn()) {
            header('Location: ../tenant/pay_now.php?error=' . urlencode("A rent payment for {$month} {$year} has already been submitted. Check your payment history."));
            exit;
        }
    }
    
    // For utility payments, add utility type to notes
    if ($paymentType === 'Utility' && $utilityType) {
        $notes = "[{$utilityType} Utility] " . $notes;
    }

    // Status is Pending until admin/caretaker confirms
    // Cheque payments are always Pending regardless (admin must clear them)
    $status = 'Pending';

    // Build INSERT query
    $cols = 'tenant_id, unit_id, apartment_id, month, year, amount,
             payment_method, reference_number, payment_date, status, notes, created_at';
    $vals = ':tid, :uid, :aid, :month, :year, :amount,
             :method, :ref, :date, :status, :notes, NOW()';
    $params = [
        ':tid'    => $tenantId,
        ':uid'    => $unitId,
        ':aid'    => $aptId,
        ':month'  => $month,
        ':year'   => $year,
        ':amount' => $amount,
        ':method' => $method,
        ':ref'    => $reference ?: null,
        ':date'   => $payDate,
        ':status' => $status,
        ':notes'  => $notes . ($paymentType === 'Deposit' ? ' [DEPOSIT PAYMENT - Submitted by tenant]' : ($paymentType === 'Utility' ? ' [UTILITY PAYMENT - Submitted by tenant]' : ' [Submitted by tenant]')),
    ];

    // Add payment_type if column exists
    if ($hasPaymentType) {
        $cols .= ', payment_type';
        $vals .= ', :ptype';
        $params[':ptype'] = $paymentType;
    }

    // Add cheque columns if they exist and method is Cheque
    $hasChequeCol = in_array('cheque_number', $pdo->query("SHOW COLUMNS FROM payments")->fetchAll(PDO::FETCH_COLUMN));
    if ($hasChequeCol && $method === 'Cheque') {
        $cols .= ', cheque_number, cheque_bank, cheque_status';
        $vals .= ', :cheque_no, :cheque_bank, :cheque_st';
        $params[':cheque_no']  = $chequeNo   ?: null;
        $params[':cheque_bank']= $chequeBank ?: null;
        $params[':cheque_st']  = 'Pending'; // always Pending until admin clears it
    }

    $pdo->prepare("INSERT INTO payments ({$cols}) VALUES ({$vals})")->execute($params);
    $paymentId = (int)$pdo->lastInsertId();

    if ($paymentType === 'Deposit') {
        createNotification(
            $tenantId,
            'payment_received',
            'Deposit submitted for verification',
            'Your deposit payment proof was submitted and is awaiting administrator verification.',
            '../tenant/payments.php'
        );
    }

    // Log activity
    logActivity(
        $tenantId,
        'submit_payment',
        'payments',
        $paymentId,
        "{$paymentType} payment submitted - {$month} {$year} - " . formatKES($amount)
    );

    // Notify caretaker/admin about pending payment
    // Try caretaker_assignments first (v2 schema), fall back to apartments.caretaker_id
    $caretakerId = false;
    if (_hasTable('caretaker_assignments')) {
        $ctStmt = $pdo->prepare(
            "SELECT ca.caretaker_id FROM caretaker_assignments ca
             JOIN users u ON u.id = ca.caretaker_id
             WHERE ca.apartment_id = :aid AND ca.status = 'active' AND u.status = 'active'
             LIMIT 1"
        );
        $ctStmt->execute([':aid' => $aptId]);
        $caretakerId = $ctStmt->fetchColumn();
    }
    if (!$caretakerId) {
        $ctStmt = $pdo->prepare(
            "SELECT u.id FROM users u
             JOIN apartments a ON a.caretaker_id = u.id
             WHERE a.id = :aid AND u.role = 'caretaker' AND u.status = 'active'
             LIMIT 1"
        );
        $ctStmt->execute([':aid' => $aptId]);
        $caretakerId = $ctStmt->fetchColumn();
    }

    if ($caretakerId) {
        createNotification(
            $caretakerId,
            'payment_pending',
            "New payment submission",
            "Tenant {$_SESSION['name']} submitted a {$paymentType} payment of " . formatKES($amount) . " for verification.",
            'caretaker/rent.php'
        );
    }

    // Also notify admins
    $admins = $pdo->query("SELECT id FROM users WHERE role = 'admin' AND status = 'active'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($admins as $adminId) {
        createNotification(
            $adminId,
            'payment_pending',
            "New payment submission",
            "Tenant {$_SESSION['name']} submitted a {$paymentType} payment of " . formatKES($amount) . " for verification.",
            'admin/payments.php'
        );
    }

    $isForward = ($year > (int)date('Y')) || ($year === (int)date('Y') && array_search($month, ['January','February','March','April','May','June','July','August','September','October','November','December']) > ((int)date('n') - 1));
    $forwardNote = $isForward ? " This is a forward payment for a future month." : "";
    $msg = $paymentType === 'Rent'
        ? "Rent paid successfully! Your rent payment of " . formatKES($amount) . " for {$month} {$year} is pending verification.{$forwardNote} You'll be notified once confirmed."
        : "Payment submitted successfully! Your {$paymentType} payment of " . formatKES($amount) . " for {$month} {$year} is pending verification.{$forwardNote} You'll be notified once confirmed.";
    header('Location: ../tenant/pay_now.php?success=' . urlencode($msg));
    exit;

} catch (Exception $e) {
    error_log('submit_tenant_payment error: ' . $e->getMessage());
    header('Location: ../tenant/pay_now.php?error=' . urlencode('Failed to submit payment. Please try again.'));
    exit;
}
