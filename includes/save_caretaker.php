<?php
/**
 * Rentisha RMS — Save / Assign / Remove Caretaker
 *
 * action=save   → create or update caretaker account
 * action=assign → assign caretaker to an apartment (enforces 1-per-caretaker rule)
 * action=remove → deactivate caretaker, end assignment, retain history
 */
session_start();
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/db_helpers.php';
requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../admin/caretakers.php'); exit;
}
requireCsrfToken();

$action = trim($_POST['action'] ?? 'save');
$back   = '../admin/caretakers.php';

// ═══════════════════════════════════════════════════════════
//  ACTION: SAVE (create or update caretaker account)
// ═══════════════════════════════════════════════════════════
if ($action === 'save') {

    $edit_id   = (int)($_POST['edit_id']  ?? 0) ?: null;
    $full_name = trim($_POST['full_name'] ?? '');
    $email     = strtolower(trim($_POST['email']     ?? ''));
    $phone     = trim($_POST['phone']     ?? '');
    $normalizedPhone = rmsNormalizePhone($phone);
    $password  = $_POST['password']       ?? '';
    $confirm   = $_POST['confirm_password'] ?? '';
    $status    = in_array($_POST['status'] ?? '', ['active','inactive','suspended'])
                 ? $_POST['status'] : 'active';

    if (empty($full_name) || empty($email) || !$normalizedPhone) {
        $message = !$normalizedPhone ? 'Enter valid phone number.' : 'Name and email are required.';
        header('Location: ' . $back . '?error=' . urlencode($message)); exit;
    }
    if (!rmsValidEmail($email)) {
        header('Location: ' . $back . '?error=' . urlencode('Invalid email address.')); exit;
    }
    if (!$edit_id) {
        if (strlen($password) < rmsPasswordMinLength()) {
            header('Location: ' . $back . '?error=' . urlencode('Password must meet the minimum length requirement.')); exit;
        }
        if ($password !== $confirm) {
            header('Location: ' . $back . '?error=' . urlencode('Passwords do not match.')); exit;
        }
    }

    // Avatar upload
    $avatarPath = null;
    if (!empty($_FILES['avatar']['tmp_name'])) {
        $ext     = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','webp'];
        if (in_array($ext, $allowed) && $_FILES['avatar']['size'] <= 5 * 1024 * 1024) {
            $dir = __DIR__ . '/../assets/images/avatars/';
            if (!is_dir($dir)) { mkdir($dir, 0755, true); }
            $fname = 'ct_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $dir . $fname)) {
                $avatarPath = 'assets/images/avatars/' . $fname;
            }
        }
    }

    try {
        $pdo = getDB();

        // ── UPDATE existing caretaker ─────────────────────────
        if ($edit_id) {
            $chk = $pdo->prepare(
                "SELECT id FROM users
                 WHERE LOWER(email)=LOWER(:email) AND id<>:id
                 LIMIT 1"
            );
            $chk->execute([':email' => $email, ':id' => $edit_id]);
            if ($chk->fetchColumn()) {
                header('Location: ' . $back . '?error=' . urlencode("Email \"$email\" is already registered.")); exit;
            }
            $sql    = "UPDATE users SET full_name=:name, email=:email, phone=:phone,
                       status=:status, updated_at=NOW()";
            $params = [':name'=>$full_name, ':email'=>$email, ':phone'=>$normalizedPhone,
                       ':status'=>$status,  ':id'=>$edit_id];
            if ($avatarPath) { $sql .= ', avatar=:avatar'; $params[':avatar'] = $avatarPath; }
            $sql .= " WHERE id=:id AND role='caretaker'";
            $pdo->prepare($sql)->execute($params);

            logActivity($_SESSION['user_id'], 'update_caretaker', 'users', $edit_id, $full_name);
            header('Location: ' . $back . '?success=' . urlencode("Caretaker \"{$full_name}\" updated.")); exit;
        }

        // ── CREATE new caretaker ──────────────────────────────
        $chk = $pdo->prepare('SELECT id FROM users WHERE LOWER(email)=LOWER(:e) LIMIT 1');
        $chk->execute([':e' => $email]);
        if ($chk->fetchColumn()) {
            header('Location: ' . $back . '?error=' . urlencode("Email \"{$email}\" is already registered.")); exit;
        }

        // Create caretaker account — password set by admin at account creation
        $defaultHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $pdo->prepare(
            "INSERT INTO users
               (full_name, email, phone, password_hash, role, status, avatar,
                must_change_password, created_at)
             VALUES
               (:name, :email, :phone, :hash, 'caretaker', 'active', :avatar, 1, NOW())"
        )->execute([
            ':name'   => $full_name,
            ':email'  => $email,
            ':phone'  => $normalizedPhone,
            ':hash'   => $defaultHash,
            ':avatar' => $avatarPath,
        ]);
        $ctId = (int)$pdo->lastInsertId();

        logActivity($_SESSION['user_id'], 'create_caretaker', 'users', $ctId, $full_name);

        header('Location: ' . $back . '?success=' . urlencode(
            "Caretaker \"{$full_name}\" added successfully."
        ));
        exit;

    } catch (PDOException $e) {
        error_log('save_caretaker error: ' . $e->getMessage());
        $msg = str_contains($e->getMessage(), '1062')
            ? "Email \"{$email}\" is already registered."
            : 'Failed to save. Please try again.';
        header('Location: ' . $back . '?error=' . urlencode($msg)); exit;
    }
}

