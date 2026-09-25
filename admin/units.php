<?php
session_start();
require_once '../includes/auth_check.php';
require_once '../includes/db_helpers.php';
requireRole('admin');

$pageTitle  = 'Units';
$activePage = 'units';

$filterApt    = (int)($_GET['apt']    ?? 0) ?: null;
$filterStatus = $_GET['status'] ?? '';
$filterType   = $_GET['type']   ?? '';


$units      = getUnits($filterApt);
$apartments = getApartments();
$allTenants = getTenants('active_or_prospective');

if ($filterStatus) {
    $units = array_filter($units, fn($u) => strtolower($u['status']) === strtolower($filterStatus));
}
if ($filterType) {
    $units = array_filter($units, fn($u) => $u['unit_type'] === $filterType);
}
$units = array_values($units);


$viewUnit = null;
if (!empty($_GET['view'])) {
    $pdo  = getDB();

    
    $hasMoveIn = $pdo->query("SHOW COLUMNS FROM tenant_units LIKE 'move_in_date'")->fetchColumn();
    $miField   = $hasMoveIn ? 'tu.move_in_date,' : 'tu.lease_start AS move_in_date,';

    $stmt = $pdo->prepare(
        "SELECT u.*, a.name AS apartment_name, a.location, a.id AS apartment_id,
                COALESCE(ten.full_name,'—') AS tenant_name,
                ten.id AS tenant_id, ten.phone AS tenant_phone,
                {$miField} tu.id AS assignment_id,
                (SELECT p.status FROM payments p
                 WHERE p.unit_id = u.id AND p.month=:cm AND p.year=:cy LIMIT 1) AS rent_status
         FROM units u
         JOIN apartments a ON a.id = u.apartment_id
         LEFT JOIN tenant_units tu ON tu.unit_id = u.id AND tu.status = 'active'
         LEFT JOIN users ten ON ten.id = tu.tenant_id
         WHERE u.id = :id LIMIT 1"
    );
    $stmt->execute([':id' => (int)$_GET['view'], ':cm' => date('F'), ':cy' => (int)date('Y')]);
    $viewUnit = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($viewUnit) {
        
        $ph = $pdo->prepare(
            "SELECT p.*, u2.full_name AS tenant_name
             FROM payments p
             JOIN users u2 ON u2.id = p.tenant_id
             WHERE p.unit_id = :uid
             ORDER BY p.year DESC, FIELD(p.month,
               'January','February','March','April','May','June',
               'July','August','September','October','November','December') DESC
             LIMIT 24"
        );
        $ph->execute([':uid' => (int)$_GET['view']]);
        $unitPayHistory = $ph->fetchAll(PDO::FETCH_ASSOC);

        
        $th = $pdo->prepare(
            "SELECT tu.*, u3.full_name AS tenant_name, u3.phone
             FROM tenant_units tu
             JOIN users u3 ON u3.id = tu.tenant_id
             WHERE tu.unit_id = :uid
             ORDER BY tu.created_at DESC"
        );
        $th->execute([':uid' => (int)$_GET['view']]);
        $unitTenantHistory = $th->fetchAll(PDO::FETCH_ASSOC);
    }
}


$allUnits     = getUnits();  
$totalUnits   = count($allUnits);
$occupied     = count(array_filter($allUnits, fn($u) => $u['status'] === 'Occupied'));
$vacant       = count(array_filter($allUnits, fn($u) => $u['status'] === 'Vacant'));
$maintenance  = count(array_filter($allUnits, fn($u) => $u['status'] === 'Under Maintenance'));


$editUnit = null;
if (!empty($_GET['edit'])) {
    $pdo2 = getDB();
    $es   = $pdo2->prepare("SELECT * FROM units WHERE id=:id LIMIT 1");
    $es->execute([':id' => (int)$_GET['edit']]);
    $editUnit = $es->fetch(PDO::FETCH_ASSOC);
}

