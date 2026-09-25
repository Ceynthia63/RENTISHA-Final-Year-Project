<?php
/**
 * Tenant Utility Bill Submission
 * Separate handler for utility bills (Water, Electricity, etc.)
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
$utilityType  = trim($_POST['utility_type'] ?? 'Water');
$amount       = (float)($_POST['amount'] ?? 0);
$month        = trim($_POST['month'] ?? date('F'));
$year         = (int)($_POST['year'] ?? date('Y'));
$method       = trim($_POST['payment_method'] ?? '');
$reference    = trim($_POST['reference'] ?? '');
$bankAccount  = trim($_POST['bank_account'] ?? '');
$payDate      = trim($_POST['payment_date'] ?? date('Y-m-d'));
$notes        = trim($_POST['notes'] ?? '');

// Build reference/account info based on method
if ($method === 'Bank Transfer' && $bankAccount) {
    $reference = 'Bank: ' . $bankAccount;
} elseif (empty($reference)) {
    $reference = $method;
}

// Validation
if ($amount <= 0 || empty($method) || empty($utilityType)) {
    header('Location: ../tenant/pay_now.php?error=' . urlencode('Amount, utility type, and payment method are required.'));
    exit;
}

$validUtilities = ['Water', 'Electricity', 'Garbage', 'Security', 'Internet', 'Gas', 'Other'];
if (!in_array($utilityType, $validUtilities)) {
    $utilityType = 'Other';
}

try {
    $pdo = getDB();

    $tuRow = $pdo->prepare(
        "SELECT tu.unit_id, u.apartment_id
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

    // Check if utility_bills table exists
    $tables = $pdo->query("SHOW TABLES LIKE 'utility_bills'")->fetchColumn();
    
    if (!$tables) {
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
            ':ref'    => $reference,
            ':date'   => $payDate,
            ':status' => 'Pending',
            ':notes'  => "[{$utilityType} Utility Bill] " . $notes . ' [Submitted by tenant]',
        ];
        
        // Check if payment_type column exists
        $columns = $pdo->query("SHOW COLUMNS FROM payments")->fetchAll(PDO::FETCH_COLUMN);
        if (in_array('payment_type', $columns)) {
            $cols .= ', payment_type';
            $vals .= ', :ptype';
            $params[':ptype'] = 'Utility';
        }
        
        $pdo->prepare("INSERT INTO payments ({$cols}) VALUES ({$vals})")->execute($params);
        $billId = (int)$pdo->lastInsertId();
        
    } else {
        // Use dedicated utility_bills table
        // Columns: bill_type, billing_month, billing_year, amount, reference, due_date, status

        // Check for duplicate bill (same tenant + type + month + year)
        $dupCheck = $pdo->prepare(
            "SELECT id FROM utility_bills 
             WHERE tenant_id = :tid AND bill_type = :type 
               AND billing_month = :month AND billing_year = :year
             LIMIT 1"
        );
        $dupCheck->execute([
            ':tid'   => $tenantId,
            ':type'  => $utilityType,
            ':month' => $month,
            ':year'  => $year,
        ]);
        if ($dupCheck->fetchColumn()) {
            header('Location: ../tenant/pay_now.php?error=' . urlencode("{$utilityType} bill for {$month} {$year} already submitted."));
            exit;
        }

        // Calculate a due date: end of the billing month
        $dueDate = date('Y-m-t', strtotime("{$year}-" . date('m', strtotime($month)) . "-01"));

        $pdo->prepare(
            "INSERT INTO utility_bills
                (tenant_id, unit_id, apartment_id,
                 bill_type, billing_month, billing_year,
                 amount, reference, due_date, status,
                 description, created_at)
             VALUES
                (:tid, :uid, :aid,
                 :type, :month, :year,
                 :amount, :ref, :due, 'Pending',
                 :desc, NOW())"
        )->execute([
            ':tid'    => $tenantId,
            ':uid'    => $unitId,
            ':aid'    => $aptId,
            ':type'   => $utilityType,
            ':month'  => $month,
            ':year'   => $year,
            ':amount' => $amount,
            ':ref'    => $reference ?: null,
            ':due'    => $dueDate,
            ':desc'   => ($notes ? $notes . ' ' : '') . "[Submitted by tenant via " . $method . "]",
        ]);

        $billId = (int)$pdo->lastInsertId();
    }

    // Log activity
    logActivity(
        $tenantId,
        'submit_utility_bill',
        $tables ? 'utility_bills' : 'payments',
        $billId,
        "{$utilityType} bill submitted - {$month} {$year} - " . formatKES($amount)
    );

    // Notify caretaker/admin
    $ctStmt = $pdo->prepare(
        "SELECT u.id FROM users u 
         JOIN apartments a ON a.caretaker_id = u.id
         WHERE a.id = :aid AND u.role = 'caretaker' AND u.status = 'active'
         LIMIT 1"
    );
    $ctStmt->execute([':aid' => $aptId]);
    $caretakerId = $ctStmt->fetchColumn();

    if ($caretakerId) {
        createNotification(
            $caretakerId,
            'utility_bill_pending',
            "New utility bill submission",
            "Tenant {$_SESSION['name']} submitted a {$utilityType} bill of " . formatKES($amount) . " for {$month} {$year}.",
            'caretaker/utilities.php'
        );
    }

    // Notify admins
    $admins = $pdo->query("SELECT id FROM users WHERE role = 'admin' AND status = 'active'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($admins as $adminId) {
        createNotification(
            $adminId,
            'utility_bill_pending',
            "New utility bill submission",
            "Tenant {$_SESSION['name']} submitted a {$utilityType} bill of " . formatKES($amount) . " for {$month} {$year}.",
            'admin/payments.php'
        );
    }

    $msg = "Utility bill submitted successfully! Your {$utilityType} bill of " . formatKES($amount) . " for {$month} {$year} is pending verification.";
    header('Location: ../tenant/payments.php?success=' . urlencode($msg));
    exit;

} catch (Exception $e) {
    error_log('submit_utility_bill error: ' . $e->getMessage());
    header('Location: ../tenant/pay_now.php?error=' . urlencode('Failed to submit utility bill: ' . $e->getMessage()));
    exit;
}