// ═══════════════════════════════════════════════════════════
//  ACTION: ASSIGN (one apartment per caretaker enforced)
// ═══════════════════════════════════════════════════════════
if ($action === 'assign') {

    $caretakerId  = (int)($_POST['caretaker_id']  ?? 0);
    $apartmentId  = (int)($_POST['apartment_id']  ?? 0);
    $assignedDate = trim($_POST['assigned_date']  ?? date('Y-m-d'));

    if (!$caretakerId || !$apartmentId) {
        header('Location: ' . $back . '?error=' . urlencode('Caretaker and apartment are required.')); exit;
    }
    $parsedDate = DateTime::createFromFormat('!Y-m-d', $assignedDate);
    if (!$parsedDate || $parsedDate->format('Y-m-d') !== $assignedDate) {
        header('Location: ' . $back . '?error=' . urlencode('Enter a valid assignment date.')); exit;
    }

    try {
        $pdo = getDB();

        // Verify caretaker exists and is active
        $ct = $pdo->prepare("SELECT id, full_name FROM users WHERE id=:id AND role='caretaker' LIMIT 1");
        $ct->execute([':id' => $caretakerId]);
        $ctRow = $ct->fetch(PDO::FETCH_ASSOC);
        if (!$ctRow) {
            header('Location: ' . $back . '?error=' . urlencode('Caretaker not found.')); exit;
        }

        // ── ONE-APARTMENT RULE ─────────────────────────────────
        // Check if THIS caretaker already has an active assignment to a DIFFERENT apartment
        $existing = $pdo->prepare(
            "SELECT ca.apartment_id, a.name FROM caretaker_assignments ca
             JOIN apartments a ON a.id = ca.apartment_id
             WHERE ca.caretaker_id=:cid AND ca.status='active' AND ca.apartment_id != :aid
             LIMIT 1"
        );
        $existing->execute([':cid' => $caretakerId, ':aid' => $apartmentId]);
        $conflict = $existing->fetch(PDO::FETCH_ASSOC);
        if ($conflict) {
            // End the existing assignment first
            $pdo->prepare(
                "UPDATE caretaker_assignments SET status='inactive', removed_date=CURDATE()
                 WHERE caretaker_id=:cid AND status='active'"
            )->execute([':cid' => $caretakerId]);

            // Remove from previous apartment
            $pdo->prepare(
                "UPDATE apartments SET caretaker_id=NULL WHERE id=:aid AND caretaker_id=:cid"
            )->execute([':aid' => $conflict['apartment_id'], ':cid' => $caretakerId]);
        }

        // End any other caretaker's active assignment on THIS apartment (replacing them)
        $prevCt = $pdo->prepare(
            "SELECT ca.caretaker_id FROM caretaker_assignments ca
             WHERE ca.apartment_id=:aid AND ca.status='active' AND ca.caretaker_id != :cid
             LIMIT 1"
        );
        $prevCt->execute([':aid' => $apartmentId, ':cid' => $caretakerId]);
        $prevCtId = $prevCt->fetchColumn();
        if ($prevCtId) {
            $pdo->prepare(
                "UPDATE caretaker_assignments SET status='inactive', removed_date=CURDATE()
                 WHERE apartment_id=:aid AND status='active'"
            )->execute([':aid' => $apartmentId]);
        }

        // Create new assignment record
        $pdo->prepare(
            "INSERT INTO caretaker_assignments (caretaker_id, apartment_id, assigned_date, status)
             VALUES (:cid, :aid, :insert_date, 'active')
             ON DUPLICATE KEY UPDATE status='active', removed_date=NULL, assigned_date=:update_date"
        )->execute([
            ':cid'         => $caretakerId,
            ':aid'         => $apartmentId,
            ':insert_date' => $assignedDate,
            ':update_date' => $assignedDate,
        ]);

        // Also update apartments.caretaker_id and caretaker_apartments
        $pdo->prepare("UPDATE apartments SET caretaker_id=:cid WHERE id=:aid")
            ->execute([':cid' => $caretakerId, ':aid' => $apartmentId]);

        $pdo->prepare(
            "INSERT IGNORE INTO caretaker_apartments (caretaker_id, apartment_id) VALUES (:cid, :aid)"
        )->execute([':cid' => $caretakerId, ':aid' => $apartmentId]);

        // Update session if this caretaker is currently logged in
        // (edge case — admin is doing this, but keeping session in sync)

        logActivity(
            $_SESSION['user_id'], 'assign_caretaker', 'caretaker_assignments',
            $caretakerId, $ctRow['full_name'] . ' → apt #' . $apartmentId
        );

        // Notify caretaker
        createNotification(
            $caretakerId,
            'assignment',
            'You have been assigned to an apartment',
            'Your apartment assignment has been updated. Log in to view your dashboard.',
            'caretaker/dashboard.php'
        );

        $aptName = $pdo->prepare("SELECT name FROM apartments WHERE id=:id");
        $aptName->execute([':id' => $apartmentId]);
        $aptN = $aptName->fetchColumn() ?: 'Apartment #' . $apartmentId;

        header('Location: ' . $back . '?success=' . urlencode(
            $ctRow['full_name'] . ' has been assigned to ' . $aptN . '.'
        )); exit;

    } catch (Exception $e) {
        error_log('assign_caretaker error: ' . $e->getMessage());
        header('Location: ' . $back . '?error=' . urlencode('Assignment failed. Please try again.')); exit;
    }
}

