<?php
require_once __DIR__ . '/csrf.php';

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/db_helpers.php';

$token    = trim($_GET['token'] ?? '');
$error    = '';
$success  = false;
$validToken = false;
$userId   = null;

if (empty($token)) {
    header('Location: ../index.php');
    exit;
}


try {
    $pdo  = getDB();
    $stmt = $pdo->prepare(
        'SELECT pr.user_id, u.full_name
         FROM password_resets pr
         JOIN users u ON u.id = pr.user_id
         WHERE pr.token_hash = :hash
           AND pr.expires_at > NOW()
         LIMIT 1'
    );
    $stmt->execute([':hash' => hash('sha256', $token)]);
    $row = $stmt->fetch();
    if ($row) {
        $validToken = true;
        $userId     = $row['user_id'];
    } else {
        $error = 'This reset link is invalid or has expired.';
    }
} catch (Exception $e) {
    error_log('Reset token lookup error: ' . $e->getMessage());
    $error = 'A system error occurred.';
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validToken) {
    requireCsrfToken();
    $newPwd     = $_POST['new_password']     ?? '';
    $confirmPwd = $_POST['confirm_password'] ?? '';

    $minPasswordLength = rmsPasswordMinLength();
    if (strlen($newPwd) < $minPasswordLength) {
        $error = 'Password must meet the minimum length requirement.';
    } elseif ($newPwd !== $confirmPwd) {
        $error = 'Passwords do not match.';
    } else {
        try {
            $hash = password_hash($newPwd, PASSWORD_BCRYPT, ['cost' => 12]);
            $pdo->prepare('UPDATE users SET password_hash = :hash WHERE id = :uid')
                ->execute([':hash' => $hash, ':uid' => $userId]);
            // Invalidate token
            $pdo->prepare('DELETE FROM password_resets WHERE user_id = :uid')
                ->execute([':uid' => $userId]);
            $success = true;
        } catch (Exception $e) {
            error_log('Password reset update error: ' . $e->getMessage());
            $error = 'Failed to update password. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Reset Password — RMS</title>
  <link rel="icon" type="image/svg+xml" href="../assets/favicon.svg?v=2">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="../assets/css/style.css">
  <style>
    body{min-height:100vh;background:linear-gradient(135deg,#0f2460,#1a56db,#06b6d4);display:flex;align-items:center;justify-content:center;padding:20px;}
    .reset-card{background:#fff;border-radius:var(--radius-xl);box-shadow:var(--shadow-lg);padding:40px 36px;width:100%;max-width:440px;animation:fadeUp .5s ease;}
    @keyframes fadeUp{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}
    .brand-row{display:flex;align-items:center;gap:10px;margin-bottom:28px;}
    .brand-row .logo{width:40px;height:40px;background:linear-gradient(135deg,var(--primary),var(--secondary));border-radius:10px;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:900;font-size:1.1rem;}
  </style>
</head>
<body>
<div class="reset-card">
  <div class="brand-row">
    <div class="logo">R</div>
    <div><div style="font-weight:800;font-size:1rem;">RMS</div><div style="font-size:.68rem;color:var(--text-muted);">Rental Management System</div></div>
  </div>

  <?php if ($success): ?>
    <div style="text-align:center;padding:10px 0 20px;">
      <div style="width:56px;height:56px;background:#d1fae5;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;margin-bottom:12px;">
        <i class="bi bi-shield-check" style="color:var(--success);font-size:1.5rem;"></i>
      </div>
      <h2>Password Updated!</h2>
      <p style="margin-top:8px;font-size:.9rem;">Your password has been changed successfully. You can now log in.</p>
    </div>
    <a href="../index.php" class="btn btn-primary w-full"><i class="bi bi-box-arrow-in-right"></i> Go to Login</a>

  <?php elseif (!$validToken): ?>
    <div class="alert alert-danger"><i class="bi bi-exclamation-circle-fill"></i><div><?= htmlspecialchars($error) ?></div></div>
    <a href="forgot_password.php" class="btn btn-primary w-full mt-4"><i class="bi bi-arrow-repeat"></i> Request New Link</a>

  <?php else: ?>
    <h2 style="margin-bottom:6px;">Set New Password</h2>
    <p style="font-size:.875rem;margin-bottom:24px;">Choose a strong password for your account.</p>

    <?php if ($error): ?>
      <div class="alert alert-danger" style="margin-bottom:16px;">
        <i class="bi bi-exclamation-circle-fill"></i><div><?= htmlspecialchars($error) ?></div>
      </div>
    <?php endif; ?>

    <form method="POST" action="reset_password.php?token=<?= urlencode($token) ?>" data-validate>
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(rmsCsrfToken()) ?>">
      <div class="form-group">
        <label class="form-label">New Password <span class="required">*</span></label>
        <div class="input-group">
          <i class="bi bi-lock-fill input-icon"></i>
          <input type="password" name="new_password" id="newPwd" class="form-control"
                 placeholder="Min 8 characters" required minlength="8">
        </div>
        <div class="form-hint">Min 8 characters — mix letters, numbers, and symbols.</div>
      </div>
      <div class="form-group">
        <label class="form-label">Confirm Password <span class="required">*</span></label>
        <div class="input-group">
          <i class="bi bi-lock-fill input-icon"></i>
          <input type="password" name="confirm_password" id="confirmPwd" class="form-control"
                 placeholder="Repeat new password" required>
        </div>
        <span class="form-error" id="pwdErr" style="display:none">
          <i class="bi bi-exclamation-circle"></i> Passwords do not match
        </span>
      </div>
      <button type="submit" class="btn btn-primary w-full">
        <i class="bi bi-shield-check"></i> Set New Password
      </button>
    </form>
  <?php endif; ?>
</div>
<script src="../assets/js/main.js"></script>
<script>
document.getElementById('confirmPwd')?.addEventListener('input', function () {
  document.getElementById('pwdErr').style.display =
    this.value === document.getElementById('newPwd').value ? 'none' : 'flex';
});
</script>
</body>
</html>
