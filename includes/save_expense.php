<?php
/**
 * Rentisha RMS — Save / Update / Delete Maintenance Expense
 */
session_start();
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/db_helpers.php';
// Both admins and caretakers can save expenses
if (!hasRole('admin') && !hasRole('caretaker')) {
    header('Location: ../index.php'); exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . (hasRole('admin') ? '../admin/expenses.php' : '../caretaker/expenses.php'));
    exit;
}
requireCsrfToken();

$pdo  = getDB();
$back = hasRole('admin') ? '../admin/expenses.php' : '../caretaker/expenses.php';

// ── DELETE ────────────────────────────────────────────────────
if (!empty($_POST['delete_id'])) {
    $id = (int)$_POST['delete_id'];
    $where = 'id=?';
    $params = [$id];
    if (hasRole('caretaker')) {
        $where .= ' AND apartment_id=?';
        $params[] = (int)($_SESSION['apartment_id'] ?? 0);
    }
    $stmt = $pdo->prepare("DELETE FROM shared_expenses WHERE {$where}");
    $stmt->execute($params);
    if ($stmt->rowCount() !== 1) {
        header('Location: ' . $back . '?error=' . urlencode('That expense could not be found or is outside your apartment.'));
        exit;
    }
    logActivity($_SESSION['user_id'], 'delete_expense', 'shared_expenses', $id, "Deleted expense #{$id}");
    header('Location: ' . $back . '?success=' . urlencode('Expense deleted.'));
    exit;
}

// ── INSERT / UPDATE ───────────────────────────────────────────
$edit_id       = (int)($_POST['edit_id']       ?? 0) ?: null;
$expense_type  = trim($_POST['expense_type']   ?? '');
$cost_category = trim($_POST['cost_category']  ?? 'Other');
$description   = trim($_POST['description']    ?? '');
$amount        = (float)($_POST['amount']      ?? 0);
$expense_date  = trim($_POST['expense_date']   ?? '') ?: null;
$vendor        = trim($_POST['vendor']         ?? '') ?: null;
$receipt_ref   = trim($_POST['receipt_ref']    ?? '') ?: null;
$apartment_id  = (int)($_POST['apartment_id']  ?? 0) ?: null;
$paid_by       = (int)($_POST['paid_by']       ?? 0) ?: null;
$month         = trim($_POST['month']          ?? date('F'));
$year          = (int)($_POST['year']          ?? date('Y'));
$status        = trim($_POST['status']         ?? 'Paid');

// Caretaker defaults: apartment from session, paid_by = themselves
if (hasRole('caretaker')) {
    if (!$apartment_id && !empty($_SESSION['apartment_id'])) {
        $apartment_id = (int)$_SESSION['apartment_id'];
    }
    if (!$paid_by) {
        $paid_by = (int)$_SESSION['user_id'];
    }
    // Caretakers can only see/edit their own apartment's expenses
    $status = $status ?: 'Paid';
}

$validTypes = ['Plumbing','Electrical','Structural','Appliances','Security',
               'Cleaning','Pest Control','Locks & Keys','Painting',
               'General Maintenance','Other'];
$validCategories = ['Labour','Materials','Equipment','Contractor','Permit','Other'];
$validStatuses   = ['Paid','Pending','Cancelled'];
$validMonths     = ['January','February','March','April','May','June',
                    'July','August','September','October','November','December'];

// Validation
if (!in_array($expense_type, $validTypes) || empty($description) || $amount <= 0) {
    header('Location: ' . $back . '?error=' . urlencode('Type, description and amount are required.'));
    exit;
}
if (!in_array($cost_category, $validCategories)) $cost_category = 'Other';
if (!in_array($status, $validStatuses))           $status        = 'Paid';
if (!in_array($month, $validMonths))              $month         = date('F');

try {
    if ($edit_id) {
        // ── UPDATE ────────────────────────────────────────────
    if (hasRole('caretaker')) {
        $scope = $pdo->prepare(
            'SELECT id FROM shared_expenses WHERE id=? AND apartment_id=? LIMIT 1'
        );
        $scope->execute([$edit_id, (int)($_SESSION['apartment_id'] ?? 0)]);
        if (!$scope->fetchColumn()) {
            header('Location: ' . $back . '?error=' . urlencode('That expense could not be found or is outside your apartment.'));
            exit;
        }
    }
    $pdo->prepare(
            "UPDATE shared_expenses SET
               expense_type  = ?,
               cost_category = ?,
               description   = ?,
               amount        = ?,
               expense_date  = ?,
               vendor        = ?,
               receipt_ref   = ?,
               apartment_id  = ?,
               paid_by       = ?,
               month         = ?,
               year          = ?,
               status        = ?
             WHERE id = ?"
        )->execute([
            $expense_type, $cost_category, $description,
            $amount, $expense_date, $vendor, $receipt_ref,
            $apartment_id, $paid_by,
            $month, $year, $status,
            $edit_id,
        ]);

        logActivity($_SESSION['user_id'], 'update_expense', 'shared_expenses', $edit_id,
            "{$expense_type} — " . formatKES($amount));

        header('Location: ' . $back . '?success=' . urlencode('Expense updated successfully.'));
        exit;

    } else {
        // ── INSERT ────────────────────────────────────────────
        $pdo->prepare(
            "INSERT INTO shared_expenses
               (expense_type, cost_category, description, amount,
                expense_date, vendor, receipt_ref,
                apartment_id, paid_by,
                month, year, status, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())"
        )->execute([
            $expense_type, $cost_category, $description, $amount,
            $expense_date, $vendor, $receipt_ref,
            $apartment_id, $paid_by,
            $month, $year, $status,
        ]);

        $newId = (int)$pdo->lastInsertId();
        logActivity($_SESSION['user_id'], 'add_expense', 'shared_expenses', $newId,
            "{$expense_type} — " . formatKES($amount) . " — {$month} {$year}");

        // Notify admins when a caretaker reports an expense
        if (hasRole('caretaker')) {
            $admins = $pdo->query(
                "SELECT id FROM users WHERE role='admin' AND status='active'"
            )->fetchAll(PDO::FETCH_COLUMN);
            $aptName = !empty($_SESSION['apartment']) ? $_SESSION['apartment'] : 'an apartment';
            foreach ($admins as $adminId) {
                createNotification(
                    (int)$adminId,
                    'expense_reported',
                    "Maintenance expense reported",
                    "{$_SESSION['name']} reported a {$expense_type} expense of " . formatKES($amount) . " for {$aptName}.",
                    'admin/expenses.php'
                );
            }
        }

        header('Location: ' . $back . '?success=' . urlencode('Expense logged successfully.'));
        exit;
    }

} catch (Exception $e) {
    error_log('save_expense error: ' . $e->getMessage());
    header('Location: ' . $back . '?error=' . urlencode('Failed to save expense. Please try again.'));
    exit;
}
