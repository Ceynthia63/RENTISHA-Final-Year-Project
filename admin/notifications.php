<?php

session_start();
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/db_helpers.php';
requireRole('admin');

$pageTitle = 'Notifications';
$uid = (int)$_SESSION['user_id'];

$filter = $_GET['filter'] ?? 'all'; 

$pdo = getDB();
$sql = "SELECT * FROM notifications WHERE user_id = :uid";
if ($filter === 'unread') {
    $sql .= " AND is_read = 0";
} elseif ($filter === 'read') {
    $sql .= " AND is_read = 1";
}
$sql .= " ORDER BY created_at DESC LIMIT 100";

$stmt = $pdo->prepare($sql);
$stmt->execute([':uid' => $uid]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

$unreadCount = getUnreadNotificationCount($uid);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $pageTitle ?> — Rentisha</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>

<?php include '../includes/sidebar_admin.php'; ?>

<div class="main-content" id="mainContent">
  <?php include '../includes/topnav.php'; ?>

  <div class="content-wrapper">
    
    
    <div class="page-header">
      <div>
        <h2><i class="bi bi-bell-fill"></i> Notifications</h2>
        <p class="text-muted">View and manage your notifications</p>
      </div>
      <div style="display: flex; gap: 10px;">
        <?php if ($unreadCount > 0): ?>
        <a href="../includes/mark_notifications_read.php" class="btn btn-outline" 
           onclick="return confirm('Mark all <?= $unreadCount ?> notifications as read?')">
          <i class="bi bi-check-all"></i> Mark All Read (<?= $unreadCount ?>)
        </a>
        <?php endif; ?>
      </div>
    </div>

    
    <div class="tabs" style="margin-bottom: 20px;">
      <a href="?filter=all" class="tab <?= $filter === 'all' ? 'active' : '' ?>">
        <i class="bi bi-list-ul"></i> All (<?= count($notifications) ?>)
      </a>
      <a href="?filter=unread" class="tab <?= $filter === 'unread' ? 'active' : '' ?>">
        <i class="bi bi-envelope-fill"></i> Unread (<?= $unreadCount ?>)
      </a>
      <a href="?filter=read" class="tab <?= $filter === 'read' ? 'active' : '' ?>">
        <i class="bi bi-envelope-open"></i> Read
      </a>
    </div>

    
    <div class="card">
      <?php if (empty($notifications)): ?>
        <div class="empty-state">
          <i class="bi bi-bell-slash" style="font-size: 4rem; opacity: 0.3;"></i>
          <h3>No Notifications</h3>
          <p>
            <?php if ($filter === 'unread'): ?>
              You're all caught up! No unread notifications.
            <?php elseif ($filter === 'read'): ?>
              No read notifications yet.
            <?php else: ?>
              You don't have any notifications yet.
            <?php endif; ?>
          </p>
        </div>
      <?php else: ?>
        <div class="notification-list">
          <?php
          $notifIcons = [
              'payment_received' => ['icon' => 'bi-cash-coin', 'color' => 'success', 'bg' => 'rgba(34,197,94,0.1)'],
              'maintenance_new'  => ['icon' => 'bi-wrench', 'color' => 'warning', 'bg' => 'rgba(251,191,36,0.1)'],
              'maintenance_resolved' => ['icon' => 'bi-check-circle', 'color' => 'success', 'bg' => 'rgba(34,197,94,0.1)'],
              'rent_overdue'     => ['icon' => 'bi-exclamation-triangle', 'color' => 'danger', 'bg' => 'rgba(239,68,68,0.1)'],
              'move_in'          => ['icon' => 'bi-house-fill', 'color' => 'primary', 'bg' => 'rgba(59,130,246,0.1)'],
              'move_out'         => ['icon' => 'bi-box-arrow-right', 'color' => 'warning', 'bg' => 'rgba(251,191,36,0.1)'],
              'announcement'     => ['icon' => 'bi-megaphone', 'color' => 'info', 'bg' => 'rgba(14,165,233,0.1)'],
              'tenant_registration' => ['icon' => 'bi-person-plus-fill', 'color' => 'info', 'bg' => 'rgba(14,165,233,0.1)'],
              'assignment'       => ['icon' => 'bi-building', 'color' => 'primary', 'bg' => 'rgba(59,130,246,0.1)'],
              'caretaker_registration' => ['icon' => 'bi-person-plus', 'color' => 'info', 'bg' => 'rgba(14,165,233,0.1)'],
              'account_approved' => ['icon' => 'bi-check-circle-fill', 'color' => 'success', 'bg' => 'rgba(34,197,94,0.1)'],
          ];
          
          foreach ($notifications as $n):
            $iconData = $notifIcons[$n['type']] ?? ['icon' => 'bi-bell', 'color' => 'primary', 'bg' => 'rgba(59,130,246,0.1)'];
            $isUnread = !$n['is_read'];
          ?>
          <div class="notification-item <?= $isUnread ? 'unread' : '' ?>" 
               style="display: flex; gap: 15px; padding: 16px; border-bottom: 1px solid var(--border); <?= $isUnread ? 'background: var(--primary-pale);' : '' ?>">
            
        
            <div style="width: 50px; height: 50px; border-radius: 12px; background: <?= $iconData['bg'] ?>; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
              <i class="bi <?= $iconData['icon'] ?> text-<?= $iconData['color'] ?>" style="font-size: 1.5rem;"></i>
            </div>

            
            <div style="flex: 1; min-width: 0;">
              <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 5px;">
                <h4 style="font-size: 0.95rem; margin: 0; <?= $isUnread ? 'font-weight: 700;' : 'font-weight: 500;' ?>">
                  <?= htmlspecialchars($n['title']) ?>
                  <?php if ($isUnread): ?>
                    <span class="badge badge-primary" style="margin-left: 8px; font-size: 0.65rem;">NEW</span>
                  <?php endif; ?>
                </h4>
                <span class="text-muted" style="font-size: 0.75rem; white-space: nowrap;">
                  <?= formatDate($n['created_at']) ?>
                </span>
              </div>
              
              <?php if (!empty($n['body'])): ?>
              <p style="margin: 0 0 8px 0; color: var(--text-muted); font-size: 0.85rem; line-height: 1.5;">
                <?= htmlspecialchars($n['body']) ?>
              </p>
              <?php endif; ?>

              <div style="display: flex; gap: 10px; align-items: center;">
                <?php if (!empty($n['link'])): ?>
                <a href="<?= htmlspecialchars(notificationLink($n['link'])) ?>" class="btn btn-sm btn-outline">
                  <i class="bi bi-arrow-right"></i> View Details
                </a>
                <?php endif; ?>
                
                <?php if ($isUnread): ?>
                <form method="POST" action="../includes/mark_notification_read.php" style="display: inline;">
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(rmsCsrfToken()) ?>">
                  <input type="hidden" name="notification_id" value="<?= $n['id'] ?>">
                  <button type="submit" class="btn btn-sm btn-text" style="color: var(--primary);">
                    <i class="bi bi-check"></i> Mark as Read
                  </button>
                </form>
                <?php endif; ?>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

  </div>
</div>

<style>
.notification-item {
  transition: all 0.2s;
}
.notification-item:hover {
  background: var(--hover-bg) !important;
}
.empty-state {
  text-align: center;
  padding: 60px 20px;
}
.empty-state h3 {
  margin: 15px 0 5px;
  color: var(--text-dark);
}
.empty-state p {
  color: var(--text-muted);
}
.tabs {
  display: flex;
  gap: 5px;
  border-bottom: 2px solid var(--border);
}
.tab {
  padding: 12px 20px;
  text-decoration: none;
  color: var(--text-muted);
  font-weight: 500;
  border-bottom: 3px solid transparent;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  gap: 8px;
}
.tab:hover {
  color: var(--text-dark);
  background: var(--hover-bg);
}
.tab.active {
  color: var(--primary);
  border-bottom-color: var(--primary);
}
</style>

<script src="../assets/js/main.js"></script>
</body>
</html>
