<?php
require_once __DIR__ . '/includes/csrf.php';

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/db_helpers.php';

if (empty($_SESSION['user_id']) || empty($_SESSION['must_change_password'])) {
    $map = ['admin'=>'admin/dashboard.php','caretaker'=>'caretaker/dashboard.php','tenant'=>'tenant/dashboard.php'];
    $dest = isset($_SESSION['role'], $map[$_SESSION['role']]) ? $map[$_SESSION['role']] : 'index.php';
    header('Location: ' . $dest); exit;
}

$userId   = (int)$_SESSION['user_id'];
$userName = $_SESSION['name']  ?? '';
$errors   = [];
$success  = false;


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();
    $newPwd  = $_POST['new_password']     ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    $minPasswordLength = rmsPasswordMinLength();
    if (strlen($newPwd) < $minPasswordLength) {
        $errors[] = 'Password must meet the minimum length requirement.';
    }
    if ($newPwd !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        try {
            $pdo  = getDB();
            $hash = password_hash($newPwd, PASSWORD_BCRYPT, ['cost' => 12]);
            $pdo->prepare(
                "UPDATE users SET password_hash=:hash, must_change_password=0, updated_at=NOW() WHERE id=:id"
            )->execute([':hash' => $hash, ':id' => $userId]);

            
            unset($_SESSION['must_change_password']);
            $success = true;

        } catch (Exception $e) {
            error_log('set_password: ' . $e->getMessage());
            $errors[] = 'Failed to update password. Please try again.';
        }
    }
}

