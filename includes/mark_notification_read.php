<?php

session_start();
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '../index.php'));
    exit;
}
requireCsrfToken();

$notificationId = (int)($_POST['notification_id'] ?? 0);
$userId = (int)($_SESSION['user_id'] ?? 0);

if ($notificationId && $userId) {
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("
            UPDATE notifications 
            SET is_read = 1, read_at = NOW() 
            WHERE id = :nid AND user_id = :uid
        ");
        $stmt->execute([':nid' => $notificationId, ':uid' => $userId]);
    } catch (Exception $e) {
        error_log('Mark notification read error: ' . $e->getMessage());
    }
}

header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '../admin/notifications.php'));
exit;
