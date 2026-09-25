<?php
$pageTitle  = $pageTitle  ?? 'Dashboard';
$role       = $_SESSION['role']  ?? 'admin';
$userName   = $_SESSION['name']  ?? 'User';
$userAvatar = $_SESSION['avatar'] ?? '';

require_once __DIR__ . '/db_helpers.php';
require_once __DIR__ . '/csrf.php';
$uid = (int)($_SESSION['user_id'] ?? 0);

// Real notification count from DB
$notifCount = $uid ? getUnreadNotificationCount($uid) : 0;
$notifications = $uid ? getNotifications($uid, 5) : [];

$roleBadgeMap = array(
    'admin'     => array('label' => 'Admin',     'class' => 'badge-info'),
    'caretaker' => array('label' => 'Caretaker', 'class' => 'badge-progress'),
    'tenant'    => array('label' => 'Tenant',    'class' => 'badge-active'),
);
$roleBadge = isset($roleBadgeMap[$role]) ? $roleBadgeMap[$role] : array('label' => 'User', 'class' => 'badge-info');

$baseMap = array('caretaker' => '../caretaker/', 'tenant' => '../tenant/');
$base = isset($baseMap[$role]) ? $baseMap[$role] : '';
$profilePage  = $base . 'profile.php';
$settingsPage = $base . 'settings.php';
?>
<link rel="icon" type="image/svg+xml" href="../assets/favicon.svg?v=2">
<script>window.rmsCsrfToken = <?= json_encode(rmsCsrfToken()) ?>;</script>
<header class="topnav" role="banner">

  <button class="toggle-btn" id="sidebarToggle" aria-label="Toggle sidebar">
    <i class="bi bi-list"></i>
  </button>

  <div>
    <div class="page-title"><?= htmlspecialchars($pageTitle) ?></div>
  </div>

  <div class="topnav-right">

    <!-- Search (desktop only) -->
    <div class="input-group" style="width:200px;" aria-label="Search">
      <i class="bi bi-search input-icon"></i>
      <input type="search" class="form-control" placeholder="Quick search…"
             style="height:36px;" aria-label="Search">
    </div>

    <!-- Notifications -->
    <div class="dropdown">
      <button class="topnav-icon-btn" data-dropdown="notifDropdown" aria-label="Notifications">
        <i class="bi bi-bell-fill"></i>
        <?php if ($notifCount > 0): ?>
        <span class="badge" aria-label="<?= $notifCount ?> unread notifications"></span>
        <?php endif; ?>
      </button>
      <div class="dropdown-menu" id="notifDropdown" role="menu">
        <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 16px 8px;border-bottom:1px solid var(--border);">
          <strong style="font-size:.85rem;">Notifications</strong>
          <?php if ($notifCount > 0): ?>
          <a href="../includes/mark_notifications_read.php" style="font-size:.72rem;color:var(--primary);">
            Mark all read
          </a>
          <?php endif; ?>
        </div>
        <?php if (empty($notifications)): ?>
        <div style="padding:18px 16px;text-align:center;color:var(--text-muted);font-size:.82rem;">
          No notifications
        </div>
        <?php else: ?>
          <?php
          $notifIcons = [
              'payment_received' => 'bi-cash-coin text-success',
              'maintenance_new'  => 'bi-wrench text-warning',
              'maintenance_resolved' => 'bi-check-circle text-success',
              'rent_overdue'     => 'bi-exclamation-triangle text-danger',
              'move_in'          => 'bi-house-fill text-primary',
              'move_out'         => 'bi-box-arrow-right text-warning',
              'announcement'     => 'bi-megaphone text-primary',
              'assignment'       => 'bi-building text-primary',
          ];
          foreach ($notifications as $n):
            $ico = $notifIcons[$n['type']] ?? 'bi-bell text-primary';
          ?>
          <div class="dropdown-item" style="<?= !$n['is_read'] ? 'background:var(--primary-pale);' : '' ?>" role="menuitem">
            <i class="bi <?= $ico ?>" style="font-size:1rem;flex-shrink:0;"></i>
            <div>
              <div style="font-size:.82rem;font-weight:<?= !$n['is_read'] ? '700' : '500' ?>;">
                <?= htmlspecialchars(mb_substr($n['title'],0,50)) ?>
              </div>
              <div style="font-size:.7rem;color:var(--text-muted);">
                <?= formatDate($n['created_at']) ?>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>
        <div class="dropdown-divider"></div>
        <div style="padding:8px 16px;text-align:center;">
          <a href="<?= $base ?>notifications.php" style="font-size:.78rem;color:var(--primary);font-weight:600;">
            View all notifications
          </a>
        </div>
      </div>
    </div>

    <!-- Profile dropdown -->
    <div class="dropdown">
      <div class="topnav-profile" data-dropdown="profileDropdown" role="button"
           aria-haspopup="true" tabindex="0">
        <div class="avatar">
          <?php if ($userAvatar): ?>
            <img src="../<?= htmlspecialchars($userAvatar) ?>" alt="Profile">
          <?php else: ?>
            <?= strtoupper(substr($userName, 0, 1)) ?>
          <?php endif; ?>
        </div>
        <div>
          <div class="profile-name"><?= htmlspecialchars($userName) ?></div>
          <div class="profile-role"><?= $roleBadge['label'] ?></div>
        </div>
        <i class="bi bi-chevron-down" style="font-size:.65rem;color:var(--text-muted);"></i>
      </div>
      <div class="dropdown-menu" id="profileDropdown" role="menu">
        <div style="padding:10px 16px 8px;">
          <div style="font-size:.82rem;font-weight:700;"><?= htmlspecialchars($userName) ?></div>
          <span class="badge <?= $roleBadge['class'] ?>" style="margin-top:3px;"><?= $roleBadge['label'] ?></span>
        </div>
        <a href="<?= $profilePage ?>" class="dropdown-item" role="menuitem">
          <i class="bi bi-person-circle"></i> My Profile
        </a>
        <?php if ($role === 'admin'): ?>
        <a href="settings.php" class="dropdown-item" role="menuitem">
          <i class="bi bi-gear"></i> Settings
        </a>
        <?php endif; ?>
        <div class="dropdown-divider"></div>
        <a href="../includes/auth_logout.php" class="dropdown-item danger" role="menuitem"
           onclick="return confirm('Logout of Rentisha?')">
          <i class="bi bi-box-arrow-left"></i> Logout
        </a>
      </div>
    </div>

  </div>
