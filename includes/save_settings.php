<?php
/**
 * Rentisha RMS — Save System Settings
 */
session_start();
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/db_helpers.php';
requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../admin/settings.php'); exit;
}
requireCsrfToken();

$allowed = [
    'system_name', 'backend_created_date', 'date_format', 'rent_due_day',
    'penalty_percent', 'paybill_number', 'timezone',
    'payment_methods',
];

try {
    $pdo  = getDB();
    $stmt = $pdo->prepare(
        "INSERT INTO system_settings (`key`, `value`) VALUES (:key, :value)
         ON DUPLICATE KEY UPDATE `value` = :value"
    );
    foreach ($allowed as $key) {
        if (isset($_POST[$key])) {
            if ($key === 'payment_methods') {
                $validMethods = ['M-PESA Paybill','Airtel Money','Cash','PDQ/POS','Cheque','Bank Transfer'];
                $methods = is_array($_POST[$key]) ? $_POST[$key] : [];
                $methods = array_values(array_intersect($validMethods, array_unique(array_map('trim', $methods))));
                $value = json_encode($methods);
            } else {
                $value = trim((string)$_POST[$key]);
            }

            // The system uses Kenyan Shillings throughout.
            $stmt->execute([':key' => 'currency', ':value' => 'KES']);
            // Basic sanitization per key
            if ($key === 'backend_created_date') {
                $date = DateTime::createFromFormat('Y-m-d', $value);
                if (!$date || $date->format('Y-m-d') !== $value || $value > date('Y-m-d')) {
                    throw new InvalidArgumentException('Invalid backend creation date.');
                }
            }
            if ($key === 'rent_due_day')    { $value = (string)max(1, min(28, (int)$value)); }
            if ($key === 'penalty_percent') { $value = (string)max(0, min(100, (int)$value)); }
            $stmt->execute([':key' => $key, ':value' => $value]);
        }
    }

    if (isset($_POST['payment_methods']) === false && isset($_POST['paybill_number'])) {
        $stmt->execute([':key' => 'payment_methods', ':value' => '[]']);
    }

    if (isset($_POST['notification_settings']) && is_array($_POST['notification_settings'])) {
        $validNotifications = [
            'rent_due_reminders', 'overdue_payment_alerts', 'maintenance_request_alerts',
            'move_in_notifications', 'move_out_notifications', 'new_tenant_registration',
        ];
        foreach ($validNotifications as $key) {
            $value = !empty($_POST['notification_settings'][$key]) ? '1' : '0';
            $stmt->execute([':key' => 'notification_' . $key, ':value' => $value]);
        }
    }
} catch (Exception $e) {
    error_log('save_settings error: ' . $e->getMessage());
    header('Location: ../admin/settings.php?error=' . urlencode('Failed to save settings.')); exit;
}

header('Location: ../admin/settings.php?success=' . urlencode('Settings saved successfully.'));
exit;