$success = $_GET['success'] ?? '';
$error   = $_GET['error']   ?? '';

$unitTypes = [
    'Bedsitter','Studio','Self-Contained',
    '1 Bedroom','2 Bedroom','3 Bedroom','4 Bedroom',
    'Penthouse','Maisonette','Office','Shop','Other'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Units — Rentisha Admin</title>
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

      <?php if ($success): ?>
      <div class="alert alert-success mb-4"><i class="bi bi-check-circle-fill"></i><div><?= htmlspecialchars($success) ?></div></div>
      <?php endif; ?>
      <?php if ($error): ?>
      <div class="alert alert-danger mb-4"><i class="bi bi-exclamation-circle-fill"></i><div><?= htmlspecialchars($error) ?></div></div>
      <?php endif; ?>

      <?php if ($viewUnit): ?>
      
      <div class="page-header">
        <div>
          <h1>Unit <?= htmlspecialchars($viewUnit['unit_number']) ?></h1>
          <p>
            <i class="bi bi-building"></i> <?= htmlspecialchars($viewUnit['apartment_name']) ?>
            &nbsp;·&nbsp;
            <i class="bi bi-geo-alt"></i> <?= htmlspecialchars($viewUnit['location']) ?>
          </p>
        </div>
        <div class="d-flex gap-2">
          <a href="units.php<?= $filterApt ? '?apt='.$filterApt : '' ?>" class="btn btn-outline btn-sm">
            <i class="bi bi-arrow-left"></i> Back
          </a>
          <button class="btn btn-primary btn-sm"
            onclick="openEditUnitModal(<?= htmlspecialchars(json_encode($viewUnit)) ?>)">
            <i class="bi bi-pencil"></i> Edit Unit
          </button>
          <?php if ($viewUnit['status'] === 'Vacant'): ?>
          <button class="btn btn-success btn-sm" data-modal-open="assignTenantModal">
            <i class="bi bi-person-plus"></i> Assign Tenant
          </button>
          <?php elseif ($viewUnit['status'] === 'Occupied'): ?>
          <button class="btn btn-warning btn-sm" data-modal-open="vacateModal">
            <i class="bi bi-box-arrow-right"></i> Vacate Unit
          </button>
          <?php endif; ?>
        </div>
      </div>

      <div class="grid-2 mb-4">
      
        <div class="card">
          <div class="card-header"><h3 class="card-title"><i class="bi bi-door-open"></i> Unit Details</h3></div>
          <div class="card-body">
            <?php
            $feats = json_decode($viewUnit['features'] ?? '[]', true) ?: [];
            $info  = [
              ['Unit Number',    $viewUnit['unit_number']],
              ['Apartment',      $viewUnit['apartment_name']],
              ['Unit Type',      $viewUnit['unit_type']],
              ['Floor',          $viewUnit['floor'] ? 'Floor ' . $viewUnit['floor'] : 'Ground'],
              ['Bathrooms',      $viewUnit['bathrooms'] ?? 1],
              ['Monthly Rent',   formatKES($viewUnit['monthly_rent'])],
              ['Security Deposit',formatKES($viewUnit['deposit'] ?? 0)],
              ['Current Tenant', $viewUnit['tenant_name']],
              ['Move-in Date',   formatDate($viewUnit['move_in_date'])],
              ['Rent Status',    $viewUnit['rent_status'] ?? '—'],
              ['Unit Status',    $viewUnit['status']],
            ];
            ?>
            <table style="width:100%;border-collapse:collapse;font-size:.85rem;">
              <?php foreach ($info as [$label,$val]): ?>
              <tr style="border-bottom:1px solid var(--border);">
                <td style="padding:7px 0;color:var(--text-muted);width:40%;"><?= $label ?></td>
                <td style="padding:7px 0;font-weight:600;"><?= htmlspecialchars($val ?? '—') ?></td>
              </tr>
              <?php endforeach; ?>
            </table>
            <?php if ($feats): ?>
            <div style="margin-top:12px;">
              <div style="font-size:.75rem;color:var(--text-muted);margin-bottom:6px;">AMENITIES</div>
              <div style="display:flex;flex-wrap:wrap;gap:6px;">
                <?php foreach ($feats as $f): ?>
                <span class="badge badge-info"><?= htmlspecialchars($f) ?></span>
                <?php endforeach; ?>
              </div>
            </div>
            <?php endif; ?>
            <?php if ($viewUnit['notes']): ?>
            <p style="margin-top:10px;font-size:.83rem;color:var(--text-secondary);"><?= htmlspecialchars($viewUnit['notes']) ?></p>
            <?php endif; ?>
          </div>
        </div>

        
        <div>
          
          <div class="card mb-4">
            <div class="card-header"><h3 class="card-title"><i class="bi bi-people"></i> Tenant History</h3></div>
            <div class="card-body p-0">
              <?php if (empty($unitTenantHistory)): ?>
              <div style="text-align:center;padding:20px;color:var(--text-muted);">No tenant history.</div>
              <?php else: ?>
              <div class="table-wrapper">
                <table class="rms-table">
                  <thead><tr><th>Tenant</th><th>Move-in</th><th>Move-out</th><th>Status</th></tr></thead>
                  <tbody>
                    <?php foreach ($unitTenantHistory as $h): ?>
                    <tr>
                      <td class="fw-bold"><?= htmlspecialchars($h['tenant_name']) ?></td>
                      <td><?= formatDate($h['move_in_date']) ?></td>
                      <td><?= $h['move_out_date'] ? formatDate($h['move_out_date']) : '—' ?></td>
                      <td><span class="badge <?= $h['status']==='active'?'badge-active':'badge-moved-out' ?>"><?= ucfirst($h['status']) ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
              <?php endif; ?>
            </div>
          </div>

          
          <div class="card">
            <div class="card-header"><h3 class="card-title"><i class="bi bi-receipt"></i> Recent Payments</h3></div>
            <div class="card-body p-0">
              <?php if (empty($unitPayHistory)): ?>
              <div style="text-align:center;padding:20px;color:var(--text-muted);">No payment records.</div>
              <?php else: ?>
              <div class="table-wrapper">
                <table class="rms-table">
                  <thead><tr><th>Period</th><th>Tenant</th><th>Amount</th><th>Status</th></tr></thead>
                  <tbody>
                    <?php foreach (array_slice($unitPayHistory,0,8) as $p): ?>
                    <tr>
                      <td><?= $p['month'] . ' ' . $p['year'] ?></td>
                      <td><?= htmlspecialchars($p['tenant_name']) ?></td>
                      <td class="fw-bold"><?= formatKES($p['amount']) ?></td>
                      <td><span class="badge <?= statusBadgeClass($p['status']) ?>"><?= $p['status'] ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>

     
      <?php if ($viewUnit['status'] === 'Vacant'): ?>
      <div class="modal-overlay" id="assignTenantModal">
        <div class="modal">
          <div class="modal-header">
            <h3><i class="bi bi-person-plus-fill"></i> Assign Tenant to Unit <?= htmlspecialchars($viewUnit['unit_number']) ?></h3>
            <button class="modal-close" data-modal-close><i class="bi bi-x-lg"></i></button>
          </div>
          <div class="modal-body">
            <form action="../includes/move_in.php" method="POST" data-validate>
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(rmsCsrfToken()) ?>">
              <input type="hidden" name="unit_id" value="<?= $viewUnit['id'] ?>">
              <div class="form-group">
                <label class="form-label">Select Tenant <span class="required">*</span></label>
                <select name="tenant_id" class="form-control" required>
                  <option value="">— Select Active Tenant without a Unit —</option>
                  <?php foreach ($allTenants as $t):
                    if ($t['unit_id']) continue; 
                  ?>
                  <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['full_name']) ?> — <?= htmlspecialchars($t['phone'] ?? '') ?></option>
                  <?php endforeach; ?>
                </select>
                <div class="form-hint">Only tenants without an active unit assignment are shown. <a href="tenants.php?action=add">Add a new tenant</a>.</div>
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
                <textarea name="notes" class="form-control" rows="2"></textarea>
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

      
      <?php if ($viewUnit['status'] === 'Occupied' && $viewUnit['tenant_id']): ?>
      <div class="modal-overlay" id="vacateModal">
        <div class="modal">
          <div class="modal-header">
            <h3><i class="bi bi-box-arrow-right"></i> Vacate Unit <?= htmlspecialchars($viewUnit['unit_number']) ?></h3>
            <button class="modal-close" data-modal-close><i class="bi bi-x-lg"></i></button>
          </div>
          <div class="modal-body">
            <div class="alert alert-warning">
              <i class="bi bi-exclamation-triangle-fill"></i>
              <div>This will move out <strong><?= htmlspecialchars($viewUnit['tenant_name']) ?></strong>
                and mark this unit as <strong>Vacant</strong>. History is retained.</div>
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
                  <option value="">— Select —</option>
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
      
      
      <div class="page-header">
        <div>
          <h1>Units Management</h1>
          <p>View and manage all rental units. <i class="bi bi-info-circle"></i> New units are created when setting up apartments.</p>
        </div>
      </div>

      
      <div class="stats-grid" style="grid-template-columns:repeat(auto-fill,minmax(140px,1fr));margin-bottom:18px;">
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
        <div class="stat-card">
          <div class="stat-icon"><i class="bi bi-tools"></i></div>
          <div class="stat-value" data-count="<?= $maintenance ?>"><?= $maintenance ?></div>
          <div class="stat-label">Maintenance</div>
        </div>
      </div>

    
      <div class="filter-bar">
        <div class="input-group search-box">
          <i class="bi bi-search input-icon"></i>
          <input type="search" class="form-control" placeholder="Search unit, tenant, type…" data-search-table="unitsTable">
        </div>
        <form method="GET" style="display:contents;">
          <select name="apt" class="form-control" style="width:auto;" onchange="this.form.submit()">
            <option value="">All Apartments</option>
            <?php foreach ($apartments as $apt): ?>
            <option value="<?= $apt['id'] ?>" <?= $filterApt===$apt['id']?'selected':'' ?>>
              <?= htmlspecialchars($apt['name']) ?>
            </option>
            <?php endforeach; ?>
          </select>
          <select name="status" class="form-control" style="width:auto;" onchange="this.form.submit()">
            <option value="">All Statuses</option>
            <option value="Occupied"         <?= $filterStatus==='Occupied'?'selected':'' ?>>Occupied</option>
            <option value="Vacant"           <?= $filterStatus==='Vacant'?'selected':'' ?>>Vacant</option>
            <option value="Under Maintenance"<?= $filterStatus==='Under Maintenance'?'selected':'' ?>>Maintenance</option>
          </select>
          <select name="type" class="form-control" style="width:auto;" onchange="this.form.submit()">
            <option value="">All Types</option>
            <?php foreach ($unitTypes as $ut): ?>
            <option value="<?= $ut ?>" <?= $filterType===$ut?'selected':'' ?>><?= $ut ?></option>
            <?php endforeach; ?>
          </select>
        </form>
      </div>

      
      <div class="card">
        <div class="card-body p-0">
          <div class="table-wrapper">
            <table class="rms-table" id="unitsTable">
              <thead>
                <tr>
                  <th>#</th><th>Unit No.</th><th>Apartment</th><th>Type</th>
                  <th>Floor</th><th>Rent / Month</th><th>Tenant</th>
                  <th>Move-in</th><th>Rent Status</th><th>Occupancy</th>
                  <th class="col-actions">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($units)): ?>
                <tr>
                  <td colspan="11" style="text-align:center;padding:36px;color:var(--text-muted);">
                    <i class="bi bi-door-open" style="font-size:2.5rem;display:block;margin-bottom:8px;"></i>
                    No units found<?= $filterApt ? ' in this apartment' : '' ?>.
                    <?php if (!$filterApt): ?>
                    <br><a href="#" data-modal-open="unitModal">Add the first unit</a>.
                    <?php endif; ?>
                  </td>
                </tr>
                <?php else: ?>
                  <?php foreach ($units as $i => $u): ?>
                  <tr>
                    <td><?= $i + 1 ?></td>
                    <td>
                      <a href="units.php?view=<?= $u['id'] ?>" style="font-weight:700;color:var(--primary);">
                        <?= htmlspecialchars($u['unit_number']) ?>
                      </a>
                    </td>
                    <td>
                      <a href="apartments.php?view=<?= $u['apartment_id'] ?>" style="font-size:.82rem;">
                        <?= htmlspecialchars($u['apartment_name']) ?>
                      </a>
                    </td>
                    <td><span class="badge badge-info"><?= htmlspecialchars($u['unit_type']) ?></span></td>
                    <td><?= $u['floor'] ? 'Floor '.$u['floor'] : 'Ground' ?></td>
                    <td class="fw-bold"><?= formatKES($u['monthly_rent']) ?></td>
                    <td>
                      <?php if ($u['tenant_name'] && $u['tenant_name'] !== '—'): ?>
                        <a href="tenants.php?view=<?= $u['tenant_id'] ?>" style="font-weight:600;color:var(--primary);">
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
                    <td>
                      <span class="badge <?= statusBadgeClass($u['status']) ?>"><?= $u['status'] ?></span>
                    </td>
                    <td class="col-actions">
                      <div class="d-flex gap-1" style="justify-content:flex-end;">
                        <a href="units.php?view=<?= $u['id'] ?>"
                           class="btn btn-secondary btn-sm btn-icon" title="View"><i class="bi bi-eye"></i></a>
                        <button class="btn btn-outline btn-sm btn-icon" title="Edit"
                          onclick="openEditUnitModal(<?= htmlspecialchars(json_encode($u)) ?>)">
                          <i class="bi bi-pencil"></i>
                        </button>
                        <?php if ($u['status'] === 'Vacant'): ?>
                        <a href="tenants.php?action=add&unit=<?= $u['id'] ?>"
                           class="btn btn-success btn-sm btn-icon" title="Assign Tenant">
                          <i class="bi bi-person-plus"></i>
                        </a>
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

      <?php endif; ?>

    </div>
  </div>
