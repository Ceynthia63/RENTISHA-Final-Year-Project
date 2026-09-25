<?php
session_start();
require_once '../includes/auth_check.php';
require_once '../includes/db_helpers.php';
requireRole('admin');

$pageTitle  = 'Caretakers';
$activePage = 'caretakers';

$pdo = getDB();

$activeTab = $_GET['tab'] ?? 'list';
if (!in_array($activeTab, ['list', 'history'], true)) {
    $activeTab = 'list';
}


$caretakers = getCaretakers();
$apartments = getApartments();


$caretakerHistory = $pdo->query(
    "SELECT * FROM caretaker_history ORDER BY removed_date DESC, created_at DESC"
)->fetchAll(PDO::FETCH_ASSOC);


$viewCaretaker = null;
if (!empty($_GET['view'])) {
    $viewCaretaker = getCaretakerById((int)$_GET['view']);
    if ($viewCaretaker && $viewCaretaker['apartment_id']) {
        $ctUnits    = getUnits((int)$viewCaretaker['apartment_id']);
        $ctTenants  = getTenants('active', (int)$viewCaretaker['apartment_id']);
        $ctMaint    = getMaintenanceRequests(['apartment_id' => (int)$viewCaretaker['apartment_id']]);
        $ctPayTotals= getMonthlyTotals((int)$viewCaretaker['apartment_id']);

  
        $pdo  = getDB();
        $hist = $pdo->prepare(
            "SELECT ca.*, a.name AS apartment_name,
                    ca.assigned_date, ca.removed_date, ca.status
             FROM caretaker_assignments ca
             JOIN apartments a ON a.id = ca.apartment_id
             WHERE ca.caretaker_id = :cid
             ORDER BY ca.assigned_date DESC"
        );
        $hist->execute([':cid' => $viewCaretaker['id']]);
        $ctHistory = $hist->fetchAll(PDO::FETCH_ASSOC);
    }
}

