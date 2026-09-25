<?php
session_start();
require_once '../includes/auth_check.php';
require_once '../includes/db_helpers.php';
requireRole('caretaker');

$pageTitle  = 'My Tenants';
$activePage = 'tenants';

$aptId = (int)($_SESSION['apartment_id'] ?? 0);
$assignedApartment = getCaretakerApartment((int)($_SESSION['user_id'] ?? 0));
if ($assignedApartment) {
    $aptId = (int)$assignedApartment['id'];
    $_SESSION['apartment_id'] = $aptId;
    $_SESSION['apartment'] = $assignedApartment['name'];
}
if (!$aptId) { header('Location: dashboard.php'); exit; }

$pdo = getDB();

$activeTab = $_GET['tab'] ?? 'list';

$assignedTenants = getTenants('active_or_prospective', $aptId);

$pendingTenants = [];
try {
    $stmt = $pdo->prepare(
        "SELECT u.id, u.full_name, u.email, u.phone, u.status, u.created_at,
               a.name AS preferred_apartment_name,
               u.notes AS tenant_notes
         FROM users u
         JOIN apartments a ON a.id = u.preferred_apartment_id
         WHERE u.role = 'tenant'
           AND u.status = 'pending_approval'
           AND u.preferred_apartment_id = :apt_id
         ORDER BY u.created_at DESC"
    );
    $stmt->execute([':apt_id' => $aptId]);
    $pendingTenants = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log('Pending tenants load: ' . $e->getMessage());
    $pendingTenants = [];
}

if (!is_array($pendingTenants)) {
    $pendingTenants = [];
}
$pendingCount = count($pendingTenants);

