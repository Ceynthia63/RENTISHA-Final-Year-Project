<?php
/**
 * RMS — Logout Handler
 */

session_start();

// Clear remember-me cookie if set
if (isset($_COOKIE['rms_remember'])) {
    // Revoke token in DB
    if (!empty($_SESSION['user_id'])) {
        require_once __DIR__ . '/db.php';
        try {
            $pdo = getDB();
            $pdo->prepare('DELETE FROM remember_tokens WHERE user_id = :uid')
                ->execute([':uid' => $_SESSION['user_id']]);
        } catch (Exception $e) {
            error_log('Logout token revoke error: ' . $e->getMessage());
        }
    }
    setcookie('rms_remember', '', [
        'expires'  => time() - 3600,
        'path'     => '/RMS-Project/',
        'secure'   => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

// Destroy session completely
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}
session_destroy();

header('Location: ../index.php?msg=' . urlencode('You have been logged out successfully.'));
exit;
