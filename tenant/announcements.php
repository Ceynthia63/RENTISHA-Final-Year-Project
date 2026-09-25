<?php
session_start();
require_once '../includes/auth_check.php';
require_once '../includes/db_helpers.php';
requireActiveTenant();

$pageTitle  = 'Announcements';
$activePage = 'announcements';

$tenantId = (int)$_SESSION['user_id'];
$aptId    = (int)($_SESSION['apartment_id'] ?? 0);
$announcements = $aptId
    ? getAnnouncementsForTenant($tenantId, $aptId)
    : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Announcements — Rentisha</title>
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

      <div class="page-header">
        <div>
          <h1>Announcements</h1>
          <p>Notices from your landlord and caretaker</p>
        </div>
        <?php
          $newCount = count(array_filter($announcements, fn($a) => strtotime($a['created_at']) > strtotime('-7 days')));
          if ($newCount): ?>
        <span class="badge badge-info" style="font-size:.8rem;padding:5px 12px;">
          <i class="bi bi-bell"></i> <?= $newCount ?> New
        </span>
        <?php endif; ?>
      </div>

      <div class="card">
        <div class="card-body">
          <?php if (empty($announcements)): ?>
          <div style="text-align:center;padding:40px;color:var(--text-muted);">
            <i class="bi bi-megaphone" style="font-size:2.5rem;display:block;margin-bottom:8px;"></i>
            No announcements at the moment.
          </div>
          <?php else: ?>
            <?php foreach ($announcements as $ann):
              $isNew = strtotime($ann['created_at']) > strtotime('-7 days');
            ?>
            <div class="announcement-card
              <?= $ann['type']==='Urgent'?'urgent':($ann['type']==='Information'?'info':'') ?>"
              style="margin-bottom:14px;position:relative;">
              <?php if ($isNew): ?>
              <span class="badge badge-new" style="position:absolute;top:10px;right:12px;font-size:.65rem;">New</span>
              <?php endif; ?>
              <div class="ann-title"><?= htmlspecialchars($ann['title']) ?></div>
              <div class="ann-date">
                <i class="bi bi-calendar3"></i> <?= formatDate($ann['created_at']) ?>
                &nbsp;·&nbsp;
                <i class="bi bi-person"></i> <?= htmlspecialchars($ann['author_name']) ?>
              </div>
              <div class="ann-body" style="margin-top:8px;"><?= htmlspecialchars($ann['body']) ?></div>
            </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>

    </div>
  </div>
</div>
<script src="../assets/js/main.js"></script>
</body>
</html>
