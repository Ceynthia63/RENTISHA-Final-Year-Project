<?php
session_start();
require_once '../includes/auth_check.php';
require_once '../includes/db_helpers.php';
requireRole('admin');

$pageTitle  = 'Settings';
$activePage = 'settings';

$settings = getAllSettings();
$acceptedMethods = json_decode($settings['payment_methods'] ?? '[]', true);
if (!is_array($acceptedMethods) || !$acceptedMethods) {
  $acceptedMethods = ['M-PESA Paybill','Airtel Money','Cash','PDQ/POS','Cheque','Bank Transfer'];
}
$notificationSettings = [
  'rent_due_reminders' => ['Rent due reminders', 'Send automatic reminders to tenants 3 days before rent is due'],
  'overdue_payment_alerts' => ['Overdue payment alerts', 'Alert admin and caretakers when payments become overdue'],
  'maintenance_request_alerts' => ['Maintenance request alerts', 'Notify caretakers when a new maintenance request is submitted'],
  'move_in_notifications' => ['Move-in notifications', 'Notify admin when a tenant moves into a unit'],
  'move_out_notifications' => ['Move-out notifications', 'Notify admin when a tenant moves out'],
  'new_tenant_registration' => ['New tenant registration', 'Notify admin when a new prospective tenant registers'],
];
$success  = $_GET['success'] ?? '';
$error    = $_GET['error']   ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Settings — Rentisha Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="../assets/css/style.css">
  <style>
    .settings-nav{display:flex;flex-direction:column;gap:4px;}
    .settings-nav-item{display:flex;align-items:center;gap:10px;padding:10px 14px;border-radius:var(--radius-md);cursor:pointer;font-size:.875rem;font-weight:500;color:var(--text-secondary);transition:var(--transition);}
    .settings-nav-item:hover{background:var(--primary-pale);color:var(--primary);}
    .settings-nav-item.active{background:var(--primary-pale);color:var(--primary);font-weight:700;}
    .settings-nav-item i{width:20px;text-align:center;}
    .settings-section{display:none;}
    .settings-section.active{display:block;}
    .toggle-switch{position:relative;width:44px;height:24px;flex-shrink:0;}
    .toggle-switch input{opacity:0;width:0;height:0;}
    .toggle-slider{position:absolute;inset:0;background:var(--border);border-radius:24px;cursor:pointer;transition:.3s;}
    .toggle-slider::before{content:'';position:absolute;width:18px;height:18px;left:3px;bottom:3px;background:#fff;border-radius:50%;transition:.3s;}
    .toggle-switch input:checked+.toggle-slider{background:var(--primary);}
    .toggle-switch input:checked+.toggle-slider::before{transform:translateX(20px);}
    .setting-row{display:flex;align-items:center;justify-content:space-between;padding:14px 0;border-bottom:1px solid var(--border);}
    .setting-row:last-child{border-bottom:none;}
    .setting-row .setting-info h4{font-size:.9rem;font-weight:600;}
    .setting-row .setting-info p{font-size:.78rem;color:var(--text-muted);margin-top:2px;}
  </style>
</head>
<body>
<div class="rms-shell">
  <div class="sidebar-overlay" id="sidebarOverlay"></div>
  <?php include '../includes/sidebar_admin.php'; ?>
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
        <div><h1>System Settings</h1><p>Configure Rentisha preferences and integrations</p></div>
      </div>

      <div style="display:grid;grid-template-columns:200px 1fr;gap:20px;align-items:start;">

        <!-- Settings sidebar nav -->
        <div class="card">
          <div class="card-body">
            <div class="settings-nav" role="tablist">
              <div class="settings-nav-item active" data-section="general"       role="tab"><i class="bi bi-gear"></i>        General</div>
              <div class="settings-nav-item"         data-section="payments"      role="tab"><i class="bi bi-cash-coin"></i>   Payments</div>
              <div class="settings-nav-item"         data-section="notifications" role="tab"><i class="bi bi-bell"></i>        Notifications</div>
            </div>
          </div>
        </div>

        <!-- Settings panels -->
        <div>

          <!-- General -->
          <div class="settings-section active card" id="section-general">
            <div class="card-header"><h3 class="card-title"><i class="bi bi-gear"></i> General Settings</h3></div>
            <div class="card-body">
              <form action="../includes/save_settings.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(rmsCsrfToken()) ?>">
                <div class="form-group">
                  <label class="form-label">System / Property Name</label>
                  <input type="text" name="system_name" class="form-control"
                         value="<?= htmlspecialchars($settings['system_name'] ?? 'Rentisha') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Rent Due Day (each month)</label>
                    <input type="number" name="rent_due_day" class="form-control"
                           value="<?= htmlspecialchars($settings['rent_due_day'] ?? '5') ?>"
                           min="1" max="28">
                    <div class="form-hint">Day of the month when rent is due.</div>
                </div>
                <div class="form-group">
                  <label class="form-label">Late Payment Penalty (%)</label>
                  <input type="number" name="penalty_percent" class="form-control"
                         value="<?= htmlspecialchars($settings['penalty_percent'] ?? '10') ?>"
                         min="0" max="100" style="max-width:120px;">
                </div>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Save Settings</button>
              </form>
            </div>
          </div>

          <!-- Payments -->
          <div class="settings-section card" id="section-payments">
            <div class="card-header"><h3 class="card-title"><i class="bi bi-cash-coin"></i> Payment Settings</h3></div>
            <div class="card-body">
              <div class="alert alert-info mb-4">
                <i class="bi bi-info-circle-fill"></i>
                <div>Configure your M-PESA Paybill. Tenants will see this on the payments page.</div>
              </div>
              <form action="../includes/save_settings.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(rmsCsrfToken()) ?>">
                <div class="form-group">
                  <label class="form-label">M-PESA Paybill Number</label>
                  <input type="text" name="paybill_number" class="form-control"
                         value="<?= htmlspecialchars($settings['paybill_number'] ?? '') ?>"
                         placeholder="e.g. 522533" style="max-width:220px;">
                  <div class="form-hint">Tenants use this to pay rent via M-PESA. Account = Unit Number.</div>
                </div>
                <div class="form-group">
                  <label class="form-label">Payment Methods Accepted</label>
                  <div style="display:flex;flex-direction:column;gap:8px;margin-top:6px;">
                    <?php foreach (['M-PESA Paybill','Airtel Money','Cash','PDQ/POS','Cheque','Bank Transfer'] as $method): ?>
                    <label style="display:flex;align-items:center;gap:8px;font-size:.875rem;cursor:pointer;">
                      <input type="checkbox" name="payment_methods[]" value="<?= $method ?>"
                             <?= in_array($method, $acceptedMethods, true) ? 'checked' : '' ?>
                             style="accent-color:var(--primary);width:16px;height:16px;">
                      <?= $method ?>
                    </label>
                    <?php endforeach; ?>
                  </div>
                </div>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Save Payment Settings</button>
              </form>
            </div>
          </div>

          <!-- Notifications -->
          <div class="settings-section card" id="section-notifications">
            <div class="card-header"><h3 class="card-title"><i class="bi bi-bell"></i> Notification Preferences</h3></div>
            <div class="card-body">
              <form action="../includes/save_settings.php" method="POST">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(rmsCsrfToken()) ?>">
              <?php foreach ($notificationSettings as $key => [$title,$desc]): ?>
              <div class="setting-row">
                <div class="setting-info">
                  <h4><?= $title ?></h4>
                  <p><?= $desc ?></p>
                </div>
                <label class="toggle-switch" aria-label="<?= $title ?>">
                  <input type="hidden" name="notification_settings[<?= $key ?>]" value="0">
                  <input type="checkbox" name="notification_settings[<?= $key ?>]" value="1"
                    <?= ($settings['notification_' . $key] ?? '1') === '1' ? 'checked' : '' ?>>
                  <span class="toggle-slider"></span>
                </label>
              </div>
              <?php endforeach; ?>
              <button type="submit" class="btn btn-primary mt-4">
                <i class="bi bi-check-lg"></i> Save Preferences
              </button>
              </form>
            </div>
          </div>

        </div>
      </div>

    </div>
  </div>
</div>
<script src="../assets/js/main.js"></script>
<script>
document.querySelectorAll('.settings-nav-item').forEach(item => {
  item.addEventListener('click', () => {
    document.querySelectorAll('.settings-nav-item').forEach(i => i.classList.remove('active'));
    document.querySelectorAll('.settings-section').forEach(s => s.classList.remove('active'));
    item.classList.add('active');
    const sec = document.getElementById('section-' + item.dataset.section);
    if (sec) sec.classList.add('active');
  });
});
</script>
</body>
</html>
