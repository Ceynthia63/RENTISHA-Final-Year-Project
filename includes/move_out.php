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

$back        = hasRole('admin') ? '../admin/tenants.php' : '../caretaker/tenants.php';
$tenantId    = (int)($_POST['tenant_id']    ?? 0);
$moveOutDate = trim($_POST['move_out_date'] ?? date('Y-m-d'));
$reason      = trim($_POST['reason']        ?? '');
$notes       = trim($_POST['notes']         ?? '');

if (!$tenantId) {
    header('Location: ' . $back . '?error=' . urlencode('Invalid tenant.'));
    exit;
}

try {
    $pdo = getDB();

    // ── Fetch tenant and active assignment ────────────────────
    $tenant = $pdo->prepare(
        "SELECT u.id, u.full_name, u.status,
                tu.id AS assignment_id, tu.unit_id, tu.move_in_date,
                un.unit_number, un.apartment_id
         FROM users u
         JOIN tenant_units tu ON tu.tenant_id = u.id AND tu.status = 'active'
         JOIN units un        ON un.id = tu.unit_id
         WHERE u.id = :id AND u.role = 'tenant'
         LIMIT 1"
    );
    $tenant->execute([':id' => $tenantId]);
    $row = $tenant->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        header('Location: ' . $back . '?error=' . urlencode('Tenant has no active unit assignment.'));
        exit;
    }

    // ── Caretaker scope check ─────────────────────────────────
    if (hasRole('caretaker') && !empty($_SESSION['apartment_id'])) {
        if ((int)$row['apartment_id'] !== (int)$_SESSION['apartment_id']) {
            header('Location: ' . $back . '?error=' . urlencode('You can only process move-outs in your apartment.'));
            exit;
        }
    }

    // ── Build notes string ────────────────────────────────────
    $fullNotes = trim(($reason ? "Reason: {$reason}. " : '') . $notes);

    // ── 1. Update tenant_units: record move-out date ──────────
    // Status 'ended' is the correct ENUM value for a completed tenancy.
    // The user-facing "moved_out" state lives on users.status, not here.
    $pdo->prepare(
        "UPDATE tenant_units
         SET status='ended', move_out_date=:mod, notes=:notes, updated_at=NOW()
         WHERE id=:aid"
    )->execute([':mod' => $moveOutDate, ':notes' => $fullNotes, ':aid' => $row['assignment_id']]);

    // ── 2. Mark unit as Vacant ────────────────────────────────
    $pdo->prepare("UPDATE units SET status='Vacant', updated_at=NOW() WHERE id=:uid")
        ->execute([':uid' => $row['unit_id']]);

    // ── 3. Change tenant account status to moved_out ─────────
    // 'moved_out' is valid on users.status ENUM (added in schema v2).
    $pdo->prepare("UPDATE users SET status='moved_out', updated_at=NOW() WHERE id=:id")
        ->execute([':id' => $tenantId]);

    // ── 4. Notifications ──────────────────────────────────────
    // Notify admin if caretaker did this
    if (hasRole('caretaker')) {
        $adminId = (int)$pdo->query("SELECT id FROM users WHERE role='admin' LIMIT 1")->fetchColumn();
        if ($adminId) {
            createNotification(
                $adminId,
                'move_out',
                'Tenant moved out — Unit ' . $row['unit_number'],
                $row['full_name'] . ' moved out of Unit ' . $row['unit_number'] . ' on ' . formatDate($moveOutDate) . '.',
                '../admin/tenants.php?view=' . $tenantId
            );
        }
    }

    // ── 5. Activity log ───────────────────────────────────────
    logActivity(
        $_SESSION['user_id'],
        'move_out',
        'tenant_units',
        $tenantId,
        $row['full_name'] . ' left Unit ' . $row['unit_number'] . ' on ' . $moveOutDate
    );

    header('Location: ' . $back . '?success=' . urlencode(
        $row['full_name'] . ' has been moved out of Unit ' . $row['unit_number'] .
        ' on ' . formatDate($moveOutDate) . '. Unit is now Vacant.'
    ));
    exit;

} catch (Exception $e) {
    error_log('move_out error: ' . $e->getMessage());
    header('Location: ' . $back . '?error=' . urlencode('Move-out failed. Please try again.'));
    exit;
}
