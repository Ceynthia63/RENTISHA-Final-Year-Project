<?php
session_start();
require_once '../includes/auth_check.php';
require_once '../includes/db_helpers.php';
requireRole('admin');
$pdo = getDB();

$pageTitle  = 'Tenant Management';
$activePage = 'tenants';


$filterStatus = $_GET['status'] ?? '';
$filterApt    = (int)($_GET['apt'] ?? 0) ?: null;


$tenants    = getTenants($filterStatus ?: null, $filterApt);
$apartments = getApartments();
$vacantUnits= getVacantUnits();
$pendingApplications = [];
if (!$filterStatus || $filterStatus === 'pending_approval') {
    $pendingSql = "SELECT u.id, u.full_name, u.email, u.phone, u.created_at,
                          a.name AS apartment_name
                   FROM users u
                   JOIN apartments a ON a.id = u.preferred_apartment_id
                   WHERE u.role='tenant' AND u.status='pending_approval'";
    $pendingParams = [];
    if ($filterApt) {
        $pendingSql .= " AND u.preferred_apartment_id=:apt";
        $pendingParams[':apt'] = $filterApt;
    }
    $pendingSql .= " ORDER BY u.created_at ASC, u.id ASC";
    $pendingStmt = $pdo->prepare($pendingSql);
    $pendingStmt->execute($pendingParams);
    $pendingApplications = $pendingStmt->fetchAll(PDO::FETCH_ASSOC);
}

$viewTenant = null;
if (!empty($_GET['view'])) {
    $viewTenant = getTenantById((int)$_GET['view']);
    if ($viewTenant) {
        $tenantPayments  = getTenantPaymentHistory((int)$_GET['view']);
        $tenantYearly    = getTenantYearlyPayments((int)$_GET['view'], (int)date('Y'));
        $tenantMaint     = getMaintenanceRequests(['tenant_id' => (int)$_GET['view']]);
        $tenantOutstanding = getTenantOutstanding((int)$_GET['view']);
    }
}


