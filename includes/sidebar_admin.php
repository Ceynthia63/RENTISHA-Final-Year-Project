<?php
$activePage = $activePage ?? '';
// Live DB badge counts
require_once __DIR__ . '/db_helpers.php';
try {
    $pdo = getDB();
    $maintPending = (int)$pdo->query("SELECT COUNT(*) FROM maintenance_requests WHERE status='Pending'")->fetchColumn();
    $notifCount   = isset($_SESSION['user_id']) ? getUnreadNotificationCount((int)$_SESSION['user_id']) : 0;
    $overdueCount = (int)$pdo->query("SELECT COUNT(*) FROM payments WHERE status='Overdue'")->fetchColumn();
} catch (Exception $e) {
    $maintPending = 0; $notifCount = 0; $overdueCount = 0;
}
?>
<aside class="sidebar" id="sidebar">

  <div class="sidebar-brand">
    <div class="brand-icon" style="background:linear-gradient(135deg,#007BFF,#08CFFF);">R</div>
    <div class="brand-text">
      RENTISHA
      <span>Admin Panel</span>
    </div>
  </div>

  <nav class="sidebar-nav" aria-label="Admin Navigation">

    <div class="nav-section-label">Main</div>

    <a href="dashboard.php" class="nav-item <?= $activePage==='dashboard'?'active':'' ?>" data-page="dashboard.php">
      <i class="bi bi-speedometer2"></i>
      <span class="nav-label">Dashboard</span>
    </a>

    <div class="nav-section-label">Properties</div>

    <a href="apartments.php" class="nav-item <?= $activePage==='apartments'?'active':'' ?>" data-page="apartments.php">
      <i class="bi bi-building"></i>
      <span class="nav-label">Apartments</span>
    </a>

    <a href="units.php" class="nav-item <?= $activePage==='units'?'active':'' ?>" data-page="units.php">
      <i class="bi bi-door-open"></i>
      <span class="nav-label">Units</span>
    </a>

    <div class="nav-section-label">People</div>

    <a href="tenants.php" class="nav-item <?= $activePage==='tenants'?'active':'' ?>" data-page="tenants.php">
      <i class="bi bi-people-fill"></i>
      <span class="nav-label">Tenants</span>
    </a>

    <a href="caretakers.php" class="nav-item <?= $activePage==='caretakers'?'active':'' ?>" data-page="caretakers.php">
      <i class="bi bi-person-badge-fill"></i>
      <span class="nav-label">Caretakers</span>
    </a>

    <div class="nav-section-label">Finance</div>

    <a href="payments.php" class="nav-item <?= $activePage==='payments'?'active':'' ?>" data-page="payments.php">
      <i class="bi bi-cash-stack"></i>
      <span class="nav-label">Payments</span>
      <?php if ($overdueCount > 0): ?>
      <span class="nav-badge"><?= $overdueCount ?></span>
      <?php endif; ?>
    </a>

    <a href="expenses.php" class="nav-item <?= $activePage==='expenses'?'active':'' ?>" data-page="expenses.php">
      <i class="bi bi-wrench-adjustable-circle-fill"></i>
      <span class="nav-label">Maint. Expenses</span>
    </a>

    <a href="reports.php" class="nav-item <?= $activePage==='reports'?'active':'' ?>" data-page="reports.php">
      <i class="bi bi-graph-up-arrow"></i>
      <span class="nav-label">Reports</span>
    </a>

    <div class="nav-section-label">Operations</div>

    <a href="maintenance.php" class="nav-item <?= $activePage==='maintenance'?'active':'' ?>" data-page="maintenance.php">
      <i class="bi bi-wrench-adjustable"></i>
      <span class="nav-label">Maintenance</span>
      <?php if ($maintPending > 0): ?>
      <span class="nav-badge"><?= $maintPending ?></span>
      <?php endif; ?>
    </a>

    <a href="announcements.php" class="nav-item <?= $activePage==='announcements'?'active':'' ?>" data-page="announcements.php">
      <i class="bi bi-megaphone-fill"></i>
      <span class="nav-label">Announcements</span>
    </a>

    <div class="nav-section-label">Account</div>

    <a href="profile.php" class="nav-item <?= $activePage==='profile'?'active':'' ?>" data-page="profile.php">
      <i class="bi bi-person-circle"></i>
      <span class="nav-label">Profile</span>
    </a>

    <a href="settings.php" class="nav-item <?= $activePage==='settings'?'active':'' ?>" data-page="settings.php">
      <i class="bi bi-gear-fill"></i>
      <span class="nav-label">Settings</span>
    </a>

    <a href="../includes/auth_logout.php" class="nav-item"
       onclick="return confirm('Logout of Rentisha?')">
      <i class="bi bi-box-arrow-left"></i>
      <span class="nav-label">Logout</span>
    </a>

  </nav>

  <div class="sidebar-footer">
    <div class="sidebar-user" onclick="window.location='profile.php'" style="cursor:pointer;">
      <div class="user-avatar">
        <?php if (!empty($_SESSION['avatar'])): ?>
          <img src="../<?= htmlspecialchars($_SESSION['avatar']) ?>" alt="Profile">
        <?php else: ?>
          <?= strtoupper(substr($_SESSION['name'] ?? 'A', 0, 1)) ?>
        <?php endif; ?>
      </div>
      <div class="user-info">
        <div class="user-name"><?= htmlspecialchars($_SESSION['name'] ?? 'Administrator') ?></div>
        <div class="user-role">System Admin</div>
      </div>
    </div>
  </div>

</aside>
