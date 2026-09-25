<?php
/**
 * Rentisha RMS — Save / Update Unit
 */
session_start();
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/db_helpers.php';
requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../admin/units.php'); exit;
}
requireCsrfToken();

$edit_id      = (int)($_POST['edit_id']      ?? 0) ?: null;

// Block creation of new units - units can only be created via apartments
if (!$edit_id) {
    header('Location: ../admin/units.php?error=' . urlencode('New units can only be created when setting up apartments. Edit existing units here.'));
    exit;
}

$apartment_id = (int)($_POST['apartment_id'] ?? 0);
$unit_number  = trim($_POST['unit_number']   ?? '');
$unit_type    = trim($_POST['unit_type']      ?? '1 Bedroom');
$floor        = (int)($_POST['floor']        ?? 0);
$bathrooms    = (int)($_POST['bathrooms']    ?? 1);
$monthly_rent = filter_var($_POST['monthly_rent'] ?? null, FILTER_VALIDATE_FLOAT);
$deposit      = (float)($_POST['deposit']    ?? 0);
$status       = trim($_POST['status']        ?? 'Vacant');
$notes        = trim($_POST['notes']         ?? '');
$features     = json_encode((array)($_POST['features'] ?? []));

$validTypes = [
    'Bedsitter','Studio','Self-Contained',
    '1 Bedroom','2 Bedroom','3 Bedroom','4 Bedroom',
    'Penthouse','Maisonette','Shop','Office','Other'
];
$validStatuses = ['Vacant','Occupied','Under Maintenance'];

if (!$apartment_id || empty($unit_number) || $monthly_rent === false || $monthly_rent <= 0) {
    header('Location: ../admin/units.php?error=' . urlencode('Apartment, unit number and rent are required.'));
    exit;
}
if (!in_array($unit_type,  $validTypes))    { $unit_type = '1 Bedroom'; }
if (!in_array($status,     $validStatuses)) { $status    = 'Vacant'; }

try {
    $pdo = getDB();

    if ($edit_id) {
        // Check unit isn't being set Vacant while still has an active tenant
        if ($status === 'Vacant') {
            $hasTenant = $pdo->prepare(
                "SELECT id FROM tenant_units WHERE unit_id=:uid AND status='active' LIMIT 1"
            );
            $hasTenant->execute([':uid' => $edit_id]);
            if ($hasTenant->fetchColumn()) {
                header('Location: ../admin/units.php?error=' . urlencode(
                    'Cannot set unit to Vacant while a tenant is assigned. Process move-out first.'
                ));
                exit;
            }
        }

        // Get old apartment_id before updating (in case it changes)
        $oldAptStmt = $pdo->prepare("SELECT apartment_id FROM units WHERE id = :id");
        $oldAptStmt->execute([':id' => $edit_id]);
        $oldAptId = (int)$oldAptStmt->fetchColumn();

        $pdo->prepare(
            "UPDATE units SET
               apartment_id=:apt, unit_number=:num, unit_type=:type,
               floor=:floor, bathrooms=:baths, monthly_rent=:rent,
               deposit=:dep, status=:status, features=:feat, notes=:notes,
               updated_at=NOW()
             WHERE id=:id"
        )->execute([
            ':apt'   => $apartment_id, ':num'  => $unit_number,
            ':type'  => $unit_type,    ':floor' => $floor,
            ':baths' => $bathrooms,    ':rent'  => $monthly_rent,
            ':dep'   => $deposit,      ':status'=> $status,
            ':feat'  => $features,     ':notes' => $notes,
            ':id'    => $edit_id,
        ]);

        // Auto-update apartment's total_units count (for both old and new apartment if changed)
        // Update new apartment
        $pdo->prepare(
            "UPDATE apartments SET total_units = (
                SELECT COUNT(*) FROM units WHERE apartment_id = :apt
            ) WHERE id = :apt"
        )->execute([':apt' => $apartment_id]);
        
        // Update old apartment if different
        if ($oldAptId && $oldAptId !== $apartment_id) {
            $pdo->prepare(
                "UPDATE apartments SET total_units = (
                    SELECT COUNT(*) FROM units WHERE apartment_id = :apt
                ) WHERE id = :apt"
            )->execute([':apt' => $oldAptId]);
        }

        logActivity($_SESSION['user_id'], 'update_unit', 'units', $edit_id, $unit_number);
        $redirect = '../admin/units.php?apt=' . $apartment_id . '&success=' . urlencode("Unit {$unit_number} updated.");
    }

} catch (PDOException $e) {
    error_log('save_unit error: ' . $e->getMessage());
    $msg = str_contains($e->getMessage(),'1062')
        ? "Unit {$unit_number} already exists in this apartment."
        : 'Failed to save unit. Please try again.';
    header('Location: ../admin/units.php?apt=' . $apartment_id . '&error=' . urlencode($msg));
    exit;
}

header('Location: ' . $redirect);
exit;
