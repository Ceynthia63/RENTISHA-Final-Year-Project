<?php
/**
 * Rentisha RMS — Tenant Application: Approve / Reject
 * Used by caretaker/tenants.php for pending tenant applications.
 *
 * ACTIONS:
 * - approve → activate a pending_approval tenant account
 * - reject  → permanently delete a pending_approval application
 */
session_start();
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/db_helpers.php';

if (!hasRole('admin') && !hasRole('caretaker')) {
    header('Location: ../index.php'); exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $back = hasRole('admin') ? '../admin/tenants.php' : '../caretaker/tenants.php';
    header('Location: ' . $back); exit;
}
requireCsrfToken();

$action = trim($_POST['action'] ?? '');
$back   = hasRole('admin') ? '../admin/tenants.php' : '../caretaker/tenants.php';

// ═══════════════════════════════════════════════════════════
//  APPROVE — approve a pending tenant application for unit assignment
// ═══════════════════════════════════════════════════════════
if ($action === 'approve') {
    $tenantId = (int)($_POST['tenant_id'] ?? 0);
    if (!$tenantId) {
        header('Location: ' . $back . '?error=' . urlencode('Invalid tenant.')); exit;
    }

    try {
        $pdo = getDB();

        $tenantQuery = $pdo->prepare(
            "SELECT u.full_name, u.preferred_apartment_id
             FROM users u
             WHERE u.id = :id AND u.role = 'tenant' AND u.status = 'pending_approval'
             LIMIT 1"
        );
        $tenantQuery->execute([':id' => $tenantId]);
        $tenant = $tenantQuery->fetch(PDO::FETCH_ASSOC);

        if (!$tenant) {
            header('Location: ' . $back . '?error=' . urlencode('Pending tenant not found.')); exit;
        }

        // Caretakers can only approve tenants for their apartment
        if (hasRole('caretaker')) {
            $caretakerApartment = getCaretakerApartment((int)$_SESSION['user_id']);
            $caretakerApartmentId = $caretakerApartment ? (int)$caretakerApartment['id'] : 0;
            if (!$caretakerApartmentId || (int)$tenant['preferred_apartment_id'] !== $caretakerApartmentId) {
                header('Location: ' . $back . '?error=' . urlencode('You can only approve tenants for your apartment.')); exit;
            }
        }

        $pdo->prepare(
            "UPDATE users SET status='prospective', updated_at=NOW() WHERE id=:id AND role='tenant'"
        )->execute([':id' => $tenantId]);

        createNotification(
            $tenantId,
            'application_approved',
            'Your tenant application has been approved!',
            'Your application has been approved. A unit will be assigned to you, after which you can submit your deposit payment proof.',
            '../tenant/dashboard.php'
        );

        logActivity($_SESSION['user_id'], 'approve_tenant', 'users', $tenantId, $tenant['full_name']);
        header('Location: ' . $back . '?tab=unassigned&success=' . urlencode("\"{$tenant['full_name']}\" approved for unit assignment."));
        exit;

    } catch (Exception $e) {
        error_log('approve_tenant error: ' . $e->getMessage());
        header('Location: ' . $back . '?error=' . urlencode('Approval failed. Please try again.')); exit;
    }
}

// ═══════════════════════════════════════════════════════════
//  REJECT — delete a pending tenant application
// ═══════════════════════════════════════════════════════════
if ($action === 'reject') {
    $tenantId = (int)($_POST['tenant_id'] ?? 0);
    $reason   = trim($_POST['notes'] ?? '');

    if (!$tenantId) {
        header('Location: ' . $back . '?error=' . urlencode('Invalid tenant.')); exit;
    }

    try {
        $pdo = getDB();

        $tenantQuery = $pdo->prepare(
            "SELECT full_name, preferred_apartment_id FROM users
             WHERE id = :id AND status = 'pending_approval' AND role = 'tenant'
             LIMIT 1"
        );
        $tenantQuery->execute([':id' => $tenantId]);
        $tenant = $tenantQuery->fetch(PDO::FETCH_ASSOC);

        if (!$tenant) {
            header('Location: ' . $back . '?error=' . urlencode('Pending tenant not found.')); exit;
        }

        if (hasRole('caretaker')) {
            $caretakerApartment = getCaretakerApartment((int)$_SESSION['user_id']);
            $caretakerApartmentId = $caretakerApartment ? (int)$caretakerApartment['id'] : 0;
            if (!$caretakerApartmentId || (int)$tenant['preferred_apartment_id'] !== $caretakerApartmentId) {
                header('Location: ' . $back . '?error=' . urlencode('You can only reject tenants for your apartment.')); exit;
            }
        }

        $pdo->prepare(
            "DELETE FROM users WHERE id=:id AND role='tenant' AND status='pending_approval'"
        )->execute([':id' => $tenantId]);

        logActivity($_SESSION['user_id'], 'reject_tenant', 'users', $tenantId,
            $tenant['full_name'] . ($reason ? ' — ' . $reason : ''));

        header('Location: ' . $back . '?success=' . urlencode("Application for \"{$tenant['full_name']}\" rejected and removed."));
        exit;

    } catch (Exception $e) {
        error_log('reject_tenant error: ' . $e->getMessage());
        header('Location: ' . $back . '?error=' . urlencode('Rejection failed. Please try again.')); exit;
    }
}

// Unknown action
header('Location: ' . $back); exit;
