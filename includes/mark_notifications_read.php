<?php
session_start();
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/db_helpers.php';
if ($uid = (int)($_SESSION['user_id'] ?? 0)) {
    markNotificationsRead($uid);
}
header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '../index.php'));
exit;
