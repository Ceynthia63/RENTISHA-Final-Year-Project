<?php
/**
 * Rentisha RMS — Payment Handler
 *
 * action=record  → Admin/Caretaker records a new payment
 * action=confirm → Admin confirms a pending/overdue payment as Paid
 * action=remind  → Send payment reminder notification to tenant
 */
session_start();
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/db_helpers.php';

if (!hasRole('admin') && !hasRole('caretaker')) {
    header('Location: ../index.php'); exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../admin/payments.php'); exit;
}
requireCsrfToken();

$action = trim($_POST['action'] ?? 'record');
$back   = hasRole('admin') ? '../admin/payments.php' : '../caretaker/rent.php';

// ═══════════════════════════════════════════════════════════
//  ACTION: RECORD — create a new payment entry
// ═══════════════════════════════════════════════════════════
if ($action === 'record') {

    $tenantId     = (int)($_POST['tenant_id']      ?? 0);
    $month        = trim($_POST['month']            ?? '');
    $year         = (int)($_POST['year']            ?? date('Y'));
    $amount       = (float)($_POST['amount']        ?? 0);
    $method       = trim($_POST['payment_method']   ?? 'M-PESA Paybill');
    $reference    = trim($_POST['reference_number'] ?? '');
    $payDate      = trim($_POST['payment_date']     ?? date('Y-m-d')) ?: null;
    $status       = trim($_POST['status']           ?? 'Paid');
    $notes        = trim($_POST['notes']            ?? '');
    $chequeNo     = trim($_POST['cheque_number']    ?? '');
    $chequeBank   = trim($_POST['cheque_bank']      ?? '');
    $chequeStatus = trim($_POST['cheque_status']    ?? '');

    $validMonths  = ['January','February','March','April','May','June',
                     'July','August','September','October','November','December'];
    $validStatuses= ['Paid','Pending','Partial','Overdue','Waived'];
    // Full list matching the expanded payment_method ENUM in schema v2
    $validMethods = ['M-PESA','M-PESA Paybill','Airtel Money','PDQ/POS',
                     'Cheque','Cash','Bank Transfer','Other'];
    $validChequeStatuses = ['Pending','Cleared','Bounced'];

    if (!$tenantId || !in_array($month, $validMonths) || $amount <= 0) {
        header('Location: ' . $back . '?error=' . urlencode('Tenant, month and amount are required.')); exit;
    }
    if (!in_array($status, $validStatuses))  { $status = 'Paid'; }
    if (!in_array($method, $validMethods))   { $method = 'Other'; }
    // Validate cheque_status against its ENUM; blank → null
    if (!in_array($chequeStatus, $validChequeStatuses)) { $chequeStatus = ''; }

    // Cheque-specific: only Cleared cheques count as Paid
    if ($method === 'Cheque' && $chequeStatus !== 'Cleared') {
        $status = 'Pending';
    }

    try {
        $pdo = getDB();

        // Get unit_id, apartment_id and monthly rent for this tenant
        $tuRow = $pdo->prepare(
            "SELECT tu.unit_id, u.apartment_id AS apt_id, u.monthly_rent, tu.monthly_rent AS assigned_rent
             FROM tenant_units tu
             JOIN units u ON u.id = tu.unit_id
             WHERE tu.tenant_id = :tid AND tu.status = 'active'
             LIMIT 1"
        );
        $tuRow->execute([':tid' => $tenantId]);
        $tuData   = $tuRow->fetch(PDO::FETCH_ASSOC);
        $unitId   = $tuData['unit_id'] ?? null;
        $aptId    = $tuData['apt_id']  ?? null;
        $expectedRent = (float)($tuData['assigned_rent'] ?? $tuData['monthly_rent'] ?? 0);

        // Caretaker scope check
        if (hasRole('caretaker') && !empty($_SESSION['apartment_id'])) {
            if ($aptId && (int)$aptId !== (int)$_SESSION['apartment_id']) {
                header('Location: ' . $back . '?error=' . urlencode('You can only record payments for your apartment.')); exit;
            }
        }

        // ── OVERPAYMENT / UNDERPAYMENT TRACKING ──────────────────
        // Get previous month's credit (if any)
        $prevCredit = 0;
        $prevPayment = $pdo->prepare(
            "SELECT balance FROM payments 
             WHERE tenant_id = :tid 
               AND balance < 0
               AND (year < :year OR (year = :year AND FIELD(month,
                 'January','February','March','April','May','June',
                 'July','August','September','October','November','December')
                 < FIELD(:month,
                 'January','February','March','April','May','June',
                 'July','August','September','October','November','December')))
             ORDER BY year DESC, FIELD(month,
               'January','February','March','April','May','June',
               'July','August','September','October','November','December') DESC
             LIMIT 1"
        );
        $prevPayment->execute([':tid' => $tenantId, ':year' => $year, ':month' => $month]);
        $prevBalance = $prevPayment->fetchColumn();
        if ($prevBalance !== false && $prevBalance < 0) {
            $prevCredit = abs((float)$prevBalance); // Credit is stored as negative
        }

        // Calculate balance after applying credit
        $amountAfterCredit = $amount + $prevCredit;
        $balance = $expectedRent - $amountAfterCredit;

        // If balance is negative, it means overpayment (credit for next month)
        // If balance is positive, it means underpayment (still owed)

        // Determine payment status based on balance
        if ($balance <= 0) {
            $status = 'Paid'; // Fully paid or overpaid
        } elseif ($balance > 0 && $balance < $expectedRent) {
            $status = 'Partial'; // Partially paid
        } else {
            $status = $status; // Keep original status (Pending/Overdue)
        }

        // Clear ONLY the specific credit row that was applied — identified by the
        // same query used above (most recent balance < 0 before this period).
        // Using a targeted WHERE clause avoids accidentally wiping unrelated credits.
        if ($prevCredit > 0) {
            $pdo->prepare(
                "UPDATE payments SET balance = 0
                 WHERE tenant_id = :tid
                   AND balance < 0
                   AND (year < :year OR (year = :year AND FIELD(month,
                     'January','February','March','April','May','June',
                     'July','August','September','October','November','December')
                     < FIELD(:month,
                     'January','February','March','April','May','June',
                     'July','August','September','October','November','December')))
                 ORDER BY year DESC, FIELD(month,
                   'January','February','March','April','May','June',
                   'July','August','September','October','November','December') DESC
                 LIMIT 1"
            )->execute([':tid' => $tenantId, ':year' => $year, ':month' => $month]);
        }

        // ── PAYMENT SEQUENCING ENFORCEMENT ───────────────────
        // Check for any outstanding (Pending/Overdue) payments BEFORE this month/year
        $seq = $pdo->prepare(
            "SELECT id, month, year FROM payments
             WHERE tenant_id = :tid
               AND status IN ('Pending','Overdue')
               AND (year < :year OR (year = :year AND FIELD(month,
                 'January','February','March','April','May','June',
                 'July','August','September','October','November','December')
                 < FIELD(:month,
                 'January','February','March','April','May','June',
                 'July','August','September','October','November','December')))
             LIMIT 1"
        );
        $seq->execute([':tid' => $tenantId, ':year' => $year, ':month' => $month]);
        $earlier = $seq->fetch(PDO::FETCH_ASSOC);
        if ($earlier && $status === 'Paid') {
            header('Location: ' . $back . '?error=' . urlencode(
                "Cannot mark {$month} {$year} as Paid. Please clear the outstanding " .
                $earlier['month'] . ' ' . $earlier['year'] . ' balance first.'
            )); exit;
        }

        // Check duplicate payment for same month/year
        $dup = $pdo->prepare(
            "SELECT id FROM payments WHERE tenant_id=:tid AND month=:m AND year=:y LIMIT 1"
        );
        $dup->execute([':tid'=>$tenantId,':m'=>$month,':y'=>$year]);
        if ($dup->fetchColumn()) {
            header('Location: ' . $back . '?error=' . urlencode(
                "A payment record for {$month} {$year} already exists for this tenant. Edit it instead."
            )); exit;
        }

        // Insert payment — build query defensively in case cheque/receipt columns
        // were not yet added to a pre-migration database.
        $hasCheque  = $pdo->query("SHOW COLUMNS FROM payments LIKE 'cheque_number'")->fetchColumn();
        $hasReceipt = $pdo->query("SHOW COLUMNS FROM payments LIKE 'receipt_number'")->fetchColumn();
        $hasConfirm = $pdo->query("SHOW COLUMNS FROM payments LIKE 'confirmed_by'")->fetchColumn();
        $hasBalance = $pdo->query("SHOW COLUMNS FROM payments LIKE 'balance'")->fetchColumn();

        $pCols   = 'tenant_id, unit_id, apartment_id, month, year, amount,
                    payment_method, reference_number, payment_date, status, notes, recorded_by, created_at';
        $pVals   = ':tid, :uid, :aid, :month, :year, :amount,
                    :method, :ref, :date, :status, :notes, :by, NOW()';
        $pParams = [
            ':tid'    => $tenantId,  ':uid'   => $unitId,
            ':aid'    => $aptId,     ':month' => $month,
            ':year'   => $year,      ':amount'=> $amount,
            ':method' => $method,    ':ref'   => $reference ?: null,
            ':date'   => $payDate,   ':status'=> $status,
            ':notes'  => $notes,     ':by'    => $_SESSION['user_id'],
        ];

        if ($hasBalance) {
            $pCols  .= ', expected_amount, balance, carried_credit';
            $pVals  .= ', :expected, :balance, :credit';
            $pParams[':expected'] = $expectedRent;
            $pParams[':balance']  = $balance;
            $pParams[':credit']   = $prevCredit;
        }

        if ($hasCheque) {
            $pCols  .= ', cheque_number, cheque_bank, cheque_status';
            $pVals  .= ', :cheque_no, :cheque_bank, :cheque_status';
            $pParams[':cheque_no']     = $chequeNo    ?: null;
            $pParams[':cheque_bank']   = $chequeBank  ?: null;
            $pParams[':cheque_status'] = $chequeStatus ?: null;
        }

        $pdo->prepare("INSERT INTO payments ({$pCols}) VALUES ({$pVals})")
            ->execute($pParams);
        $payId = (int)$pdo->lastInsertId();

        // Build success message
        $msg = "Payment of " . formatKES($amount) . " for {$month} {$year} recorded successfully.";
        
        if ($hasBalance) {
            if ($prevCredit > 0) {
                $msg .= " Credit of " . formatKES($prevCredit) . " applied from previous month.";
            }
            if ($balance < 0) {
                $msg .= " Overpayment of " . formatKES(abs($balance)) . " will be credited to next month.";
            } elseif ($balance > 0 && $status === 'Partial') {
                $msg .= " Remaining balance: " . formatKES($balance) . " (Partial payment).";
            }
        }

        // Notify tenant if Paid
        if ($status === 'Paid') {
            $notifBody = "Your rent payment of " . formatKES($amount) . " for {$month} {$year} has been confirmed.";
            if ($hasBalance && $balance < 0) {
                $notifBody .= " You have a credit of " . formatKES(abs($balance)) . " for next month.";
            }
            createNotification(
                $tenantId,
                'payment_received',
                "Payment confirmed — {$month} {$year}",
                $notifBody,
                'tenant/payments.php'
            );
        } elseif ($status === 'Partial') {
            createNotification(
                $tenantId,
                'payment_partial',
                "Partial payment received — {$month} {$year}",
                "Partial payment of " . formatKES($amount) . " received. Remaining balance: " . formatKES($balance) . ".",
                'tenant/payments.php'
            );
        }

        logActivity($_SESSION['user_id'], 'record_payment', 'payments', $payId,
            "{$month} {$year} — " . formatKES($amount) . " — tenant #{$tenantId}");

        header('Location: ' . $back . '?success=' . urlencode($msg)); exit;

    } catch (Exception $e) {
        error_log('save_payment record error: ' . $e->getMessage());
        header('Location: ' . $back . '?error=' . urlencode('Failed to save payment. Please try again.')); exit;
    }
}

