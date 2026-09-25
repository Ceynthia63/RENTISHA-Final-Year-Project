<?php
session_start();
require_once '../includes/auth_check.php';
require_once '../includes/db_helpers.php';
requireRole('caretaker');

$pageTitle  = 'My Units';
$activePage = 'units';

$aptId = (int)($_SESSION['apartment_id'] ?? 0);
if (!$aptId) { header('Location: dashboard.php'); exit; }

$units       = getUnits($aptId);
$vacantUnits = getVacantUnits($aptId);
$tenants     = getTenants('active_or_prospective', $aptId);

$viewUnit = null;
if (!empty($_GET['view'])) {
    $pdo  = getDB();
    $stmt = $pdo->prepare(
        "SELECT u.*, a.name AS apartment_name, a.location,
                COALESCE(ten.full_name,'—') AS tenant_name,
                ten.id AS tenant_id, ten.phone AS tenant_phone,
                tu.move_in_date,
                (SELECT p.status FROM payments p WHERE p.unit_id=u.id AND p.month=:cm AND p.year=:cy LIMIT 1) AS rent_status
         FROM units u
         JOIN apartments a ON a.id=u.apartment_id
         LEFT JOIN tenant_units tu ON tu.unit_id=u.id AND tu.status='active'
         LEFT JOIN users ten ON ten.id=tu.tenant_id
         WHERE u.id=:id AND u.apartment_id=:apt LIMIT 1"
    );
    $stmt->execute([':id'=>(int)$_GET['view'],':apt'=>$aptId,':cm'=>date('F'),':cy'=>(int)date('Y')]);
    $viewUnit = $stmt->fetch(PDO::FETCH_ASSOC);
}

$success = $_GET['success'] ?? '';
$error   = $_GET['error']   ?? '';

