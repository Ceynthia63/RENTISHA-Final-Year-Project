<?php
session_start();
require_once '../includes/auth_check.php';
require_once '../includes/db_helpers.php';
requireRole('caretaker');

$pageTitle  = 'Maintenance';
$activePage = 'maintenance';

$aptId = (int)($_SESSION['apartment_id'] ?? 0);
if (!$aptId) { header('Location: dashboard.php'); exit; }

$allMaint  = getMaintenanceRequests(['apartment_id' => $aptId]);
$pending   = array_values(array_filter($allMaint, fn($m) => $m['status'] === 'Pending'));
$inProgress= array_values(array_filter($allMaint, fn($m) => $m['status'] === 'In Progress'));
$resolved  = array_values(array_filter($allMaint, fn($m) => $m['status'] === 'Resolved'));

$success = $_GET['success'] ?? '';
$error   = $_GET['error']   ?? '';

$updateId = (int)($_GET['update'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Maintenance — Rentisha Caretaker</title>
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
          <h1>Maintenance Requests</h1>
          <p><?= htmlspecialchars($_SESSION['apartment'] ?? '') ?></p>
        </div>
      </div>

      <!-- Stats -->
      <div class="stats-grid" style="grid-template-columns:repeat(auto-fill,minmax(140px,1fr));margin-bottom:18px;">
        <div class="stat-card red">
          <div class="stat-icon"><i class="bi bi-hourglass-split"></i></div>
          <div class="stat-value" data-count="<?= count($pending) ?>"><?= count($pending) ?></div>
          <div class="stat-label">Pending</div>
        </div>
        <div class="stat-card orange">
          <div class="stat-icon"><i class="bi bi-play-circle"></i></div>
          <div class="stat-value" data-count="<?= count($inProgress) ?>"><?= count($inProgress) ?></div>
          <div class="stat-label">In Progress</div>
        </div>
        <div class="stat-card green">
          <div class="stat-icon"><i class="bi bi-check-circle"></i></div>
          <div class="stat-value" data-count="<?= count($resolved) ?>"><?= count($resolved) ?></div>
          <div class="stat-label">Resolved</div>
        </div>
        <div class="stat-card blue">
          <div class="stat-icon"><i class="bi bi-list-task"></i></div>
          <div class="stat-value" data-count="<?= count($allMaint) ?>"><?= count($allMaint) ?></div>
          <div class="stat-label">Total</div>
        </div>
      </div>

      <!-- Tabs -->
      <div class="tabs" role="tablist">
        <div class="tab-item active" data-tab="mt-pending" role="tab">
          <i class="bi bi-hourglass-split"></i> Pending
          <?php if (count($pending)): ?>
          <span style="background:var(--danger);color:#fff;font-size:.62rem;padding:1px 6px;border-radius:10px;"><?= count($pending) ?></span>
          <?php endif; ?>
        </div>
        <div class="tab-item" data-tab="mt-progress" role="tab"><i class="bi bi-play-circle"></i> In Progress</div>
        <div class="tab-item" data-tab="mt-resolved" role="tab"><i class="bi bi-check-circle"></i> Resolved</div>
        <div class="tab-item" data-tab="mt-all" role="tab"><i class="bi bi-list-ul"></i> All</div>
      </div>

      <!-- Pending -->
      <div id="mt-pending" class="tab-panel active">
        <?php if (empty($pending)): ?>
        <div style="text-align:center;padding:40px;color:var(--text-muted);">
          <i class="bi bi-check2-all" style="font-size:2rem;display:block;margin-bottom:8px;color:#7ED321;"></i>
          No pending requests. All clear!
        </div>
        <?php else: ?>
          <?php foreach ($pending as $req): ?>
          <?php include __DIR__ . '/../includes/_maintenance_card.php'; ?>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <!-- In Progress -->
      <div id="mt-progress" class="tab-panel">
        <?php if (empty($inProgress)): ?>
        <div style="text-align:center;padding:40px;color:var(--text-muted);">No requests in progress.</div>
        <?php else: ?>
          <?php foreach ($inProgress as $req): ?>
          <?php include __DIR__ . '/../includes/_maintenance_card.php'; ?>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <!-- Resolved -->
      <div id="mt-resolved" class="tab-panel">
        <?php if (empty($resolved)): ?>
        <div style="text-align:center;padding:40px;color:var(--text-muted);">No resolved requests yet.</div>
        <?php else: ?>
          <?php foreach ($resolved as $req): ?>
          <div class="maintenance-card" style="opacity:.85;">
            <div class="mc-header">
              <div>
                <span class="mc-title"><?= htmlspecialchars($req['title']) ?></span>
                <span class="badge badge-info" style="margin-left:6px;font-size:.7rem;"><?= htmlspecialchars($req['category']) ?></span>
              </div>
              <span class="badge badge-resolved">Resolved</span>
            </div>
            <div class="mc-meta">
              <span><i class="bi bi-person"></i> <?= htmlspecialchars($req['tenant_name']) ?></span>
              <span><i class="bi bi-door-open"></i> <?= htmlspecialchars($req['unit_number'] ?? '—') ?></span>
              <span><i class="bi bi-calendar-check"></i> Resolved: <?= formatDate($req['resolved_at'] ?? $req['updated_at']) ?></span>
            </div>
            <?php if ($req['caretaker_notes']): ?>
            <div class="mc-desc"><i class="bi bi-check-circle-fill" style="color:#7ED321;"></i> <?= htmlspecialchars($req['caretaker_notes']) ?></div>
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <!-- All -->
      <div id="mt-all" class="tab-panel">
        <div class="filter-bar">
          <div class="input-group search-box">
            <i class="bi bi-search input-icon"></i>
            <input type="search" class="form-control" placeholder="Search…" data-search-table="maintAllTable">
          </div>
        </div>
        <div class="card">
          <div class="card-body p-0">
            <div class="table-wrapper">
              <table class="rms-table" id="maintAllTable">
                <thead><tr><th>Issue</th><th>Tenant</th><th>Unit</th><th>Category</th><th>Priority</th><th>Reported</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                  <?php if (empty($allMaint)): ?>
                  <tr><td colspan="8" style="text-align:center;padding:24px;color:var(--text-muted);">No maintenance requests.</td></tr>
                  <?php else: ?>
                    <?php foreach ($allMaint as $r): ?>
                    <tr>
                      <td class="fw-bold"><?= htmlspecialchars($r['title']) ?></td>
                      <td><?= htmlspecialchars($r['tenant_name']) ?></td>
                      <td><span class="badge badge-info"><?= htmlspecialchars($r['unit_number'] ?? '—') ?></span></td>
                      <td><?= htmlspecialchars($r['category']) ?></td>
                      <td><span class="badge <?= $r['priority']==='Emergency'||$r['priority']==='Urgent'?'badge-overdue':'badge-info' ?>"><?= $r['priority'] ?></span></td>
                      <td><?= formatDate($r['created_at']) ?></td>
                      <td><span class="badge <?= statusBadgeClass($r['status']) ?>"><?= $r['status'] ?></span></td>
                      <td>
                        <button class="btn btn-outline btn-sm" onclick="openUpdateModal(<?= htmlspecialchars(json_encode($r)) ?>)">
                          <i class="bi bi-pencil"></i> Update
                        </button>
                      </td>
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

<!-- Update Maintenance Modal -->
<div class="modal-overlay" id="updateMaintModal">
  <div class="modal modal-lg">
    <div class="modal-header">
      <h3><i class="bi bi-wrench-adjustable"></i> Update Maintenance Request</h3>
      <button class="modal-close" data-modal-close><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="modal-body">
      <div id="maintIssueTitle" style="font-weight:700;font-size:1rem;margin-bottom:4px;"></div>
      <div id="maintIssueMeta" style="font-size:.78rem;color:var(--text-muted);margin-bottom:16px;"></div>
      <form action="../includes/save_maintenance.php" method="POST" data-validate>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(rmsCsrfToken()) ?>">
        <input type="hidden" name="edit_id" id="maintEditId" value="">
        <div class="form-group">
          <label class="form-label">New Status <span class="required">*</span></label>
          <select name="new_status" id="maintNewStatus" class="form-control" required>
            <option value="Pending">Pending</option>
            <option value="In Progress">In Progress</option>
            <option value="Resolved">Resolved</option>
            <option value="Cancelled">Cancelled</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Action Taken / Notes <span class="required">*</span></label>
          <textarea name="notes" class="form-control" rows="4"
            placeholder="Describe what was done or what is planned…" required></textarea>
          <span class="form-error" style="display:none"><i class="bi bi-exclamation-circle"></i> Required</span>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Work Date</label>
            <input type="date" name="work_date" class="form-control" value="<?= date('Y-m-d') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Cost Incurred (KES)</label>
            <input type="number" name="cost" class="form-control" placeholder="e.g. 2500" min="0">
          </div>
        </div>
        <div class="modal-footer" style="padding:0;margin-top:8px;">
          <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
          <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Save Update</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="../assets/js/main.js"></script>
<script>
function openUpdateModal(r) {
  document.getElementById('maintEditId').value       = r.id;
  document.getElementById('maintNewStatus').value    = r.status;
  document.getElementById('maintIssueTitle').textContent = r.title;
  document.getElementById('maintIssueMeta').textContent  =
    'Tenant: ' + r.tenant_name + ' | Unit: ' + (r.unit_number||'—') +
    ' | Reported: ' + (r.created_at||'').substr(0,10);
  openModal('updateMaintModal');
}

<?php if ($updateId): ?>
const req = <?php
  $found = array_values(array_filter($allMaint, fn($m) => $m['id'] == $updateId));
  echo !empty($found) ? json_encode($found[0]) : 'null';
?>;
if (req) openUpdateModal(req);
<?php endif; ?>
</script>
</body>
</html>