</div>


<div class="modal-overlay" id="unitModal">
  <div class="modal modal-lg">
    <div class="modal-header">
      <h3 id="unitModalTitle"><i class="bi bi-pencil-square"></i> Edit Unit</h3>
      <button class="modal-close" data-modal-close><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="modal-body">
      <form action="../includes/save_unit.php" method="POST" data-validate id="unitForm">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(rmsCsrfToken()) ?>">
        <input type="hidden" name="edit_id" id="unitEditId" value="">

        <div class="alert alert-info mb-4">
          <i class="bi bi-info-circle-fill"></i>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Apartment <span class="required">*</span></label>
            <select name="apartment_id" id="unitApt" class="form-control" required>
              <option value="">— Select Apartment —</option>
              <?php foreach ($apartments as $apt): ?>
              <option value="<?= $apt['id'] ?>" <?= $filterApt===$apt['id']?'selected':'' ?>>
                <?= htmlspecialchars($apt['name']) ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Unit Number <span class="required">*</span></label>
            <input type="text" name="unit_number" id="unitNumber" class="form-control" placeholder="e.g. A-101" required>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Unit Type <span class="required">*</span></label>
            <select name="unit_type" id="unitType" class="form-control" required>
              <option value="">— Select Type —</option>
              <?php foreach ($unitTypes as $ut): ?>
              <option value="<?= $ut ?>"><?= $ut ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Floor</label>
            <input type="number" name="floor" id="unitFloor" class="form-control" placeholder="0 = Ground" min="0">
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Monthly Rent (KES) <span class="required">*</span></label>
            <div class="input-group">
              <i class="bi bi-currency-exchange input-icon"></i>
              <input type="number" name="monthly_rent" id="unitRent" class="form-control" placeholder="e.g. 15000" required min="0.01" step="0.01">
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Security Deposit (KES)</label>
            <div class="input-group">
              <i class="bi bi-currency-exchange input-icon"></i>
              <input type="number" name="deposit" id="unitDeposit" class="form-control" placeholder="e.g. 30000" min="0">
            </div>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Bathrooms</label>
            <select name="bathrooms" id="unitBaths" class="form-control">
              <option value="1">1</option><option value="2">2</option><option value="3">3+</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Status</label>
            <select name="status" id="unitStatus" class="form-control">
              <option value="Vacant">Vacant</option>
              <option value="Occupied">Occupied</option>
              <option value="Under Maintenance">Under Maintenance</option>
            </select>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Amenities / Features</label>
          <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:6px;" id="featuresGrid">
            <?php foreach (['Balcony','Parking','Water Heater','Furnished','DSTV Point','Fibre Ready','WiFi','Garden View','En-suite','Storage'] as $f): ?>
            <label style="display:flex;align-items:center;gap:6px;font-size:.82rem;cursor:pointer;padding:5px 8px;border:1px solid var(--border);border-radius:6px;">
              <input type="checkbox" name="features[]" value="<?= $f ?>" class="feat-check" style="accent-color:var(--primary);">
              <?= $f ?>
            </label>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Notes</label>
          <textarea name="notes" id="unitNotes" class="form-control" rows="2" placeholder="Optional notes about this unit…"></textarea>
        </div>

        <div class="modal-footer" style="padding:0;margin-top:8px;">
          <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
          <button type="submit" class="btn btn-primary" id="unitSubmitBtn">
            <i class="bi bi-check-lg"></i> Save Unit
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="../assets/js/main.js"></script>
<script>
function openEditUnitModal(u) {
  document.getElementById('unitModalTitle').innerHTML = '<i class="bi bi-pencil-square"></i> Edit Unit';
  document.getElementById('unitSubmitBtn').innerHTML  = '<i class="bi bi-check-lg"></i> Update Unit';
  document.getElementById('unitEditId').value    = u.id;
  document.getElementById('unitApt').value       = u.apartment_id  || '';
  document.getElementById('unitNumber').value    = u.unit_number   || '';
  document.getElementById('unitFloor').value     = u.floor         || '0';
  document.getElementById('unitRent').value      = u.monthly_rent  || '';
  document.getElementById('unitDeposit').value   = u.deposit       || '';
  document.getElementById('unitStatus').value    = u.status        || 'Vacant';
  document.getElementById('unitNotes').value     = u.notes         || '';


  const typeEl = document.getElementById('unitType');
  if (typeEl) typeEl.value = u.unit_type || '';

  
  const bathEl = document.getElementById('unitBaths');
  if (bathEl) bathEl.value = u.bathrooms || '1';

  
  const feats = (() => { try { return JSON.parse(u.features || '[]'); } catch(e){ return []; } })();
  document.querySelectorAll('.feat-check').forEach(cb => {
    cb.checked = feats.includes(cb.value);
  });

  openModal('unitModal');

<?php if (isset($_GET['action']) && $_GET['action']==='add'): ?>
openModal('unitModal');
<?php endif; ?>

<?php if ($editUnit): ?>
openEditUnitModal(<?= json_encode($editUnit) ?>);
<?php endif; ?>
</script>
</body>
</html>