// ═══════════════════════════════════════════════════════════
//  ACTION: REMOVE — archive to caretaker_history, then DELETE account
// ═══════════════════════════════════════════════════════════
if ($action === 'remove') {

    $caretakerId = (int)($_POST['caretaker_id'] ?? 0);
    $reason      = trim($_POST['notes'] ?? '');

    if (!$caretakerId) {
        header('Location: ' . $back . '?error=' . urlencode('Invalid caretaker.')); exit;
    }

    try {
        $pdo = getDB();

        // Fetch full caretaker details before deletion
        $ct = $pdo->prepare(
            "SELECT u.full_name, u.email, u.phone, u.id_number,
                    ca.apartment_id, ca.assigned_date,
                    a.name AS apt_name
             FROM users u
             LEFT JOIN caretaker_assignments ca ON ca.caretaker_id = u.id AND ca.status = 'active'
             LEFT JOIN apartments a ON a.id = ca.apartment_id
             WHERE u.id = :id AND u.role = 'caretaker'
             LIMIT 1"
        );
        $ct->execute([':id' => $caretakerId]);
        $ctRow = $ct->fetch(PDO::FETCH_ASSOC);

        if (!$ctRow) {
            header('Location: ' . $back . '?error=' . urlencode('Caretaker not found.')); exit;
        }

        // Fetch the admin's name for the history record
        $adminName = $pdo->prepare("SELECT full_name FROM users WHERE id = :id LIMIT 1");
        $adminName->execute([':id' => $_SESSION['user_id']]);
        $removedByName = $adminName->fetchColumn() ?: 'Admin';

        // ── 1. Archive to caretaker_history (survives account deletion) ──
        $pdo->prepare(
            "INSERT INTO caretaker_history
               (full_name, email, phone, id_number, apartment_name, apartment_id,
                assigned_date, removed_date, removal_reason, removed_by_name,
                original_user_id, created_at)
             VALUES
               (:name, :email, :phone, :idn, :apt_name, :apt_id,
                :assigned, CURDATE(), :reason, :by,
                :orig_id, NOW())"
        )->execute([
            ':name'     => $ctRow['full_name'],
            ':email'    => $ctRow['email'],
            ':phone'    => $ctRow['phone'],
            ':idn'      => $ctRow['id_number'],
            ':apt_name' => $ctRow['apt_name'],
            ':apt_id'   => $ctRow['apartment_id'],
            ':assigned' => $ctRow['assigned_date'],
            ':reason'   => $reason ?: null,
            ':by'       => $removedByName,
            ':orig_id'  => $caretakerId,
        ]);

        // ── 2. End active assignment ──────────────────────────
        $pdo->prepare(
            "UPDATE caretaker_assignments
             SET status = 'inactive', removed_date = CURDATE(), notes = :notes
             WHERE caretaker_id = :cid AND status = 'active'"
        )->execute([':cid' => $caretakerId, ':notes' => $reason]);

        // ── 3. Free the apartment ─────────────────────────────
        if ($ctRow['apartment_id']) {
            $pdo->prepare(
                "UPDATE apartments SET caretaker_id = NULL WHERE id = :aid AND caretaker_id = :cid"
            )->execute([':aid' => $ctRow['apartment_id'], ':cid' => $caretakerId]);
        }

        // ── 4. Log the activity BEFORE deleting the user ─────
        logActivity(
            $_SESSION['user_id'], 'remove_caretaker', 'caretaker_history',
            $caretakerId,
            $ctRow['full_name'] . ($reason ? ' — ' . $reason : '') . ' (account deleted)'
        );

        // ── 5. Delete all linked tokens/sessions for clean removal
        $pdo->prepare("DELETE FROM remember_tokens  WHERE user_id = :id")->execute([':id' => $caretakerId]);
        $pdo->prepare("DELETE FROM password_resets  WHERE user_id = :id")->execute([':id' => $caretakerId]);
        $pdo->prepare("DELETE FROM notifications    WHERE user_id = :id")->execute([':id' => $caretakerId]);

        // ── 6. DELETE the user account permanently ────────────
        $pdo->prepare("DELETE FROM users WHERE id = :id AND role = 'caretaker'")
            ->execute([':id' => $caretakerId]);

        $msg = $ctRow['full_name'] . ' has been removed and their account deleted. '
             . 'Service record saved to history.';
        if ($ctRow['apt_name']) {
            $msg .= ' "' . $ctRow['apt_name'] . '" needs a new caretaker.';
        }
        header('Location: ' . $back . '?success=' . urlencode($msg)); exit;

    } catch (Exception $e) {
        error_log('remove_caretaker error: ' . $e->getMessage());
        header('Location: ' . $back . '?error=' . urlencode('Removal failed. Please try again.')); exit;
    }
}

