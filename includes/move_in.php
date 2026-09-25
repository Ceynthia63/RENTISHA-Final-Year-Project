<?php

session_start();
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/db_helpers.php';

if (!hasRole('admin') && !hasRole('caretaker')) {
    header('Location: ../index.php'); exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../admin/tenants.php'); exit;
}
requireCsrfToken();

$back      = hasRole('admin') ? '../admin/tenants.php' : '../caretaker/tenants.php';
$tenantId  = (int)($_POST['tenant_id']    ?? 0);
$unitId    = (int)($_POST['unit_id']      ?? 0);
$moveInDate= trim($_POST['move_in_date']  ?? date('Y-m-d'));
$depositAmount = (float)($_POST['deposit_amount'] ?? 0);
$notes     = trim($_POST['notes']         ?? '');

if (!$tenantId || !$unitId) {
    header('Location: ' . $back . '?error=' . urlencode('Tenant and unit are required.'));
    exit;
}
$parsedMoveInDate = DateTime::createFromFormat('!Y-m-d', $moveInDate);
if (!$parsedMoveInDate || $parsedMoveInDate->format('Y-m-d') !== $moveInDate) {
    header('Location: ' . $back . '?error=' . urlencode('Enter a valid move-in date.'));
    exit;
}

try {
    $pdo = getDB();
    $pdo->beginTransaction();

    // ── Verify tenant exists and is active ────────────────────
    $tenant = $pdo->prepare("SELECT id, full_name, status FROM users WHERE id=:id AND role='tenant' LIMIT 1");
    $tenant->execute([':id' => $tenantId]);
    $tenantRow = $tenant->fetch(PDO::FETCH_ASSOC);
    if (!$tenantRow) {
        $pdo->rollBack();
        header('Location: ' . $back . '?error=' . urlencode('Tenant not found.'));
        exit;
    }
    if (!in_array($tenantRow['status'], ['active', 'prospective'], true)) {
        $pdo->rollBack();
        header('Location: ' . $back . '?error=' . urlencode('Only an approved tenant can be assigned a unit.'));
        exit;
    }

    // ── Verify tenant has no active assignment already ────────
    $existing = $pdo->prepare("SELECT id FROM tenant_units WHERE tenant_id=:tid AND status='active' LIMIT 1");
    $existing->execute([':tid' => $tenantId]);
    if ($existing->fetchColumn()) {
        $pdo->rollBack();
        header('Location: ' . $back . '?error=' . urlencode(
            $tenantRow['full_name'] . ' already has an active unit assignment. Process move-out first.'
        ));
        exit;
    }

    // ── Verify unit is vacant ─────────────────────────────────
    $unit = $pdo->prepare("SELECT id, unit_number, monthly_rent, apartment_id, status FROM units WHERE id=:uid LIMIT 1 FOR UPDATE");
    $unit->execute([':uid' => $unitId]);
    $unitRow = $unit->fetch(PDO::FETCH_ASSOC);
    if (!$unitRow || $unitRow['status'] !== 'Vacant') {
        $pdo->rollBack();
        header('Location: ' . $back . '?error=' . urlencode('That unit is not vacant.'));
        exit;
    }

    // ── Caretaker scope check ─────────────────────────────────
    if (hasRole('caretaker')) {
        $caretakerApartment = getCaretakerApartment((int)$_SESSION['user_id']);
        if (!$caretakerApartment || (int)$unitRow['apartment_id'] !== (int)$caretakerApartment['id']) {
            $pdo->rollBack();
            header('Location: ' . $back . '?error=' . urlencode('You can only assign units in your apartment.'));
            exit;
        }
    }

    // ── Verify unit has no other active tenant ────────────────
    $occupied = $pdo->prepare("SELECT id FROM tenant_units WHERE unit_id=:uid AND status='active' LIMIT 1 FOR UPDATE");
    $occupied->execute([':uid' => $unitId]);
    if ($occupied->fetchColumn()) {
        $pdo->rollBack();
        header('Location: ' . $back . '?error=' . urlencode('Unit ' . $unitRow['unit_number'] . ' is already occupied by another tenant.'));
        exit;
    }

    // ── Create tenant_units record ────────────────────────────
    // Build INSERT defensively in case columns were added by migration
    $hasMoveIn  = $pdo->query("SHOW COLUMNS FROM tenant_units LIKE 'move_in_date'")->fetchColumn();
    $hasRent    = $pdo->query("SHOW COLUMNS FROM tenant_units LIKE 'monthly_rent'")->fetchColumn();
    $hasNotes   = $pdo->query("SHOW COLUMNS FROM tenant_units LIKE 'notes'")->fetchColumn();

    $tuCols   = 'tenant_id, unit_id, status, created_at';
    $tuVals   = ':tid, :uid, \'active\', NOW()';
    $tuParams = [':tid' => $tenantId, ':uid' => $unitId];

    if ($hasMoveIn) {
        $tuCols .= ', move_in_date'; $tuVals .= ', :mid';
        $tuParams[':mid'] = $moveInDate;
    } else {
        $tuCols .= ', lease_start'; $tuVals .= ', :mid';
        $tuParams[':mid'] = $moveInDate;
    }
    if ($hasRent) {
        $tuCols .= ', monthly_rent'; $tuVals .= ', :rent';
        $tuParams[':rent'] = $unitRow['monthly_rent'];
    }
    $hasDeposit = $pdo->query("SHOW COLUMNS FROM tenant_units LIKE 'deposit_amount'")->fetchColumn();
    if ($hasDeposit) {
        if ($depositAmount <= 0) {
            $pdo->rollBack();
            header('Location: ' . $back . '?error=' . urlencode('Agreed deposit amount is required.'));
            exit;
        }
        $tuCols .= ', deposit_amount'; $tuVals .= ', :deposit';
        $tuParams[':deposit'] = $depositAmount;
    }
    if ($hasNotes && $notes !== '') {
        $tuCols .= ', notes'; $tuVals .= ', :notes';
        $tuParams[':notes'] = $notes;
    }

    $pdo->prepare("INSERT INTO tenant_units ({$tuCols}) VALUES ({$tuVals})")
        ->execute($tuParams);

    // ── Mark unit as Occupied ─────────────────────────────────
    $pdo->prepare("UPDATE units SET status='Occupied', updated_at=NOW() WHERE id=:uid")
        ->execute([':uid' => $unitId]);
    $pdo->prepare("UPDATE users SET preferred_apartment_id=:apt_id, updated_at=NOW() WHERE id=:tid")
        ->execute([':apt_id' => $unitRow['apartment_id'], ':tid' => $tenantId]);

    // ── Notifications ─────────────────────────────────────────
    // Notify tenant
    createNotification(
        $tenantId,
        'move_in',
        'Unit assigned — deposit required',
        'You have been assigned to Unit ' . $unitRow['unit_number'] . '. Submit your deposit payment amount for administrator verification.',
        '../tenant/pay_now.php'
    );

    // Notify admin if caretaker did this
    if (hasRole('caretaker')) {
        $adminId = (int)$pdo->query("SELECT id FROM users WHERE role='admin' LIMIT 1")->fetchColumn();
        if ($adminId) {
            createNotification(
                $adminId,
                'move_in',
                'Tenant moved in — Unit ' . $unitRow['unit_number'],
                $tenantRow['full_name'] . ' moved into Unit ' . $unitRow['unit_number'] . ' on ' . formatDate($moveInDate) . '.',
                '../admin/tenants.php?view=' . $tenantId
            );
        }
    }

    // ── Activity log ──────────────────────────────────────────
    logActivity(
        $_SESSION['user_id'],
        'move_in',
        'tenant_units',
        $tenantId,
        $tenantRow['full_name'] . ' → Unit ' . $unitRow['unit_number'] . ' on ' . $moveInDate
    );

    $pdo->commit();
    header('Location: ' . $back . '?success=' . urlencode(
        $tenantRow['full_name'] . ' has been moved into Unit ' . $unitRow['unit_number'] . ' on ' . formatDate($moveInDate) . '.'
    ));
    exit;

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('move_in error: ' . $e->getMessage());
    header('Location: ' . $back . '?error=' . urlencode('Move-in failed. Please try again.'));
    exit;
}
