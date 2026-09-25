<?php
/**
 * Rentisha RMS — Save / Update / Delete Utility Bill
 */
session_start();
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/db_helpers.php';

// Both caretakers and admins can manage utility bills
if (!hasRole('caretaker') && !hasRole('admin')) {
    header('Location: ../index.php'); exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . (hasRole('admin') ? '../admin/payments.php' : '../caretaker/utilities.php'));
    exit;
}
requireCsrfToken();

$action = $_POST['action'] ?? '';
$back   = hasRole('admin') ? '../admin/payments.php' : '../caretaker/utilities.php';

try {
    $pdo = getDB();

    // ═══════════════════════════════════════════════════════════════
    // CREATE / UPDATE UTILITY BILL
    // ═══════════════════════════════════════════════════════════════
    if ($action === 'create' || $action === 'update') {
        $billId       = (int)($_POST['bill_id'] ?? 0) ?: null;
        $apartmentId  = (int)($_POST['apartment_id'] ?? 0);
        $tenantId     = (int)($_POST['tenant_id'] ?? 0);
        $billType     = trim($_POST['bill_type'] ?? '');
        $description  = trim($_POST['description'] ?? '');
        $amount       = (float)($_POST['amount'] ?? 0);
        $dueDate      = trim($_POST['due_date'] ?? '');
        $billingMonth = trim($_POST['billing_month'] ?? '');
        $billingYear  = (int)($_POST['billing_year'] ?? date('Y'));

        // Validation
        if (!$tenantId || !$billType || $amount <= 0 || !$dueDate || !$billingMonth) {
            header('Location: ' . $back . '?error=' . urlencode('All required fields must be filled.'));
            exit;
        }

        // Get unit_id from tenant
        $tuRow = $pdo->prepare(
            "SELECT tu.unit_id FROM tenant_units tu
             WHERE tu.tenant_id = :tid AND tu.status = 'active' LIMIT 1"
        );
        $tuRow->execute([':tid' => $tenantId]);
        $unitId = $tuRow->fetchColumn();

        if (!$unitId) {
            header('Location: ' . $back . '?error=' . urlencode('Tenant has no active unit assignment.'));
            exit;
        }

        // Determine status based on due date
        $today = date('Y-m-d');
        $status = ($dueDate < $today) ? 'Overdue' : 'Pending';

        if ($billId) {
            // UPDATE
            $pdo->prepare(
                "UPDATE utility_bills SET
                   tenant_id = :tid, unit_id = :uid, apartment_id = :aid,
                   bill_type = :type, description = :desc, amount = :amount,
                   billing_month = :month, billing_year = :year,
                   due_date = :due, status = :status
                 WHERE id = :id"
            )->execute([
                ':tid'   => $tenantId,
                ':uid'   => $unitId,
                ':aid'   => $apartmentId,
                ':type'  => $billType,
                ':desc'  => $description,
                ':amount'=> $amount,
                ':month' => $billingMonth,
                ':year'  => $billingYear,
                ':due'   => $dueDate,
                ':status'=> $status,
                ':id'    => $billId,
            ]);
            $msg = 'Utility bill updated successfully.';
        } else {
            // CREATE
            $pdo->prepare(
                "INSERT INTO utility_bills
                   (tenant_id, unit_id, apartment_id, bill_type, description,
                    amount, billing_month, billing_year, due_date, status,
                    issued_by, created_at)
                 VALUES
                   (:tid, :uid, :aid, :type, :desc,
                    :amount, :month, :year, :due, :status,
                    :issued, NOW())"
            )->execute([
                ':tid'    => $tenantId,
                ':uid'    => $unitId,
                ':aid'    => $apartmentId,
                ':type'   => $billType,
                ':desc'   => $description,
                ':amount' => $amount,
                ':month'  => $billingMonth,
                ':year'   => $billingYear,
                ':due'    => $dueDate,
                ':status' => $status,
                ':issued' => $_SESSION['user_id'],
            ]);
            $billId = (int)$pdo->lastInsertId();
            $msg = 'Utility bill created successfully.';

            // Notify tenant
            createNotification(
                $tenantId,
                'utility_bill',
                "New utility bill — {$billType}",
                "A {$billType} bill of " . formatKES($amount) . " for {$billingMonth} {$billingYear} has been issued. Due: " . formatDate($dueDate) . ".",
                'tenant/expenses.php'
            );
        }

        logActivity(
            $_SESSION['user_id'],
            $billId ? 'update_utility_bill' : 'create_utility_bill',
            'utility_bills',
            $billId,
            "{$billType} - {$billingMonth} {$billingYear} - " . formatKES($amount)
        );

        header('Location: ' . $back . '?success=' . urlencode($msg));
        exit;
    }

    // ═══════════════════════════════════════════════════════════════
    // MARK AS PAID
    // ═══════════════════════════════════════════════════════════════
    if ($action === 'mark_paid') {
        $billId = (int)($_POST['bill_id'] ?? 0);
        if (!$billId) {
            header('Location: ' . $back . '?error=' . urlencode('Invalid bill ID.'));
            exit;
        }

        $bill = $pdo->prepare("SELECT * FROM utility_bills WHERE id = :id LIMIT 1");
        $bill->execute([':id' => $billId]);
        $billData = $bill->fetch(PDO::FETCH_ASSOC);

        if (!$billData) {
            header('Location: ' . $back . '?error=' . urlencode('Bill not found.'));
            exit;
        }

        // Update status
        $pdo->prepare(
            "UPDATE utility_bills SET status = 'Paid', paid_date = CURDATE()
             WHERE id = :id"
        )->execute([':id' => $billId]);

        // Notify tenant
        createNotification(
            $billData['tenant_id'],
            'utility_paid',
            "Utility bill payment confirmed",
            "Your {$billData['bill_type']} payment of " . formatKES($billData['amount']) . " for {$billData['billing_month']} {$billData['billing_year']} has been confirmed.",
            'tenant/expenses.php'
        );

        logActivity(
            $_SESSION['user_id'],
            'confirm_utility_payment',
            'utility_bills',
            $billId,
            "{$billData['bill_type']} - " . formatKES($billData['amount'])
        );

        header('Location: ' . $back . '?success=' . urlencode('Utility bill marked as paid.'));
        exit;
    }

    // ═══════════════════════════════════════════════════════════════
    // DELETE BILL
    // ═══════════════════════════════════════════════════════════════
    if ($action === 'delete') {
        $billId = (int)($_POST['bill_id'] ?? 0);
        if (!$billId) {
            header('Location: ' . $back . '?error=' . urlencode('Invalid bill ID.'));
            exit;
        }

        $pdo->prepare("DELETE FROM utility_bills WHERE id = :id")->execute([':id' => $billId]);

        logActivity(
            $_SESSION['user_id'],
            'delete_utility_bill',
            'utility_bills',
            $billId,
            'Deleted'
        );

        header('Location: ' . $back . '?success=' . urlencode('Utility bill deleted successfully.'));
        exit;
    }

} catch (Exception $e) {
    error_log('save_utility_bill error: ' . $e->getMessage());
    header('Location: ' . $back . '?error=' . urlencode('Failed to save utility bill. Please try again.'));
    exit;
}

header('Location: ' . $back);
exit;