// ═══════════════════════════════════════════════════════════
//  ACTION: APPROVE — activate a pending caretaker registration
// ═══════════════════════════════════════════════════════════
if ($action === 'approve') {
    $caretakerId = (int)($_POST['caretaker_id'] ?? 0);
    $autoAssign  = isset($_POST['auto_assign']) && $_POST['auto_assign'] === '1';
    $assignedDate = trim($_POST['assigned_date'] ?? date('Y-m-d'));
    $parsedDate = DateTime::createFromFormat('!Y-m-d', $assignedDate);
    if (!$parsedDate || $parsedDate->format('Y-m-d') !== $assignedDate) {
        header('Location: ' . $back . '?error=' . urlencode('Enter a valid assignment date.')); exit;
    }
    
    if (!$caretakerId) {
        header('Location: ' . $back . '?error=' . urlencode('Invalid caretaker.')); exit;
    }
    try {
        $pdo = getDB();
        
        $ctQuery = $pdo->prepare(
            "SELECT full_name, notes FROM users 
             WHERE id = :id AND role = 'caretaker' AND status = 'pending_approval' 
             LIMIT 1"
        );
        $ctQuery->execute([':id' => $caretakerId]);
        $caretaker = $ctQuery->fetch(PDO::FETCH_ASSOC);
        
        if (!$caretaker) {
            header('Location: ' . $back . '?error=' . urlencode('Pending caretaker not found.')); exit;
        }
        
        $fullName = $caretaker['full_name'];
        
        // Activate the account
        $pdo->prepare(
            "UPDATE users SET status = 'active', updated_at = NOW()
             WHERE id = :id AND role = 'caretaker'"
        )->execute([':id' => $caretakerId]);

        // Check if they applied for a specific apartment and auto-assign if requested
        $assignmentMade = false;
        if ($autoAssign && $caretaker['notes']) {
            // Extract apartment name from notes
            if (preg_match('/Applied for:\s*([^\n(]+)\s*\(([^)]+)\)/i', $caretaker['notes'], $match)) {
                $aptName = trim($match[1]);
                $aptLoc  = trim($match[2]);
                
                // Find the apartment by name and location
                $aptQuery = $pdo->prepare(
                    "SELECT id FROM apartments 
                     WHERE name = :name AND (location = :loc OR CONCAT(town, ', ', county) LIKE :loc OR county = :loc)
                     AND status = 'Active'
                     LIMIT 1"
                );
                $aptQuery->execute([':name' => $aptName, ':loc' => $aptLoc]);
                $apartmentId = $aptQuery->fetchColumn();
                
                if ($apartmentId) {
                    // End any existing assignments for this caretaker
                    $pdo->prepare(
                        "UPDATE caretaker_assignments SET status='inactive', removed_date=CURDATE()
                         WHERE caretaker_id=:cid AND status='active'"
                    )->execute([':cid' => $caretakerId]);
                    
                    // End any other caretaker's assignment to this apartment
                    $pdo->prepare(
                        "UPDATE caretaker_assignments SET status='inactive', removed_date=CURDATE()
                         WHERE apartment_id=:aid AND status='active'"
                    )->execute([':aid' => $apartmentId]);
                    
                    // Create new assignment
                    $pdo->prepare(
                        "INSERT INTO caretaker_assignments (caretaker_id, apartment_id, assigned_date, status)
                         VALUES (:cid, :aid, :insert_date, 'active')
                         ON DUPLICATE KEY UPDATE status='active', removed_date=NULL, assigned_date=:update_date"
                    )->execute([
                        ':cid'         => $caretakerId,
                        ':aid'         => $apartmentId,
                        ':insert_date' => $assignedDate,
                        ':update_date' => $assignedDate,
                    ]);
                    
                    // Update apartments table
                    $pdo->prepare("UPDATE apartments SET caretaker_id=:cid WHERE id=:aid")
                        ->execute([':cid' => $caretakerId, ':aid' => $apartmentId]);
                    
                    // Update caretaker_apartments table
                    $pdo->prepare(
                        "INSERT IGNORE INTO caretaker_apartments (caretaker_id, apartment_id) VALUES (:cid, :aid)"
                    )->execute([':cid' => $caretakerId, ':aid' => $apartmentId]);
                    
                    $assignmentMade = true;
                }
            }
        }

        // Notify the caretaker their account is active
        $notifBody = 'Welcome to Rentisha. You can now log in to your caretaker dashboard.';
        if ($assignmentMade) {
            $notifBody = 'Welcome to Rentisha! You have been assigned to ' . $aptName . '. You can now log in to manage your apartment.';
        }
        
        createNotification(
            $caretakerId, 'account_approved',
            'Your caretaker account has been approved!',
            $notifBody,
            'caretaker/dashboard.php'
        );

        logActivity($_SESSION['user_id'], 'approve_caretaker', 'users', $caretakerId, $fullName);
        
        $msg = "\"{$fullName}\" approved and can now log in.";
        if ($assignmentMade) {
            $msg .= " Automatically assigned to {$aptName}.";
        }
        header('Location: ' . $back . '?success=' . urlencode($msg)); exit;

    } catch (Exception $e) {
        error_log('approve_caretaker error: ' . $e->getMessage());
        header('Location: ' . $back . '?error=' . urlencode('Approval failed. Please try again.')); exit;
    }
}