$success = $_GET['success'] ?? '';
$error   = $_GET['error']   ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Caretakers — Rentisha Admin</title>
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
      <div class="alert alert-success mb-4">
        <i class="bi bi-check-circle-fill"></i>
        <div>
          <?= htmlspecialchars($success) ?>
          <?php if (!empty($_GET['setup_url'])): ?>
          <div style="margin-top:10px;padding:12px;background:rgba(0,0,0,.2);border-radius:8px;border:1px solid rgba(255,255,255,.1);">
            <div style="font-size:.8rem;font-weight:600;margin-bottom:6px;">
              <i class="bi bi-link-45deg"></i>
              Account Setup Link for <strong><?= htmlspecialchars($_GET['setup_name'] ?? '') ?></strong>
            </div>
            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
              <input type="text" id="setupLinkInput" readonly
                value="<?= htmlspecialchars(urldecode($_GET['setup_url'])) ?>"
                style="flex:1;min-width:0;padding:6px 10px;background:rgba(0,0,0,.3);
                       border:1px solid rgba(255,255,255,.2);border-radius:6px;
                       color:#67e8f9;font-size:.75rem;font-family:monospace;">
              <button onclick="copySetupLink()" class="btn btn-sm btn-secondary"
                style="white-space:nowrap;padding:6px 12px;border-radius:6px;
                       background:rgba(8,207,255,.15);border:1px solid rgba(8,207,255,.3);
                       color:#67e8f9;font-size:.78rem;cursor:pointer;font-weight:600;">
                <i class="bi bi-clipboard" id="copyIcon"></i> Copy Link
              </button>
            </div>
            <div style="font-size:.72rem;color:rgba(255,255,255,.45);margin-top:6px;">
              <i class="bi bi-clock"></i> Valid for 72 hours.
              Share this link with the caretaker so they can set their own password.
            </div>
          </div>
          <?php endif; ?>
        </div>
      </div>
      <?php endif;
      if ($error): ?>
      <div class="alert alert-danger mb-4"><i class="bi bi-exclamation-circle-fill"></i><div><?= htmlspecialchars($error) ?></div></div>
      <?php endif; ?>

      <?php if ($viewCaretaker): ?>
      
      <div class="page-header">
        <div>
          <h1><?= htmlspecialchars($viewCaretaker['full_name']) ?></h1>
          <p>
            <span class="badge <?= statusBadgeClass($viewCaretaker['status']) ?>"><?= ucfirst($viewCaretaker['status']) ?></span>
            &nbsp;<?= $viewCaretaker['apartment_id']
              ? htmlspecialchars($viewCaretaker['apartment_name'] . ' — ' . $viewCaretaker['apartment_location'])
              : 'Not assigned to any apartment' ?>
          </p>
        </div>
        <div class="d-flex gap-2">
          <a href="caretakers.php" class="btn btn-outline btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
          <button class="btn btn-primary btn-sm"
            onclick="openEditModal(<?= htmlspecialchars(json_encode($viewCaretaker)) ?>)">
            <i class="bi bi-pencil"></i> Edit
          </button>
          <?php if ($viewCaretaker['status'] === 'active'): ?>
          <button class="btn btn-danger btn-sm" data-modal-open="removeCaretakerModal">
            <i class="bi bi-person-dash"></i> Remove Caretaker
          </button>
          <?php endif; ?>
        </div>
      </div>

      <div class="grid-2 mb-4">

        
        <div class="card">
          <div class="card-header"><h3 class="card-title"><i class="bi bi-person-badge"></i> Caretaker Profile</h3></div>
          <div class="card-body">
            <div style="display:flex;align-items:center;gap:14px;margin-bottom:18px;">
              <?php if ($viewCaretaker['avatar']): ?>
              <img src="../<?= htmlspecialchars($viewCaretaker['avatar']) ?>"
                   style="width:64px;height:64px;border-radius:50%;object-fit:cover;border:2px solid var(--primary);" alt="">
              <?php else: ?>
              <div style="width:64px;height:64px;border-radius:50%;background:linear-gradient(135deg,#007BFF,#08CFFF);display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.5rem;font-weight:800;">
                <?= strtoupper(substr($viewCaretaker['full_name'],0,1)) ?>
              </div>
              <?php endif; ?>
              <div>
                <div style="font-size:1.1rem;font-weight:700;"><?= htmlspecialchars($viewCaretaker['full_name']) ?></div>
                <div style="font-size:.8rem;color:var(--text-muted);">Added <?= formatDate($viewCaretaker['created_at']) ?></div>
              </div>
            </div>
            <?php $rows = [
              ['Phone',             $viewCaretaker['phone'],             'bi-phone'],
              ['Email',             $viewCaretaker['email'],             'bi-envelope'],
              ['Assigned Apartment',$viewCaretaker['apartment_name'],    'bi-building'],
              ['Apartment Location',$viewCaretaker['apartment_location'],'bi-geo-alt'],
              ['Assignment Date',   formatDate($viewCaretaker['assigned_date']), 'bi-calendar-check'],
              ['Account Status',    ucfirst($viewCaretaker['status']),   'bi-person-check'],
            ]; ?>
            <table style="width:100%;border-collapse:collapse;font-size:.85rem;">
              <?php foreach ($rows as [$label,$val,$icon]): ?>
              <tr style="border-bottom:1px solid var(--border);">
                <td style="padding:7px 0;color:var(--text-muted);width:40%;"><i class="bi <?= $icon ?>" style="margin-right:5px;"></i><?= $label ?></td>
                <td style="padding:7px 0;font-weight:600;"><?= htmlspecialchars($val ?? '—') ?></td>
              </tr>
              <?php endforeach; ?>
            </table>
          </div>
        </div>

        
        <?php if ($viewCaretaker['apartment_id']): ?>
        <div>
          <div class="stats-grid" style="grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
            <div class="stat-card blue" style="padding:14px;">
              <div class="stat-icon"><i class="bi bi-door-open"></i></div>
              <div class="stat-value" style="font-size:1.4rem;"><?= $viewCaretaker['total_units'] ?></div>
              <div class="stat-label">Total Units</div>
            </div>
            <div class="stat-card green" style="padding:14px;">
              <div class="stat-icon"><i class="bi bi-check-circle"></i></div>
              <div class="stat-value" style="font-size:1.4rem;"><?= $viewCaretaker['occupied_units'] ?></div>
              <div class="stat-label">Occupied</div>
            </div>
            <div class="stat-card green" style="padding:14px;">
              <div class="stat-icon"><i class="bi bi-cash-stack"></i></div>
              <div class="stat-value" style="font-size:.95rem;"><?= formatKES($ctPayTotals['collected'] ?? 0) ?></div>
              <div class="stat-label">Collected <?= date('M') ?></div>
            </div>
            <div class="stat-card red" style="padding:14px;">
              <div class="stat-icon"><i class="bi bi-wrench"></i></div>
              <div class="stat-value" style="font-size:1.4rem;"><?= $viewCaretaker['pending_maintenance'] ?></div>
              <div class="stat-label">Pending Maint.</div>
            </div>
          </div>

          
          <div class="card">
            <div class="card-header"><h3 class="card-title"><i class="bi bi-clock-history"></i> Assignment History</h3></div>
            <div class="card-body p-0">
              <div class="table-wrapper">
                <table class="rms-table">
                  <thead><tr><th>Apartment</th><th>Assigned</th><th>Removed</th><th>Status</th></tr></thead>
                  <tbody>
                    <?php if (empty($ctHistory)): ?>
                    <tr><td colspan="4" style="text-align:center;color:var(--text-muted);padding:16px;">No history.</td></tr>
                    <?php else: ?>
                      <?php foreach ($ctHistory as $h): ?>
                      <tr>
                        <td><?= htmlspecialchars($h['apartment_name']) ?></td>
                        <td><?= formatDate($h['assigned_date']) ?></td>
                        <td><?= $h['removed_date'] ? formatDate($h['removed_date']) : '—' ?></td>
                        <td><span class="badge <?= $h['status']==='active'?'badge-active':'badge-inactive' ?>"><?= ucfirst($h['status']) ?></span></td>
                      </tr>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
        <?php else: ?>
        <div class="card">
          <div class="card-body" style="text-align:center;padding:40px;color:var(--text-muted);">
            <i class="bi bi-building-slash" style="font-size:2rem;display:block;margin-bottom:8px;"></i>
            Not assigned to any apartment.<br>
            <button class="btn btn-primary btn-sm mt-3"
              onclick="openAssignModal(<?= $viewCaretaker['id'] ?>, '<?= htmlspecialchars($viewCaretaker['full_name']) ?>')">
              <i class="bi bi-building-add"></i> Assign Apartment
            </button>
          </div>
        </div>
        <?php endif; ?>

      </div>

      
      <?php if ($viewCaretaker['apartment_id'] && !empty($ctTenants)): ?>
      <div class="card mb-4">
        <div class="card-header">
          <h3 class="card-title"><i class="bi bi-people"></i> Tenants Under Care (<?= count($ctTenants) ?>)</h3>
          <a href="tenants.php?apt=<?= $viewCaretaker['apartment_id'] ?>" class="btn btn-secondary btn-sm">Manage</a>
        </div>
        <div class="card-body p-0">
          <div class="table-wrapper">
            <table class="rms-table">
              <thead><tr><th>Tenant</th><th>Unit</th><th>Move-in</th><th>Rent Status</th></tr></thead>
              <tbody>
                <?php foreach ($ctTenants as $t): ?>
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
        </div>
      </div>
      <?php endif; ?>

      
      <div class="modal-overlay" id="removeCaretakerModal">
        <div class="modal">
          <div class="modal-header">
            <h3><i class="bi bi-person-dash"></i> Remove Caretaker</h3>
            <button class="modal-close" data-modal-close><i class="bi bi-x-lg"></i></button>
          </div>
          <div class="modal-body">
            <div class="alert alert-warning">
              <i class="bi bi-exclamation-triangle-fill"></i>
              <div>
                Removing <strong><?= htmlspecialchars($viewCaretaker['full_name']) ?></strong> will:
                <ul style="margin-top:6px;padding-left:16px;font-size:.83rem;">
                  <li>Deactivate their account (they cannot log in)</li>
                  <li>End their apartment assignment</li>
                  <li>Retain all historical records</li>
                </ul>
              </div>
            </div>
            <form action="../includes/save_caretaker.php" method="POST">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(rmsCsrfToken()) ?>">
              <input type="hidden" name="action"      value="remove">
              <input type="hidden" name="caretaker_id" value="<?= $viewCaretaker['id'] ?>">
              <div class="form-group">
                <label class="form-label">Removal Reason (optional)</label>
                <textarea name="notes" class="form-control" rows="2"
                  placeholder="e.g. Resigned, Terminated…"></textarea>
              </div>
              <?php if ($viewCaretaker['apartment_id']): ?>
              <div class="alert alert-info">
                <i class="bi bi-building"></i>
                <div><strong><?= htmlspecialchars($viewCaretaker['apartment_name']) ?></strong> will need a new caretaker.
                <a href="apartments.php?edit=<?= $viewCaretaker['apartment_id'] ?>">Assign one now</a> after removal.</div>
              </div>
              <?php endif; ?>
              <div class="modal-footer" style="padding:0;margin-top:8px;">
                <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-danger"><i class="bi bi-person-dash"></i> Confirm Removal</button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <?php else: ?>
      
      <div class="page-header">
        <div>
          <h1>Caretaker Management</h1>
          <p>Add, assign and manage caretakers — one apartment per caretaker</p>
        </div>
        <button class="btn btn-primary" data-modal-open="ctModal">
          <i class="bi bi-person-plus-fill"></i> Add Caretaker
        </button>
      </div>

      
      <div style="display:flex;gap:0;border-bottom:2px solid var(--border);margin-bottom:20px;">
        <?php
          $tabs = [
            'list'    => ['label' => 'Active Caretakers', 'icon' => 'bi-person-badge',    'count' => count($caretakers)],
            'history' => ['label' => 'Removed (History)', 'icon' => 'bi-clock-history',   'count' => count($caretakerHistory)],
          ];
          foreach ($tabs as $key => $t):
            $isActive = ($activeTab === $key);
        ?>
        <a href="caretakers.php?tab=<?= $key ?>"
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
      <
      <?php if (empty($pendingCaretakers)): ?>
      <div class="card">
        <div class="card-body" style="text-align:center;padding:48px;color:var(--text-muted);">
          <i class="bi bi-hourglass" style="font-size:2.5rem;display:block;margin-bottom:8px;"></i>
          No pending caretaker registrations.
        </div>
      </div>
      <?php else: ?>
      <div class="card">
        <div class="card-header">
          <h3 class="card-title"><i class="bi bi-hourglass-split"></i> Pending Caretaker Applications (<?= $pendingCount ?>)</h3>
        </div>
        <div class="card-body p-0">
          <div class="table-wrapper">
            <table class="rms-table">
              <thead>
                <tr><th>#</th><th>Applicant</th><th>Phone</th><th>ID Number</th><th>Applied For</th><th>Experience / Notes</th><th>Applied</th><th class="col-actions">Actions</th></tr>
              </thead>
              <tbody>
                <?php foreach ($pendingCaretakers as $i => $pc): ?>
                <?php
                  
                  $appliedApartment = '—';
                  $experienceNotes  = htmlspecialchars($pc['notes'] ?? '—');
                  if ($pc['notes']) {
                    if (preg_match('/Applied for:\s*([^\n(]+)(?:\s*\([^)]+\))?/i', $pc['notes'], $match)) {
                      $appliedApartment = htmlspecialchars(trim($match[1]));
                      
                      if (preg_match('/Experience:\s*(.+)/is', $pc['notes'], $expMatch)) {
                        $experienceNotes = htmlspecialchars(trim($expMatch[1]));
                      } else {
                        $experienceNotes = '—';
                      }
                    }
                  }
                ?>
                <tr>
                  <td><?= $i + 1 ?></td>
                  <td>
                    <div style="font-weight:600;"><?= htmlspecialchars($pc['full_name']) ?></div>
                    <div style="font-size:.72rem;color:var(--text-muted);"><?= htmlspecialchars($pc['email']) ?></div>
                  </td>
                  <td><?= htmlspecialchars($pc['phone'] ?? '—') ?></td>
                  <td><?= htmlspecialchars($pc['id_number'] ?? '—') ?></td>
                  <td>
                    <span style="font-weight:600;color:var(--primary);"><?= $appliedApartment ?></span>
                  </td>
                  <td style="max-width:200px;font-size:.8rem;color:var(--text-muted);">
                    <?= $experienceNotes ?>
                  </td>
                  <td style="font-size:.8rem;"><?= formatDate($pc['created_at']) ?></td>
                  <td class="col-actions">
                    <div class="d-flex gap-2" style="justify-content:flex-end;">
                      
                      <?php if ($appliedApartment !== '—'): ?>
                      <form method="POST" action="../includes/save_caretaker.php" style="display:flex;gap:4px;align-items:center;">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(rmsCsrfToken()) ?>">
                        <input type="hidden" name="action"       value="approve">
                        <input type="hidden" name="caretaker_id" value="<?= $pc['id'] ?>">
                        <input type="hidden" name="auto_assign"  value="1">
                        <button class="btn btn-success btn-sm" title="Approve & Assign to <?= $appliedApartment ?>"
                          onclick="return confirm('Approve <?= htmlspecialchars($pc['full_name']) ?> and assign to <?= $appliedApartment ?>?')">
                          <i class="bi bi-check-lg"></i> Approve & Assign
                        </button>
                      </form>
                      <form method="POST" action="../includes/save_caretaker.php">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(rmsCsrfToken()) ?>">
                        <input type="hidden" name="action"       value="approve">
                        <input type="hidden" name="caretaker_id" value="<?= $pc['id'] ?>">
                        <button class="btn btn-primary btn-sm" title="Approve without assignment"
                          onclick="return confirm('Approve <?= htmlspecialchars($pc['full_name']) ?> without assigning an apartment?')">
                          <i class="bi bi-check-lg"></i> Approve Only
                        </button>
                      </form>
                      <?php else: ?>
                      <form method="POST" action="../includes/save_caretaker.php">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(rmsCsrfToken()) ?>">
                        <input type="hidden" name="action"       value="approve">
                        <input type="hidden" name="caretaker_id" value="<?= $pc['id'] ?>">
                        <button class="btn btn-primary btn-sm" title="Approve"
                          onclick="return confirm('Approve <?= htmlspecialchars($pc['full_name']) ?>?')">
                          <i class="bi bi-check-lg"></i> Approve
                        </button>
                      </form>
                      <?php endif; ?>
                    
                      <button class="btn btn-danger btn-sm" title="Reject"
                        onclick="openRejectModal(<?= $pc['id'] ?>, '<?= htmlspecialchars($pc['full_name']) ?>')">
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

      <?php elseif ($activeTab === 'history'): ?>
      
      <?php if (empty($caretakerHistory)): ?>
      <div class="card">
        <div class="card-body" style="text-align:center;padding:48px;color:var(--text-muted);">
          <i class="bi bi-clock-history" style="font-size:2.5rem;display:block;margin-bottom:8px;"></i>
          No caretaker history yet. Removed caretakers will appear here.
        </div>
      </div>
      <?php else: ?>
      <div class="card">
        <div class="card-header">
          <h3 class="card-title"><i class="bi bi-clock-history"></i> Removed Caretakers — Service Records (<?= count($caretakerHistory) ?>)</h3>
        </div>
        <div class="card-body p-0">
          <div class="table-wrapper">
            <table class="rms-table">
              <thead>
                <tr>
                  <th>#</th><th>Name</th><th>Contact</th>
                  <th>Apartment Served</th><th>Served From</th>
                  <th>Removed</th><th>Removed By</th><th>Reason</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($caretakerHistory as $i => $h): ?>
                <tr>
                  <td><?= $i + 1 ?></td>
                  <td>
                    <div style="font-weight:600;"><?= htmlspecialchars($h['full_name']) ?></div>
                    <div style="font-size:.72rem;color:var(--text-muted);"><?= htmlspecialchars($h['email']) ?></div>
                  </td>
                  <td style="font-size:.82rem;"><?= htmlspecialchars($h['phone'] ?? '—') ?></td>
                  <td>
                    <?php if ($h['apartment_name']): ?>
                      <span style="font-weight:600;"><?= htmlspecialchars($h['apartment_name']) ?></span>
                    <?php else: ?>
                      <span style="color:var(--text-muted);">— Not assigned —</span>
                    <?php endif; ?>
                  </td>
                  <td style="font-size:.82rem;"><?= $h['assigned_date'] ? formatDate($h['assigned_date']) : '—' ?></td>
                  <td style="font-size:.82rem;">
                    <span style="color:#f85149;font-weight:600;"><?= $h['removed_date'] ? formatDate($h['removed_date']) : '—' ?></span>
                  </td>
                  <td style="font-size:.82rem;"><?= htmlspecialchars($h['removed_by_name'] ?? '—') ?></td>
                  <td style="font-size:.78rem;color:var(--text-muted);max-width:180px;">
                    <?= htmlspecialchars($h['removal_reason'] ?? '—') ?>
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
      
      <?php
        $cntActive   = count(array_filter($caretakers, fn($c) => $c['status']==='active'));
        $cntInactive = count(array_filter($caretakers, fn($c) => $c['status']!=='active'));
        $cntAssigned = count(array_filter($caretakers, fn($c) => !empty($c['apartment_id'])));
        $cntFree     = count(array_filter($caretakers, fn($c) => empty($c['apartment_id']) && $c['status']==='active'));
      ?>
      <div class="stats-grid" style="grid-template-columns:repeat(auto-fill,minmax(140px,1fr));margin-bottom:18px;">
        <div class="stat-card green">
          <div class="stat-icon"><i class="bi bi-person-check"></i></div>
          <div class="stat-value" data-count="<?= $cntActive ?>"><?= $cntActive ?></div>
          <div class="stat-label">Active</div>
        </div>
        <div class="stat-card blue">
          <div class="stat-icon"><i class="bi bi-building-check"></i></div>
          <div class="stat-value" data-count="<?= $cntAssigned ?>"><?= $cntAssigned ?></div>
          <div class="stat-label">Assigned</div>
        </div>
        <div class="stat-card orange">
          <div class="stat-icon"><i class="bi bi-person-exclamation"></i></div>
          <div class="stat-value" data-count="<?= $cntFree ?>"><?= $cntFree ?></div>
          <div class="stat-label">Unassigned</div>
        </div>
        <div class="stat-card">
          <div class="stat-icon"><i class="bi bi-person-slash"></i></div>
          <div class="stat-value" data-count="<?= $cntInactive ?>"><?= $cntInactive ?></div>
          <div class="stat-label">Inactive</div>
        </div>
      </div>

      <!-- Filter -->
      <div class="filter-bar">
        <div class="input-group search-box">
          <i class="bi bi-search input-icon"></i>
          <input type="search" class="form-control" placeholder="Search caretaker…" data-search-table="ctTable">
        </div>
      </div>

      <!-- Caretakers table -->
      <div class="card">
        <div class="card-body p-0">
          <div class="table-wrapper">
            <table class="rms-table" id="ctTable">
              <thead>
                <tr>
                  <th>#</th><th>Caretaker</th><th>Phone</th>
                  <th>Assigned Apartment</th><th>Units</th><th>Tenants</th>
                  <th>Pending Maint.</th><th>Status</th>
                  <th class="col-actions">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($caretakers)): ?>
                <tr>
                  <td colspan="9" style="text-align:center;padding:36px;color:var(--text-muted);">
                    <i class="bi bi-person-badge" style="font-size:2.5rem;display:block;margin-bottom:8px;"></i>
                    No caretakers yet. <a href="#" data-modal-open="ctModal">Add the first caretaker</a>.
                  </td>
                </tr>
                <?php else: ?>
                  <?php foreach ($caretakers as $i => $ct): ?>
                  <tr>
                    <td><?= $i + 1 ?></td>
                    <td>
                      <div style="display:flex;align-items:center;gap:10px;">
                        <?php if ($ct['avatar']): ?>
                        <img src="../<?= htmlspecialchars($ct['avatar']) ?>"
                             style="width:32px;height:32px;border-radius:50%;object-fit:cover;flex-shrink:0;" alt="">
                        <?php else: ?>
                        <div style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,#007BFF,#08CFFF);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:.8rem;flex-shrink:0;">
                          <?= strtoupper(substr($ct['full_name'],0,1)) ?>
                        </div>
                        <?php endif; ?>
                        <div>
                          <a href="caretakers.php?view=<?= $ct['id'] ?>" style="font-weight:600;color:var(--primary);">
                            <?= htmlspecialchars($ct['full_name']) ?>
                          </a>
                          <div style="font-size:.72rem;color:var(--text-muted);"><?= htmlspecialchars($ct['email']) ?></div>
                        </div>
                      </div>
                    </td>
                    <td><?= htmlspecialchars($ct['phone'] ?? '—') ?></td>
                    <td>
                      <?php if ($ct['apartment_id']): ?>
                        <a href="apartments.php?view=<?= $ct['apartment_id'] ?>" style="color:var(--primary);font-weight:600;">
                          <?= htmlspecialchars($ct['apartment_name']) ?>
                        </a>
                        <div style="font-size:.72rem;color:var(--text-muted);"><?= htmlspecialchars($ct['apartment_location']) ?></div>
                      <?php else: ?>
                        <span class="text-muted fs-sm">— Unassigned —</span>
                      <?php endif; ?>
                    </td>
                    <td><?= $ct['total_units'] ?: '—' ?></td>
                    <td><?= $ct['occupied_units'] ?: '—' ?></td>
                    <td>
                      <?php if ($ct['pending_maintenance'] > 0): ?>
                        <span class="badge badge-overdue"><?= $ct['pending_maintenance'] ?> open</span>
                      <?php else: ?>
                        <span class="badge badge-paid">0</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <span class="badge <?= statusBadgeClass($ct['status']) ?>">
                        <span class="dot"></span><?= ucfirst($ct['status']) ?>
                      </span>
                    </td>
                    <td class="col-actions">
                      <div class="d-flex gap-2" style="justify-content:flex-end;">
                        <a href="caretakers.php?view=<?= $ct['id'] ?>"
                           class="btn btn-secondary btn-sm btn-icon" title="View"><i class="bi bi-eye"></i></a>
                        <button class="btn btn-outline btn-sm btn-icon" title="Edit"
                          onclick="openEditModal(<?= htmlspecialchars(json_encode($ct)) ?>)">
                          <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-primary btn-sm btn-icon" title="Assign Apartment"
                          onclick="openAssignModal(<?= $ct['id'] ?>, '<?= htmlspecialchars($ct['full_name']) ?>', <?= $ct['apartment_id'] ?: 'null' ?>)">
                          <i class="bi bi-building-add"></i>
                        </button>
                        <?php if ($ct['status'] === 'active'): ?>
                        <button class="btn btn-danger btn-sm btn-icon" title="Remove"
                          onclick="openRemoveModal(<?= $ct['id'] ?>, '<?= htmlspecialchars($ct['full_name'], ENT_QUOTES) ?>')">
                          <i class="bi bi-person-dash"></i>
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

      <?php endif; ?>

      <?php endif; ?>

    </div>
  </div>
