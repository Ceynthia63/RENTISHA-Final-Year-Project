<?php
/**
 * RMS — Update Profile Information
 */
session_start();
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/db_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_SESSION['user_id'])) {
    header('Location: ../index.php'); exit;
}
requireCsrfToken();

$full_name        = trim($_POST['full_name']        ?? '');
$email            = trim($_POST['email']            ?? '');
$phone            = trim($_POST['phone']            ?? '');
$emergency_name   = trim($_POST['emergency_name']   ?? '');
$emergency_phone  = trim($_POST['emergency_phone']  ?? '');
$normalizedPhone = rmsNormalizePhone($phone);
$normalizedEmergencyPhone = $emergency_phone === '' ? '' : rmsNormalizePhone($emergency_phone);

if (empty($full_name)) {
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '../index.php') . '?error=' . urlencode('Full name is required.'));
    exit;
}

if (!$normalizedPhone || ($emergency_phone !== '' && !$normalizedEmergencyPhone)) {
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '../index.php') . '?error=' . urlencode('Enter valid phone number.'));
    exit;
}

if ($email && !rmsValidEmail($email)) {
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '../index.php') . '?error=' . urlencode('Invalid email address.'));
    exit;
}

// Build emergency_contact string
$ec = trim($emergency_name . ($normalizedEmergencyPhone ? ' — ' . $normalizedEmergencyPhone : ''));

try {
    $pdo = getDB();
    $pdo->prepare(
        'UPDATE users SET full_name=:name, email=:email, phone=:phone,
                          emergency_contact=:ec, updated_at=NOW()
         WHERE id=:id'
    )->execute([
        ':name'  => $full_name,
        ':email' => $email,
        ':phone' => $normalizedPhone,
        ':ec'    => $ec,
        ':id'    => $_SESSION['user_id'],
    ]);
} catch (PDOException $e) {
    error_log('Update profile error: ' . $e->getMessage());
    $msg = strpos($e->getMessage(), '1062') !== false
        ? 'That email is already in use by another account.'
        : 'Failed to update profile. Please try again.';
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '../index.php') . '?error=' . urlencode($msg));
    exit;
}

// Refresh session
$_SESSION['name']  = $full_name;
$_SESSION['email'] = $email;
$_SESSION['phone'] = $normalizedPhone;

header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '../index.php') . '?success=' . urlencode('Profile updated successfully.'));
exit;
