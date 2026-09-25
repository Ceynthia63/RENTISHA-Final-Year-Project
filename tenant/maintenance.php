<?php
session_start();
require_once '../includes/auth_check.php';
require_once '../includes/db_helpers.php';
requireActiveTenant();

$pageTitle  = 'Maintenance';
$activePage = 'maintenance';

$tenantId = (int)$_SESSION['user_id'];
$tenant   = getTenantById($tenantId);
$requests = getMaintenanceRequests(['tenant_id' => $tenantId]);

$active   = array_values(array_filter($requests, fn($r) => in_array($r['status'],['Pending','In Progress'])));
$resolved = array_values(array_filter($requests, fn($r) => $r['status']==='Resolved'));

$success = $_GET['success'] ?? '';
$error   = $_GET['error']   ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Maintenance — Rentisha</title>
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
        <div><h1>Maintenance Requests</h1><p>Report and track repair issues in your unit</p></div>
        <button class="btn btn-primary" data-modal-open="newMaintModal">
          <i class="bi bi-plus-lg"></i> New Request
        </button>
      </div>

      <!-- Tabs -->
      <div class="tabs" role="tablist">
        <div class="tab-item active" data-tab="tm-active" role="tab">
          <i class="bi bi-hourglass-split"></i> Active
          <?php if (count($active)): ?>
          <span style="background:var(--danger);color:#fff;font-size:.62rem;padding:1px 6px;border-radius:10px;"><?= count($active) ?></span>
          <?php endif; ?>
        </div>
        <div class="tab-item" data-tab="tm-resolved" role="tab"><i class="bi bi-check-circle"></i> Resolved (<?= count($resolved) ?>)</div>
        <div class="tab-item" data-tab="tm-all" role="tab"><i class="bi bi-list-ul"></i> All (<?= count($requests) ?>)</div>
      </div>

      <!-- Active -->
      <div id="tm-active" class="tab-panel active">
        <?php if (empty($active)): ?>
        <div style="text-align:center;padding:40px;color:var(--text-muted);">
          <i class="bi bi-check2-all" style="font-size:2rem;display:block;margin-bottom:8px;color:#7ED321;"></i>
          No open maintenance requests. All is well!
          <br><a href="#" data-modal-open="newMaintModal" style="margin-top:8px;display:inline-block;">Submit a new request</a>.
        </div>
        <?php else: ?>
          <?php foreach ($active as $req): ?>
          <div class="maintenance-card">
            <div class="mc-header">
              <div>
                <span class="mc-title"><?= htmlspecialchars($req['title']) ?></span>
                <span class="badge badge-info" style="margin-left:6px;font-size:.7rem;"><?= htmlspecialchars($req['category']) ?></span>
                <?php if (in_array($req['priority'],['Urgent','Emergency'])): ?>
                <span class="badge badge-overdue" style="margin-left:4px;font-size:.7rem;"><?= $req['priority'] ?></span>
                <?php endif; ?>
              </div>
              <span class="badge <?= statusBadgeClass($req['status']) ?>"><?= $req['status'] ?></span>
            </div>
            <div class="mc-desc"><?= htmlspecialchars(mb_substr($req['description'],0,150)) ?>…</div>
            <div class="mc-meta">
              <span><i class="bi bi-calendar3"></i> Reported: <?= formatDate($req['created_at']) ?></span>
              <span><i class="bi bi-door-open"></i> <?= htmlspecialchars($req['unit_number'] ?? '—') ?></span>
            </div>
            <?php if ($req['caretaker_notes']): ?>
            <div style="margin-top:8px;padding:8px 12px;background:var(--primary-pale);border-radius:6px;font-size:.8rem;">
              <i class="bi bi-person-badge" style="color:var(--primary);"></i>
              <strong>Caretaker:</strong> <?= htmlspecialchars($req['caretaker_notes']) ?>
            </div>
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <!-- Resolved -->
      <div id="tm-resolved" class="tab-panel">
        <?php if (empty($resolved)): ?>
        <div style="text-align:center;padding:32px;color:var(--text-muted);">No resolved requests yet.</div>
        <?php else: ?>
          <?php foreach ($resolved as $req): ?>
          <div class="maintenance-card" style="opacity:.88;">
            <div class="mc-header">
              <div>
                <span class="mc-title"><?= htmlspecialchars($req['title']) ?></span>
                <span class="badge badge-info" style="margin-left:6px;font-size:.7rem;"><?= htmlspecialchars($req['category']) ?></span>
              </div>
              <span class="badge badge-resolved">Resolved</span>
            </div>
            <div class="mc-meta">
              <span><i class="bi bi-calendar3"></i> Reported: <?= formatDate($req['created_at']) ?></span>
              <?php if ($req['resolved_at']): ?>
              <span><i class="bi bi-calendar-check"></i> Resolved: <?= formatDate($req['resolved_at']) ?></span>
              <?php endif; ?>
            </div>
            <?php if ($req['caretaker_notes']): ?>
            <div class="mc-desc" style="margin-top:6px;">
              <i class="bi bi-check-circle-fill" style="color:#7ED321;"></i> <?= htmlspecialchars($req['caretaker_notes']) ?>
            </div>
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <!-- All -->
      <div id="tm-all" class="tab-panel">
        <div class="card">
          <div class="card-body p-0">
            <div class="table-wrapper">
              <table class="rms-table">
                <thead><tr><th>Issue</th><th>Category</th><th>Priority</th><th>Reported</th><th>Status</th></tr></thead>
                <tbody>
                  <?php if (empty($requests)): ?>
                  <tr><td colspan="5" style="text-align:center;padding:24px;color:var(--text-muted);">No requests.</td></tr>
                  <?php else: ?>
                    <?php foreach ($requests as $r): ?>
                    <tr>
                      <td class="fw-bold"><?= htmlspecialchars($r['title']) ?></td>
                      <td><span class="badge badge-info"><?= htmlspecialchars($r['category']) ?></span></td>
                      <td><?= $r['priority'] ?></td>
                      <td><?= formatDate($r['created_at']) ?></td>
                      <td><span class="badge <?= statusBadgeClass($r['status']) ?>"><?= $r['status'] ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