</div>


<div class="modal-overlay" id="ctModal">
  <div class="modal modal-lg">
    <div class="modal-header">
      <h3 id="ctModalTitle"><i class="bi bi-person-badge-fill"></i> Add New Caretaker</h3>
      <button class="modal-close" data-modal-close><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="modal-body">
      <form action="../includes/save_caretaker.php" method="POST" enctype="multipart/form-data" data-validate id="ctForm">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(rmsCsrfToken()) ?>">
        <input type="hidden" name="action"  value="save">
        <input type="hidden" name="edit_id" id="ctEditId" value="">

        
        <div class="avatar-upload-area" style="margin-bottom:20px;">
          <div class="avatar-preview" id="ctAvatarPreview">
            <i class="bi bi-person-fill" style="font-size:2rem;color:var(--primary);"></i>
          </div>
          <div class="avatar-actions">
            <label class="btn btn-secondary btn-sm" for="ctAvatarInput">
              <i class="bi bi-upload"></i> Upload Photo
            </label>
            <input type="file" id="ctAvatarInput" name="avatar" class="avatar-file-input" accept="image/*" style="display:none;">
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Full Name <span class="required">*</span></label>
            <input type="text" name="full_name" id="ctName" class="form-control" placeholder="e.g. John Kariuki" required>
          </div>
          <div class="form-group">
            <label class="form-label">Email Address <span class="required">*</span></label>
            <input type="email" name="email" id="ctEmail" class="form-control" placeholder="john@email.com" required>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Phone Number <span class="required">*</span></label>
            <input type="tel" name="phone" id="ctPhone" class="form-control" placeholder="07XX XXX XXX" pattern="(?:0[17][0-9]{8}|\+254[17][0-9]{8}|00254[17][0-9]{8})" maxlength="13" inputmode="tel" title="Enter a valid Kenyan phone number" required>
          </div>
          <div class="form-group">
            <label class="form-label">Status</label>
            <select name="status" id="ctStatus" class="form-control">
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
            </select>
          </div>
        </div>

        <div id="ctPasswordSection">
          <div class="form-row" id="ctPwdRow">
            <div class="form-group">
              <label class="form-label">Password <span class="required">*</span></label>
              <input type="password" name="password" id="ctPassword" class="form-control"
                     placeholder="Minimum 8 characters" minlength="8">
            </div>
            <div class="form-group">
              <label class="form-label">Confirm Password <span class="required">*</span></label>
              <input type="password" name="confirm_password" id="ctConfirm" class="form-control"
                     placeholder="Repeat password">
            </div>
          </div>
          <span class="form-error" id="ctPwdErr" style="display:none;margin-top:-10px;margin-bottom:8px;">
            <i class="bi bi-exclamation-circle"></i> Passwords do not match
          </span>
        </div>

        <div class="modal-footer" style="padding:0;margin-top:10px;">
          <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
          <button type="submit" class="btn btn-primary" id="ctSubmitBtn">
            <i class="bi bi-check-lg"></i> Save Caretaker
          </button>
        </div>
      </form>
    </div>
  </div>