// ═══════════════════════════════════════════════════════════
//  ACTION: REJECT — delete a pending caretaker registration
// ═══════════════════════════════════════════════════════════
if ($action === 'reject') {
    $caretakerId = (int)($_POST['caretaker_id'] ?? 0);
    $reason      = trim($_POST['notes'] ?? '');
    if (!$caretakerId) {
        header('Location: ' . $back . '?error=' . urlencode('Invalid caretaker.')); exit;
    }
    try {
        $pdo = getDB();
        $name = $pdo->prepare("SELECT full_name FROM users WHERE id = :id AND status = 'pending_approval' LIMIT 1");
        $name->execute([':id' => $caretakerId]);
        $fullName = $name->fetchColumn();
        if (!$fullName) {
            header('Location: ' . $back . '?error=' . urlencode('Pending registration not found.')); exit;
        }
        // Hard delete — they never had access so no history needed
        $pdo->prepare("DELETE FROM users WHERE id = :id AND role = 'caretaker' AND status = 'pending_approval'")
            ->execute([':id' => $caretakerId]);

        logActivity($_SESSION['user_id'], 'reject_caretaker', 'users', $caretakerId,
            $fullName . ($reason ? ' — ' . $reason : ''));
        header('Location: ' . $back . '?success=' . urlencode("Registration for \"{$fullName}\" rejected and removed.")); exit;

    } catch (Exception $e) {
        error_log('reject_caretaker error: ' . $e->getMessage());
        header('Location: ' . $back . '?error=' . urlencode('Rejection failed. Please try again.')); exit;
    }
}

// Fallback
header('Location: ' . $back); exit;
