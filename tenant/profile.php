<?php
session_start();
require_once '../includes/auth_check.php';
require_once '../includes/db_helpers.php';
requireRole('tenant');

$pageTitle  = 'My Profile';
$activePage = 'profile';

$tenant  = getTenantById((int)$_SESSION['user_id']);
$success = $_GET['success'] ?? '';
$error   = $_GET['error']   ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Profile — Rentisha</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="rms-shell">
  <div class="sidebar-overlay" id="sidebarOverlay"></div>
  <?php include '../includes/sidebar_tenant.php'; ?>
  <div class="main-content" id="mainContent">
    <?php include '../includes/topnav.php'; ?>
    <div class="page-content">

      <?php if ($success): ?>
      <div class="alert alert-success mb-4"><i class="bi bi-check-circle-fill"></i><div><?= htmlspecialchars($success) ?></div></div>
      <?php endif; ?>
      <?php if ($error): ?>
      <div class="alert alert-danger mb-4"><i class="bi bi-exclamation-circle-fill"></i><div><?= htmlspecialchars($error) ?></div></div>
      <?php endif; ?>

      <div class="page-header">
        <div><h1>My Profile</h1><p>Manage your personal information and password</p></div>
      </div>

      <div class="grid-2">
        <!-- Photo -->
        <div class="card">
          <div class="card-header"><h3 class="card-title"><i class="bi bi-person-circle"></i> Profile Photo</h3></div>
          <div class="card-body" style="text-align:center;">
            <form action="../includes/upload_avatar.php" method="POST" enctype="multipart/form-data">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(rmsCsrfToken()) ?>">
              <div class="avatar-upload-area">
                <div class="avatar-preview" id="tAvatarPreview">
                  <?php if (!empty($_SESSION['avatar'])): ?>
                    <img src="../<?= htmlspecialchars($_SESSION['avatar']) ?>" alt="Profile" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">
                  <?php else: ?>
                    <span style="font-size:2.5rem;font-weight:800;color:#007BFF;">
                      <?= strtoupper(substr($_SESSION['name']??'T',0,1)) ?>
                    </span>
                  <?php endif; ?>
                </div>
                <div class="avatar-actions">
                  <label class="btn btn-primary btn-sm" for="tAvatarFile" aria-label="Upload photo">
                    <i class="bi bi-camera-fill"></i> Change Photo
                  </label>
                  <input type="file" id="tAvatarFile" name="avatar" class="avatar-file-input" accept="image/*" style="display:none;">
                </div>
                <p class="form-hint">JPEG, PNG or WebP — max 5 MB</p>
                <button type="submit" class="btn btn-success btn-sm">
                  <i class="bi bi-cloud-upload"></i> Save Photo
                </button>
              </div>
            </form>
          </div>
        </div>

        <!-- Editable info -->
        <div class="card">
          <div class="card-header"><h3 class="card-title"><i class="bi bi-info-circle"></i> Personal Information</h3></div>
          <div class="card-body">
            <form action="../includes/update_profile.php" method="POST" data-validate>
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(rmsCsrfToken()) ?>">
              <div class="form-group">
                <label class="form-label">Full Name <span class="required">*</span></label>
                <input type="text" name="full_name" class="form-control"
                       value="<?= htmlspecialchars($tenant['full_name'] ?? '') ?>" required>
              </div>
              <div class="form-group">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control"
                       value="<?= htmlspecialchars($tenant['email'] ?? '') ?>" placeholder="your@email.com">
              </div>
              <div class="form-group">
                <label class="form-label">Phone Number</label>
                <input type="tel" name="phone" class="form-control"
                       value="<?= htmlspecialchars($tenant['phone'] ?? '') ?>" placeholder="07XX XXX XXX" pattern="(?:0[17][0-9]{8}|\+254[17][0-9]{8}|00254[17][0-9]{8})" maxlength="13" inputmode="tel" title="Enter a valid Kenyan phone number">
              </div>
              <div class="form-group">
                <label class="form-label">Emergency Contact</label>
                <input type="text" name="emergency_contact" class="form-control"
                       value="<?= htmlspecialchars($tenant['emergency_contact'] ?? '') ?>"
                       placeholder="Name — 07XX XXX XXX">
              </div>
              <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-lg"></i> Update Profile
              </button>
            </form>
          </div>
        </div>
      </div>

      <!-- Read-only lease info -->
      <div class="card mt-4">
        <div class="card-header"><h3 class="card-title"><i class="bi bi-file-earmark-lock"></i> Lease & Unit Information</h3>
          <div class="form-hint" style="font-size:.75rem;color:var(--text-muted);">These fields are managed by Admin / Caretaker</div>
        </div>
        <div class="card-body">
          <div class="form-row">
            <?php $readOnly = [
              ['Apartment',    $tenant['apartment_name'] ?? '—'],
              ['Unit',         $tenant['unit_number'] ?? '—'],
              ['Monthly Rent', $tenant['unit_rent'] ? formatKES($tenant['unit_rent']) : '—'],
              ['Move-in Date', formatDate($tenant['move_in_date'])],
              ['Caretaker',    $tenant['caretaker_name'] ?? '—'],
              ['Caretaker Ph', $tenant['caretaker_phone'] ?? '—'],
            ]; foreach ($readOnly as [$l,$v]): ?>
            <div>
              <p class="fs-sm text-muted"><?= $l ?></p>
              <p class="fw-bold"><?= htmlspecialchars($v) ?></p>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <!-- Change password -->
      <div class="card mt-4">
        <div class="card-header"><h3 class="card-title"><i class="bi bi-shield-lock"></i> Change Password</h3></div>
        <div class="card-body">
          <form action="../includes/change_password.php" method="POST" data-validate style="max-width:500px;">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(rmsCsrfToken()) ?>">
            <div class="form-group">
              <label class="form-label">Current Password <span class="required">*</span></label>
              <div class="input-group">
                <i class="bi bi-lock-fill input-icon"></i>
                <input type="password" name="current_password" class="form-control" required placeholder="Current password">
              </div>
            </div>
            <div class="form-group">
              <label class="form-label">New Password <span class="required">*</span></label>
              <div class="input-group">
                <i class="bi bi-lock-fill input-icon"></i>
                <input type="password" name="new_password" id="tNewPwd" class="form-control" required placeholder="Min 8 characters">
              </div>
            </div>
            <div class="form-group">
              <label class="form-label">Confirm New Password <span class="required">*</span></label>
              <div class="input-group">
                <i class="bi bi-lock-fill input-icon"></i>
                <input type="password" name="confirm_password" id="tConfirmPwd" class="form-control" required placeholder="Repeat new password">
              </div>
              <span class="form-error" id="tPwdErr" style="display:none;">
                <i class="bi bi-exclamation-circle"></i> Passwords do not match
              </span>
            </div>
            <button type="submit" class="btn btn-danger">
              <i class="bi bi-shield-check"></i> Change Password
            </button>
          </form>
        </div>
      </div>

    </div>
  </div>
</div>
<script src="../assets/js/main.js"></script>
<script>
document.getElementById('tConfirmPwd')?.addEventListener('input', function() {
  document.getElementById('tPwdErr').style.display =
    this.value === document.getElementById('tNewPwd').value ? 'none' : 'flex';
});
</script>
</body>
</html>