</div>


<div class="modal-overlay" id="assignAptModal">
  <div class="modal">
    <div class="modal-header">
      <h3><i class="bi bi-building-add"></i> Assign Apartment</h3>
      <button class="modal-close" data-modal-close><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="modal-body">
      <form action="../includes/save_caretaker.php" method="POST" data-validate>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(rmsCsrfToken()) ?>">
        <input type="hidden" name="action"       value="assign">
        <input type="hidden" name="caretaker_id" id="assignCtId" value="">

        <div class="alert alert-info mb-4">
          <i class="bi bi-info-circle-fill"></i>
          <div>
            Assigning apartment to: <strong id="assignCtName"></strong><br>
            <small>A caretaker can only manage <strong>one apartment</strong> at a time.
            Any existing active assignment will be ended.</small>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Select Apartment <span class="required">*</span></label>
          <select name="apartment_id" id="assignAptId" class="form-control" required>
            <option value="">— Select Apartment —</option>
            <?php foreach ($apartments as $apt): ?>
            <option value="<?= $apt['id'] ?>">
              <?= htmlspecialchars($apt['name']) ?> —
              <?= htmlspecialchars($apt['location']) ?>
              (Caretaker: <?= htmlspecialchars($apt['caretaker_name']) ?>)
            </option>
            <?php endforeach; ?>
          </select>
          <div class="form-hint">Apartments already assigned to another caretaker will transfer the assignment.</div>
        </div>
        <div class="form-group">
          <label class="form-label">Assignment Date</label>
          <input type="date" name="assigned_date" class="form-control" value="<?= date('Y-m-d') ?>">
        </div>

        <div class="modal-footer" style="padding:0;margin-top:8px;">
          <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
          <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Confirm Assignment</button>
        </div>
      </form>
    </div>
  </div>