$success = $_GET['success'] ?? '';
$error   = $_GET['error']   ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Tenants — Rentisha Admin</title>
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

      <?php if ($viewTenant): ?>
      
      <div class="page-header">
        <div>
          <h1><?= htmlspecialchars($viewTenant['full_name']) ?></h1>
          <p>
            <span class="badge <?= statusBadgeClass($viewTenant['status']) ?>"><?= ucfirst($viewTenant['status']) ?></span>
            &nbsp;<?= $viewTenant['apartment_name'] !== '—' ? htmlspecialchars($viewTenant['apartment_name'] . ' · Unit ' . $viewTenant['unit_number']) : 'No active unit' ?>
          </p>
        </div>
        <div class="d-flex gap-2">
          <a href="tenants.php" class="btn btn-outline btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
          <?php if ($viewTenant['status'] === 'active' && $viewTenant['unit_id']): ?>
          <button class="btn btn-warning btn-sm" data-modal-open="moveOutModal">
            <i class="bi bi-box-arrow-right"></i> Process Move-Out
          </button>
          <?php endif; ?>
          <?php if ($viewTenant['status'] === 'active' && !$viewTenant['unit_id']): ?>
          <button class="btn btn-primary btn-sm" data-modal-open="moveInModal">
            <i class="bi bi-house-door"></i> Assign Unit
          </button>
          <?php endif; ?>
        </div>
      </div>

      <div class="grid-2 mb-4">
        
        <div class="card">
          <div class="card-header">
            <h3 class="card-title"><i class="bi bi-person-circle"></i> Personal Information</h3>
            <button class="btn btn-outline btn-sm" onclick="openEditTenantModal(<?= htmlspecialchars(json_encode($viewTenant)) ?>)">
              <i class="bi bi-pencil"></i> Edit
            </button>
          </div>
          <div class="card-body">
            <div style="display:flex;align-items:center;gap:14px;margin-bottom:18px;">
              <?php if ($viewTenant['avatar']): ?>
              <img src="../<?= htmlspecialchars($viewTenant['avatar']) ?>" style="width:64px;height:64px;border-radius:50%;object-fit:cover;border:2px solid var(--primary);" alt="">
              <?php else: ?>
              <div style="width:64px;height:64px;border-radius:50%;background:linear-gradient(135deg,#007BFF,#08CFFF);display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.5rem;font-weight:800;">
                <?= strtoupper(substr($viewTenant['full_name'],0,1)) ?>
              </div>
              <?php endif; ?>
              <div>
                <div style="font-size:1.1rem;font-weight:700;"><?= htmlspecialchars($viewTenant['full_name']) ?></div>
                <div style="font-size:.8rem;color:var(--text-muted);">Registered <?= formatDate($viewTenant['registered_at']) ?></div>
              </div>
            </div>
            <?php $info = [
              ['Phone',    $viewTenant['phone'],              'bi-phone'],
              ['Email',    $viewTenant['email'],              'bi-envelope'],
              ['Apartment',$viewTenant['apartment_name'],     'bi-building'],
              ['Unit',     $viewTenant['unit_number'],        'bi-door-open'],
              ['Unit Type',$viewTenant['unit_type'] ?? '—',   'bi-house'],
              ['Monthly Rent', $viewTenant['unit_id'] ? formatKES($viewTenant['unit_rent'] ?? 0) : '—', 'bi-cash'],
              ['Move-in Date', formatDate($viewTenant['move_in_date']),   'bi-calendar-check'],
              ['Move-out Date',formatDate($viewTenant['move_out_date']),  'bi-calendar-x'],
              ['Caretaker',   $viewTenant['caretaker_name'] ?? '—',      'bi-person-badge'],
              ['Emergency',   $viewTenant['emergency_contact'] ?? '—',   'bi-telephone-plus'],
            ]; ?>
            <table style="width:100%;border-collapse:collapse;font-size:.85rem;">
              <?php foreach ($info as [$label,$val,$icon]): ?>
              <tr style="border-bottom:1px solid var(--border);">
                <td style="padding:7px 0;color:var(--text-muted);width:38%;"><i class="bi <?= $icon ?>" style="margin-right:5px;"></i><?= $label ?></td>
                <td style="padding:7px 0;font-weight:600;"><?= htmlspecialchars($val ?? '—') ?></td>
              </tr>
              <?php endforeach; ?>
            </table>
          </div>
        </div>

        
        <div>
          
          <?php if ($tenantOutstanding['total'] > 0): ?>
          <div class="alert alert-danger mb-4">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <div>
              <strong>Outstanding Balance: <?= formatKES($tenantOutstanding['total']) ?></strong><br>
              <span style="font-size:.82rem;">
                Earliest unpaid:
                <?= $tenantOutstanding['earliest']['month'] ?? '' ?>
                <?= $tenantOutstanding['earliest']['year']  ?? '' ?>
              </span>
            </div>
          </div>
          <?php endif; ?>

          
          <div class="card mb-4">
            <div class="card-header">
              <h3 class="card-title"><i class="bi bi-calendar3"></i> <?= date('Y') ?> Payment Record</h3>
              <a href="payments.php?tenant=<?= $viewTenant['id'] ?>" class="btn btn-secondary btn-sm">Full History</a>
            </div>
            <div class="card-body">
              <div class="months-grid">
                <?php foreach ($tenantYearly as $month => $p): ?>
                <div class="month-cell <?= $p['status'] ? strtolower($p['status']) : '' ?>">
                  <div class="month-name"><?= substr($month,0,3) ?></div>
                  <div class="month-amount" style="font-size:.78rem;">
                    <?= $p['amount'] ? formatKES($p['amount']) : '—' ?>
                  </div>
                  <div class="month-status" style="margin-top:4px;">
                    <?php if ($p['status'] === 'Paid'): ?>
                      <span class="badge badge-paid" style="font-size:.6rem;">Paid</span>
                    <?php elseif ($p['status'] === 'Pending'): ?>
                      <span class="badge badge-pending" style="font-size:.6rem;">Due</span>
                    <?php elseif ($p['status'] === 'Overdue'): ?>
                      <span class="badge badge-overdue" style="font-size:.6rem;">Late</span>
                    <?php else: ?>
                      <span style="color:var(--text-muted);font-size:.68rem;">—</span>
                    <?php endif; ?>
                  </div>
                </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>

        
          <div class="card">
            <div class="card-header">
              <h3 class="card-title"><i class="bi bi-wrench"></i> Maintenance (<?= count($tenantMaint) ?>)</h3>
            </div>
            <div class="card-body" style="padding:10px;">
              <?php if (empty($tenantMaint)): ?>
              <p style="color:var(--text-muted);font-size:.85rem;text-align:center;padding:10px;">No maintenance requests.</p>
              <?php else: ?>
                <?php foreach (array_slice($tenantMaint,0,3) as $m): ?>
                <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid var(--border);font-size:.83rem;">
                  <span><?= htmlspecialchars($m['title']) ?></span>
                  <span class="badge <?= statusBadgeClass($m['status']) ?>"><?= $m['status'] ?></span>
                </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>

      <?php else: ?>
      
      <div class="page-header">
        <div>
          <h1>Tenant Management</h1>
          <p>Add, assign, manage and track all tenants across your properties</p>
        </div>
        <button class="btn btn-primary" data-modal-open="addTenantModal">
          <i class="bi bi-person-plus-fill"></i> Add Tenant
        </button>
      </div>

      
      <?php
        $allT  = getTenants();
        $cntActive  = count(array_filter($allT, fn($t) => $t['status']==='active'));
        $cntProsp   = count(array_filter($allT, fn($t) => $t['status']==='prospective'));
        $cntMoved   = count(array_filter($allT, fn($t) => $t['status']==='moved_out'));
        $cntOverdue = count(array_filter($allT, fn($t) => $t['rent_status']==='Overdue'));
      ?>
      <div class="stats-grid" style="grid-template-columns:repeat(auto-fill,minmax(140px,1fr));margin-bottom:18px;">
        <div class="stat-card green">
          <div class="stat-icon"><i class="bi bi-person-check"></i></div>
          <div class="stat-value" data-count="<?= $cntActive ?>"><?= $cntActive ?></div>
          <div class="stat-label">Active Tenants</div>
        </div>
        <div class="stat-card orange">
          <div class="stat-icon"><i class="bi bi-person-plus"></i></div>
          <div class="stat-value" data-count="<?= $cntProsp ?>"><?= $cntProsp ?></div>
          <div class="stat-label">Prospective</div>
        </div>
        <div class="stat-card">
          <div class="stat-icon"><i class="bi bi-box-arrow-right"></i></div>
          <div class="stat-value" data-count="<?= $cntMoved ?>"><?= $cntMoved ?></div>
          <div class="stat-label">Moved Out</div>
        </div>
        <div class="stat-card red">
          <div class="stat-icon"><i class="bi bi-exclamation-circle"></i></div>
          <div class="stat-value" data-count="<?= $cntOverdue ?>"><?= $cntOverdue ?></div>
          <div class="stat-label">Overdue Rent</div>
        </div>
      </div>

      
      <div class="filter-bar">
        <div class="input-group search-box">
          <i class="bi bi-search input-icon"></i>
          <input type="search" class="form-control" placeholder="Search by name, phone, email, unit…" data-search-table="tenantTable">
        </div>
        <select class="form-control" style="width:auto;" onchange="location='tenants.php?status='+this.value+(<?= $filterApt ? "'&apt=".$filterApt."'" : "''" ?>)">
          <option value="" <?= !$filterStatus?'selected':'' ?>>All Statuses</option>
          <option value="pending_approval" <?= $filterStatus==='pending_approval'?'selected':'' ?>>Pending Approval</option>
          <option value="active"      <?= $filterStatus==='active'?'selected':'' ?>>Active</option>
          <option value="prospective" <?= $filterStatus==='prospective'?'selected':'' ?>>Prospective</option>
          <option value="moved_out"   <?= $filterStatus==='moved_out'?'selected':'' ?>>Moved Out</option>
          <option value="inactive"    <?= $filterStatus==='inactive'?'selected':'' ?>>Inactive</option>
          <option value="suspended"   <?= $filterStatus==='suspended'?'selected':'' ?>>Suspended</option>
        </select>
        <select class="form-control" style="width:auto;" onchange="location='tenants.php?apt='+this.value+(<?= $filterStatus ? "'&status=".$filterStatus."'" : "''" ?>)">
          <option value="" <?= !$filterApt?'selected':'' ?>>All Apartments</option>
          <?php foreach ($apartments as $apt): ?>
          <option value="<?= $apt['id'] ?>" <?= $filterApt===$apt['id']?'selected':'' ?>>
            <?= htmlspecialchars($apt['name']) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>

      <?php if (!empty($pendingApplications)): ?>
      <div class="card mb-4">
        <div class="card-header">
          <h3 class="card-title"><i class="bi bi-hourglass-split"></i> Pending Tenant Applications (<?= count($pendingApplications) ?>)</h3>
        </div>
        <div class="card-body p-0">
          <div class="table-wrapper">
            <table class="rms-table">
              <thead><tr><th>Applicant</th><th>Contact</th><th>Apartment Requested</th><th>Applied</th><th class="col-actions">Actions</th></tr></thead>
              <tbody>
              <?php foreach ($pendingApplications as $application): ?>
                <tr>
                  <td class="fw-bold"><?= htmlspecialchars($application['full_name']) ?></td>
                  <td><?= htmlspecialchars($application['phone']) ?><br><span class="text-muted fs-sm"><?= htmlspecialchars($application['email']) ?></span></td>
                  <td><span class="badge badge-primary"><?= htmlspecialchars($application['apartment_name']) ?></span></td>
                  <td><?= formatDate($application['created_at']) ?></td>
                  <td class="col-actions">
                    <form method="POST" action="../includes/save_tenant_updated.php" style="display:inline;" onsubmit="return confirm('Approve this tenant application?')">
                      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(rmsCsrfToken()) ?>">
                      <input type="hidden" name="action" value="approve">
                      <input type="hidden" name="tenant_id" value="<?= (int)$application['id'] ?>">
                      <button class="btn btn-primary btn-sm"><i class="bi bi-check-lg"></i> Approve</button>
                    </form>
                    <form method="POST" action="../includes/save_tenant_updated.php" style="display:inline;" onsubmit="return confirm('Reject this tenant application?')">
                      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(rmsCsrfToken()) ?>">
                      <input type="hidden" name="action" value="reject">
                      <input type="hidden" name="tenant_id" value="<?= (int)$application['id'] ?>">
                      <button class="btn btn-danger btn-sm"><i class="bi bi-x-lg"></i> Reject</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
      <?php endif; ?>

      
      <div class="card">
        <div class="card-body p-0">
          <div class="table-wrapper">
            <table class="rms-table" id="tenantTable">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Tenant</th>
                  <th>Contact</th>
                  <th>Unit</th>
                  <th>Apartment</th>
                  <th>Rent / Month</th>
                  <th>Move-in</th>
                  <th>Rent Status</th>
                  <th>Account</th>
                  <th class="col-actions">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($tenants)): ?>
                <tr>
                  <td colspan="10" style="text-align:center;padding:36px;color:var(--text-muted);">
                    <i class="bi bi-people" style="font-size:2.5rem;display:block;margin-bottom:8px;"></i>
                    No tenants found<?= $filterStatus ? ' with status "'.htmlspecialchars($filterStatus).'"' : '' ?>.
                    <?php if (!$filterStatus): ?>
                    <br><a href="#" data-modal-open="addTenantModal">Add the first tenant</a>.
                    <?php endif; ?>
                  </td>
                </tr>
                <?php else: ?>
                  <?php foreach ($tenants as $i => $t): ?>
                  <tr>
                    <td><?= $i + 1 ?></td>
                    <td>
                      <div style="display:flex;align-items:center;gap:10px;">
                        <?php if ($t['avatar']): ?>
                        <img src="../<?= htmlspecialchars($t['avatar']) ?>" style="width:32px;height:32px;border-radius:50%;object-fit:cover;flex-shrink:0;" alt="">
                        <?php else: ?>
                        <div style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,#007BFF,#08CFFF);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:.8rem;flex-shrink:0;">
                          <?= strtoupper(substr($t['full_name'],0,1)) ?>
                        </div>
                        <?php endif; ?>
                        <div>
                          <a href="tenants.php?view=<?= $t['id'] ?>" style="font-weight:600;color:var(--primary);">
                            <?= htmlspecialchars($t['full_name']) ?>
                          </a>
                          <div style="font-size:.72rem;color:var(--text-muted);"><?= htmlspecialchars($t['email'] ?? '') ?></div>
                        </div>
                      </div>
                    </td>
                    <td><?= htmlspecialchars($t['phone'] ?? '—') ?></td>
                    <td>
                      <?php if ($t['unit_number'] && $t['unit_number'] !== '—'): ?>
                      <span class="badge badge-info"><?= htmlspecialchars($t['unit_number']) ?></span>
                      <?php else: ?><span class="text-muted fs-sm">—</span><?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($t['apartment_name'] ?? '—') ?></td>
                    <td class="fw-bold">
                      <?= ($t['unit_rent'] || $t['assigned_rent']) ? formatKES($t['unit_rent'] ?? $t['assigned_rent']) : '—' ?>
                    </td>
                    <td><?= formatDate($t['move_in_date']) ?></td>
                    <td>
                      <?php if ($t['rent_status']): ?>
                        <span class="badge <?= statusBadgeClass($t['rent_status']) ?>"><?= $t['rent_status'] ?></span>
                      <?php elseif ($t['status'] === 'active'): ?>
                        <span class="badge badge-pending">No Record</span>
                      <?php else: ?>
                        <span class="text-muted fs-sm">—</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <span class="badge <?= statusBadgeClass($t['status']) ?>"><?= ucfirst($t['status']) ?></span>
                    </td>
                    <td class="col-actions">
                      <div class="d-flex gap-2" style="justify-content:flex-end;">
                        <a href="tenants.php?view=<?= $t['id'] ?>" class="btn btn-secondary btn-sm btn-icon" title="View"><i class="bi bi-eye"></i></a>
                        <button class="btn btn-outline btn-sm btn-icon" title="Edit"
                          onclick="openEditTenantModal(<?= htmlspecialchars(json_encode($t)) ?>)">
                          <i class="bi bi-pencil"></i>
                        </button>
                        <?php if ($t['status']==='active' && !$t['unit_id']): ?>
                        <button class="btn btn-primary btn-sm btn-icon" title="Assign Unit"
                          onclick="openMoveInModal(<?= $t['id'] ?>, '<?= htmlspecialchars($t['full_name']) ?>')">
                          <i class="bi bi-house-door"></i>
                        </button>
                        <?php endif; ?>
                        <?php if ($t['status']==='active' && $t['unit_id']): ?>
                        <button class="btn btn-warning btn-sm btn-icon" title="Move Out"
                          onclick="openMoveOutModal(<?= $t['id'] ?>, '<?= htmlspecialchars($t['full_name']) ?>', '<?= htmlspecialchars($t['unit_number']) ?>')">
                          <i class="bi bi-box-arrow-right"></i>
                        </button>
                        <?php endif; ?>
                        <?php if ($t['rent_status'] === 'Overdue' || $t['rent_status'] === 'Pending'): ?>
                        <a href="payments.php?remind=<?= $t['id'] ?>" class="btn btn-danger btn-sm btn-icon" title="Send Reminder">
                          <i class="bi bi-send"></i>
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

      <?php endif;  ?>

    </div>
  </div>
