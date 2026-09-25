<?php
/**
 * RMS — Save Maintenance Request
 * POST handler — tenant submits or updates a maintenance request.
 */
session_start();
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/db_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php'); exit;
}
requireCsrfToken();

// SECURITY: tenants must always use their own ID — never trust POST input for this.
// Admins/caretakers may act on behalf of a specific tenant (e.g. logging on their behalf).
if (hasRole('tenant')) {
    $tenant_id = (int)$_SESSION['user_id'];
} else {
    $tenant_id = (int)($_POST['tenant_id'] ?? $_SESSION['user_id'] ?? 0);
}
$unit_id     = (int)($_POST['unit_id']     ?? $_SESSION['unit_id'] ?? 0);
$category    = trim($_POST['category']     ?? '');
$priority    = trim($_POST['priority']     ?? 'Normal');
$title       = trim($_POST['title']        ?? '');
$description = trim($_POST['description']  ?? '');
$pref_time   = trim($_POST['preferred_time'] ?? '');
$edit_id     = (int)($_POST['edit_id']     ?? 0) ?: null;

$validCategories = ['Plumbing','Electrical','Structural / Building','Appliances',
                    'Security / Lock','Pest Control','Cleaning / Sanitation','Other'];
$validPriorities = ['Normal','Urgent','Emergency'];

// Validation only applies to new requests — updates only need edit_id + new_status
if (!$edit_id) {
    if (empty($title) || empty($description) || empty($category)) {
        $back = hasRole('tenant') ? '../tenant/maintenance.php'
            : (hasRole('admin') ? '../admin/maintenance.php' : '../caretaker/maintenance.php');
        header('Location: ' . $back . '?error=' . urlencode('Title, category, and description are required.'));
        exit;
    }
}

if (!in_array($category, $validCategories)) { $category = 'Other'; }
if (!in_array($priority, $validPriorities)) { $priority = 'Normal'; }

// Handle photo uploads (up to 3)
$photoPaths = [];
if (!empty($_FILES['photos']['tmp_name'])) {
    $uploadDir = __DIR__ . '/../assets/images/maintenance/';
    if (!is_dir($uploadDir)) { mkdir($uploadDir, 0755, true); }

    foreach ($_FILES['photos']['tmp_name'] as $i => $tmpName) {
        if (empty($tmpName) || count($photoPaths) >= 3) break;
        $size = $_FILES['photos']['size'][$i];
        $name = $_FILES['photos']['name'][$i];
        $ext  = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg','jpeg','png','webp']) || $size > 5 * 1024 * 1024) continue;

        // Verify file is genuinely an image by inspecting its binary content,
        // not just the client-supplied extension or MIME type.
        $imageInfo = @getimagesize($tmpName);
        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!$imageInfo || !in_array($imageInfo['mime'], $allowedMimes)) continue;

        $fname = 'maint_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        if (move_uploaded_file($tmpName, $uploadDir . $fname)) {
            $photoPaths[] = 'assets/images/maintenance/' . $fname;
        }
    }
}

