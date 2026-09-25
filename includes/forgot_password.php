<?php
require_once __DIR__ . '/csrf.php';

require_once __DIR__ . '/db.php';

$sent  = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();
    $email = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        try {
            $pdo  = getDB();
            $stmt = $pdo->prepare('SELECT id, full_name FROM users WHERE email = :email AND status = "active" LIMIT 1');
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch();

            if ($user) {
                
                $token   = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

                $pdo->prepare(
                    'INSERT INTO password_resets (user_id, token_hash, expires_at)
                     VALUES (:uid, :hash, :exp)
                     ON DUPLICATE KEY UPDATE token_hash = :hash, expires_at = :exp'
                )->execute([
                    ':uid'  => $user['id'],
                    ':hash' => hash('sha256', $token),
                    ':exp'  => $expires,
                ]);

                
                $resetLink = (isset($_SERVER['HTTPS']) ? 'https' : 'http')
                    . '://' . $_SERVER['HTTP_HOST']
                    . '/RMS-Project/includes/reset_password.php?token=' . urlencode($token);

               
                $subject = 'RMS — Password Reset Request';
                $body    = "Hello {$user['full_name']},\n\n"
                         . "We received a request to reset your RMS password.\n\n"
                         . "Click the link below to reset it (valid for 1 hour):\n"
                         . $resetLink . "\n\n"
                         . "If you did not request this, ignore this email.\n\n"
                         . "— RMS Team";
                $headers = "From: noreply@rms.co.ke\r\n"
                         . "Content-Type: text/plain; charset=UTF-8\r\n";
                
                if (!@mail($email, $subject, $body, $headers)) {
                    error_log('Forgot password email could not be sent. Configure SMTP in php.ini or use a mail service.');
                }
            }
            
            $sent = true;
        } catch (Exception $e) {
            error_log('Forgot password error: ' . $e->getMessage());
            $error = 'A system error occurred. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Forgot Password — RMS</title>
  <link rel="icon" type="image/svg+xml" href="../assets/favicon.svg?v=2">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="../assets/css/style.css">
  <style>
    body {
      min-height:100vh;
      background:linear-gradient(135deg,#0f2460,#1a56db,#06b6d4);
      display:flex;align-items:center;justify-content:center;padding:20px;
    }
    .forgot-card {
      background:#fff;border-radius:var(--radius-xl);
      box-shadow:var(--shadow-lg);padding:40px 36px;
      width:100%;max-width:440px;
      animation:fadeUp .5s ease;
    }
    @keyframes fadeUp { from{opacity:0;transform:translateY(20px)} to{opacity:1;transform:translateY(0)} }
    .brand-row { display:flex;align-items:center;gap:10px;margin-bottom:28px; }
    .brand-row .logo { width:40px;height:40px;background:linear-gradient(135deg,var(--primary),var(--secondary));border-radius:10px;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:900;font-size:1.1rem; }
    .back-link { display:inline-flex;align-items:center;gap:6px;font-size:.83rem;color:var(--primary);font-weight:600;margin-top:20px; }
  </style>
</head>
<body>
<div class="forgot-card">
  <div class="brand-row">
    <div class="logo">R</div>
    <div><div style="font-weight:800;font-size:1rem;">RMS</div><div style="font-size:.68rem;color:var(--text-muted);">Rental Management System</div></div>
  </div>

  <?php if ($sent): ?>
    <div style="text-align:center;padding:10px 0 20px;">
      <div style="width:56px;height:56px;background:#d1fae5;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;margin-bottom:12px;">
        <i class="bi bi-envelope-check-fill" style="color:var(--success);font-size:1.5rem;"></i>
      </div>
      <h2>Check your email</h2>
      <p style="margin-top:8px;font-size:.9rem;">If an account exists for that email, we've sent a password reset link. It expires in <strong>1 hour</strong>.</p>
    </div>
    <a href="../index.php" class="btn btn-primary w-full"><i class="bi bi-box-arrow-in-right"></i> Back to Login</a>
  <?php else: ?>
    <h2 style="margin-bottom:6px;">Forgot Password?</h2>
    <p style="font-size:.875rem;margin-bottom:24px;">Enter your registered email and we'll send a reset link.</p>

    <?php if ($error): ?>
      <div class="alert alert-danger" style="margin-bottom:16px;">
        <i class="bi bi-exclamation-circle-fill"></i><div><?= htmlspecialchars($error) ?></div>
      </div>
    <?php endif; ?>

    <form method="POST" data-validate>
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(rmsCsrfToken()) ?>">
      <div class="form-group">
        <label class="form-label">Email Address <span class="required">*</span></label>
        <div class="input-group">
          <i class="bi bi-envelope-fill input-icon"></i>
          <input type="email" name="email" class="form-control" placeholder="you@example.com"
                 required autocomplete="email"
                 value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        </div>
        <span class="form-error" style="display:none"><i class="bi bi-exclamation-circle"></i> Required</span>
      </div>
      <button type="submit" class="btn btn-primary w-full">
        <i class="bi bi-send-fill"></i> Send Reset Link
      </button>
    </form>
    <a href="../index.php" class="back-link"><i class="bi bi-arrow-left"></i> Back to Login</a>
  <?php endif; ?>
</div>
<script src="../assets/js/main.js"></script>
</body>
</html>
