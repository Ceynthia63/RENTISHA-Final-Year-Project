<?php
/**
 * RMS — Upload / Update Profile Avatar
 */
session_start();
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php'); exit;
}
requireCsrfToken();

if (empty($_SESSION['user_id'])) {
    header('Location: ../index.php'); exit;
}

if (empty($_FILES['avatar']['tmp_name'])) {
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '../index.php') . '?error=' . urlencode('No file uploaded.'));
    exit;
}

$file    = $_FILES['avatar'];
$ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$allowed = ['jpg', 'jpeg', 'png', 'webp'];

if (!in_array($ext, $allowed)) {
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '../index.php') . '?error=' . urlencode('Only JPEG, PNG or WebP files allowed.'));
    exit;
}

if ($file['size'] > 5 * 1024 * 1024) {
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '../index.php') . '?error=' . urlencode('File must be under 5 MB.'));
    exit;
}

// Verify it's actually an image
$imageInfo = getimagesize($file['tmp_name']);
if ($imageInfo === false) {
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '../index.php') . '?error=' . urlencode('Invalid image file.'));
    exit;
}

// Save to disk
$uploadDir = __DIR__ . '/../assets/images/avatars/';
if (!is_dir($uploadDir)) { mkdir($uploadDir, 0755, true); }

$filename = 'user_' . $_SESSION['user_id'] . '_' . time() . '.' . $ext;
$dest     = $uploadDir . $filename;

if (!move_uploaded_file($file['tmp_name'], $dest)) {
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '../index.php') . '?error=' . urlencode('Upload failed. Check folder permissions.'));
    exit;
}

// Delete old avatar file if it exists
if (!empty($_SESSION['avatar'])) {
    $oldFile = __DIR__ . '/../' . ltrim($_SESSION['avatar'], '/');
    if (is_file($oldFile)) { @unlink($oldFile); }
}

$relativePath = 'assets/images/avatars/' . $filename;

// Update DB
try {
    $pdo = getDB();
    $pdo->prepare('UPDATE users SET avatar = :avatar WHERE id = :id')
        ->execute([':avatar' => $relativePath, ':id' => $_SESSION['user_id']]);
} catch (Exception $e) {
    error_log('Avatar DB update error: ' . $e->getMessage());
}

// Update session
$_SESSION['avatar'] = $relativePath;

header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '../index.php') . '?success=' . urlencode('Profile photo updated.'));
exit;