try {
    $pdo = getDB();

    if ($edit_id && (hasRole('caretaker') || hasRole('admin'))) {
        // Caretaker/admin updating status and/or notes
        $newStatus = trim($_POST['new_status'] ?? '');
        $notes     = trim($_POST['notes']      ?? '');

        $validStatuses = ['Pending','In Progress','Resolved','Cancelled'];
        if (!in_array($newStatus, $validStatuses)) {
            $back = hasRole('tenant') ? '../tenant/maintenance.php'
                : (hasRole('admin') ? '../admin/maintenance.php' : '../caretaker/maintenance.php');
            header('Location: ' . $back . '?error=' . urlencode('Invalid status.'));
            exit;
        }

        // SECURITY: Verify the request belongs to this caretaker's apartment.
        // Admins may update any request.
        if (hasRole('caretaker') && !empty($_SESSION['apartment_id'])) {
            $scopeCheck = $pdo->prepare(
                'SELECT mr.id FROM maintenance_requests mr
                 JOIN tenant_units tu ON tu.tenant_id = mr.tenant_id AND tu.status = "active"
                 JOIN units u ON u.id = tu.unit_id
                 WHERE mr.id = :id AND u.apartment_id = :apt
                 LIMIT 1'
            );
            $scopeCheck->execute([':id' => $edit_id, ':apt' => (int)$_SESSION['apartment_id']]);
            if (!$scopeCheck->fetchColumn()) {
                header('Location: ../caretaker/maintenance.php?error=' . urlencode(
                    'You can only update maintenance requests for your apartment.'
                ));
                exit;
            }
        }

        $setClauses = ['status=:status', 'updated_at=NOW()'];
        $params     = [':status' => $newStatus, ':id' => $edit_id];

        if ($notes !== '') {
            $setClauses[] = 'caretaker_notes=:notes';
            $params[':notes'] = $notes;
        }
        if ($newStatus === 'Resolved') {
            $setClauses[] = 'resolved_at=NOW()';
        }

        $pdo->prepare(
            'UPDATE maintenance_requests SET ' . implode(', ', $setClauses) . ' WHERE id=:id'
        )->execute($params);

        // Log the update — silently skip if maintenance_logs table doesn't exist
        try {
            $pdo->prepare(
                'INSERT INTO maintenance_logs (request_id, updated_by, status, notes, created_at)
                 VALUES (:rid, :uid, :status, :notes, NOW())'
            )->execute([
                ':rid'    => $edit_id,
                ':uid'    => $_SESSION['user_id'],
                ':status' => $newStatus,
                ':notes'  => $notes,
            ]);
        } catch (Exception $logEx) {
            // maintenance_logs table missing — not fatal
            error_log('maintenance_logs insert skipped: ' . $logEx->getMessage());
        }

        // Notify tenant if resolved
        if ($newStatus === 'Resolved') {
            try {
                $tenantRow = $pdo->prepare(
                    'SELECT tenant_id FROM maintenance_requests WHERE id=:id LIMIT 1'
                );
                $tenantRow->execute([':id' => $edit_id]);
                $tid = $tenantRow->fetchColumn();
                if ($tid) {
                    createNotification(
                        (int)$tid,
                        'maintenance_resolved',
                        'Maintenance request resolved',
                        'Your maintenance request has been resolved.' . ($notes ? ' Note: ' . $notes : ''),
                        'tenant/maintenance.php'
                    );
                }
            } catch (Exception $notifEx) {
                error_log('Maintenance resolve notification error: ' . $notifEx->getMessage());
            }
        }

    } else {
        // New request from tenant
        $photoJson = json_encode($photoPaths);
        $pdo->prepare(
            'INSERT INTO maintenance_requests
                (tenant_id, unit_id, category, priority, title, description,
                 preferred_time, photos, status, created_at)
             VALUES
                (:tid, :uid, :cat, :pri, :title, :desc,
                 :pref, :photos, "Pending", NOW())'
        )->execute([
            ':tid'    => $tenant_id,
            ':uid'    => $unit_id ?: null,
            ':cat'    => $category,
            ':pri'    => $priority,
            ':title'  => $title,
            ':desc'   => $description,
            ':pref'   => $pref_time,
            ':photos' => $photoJson,
        ]);
    }
} catch (Exception $e) {
    error_log('Save maintenance error: ' . $e->getMessage());
    $back = hasRole('tenant') ? '../tenant/maintenance.php'
        : (hasRole('admin') ? '../admin/maintenance.php' : '../caretaker/maintenance.php');
    header('Location: ' . $back . '?error=' . urlencode('Failed to save request.'));
    exit;
}

$back = hasRole('tenant') ? '../tenant/maintenance.php'
    : (hasRole('admin') ? '../admin/maintenance.php' : '../caretaker/maintenance.php');
header('Location: ' . $back . '?success=' . urlencode(
    $edit_id ? 'Request updated successfully.' : 'Maintenance request submitted successfully.'
));
exit;