</div>


<div class="modal-overlay" id="removeCtModal">
  <div class="modal">
    <div class="modal-header">
      <h3><i class="bi bi-person-dash"></i> Remove Caretaker</h3>
      <button class="modal-close" data-modal-close><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="modal-body">
      <div class="alert alert-danger" style="margin-bottom:16px;">
        <i class="bi bi-exclamation-triangle-fill" style="flex-shrink:0;margin-top:2px;"></i>
        <div>
          Removing <strong id="removeCtName"></strong> will:
          <ul style="margin-top:6px;padding-left:16px;font-size:.83rem;">
            <li>Archive their details to the <strong>Removed (History)</strong> tab</li>
            <li>Free their assigned apartment for a new caretaker</li>
            <li><strong>Permanently delete their login account</strong></li>
          </ul>
        </div>
      </div>
      <form method="POST" action="../includes/save_caretaker.php" id="removeCtForm">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(rmsCsrfToken()) ?>">
        <input type="hidden" name="action"       value="remove">
        <input type="hidden" name="caretaker_id" id="removeCtId">
        <div class="form-group">
          <label class="form-label">Reason for Removal <span style="color:var(--text-muted);font-weight:400;">(optional)</span></label>
          <textarea name="notes" class="form-control" rows="2"
            placeholder="e.g. Resigned, Misconduct, Contract ended…"></textarea>
        </div>
        <div class="modal-footer" style="padding:0;margin-top:8px;">
          <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
          <button type="submit" class="btn btn-danger">
            <i class="bi bi-trash3"></i> Remove & Delete Account
          </button>
        </div>
      </form>
    </div>
  </div>
