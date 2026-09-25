<?php
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/db_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}
requireCsrfToken();

$fullName = trim($_POST['full_name'] ?? '');
$email = strtolower(trim($_POST['email'] ?? ''));
$phone = trim($_POST['phone'] ?? '');
$idNumber = trim($_POST['id_number'] ?? '');
$apartmentId = (int)($_POST['preferred_apartment_id'] ?? 0);
$notes = trim($_POST['notes'] ?? '');
$password = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';
$normalizedPhone = rmsNormalizePhone($phone);

if ($fullName === '' || $email === '' || !$normalizedPhone || !$apartmentId) {
    $message = !$normalizedPhone ? 'Enter valid phone number.' : 'Name, email and apartment are required.';
    header('Location: ../index.php?error=' . urlencode($message));
    exit;
}
if (!rmsValidEmail($email)) {
    header('Location: ../index.php?error=' . urlencode('Please enter a valid email address.'));
    exit;
}
if (strlen($password) < rmsPasswordMinLength() || $password !== $confirmPassword) {
    header('Location: ../index.php?error=' . urlencode('Passwords must match and meet the minimum length.'));
    exit;
}

try {
    $pdo = getDB();
    $apartmentStmt = $pdo->prepare(
        "SELECT id, name, caretaker_id FROM apartments WHERE id=:id AND status='Active' LIMIT 1"
    );
    $apartmentStmt->execute([':id' => $apartmentId]);
    $apartment = $apartmentStmt->fetch(PDO::FETCH_ASSOC);
    if (!$apartment) {
        header('Location: ../index.php?error=' . urlencode('Please select an active apartment.'));
        exit;
    }

    $duplicate = $pdo->prepare("SELECT id FROM users WHERE LOWER(email)=LOWER(:email) LIMIT 1");
    $duplicate->execute([':email' => $email]);
    if ($duplicate->fetchColumn()) {
        header('Location: ../index.php?error=' . urlencode('That email is already registered.'));
        exit;
    }

    $pdo->prepare(
        "INSERT INTO users
         (full_name, email, phone, id_number, password_hash, role, status,
          preferred_apartment_id, notes, must_change_password, created_at)
         VALUES (:name, :email, :phone, :idn, :hash, 'tenant', 'pending_approval',
                 :apartment, :notes, 0, NOW())"
    )->execute([
        ':name' => $fullName,
        ':email' => $email,
        ':phone' => $normalizedPhone,
        ':idn' => $idNumber ?: null,
        ':hash' => password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]),
        ':apartment' => $apartmentId,
        ':notes' => $notes ?: null,
    ]);
    $tenantId = (int)$pdo->lastInsertId();

    $caretakerId = (int)($apartment['caretaker_id'] ?? 0);
    if (!$caretakerId && _hasTable('caretaker_assignments')) {
        $assignment = $pdo->prepare(
            "SELECT caretaker_id FROM caretaker_assignments
             WHERE apartment_id=:apartment AND status='active' LIMIT 1"
        );
        $assignment->execute([':apartment' => $apartmentId]);
        $caretakerId = (int)$assignment->fetchColumn();
    }
    if ($caretakerId) {
        createNotification(
            $caretakerId,
            'tenant_registration',
            'New tenant application',
            "{$fullName} applied for {$apartment['name']}. Review the application and approve or reject it.",
            '../caretaker/tenants.php?tab=pending'
        );
    } else {
        $adminId = (int)$pdo->query("SELECT id FROM users WHERE role='admin' LIMIT 1")->fetchColumn();
        if ($adminId) {
            createNotification(
                $adminId,
                'tenant_registration',
                'Tenant application needs review',
                "{$fullName} applied for {$apartment['name']}, which has no assigned caretaker.",
                '../admin/tenants.php?status=pending_approval'
            );
        }
    }

    $reviewMessage = $caretakerId
        ? 'Application submitted. The caretaker for ' . $apartment['name'] . ' will review it.'
        : 'Application submitted. An administrator will review it because this apartment has no assigned caretaker.';
    header('Location: ../index.php?msg=' . urlencode($reviewMessage));
    exit;
} catch (PDOException $e) {
    error_log('register_tenant error: ' . $e->getMessage());
    header('Location: ../index.php?error=' . urlencode('Registration failed. Please try again.'));
    exit;
}