$unassignedTenants = [];
try {
    $checkCol = $pdo->query("SHOW COLUMNS FROM users LIKE 'preferred_apartment_id'")->fetchColumn();
    if ($checkCol) {
        $stmt = $pdo->prepare(
            "SELECT u.id, u.full_name, u.email, u.phone, u.status, u.created_at,
                    u.notes AS tenant_notes
             FROM users u
             LEFT JOIN tenant_units tu ON tu.tenant_id = u.id AND tu.status = 'active'
             WHERE u.role = 'tenant'
               AND u.status IN ('active','prospective')
               AND u.preferred_apartment_id = :apt_id
               AND tu.id IS NULL
             ORDER BY u.created_at DESC"
        );
        $stmt->execute([':apt_id' => $aptId]);
        $unassignedTenants = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    error_log('Unassigned tenants load: ' . $e->getMessage());
}

$vacantUnits = getVacantUnits($aptId);

$movedOutTenants = getTenants('moved_out', $aptId);

$viewTenant = null;
if (!empty($_GET['view'])) {
    $viewTenant = getTenantById((int)$_GET['view']);
    if ($viewTenant && (int)($viewTenant['apartment_id'] ?? 0) !== $aptId) {
        $checkPending = $pdo->prepare(
            "SELECT id FROM users WHERE id=:id AND preferred_apartment_id=:apt AND role='tenant'"
        );
        $checkPending->execute([':id' => (int)$_GET['view'], ':apt' => $aptId]);
        if (!$checkPending->fetchColumn()) {
            header('Location: tenants.php'); exit;
        }
        $viewTenant = getTenantById((int)$_GET['view']);
    }
    if ($viewTenant) {
        $yearly      = getTenantYearlyPayments($viewTenant['id'], (int)date('Y'));
        $outstanding = getTenantOutstanding($viewTenant['id']);
        $maint       = getMaintenanceRequests(['tenant_id' => $viewTenant['id']]);
    }
}

$success = $_GET['success'] ?? '';
$error   = $_GET['error']   ?? '';
$aptName = $_SESSION['apartment'] ?? 'My Apartment';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Tenants — Rentisha Caretaker</title>
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

      <?php if ($viewTenant): ?>
      <!-- ══════════════════════════════════ TENANT DETAIL ══ -->
      <div class="page-header">
        <div>
          <h1><?= htmlspecialchars($viewTenant['full_name']) ?></h1>
          <p>
            <span class="badge <?= statusBadgeClass($viewTenant['status']) ?>"><?= ucfirst($viewTenant['status']) ?></span>
            &nbsp;
            <?= $viewTenant['unit_number'] ? 'Unit ' . htmlspecialchars($viewTenant['unit_number']) : 'No unit assigned yet' ?>
          </p>
        </div>
        <div class="d-flex gap-2">
          <a href="tenants.php" class="btn btn-outline btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
          <?php if ($viewTenant['status'] === 'active' && $viewTenant['unit_id']): ?>
          <button class="btn btn-warning btn-sm" data-modal-open="moveOutModal">
            <i class="bi bi-box-arrow-right"></i> Move Out
          </button>
          <?php endif; ?>
          <?php if ($viewTenant['status'] === 'active' && !$viewTenant['unit_id']): ?>
          <button class="btn btn-primary btn-sm"
                  onclick="openAssignModal(<?= $viewTenant['id'] ?>, '<?= htmlspecialchars(addslashes($viewTenant['full_name'])) ?>')">
            <i class="bi bi-house-door"></i> Assign Unit
          </button>
          <?php endif; ?>
        </div>
      </div>

      <div class="grid-2 mb-4">
        <div class="card">
          <div class="card-header"><h3 class="card-title"><i class="bi bi-person"></i> Tenant Info</h3></div>
          <div class="card-body">
            <?php $rows = [
              ['Phone',        $viewTenant['phone'],                 'bi-phone'],
              ['Email',        $viewTenant['email'],                 'bi-envelope'],
              ['Unit',         $viewTenant['unit_number'] ?? '—',    'bi-door-open'],
              ['Move-in',      formatDate($viewTenant['move_in_date']), 'bi-calendar-check'],
              ['Monthly Rent', $viewTenant['unit_rent'] ? formatKES($viewTenant['unit_rent']) : '—', 'bi-cash'],
              ['Outstanding',  $outstanding['total'] > 0 ? formatKES($outstanding['total']) : 'None', 'bi-exclamation-circle'],
              ['Emergency',    $viewTenant['emergency_contact'] ?? '—', 'bi-telephone-plus'],
            ]; ?>
            <table style="width:100%;border-collapse:collapse;font-size:.85rem;">
              <?php foreach ($rows as [$l,$v,$ic]): ?>
              <tr style="border-bottom:1px solid var(--border);">
                <td style="padding:7px 0;color:var(--text-muted);width:40%;">
                  <i class="bi <?= $ic ?>" style="margin-right:5px;"></i><?= $l ?>
                </td>
                <td style="padding:7px 0;font-weight:600;"><?= htmlspecialchars((string)($v ?? '—')) ?></td>
              </tr>
              <?php endforeach; ?>
            </table>
          </div>
        </div>

        <div>
          <?php if ($outstanding['total'] > 0): ?>
          <div class="alert alert-danger mb-4">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <div><strong>Outstanding: <?= formatKES($outstanding['total']) ?></strong></div>
          </div>
          <?php endif; ?>
          <div class="card mb-4">
            <div class="card-header"><h3 class="card-title"><i class="bi bi-calendar3"></i> <?= date('Y') ?> Payments</h3></div>
            <div class="card-body">
              <div class="months-grid">
                <?php foreach ($yearly as $month => $p): ?>
                <div class="month-cell <?= $p['status'] ? strtolower($p['status']) : '' ?>">
                  <div class="month-name"><?= substr($month,0,3) ?></div>
                  <div class="month-amount" style="font-size:.72rem;"><?= $p['amount'] ? formatKES($p['amount']) : '—' ?></div>
                  <div class="month-status" style="margin-top:3px;">
                    <?php if ($p['status'] === 'Paid'): ?>
                      <span class="badge badge-paid" style="font-size:.58rem;">Paid</span>
                    <?php elseif ($p['status'] === 'Overdue'): ?>
                      <span class="badge badge-overdue" style="font-size:.58rem;">Late</span>
                    <?php elseif ($p['status'] === 'Pending'): ?>
                      <span class="badge badge-pending" style="font-size:.58rem;">Due</span>
                    <?php else: ?>
                      <span style="color:var(--text-muted);font-size:.65rem;">—</span>
                    <?php endif; ?>
                  </div>
                </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Move Out Modal -->
      <div class="modal-overlay" id="moveOutModal">
        <div class="modal">
          <div class="modal-header">
            <h3><i class="bi bi-box-arrow-right"></i> Process Move-Out</h3>
            <button class="modal-close" data-modal-close><i class="bi bi-x-lg"></i></button>
          </div>
          <div class="modal-body">
            <div class="alert alert-warning">
              <i class="bi bi-exclamation-triangle-fill"></i>
              <div>Moving out <strong><?= htmlspecialchars($viewTenant['full_name']) ?></strong> from Unit <strong><?= htmlspecialchars($viewTenant['unit_number'] ?? '') ?></strong>. History retained.</div>
            </div>
            <form action="../includes/move_out.php" method="POST">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(rmsCsrfToken()) ?>">
              <input type="hidden" name="tenant_id" value="<?= $viewTenant['id'] ?>">
              <div class="form-group">
                <label class="form-label">Move-out Date <span class="required">*</span></label>
                <input type="date" name="move_out_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
              </div>
              <div class="form-group">
                <label class="form-label">Reason</label>
                <select name="reason" class="form-control">
                  <option value="">—</option>
                  <option>End of lease</option><option>Tenant request</option>
                  <option>Eviction</option><option>Transfer</option><option>Other</option>
                </select>
              </div>
              <div class="form-group">
                <label class="form-label">Notes</label>
                <textarea name="notes" class="form-control" rows="2"></textarea>
              </div>
              <div class="modal-footer" style="padding:0;margin-top:8px;">
                <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-warning"><i class="bi bi-box-arrow-right"></i> Confirm</button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <?php else: ?>
      <!-- ══════════════════════════════════ TENANTS LIST ══ -->
      <div class="page-header">
        <div>
          <h1>Tenants — <?= htmlspecialchars($aptName) ?></h1>
          <p>Approve applications and manage tenants for your apartment</p>
        </div>
        <button class="btn btn-primary" data-modal-open="addTenantModal">
          <i class="bi bi-person-plus-fill"></i> Add Tenant
        </button>
      </div>

      <!-- Tab navigation -->
      <div style="display:flex;gap:0;border-bottom:2px solid var(--border);margin-bottom:20px;">
        <?php
          $tabs = [
            'list'    => ['label' => 'Active Tenants',     'icon' => 'bi-people-fill',      'count' => count($assignedTenants)],
            'pending' => ['label' => 'Pending Approval',   'icon' => 'bi-hourglass-split',  'count' => $pendingCount],
            'unassigned' => ['label' => 'Awaiting Unit',   'icon' => 'bi-person-exclamation', 'count' => count($unassignedTenants)],
          ];
          foreach ($tabs as $key => $t):
            $isActive = ($activeTab === $key);
        ?>
        <a href="tenants.php?tab=<?= $key ?>"
           style="display:flex;align-items:center;gap:7px;padding:10px 18px;
                  text-decoration:none;font-size:.85rem;font-weight:600;border-bottom:2px solid transparent;
                  margin-bottom:-2px;transition:all .2s;
                  <?= $isActive
                      ? 'color:var(--primary);border-bottom-color:var(--primary);'
                      : 'color:var(--text-muted);' ?>">
          <i class="bi <?= $t['icon'] ?>"></i>
          <?= $t['label'] ?>
          <?php if ($t['count'] > 0): ?>
          <span style="background:<?= ($key==='pending' && $t['count']>0) ? '#d29922' : 'var(--primary)' ?>;
                       color:#fff;font-size:.68rem;padding:1px 7px;border-radius:10px;font-weight:700;">
            <?= $t['count'] ?>
          </span>
          <?php endif; ?>
        </a>
        <?php endforeach; ?>
      </div>

      <?php if ($activeTab === 'pending'): ?>
      <!-- ══════ PENDING APPROVALS TAB ══════ -->
      <?php if (empty($pendingTenants)): ?>
      <div class="card">
        <div class="card-body" style="text-align:center;padding:48px;color:var(--text-muted);">
          <i class="bi bi-hourglass" style="font-size:2.5rem;display:block;margin-bottom:8px;"></i>
          No pending tenant applications for your apartment.
        </div>
      </div>
      <?php else: ?>
      <div class="card">
        <div class="card-header">
          <h3 class="card-title"><i class="bi bi-hourglass-split"></i> Tenant Applications Awaiting Approval (<?= $pendingCount ?>)</h3>
        </div>
        <div class="card-body p-0">
          <div class="table-wrapper">
            <table class="rms-table">
              <thead>
                <tr><th>#</th><th>Applicant</th><th>Phone</th><th>Email</th><th>Apartment</th><th>Notes</th><th>Applied</th><th class="col-actions">Actions</th></tr>
              </thead>
              <tbody>
                <?php foreach ($pendingTenants as $i => $pt): ?>
                <tr>
                  <td><?= $i + 1 ?></td>
                  <td>
                    <div style="font-weight:600;"><?= htmlspecialchars($pt['full_name']) ?></div>
                  </td>
                  <td><?= htmlspecialchars($pt['phone'] ?? '—') ?></td>
                  <td style="font-size:.82rem;color:var(--text-muted);"><?= htmlspecialchars($pt['email']) ?></td>
                  <td><span class="badge badge-primary"><?= htmlspecialchars($pt['preferred_apartment_name']) ?></span></td>
                  <td style="max-width:180px;font-size:.8rem;color:var(--text-muted);">
                    <?= htmlspecialchars($pt['tenant_notes'] ?? '—') ?>
                  </td>
                  <td style="font-size:.82rem;"><?= formatDate($pt['created_at']) ?></td>
                  <td class="col-actions">
                    <div class="d-flex gap-2" style="justify-content:flex-end;">
                      <!-- Approve -->
                      <form method="POST" action="../includes/save_tenant_updated.php"
                            onsubmit="return confirm('Approve <?= htmlspecialchars($pt['full_name']) ?>?')">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(rmsCsrfToken()) ?>">
                        <input type="hidden" name="action"    value="approve">
                        <input type="hidden" name="tenant_id" value="<?= $pt['id'] ?>">
                        <button class="btn btn-primary btn-sm" title="Approve">
                          <i class="bi bi-check-lg"></i> Approve
                        </button>
                      </form>
                      <!-- Reject -->
                      <button class="btn btn-danger btn-sm" title="Reject"
                        onclick="openRejectTenantModal(<?= $pt['id'] ?>, '<?= htmlspecialchars($pt['full_name']) ?>')">
                        <i class="bi bi-x-lg"></i> Reject
                      </button>
                    </div>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <?php elseif ($activeTab === 'unassigned'): ?>
      <!-- ══════ UNASSIGNED (APPROVED BUT NO UNIT) TAB ══════ -->
      <?php if (empty($unassignedTenants)): ?>
      <div class="card">
        <div class="card-body" style="text-align:center;padding:48px;color:var(--text-muted);">
          <i class="bi bi-person-check" style="font-size:2.5rem;display:block;margin-bottom:8px;"></i>
          All approved tenants have been assigned units.
        </div>
      </div>
      <?php else: ?>
      <div class="card">
        <div class="card-header" style="background:linear-gradient(135deg,#001A3D,#007BFF);color:#fff;">
          <h3 class="card-title" style="color:#fff;">
            <i class="bi bi-person-exclamation"></i>
            Approved Tenants Awaiting Unit Assignment (<?= count($unassignedTenants) ?>)
          </h3>
        </div>
        <div class="card-body p-0">
          <div class="table-wrapper">
            <table class="rms-table">
              <thead>
                <tr>
                  <th>Tenant</th>
                  <th>Phone</th>
                  <th>Registered</th>
                  <th>Notes</th>
                  <th class="col-actions">Action</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($unassignedTenants as $pt): ?>
                <tr style="background:#e6f2ff20;">
                  <td>
                    <div style="font-weight:700;color:var(--primary);"><?= htmlspecialchars($pt['full_name']) ?></div>
                    <div style="font-size:.72rem;color:var(--text-muted);"><?= htmlspecialchars($pt['email'] ?? '') ?></div>
                  </td>
                  <td><?= htmlspecialchars($pt['phone']) ?></td>
                  <td><?= formatDate($pt['created_at']) ?></td>
                  <td style="font-size:.78rem;color:var(--text-muted);max-width:150px;">
                    <?= htmlspecialchars(mb_substr($pt['tenant_notes'] ?? '', 0, 80)) ?>
                  </td>
                  <td class="col-actions">
                    <button class="btn btn-primary btn-sm"
                            onclick="openAssignModal(<?= $pt['id'] ?>, '<?= htmlspecialchars(addslashes($pt['full_name'])) ?>')">
                      <i class="bi bi-house-door-fill"></i> Assign Unit
                    </button>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <?php else: ?>
      <!-- ══════ ACTIVE TENANTS LIST (default) ══════ -->

      <!-- ── UNASSIGNED ALERT ─────────────────────────── -->
      <?php if (!empty($unassignedTenants)): ?>
      <div class="alert alert-info mb-4">
        <i class="bi bi-person-exclamation" style="font-size:1.1rem;"></i>
        <div>
          <strong><?= count($unassignedTenants) ?> approved tenant<?= count($unassignedTenants) > 1 ? 's are' : ' is' ?> waiting for unit assignment.</strong>
          <a href="tenants.php?tab=unassigned" style="color:#08CFFF;font-weight:600;">Assign units now →</a>
        </div>
      </div>
      <?php endif; ?>

      <!-- Stats -->
      <?php $cntOverdue = count(array_filter($assignedTenants, fn($t) => ($t['rent_status'] ?? '') === 'Overdue')); ?>
      <div class="stats-grid" style="grid-template-columns:repeat(auto-fill,minmax(140px,1fr));margin-bottom:18px;">
        <div class="stat-card green">
          <div class="stat-icon"><i class="bi bi-person-check"></i></div>
          <div class="stat-value" data-count="<?= count($assignedTenants) ?>"><?= count($assignedTenants) ?></div>
          <div class="stat-label">Active Tenants</div>
        </div>
        <div class="stat-card orange">
          <div class="stat-icon"><i class="bi bi-hourglass-split"></i></div>
          <div class="stat-value" data-count="<?= $pendingCount ?>"><?= $pendingCount ?></div>
          <div class="stat-label">Pending Approval</div>
        </div>
        <div class="stat-card blue">
          <div class="stat-icon"><i class="bi bi-person-exclamation"></i></div>
          <div class="stat-value" data-count="<?= count($unassignedTenants) ?>"><?= count($unassignedTenants) ?></div>
          <div class="stat-label">Awaiting Unit</div>
        </div>
        <div class="stat-card red">
          <div class="stat-icon"><i class="bi bi-exclamation-circle"></i></div>
          <div class="stat-value" data-count="<?= $cntOverdue ?>"><?= $cntOverdue ?></div>
          <div class="stat-label">Overdue Rent</div>
        </div>
      </div>

      <!-- Filter -->
      <div class="filter-bar">
        <div class="input-group search-box">
          <i class="bi bi-search input-icon"></i>
          <input type="search" class="form-control" placeholder="Search tenant, unit, phone…" data-search-table="ctTenantTable">
        </div>
      </div>

      <!-- Assigned tenants table -->
      <div class="card">
        <div class="card-header">
          <h3 class="card-title"><i class="bi bi-people-fill"></i> Assigned Tenants (<?= count($assignedTenants) ?>)</h3>
        </div>
        <div class="card-body p-0">
          <div class="table-wrapper">
            <table class="rms-table" id="ctTenantTable">
              <thead>
                <tr><th>#</th><th>Tenant</th><th>Phone</th><th>Unit</th><th>Rent</th><th>Move-in</th><th>Rent Status</th><th>Actions</th></tr>
              </thead>
              <tbody>
                <?php if (empty($assignedTenants)): ?>
                <tr><td colspan="8" style="text-align:center;padding:32px;color:var(--text-muted);">
                  No assigned tenants yet.
                  <?php if (!empty($pendingTenants)): ?>
                  Assign units to the <?= count($pendingTenants) ?> pending tenant<?= count($pendingTenants)>1?'s':'' ?> above.
                  <?php else: ?>
                  <a href="#" data-modal-open="addTenantModal">Add a tenant</a>.
                  <?php endif; ?>
                </td></tr>
                <?php else: ?>
                  <?php foreach ($assignedTenants as $i => $t): ?>
                  <tr>
                    <td><?= $i + 1 ?></td>
                    <td>
                      <a href="tenants.php?view=<?= $t['id'] ?>" style="font-weight:600;color:var(--primary);">
                        <?= htmlspecialchars($t['full_name']) ?>
                      </a>
                      <div style="font-size:.72rem;color:var(--text-muted);"><?= htmlspecialchars($t['email'] ?? '') ?></div>
                    </td>
                    <td><?= htmlspecialchars($t['phone'] ?? '—') ?></td>
                    <td>
                      <?= $t['unit_number'] ? '<span class="badge badge-info">'.htmlspecialchars($t['unit_number']).'</span>' : '—' ?>
                    </td>
                    <td class="fw-bold"><?= $t['unit_rent'] ? formatKES($t['unit_rent']) : '—' ?></td>
                    <td><?= formatDate($t['move_in_date']) ?></td>
                    <td>
                      <?php if ($t['rent_status']): ?>
                      <span class="badge <?= statusBadgeClass($t['rent_status']) ?>"><?= $t['rent_status'] ?></span>
                      <?php else: ?>
                      <span class="badge badge-pending">No Record</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <div class="d-flex gap-1">
                        <a href="tenants.php?view=<?= $t['id'] ?>" class="btn btn-secondary btn-sm btn-icon" title="View">
                          <i class="bi bi-eye"></i>
                        </a>
                        <?php if (!$t['unit_id']): ?>
                        <button class="btn btn-primary btn-sm btn-icon" title="Assign Unit"
                          onclick="openAssignModal(<?= $t['id'] ?>, '<?= htmlspecialchars(addslashes($t['full_name'])) ?>')">
                          <i class="bi bi-house-door"></i>
                        </button>
                        <?php endif; ?>
                        <?php if ($t['rent_status'] === 'Overdue' || $t['rent_status'] === 'Pending'): ?>
                        <button class="btn btn-warning btn-sm btn-icon" title="Send Reminder"
                          onclick="showToast('Reminder sent to <?= htmlspecialchars(addslashes($t['full_name'])) ?>','success')">
                          <i class="bi bi-send"></i>
                        </button>
                        <?php endif; ?>
                      </div>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Former tenants (collapsed) -->
      <?php if (!empty($movedOutTenants)): ?>
      <details style="margin-top:16px;">
        <summary style="cursor:pointer;font-weight:600;color:var(--text-muted);font-size:.875rem;padding:8px 0;">
          <i class="bi bi-clock-history"></i> Former Tenants (<?= count($movedOutTenants) ?>) — History
        </summary>
        <div class="card mt-3">
          <div class="card-body p-0">
            <div class="table-wrapper">
              <table class="rms-table">
                <thead><tr><th>Tenant</th><th>Unit</th><th>Move-in</th><th>Move-out</th><th>Status</th></tr></thead>
                <tbody>
                  <?php foreach ($movedOutTenants as $t): ?>
                  <tr style="opacity:.8;">
                    <td class="fw-bold"><?= htmlspecialchars($t['full_name']) ?></td>
                    <td><?= htmlspecialchars($t['unit_number'] ?? '—') ?></td>
                    <td><?= formatDate($t['move_in_date']) ?></td>
                    <td><?= formatDate($t['move_out_date'] ?? null) ?></td>
                    <td><span class="badge badge-moved-out">Moved Out</span></td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </details>
      <?php endif; ?>

      <?php endif; // end tab switch (pending / unassigned / list) ?>

      <?php endif; ?>

    </div>
  </div>
</div>

<!-- ── ASSIGN UNIT MODAL ───────────────────────────────────── -->
<div class="modal-overlay" id="assignUnitModal">
  <div class="modal modal-lg">
    <div class="modal-header">
      <h3><i class="bi bi-house-door-fill"></i> Assign Unit to Tenant</h3>
      <button class="modal-close" data-modal-close><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="modal-body">
      <div class="alert alert-info mb-4">
        <i class="bi bi-info-circle-fill"></i>
        <div>Assigning unit to: <strong id="assignTenantName"></strong></div>
      </div>
      <form action="../includes/move_in.php" method="POST" data-validate>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(rmsCsrfToken()) ?>">
        <input type="hidden" name="tenant_id" id="assignTenantId" value="">

        <div class="form-group">
          <label class="form-label">Select Vacant Unit <span class="required">*</span></label>
          <?php if (empty($vacantUnits)): ?>
          <div class="alert alert-warning" style="margin-top:4px;">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <div>No vacant units available in <?= htmlspecialchars($aptName) ?>. Contact Admin to add units.</div>
          </div>
          <?php else: ?>
          <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:8px;margin-top:6px;" id="unitCards">
            <?php foreach ($vacantUnits as $vu): ?>
            <label style="border:1.5px solid var(--border);border-radius:10px;padding:12px;cursor:pointer;transition:all .2s;display:block;"
                   onmouseover="this.style.borderColor='var(--primary)'"
                   onmouseout="this.style.borderColor='var(--border)'">
              <input type="radio" name="unit_id" value="<?= $vu['id'] ?>" required
                     style="margin-right:8px;accent-color:var(--primary);"
                     onchange="this.closest('#unitCards').querySelectorAll('label').forEach(l=>l.style.background='');this.closest('label').style.background='var(--primary-pale)'">
              <strong><?= htmlspecialchars($vu['unit_number']) ?></strong>
              <div style="font-size:.78rem;color:var(--text-muted);margin-top:3px;">
                <?= htmlspecialchars($vu['unit_type']) ?> · <?= formatKES($vu['monthly_rent']) ?>/mo
              </div>
            </label>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </div>

        <div class="form-group">
          <label class="form-label">Agreed Deposit (KES) <span class="required">*</span></label>
          <input type="number" name="deposit_amount" class="form-control" min="1" step="1" required placeholder="Enter agreed deposit">
        </div>
        <div class="form-group">
          <label class="form-label">Move-in Date <span class="required">*</span></label>
          <input type="date" name="move_in_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">Notes (optional)</label>
          <textarea name="notes" class="form-control" rows="2" placeholder="Any notes about this move-in…"></textarea>
        </div>

        <div class="modal-footer" style="padding:0;margin-top:8px;">
          <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
          <button type="submit" class="btn btn-primary" <?= empty($vacantUnits)?'disabled':'' ?>>
            <i class="bi bi-house-door"></i> Confirm Move-In
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ── ADD NEW TENANT MANUALLY ────────────────────────────── -->
<div class="modal-overlay" id="addTenantModal">
  <div class="modal modal-lg">
    <div class="modal-header">
      <h3><i class="bi bi-person-plus-fill"></i> Register New Tenant</h3>
      <button class="modal-close" data-modal-close><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="modal-body">
      <div class="alert alert-info mb-3">
        <i class="bi bi-info-circle-fill"></i>
        <div>You can register a tenant directly. They will be added to <?= htmlspecialchars($aptName) ?>.</div>
      </div>
      <form action="../includes/save_tenant.php" method="POST" enctype="multipart/form-data" data-validate>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(rmsCsrfToken()) ?>">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Full Name <span class="required">*</span></label>
            <input type="text" name="full_name" class="form-control" placeholder="Full name" required>
            <span class="form-error" style="display:none"><i class="bi bi-exclamation-circle"></i> Required</span>
          </div>
          <div class="form-group">
            <label class="form-label">Phone <span class="required">*</span></label>
            <input type="tel" name="phone" class="form-control" placeholder="07XX XXX XXX" pattern="(?:0[17][0-9]{8}|\+254[17][0-9]{8}|00254[17][0-9]{8})" maxlength="13" inputmode="tel" title="Enter a valid Kenyan phone number" required>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" placeholder="email@example.com">
          </div>
          <div class="form-group">
            <label class="form-label">Assign to Unit</label>
            <select name="unit_id" class="form-control">
              <option value="">— Assign Later —</option>
              <?php foreach ($vacantUnits as $vu): ?>
              <option value="<?= $vu['id'] ?>">
                <?= htmlspecialchars($vu['unit_number']) ?>
                (<?= htmlspecialchars($vu['unit_type']) ?> · <?= formatKES($vu['monthly_rent']) ?>)
              </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Move-in Date</label>
            <input type="date" name="move_in_date" class="form-control" value="<?= date('Y-m-d') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Password <span class="required">*</span></label>
            <input type="password" name="password" class="form-control" placeholder="Min 8 chars" required minlength="8">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Confirm Password <span class="required">*</span></label>
          <input type="password" name="confirm_password" class="form-control" placeholder="Repeat" required>
        </div>
        <div class="modal-footer" style="padding:0;margin-top:8px;">
          <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
          <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Register Tenant</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ══════════ REJECT TENANT MODAL ══════════ -->
<div class="modal-overlay" id="rejectTenantModal">
  <div class="modal">
    <div class="modal-header">
      <h3><i class="bi bi-x-circle"></i> Reject Application</h3>
      <button class="modal-close" data-modal-close><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="modal-body">
      <p style="color:var(--text-muted);font-size:.88rem;margin-bottom:16px;">
        Rejecting <strong id="rejectTenantName"></strong>'s application will permanently delete their registration.
      </p>
      <form method="POST" action="../includes/save_tenant_updated.php" id="rejectTenantForm">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(rmsCsrfToken()) ?>">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(rmsCsrfToken()) ?>">
        <input type="hidden" name="action" value="reject">
        <input type="hidden" name="tenant_id" id="rejectTenantId">
        <div class="form-group">
          <label class="form-label">Reason <span style="color:var(--text-muted);font-weight:400;">(optional)</span></label>
          <textarea name="notes" class="form-control" rows="2"
            placeholder="e.g. Incomplete information, apartment full…"></textarea>
        </div>
        <div class="modal-footer" style="padding:0;margin-top:8px;">
          <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
          <button type="submit" class="btn btn-danger">
            <i class="bi bi-x-lg"></i> Confirm Rejection
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="../assets/js/main.js"></script>
<script>
function openAssignModal(id, name) {
  document.getElementById('assignTenantId').value       = id;
  document.getElementById('assignTenantName').textContent = name;

  openModal('assignUnitModal');
}

function openRejectTenantModal(id, name) {
  document.getElementById('rejectTenantId').value          = id;
  document.getElementById('rejectTenantName').textContent  = name;
  const ta = document.querySelector('#rejectTenantForm textarea[name="notes"]');
  if (ta) ta.value = '';
  openModal('rejectTenantModal');
}
</script>
</body>
</html>