</div>

<
<div class="modal-overlay" id="addTenantModal">
  <div class="modal modal-lg">
    <div class="modal-header">
      <h3 id="tenantModalTitle"><i class="bi bi-person-plus-fill"></i> Add New Tenant</h3>
      <button class="modal-close" data-modal-close><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="modal-body">
      <form action="../includes/save_tenant.php" method="POST" enctype="multipart/form-data" data-validate id="tenantForm">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(rmsCsrfToken()) ?>">
        <input type="hidden" name="edit_id" id="tenantEditId" value="">

        
        <div class="avatar-upload-area" style="margin-bottom:20px;">
          <div class="avatar-preview" id="tenantAvatarPreview">
            <i class="bi bi-person-fill" style="font-size:2rem;color:var(--primary);"></i>
          </div>
          <div class="avatar-actions">
            <label class="btn btn-secondary btn-sm" for="tenantAvatarInput">
              <i class="bi bi-upload"></i> Upload Photo
            </label>
            <input type="file" id="tenantAvatarInput" name="avatar" class="avatar-file-input" accept="image/*" style="display:none;">
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Full Name <span class="required">*</span></label>
            <input type="text" name="full_name" id="tName" class="form-control" placeholder="e.g. Jane Wanjiku" required>
          </div>
          <div class="form-group">
            <label class="form-label">Email Address</label>
            <input type="email" name="email" id="tEmail" class="form-control" placeholder="jane@email.com">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Phone Number <span class="required">*</span></label>
            <input type="tel" name="phone" id="tPhone" class="form-control" placeholder="07XX XXX XXX" pattern="(?:0[17][0-9]{8}|\+254[17][0-9]{8}|00254[17][0-9]{8})" maxlength="13" inputmode="tel" title="Enter a valid Kenyan phone number" required>
          </div>
          <div class="form-group">
            <label class="form-label">National ID / Passport</label>
            <input type="text" name="id_number" id="tIdNum" class="form-control" placeholder="ID Number">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Emergency Contact</label>
            <input type="text" name="emergency_contact" id="tEmergency" class="form-control" placeholder="Name — 07XX XXX XXX">
          </div>
          <div class="form-group">
            <label class="form-label">Account Status</label>
            <select name="status" id="tStatus" class="form-control">
              <option value="active">Active</option>
              <option value="prospective">Prospective</option>
              <option value="inactive">Inactive</option>
              <option value="suspended">Suspended</option>
            </select>
          </div>
        </div>

        <hr style="margin:14px 0;border-color:var(--border);">
        <p style="font-size:.8rem;color:var(--text-muted);margin-bottom:12px;">
          <i class="bi bi-house-door"></i> Unit assignment (leave blank to assign later):
        </p>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Assign to Unit</label>
            <select name="unit_id" id="tUnit" class="form-control">
              <option value="">— Assign Later —</option>
              <?php foreach ($vacantUnits as $vu): ?>
              <option value="<?= $vu['id'] ?>">
                <?= htmlspecialchars($vu['unit_number']) ?> — <?= htmlspecialchars($vu['apartment_name']) ?>
                (<?= htmlspecialchars($vu['unit_type']) ?> · <?= formatKES($vu['monthly_rent']) ?>)
              </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Move-in Date</label>
            <input type="date" name="move_in_date" id="tMoveIn" class="form-control" value="<?= date('Y-m-d') ?>">
          </div>
        </div>

        <hr style="margin:14px 0;border-color:var(--border);">
        <div class="form-row" id="passwordRow">
          <div class="form-group">
            <label class="form-label">Password <span class="required">*</span></label>
            <input type="password" name="password" id="tPassword" class="form-control" placeholder="Minimum 8 characters" required minlength="8">
          </div>
          <div class="form-group">
            <label class="form-label">Confirm Password <span class="required">*</span></label>
            <input type="password" name="confirm_password" id="tConfirm" class="form-control" placeholder="Repeat password" required>
          </div>
        </div>
        <span class="form-error" id="tPwdErr" style="display:none;margin-top:-10px;margin-bottom:8px;">
          <i class="bi bi-exclamation-circle"></i> Passwords do not match
        </span>

        <div class="modal-footer" style="padding:0;margin-top:10px;">
          <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
          <button type="submit" class="btn btn-primary" id="tenantSubmitBtn">
            <i class="bi bi-check-lg"></i> Save Tenant
          </button>
        </div>
      </form>
    </div>
  </div>