</div>


<div class="modal-overlay" id="rejectCtModal">
  <div class="modal">
    <div class="modal-header">
      <h3><i class="bi bi-x-circle"></i> Reject Application</h3>
      <button class="modal-close" data-modal-close><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="modal-body">
      <p style="color:var(--text-muted);font-size:.88rem;margin-bottom:16px;">
        Rejecting <strong id="rejectCtName"></strong>'s application will permanently delete their registration.
      </p>
      <form method="POST" action="../includes/save_caretaker.php" id="rejectCtForm">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(rmsCsrfToken()) ?>">
        <input type="hidden" name="action"       value="reject">
        <input type="hidden" name="caretaker_id" id="rejectCtId">
        <div class="form-group">
          <label class="form-label">Reason <span style="color:var(--text-muted);font-weight:400;">(optional)</span></label>
          <textarea name="notes" class="form-control" rows="2"
            placeholder="e.g. Incomplete information, not suitable…"></textarea>
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
function openEditModal(ct) {
  document.getElementById('ctModalTitle').innerHTML = '<i class="bi bi-pencil-square"></i> Edit Caretaker';
  document.getElementById('ctSubmitBtn').innerHTML  = '<i class="bi bi-check-lg"></i> Update Caretaker';
  document.getElementById('ctEditId').value  = ct.id;
  document.getElementById('ctName').value    = ct.full_name || '';
  document.getElementById('ctEmail').value   = ct.email     || '';
  document.getElementById('ctPhone').value   = ct.phone     || '';
  document.getElementById('ctStatus').value  = ct.status    || 'active';

  const pwdRow = document.getElementById('ctPwdRow');
  if (pwdRow) pwdRow.style.display = 'none';
  const pwd = document.getElementById('ctPassword');
  const cfm = document.getElementById('ctConfirm');
  if (pwd) pwd.required = false;
  if (cfm) cfm.required = false;
  openModal('ctModal');
}

