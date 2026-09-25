<?php
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/db.php';


header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
if (isset($_SESSION['user_id'], $_SESSION['role'])) {
    $map = [
        'admin'     => 'admin/dashboard.php',
        'caretaker' => 'caretaker/dashboard.php',
        'tenant'    => 'tenant/dashboard.php',
    ];
    $dest = $map[$_SESSION['role']] ?? 'index.php';
    header('Location: ' . $dest); exit;
}

$registrationApartments = [];
try {
    $registrationDb = getDB();
    $registrationApartments = $registrationDb->query(
        "SELECT id, name, location FROM apartments WHERE status='Active' ORDER BY name"
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log('Registration apartment load error: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Rentisha — Rental Management System</title>
  <link rel="icon" type="image/svg+xml" href="assets/favicon.svg?v=2">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>
    :root {
      --navy:      #001A3D;
      --navy-dark: #001329;
      --blue:      #007BFF;
      --cyan:      #08CFFF;
      --lime:      #7ED321;
      --white:     #FFFFFF;
      --bg-light:  #F5F8FC;
      --text-dark: #10233F;
      --text-muted:#6B7A90;
    }

    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: 'Inter', sans-serif;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 12px;
      position: relative;
      background: var(--navy-dark);
      overflow-y: auto;
    }

    
    .bg-image {
      position: fixed;
      inset: 0;
      background:
        linear-gradient(135deg, rgba(0,26,61,.92) 0%, rgba(0,123,255,.55) 60%, rgba(8,207,255,.35) 100%),
        url('assets/images/login-bg.jpg') center/cover no-repeat;
      z-index: 0;
    }

    
    .blob {
      position: fixed;
      border-radius: 50%;
      opacity: .07;
      background: var(--cyan);
      animation: floatBlob 10s ease-in-out infinite;
      z-index: 0;
    }
    .blob-1 { width:500px;height:500px;top:-150px;right:-100px; }
    .blob-2 { width:380px;height:380px;bottom:-100px;left:-80px;animation-delay:4s;background:var(--blue); }
    .blob-3 { width:200px;height:200px;top:40%;left:30%;animation-delay:7s;background:var(--lime);opacity:.04; }

    @keyframes floatBlob {
      0%,100% { transform: translateY(0) scale(1); }
      50%      { transform: translateY(-24px) scale(1.06); }
    }

    
    .login-card {
      position: relative;
      z-index: 1;
      background: rgba(255,255,255,.06);
      backdrop-filter: blur(22px);
      -webkit-backdrop-filter: blur(22px);
      border: 1px solid rgba(255,255,255,.14);
      border-radius: 20px;
      box-shadow: 0 24px 60px rgba(0,0,0,.45);
      width: 100%;
      max-width: 420px;
      overflow: hidden;
      animation: cardUp .55s cubic-bezier(.34,1.3,.64,1) both;
    
      max-height: calc(100vh - 24px);
      overflow-y: auto;
    }

    @keyframes cardUp {
      from { opacity:0; transform: translateY(40px) scale(.97); }
      to   { opacity:1; transform: translateY(0)   scale(1); }
    }

    
    .card-head {
      padding: 20px 28px 16px;
      text-align: center;
      border-bottom: 1px solid rgba(255,255,255,.1);
    }

    .brand-logo {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      margin-bottom: 10px;
    }

    .logo-icon {
      width: 40px; height: 40px;
      background: linear-gradient(135deg, var(--blue), var(--cyan));
      border-radius: 11px;
      display: flex; align-items: center; justify-content: center;
      font-size: 1.3rem; font-weight: 900; color: var(--white);
      box-shadow: 0 4px 16px rgba(0,123,255,.4);
      flex-shrink: 0;
    }

    .logo-text { text-align: left; }

    .logo-text .name {
      font-size: 1.25rem;
      font-weight: 900;
      letter-spacing: 1px;
      background: linear-gradient(90deg, var(--white), var(--cyan));
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }

    .logo-text .tagline {
      font-size: .62rem;
      color: rgba(255,255,255,.5);
      letter-spacing: 1.5px;
      text-transform: uppercase;
      display: block;
      margin-top: -2px;
    }

    .card-head h2 {
      font-size: .9rem;
      font-weight: 600;
      color: rgba(255,255,255,.85);
      margin-bottom: 2px;
    }

    .card-head p {
      font-size: .72rem;
      color: rgba(255,255,255,.45);
    }

    
    .card-body {
      padding: 18px 28px 22px;
    }

    .error-box {
      display: none;
      align-items: center;
      gap: 10px;
      background: rgba(239,68,68,.15);
      border: 1px solid rgba(239,68,68,.4);
      border-radius: 10px;
      padding: 11px 14px;
      margin-bottom: 18px;
      color: #fca5a5;
      font-size: .83rem;
    }
    .error-box.show { display: flex; }

    .success-box {
      display: none;
      align-items: center;
      gap: 10px;
      background: rgba(126,211,33,.12);
      border: 1px solid rgba(126,211,33,.35);
      border-radius: 10px;
      padding: 11px 14px;
      margin-bottom: 18px;
      color: #a3e635;
      font-size: .83rem;
    }
    .success-box.show { display: flex; }

    .form-group { margin-bottom: 11px; }

    .form-label {
      display: block;
      font-size: .75rem;
      font-weight: 600;
      color: rgba(255,255,255,.7);
      margin-bottom: 5px;
      letter-spacing: .3px;
    }

    .input-wrap {
      position: relative;
      display: flex;
      align-items: center;
    }

    .input-icon {
      position: absolute;
      left: 12px;
      color: rgba(255,255,255,.35);
      font-size: .9rem;
      pointer-events: none;
    }

    .input-wrap input {
      width: 100%;
      padding: 9px 12px 9px 34px;
      background: rgba(255,255,255,.08);
      border: 1.5px solid rgba(255,255,255,.12);
      border-radius: 9px;
      color: var(--white);
      font-size: .85rem;
      font-family: inherit;
      transition: all .2s;
      outline: none;
    }

    .input-wrap input::placeholder { color: rgba(255,255,255,.3); }

    .input-wrap input:focus {
      border-color: var(--blue);
      background: rgba(0,123,255,.1);
      box-shadow: 0 0 0 3px rgba(0,123,255,.2);
    }

    .input-wrap .toggle-pwd {
      position: absolute;
      right: 10px;
      cursor: pointer;
      color: rgba(255,255,255,.35);
      font-size: .85rem;
      transition: color .2s;
      background: none;
      border: none;
      padding: 0;
    }
    .input-wrap .toggle-pwd:hover { color: rgba(255,255,255,.7); }

    .form-footer {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 14px;
    }

    .remember-me {
      display: flex;
      align-items: center;
      gap: 5px;
      font-size: .75rem;
      color: rgba(255,255,255,.55);
      cursor: pointer;
    }

    .remember-me input { accent-color: var(--blue); cursor: pointer; }

    .forgot-link {
      font-size: .75rem;
      color: var(--cyan);
      font-weight: 600;
      text-decoration: none;
      transition: opacity .2s;
    }
    .forgot-link:hover { opacity: .75; }

    .btn-login {
      width: 100%;
      padding: 10px;
      background: linear-gradient(135deg, var(--blue), var(--cyan));
      color: var(--white);
      border: none;
      border-radius: 9px;
      font-size: .9rem;
      font-weight: 700;
      font-family: inherit;
      cursor: pointer;
      transition: all .25s;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 7px;
      box-shadow: 0 4px 16px rgba(0,123,255,.4);
      letter-spacing: .3px;
    }

    .btn-login:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 22px rgba(0,123,255,.5);
    }

    .btn-login:active { transform: translateY(0); }
    .btn-login:disabled { opacity: .6; cursor: not-allowed; transform: none; }

    .divider {
      display: flex;
      align-items: center;
      gap: 8px;
      margin: 14px 0 10px;
    }
    .divider::before, .divider::after {
      content: '';
      flex: 1;
      height: 1px;
      background: rgba(255,255,255,.1);
    }
    .divider span { font-size: .7rem; color: rgba(255,255,255,.35); white-space: nowrap; }

    /* Features strip — hidden when viewport is short */
    .features {
      display: flex;
      gap: 0;
      border-top: 1px solid rgba(255,255,255,.08);
    }

    @media (max-height: 620px) {
      .features { display: none; }
    }

    .feature {
      flex: 1;
      padding: 10px 6px;
      text-align: center;
      border-right: 1px solid rgba(255,255,255,.08);
    }
    .feature:last-child { border-right: none; }

    .feature i {
      display: block;
      font-size: .95rem;
      color: var(--cyan);
      margin-bottom: 2px;
    }

    .feature span {
      font-size: .62rem;
      color: rgba(255,255,255,.4);
      display: block;
      line-height: 1.3;
    }

    @media (max-width: 480px) {
      .card-head, .card-body { padding-left: 22px; padding-right: 22px; }
      .features { display: none; }
      .links-row { flex-direction: column; }
    }
  </style>
</head>
<body>

  <div class="bg-image"></div>
  <div class="blob blob-1"></div>
  <div class="blob blob-2"></div>
  <div class="blob blob-3"></div>



  <div class="login-card">

    <!-- Brand header -->
    <div class="card-head">
      <div class="brand-logo">
        <div class="logo-icon">R</div>
        <div class="logo-text">
          <span class="name">RENTISHA</span>
          <span class="tagline">Rental Management System</span>
        </div>
      </div>
      <h2 id="cardTitle">Welcome Back</h2>
      <p id="cardSubtitle">Sign in to continue managing your properties</p>
    </div>

    <!-- Form -->
    <div class="card-body">

      <!-- Error -->
      <div class="error-box" id="errorBox">
        <i class="bi bi-exclamation-circle-fill"></i>
        <span id="errorMsg"></span>
      </div>

      <!-- Success (e.g. after password reset) -->
      <div class="success-box" id="successBox">
        <i class="bi bi-check-circle-fill"></i>
        <span id="successMsg"></span>
      </div>

      <form id="loginForm" action="includes/auth_login.php" method="POST">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(rmsCsrfToken()) ?>">
        <div class="form-group">
          <label class="form-label" for="email_or_phone">Email or Phone Number</label>
          <div class="input-wrap">
            <i class="bi bi-person-fill input-icon"></i>
            <input type="text" id="email_or_phone" name="email_or_phone"
                   placeholder="Enter your email or phone number"
                   autocomplete="username"
                   value="<?= htmlspecialchars($_GET['prefill'] ?? '') ?>"
                   required autofocus>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label" for="password">Password</label>
          <div class="input-wrap">
            <i class="bi bi-lock-fill input-icon"></i>
            <input type="password" id="password" name="password"
                   placeholder="Enter your password"
                   autocomplete="current-password" required>
            <button type="button" class="toggle-pwd" id="togglePwd" aria-label="Toggle password visibility">
              <i class="bi bi-eye-slash-fill"></i>
            </button>
          </div>
        </div>

        <div class="form-footer">
          <label class="remember-me">
            <input type="checkbox" name="remember"> Remember me
          </label>
          <a href="includes/forgot_password.php" class="forgot-link">Forgot Password?</a>
        </div>

        <button type="submit" class="btn-login" id="loginBtn">
          <i class="bi bi-box-arrow-in-right"></i> Sign In
        </button>
      </form>

      <div style="text-align:center;margin-top:16px;">
        <button type="button" class="forgot-link" id="showRegister"
                style="background:none;border:0;cursor:pointer;font-family:inherit;">
          New tenant? Apply for an apartment
        </button>
      </div>

      <form id="registerForm" action="includes/register_tenant.php" method="POST" style="display:none;">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(rmsCsrfToken()) ?>">
        <div class="form-group">
          <label class="form-label" for="reg_name">Full Name</label>
          <div class="input-wrap"><i class="bi bi-person-fill input-icon"></i>
            <input type="text" id="reg_name" name="full_name" placeholder="Your full name" required>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label" for="reg_email">Email</label>
          <div class="input-wrap"><i class="bi bi-envelope-fill input-icon"></i>
            <input type="email" id="reg_email" name="email" maxlength="180" placeholder="you@example.com" required>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label" for="reg_phone">Phone Number</label>
          <div class="input-wrap"><i class="bi bi-phone-fill input-icon"></i>
            <input type="tel" id="reg_phone" name="phone" placeholder="07XX XXX XXX" pattern="(?:0[17][0-9]{8}|\+254[17][0-9]{8}|00254[17][0-9]{8})" maxlength="13" inputmode="tel" title="Enter a valid Kenyan phone number (e.g. 0712345678)" required>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label" for="reg_id">ID Number</label>
          <div class="input-wrap"><i class="bi bi-card-text input-icon"></i>
            <input type="text" id="reg_id" name="id_number" placeholder="National ID or passport">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label" for="reg_apartment">Apartment You Want</label>
          <select id="reg_apartment" name="preferred_apartment_id" required
                  style="width:100%;padding:9px 12px;background:rgba(255,255,255,.08);border:1.5px solid rgba(255,255,255,.12);border-radius:9px;color:#fff;font-family:inherit;">
            <option value="" style="color:#111827;background:#fff;">Select an apartment</option>
            <?php foreach ($registrationApartments as $apartment): ?>
            <option value="<?= (int)$apartment['id'] ?>" style="color:#111827;background:#fff;">
              <?= htmlspecialchars($apartment['name'] . ' — ' . $apartment['location']) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label" for="reg_password">Password</label>
          <div class="input-wrap"><i class="bi bi-lock-fill input-icon"></i>
            <input type="password" id="reg_password" name="password" minlength="8" placeholder="At least 8 characters" required>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label" for="reg_confirm">Confirm Password</label>
          <div class="input-wrap"><i class="bi bi-lock-fill input-icon"></i>
            <input type="password" id="reg_confirm" name="confirm_password" minlength="8" placeholder="Repeat your password" required>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label" for="reg_notes">Message to the caretaker (optional)</label>
          <textarea id="reg_notes" name="notes" rows="2" placeholder="Move-in timing or other details"
                    style="width:100%;padding:9px 12px;background:rgba(255,255,255,.08);border:1.5px solid rgba(255,255,255,.12);border-radius:9px;color:#fff;font-family:inherit;"></textarea>
        </div>
        <button type="submit" class="btn-login" id="registerBtn">
          <i class="bi bi-send"></i> Submit Application
        </button>
      </form>

      <!-- Reset helper link -->
      <div style="text-align:center;margin-top:10px;">
        <a href="includes/forgot_password.php"
           style="font-size:.68rem;color:rgba(255,255,255,.3);text-decoration:none;">
          Having trouble logging in? Reset your password
        </a>
      </div>

      <div style="text-align:center;margin-top:14px;padding-top:14px;border-top:1px solid rgba(255,255,255,.08);font-size:.75rem;color:rgba(255,255,255,.35);line-height:1.6;">
        <i class="bi bi-info-circle"></i>
        Tenant applications are reviewed by the caretaker for the apartment you select.
      </div>

    </div>

    <!-- Feature strip -->
    <div class="features">
      <div class="feature"><i class="bi bi-shield-check"></i><span>Secure Login</span></div>
      <div class="feature"><i class="bi bi-phone"></i><span>M-PESA Ready</span></div>
      <div class="feature"><i class="bi bi-bar-chart-line"></i><span>Real Reports</span></div>
      <div class="feature"><i class="bi bi-people"></i><span>3 Roles</span></div>
    </div>

  </div>

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <script>
    // Show error/success from URL params
    const params = new URLSearchParams(location.search);
    if (params.get('error')) {
      const b = document.getElementById('errorBox');
      document.getElementById('errorMsg').textContent = params.get('error');
      b.classList.add('show');
    }
    if (params.get('msg')) {
      const b = document.getElementById('successBox');
      document.getElementById('successMsg').textContent = params.get('msg');
      b.classList.add('show');
    }

    document.getElementById('togglePwd').addEventListener('click', function() {
      const input = document.getElementById('password');
      const icon  = this.querySelector('i');
      if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'bi bi-eye-fill';
      } else {
        input.type = 'password';
        icon.className = 'bi bi-eye-slash-fill';
      }
    });

    document.getElementById('loginForm').addEventListener('submit', function() {
      const btn = document.getElementById('loginBtn');
      btn.disabled = true;
      btn.innerHTML = '<i class="bi bi-arrow-repeat" style="animation:spin 1s linear infinite"></i> Signing in…';
    });

    document.getElementById('showRegister').addEventListener('click', function() {
      document.getElementById('loginForm').style.display = 'none';
      document.getElementById('registerForm').style.display = 'block';
      document.getElementById('showRegister').style.display = 'none';
      document.getElementById('cardTitle').textContent = 'Apply as a Tenant';
      document.getElementById('cardSubtitle').textContent = 'Choose the apartment you want to move into';
    });

    document.getElementById('registerForm').addEventListener('submit', function() {
      const btn = document.getElementById('registerBtn');
      btn.disabled = true;
      btn.innerHTML = '<i class="bi bi-arrow-repeat" style="animation:spin 1s linear infinite"></i> Submitting…';
    });
  </script>
  <style>
    @keyframes spin { from{transform:rotate(0deg)} to{transform:rotate(360deg)} }
  </style>
</body>
</html>