</div>


<div class="modal-overlay" id="moveInModal">
  <div class="modal">
    <div class="modal-header">
      <h3><i class="bi bi-house-door-fill"></i> Assign Unit — Move In</h3>
      <button class="modal-close" data-modal-close><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="modal-body">
      <form action="../includes/move_in.php" method="POST" data-validate>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(rmsCsrfToken()) ?>">
        <input type="hidden" name="tenant_id" id="moveInTenantId" value="">

        <div class="alert alert-info mb-4">
          <i class="bi bi-info-circle-fill"></i>
          <div>Assigning unit to: <strong id="moveInTenantName"></strong></div>
        </div>

        <div class="form-group">
          <label class="form-label">Select Vacant Unit <span class="required">*</span></label>
          <select name="unit_id" class="form-control" required>
            <option value="">— Select Unit —</option>
            <?php foreach ($vacantUnits as $vu): ?>
            <option value="<?= $vu['id'] ?>">
              <?= htmlspecialchars($vu['unit_number']) ?> —
              <?= htmlspecialchars($vu['apartment_name']) ?>
              (<?= htmlspecialchars($vu['unit_type']) ?> · <?= formatKES($vu['monthly_rent']) ?>)
            </option>
            <?php endforeach; ?>
          </select>
          <?php if (empty($vacantUnits)): ?>
          <div class="form-hint" style="color:var(--danger);">No vacant units available. <a href="units.php?action=add">Add a unit first</a>.</div>
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
          <button type="submit" class="btn btn-primary" <?= empty($vacantUnits) ? 'disabled' : '' ?>>
            <i class="bi bi-house-door"></i> Confirm Move-In
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal-overlay" id="moveOutModal">
  <div class="modal">
    <div class="modal-header">
      <h3><i class="bi bi-box-arrow-right"></i> Process Move-Out</h3>
      <button class="modal-close" data-modal-close><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="modal-body">
      <form action="../includes/move_out.php" method="POST" data-validate>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(rmsCsrfToken()) ?>">
        <input type="hidden" name="tenant_id" id="moveOutTenantId" value="<?= $viewTenant['id'] ?? '' ?>">

        <div class="alert alert-warning mb-4">
          <i class="bi bi-exclamation-triangle-fill"></i>
          <div>
            Moving out: <strong id="moveOutTenantName"><?= htmlspecialchars($viewTenant['full_name'] ?? '') ?></strong>
            from Unit <strong id="moveOutUnit"><?= htmlspecialchars($viewTenant['unit_number'] ?? '') ?></strong>.<br>
            <small>This will mark the unit as Vacant and the tenant as Moved Out. History is retained.</small>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Move-out Date <span class="required">*</span></label>
          <input type="date" name="move_out_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">Reason for Moving Out</label>
          <select name="reason" class="form-control">
            <option value="">— Select Reason —</option>
            <option>End of lease</option>
            <option>Tenant request</option>
            <option>Eviction</option>
            <option>Transfer</option>
            <option>Other</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Notes</label>
          <textarea name="notes" class="form-control" rows="2" placeholder="Any notes about this move-out…"></textarea>
        </div>

        <?php if (!empty($tenantOutstanding) && $tenantOutstanding['total'] > 0): ?>
        <div class="alert alert-danger">
          <i class="bi bi-cash-coin"></i>
          <div>
            Outstanding balance: <strong><?= formatKES($tenantOutstanding['total']) ?></strong>
            — please ensure this is cleared before or after move-out.
          </div>
        </div>
        <?php endif; ?>

        <div class="modal-footer" style="padding:0;margin-top:8px;">
          <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
          <button type="submit" class="btn btn-warning">
            <i class="bi bi-box-arrow-right"></i> Confirm Move-Out
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="../assets/js/main.js"></script>
<script>