function openAssignModal(id, name, currentAptId) {
  document.getElementById('assignCtId').value         = id;
  document.getElementById('assignCtName').textContent = name;
  const aptSel = document.getElementById('assignAptId');
  if (aptSel) aptSel.value = currentAptId || '';
  openModal('assignAptModal');
}

function openRemoveModal(id, name) {
  document.getElementById('removeCtId').value          = id;
  document.getElementById('removeCtName').textContent  = name;
  
  const ta = document.querySelector('#removeCtForm textarea[name="notes"]');
  if (ta) ta.value = '';
  openModal('removeCtModal');
}

function openRejectModal(id, name) {
  document.getElementById('rejectCtId').value          = id;
  document.getElementById('rejectCtName').textContent  = name;
  const ta = document.querySelector('#rejectCtForm textarea[name="notes"]');
  if (ta) ta.value = '';
  openModal('rejectCtModal');
}

function copySetupLink() {
  const inp  = document.getElementById('setupLinkInput');
  const icon = document.getElementById('copyIcon');
  if (!inp) return;
  navigator.clipboard.writeText(inp.value).then(() => {
    icon.className = 'bi bi-clipboard-check';
    inp.style.borderColor = '#3fb950';
    setTimeout(() => { icon.className = 'bi bi-clipboard'; inp.style.borderColor = ''; }, 2500);
  }).catch(() => { inp.select(); document.execCommand('copy'); });
}


document.querySelector('[data-modal-open="ctModal"]')?.addEventListener('click', function() {
  const pwdRow = document.getElementById('ctPwdRow');
  if (pwdRow) pwdRow.style.display = '';
  const pwd = document.getElementById('ctPassword');
  const cfm = document.getElementById('ctConfirm');
  if (pwd) { pwd.required = true; pwd.value = ''; }
  if (cfm) { cfm.required = true; cfm.value = ''; }
  
  document.getElementById('ctModalTitle').innerHTML = '<i class="bi bi-person-plus-fill"></i> Add New Caretaker';
  document.getElementById('ctSubmitBtn').innerHTML  = '<i class="bi bi-check-lg"></i> Save Caretaker';
  document.getElementById('ctEditId').value = '';
  document.getElementById('ctPwdErr').style.display = 'none';
});


document.getElementById('ctConfirm')?.addEventListener('input', function() {
  document.getElementById('ctPwdErr').style.display =
    this.value === document.getElementById('ctPassword').value ? 'none' : 'flex';
});
</script>
</body>
</html>