// ═══════════════════════════════════════════════════════════
//  ACTION: CONFIRM — mark pending/overdue payment as Paid
// ═══════════════════════════════════════════════════════════
if ($action === 'confirm') {

    $paymentId = (int)($_POST['payment_id'] ?? 0);
    if (!$paymentId) {
        header('Location: ' . $back . '?error=' . urlencode('Invalid payment.')); exit;
    }

    try {
        $pdo = getDB();

        // Fetch payment
        $pay = $pdo->prepare(
            "SELECT p.*, u.full_name AS tenant_name
             FROM payments p JOIN users u ON u.id = p.tenant_id
             WHERE p.id = :id LIMIT 1"
        );
        $pay->execute([':id' => $paymentId]);
        $payRow = $pay->fetch(PDO::FETCH_ASSOC);
        if (!$payRow) {
            header('Location: ' . $back . '?error=' . urlencode('Payment not found.')); exit;
        }

        // Cheque check: don't confirm bounced cheque
        if ($payRow['payment_method'] === 'Cheque' && $payRow['cheque_status'] === 'Bounced') {
            header('Location: ' . $back . '?error=' . urlencode('Cannot confirm — cheque has bounced.')); exit;
        }

        // Confirm it — use confirmed_by only if the column exists
        // Also sync cheque_status → Cleared when confirming a cheque payment that was Pending
        $hasConfirmCol  = $pdo->query("SHOW COLUMNS FROM payments LIKE 'confirmed_by'")->fetchColumn();
        $hasChequeCol   = $pdo->query("SHOW COLUMNS FROM payments LIKE 'cheque_status'")->fetchColumn();

        $setClauses = ["status='Paid'", "payment_date=COALESCE(payment_date, CURDATE())", "updated_at=NOW()"];
        $confirmParams = [':id' => $paymentId];
        if ($hasConfirmCol) {
            $setClauses[] = 'confirmed_by=:by';
            $confirmParams[':by'] = $_SESSION['user_id'];
        }
        // Auto-clear a pending cheque when admin explicitly confirms it
        if ($hasChequeCol && $payRow['payment_method'] === 'Cheque' && $payRow['cheque_status'] === 'Pending') {
            $setClauses[] = "cheque_status='Cleared'";
        }
        $isDeposit = isset($payRow['payment_type']) && $payRow['payment_type'] === 'Deposit';
        if ($isDeposit && !hasRole('admin')) {
            header('Location: ' . $back . '?error=' . urlencode('Only an administrator can verify a deposit payment.')); exit;
        }
        $confirmSql = "UPDATE payments SET " . implode(', ', $setClauses) . " WHERE id=:id";
        $pdo->prepare($confirmSql)->execute($confirmParams);

        // Deposit payments activate a prospective tenant only after an
        // administrator confirms the payment.
        if ($isDeposit) {
            $pdo->prepare(
                "UPDATE tenant_units
                 SET deposit_paid=1, deposit_paid_date=CURDATE(), updated_at=NOW()
                 WHERE tenant_id=:tid AND status='active'"
            )->execute([':tid' => $payRow['tenant_id']]);
            $pdo->prepare(
                "UPDATE users SET status='active', updated_at=NOW()
                 WHERE id=:tid AND role='tenant' AND status='prospective'"
            )->execute([':tid' => $payRow['tenant_id']]);
            createNotification(
                (int)$payRow['tenant_id'],
                'payment_received',
                'Deposit verified and account activated',
                'Your deposit payment was verified by the administrator. Your tenant account is now active.',
                '../tenant/dashboard.php'
            );
        }

        // Notify tenant
        createNotification(
            (int)$payRow['tenant_id'],
            'payment_received',
            "Payment confirmed — {$payRow['month']} {$payRow['year']}",
            "Your rent of " . formatKES($payRow['amount']) . " for {$payRow['month']} {$payRow['year']} has been confirmed.",
            'tenant/payments.php'
        );

        logActivity($_SESSION['user_id'], 'confirm_payment', 'payments', $paymentId,
            $payRow['month'] . ' ' . $payRow['year'] . ' — ' . $payRow['tenant_name']);

        header('Location: ' . $back . '?success=' . urlencode(
            "Payment confirmed for {$payRow['tenant_name']} — {$payRow['month']} {$payRow['year']}."
        )); exit;

    } catch (Exception $e) {
        error_log('confirm_payment error: ' . $e->getMessage());
        header('Location: ' . $back . '?error=' . urlencode('Failed to confirm payment.')); exit;
    }
}