function openEditTenantModal(t) {
  document.getElementById('tenantModalTitle').innerHTML = '<i class="bi bi-pencil-square"></i> Edit Tenant';
  document.getElementById('tenantSubmitBtn').innerHTML  = '<i class="bi bi-check-lg"></i> Update Tenant';
  document.getElementById('tenantEditId').value   = t.id;
  document.getElementById('tName').value          = t.full_name     || '';
  document.getElementById('tEmail').value         = t.email         || '';
  document.getElementById('tPhone').value         = t.phone         || '';
  document.getElementById('tIdNum').value         = t.id_number     || '';
  document.getElementById('tEmergency').value     = t.emergency_contact || '';
  document.getElementById('tStatus').value        = t.status        || 'active';
  document.getElementById('tMoveIn').value         = t.move_in_date  || '';
  
  document.getElementById('tPassword').required      = false;
  document.getElementById('tConfirm').required       = false;
  document.getElementById('passwordRow').style.display = 'none';
  openModal('addTenantModal');


function openMoveInModal(id, name) {
  document.getElementById('moveInTenantId').value   = id;
  document.getElementById('moveInTenantName').textContent = name;
  openModal('moveInModal');
}


function openMoveOutModal(id, name, unit) {
  document.getElementById('moveOutTenantId').value        = id;
  document.getElementById('moveOutTenantName').textContent = name;
  document.getElementById('moveOutUnit').textContent       = unit;
  openModal('moveOutModal');
}


document.querySelector('[data-modal-open="addTenantModal"]')?.addEventListener('click', function() {
  document.getElementById('passwordRow').style.display = '';
  document.getElementById('tPassword').required = true;
  document.getElementById('tConfirm').required  = true;
});

document.getElementById('tConfirm')?.addEventListener('input', function() {
  document.getElementById('tPwdErr').style.display =
    this.value === document.getElementById('tPassword').value ? 'none' : 'flex';
});


<?php if (isset($_GET['action']) && $_GET['action']==='add'): ?>
openModal('addTenantModal');
<?php endif; ?>
</script>
</body>
</html>
