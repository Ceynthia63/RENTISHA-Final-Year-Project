<?php
$activePage = $activePage ?? '';
require_once __DIR__ . '/db_helpers.php';
$tenantId   = (int)(isset($_SESSION['user_id'])     ? $_SESSION['user_id']     : 0);
$aptId      = (int)(isset($_SESSION['apartment_id'])? $_SESSION['apartment_id']: 0);
$tenantStatus = 'active';
$statusStmt = $tenantId ? getDB()->prepare("SELECT status FROM users WHERE id=:id AND role='tenant' LIMIT 1") : null;
if ($statusStmt) {
    $statusStmt->execute([':id' => $tenantId]);
    $tenantStatus = (string)($statusStmt->fetchColumn() ?: 'active');
}
$isProspective = $tenantStatus === 'prospective';
$notifCount = 0;
$openMaint  = 0;
$newAnns    = 0;
try {
    $notifCount = $tenantId ? getUnreadNotificationCount($tenantId) : 0;
    if ($tenantId) {
        $pdo = getDB();
        $ms  = $pdo->prepare(
            "SELECT COUNT(*) FROM maintenance_requests
             WHERE tenant_id=:tid AND status IN ('Pending','In Progress')"
        );
        $ms->execute(array(':tid' => $tenantId));
        $openMaint = (int)$ms->fetchColumn();

        if ($aptId) {
            $as = $pdo->prepare(
                "SELECT COUNT(*) FROM announcements a
                 WHERE (a.expires_at IS NULL OR a.expires_at > NOW())
                   AND created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
                   AND (a.target='all_tenants'
                        OR (a.target='specific_apartment' AND a.target_id=:aid)
                        OR (a.target='specific_tenant'    AND a.target_id=:tid))"
            );
            $as->execute(array(':aid' => $aptId, ':tid' => $tenantId));
            $newAnns = (int)$as->fetchColumn();
        }
    }
} catch (Exception $e) {
    $notifCount = 0; $openMaint = 0; $newAnns = 0;
}
?>
<aside class="sidebar" id="sidebar">

  <div class="sidebar-brand">
    <div class="brand-icon" style="background:linear-gradient(135deg,#007BFF,#08CFFF);">R</div>
    <div class="brand-text">
      RENTISHA
      <span>Tenant Portal</span>
    </div>
  </div>

  <nav class="sidebar-nav" aria-label="Tenant Navigation">

    <div class="nav-section-label">Main</div>

    <a href="dashboard.php" class="nav-item <?= $activePage==='dashboard'?'active':'' ?>" data-page="dashboard.php">
      <i class="bi bi-speedometer2"></i>
      <span class="nav-label">Dashboard</span>
    </a>

    <div class="nav-section-label">My Rental</div>

    <?php if (!$isProspective): ?>
    <a href="my_unit.php" class="nav-item <?= $activePage==='my_unit'?'active':'' ?>" data-page="my_unit.php">
      <i class="bi bi-house-fill"></i>
      <span class="nav-label">My Unit</span>
    </a>
    <?php endif; ?>

    <a href="pay_now.php" class="nav-item <?= $activePage==='pay_now'?'active':'' ?>" data-page="pay_now.php" style="background:linear-gradient(135deg,#10b981,#059669);color:white;font-weight:700;">
      <i class="bi bi-credit-card-fill"></i>
      <span class="nav-label">💰 Pay Now</span>
    </a>

    <a href="payments.php" class="nav-item <?= $activePage==='payments'?'active':'' ?>" data-page="payments.php">
      <i class="bi bi-cash-stack"></i>
      <span class="nav-label">Rent &amp; Payments</span>
    </a>

    <?php if (!$isProspective): ?>
    <a href="expenses.php" class="nav-item <?= $activePage==='expenses'?'active':'' ?>" data-page="expenses.php">
      <i class="bi bi-lightning-charge-fill"></i>
      <span class="nav-label">Shared Expenses</span>
    </a>
    <?php endif; ?>

    <div class="nav-section-label">Support</div>

    <?php if (!$isProspective): ?>
    <a href="maintenance.php" class="nav-item <?= $activePage==='maintenance'?'active':'' ?>" data-page="maintenance.php">
      <i class="bi bi-wrench-adjustable"></i>
      <span class="nav-label">Maintenance</span>
      <?php if ($openMaint > 0): ?>
      <span class="nav-badge"><?= $openMaint ?></span>
      <?php endif; ?>
    </a>
    <?php endif; ?>

    <?php if (!$isProspective): ?>
    <a href="announcements.php" class="nav-item <?= $activePage==='announcements'?'active':'' ?>" data-page="announcements.php">
      <i class="bi bi-megaphone-fill"></i>
      <span class="nav-label">Announcements</span>
      <?php if ($newAnns > 0): ?>
      <span class="nav-badge"><?= $newAnns ?></span>
      <?php endif; ?>
    </a>
    <?php endif; ?>

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
          <?= strtoupper(substr(isset($_SESSION['name']) ? $_SESSION['name'] : 'T', 0, 1)) ?>
        <?php endif; ?>
      </div>
      <div class="user-info">
        <div class="user-name"><?= htmlspecialchars(isset($_SESSION['name']) ? $_SESSION['name'] : 'Tenant') ?></div>
        <div class="user-role">Unit <?= htmlspecialchars(isset($_SESSION['unit']) ? $_SESSION['unit'] : 'N/A') ?></div>
      </div>
    </div>
  </div>

</aside>
