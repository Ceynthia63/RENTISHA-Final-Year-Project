<?php
/**
 * Rentisha RMS — Authentication & Role Guard
 * Compatible with PHP 7.4+
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/csrf.php';

// Protected pages contain session-bound forms. Prevent browsers and proxies
// from serving an old page with an expired CSRF token.
if (!headers_sent()) {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');
}

// Polyfills for PHP < 8.0
if (!function_exists('str_starts_with')) {
    function str_starts_with(string $haystack, string $needle): bool {
        return strncmp($haystack, $needle, strlen($needle)) === 0;
    }
}
if (!function_exists('str_contains')) {
    function str_contains(string $haystack, string $needle): bool {
        return $needle === '' || strpos($haystack, $needle) !== false;
    }
}
if (!function_exists('str_ends_with')) {
    function str_ends_with(string $haystack, string $needle): bool {
        return $needle === '' || substr($haystack, -strlen($needle)) === $needle;
    }
}

/**
 * Returns the web-path base URL to the RMS-Project folder.
 * e.g. "/RMS-Project"
 */
function baseUrl(): string {
    $docRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
    // __DIR__ is the /includes/ folder; go one level up to get project root
    $projectRoot = rtrim(str_replace('\\', '/', dirname(__DIR__)), '/');

    if ($docRoot !== '' && strpos($projectRoot, $docRoot) === 0) {
        $webPath = substr($projectRoot, strlen($docRoot));
    } else {
        // Fallback: derive from SCRIPT_FILENAME
        $scriptFile = str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME'] ?? '');
        // Find "RMS-Project" in the path
        if (preg_match('|(.*?/RMS-Project)|i', $scriptFile, $m)) {
            $absProject = $m[1];
            if ($docRoot !== '' && strpos($absProject, $docRoot) === 0) {
                $webPath = substr($absProject, strlen($docRoot));
            } else {
                $webPath = '/RMS-Project';
            }
        } else {
            $webPath = '/RMS-Project';
        }
    }

    if ($webPath === '' || $webPath === false) {
        $webPath = '/RMS-Project';
    }

    return rtrim($webPath, '/');
}

/**
 * Ensure user is logged in with the required role.
 * Redirects to login or correct dashboard if not.
 */
function requireRole(?string $requiredRole = null): void {
    if (empty($_SESSION['user_id'])) {
        redirectToLogin('Please log in to continue.');
    }

    require_once __DIR__ . '/db_helpers.php';
    $timeout = rmsSessionTimeout();
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout) {
        session_unset();
        session_destroy();
        redirectToLogin('Your session has expired. Please log in again.');
    }
    $_SESSION['last_activity'] = time();

    // Re-check the account on every protected request so deleted or disabled
    // users cannot continue using an existing session.
    $currentUserId = (int)$_SESSION['user_id'];
    try {
        $userStmt = getDB()->prepare(
            "SELECT role, status FROM users WHERE id = :id LIMIT 1"
        );
        $userStmt->execute([':id' => $currentUserId]);
        $currentUser = $userStmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Auth status check failed: ' . $e->getMessage());
        session_unset();
        session_destroy();
        redirectToLogin('Unable to verify your account. Please try again.');
    }

    $sessionRole = $_SESSION['role'] ?? '';
    $allowedStatuses = $sessionRole === 'tenant'
        ? ['active', 'prospective']
        : ['active'];
    if (
        !$currentUser
        || $currentUser['role'] !== $sessionRole
        || !in_array($currentUser['status'], $allowedStatuses, true)
    ) {
        session_unset();
        session_destroy();
        redirectToLogin('Your account is no longer active. Please contact the administrator.');
    }

    // Role mismatch → send user to their own dashboard
    if ($requiredRole !== null && (isset($_SESSION['role']) ? $_SESSION['role'] : '') !== $requiredRole) {
        $base = baseUrl();
        $role = $_SESSION['role'] ?? '';
        $map  = [
            'admin'     => $base . '/admin/dashboard.php',
            'caretaker' => $base . '/caretaker/dashboard.php',
            'tenant'    => $base . '/tenant/dashboard.php',
        ];
        header('Location: ' . (isset($map[$role]) ? $map[$role] : $base . '/index.php'));
        exit;
    }

    // Forced password change — redirect to set_password.php if flagged,
    // but only when the user is NOT already on set_password.php itself
    // (prevents an infinite redirect loop).
    if (!empty($_SESSION['must_change_password'])) {
        $currentScript = basename($_SERVER['SCRIPT_FILENAME'] ?? '');
        if ($currentScript !== 'set_password.php') {
            header('Location: ' . baseUrl() . '/set_password.php');
            exit;
        }
    }
}

/**
 * Require a fully active tenant for rental-management features.
 * Prospective tenants may use the payment and notification flows only.
 */
function requireActiveTenant(): void {
    requireRole('tenant');
    $stmt = getDB()->prepare("SELECT status FROM users WHERE id=:id AND role='tenant' LIMIT 1");
    $stmt->execute([':id' => (int)$_SESSION['user_id']]);
    if ($stmt->fetchColumn() !== 'active') {
        header('Location: ' . baseUrl() . '/tenant/pay_now.php?error=' . urlencode(
            'This feature becomes available after your deposit is verified and your account is activated.'
        ));
        exit;
    }
}

/**
 * Redirect to login page with optional error message.
 */
function redirectToLogin(string $error = ''): void {
    $url = baseUrl() . '/index.php';
    if ($error !== '') {
        $url .= '?error=' . urlencode($error);
    }
    header('Location: ' . $url);
    exit;
}

/**
 * Check role without redirecting.
 */
function hasRole(string $role): bool {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

/**
 * HTML-escape a value.
 */
function e($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}
