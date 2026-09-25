<?php
/**
 * Rentisha RMS — Login Handler
 * Compatible with PHP 7.4+
 * Role is determined from the database. No role selector on the form.
 */
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/db_helpers.php';
require_once __DIR__ . '/csrf.php';

// ── Helper: project base URL ──────────────────────────────────
function loginBase(): string {
    $docRoot = rtrim(str_replace('\\','/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
    $project = rtrim(str_replace('\\','/', dirname(__DIR__)), '/');
    if ($docRoot !== '' && strpos($project, $docRoot) === 0) {
        $path = substr($project, strlen($docRoot));
    } else {
        // Fallback
        if (preg_match('|(.*?/RMS-Project)|i', str_replace('\\','/',$project), $m)) {
            $path = str_replace('\\','/',$m[1]);
            if ($docRoot !== '' && strpos($path, $docRoot) === 0) {
                $path = substr($path, strlen($docRoot));
            }
        } else {
            $path = '/RMS-Project';
        }
    }
    return rtrim($path === '' ? '/RMS-Project' : $path, '/');
}

$base = loginBase();

// Only handle POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $base . '/index.php');
    exit;
}
requireCsrfToken();

// ── Input ─────────────────────────────────────────────────────
$login    = trim(isset($_POST['email_or_phone']) ? $_POST['email_or_phone'] : '');
$password = isset($_POST['password']) ? $_POST['password'] : '';
$remember = isset($_POST['remember']);

if ($login === '' || $password === '') {
    header('Location: ' . $base . '/index.php?error=' . urlencode('Please enter your email/phone and password.'));
    exit;
}

// ── Configurable brute-force guard ────────────────────────────
$attemptKey = 'login_attempts_' . md5($login);
if (!isset($_SESSION[$attemptKey])) {
    $_SESSION[$attemptKey] = array('count' => 0, 'time' => time());
}
$att = &$_SESSION[$attemptKey];
if ((time() - $att['time']) > 900) {
    $att = array('count' => 0, 'time' => time());
}
$bruteForceEnabled = rmsBruteForceEnabled();
$maxAttempts = rmsMaxLoginAttempts();
if ($bruteForceEnabled && $att['count'] >= $maxAttempts) {
    $wait = (int)ceil((900 - (time() - $att['time'])) / 60);
    header('Location: ' . $base . '/index.php?error=' . urlencode("Too many failed attempts. Try again in {$wait} min, or run fix.php."));
    exit;
}

// ── DB lookup ─────────────────────────────────────────────────
try {
    $pdo  = getDB();
    // Use two separate params for email and phone to avoid HY093 error.
    $stmt = $pdo->prepare(
        'SELECT id, full_name, email, phone, password_hash, role, status,
                avatar, must_change_password
         FROM users
         WHERE (email = :login_email OR phone = :login_phone)
         LIMIT 1'
    );
    $stmt->execute(array(':login_email' => $login, ':login_phone' => $login));
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $ex) {
    error_log('Login DB error: ' . $ex->getMessage());
    header('Location: ' . $base . '/index.php?error=' . urlencode('Database error. Check MySQL is running.'));
    exit;
}

// ── Password check ────────────────────────────────────────────
if (!$user) {
    if ($bruteForceEnabled) { $att['count']++; }
    $left = max(0, $maxAttempts - $att['count']);
    if ($left > 0) {
        $msg = "Invalid email/phone or password. {$left} attempt(s) remaining.";
    } else {
        $msg = 'Too many failed attempts. Wait 15 min or run fix.php to reset.';
    }
    header('Location: ' . $base . '/index.php?error=' . urlencode($msg));
    exit;
}

if (empty($user['password_hash'])) {
    $msg = "Your account is not fully set up. Please use the setup link sent to you, or contact the administrator.";
    header('Location: ' . $base . '/index.php?error=' . urlencode($msg));
    exit;
}

if (!password_verify($password, $user['password_hash'])) {
    if ($bruteForceEnabled) { $att['count']++; }
    $left = max(0, $maxAttempts - $att['count']);
    if ($left > 0) {
        $msg = "Invalid email/phone or password. {$left} attempt(s) remaining.";
    } else {
        $msg = 'Too many failed attempts. Wait 15 min or run fix.php to reset.';
    }
    header('Location: ' . $base . '/index.php?error=' . urlencode($msg));
    exit;
}

// ── Account status ────────────────────────────────────────────
$status  = $user['status'];
$allowed = array('active', 'prospective');
if (!in_array($status, $allowed, true)) {
    $statusMsg = array(
        'inactive'         => 'Your account is inactive. Contact the administrator.',
        'suspended'        => 'Your account has been suspended. Contact the administrator.',
        'moved_out'        => 'Your tenancy has ended. Contact the administrator.',
        'terminated'       => 'Your account has been terminated.',
        'pending_setup'    => 'Please complete your account setup using the link sent to you.',
        'pending_approval' => 'Your application is pending admin approval. You will be notified once approved.',
    );
    $msg = isset($statusMsg[$status]) ? $statusMsg[$status] : 'Your account is not active. Contact the administrator.';
    header('Location: ' . $base . '/index.php?error=' . urlencode($msg));
    exit;
}

// ── Regenerate session ────────────────────────────────────────
session_regenerate_id(true);
unset($_SESSION[$attemptKey]);

if (rmsLoginActivityEnabled()) {
    logActivity((int)$user['id'], 'login', 'users', (int)$user['id'], 'Successful login');
}

$_SESSION['user_id']       = (int)$user['id'];
$_SESSION['name']          = $user['full_name'];
$_SESSION['email']         = $user['email'];
$_SESSION['phone']         = $user['phone'];
$_SESSION['role']          = $user['role'];
$_SESSION['avatar']        = isset($user['avatar']) ? $user['avatar'] : '';
$_SESSION['last_activity'] = time();

// ── Tenant: load unit data ────────────────────────────────────
if ($user['role'] === 'tenant') {
    try {
        // Detect if move_in_date column exists (use it, else fall back to lease_start)
        $hasMoveIn = $pdo->query("SHOW COLUMNS FROM tenant_units LIKE 'move_in_date'")->fetchColumn();
        if ($hasMoveIn) {
            $miField = 'tu.move_in_date,';
        } else {
            // Older schema: alias lease_start as move_in_date so session key is consistent
            $miField = 'tu.lease_start AS move_in_date,';
        }

        $s2 = $pdo->prepare(
            "SELECT un.unit_number, a.name AS apartment_name, un.monthly_rent,
                    un.id AS unit_id, a.id AS apartment_id,
                    {$miField} tu.id AS assignment_id
             FROM tenant_units tu
             JOIN units un     ON un.id = tu.unit_id
             JOIN apartments a ON a.id  = un.apartment_id
             WHERE tu.tenant_id = :tid AND tu.status = 'active'
             LIMIT 1"
        );
        $s2->execute(array(':tid' => $user['id']));
        $ui = $s2->fetch(PDO::FETCH_ASSOC);

        if ($ui) {
            $_SESSION['unit']          = $ui['unit_number'];
            $_SESSION['apartment']     = $ui['apartment_name'];
            $_SESSION['apartment_id']  = (int)$ui['apartment_id'];
            $_SESSION['rent']          = (float)$ui['monthly_rent'];
            $_SESSION['unit_id']       = (int)$ui['unit_id'];
            $_SESSION['assignment_id'] = (int)$ui['assignment_id'];
            $_SESSION['move_in_date']  = isset($ui['move_in_date']) ? $ui['move_in_date'] : null;
        } else {
            // No unit — check preferred apartment
            $hasCol = $pdo->query("SHOW COLUMNS FROM users LIKE 'preferred_apartment_id'")->fetchColumn();
            if ($hasCol) {
                $pref = $pdo->prepare(
                    "SELECT a.id, a.name FROM users u
                     LEFT JOIN apartments a ON a.id = u.preferred_apartment_id
                     WHERE u.id = :uid LIMIT 1"
                );
                $pref->execute(array(':uid' => $user['id']));
                $pr = $pref->fetch(PDO::FETCH_ASSOC);
                if ($pr && $pr['id']) {
                    $_SESSION['preferred_apartment_id']   = (int)$pr['id'];
                    $_SESSION['preferred_apartment_name'] = $pr['name'];
                }
            }
        }
    } catch (Exception $ex) {
        error_log('Tenant session load error: ' . $ex->getMessage());
    }
}

// ── Caretaker: load assigned apartment ───────────────────────
if ($user['role'] === 'caretaker') {
    try {
        $aptRow = null;

        // Method 1: caretaker_assignments table
        $hasCaTable = $pdo->query("SHOW TABLES LIKE 'caretaker_assignments'")->fetchColumn();
        if ($hasCaTable) {
            $q = $pdo->prepare(
                "SELECT a.id, a.name, a.location
                 FROM caretaker_assignments ca
                 JOIN apartments a ON a.id = ca.apartment_id
                 WHERE ca.caretaker_id = :cid AND ca.status = 'active'
                 LIMIT 1"
            );
            $q->execute(array(':cid' => $user['id']));
            $aptRow = $q->fetch(PDO::FETCH_ASSOC);
        }

        // Method 2: apartments.caretaker_id direct
        if (!$aptRow) {
            $q2 = $pdo->prepare(
                "SELECT id, name, location FROM apartments
                 WHERE caretaker_id = :cid AND status = 'Active'
                 LIMIT 1"
            );
            $q2->execute(array(':cid' => $user['id']));
            $aptRow = $q2->fetch(PDO::FETCH_ASSOC);
        }

        // Method 3: legacy caretaker_apartments table
        if (!$aptRow) {
            $hasLeg = $pdo->query("SHOW TABLES LIKE 'caretaker_apartments'")->fetchColumn();
            if ($hasLeg) {
                $q3 = $pdo->prepare(
                    "SELECT a.id, a.name, a.location
                     FROM caretaker_apartments ca
                     JOIN apartments a ON a.id = ca.apartment_id
                     WHERE ca.caretaker_id = :cid
                     LIMIT 1"
                );
                $q3->execute(array(':cid' => $user['id']));
                $aptRow = $q3->fetch(PDO::FETCH_ASSOC);
            }
        }

        if ($aptRow) {
            $_SESSION['apartment_id']  = (int)$aptRow['id'];
            $_SESSION['apartment']     = $aptRow['name'];
            $_SESSION['apartment_loc'] = isset($aptRow['location']) ? $aptRow['location'] : '';
        }
    } catch (Exception $ex) {
        error_log('Caretaker session load error: ' . $ex->getMessage());
    }
}

// ── Remember Me ───────────────────────────────────────────────
if ($remember) {
    try {
        $token   = bin2hex(random_bytes(32));
        $expires = time() + (30 * 86400);
        $pdo->prepare(
            'INSERT INTO remember_tokens (user_id, token_hash, expires_at)
             VALUES (:uid, :hash, FROM_UNIXTIME(:exp))
             ON DUPLICATE KEY UPDATE token_hash=:hash, expires_at=FROM_UNIXTIME(:exp)'
        )->execute(array(':uid' => $user['id'], ':hash' => hash('sha256', $token), ':exp' => $expires));
        setcookie('rms_remember', $token, $expires, $base . '/', '', !empty($_SERVER['HTTPS']), true);
    } catch (Exception $ex) {
        error_log('Remember me error: ' . $ex->getMessage());
    }
}

// ── Must change password? Redirect to set_password.php ───────
if (!empty($user['must_change_password'])) {
    // Session is already set — just flag it and send them to change their password
    $_SESSION['must_change_password'] = true;
    header('Location: ' . $base . '/set_password.php');
    exit;
}

// ── Redirect to dashboard ─────────────────────────────────────
$roleMap = array(
    'admin'     => $base . '/admin/dashboard.php',
    'caretaker' => $base . '/caretaker/dashboard.php',
    'tenant'    => $base . '/tenant/dashboard.php',
);
$dest = isset($roleMap[$user['role']]) ? $roleMap[$user['role']] : $base . '/index.php';
header('Location: ' . $dest);
exit;