$dashMap = ['admin'=>'admin/dashboard.php','caretaker'=>'caretaker/dashboard.php','tenant'=>'tenant/dashboard.php'];
$dashboard = $dashMap[$_SESSION['role'] ?? ''] ?? 'index.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Set Your Password — Rentisha</title>
  <link rel="icon" type="image/svg+xml" href="assets/favicon.svg?v=2">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>
    :root{--navy:#001A3D;--blue:#007BFF;--cyan:#08CFFF;}
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
    body{font-family:'Inter',sans-serif;min-height:100vh;display:flex;align-items:center;
         justify-content:center;padding:16px;background:var(--navy);overflow-y:auto;}
    .bg{position:fixed;inset:0;background:linear-gradient(135deg,rgba(0,26,61,.95) 0%,rgba(0,123,255,.5) 60%,rgba(8,207,255,.3) 100%);z-index:0;}
    .blob{position:fixed;border-radius:50%;opacity:.07;animation:fb 10s ease-in-out infinite;pointer-events:none;z-index:0;}
    .b1{width:480px;height:480px;top:-140px;right:-100px;background:var(--cyan);}
    .b2{width:360px;height:360px;bottom:-100px;left:-80px;background:var(--blue);animation-delay:4s;}
    @keyframes fb{0%,100%{transform:translateY(0) scale(1)}50%{transform:translateY(-20px) scale(1.05)}}

    .card{position:relative;z-index:1;background:rgba(255,255,255,.06);backdrop-filter:blur(22px);
      -webkit-backdrop-filter:blur(22px);border:1px solid rgba(255,255,255,.14);border-radius:20px;
      box-shadow:0 24px 60px rgba(0,0,0,.45);width:100%;max-width:420px;
      animation:cu .5s cubic-bezier(.34,1.3,.64,1) both;}
    @keyframes cu{from{opacity:0;transform:translateY(30px) scale(.97)}to{opacity:1;transform:translateY(0) scale(1)}}

    .card-head{padding:22px 28px 16px;border-bottom:1px solid rgba(255,255,255,.1);text-align:center;}
    .brand{display:inline-flex;align-items:center;gap:10px;margin-bottom:10px;}
    .brand-icon{width:40px;height:40px;background:linear-gradient(135deg,var(--blue),var(--cyan));
      border-radius:11px;display:flex;align-items:center;justify-content:center;
      font-size:1.3rem;font-weight:900;color:#fff;box-shadow:0 4px 14px rgba(0,123,255,.4);}
    .brand-name{font-size:1.25rem;font-weight:900;letter-spacing:1px;
      background:linear-gradient(90deg,#fff,var(--cyan));-webkit-background-clip:text;
      -webkit-text-fill-color:transparent;background-clip:text;}
    .card-head h2{font-size:.95rem;font-weight:700;color:rgba(255,255,255,.9);}
    .card-head p{font-size:.76rem;color:rgba(255,255,255,.45);margin-top:3px;}

    .card-body{padding:20px 28px 24px;}

    .info-box{background:rgba(245,158,11,.12);border:1px solid rgba(245,158,11,.3);
      border-radius:9px;padding:10px 13px;margin-bottom:14px;font-size:.8rem;
      color:rgba(255,255,255,.8);display:flex;gap:9px;align-items:flex-start;}
    .alert-error{background:rgba(239,68,68,.15);border:1px solid rgba(239,68,68,.35);
      border-radius:9px;padding:10px 13px;margin-bottom:14px;font-size:.8rem;
      color:#fca5a5;display:flex;gap:9px;align-items:flex-start;}
    .alert-error ul{padding-left:16px;margin-top:3px;}
    .alert-error li{margin-bottom:2px;}

    .form-group{margin-bottom:13px;}
    .form-label{display:block;font-size:.76rem;font-weight:600;color:rgba(255,255,255,.7);margin-bottom:5px;}
    .req{color:#f87171;}
    .input-wrap{position:relative;}
    .iico{position:absolute;left:11px;top:50%;transform:translateY(-50%);color:rgba(255,255,255,.35);font-size:.88rem;pointer-events:none;}
    .eye{position:absolute;right:11px;top:50%;transform:translateY(-50%);background:none;border:none;color:rgba(255,255,255,.35);cursor:pointer;padding:0;font-size:.88rem;}
    .eye:hover{color:rgba(255,255,255,.7);}
    input{width:100%;padding:9px 36px 9px 34px;background:rgba(255,255,255,.08);border:1.5px solid rgba(255,255,255,.12);border-radius:9px;color:#fff;font-size:.855rem;font-family:inherit;outline:none;transition:all .2s;}
    input::placeholder{color:rgba(255,255,255,.3);}
    input:focus{border-color:var(--blue);background:rgba(0,123,255,.1);box-shadow:0 0 0 3px rgba(0,123,255,.2);}

    .strength-wrap{margin-top:5px;}
    .strength-bar{height:4px;border-radius:2px;background:rgba(255,255,255,.1);overflow:hidden;}
    .strength-fill{height:100%;width:0;transition:width .3s,background .3s;border-radius:2px;}
    .strength-label{font-size:.68rem;color:rgba(255,255,255,.4);margin-top:3px;}
    .mismatch{font-size:.72rem;color:#f87171;margin-top:3px;display:none;}

    .btn-submit{width:100%;padding:11px;background:linear-gradient(135deg,var(--blue),var(--cyan));
      color:#fff;border:none;border-radius:9px;font-size:.9rem;font-weight:700;font-family:inherit;
      cursor:pointer;display:flex;align-items:center;justify-content:center;gap:7px;
      box-shadow:0 4px 16px rgba(0,123,255,.4);transition:all .25s;margin-top:4px;}
    .btn-submit:hover{transform:translateY(-2px);box-shadow:0 8px 22px rgba(0,123,255,.5);}
    .btn-submit:disabled{opacity:.6;cursor:not-allowed;transform:none;}

    .success-wrap{text-align:center;padding:8px 0;}
    .success-icon{width:62px;height:62px;border-radius:50%;background:rgba(126,211,33,.2);
      display:inline-flex;align-items:center;justify-content:center;font-size:1.8rem;margin-bottom:12px;}
    .success-wrap h3{color:#a3e635;font-size:1rem;margin-bottom:8px;}
    .success-wrap p{font-size:.82rem;color:rgba(255,255,255,.6);line-height:1.7;margin-bottom:18px;}
    .btn-dash{display:inline-flex;align-items:center;gap:7px;padding:11px 28px;
      background:linear-gradient(135deg,var(--blue),var(--cyan));color:#fff;border-radius:9px;
      font-weight:700;font-size:.88rem;text-decoration:none;box-shadow:0 4px 14px rgba(0,123,255,.4);}

    @keyframes spin{from{transform:rotate(0deg)}to{transform:rotate(360deg)}}
  </style>
</head>
<body>
<div class="bg"></div>
<div class="blob b1"></div>
<div class="blob b2"></div>

<div class="card">
  <div class="card-head">
    <div class="brand">
      <div class="brand-icon">R</div>
      <span class="brand-name">RENTISHA</span>
    </div>
    <h2>Set Your Password</h2>
    <p>Hello <?= htmlspecialchars($userName) ?> — please choose a secure password</p>
  </div>

  <div class="card-body">

    <?php if ($success): ?>
    <div class="success-wrap">
      <div class="success-icon"><i class="bi bi-shield-check-fill" style="color:#7ED321;"></i></div>
      <h3>Password Updated!</h3>
      <p>Your password has been set. You're now being taken to your dashboard.</p>
      <a href="<?= htmlspecialchars($dashboard) ?>" class="btn-dash">
        <i class="bi bi-speedometer2"></i> Go to Dashboard
      </a>
      <script>setTimeout(()=>location.href='<?= htmlspecialchars($dashboard) ?>',2000);</script>
    </div>

    <?php else: ?>

    <div class="info-box">
      <i class="bi bi-key-fill" style="flex-shrink:0;margin-top:1px;color:#f59e0b;"></i>
      <div>For security, please set a new personal password for your account before continuing.</div>
    </div>

    <?php if (!empty($errors)): ?>
    <div class="alert-error">
      <i class="bi bi-exclamation-circle-fill" style="flex-shrink:0;margin-top:1px;"></i>
      <div>
        <strong>Please fix:</strong>
        <ul><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
      </div>
    </div>
    <?php endif; ?>

    <form method="POST" id="setPwdForm" novalidate>
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(rmsCsrfToken()) ?>">

      <div class="form-group">
        <label class="form-label">New Password <span class="req">*</span></label>
        <div class="input-wrap">
          <i class="bi bi-lock-fill iico"></i>
          <input type="password" name="new_password" id="newPwd"
                 placeholder="Minimum 8 characters" required minlength="8"
                 autocomplete="new-password" oninput="checkStrength(this.value)">
          <button type="button" class="eye" onclick="toggleEye('newPwd','eye1')">
            <i id="eye1" class="bi bi-eye-slash-fill"></i>
          </button>
        </div>
        <div class="strength-wrap">
          <div class="strength-bar"><div class="strength-fill" id="strengthFill"></div></div>
          <div class="strength-label" id="strengthLabel">Enter a password</div>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Confirm Password <span class="req">*</span></label>
        <div class="input-wrap">
          <i class="bi bi-lock-fill iico"></i>
          <input type="password" name="confirm_password" id="confirmPwd"
                 placeholder="Repeat password" required
                 autocomplete="new-password" oninput="checkMatch()">
          <button type="button" class="eye" onclick="toggleEye('confirmPwd','eye2')">
            <i id="eye2" class="bi bi-eye-slash-fill"></i>
          </button>
        </div>
        <div class="mismatch" id="mismatch">
          <i class="bi bi-exclamation-circle"></i> Passwords do not match
        </div>
      </div>

      <button type="submit" class="btn-submit" id="submitBtn">
        <i class="bi bi-shield-lock-fill"></i> Set Password &amp; Continue
      </button>
    </form>

    <?php endif; ?>
  </div>
</div>

<script>
function toggleEye(id, iconId) {
  const inp = document.getElementById(id);
  const ico = document.getElementById(iconId);
  inp.type = inp.type === 'password' ? 'text' : 'password';
  ico.className = inp.type === 'password' ? 'bi bi-eye-slash-fill' : 'bi bi-eye-fill';
}

function checkStrength(val) {
  const fill  = document.getElementById('strengthFill');
  const label = document.getElementById('strengthLabel');
  let score = 0;
  if (val.length >= 8)  score++;
  if (val.length >= 12) score++;
  if (/[A-Z]/.test(val)) score++;
  if (/[0-9]/.test(val)) score++;
  if (/[^A-Za-z0-9]/.test(val)) score++;
  const levels = [
    {pct:0,   color:'transparent', text:'Enter a password'},
    {pct:20,  color:'#ef4444',     text:'Very weak'},
    {pct:40,  color:'#f97316',     text:'Weak'},
    {pct:60,  color:'#f59e0b',     text:'Fair'},
    {pct:80,  color:'#84cc16',     text:'Strong'},
    {pct:100, color:'#22c55e',     text:'Very strong'},
  ];
  const lv = levels[Math.min(score, 5)];
  fill.style.width      = lv.pct + '%';
  fill.style.background = lv.color;
  label.textContent     = lv.text;
  label.style.color     = lv.color === 'transparent' ? 'rgba(255,255,255,.4)' : lv.color;
}

function checkMatch() {
  const a = document.getElementById('newPwd').value;
  const b = document.getElementById('confirmPwd').value;
  document.getElementById('mismatch').style.display = b && a !== b ? 'block' : 'none';
}

document.getElementById('setPwdForm')?.addEventListener('submit', function(e) {
  const a = document.getElementById('newPwd').value;
  const b = document.getElementById('confirmPwd').value;
  if (a !== b) {
    e.preventDefault();
    document.getElementById('mismatch').style.display = 'block';
    return;
  }
  const btn = document.getElementById('submitBtn');
  btn.disabled = true;
  btn.innerHTML = '<i class="bi bi-arrow-repeat" style="animation:spin 1s linear infinite"></i> Saving…';
});
</script>
</body>
</html>
