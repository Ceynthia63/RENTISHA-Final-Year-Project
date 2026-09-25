<?php
session_start();
require_once '../includes/auth_check.php';
require_once '../includes/db_helpers.php';
requireRole('admin');

$pageTitle  = 'Apartments';
$activePage = 'apartments';

$apts       = getApartments('Active');
$inactiveApts = getApartments('Inactive');
$allApts    = array_merge($apts, $inactiveApts);
$caretakers = getCaretakers('active');


$viewApt = null;
if (!empty($_GET['view'])) {
    $viewApt = getApartmentById((int)$_GET['view']);
    if ($viewApt) {
        $viewUnits    = getUnits((int)$_GET['view']);
        $viewTenants  = getTenants('active', (int)$_GET['view']);
        $viewPayTotals= getMonthlyTotals((int)$_GET['view']);
    }
}

$success = $_GET['success'] ?? '';
$error   = $_GET['error']   ?? '';


$editApt = null;
if (!empty($_GET['edit'])) {
    $editApt = getApartmentById((int)$_GET['edit']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Apartments — Rentisha Admin</title>
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

      <?php if ($viewApt): ?>
      
      <div class="page-header">
        <div>
          <h1><?= htmlspecialchars($viewApt['name']) ?></h1>
          <p>
            <i class="bi bi-geo-alt"></i> <?= htmlspecialchars($viewApt['location']) ?>
            <?php if ($viewApt['county']): ?> · <?= htmlspecialchars($viewApt['county']) ?><?php endif; ?>
          </p>
        </div>
        <div class="d-flex gap-2">
          <a href="apartments.php" class="btn btn-outline btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
          <a href="organize_floors.php?apt=<?= $viewApt['id'] ?>" class="btn btn-success btn-sm">
            <i class="bi bi-buildings"></i> Organize Floors
          </a>
          <a href="apartments.php?edit=<?= $viewApt['id'] ?>" class="btn btn-primary btn-sm"><i class="bi bi-pencil"></i> Edit</a>
        </div>
      </div>

      
      <div class="stats-grid" style="grid-template-columns:repeat(auto-fill,minmax(150px,1fr));margin-bottom:20px;">
        <div class="stat-card blue">
          <div class="stat-icon"><i class="bi bi-door-open"></i></div>
          <div class="stat-value"><?= $viewApt['total_units'] ?></div>
          <div class="stat-label">Total Units</div>
        </div>
        <div class="stat-card green">
          <div class="stat-icon"><i class="bi bi-check-circle"></i></div>
          <div class="stat-value"><?= $viewApt['occupied'] ?></div>
          <div class="stat-label">Occupied</div>
        </div>
        <div class="stat-card orange">
          <div class="stat-icon"><i class="bi bi-door-closed"></i></div>
          <div class="stat-value"><?= $viewApt['vacant'] ?></div>
          <div class="stat-label">Vacant</div>
        </div>
        <div class="stat-card green">
          <div class="stat-icon"><i class="bi bi-cash-stack"></i></div>
          <div class="stat-value" style="font-size:1rem;"><?= formatKES($viewPayTotals['collected'] ?? 0) ?></div>
          <div class="stat-label">Collected <?= date('M') ?></div>
        </div>
        <div class="stat-card orange">
          <div class="stat-icon"><i class="bi bi-clock-history"></i></div>
          <div class="stat-value" style="font-size:1rem;"><?= formatKES($viewPayTotals['pending'] ?? 0) ?></div>
          <div class="stat-label">Pending <?= date('M') ?></div>
        </div>
        <div class="stat-card red">
          <div class="stat-icon"><i class="bi bi-exclamation-triangle"></i></div>
          <div class="stat-value" style="font-size:1rem;"><?= formatKES($viewPayTotals['overdue'] ?? 0) ?></div>
          <div class="stat-label">Overdue <?= date('M') ?></div>
        </div>
      </div>

      <div class="grid-2 mb-4">
        
        <div class="card">
          <div class="card-header"><h3 class="card-title"><i class="bi bi-building"></i> Apartment Details</h3></div>
          <div class="card-body">
            <table style="width:100%;font-size:.875rem;border-collapse:collapse;">
              <?php $rows = [
                ['Name',        $viewApt['name']],
                ['Location',    $viewApt['location']],
                ['County',      $viewApt['county'] ?: '—'],
                ['Town / City', $viewApt['town']   ?: '—'],
                ['Estate',      $viewApt['estate'] ?: '—'],
                ['Street/Road', $viewApt['street'] ?: '—'],
                ['Caretaker',   $viewApt['caretaker_name'] . ($viewApt['caretaker_phone'] ? ' · ' . $viewApt['caretaker_phone'] : '')],
                ['Status',      $viewApt['status']],
                ['Created',     formatDate($viewApt['created_at'])],
              ];
              foreach ($rows as [$label, $val]): ?>
              <tr style="border-bottom:1px solid var(--border);">
                <td style="padding:8px 0;font-weight:600;color:var(--text-muted);width:38%;"><?= $label ?></td>
                <td style="padding:8px 0;"><?= htmlspecialchars($val) ?></td>
              </tr>
              <?php endforeach; ?>
            </table>
            <?php if ($viewApt['description']): ?>
            <p style="margin-top:12px;font-size:.85rem;color:var(--text-secondary);"><?= htmlspecialchars($viewApt['description']) ?></p>
            <?php endif; ?>
          </div>
        </div>

        
        <div class="card">
          <div class="card-header">
            <h3 class="card-title"><i class="bi bi-people"></i> Active Tenants (<?= count($viewTenants) ?>)</h3>
            <a href="tenants.php?apt=<?= $viewApt['id'] ?>" class="btn btn-secondary btn-sm">Manage</a>
          </div>
          <div class="card-body p-0">
            <?php if (empty($viewTenants)): ?>
            <div style="text-align:center;padding:24px;color:var(--text-muted);">No active tenants.</div>
            <?php else: ?>
            <div class="table-wrapper">
              <table class="rms-table">
                <thead><tr><th>Tenant</th><th>Unit</th><th>Move-in</th><th>Rent Status</th></tr></thead>
                <tbody>
                  <?php foreach ($viewTenants as $t): ?>
                  <tr>
                    <td class="fw-bold"><?= htmlspecialchars($t['full_name']) ?></td>
                    <td><span class="badge badge-info"><?= htmlspecialchars($t['unit_number']) ?></span></td>
                    <td><?= formatDate($t['move_in_date']) ?></td>
                    <td>
                      <?php if ($t['rent_status']): ?>
                      <span class="badge <?= statusBadgeClass($t['rent_status']) ?>"><?= $t['rent_status'] ?></span>
                      <?php else: ?>
                      <span class="badge badge-pending">No Record</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      
      <div class="card">
        <div class="card-header">
          <h3 class="card-title"><i class="bi bi-door-open"></i> Units in <?= htmlspecialchars($viewApt['name']) ?></h3>
          <div class="alert alert-info" style="margin:0;padding:8px 12px;font-size:0.85rem;">
            <i class="bi bi-info-circle"></i> To add more units, edit this apartment and specify the total number needed.
          </div>
        </div>
        <div class="card-body p-0">
          <?php if (empty($viewUnits)): ?>
          <div style="text-align:center;padding:24px;color:var(--text-muted);">
            No units yet. Edit this apartment to specify how many units to create.
          </div>
          <?php else: ?>
          <div class="table-wrapper">
            <table class="rms-table">
              <thead><tr><th>Unit</th><th>Type</th><th>Rent</th><th>Tenant</th><th>Move-in</th><th>Rent Status</th><th>Occupancy</th></tr></thead>
              <tbody>
                <?php foreach ($viewUnits as $u): ?>
                <tr>
                  <td class="fw-bold"><?= htmlspecialchars($u['unit_number']) ?></td>
                  <td><?= htmlspecialchars($u['unit_type']) ?></td>
                  <td class="fw-bold"><?= formatKES($u['monthly_rent']) ?></td>
                  <td><?= $u['tenant_name'] !== '—' ? htmlspecialchars($u['tenant_name']) : '<span class="text-muted fs-sm">—</span>' ?></td>
                  <td><?= formatDate($u['move_in_date']) ?></td>
                  <td>
                    <?php if ($u['rent_status']): ?>
                    <span class="badge <?= statusBadgeClass($u['rent_status']) ?>"><?= $u['rent_status'] ?></span>
                    <?php else: ?><span class="text-muted fs-sm">—</span><?php endif; ?>
                  </td>
                  <td>
                    <span class="badge <?= statusBadgeClass($u['status']) ?>"><?= $u['status'] ?></span>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?php endif; ?>
        </div>
      </div>

      <?php else: ?>
      <
      <div class="page-header">
        <div>
          <h1>Apartments</h1>
          <p>Manage all apartment blocks, units and caretaker assignments</p>
        </div>
        <div class="d-flex gap-2">
          <button class="btn btn-primary" data-modal-open="aptModal">
            <i class="bi bi-plus-lg"></i> Add Apartment
          </button>
        </div>
      </div>

      
      <div class="stats-grid" style="grid-template-columns:repeat(auto-fill,minmax(150px,1fr));margin-bottom:20px;">
        <div class="stat-card blue">
          <div class="stat-icon"><i class="bi bi-building"></i></div>
          <div class="stat-value" data-count="<?= count($apts) ?>"><?= count($apts) ?></div>
          <div class="stat-label">Active Apartments</div>
        </div>
        <div class="stat-card">
          <div class="stat-icon"><i class="bi bi-door-open"></i></div>
          <div class="stat-value" data-count="<?= array_sum(array_column($allApts,'total_units')) ?>"><?= array_sum(array_column($allApts,'total_units')) ?></div>
          <div class="stat-label">Total Units</div>
        </div>
        <div class="stat-card green">
          <div class="stat-icon"><i class="bi bi-check-circle"></i></div>
          <div class="stat-value" data-count="<?= array_sum(array_column($allApts,'occupied')) ?>"><?= array_sum(array_column($allApts,'occupied')) ?></div>
          <div class="stat-label">Occupied</div>
        </div>
        <div class="stat-card orange">
          <div class="stat-icon"><i class="bi bi-door-closed"></i></div>
          <div class="stat-value" data-count="<?= array_sum(array_column($allApts,'vacant')) ?>"><?= array_sum(array_column($allApts,'vacant')) ?></div>
          <div class="stat-label">Vacant</div>
        </div>
      </div>

      
      <div class="filter-bar">
        <div class="input-group search-box">
          <i class="bi bi-search input-icon"></i>
          <input type="search" class="form-control" placeholder="Search apartments…" data-search-table="aptTable">
        </div>
        <select class="form-control" style="width:auto;" id="statusFilter" onchange="filterTable()">
          <option value="">All Statuses</option>
          <option value="Active">Active</option>
          <option value="Inactive">Inactive</option>
        </select>
      </div>

      
      <div class="card">
        <div class="card-body p-0">
          <div class="table-wrapper">
            <table class="rms-table" id="aptTable">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Apartment</th>
                  <th>Location</th>
                  <th>Units</th>
                  <th>Occupied</th>
                  <th>Vacant</th>
                  <th>Occupancy</th>
                  <th>Caretaker</th>
                  <th>Status</th>
                  <th class="col-actions">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($allApts)): ?>
                <tr>
                  <td colspan="10" style="text-align:center;padding:40px;color:var(--text-muted);">
                    <i class="bi bi-building" style="font-size:2.5rem;display:block;margin-bottom:10px;"></i>
                    No apartments yet. <a href="#" data-modal-open="aptModal">Add your first apartment</a>.
                  </td>
                </tr>
                <?php else: ?>
                  <?php foreach ($allApts as $i => $apt):
                    $occ_pct = $apt['total_units'] > 0
                      ? round($apt['occupied'] / $apt['total_units'] * 100) : 0;
                  ?>
                  <tr data-status="<?= $apt['status'] ?>">
                    <td><?= $i + 1 ?></td>
                    <td>
                      <a href="apartments.php?view=<?= $apt['id'] ?>" style="font-weight:700;color:var(--primary);">
                        <?= htmlspecialchars($apt['name']) ?>
                      </a>
                    </td>
                    <td>
                      <i class="bi bi-geo-alt" style="color:var(--primary);"></i>
                      <?= htmlspecialchars($apt['location']) ?>
                      <?php if ($apt['county']): ?>
                        <div style="font-size:.72rem;color:var(--text-muted);"><?= htmlspecialchars($apt['county']) ?></div>
                      <?php endif; ?>
                    </td>
                    <td class="fw-bold"><?= $apt['total_units'] ?></td>
                    <td><span style="color:#7ED321;font-weight:700;"><?= $apt['occupied'] ?></span></td>
                    <td><span style="color:#f59e0b;font-weight:700;"><?= $apt['vacant'] ?></span></td>
                    <td>
                      <div style="display:flex;align-items:center;gap:8px;">
                        <div class="progress" style="width:70px;height:6px;">
                          <div class="progress-bar <?= $occ_pct >= 80 ? 'success' : ($occ_pct >= 50 ? 'warning' : 'danger') ?>"
                               style="width:<?= $occ_pct ?>%"></div>
                        </div>
                        <span style="font-size:.75rem;font-weight:700;"><?= $occ_pct ?>%</span>
                      </div>
                    </td>
                    <td><?= htmlspecialchars($apt['caretaker_name']) ?></td>
                    <td>
                      <span class="badge <?= $apt['status'] === 'Active' ? 'badge-active' : 'badge-inactive' ?>">
                        <span class="dot"></span><?= $apt['status'] ?>
                      </span>
                    </td>
                    <td class="col-actions">
                      <div class="d-flex gap-2" style="justify-content:flex-end;">
                        <a href="apartments.php?view=<?= $apt['id'] ?>" class="btn btn-secondary btn-sm btn-icon" title="View"><i class="bi bi-eye"></i></a>
                        <button class="btn btn-outline btn-sm btn-icon" title="Edit"
                          onclick="editApartment(<?= htmlspecialchars(json_encode($apt)) ?>)"><i class="bi bi-pencil"></i></button>
                        <form method="POST" action="../includes/save_apartment.php" style="display:inline;"
                          onsubmit="return confirm('<?= $apt['status']==='Active' ? 'Deactivate' : 'Reactivate' ?> this apartment?')">
                          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(rmsCsrfToken()) ?>">
                          <input type="hidden" name="edit_id" value="<?= $apt['id'] ?>">
                          <input type="hidden" name="name"    value="<?= htmlspecialchars($apt['name']) ?>">
                          <input type="hidden" name="location" value="<?= htmlspecialchars($apt['location']) ?>">
                          <input type="hidden" name="status"  value="<?= $apt['status'] === 'Active' ? 'Inactive' : 'Active' ?>">
                          <button type="submit" class="btn <?= $apt['status']==='Active' ? 'btn-warning' : 'btn-success' ?> btn-sm btn-icon"
                            title="<?= $apt['status']==='Active' ? 'Deactivate' : 'Reactivate' ?>">
                            <i class="bi bi-<?= $apt['status']==='Active' ? 'pause-circle' : 'play-circle' ?>"></i>
                          </button>
                        </form>
                        <form method="POST" action="../includes/save_apartment.php" style="display:inline;"
                          onsubmit="return confirm('⚠️ DANGER: Delete apartment &quot;<?= htmlspecialchars($apt['name']) ?>&quot;?\n\nThis will permanently delete:\n• All units in this apartment\n• All payment records\n• All tenant assignments\n• All related data\n\nThis action CANNOT be undone!\n\nType YES in the confirmation to proceed.')">
                          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(rmsCsrfToken()) ?>">
                          <input type="hidden" name="action" value="delete">
                          <input type="hidden" name="apartment_id" value="<?= $apt['id'] ?>">
                          <button type="submit" class="btn btn-danger btn-sm btn-icon" title="Delete Apartment (Permanent!)">
                            <i class="bi bi-trash"></i>
                          </button>
                        </form>
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


<div class="modal-overlay" id="aptModal">
  <div class="modal modal-lg">
    <div class="modal-header">
      <h3 id="aptModalTitle"><i class="bi bi-building-add"></i> Add New Apartment</h3>
      <button class="modal-close" data-modal-close><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="modal-body">
      <form action="../includes/save_apartment.php" method="POST" data-validate id="aptForm">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(rmsCsrfToken()) ?>">
        <input type="hidden" name="edit_id" id="aptEditId" value="">

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Apartment Name <span class="required">*</span></label>
            <input type="text" name="name" id="aptName" class="form-control" placeholder="e.g. Sunrise Apartments" required>
          </div>
          <div class="form-group">
            <label class="form-label">Location / Address <span class="required">*</span></label>
            <input type="text" name="location" id="aptLocation" class="form-control" placeholder="e.g. Kilimani, Nairobi" required>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">County</label>
            <input type="text" name="county" id="aptCounty" class="form-control" placeholder="e.g. Nairobi County">
          </div>
          <div class="form-group">
            <label class="form-label">Town / City</label>
            <input type="text" name="town" id="aptTown" class="form-control" placeholder="e.g. Nairobi">
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Estate / Area</label>
            <input type="text" name="estate" id="aptEstate" class="form-control" placeholder="e.g. Kilimani">
          </div>
          <div class="form-group">
            <label class="form-label">Street / Road</label>
            <input type="text" name="street" id="aptStreet" class="form-control" placeholder="e.g. Ngong Road">
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Assign Caretaker</label>
            <select name="caretaker_id" id="aptCaretaker" class="form-control">
              <option value="">— Unassigned —</option>
              <?php foreach ($caretakers as $ct): ?>
              <option value="<?= $ct['id'] ?>"><?= htmlspecialchars($ct['full_name']) ?>
                <?= $ct['apartment_id'] ? ' (currently: ' . htmlspecialchars($ct['apartment_name']) . ')' : ' (free)' ?>
              </option>
              <?php endforeach; ?>
            </select>
            <div class="form-hint">A caretaker can only be assigned to one apartment at a time.</div>
          </div>
          <div class="form-group">
            <label class="form-label">Status</label>
            <select name="status" id="aptStatus" class="form-control">
              <option value="Active">Active</option>
              <option value="Inactive">Inactive</option>
            </select>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">
              <span id="numUnitsLabel">Number of Units to Create</span>
              <span style="color:#f59e0b;">*</span>
            </label>
            <input type="number" name="num_units" id="aptNumUnits" class="form-control" 
                   placeholder="e.g. 4, 80, or 1500" min="0" max="5000" value="0">
            <div class="form-hint" id="numUnitsHint">
              <i class="bi bi-info-circle"></i> Enter any number (4 for small, 80 for medium, 1500 for towers). 
              Units distributed across floors automatically.
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Units Per Floor</label>
            <input type="number" name="units_per_floor" id="aptUnitsPerFloor" class="form-control" 
                   placeholder="e.g. 2, 4, 8, or 20" min="1" max="50" value="4">
            <div class="form-hint">Typical: 2-4 (small), 6-10 (medium), 12-20 (large)</div>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Ground Floor Type</label>
            <select name="ground_floor_type" id="aptGroundType" class="form-control" onchange="updateUnitTypeOptions()">
              <option value="none">No Ground Floor Units</option>
              <option value="shops">Shops (Commercial)</option>
              <option value="offices">Offices (Commercial)</option>
              <option value="mixed">Mixed (Shops + Offices)</option>
              <option value="residential">Residential (same as upper floors)</option>
            </select>
            <div class="form-hint">What type of units on the ground floor?</div>
          </div>
          <div class="form-group">
            <label class="form-label">Upper Floor Unit Type <span class="required">*</span></label>
            <select name="unit_type" id="aptUnitType" class="form-control" required>
              <optgroup label="Residential">
                <option value="Bedsitter">Bedsitter</option>
                <option value="Studio">Studio</option>
                <option value="1 Bedroom">1 Bedroom</option>
                <option value="2 Bedroom">2 Bedroom</option>
                <option value="3 Bedroom">3 Bedroom</option>
                <option value="4 Bedroom">4 Bedroom</option>
                <option value="Self-Contained">Self-Contained</option>
                <option value="Penthouse">Penthouse</option>
                <option value="Maisonette">Maisonette</option>
              </optgroup>
              <optgroup label="Commercial">
                <option value="Shop">Shop</option>
                <option value="Office">Office</option>
                <option value="Other">Other</option>
              </optgroup>
            </select>
            <div class="form-hint" id="unitTypeHint">All units on every floor will be this type. You can change individual units later.</div>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Monthly Rent per Unit (KES)</label>
          <input type="number" name="default_rent" id="aptDefaultRent" class="form-control"
                 placeholder="e.g. 15000" min="0.01" step="0.01">
          <div class="form-hint">When editing, enter a positive amount to update every existing unit in this apartment. Leave blank to keep current rents.</div>
        </div>

        <div class="form-group">
          <label class="form-label">Description</label>
          <textarea name="description" id="aptDesc" class="form-control" rows="3"
            placeholder="Brief description of the apartment…"></textarea>
        </div>

        <div class="modal-footer" style="padding:0;margin-top:8px;">
          <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
          <button type="submit" class="btn btn-primary" id="aptSubmitBtn">
            <i class="bi bi-check-lg"></i> Save Apartment
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="../assets/js/main.js"></script>
<script>

function editApartment(apt) {
  document.getElementById('aptModalTitle').innerHTML = '<i class="bi bi-pencil-square"></i> Edit Apartment';
  document.getElementById('aptSubmitBtn').innerHTML  = '<i class="bi bi-check-lg"></i> Update Apartment';
  document.getElementById('aptEditId').value    = apt.id;
  document.getElementById('aptName').value      = apt.name       || '';
  document.getElementById('aptLocation').value  = apt.location   || '';
  document.getElementById('aptCounty').value    = apt.county     || '';
  document.getElementById('aptTown').value      = apt.town       || '';
  document.getElementById('aptEstate').value    = apt.estate     || '';
  document.getElementById('aptStreet').value    = apt.street     || '';
  document.getElementById('aptDesc').value      = apt.description|| '';
  document.getElementById('aptStatus').value    = apt.status     || 'Active';
  document.getElementById('aptDefaultRent').required = false;

  const ctSel = document.getElementById('aptCaretaker');
  if (ctSel && apt.caretaker_id) {
    ctSel.value = apt.caretaker_id;
  }
  
  
  const numUnitsInput = document.getElementById('aptNumUnits');
  numUnitsInput.value = 0;
  numUnitsInput.closest('.form-row').style.display = 'none';
  openModal('aptModal');
}


<?php if ($editApt): ?>
editApartment(<?= json_encode($editApt) ?>);

document.getElementById('aptNumUnits').closest('.form-row').style.display = 'none';
<?php endif; ?>


<?php if (isset($_GET['action']) && $_GET['action'] === 'add'): ?>
openModal('aptModal');

document.getElementById('aptNumUnits').closest('.form-row').style.display = '';
<?php endif; ?>


document.querySelector('[data-modal-open="aptModal"]')?.addEventListener('click', function() {
  document.getElementById('aptForm').reset();
  document.getElementById('aptEditId').value = '';
  document.getElementById('aptModalTitle').innerHTML = '<i class="bi bi-building-add"></i> Add New Apartment';
  document.getElementById('aptSubmitBtn').innerHTML = '<i class="bi bi-check-lg"></i> Save Apartment';
  document.getElementById('aptNumUnits').closest('.form-row').style.display = '';
  document.getElementById('aptDefaultRent').required = true;
  // 
  updateUnitTypeOptions();
});

document.getElementById('aptNumUnits')?.addEventListener('input', function() {
  if (document.getElementById('aptEditId').value) {
    document.getElementById('aptDefaultRent').required = Number(this.value) > 0;
  }
});

function filterTable() {
  const val = document.getElementById('statusFilter').value.toLowerCase();
  document.querySelectorAll('#aptTable tbody tr[data-status]').forEach(row => {
    row.style.display = (!val || row.dataset.status.toLowerCase() === val) ? '' : 'none';
  });
}


function updateUnitTypeOptions() {
  const ground      = document.getElementById('aptGroundType').value;
  const unitTypeSel = document.getElementById('aptUnitType');
  const hint        = document.getElementById('unitTypeHint');

  if (ground === 'shops') {
    unitTypeSel.value = 'Shop';
    hint.innerHTML = '<span style="color:#f59e0b;font-weight:600;"><i class="bi bi-shop"></i> Ground = Shops selected. Upper floors set to Shop — change if upper floors are residential.</span>';
  } else if (ground === 'offices' || ground === 'mixed') {
    unitTypeSel.value = 'Office';
    hint.innerHTML = '<span style="color:#f59e0b;font-weight:600;"><i class="bi bi-building"></i> Commercial ground floor. Upper floors set to Office — change if needed.</span>';
  } else if (ground === 'residential') {
    if (!unitTypeSel.value || unitTypeSel.value === 'Office' || unitTypeSel.value === 'Shop') {
      unitTypeSel.value = '1 Bedroom';
    }
    hint.textContent = 'All units on every floor will be this type. You can change individual units later.';
  } else if (ground === 'none') {
    if (!unitTypeSel.value || unitTypeSel.value === 'Office' || unitTypeSel.value === 'Shop') {
      unitTypeSel.value = '1 Bedroom';
    }
    hint.textContent = 'All units on every floor will be this type. You can change individual units later.';
  }
}
</script>
</body>
</html>