</header>

<script>
(function() {
  const toggleBtn   = document.getElementById('sidebarToggle');
  const sidebar     = document.getElementById('sidebar');
  const mainContent = document.getElementById('mainContent');
  const overlay     = document.getElementById('sidebarOverlay');
  if (!toggleBtn || !sidebar) return;

  const isMobile = () => window.innerWidth <= 768;

  function toggle() {
    if (isMobile()) {
      sidebar.classList.toggle('mobile-open');
      overlay && overlay.classList.toggle('show');
    } else {
      sidebar.classList.toggle('collapsed');
      mainContent && mainContent.classList.toggle('expanded');
      localStorage.setItem('sidebar-collapsed', sidebar.classList.contains('collapsed'));
    }
  }

  toggleBtn.addEventListener('click', toggle);
  overlay && overlay.addEventListener('click', () => {
    sidebar.classList.remove('mobile-open');
    overlay.classList.remove('show');
  });

  // Restore sidebar state on desktop
  if (!isMobile() && localStorage.getItem('sidebar-collapsed') === 'true') {
    sidebar.classList.add('collapsed');
    mainContent && mainContent.classList.add('expanded');
  }

  window.addEventListener('resize', () => {
    if (!isMobile()) {
      sidebar.classList.remove('mobile-open');
      overlay && overlay.classList.remove('show');
    }
  });
})();
</script>
