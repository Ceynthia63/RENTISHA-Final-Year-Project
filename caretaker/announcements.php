<?php
session_start();
require_once '../includes/auth_check.php';
require_once '../includes/db_helpers.php';
requireRole('caretaker');

$pageTitle  = 'Announcements';
$activePage = 'announcements';

$aptId = (int)($_SESSION['apartment_id'] ?? 0);
if (!$aptId) { header('Location: dashboard.php'); exit; }

$announcements = getAnnouncementsForCaretaker($aptId);
$success = $_GET['success'] ?? '';
$error   = $_GET['error']   ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Announcements — Rentisha Caretaker</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="rms-shell">
  <div class="sidebar-overlay" id="sidebarOverlay"></div>
  <?php include '../includes/sidebar_caretaker.php'; ?>
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
        <div>
          <h1>Announcements</h1>
          <p>Notices from Administrator + Your apartment announcements</p>
        </div>
        <button class="btn btn-primary" data-modal-open="postAnnModal">
          <i class="bi bi-megaphone-fill"></i> Post Announcement
        </button>
      </div>

      <div class="card">
        <div class="card-body">
          <?php if (empty($announcements)): ?>
          <div style="text-align:center;padding:32px;color:var(--text-muted);">
            <i class="bi bi-megaphone" style="font-size:2.5rem;display:block;margin-bottom:8px;"></i>
            No announcements yet.
          </div>
          <?php else: ?>
            <?php foreach ($announcements as $ann): ?>
            <div class="announcement-card
              <?= $ann['type']==='Urgent'?'urgent':($ann['type']==='Information'?'info':'') ?>"
              style="margin-bottom:14px;">
              <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:6px;">
                <div class="ann-title"><?= htmlspecialchars($ann['title']) ?></div>
                <span class="badge <?= $ann['type']==='Urgent'?'badge-overdue':($ann['type']==='Information'?'badge-info':'badge-active') ?>">
                  <?= $ann['type'] ?>
                </span>
              </div>
              <div class="ann-date">
                <i class="bi bi-calendar3"></i> <?= formatDate($ann['created_at']) ?>
                &nbsp;·&nbsp; <i class="bi bi-person"></i> <?= htmlspecialchars($ann['author_name']) ?>
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

<!-- Post Announcement Modal -->
<div class="modal-overlay" id="postAnnModal">
  <div class="modal modal-lg">
    <div class="modal-header">
      <h3><i class="bi bi-megaphone-fill"></i> Post Apartment Announcement</h3>
      <button class="modal-close" data-modal-close><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="modal-body">
      <form action="../includes/save_announcement.php" method="POST" data-validate>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(rmsCsrfToken()) ?>">
        <input type="hidden" name="target"    value="specific_apartment">
        <input type="hidden" name="target_id" value="<?= $aptId ?>">

        <div class="alert alert-info mb-3">
          <i class="bi bi-info-circle-fill"></i>
          <div>This announcement will be visible to all tenants in
            <strong><?= htmlspecialchars($_SESSION['apartment'] ?? 'your apartment') ?></strong>.</div>
        </div>

        <div class="form-group">
          <label class="form-label">Title <span class="required">*</span></label>
          <input type="text" name="title" class="form-control" placeholder="Announcement title" required>
          <span class="form-error" style="display:none"><i class="bi bi-exclamation-circle"></i> Required</span>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Type</label>
            <select name="type" class="form-control">
              <option value="General">General</option>
              <option value="Urgent">Urgent</option>
              <option value="Information">Information</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Expires On (optional)</label>
            <input type="date" name="expires_at" class="form-control" min="<?= date('Y-m-d') ?>">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Message <span class="required">*</span></label>
          <textarea name="body" class="form-control" rows="5"
            placeholder="e.g. Water supply will be off on Friday from 8 AM to 2 PM…" required></textarea>
          <span class="form-error" style="display:none"><i class="bi bi-exclamation-circle"></i> Required</span>
        </div>
        <div class="modal-footer" style="padding:0;margin-top:8px;">
          <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
          <button type="submit" class="btn btn-primary"><i class="bi bi-send"></i> Post Announcement</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="../assets/js/main.js"></script>
</body>
</html>
