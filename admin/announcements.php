<?php
session_start();
require_once '../includes/auth_check.php';
require_once '../includes/db_helpers.php';
requireRole('admin');
$pageTitle  = 'Announcements';
$activePage = 'announcements';
$announcements = getAllAnnouncements();
$apartments = getApartments();
$tenants = getTenants('active_or_prospective');
$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Announcements — RMS Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="rms-shell">
  <div class="sidebar-overlay" id="sidebarOverlay"></div>
  <?php include '../includes/sidebar_admin.php'; ?>
  <div class="main-content" id="mainContent">
    <?php include '../includes/topnav.php'; ?>
    <div class="page-content">
    <?php if ($success): ?><div class="alert alert-success mb-4"><i class="bi bi-check-circle-fill"></i><div><?= htmlspecialchars($success) ?></div></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger mb-4"><i class="bi bi-exclamation-circle-fill"></i><div><?= htmlspecialchars($error) ?></div></div><?php endif; ?>

      <div class="page-header">
        <div>
          <h1>Announcements</h1>
          <p>Post and manage notifications for tenants and caretakers</p>
        </div>
        <button class="btn btn-primary" data-modal-open="postAnnouncementModal">
          <i class="bi bi-megaphone-fill"></i> Post Announcement
        </button>
      </div>


      <div class="filter-bar">
        <div class="input-group search-box">
          <i class="bi bi-search input-icon"></i>
          <input type="search" class="form-control" placeholder="Search announcements…" data-search-table="annTable">
        </div>
        <select class="form-control" style="width:auto">
          <option>All Types</option>
          <option>General</option>
          <option>Urgent</option>
          <option>Information</option>
        </select>
        <select class="form-control" style="width:auto">
          <option>All Audiences</option>
          <option>All Tenants</option>
          <option>All Caretakers</option>
          <option>Specific Apartment</option>
        </select>
      </div>

      
      <div class="card">
        <div class="card-header">
          <h3 class="card-title"><i class="bi bi-megaphone"></i> Published Announcements</h3>
        </div>
        <div class="card-body p-0">
          <div class="table-wrapper">
            <table class="rms-table" id="annTable">
              <thead>
                <tr><th>#</th><th>Title</th><th>Type</th><th>Target</th><th>Posted</th><th>Expires</th><th class="col-actions">Actions</th></tr>
              </thead>
              <tbody>
                <?php
                $targetLabels = [
                  'all_tenants' => 'All Tenants',
                  'all_caretakers' => 'All Caretakers',
                  'specific_apartment' => 'Specific Apartment',
                  'specific_tenant' => 'Specific Tenant',
                ];
                $tc = ['Urgent'=>'badge-overdue','General'=>'badge-info','Information'=>'badge-active'];
                foreach($announcements as $a): ?>
                <tr>
                  <td><?= (int)$a['id'] ?></td>
                  <td class="fw-bold"><?= htmlspecialchars($a['title']) ?></td>
                  <td><span class="badge <?= $tc[$a['type']] ?? 'badge-info' ?>"><?= htmlspecialchars($a['type']) ?></span></td>
                  <td><i class="bi bi-people fs-sm text-muted"></i>
                    <?= htmlspecialchars($a['target'] === 'specific_apartment' && $a['apartment_name'] ? $a['apartment_name'] : ($targetLabels[$a['target']] ?? $a['target'])) ?>
                  </td>
                  <td><?= formatDate($a['created_at']) ?></td>
                  <td><?= $a['expires_at'] ? formatDate($a['expires_at']) : '—' ?></td>
                  <td class="col-actions">
                    <div class="d-flex gap-2" style="justify-content:flex-end">
                      <button class="btn btn-outline btn-sm btn-icon" title="Edit" data-modal-open="postAnnouncementModal"><i class="bi bi-pencil"></i></button>
                      <button class="btn btn-danger btn-sm btn-icon" title="Delete" onclick="confirmAction('Delete this announcement?')"><i class="bi bi-trash"></i></button>
                    </div>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>


<div class="modal-overlay" id="postAnnouncementModal">
  <div class="modal modal-lg">
    <div class="modal-header">
      <h3><i class="bi bi-megaphone-fill"></i> Post Announcement</h3>
      <button class="modal-close" data-modal-close><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="modal-body">
      <form action="../includes/save_announcement.php" method="POST" data-validate>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(rmsCsrfToken()) ?>">
        <div class="form-group">
          <label class="form-label">Title <span class="required">*</span></label>
          <input type="text" name="title" class="form-control" placeholder="Announcement title" required>
          <span class="form-error" style="display:none"><i class="bi bi-exclamation-circle"></i> Required</span>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Type</label>
            <select name="type" class="form-control">
              <option>General</option>
              <option>Urgent</option>
              <option>Information</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Target Audience <span class="required">*</span></label>
            <select name="target" class="form-control" required>
              <option value="all_tenants">All Tenants</option>
              <option value="all_caretakers">All Caretakers</option>
              <option value="specific_apartment">Specific Apartment</option>
              <option value="specific_tenant">Specific Tenant</option>
            </select>
          </div>
        </div>
        <div class="form-group" id="apartmentSelect" style="display:none">
          <label class="form-label">Select Apartment</label>
          <select name="apartment_id" class="form-control">
            <option value="">-- Select --</option>
            <?php foreach ($apartments as $apartment): ?>
            <option value="<?= (int)$apartment['id'] ?>"><?= htmlspecialchars($apartment['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group" id="tenantSelect" style="display:none">
          <label class="form-label">Select Tenant</label>
          <select name="tenant_id" class="form-control">
            <option value="">-- Select --</option>
            <?php foreach ($tenants as $tenant): ?>
            <option value="<?= (int)$tenant['id'] ?>">
              <?= htmlspecialchars($tenant['full_name']) ?> — <?= htmlspecialchars($tenant['email']) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Message <span class="required">*</span></label>
          <textarea name="body" class="form-control" rows="5" placeholder="Write your announcement…" required></textarea>
          <span class="form-error" style="display:none"><i class="bi bi-exclamation-circle"></i> Required</span>
        </div>
        <div class="form-group">
          <label class="form-label">Expires On (optional)</label>
          <input type="date" name="expires_at" class="form-control">
          <div class="form-hint">Announcement will be hidden from users after this date.</div>
        </div>
        <div class="modal-footer" style="padding:0;margin-top:8px;">
          <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-send"></i> Post Announcement
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="../assets/js/main.js"></script>
<script>
document.querySelector('[name="target"]').addEventListener('change', function() {
  document.getElementById('apartmentSelect').style.display =
    this.value === 'specific_apartment' ? 'block' : 'none';
  document.getElementById('tenantSelect').style.display =
    this.value === 'specific_tenant' ? 'block' : 'none';
});
</script>
</body>
</html>
