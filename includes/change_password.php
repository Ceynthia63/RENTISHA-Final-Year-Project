<?php
/**
 * RMS — Change Password
 */
session_start();
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_SESSION['user_id'])) {
    header('Location: ../index.php'); exit;
}
requireCsrfToken();

$current = $_POST['current_password'] ?? '';
$new     = $_POST['new_password']     ?? '';
$confirm = $_POST['confirm_password'] ?? '';

// Use a hardcoded role-based redirect — never trust the user-controlled Referer header.
$role = $_SESSION['role'] ?? '';
$backMap = [
    'admin'     => '../admin/profile.php',
    'caretaker' => '../caretaker/profile.php',
    'tenant'    => '../tenant/profile.php',
];
$back = $backMap[$role] ?? '../index.php';

if (empty($current) || empty($new) || empty($confirm)) {
    header('Location: ' . $back . '?error=' . urlencode('All password fields are required.')); exit;
}
if (strlen($new) < rmsPasswordMinLength()) {
    header('Location: ' . $back . '?error=' . urlencode('New password must meet the minimum length requirement.')); exit;
}
if ($new !== $confirm) {
    header('Location: ' . $back . '?error=' . urlencode('New passwords do not match.')); exit;
}

try {
    $pdo  = getDB();
    $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = :id');
    $stmt->execute([':id' => $_SESSION['user_id']]);
    $row  = $stmt->fetch();

    if (!$row || !password_verify($current, $row['password_hash'])) {
        header('Location: ' . $back . '?error=' . urlencode('Current password is incorrect.')); exit;
    }

    $hash = password_hash($new, PASSWORD_BCRYPT, ['cost' => 12]);
    $pdo->prepare(
        'UPDATE users SET password_hash = :hash, must_change_password = 0, updated_at = NOW() WHERE id = :id'
    )->execute([':hash' => $hash, ':id' => $_SESSION['user_id']]);

} catch (Exception $e) {
    error_log('Change password error: ' . $e->getMessage());
    header('Location: ' . $back . '?error=' . urlencode('Failed to change password.')); exit;
}

header('Location: ' . $back . '?success=' . urlencode('Password changed successfully.'));
exit;