$totalUnits = count($units);
$occupied   = count(array_filter($units, fn($u) => $u['status']==='Occupied'));
$vacant     = count(array_filter($units, fn($u) => $u['status']==='Vacant'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Units — Rentisha Caretaker</title>
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

      <?php if ($viewUnit): ?>
      <!-- UNIT DETAIL -->
      <div class="page-header">
        <div>
          <h1>Unit <?= htmlspecialchars($viewUnit['unit_number']) ?></h1>
          <p><i class="bi bi-building"></i> <?= htmlspecialchars($viewUnit['apartment_name']) ?></p>
        </div>
        <div class="d-flex gap-2">
          <a href="units.php" class="btn btn-outline btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
          <?php if ($viewUnit['status']==='Vacant'): ?>
          <button class="btn btn-success btn-sm" data-modal-open="assignModal">
            <i class="bi bi-person-plus"></i> Assign Tenant
          </button>
          <?php elseif ($viewUnit['status']==='Occupied' && $viewUnit['tenant_id']): ?>
          <button class="btn btn-warning btn-sm" data-modal-open="vacateModal">
            <i class="bi bi-box-arrow-right"></i> Move Out
          </button>
          <?php endif; ?>
        </div>
      </div>

      <div class="card mb-4">
        <div class="card-body">
          <?php $info = [
            ['Unit Number',  $viewUnit['unit_number']],
            ['Type',         $viewUnit['unit_type']],
            ['Floor',        $viewUnit['floor'] ? 'Floor '.$viewUnit['floor'] : 'Ground'],
            ['Monthly Rent', formatKES($viewUnit['monthly_rent'])],
            ['Status',       $viewUnit['status']],
            ['Tenant',       $viewUnit['tenant_name']],
            ['Tenant Phone', $viewUnit['tenant_phone'] ?? '—'],
            ['Move-in Date', formatDate($viewUnit['move_in_date'])],
            ['Rent Status',  $viewUnit['rent_status'] ?? '—'],
          ]; ?>
          <div class="form-row">
            <?php foreach ($info as [$l,$v]): ?>
            <div>
              <p class="fs-sm text-muted"><?= $l ?></p>
              <p class="fw-bold"><?= htmlspecialchars($v ?? '—') ?></p>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <!-- Assign Tenant Modal -->
      <?php if ($viewUnit['status']==='Vacant'): ?>
      <div class="modal-overlay" id="assignModal">
        <div class="modal">
          <div class="modal-header">
            <h3><i class="bi bi-person-plus-fill"></i> Assign Tenant to <?= htmlspecialchars($viewUnit['unit_number']) ?></h3>
            <button class="modal-close" data-modal-close><i class="bi bi-x-lg"></i></button>
          </div>
          <div class="modal-body">
            <form action="../includes/move_in.php" method="POST" data-validate>
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(rmsCsrfToken()) ?>">
              <input type="hidden" name="unit_id" value="<?= $viewUnit['id'] ?>">
              <div class="form-group">
                <label class="form-label">Select Tenant <span class="required">*</span></label>
                <select name="tenant_id" class="form-control" required>
                  <option value="">— Select —</option>
                  <?php foreach ($tenants as $t): if ($t['unit_id']) continue; ?>
                  <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['full_name']) ?> — <?= htmlspecialchars($t['phone']??'') ?></option>
                  <?php endforeach; ?>
                </select>
                <div class="form-hint">Only tenants without an active unit shown. <a href="tenants.php?action=add">Register new tenant</a>.</div>
              </div>
              <div class="form-group">
                <label class="form-label">Agreed Deposit (KES) <span class="required">*</span></label>
                <input type="number" name="deposit_amount" class="form-control" min="1" step="1" required placeholder="Enter agreed deposit">
              </div>
              <div class="form-group">
                <label class="form-label">Move-in Date <span class="required">*</span></label>
                <input type="date" name="move_in_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
              </div>
              <div class="modal-footer" style="padding:0;margin-top:8px;">
                <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-house-door"></i> Confirm Move-In</button>
              </div>
            </form>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <!-- Vacate Modal -->
      <?php if ($viewUnit['status']==='Occupied' && $viewUnit['tenant_id']): ?>
      <div class="modal-overlay" id="vacateModal">
        <div class="modal">
          <div class="modal-header">
            <h3><i class="bi bi-box-arrow-right"></i> Move Out — <?= htmlspecialchars($viewUnit['unit_number']) ?></h3>
            <button class="modal-close" data-modal-close><i class="bi bi-x-lg"></i></button>
          </div>
          <div class="modal-body">
            <div class="alert alert-warning">
              <i class="bi bi-exclamation-triangle-fill"></i>
              <div>Moving out <strong><?= htmlspecialchars($viewUnit['tenant_name']) ?></strong>. Unit becomes Vacant.</div>
            </div>
            <form action="../includes/move_out.php" method="POST">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(rmsCsrfToken()) ?>">
              <input type="hidden" name="tenant_id" value="<?= $viewUnit['tenant_id'] ?>">
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
                <button type="submit" class="btn btn-warning"><i class="bi bi-box-arrow-right"></i> Confirm Move-Out</button>
              </div>
            </form>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <?php else: ?>
      <!-- UNITS LIST -->
      <div class="page-header">
        <div>
          <h1>My Units</h1>
          <p><?= htmlspecialchars($_SESSION['apartment'] ?? '') ?> — <?= $totalUnits ?> unit(s)</p>
        </div>
      </div>

      <div class="stats-grid" style="grid-template-columns:repeat(auto-fill,minmax(130px,1fr));margin-bottom:18px;">
        <div class="stat-card blue">
          <div class="stat-icon"><i class="bi bi-door-open"></i></div>
          <div class="stat-value" data-count="<?= $totalUnits ?>"><?= $totalUnits ?></div>
          <div class="stat-label">Total Units</div>
        </div>
        <div class="stat-card green">
          <div class="stat-icon"><i class="bi bi-check-circle"></i></div>
          <div class="stat-value" data-count="<?= $occupied ?>"><?= $occupied ?></div>
          <div class="stat-label">Occupied</div>
        </div>
        <div class="stat-card orange">
          <div class="stat-icon"><i class="bi bi-door-closed"></i></div>
          <div class="stat-value" data-count="<?= $vacant ?>"><?= $vacant ?></div>
          <div class="stat-label">Vacant</div>
        </div>
      </div>

      <div class="filter-bar">
        <div class="input-group search-box">
          <i class="bi bi-search input-icon"></i>
          <input type="search" class="form-control" placeholder="Search unit, tenant…" data-search-table="ctUnitsTable">
        </div>
      </div>

      <div class="card">
        <div class="card-body p-0">
          <div class="table-wrapper">
            <table class="rms-table" id="ctUnitsTable">
              <thead>
                <tr><th>#</th><th>Unit</th><th>Type</th><th>Rent</th><th>Tenant</th><th>Move-in</th><th>Rent Status</th><th>Occupancy</th><th>Actions</th></tr>
              </thead>
              <tbody>
                <?php if (empty($units)): ?>
                <tr><td colspan="9" style="text-align:center;padding:28px;color:var(--text-muted);">No units found.</td></tr>
                <?php else: ?>
                  <?php foreach ($units as $i => $u): ?>
                  <tr>
                    <td><?= $i+1 ?></td>
                    <td>
                      <a href="units.php?view=<?= $u['id'] ?>" style="font-weight:700;color:var(--primary);">
                        <?= htmlspecialchars($u['unit_number']) ?>
                      </a>
                    </td>
                    <td><span class="badge badge-info"><?= htmlspecialchars($u['unit_type']) ?></span></td>
                    <td class="fw-bold"><?= formatKES($u['monthly_rent']) ?></td>
                    <td>
                      <?php if ($u['tenant_name'] && $u['tenant_name']!=='—'): ?>
                        <a href="tenants.php?view=<?= $u['tenant_id'] ?>" style="color:var(--primary);font-weight:600;">
                          <?= htmlspecialchars($u['tenant_name']) ?>
                        </a>
                      <?php else: ?>
                        <span class="text-muted fs-sm">—</span>
                      <?php endif; ?>
                    </td>
                    <td><?= formatDate($u['move_in_date']) ?></td>
                    <td>
                      <?php if ($u['rent_status']): ?>
                      <span class="badge <?= statusBadgeClass($u['rent_status']) ?>"><?= $u['rent_status'] ?></span>
                      <?php else: ?><span class="text-muted fs-sm">—</span><?php endif; ?>
                    </td>
                    <td><span class="badge <?= statusBadgeClass($u['status']) ?>"><?= $u['status'] ?></span></td>
                    <td>
                      <a href="units.php?view=<?= $u['id'] ?>" class="btn btn-secondary btn-sm btn-icon" title="View"><i class="bi bi-eye"></i></a>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
      <?php endif; ?>

    </div>
  </div>
</div>
<script src="../assets/js/main.js"></script>
</body>
</html>
