<?php
/**
 * Rentisha RMS — Save / Update Tenant
 * Admin and Caretaker can create tenants.
 * Caretaker is restricted to their assigned apartment's vacant units.
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

$back = hasRole('admin') ? '../admin/tenants.php' : '../caretaker/tenants.php';

// ── Input ─────────────────────────────────────────────────────
$edit_id           = (int)($_POST['edit_id']           ?? 0) ?: null;
$full_name         = trim($_POST['full_name']           ?? '');
$email             = trim($_POST['email']               ?? '');
$phone             = trim($_POST['phone']               ?? '');
$id_number         = trim($_POST['id_number']           ?? '');
$emergency_contact = trim($_POST['emergency_contact']   ?? '');
$unit_id           = (int)($_POST['unit_id']            ?? 0) ?: null;
$move_in_date      = trim($_POST['move_in_date']        ?? '') ?: date('Y-m-d');
$password          = $_POST['password']                 ?? '';
$confirm_password  = $_POST['confirm_password']         ?? '';
$status            = trim($_POST['status']              ?? 'active');
$normalizedPhone   = rmsNormalizePhone($phone);

$validStatuses = ['active','prospective','inactive','suspended','moved_out','terminated'];
if (!in_array($status, $validStatuses)) { $status = 'active'; }

// ── Validate ──────────────────────────────────────────────────
$parsedMoveInDate = DateTime::createFromFormat('!Y-m-d', $move_in_date);
if (!$parsedMoveInDate || $parsedMoveInDate->format('Y-m-d') !== $move_in_date) {
    header('Location: ' . $back . '?error=' . urlencode('Enter a valid move-in date.'));
    exit;
}
if (empty($full_name) || !$normalizedPhone) {
    $message = !$normalizedPhone ? 'Enter valid phone number.' : 'Full name is required.';
    header('Location: ' . $back . '?error=' . urlencode($message));
    exit;
}
$email = strtolower(trim($email));
if ($email && !rmsValidEmail($email)) {
    header('Location: ' . $back . '?error=' . urlencode('Invalid email address.'));
    exit;
}
if (!$edit_id) {
    if (strlen($password) < rmsPasswordMinLength()) {
        header('Location: ' . $back . '?error=' . urlencode('Password must meet the minimum length requirement.'));
        exit;
    }
    if ($password !== $confirm_password) {
        header('Location: ' . $back . '?error=' . urlencode('Passwords do not match.'));
        exit;
    }
}

// ── Avatar upload ─────────────────────────────────────────────
$avatarPath = null;
if (!empty($_FILES['avatar']['tmp_name'])) {
    $ext     = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png','webp'];
    if (!in_array($ext, $allowed) || $_FILES['avatar']['size'] > 5 * 1024 * 1024) {
        header('Location: ' . $back . '?error=' . urlencode('Invalid avatar. Use JPG/PNG/WebP under 5 MB.'));
        exit;
    }
    $dir   = __DIR__ . '/../assets/images/avatars/';
    if (!is_dir($dir)) { mkdir($dir, 0755, true); }
    $fname = 'tenant_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    if (move_uploaded_file($_FILES['avatar']['tmp_name'], $dir . $fname)) {
        $avatarPath = 'assets/images/avatars/' . $fname;
    }
}

try {
    $pdo = getDB();

    if ($edit_id) {
        // ── UPDATE existing tenant ────────────────────────────
        if ($email) {
            $chk = $pdo->prepare(
                "SELECT id FROM users
                 WHERE LOWER(email)=LOWER(:email) AND id<>:id
                 LIMIT 1"
            );
            $chk->execute([':email' => $email, ':id' => $edit_id]);
            if ($chk->fetchColumn()) {
                header('Location: ' . $back . '?error=' . urlencode("Email \"$email\" is already registered."));
                exit;
            }
        }
        $sql    = "UPDATE users SET full_name=:name, phone=:phone, status=:status,
                   id_number=:idn, emergency_contact=:ec, updated_at=NOW()";
        $params = [':name'=>$full_name, ':phone'=>$normalizedPhone, ':status'=>$status,
                   ':idn'=>$id_number, ':ec'=>$emergency_contact, ':id'=>$edit_id];
        if ($email) { $sql .= ', email=:email'; $params[':email'] = $email; }
        if ($avatarPath) { $sql .= ', avatar=:avatar'; $params[':avatar'] = $avatarPath; }
        $sql .= " WHERE id=:id AND role='tenant'";
        $pdo->prepare($sql)->execute($params);

        // Persist a changed move-in date on the tenant's active assignment.
        $moveInColumn = $pdo->query("SHOW COLUMNS FROM tenant_units LIKE 'move_in_date'")->fetchColumn()
            ? 'move_in_date'
            : 'lease_start';
        $pdo->prepare(
            "UPDATE tenant_units
             SET {$moveInColumn}=:move_in_date
             WHERE tenant_id=:tenant_id AND status='active'"
        )->execute([
            ':move_in_date' => $move_in_date,
            ':tenant_id'    => $edit_id,
        ]);

        logActivity($_SESSION['user_id'], 'update_tenant', 'users', $edit_id, $full_name);
        header('Location: ' . $back . '?success=' . urlencode("Tenant \"{$full_name}\" updated."));
        exit;
    }

    // ── INSERT new tenant ─────────────────────────────────────
    // Check email uniqueness if provided
    if ($email) {
        $chk = $pdo->prepare('SELECT id FROM users WHERE LOWER(email)=LOWER(:e) LIMIT 1');
        $chk->execute([':e' => $email]);
        if ($chk->fetchColumn()) {
            header('Location: ' . $back . '?error=' . urlencode("Email \"$email\" is already registered."));
            exit;
        }
    }

    // Check unit is actually vacant before assigning
    if ($unit_id) {
        $unitCheck = $pdo->prepare("SELECT status FROM units WHERE id=:uid LIMIT 1");
        $unitCheck->execute([':uid' => $unit_id]);
        $unitRow = $unitCheck->fetch(PDO::FETCH_ASSOC);
        if (!$unitRow || $unitRow['status'] !== 'Vacant') {
            header('Location: ' . $back . '?error=' . urlencode('That unit is not vacant. Please select a different unit.'));
            exit;
        }
        // Caretaker scope check — only allow units in their apartment
        if (hasRole('caretaker') && !empty($_SESSION['apartment_id'])) {
            $scopeChk = $pdo->prepare("SELECT apartment_id FROM units WHERE id=:uid");
            $scopeChk->execute([':uid' => $unit_id]);
            $apt = $scopeChk->fetchColumn();
            if ((int)$apt !== (int)$_SESSION['apartment_id']) {
                header('Location: ' . $back . '?error=' . urlencode('You can only assign units in your apartment.'));
                exit;
            }
        }
    }

    // Create user account — password set by admin at account creation
    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    $pdo->prepare(
        "INSERT INTO users
           (full_name, email, phone, id_number, password_hash, role,
            status, avatar, emergency_contact, must_change_password, created_at)
         VALUES (:name, :email, :phone, :idn, :hash, 'tenant',
                 :status, :avatar, :ec, 1, NOW())"
    )->execute([
        ':name'   => $full_name,
        ':email'  => $email ?: null,
        ':phone'  => $normalizedPhone,
        ':idn'    => $id_number,
        ':hash'   => $hash,
        ':status' => $status,
        ':avatar' => $avatarPath,
        ':ec'     => $emergency_contact,
    ]);
    $tenantId = (int)$pdo->lastInsertId();

    // Assign unit and record move-in date
    if ($unit_id && $tenantId && $status === 'active') {
        // Get unit rent
        $rentRow = $pdo->prepare("SELECT monthly_rent, apartment_id FROM units WHERE id=:uid");
        $rentRow->execute([':uid' => $unit_id]);
        $rentData = $rentRow->fetch(PDO::FETCH_ASSOC);

        // Insert tenant_units — include monthly_rent only if the column exists (defensive)
        $hasMoveIn   = $pdo->query("SHOW COLUMNS FROM tenant_units LIKE 'move_in_date'")->fetchColumn();
        $hasRentCol  = $pdo->query("SHOW COLUMNS FROM tenant_units LIKE 'monthly_rent'")->fetchColumn();

        $tuCols   = 'tenant_id, unit_id, status, created_at';
        $tuVals   = ':tid, :uid, \'active\', NOW()';
        $tuParams = [':tid' => $tenantId, ':uid' => $unit_id];

        if ($hasMoveIn) {
            $tuCols  .= ', move_in_date';
            $tuVals  .= ', :mid';
            $tuParams[':mid'] = $move_in_date;
        } else {
            // Older schema uses lease_start instead
            $tuCols  .= ', lease_start';
            $tuVals  .= ', :mid';
            $tuParams[':mid'] = $move_in_date;
        }
        if ($hasRentCol) {
            $tuCols  .= ', monthly_rent';
            $tuVals  .= ', :rent';
            $tuParams[':rent'] = $rentData['monthly_rent'] ?? null;
        }

        $pdo->prepare("INSERT INTO tenant_units ({$tuCols}) VALUES ({$tuVals})")
            ->execute($tuParams);

        // Mark unit as Occupied
        $pdo->prepare("UPDATE units SET status='Occupied', updated_at=NOW() WHERE id=:uid")
            ->execute([':uid' => $unit_id]);

        // Notify admin (fetch unit number properly)
        if (hasRole('caretaker')) {
            $adminId = $pdo->query("SELECT id FROM users WHERE role='admin' LIMIT 1")->fetchColumn();
            if ($adminId) {
                // Fetch the unit number for the notification body
                $unStmt = $pdo->prepare("SELECT unit_number FROM units WHERE id=:uid LIMIT 1");
                $unStmt->execute([':uid' => $unit_id]);
                $unitNumber = $unStmt->fetchColumn() ?: 'N/A';

                createNotification(
                    (int)$adminId,
                    'move_in',
                    "New tenant moved in",
                    "{$full_name} moved into Unit {$unitNumber}",
                    '../admin/tenants.php?view=' . $tenantId
                );
            }
        }
    }

    logActivity($_SESSION['user_id'], 'create_tenant', 'users', $tenantId, $full_name);
    header('Location: ' . $back . '?success=' . urlencode("Tenant \"{$full_name}\" added successfully."));
    exit;

} catch (PDOException $e) {
    error_log('save_tenant error: ' . $e->getMessage());
    $msg = str_contains($e->getMessage(), '1062')
        ? 'A tenant with this email or phone already exists.'
        : 'Failed to save tenant. Please try again.';
    header('Location: ' . $back . '?error=' . urlencode($msg));
    exit;
}
