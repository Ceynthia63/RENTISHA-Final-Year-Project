<?php
$activePage = $activePage ?? '';
require_once __DIR__ . '/db_helpers.php';
$aptId = (int)($_SESSION['apartment_id'] ?? 0);
$maintPending = 0;
$sidebarPendingTenants = 0;
$notifCount   = 0;
try {
    $pdo = getDB();
    if ($aptId) {
        $ms = $pdo->prepare(
            "SELECT COUNT(*) FROM maintenance_requests mr
             JOIN units u ON u.id = mr.unit_id
             WHERE u.apartment_id = :aid AND mr.status = 'Pending'"
        );
        $ms->execute(array(':aid' => $aptId));
        $maintPending = (int)$ms->fetchColumn();
        $ps = $pdo->prepare(
            "SELECT COUNT(*) FROM users
             WHERE role='tenant' AND status='pending_approval'
               AND preferred_apartment_id=:aid"
        );
        $ps->execute([':aid' => $aptId]);
        $sidebarPendingTenants = (int)$ps->fetchColumn();
    }
    $notifCount = isset($_SESSION['user_id']) ? getUnreadNotificationCount((int)$_SESSION['user_id']) : 0;
} catch (Exception $e) {
    $maintPending = 0;
    $sidebarPendingTenants = 0;
    $notifCount   = 0;
}
?>
<aside class="sidebar" id="sidebar">

  <div class="sidebar-brand">
    <div class="brand-icon" style="background:linear-gradient(135deg,#007BFF,#08CFFF);">R</div>
    <div class="brand-text">
      RENTISHA
      <span>Caretaker Panel</span>
    </div>
  </div>

  <nav class="sidebar-nav" aria-label="Caretaker Navigation">

    <div class="nav-section-label">Main</div>

    <a href="dashboard.php" class="nav-item <?= $activePage==='dashboard'?'active':'' ?>" data-page="dashboard.php">
      <i class="bi bi-speedometer2"></i>
      <span class="nav-label">Dashboard</span>
    </a>

    <div class="nav-section-label">My Property</div>

    <a href="units.php" class="nav-item <?= $activePage==='units'?'active':'' ?>" data-page="units.php">
      <i class="bi bi-door-open"></i>
      <span class="nav-label">My Units</span>
    </a>

    <a href="tenants.php" class="nav-item <?= $activePage==='tenants'?'active':'' ?>" data-page="tenants.php">
      <i class="bi bi-people-fill"></i>
      <span class="nav-label">Tenants</span>
      <?php if ($sidebarPendingTenants > 0): ?>
      <span class="nav-badge"><?= $sidebarPendingTenants ?></span>
      <?php endif; ?>
    </a>

    <div class="nav-section-label">Finance</div>

    <a href="rent.php" class="nav-item <?= $activePage==='rent'?'active':'' ?>" data-page="rent.php">
      <i class="bi bi-cash-stack"></i>
      <span class="nav-label">Rent Payments</span>
    </a>

    <a href="utilities.php" class="nav-item <?= $activePage==='utilities'?'active':'' ?>" data-page="utilities.php">
      <i class="bi bi-lightning-charge"></i>
      <span class="nav-label">Utility Bills</span>
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

    <a href="expenses.php" class="nav-item <?= $activePage==='expenses'?'active':'' ?>" data-page="expenses.php">
      <i class="bi bi-wrench-adjustable-circle-fill"></i>
      <span class="nav-label">Maint. Expenses</span>
    </a>

    <div class="nav-section-label">Account</div>

    <a href="profile.php" class="nav-item <?= $activePage==='profile'?'active':'' ?>" data-page="profile.php">
      <i class="bi bi-person-circle"></i>
      <span class="nav-label">Profile</span>
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
          <?= strtoupper(substr(isset($_SESSION['name']) ? $_SESSION['name'] : 'C', 0, 1)) ?>
        <?php endif; ?>
      </div>
      <div class="user-info">
        <div class="user-name"><?= htmlspecialchars(isset($_SESSION['name']) ? $_SESSION['name'] : 'Caretaker') ?></div>
        <div class="user-role"><?= htmlspecialchars(isset($_SESSION['apartment']) ? $_SESSION['apartment'] : 'Caretaker') ?></div>
      </div>
    </div>
  </div>

</aside>