<!-- New Maintenance Request Modal -->
<div class="modal-overlay" id="newMaintModal">
  <div class="modal modal-lg">
    <div class="modal-header">
      <h3><i class="bi bi-wrench-adjustable"></i> Submit Maintenance Request</h3>
      <button class="modal-close" data-modal-close><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="modal-body">
      <form action="../includes/save_maintenance.php" method="POST" enctype="multipart/form-data" data-validate>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(rmsCsrfToken()) ?>">
        <input type="hidden" name="tenant_id" value="<?= $tenantId ?>">
        <input type="hidden" name="unit_id"   value="<?= $tenant['unit_id'] ?? '' ?>">

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Category <span class="required">*</span></label>
            <select name="category" class="form-control" required>
              <option value="">— Select —</option>
              <?php foreach (['Plumbing','Electrical','Structural / Building','Appliances','Security / Lock','Pest Control','Cleaning / Sanitation','Other'] as $cat): ?>
              <option value="<?= $cat ?>"><?= $cat ?></option>
              <?php endforeach; ?>
            </select>
            <span class="form-error" style="display:none"><i class="bi bi-exclamation-circle"></i> Required</span>
          </div>
          <div class="form-group">
            <label class="form-label">Priority</label>
            <select name="priority" class="form-control">
              <option value="Normal">Normal</option>
              <option value="Urgent">Urgent</option>
              <option value="Emergency">Emergency</option>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Title / Short Description <span class="required">*</span></label>
          <input type="text" name="title" class="form-control" placeholder="e.g. Leaking pipe under kitchen sink" required>
          <span class="form-error" style="display:none"><i class="bi bi-exclamation-circle"></i> Required</span>
        </div>
        <div class="form-group">
          <label class="form-label">Full Description <span class="required">*</span></label>
          <textarea name="description" class="form-control" rows="4"
            placeholder="Where exactly? When did it start? How severe?" required></textarea>
          <span class="form-error" style="display:none"><i class="bi bi-exclamation-circle"></i> Required</span>
        </div>
        <div class="form-group">
          <label class="form-label"><i class="bi bi-camera"></i> Attach Photos (optional)</label>
          <input type="file" name="photos[]" class="form-control" accept="image/*" multiple style="padding:6px;">
          <div class="form-hint">Up to 3 photos, JPEG/PNG, max 5 MB each.</div>
        </div>
        <div class="form-group">
          <label class="form-label">Preferred Visit Time</label>
          <select name="preferred_time" class="form-control">
            <option value="">— No preference —</option>
            <option>Morning (8 AM – 12 PM)</option>
            <option>Afternoon (12 PM – 5 PM)</option>
            <option>Evening (5 PM – 7 PM)</option>
            <option>Anytime</option>
          </select>
        </div>
        <div class="modal-footer" style="padding:0;margin-top:8px;">
          <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
          <button type="submit" class="btn btn-primary"><i class="bi bi-send"></i> Submit Request</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="../assets/js/main.js"></script>
<script>
<?php if (isset($_GET['action']) && $_GET['action']==='new'): ?>
openModal('newMaintModal');
<?php endif; ?>
</script>
</body>
</html>
