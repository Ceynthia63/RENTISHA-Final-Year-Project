<?php

session_start();
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/db.php';
requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../admin/announcements.php'); exit;
}
requireCsrfToken();

$title      = trim($_POST['title']      ?? '');
$body       = trim($_POST['body']       ?? '');
$type       = trim($_POST['type']       ?? 'General');
$target     = trim($_POST['target']     ?? 'all_tenants');
$target_id  = null;
$expires_at = trim($_POST['expires_at'] ?? '');
$created_by = $_SESSION['user_id'];

$validTypes   = ['General','Urgent','Information'];
$validTargets = ['all_tenants','all_caretakers','specific_apartment','specific_tenant'];

if (empty($title) || empty($body)) {
    header('Location: ../admin/announcements.php?error=' . urlencode('Title and message are required.')); exit;
}
if (!in_array($type,   $validTypes))   { $type   = 'General'; }
if (!in_array($target, $validTargets)) { $target  = 'all_tenants'; }

if ($target === 'specific_apartment') {
    $target_id = (int)($_POST['apartment_id'] ?? ($_POST['target_id'] ?? 0));
} elseif ($target === 'specific_tenant') {
    $target_id = (int)($_POST['tenant_id'] ?? ($_POST['target_id'] ?? 0));
}

if (in_array($target, ['specific_apartment', 'specific_tenant'], true) && $target_id < 1) {
    header('Location: ../admin/announcements.php?error=' . urlencode('Please select a target for this announcement.'));
    exit;
}

if ($expires_at !== '') {
    $expiry = DateTime::createFromFormat('!Y-m-d', $expires_at);
    $expiryErrors = DateTime::getLastErrors();
    if (
        !$expiry
        || ($expiryErrors !== false && ($expiryErrors['warning_count'] > 0 || $expiryErrors['error_count'] > 0))
    ) {
        header('Location: ../admin/announcements.php?error=' . urlencode('Please provide a valid expiry date.'));
        exit;
    }
    $expires_at = $expiry->format('Y-m-d') . ' 23:59:59';
} else {
    $expires_at = null;
}

try {
    $pdo = getDB();
    $pdo->prepare(
        'INSERT INTO announcements (created_by, title, body, type, target, target_id, expires_at)
         VALUES (:uid, :title, :body, :type, :target, :tid, :exp)'
    )->execute([
        ':uid'   => $created_by,
        ':title' => $title,
        ':body'  => $body,
        ':type'  => $type,
        ':target'=> $target,
        ':tid'   => $target_id,
        ':exp'   => $expires_at,
    ]);
} catch (Exception $e) {
    error_log('Save announcement error: ' . $e->getMessage());
    header('Location: ../admin/announcements.php?error=' . urlencode('Failed to post announcement.')); exit;
}

header('Location: ../admin/announcements.php?success=' . urlencode('Announcement posted successfully.'));
exit;