// ═══════════════════════════════════════════════════════════
//  ACTION: UPDATE_CHEQUE — update cheque_status for a payment
//  Only admins can mark a cheque Cleared or Bounced
// ═══════════════════════════════════════════════════════════
if ($action === 'update_cheque') {

    if (!hasRole('admin')) {
        header('Location: ' . $back . '?error=' . urlencode('Only admins can update cheque status.')); exit;
    }

    $paymentId    = (int)($_POST['payment_id']    ?? 0);
    $chequeStatus = trim($_POST['cheque_status']  ?? '');
    $notes        = trim($_POST['notes']          ?? '');

    if (!$paymentId || !in_array($chequeStatus, ['Pending','Cleared','Bounced'])) {
        header('Location: ' . $back . '?error=' . urlencode('Invalid payment or cheque status.')); exit;
    }

    try {
        $pdo = getDB();

        // Fetch payment
        $pay = $pdo->prepare(
            "SELECT p.*, u.full_name AS tenant_name
             FROM payments p JOIN users u ON u.id = p.tenant_id
             WHERE p.id = :id LIMIT 1"
        );
        $pay->execute([':id' => $paymentId]);
        $payRow = $pay->fetch(PDO::FETCH_ASSOC);
        if (!$payRow) {
            header('Location: ' . $back . '?error=' . urlencode('Payment not found.')); exit;
        }
        if ($payRow['payment_method'] !== 'Cheque') {
            header('Location: ' . $back . '?error=' . urlencode('This payment is not a cheque payment.')); exit;
        }

        // Determine overall payment status based on new cheque status
        $newPaymentStatus = $payRow['status'];
        if ($chequeStatus === 'Cleared') {
            $newPaymentStatus = 'Paid';
        } elseif ($chequeStatus === 'Bounced') {
            $newPaymentStatus = 'Pending'; // revert to Pending; admin can decide next step
        }

        $hasConfirmCol = $pdo->query("SHOW COLUMNS FROM payments LIKE 'confirmed_by'")->fetchColumn();

        $setClauses  = ["cheque_status=:cs", "status=:st", "updated_at=NOW()"];
        $updateParams = [':cs' => $chequeStatus, ':st' => $newPaymentStatus, ':id' => $paymentId];
        if ($chequeStatus === 'Cleared') {
            $setClauses[] = "payment_date=COALESCE(payment_date, CURDATE())";
            if ($hasConfirmCol) {
                $setClauses[] = "confirmed_by=:by";
                $updateParams[':by'] = $_SESSION['user_id'];
            }
        }
        // Append any admin notes
        if ($notes) {
            $setClauses[] = "notes=CONCAT(COALESCE(notes,''), :noteappend)";
            $updateParams[':noteappend'] = "\n[Cheque {$chequeStatus} — " . date('d M Y') . "]" . ($notes ? ": {$notes}" : '');
        }

        $pdo->prepare("UPDATE payments SET " . implode(', ', $setClauses) . " WHERE id=:id")
            ->execute($updateParams);

        // Notify tenant of cheque outcome
        if ($chequeStatus === 'Cleared') {
            createNotification(
                (int)$payRow['tenant_id'],
                'payment_received',
                "Cheque cleared — {$payRow['month']} {$payRow['year']}",
                "Your cheque payment of " . formatKES($payRow['amount']) . " for {$payRow['month']} {$payRow['year']} has been cleared and confirmed.",
                'tenant/payments.php'
            );
        } elseif ($chequeStatus === 'Bounced') {
            createNotification(
                (int)$payRow['tenant_id'],
                'payment_failed',
                "Cheque bounced — {$payRow['month']} {$payRow['year']}",
                "Your cheque payment of " . formatKES($payRow['amount']) . " for {$payRow['month']} {$payRow['year']} was returned unpaid. Please contact management immediately.",
                'tenant/payments.php'
            );
        }

        logActivity($_SESSION['user_id'], 'update_cheque', 'payments', $paymentId,
            "Cheque status → {$chequeStatus} for {$payRow['tenant_name']} — {$payRow['month']} {$payRow['year']}");

        header('Location: ' . $back . '?success=' . urlencode(
            "Cheque status updated to {$chequeStatus} for {$payRow['tenant_name']} — {$payRow['month']} {$payRow['year']}."
        )); exit;

    } catch (Exception $e) {
        error_log('update_cheque error: ' . $e->getMessage());
        header('Location: ' . $back . '?error=' . urlencode('Failed to update cheque status.')); exit;
    }
}

// Fallback
header('Location: ' . $back); exit;
