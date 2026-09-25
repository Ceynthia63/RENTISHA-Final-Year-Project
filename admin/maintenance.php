<?php
session_start();
require_once '../includes/auth_check.php';
require_once '../includes/db_helpers.php';
requireRole('admin');
$pageTitle  = 'Maintenance';
$activePage = 'maintenance';


try {
  $pdo = getDB();
  
  $pending = (int)$pdo->query("SELECT COUNT(*) FROM maintenance_requests WHERE status = 'Pending'")->fetchColumn();
  $inProgress = (int)$pdo->query("SELECT COUNT(*) FROM maintenance_requests WHERE status = 'In Progress'")->fetchColumn();
  $resolved = (int)$pdo->query("SELECT COUNT(*) FROM maintenance_requests WHERE status = 'Resolved'")->fetchColumn();
  $total = (int)$pdo->query("SELECT COUNT(*) FROM maintenance_requests")->fetchColumn();
  
} catch (Exception $e) {
  $pending = 0;
  $inProgress = 0;
  $resolved = 0;
  $total = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Maintenance — RMS Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="../assets/css/style.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
</head>
<body>
<div class="rms-shell">
  <div class="sidebar-overlay" id="sidebarOverlay"></div>
  <?php include '../includes/sidebar_admin.php'; ?>
  <div class="main-content" id="mainContent">
    <?php include '../includes/topnav.php'; ?>
    <div class="page-content">

      <div class="page-header">
        <div><h1>Maintenance Management</h1><p>Monitor and manage all maintenance requests across properties</p></div>
        <button class="btn btn-outline" onclick="window.print()"><i class="bi bi-printer"></i> Print Report</button>
      </div>
      <div class="stats-grid" style="grid-template-columns:repeat(auto-fill,minmax(150px,1fr));margin-bottom:20px;">
        <div class="stat-card red"><div class="stat-icon"><i class="bi bi-hourglass-split"></i></div><div class="stat-value" data-count="<?= $pending ?>"><?= $pending ?></div><div class="stat-label">Pending</div></div>
        <div class="stat-card orange"><div class="stat-icon"><i class="bi bi-play-circle"></i></div><div class="stat-value" data-count="<?= $inProgress ?>"><?= $inProgress ?></div><div class="stat-label">In Progress</div></div>
        <div class="stat-card green"><div class="stat-icon"><i class="bi bi-check-circle"></i></div><div class="stat-value" data-count="<?= $resolved ?>"><?= $resolved ?></div><div class="stat-label">Resolved</div></div>
        <div class="stat-card blue"><div class="stat-icon"><i class="bi bi-list-task"></i></div><div class="stat-value" data-count="<?= $total ?>"><?= $total ?></div><div class="stat-label">Total</div></div>
      </div>

      
      <div class="grid-2 mb-6">
        <div class="card">
          <div class="card-header"><h3 class="card-title"><i class="bi bi-bar-chart"></i> Requests by Month</h3></div>
          <div class="card-body"><div class="chart-container" style="height:220px;"><canvas id="maintChart"></canvas></div></div>
        </div>
        <div class="card">
          <div class="card-header"><h3 class="card-title"><i class="bi bi-pie-chart"></i> Requests by Category</h3></div>
          <div class="card-body"><div class="chart-container" style="height:220px;"><canvas id="catChart"></canvas></div></div>
        </div>
      </div>

      
      <div class="filter-bar">
        <div class="input-group search-box">
          <i class="bi bi-search input-icon"></i>
          <input type="search" class="form-control" placeholder="Search requests…" data-search-table="maintTable">
        </div>
        <select class="form-control" style="width:auto">
          <option>All Apartments</option>
          <option>Sunset Heights</option>
          <option>Palm View Court</option>
          <option>Green Valley</option>
        </select>
        <select class="form-control" style="width:auto">
          <option>All Categories</option>
          <option>Plumbing</option>
          <option>Electrical</option>
          <option>Structural</option>
          <option>Security</option>
        </select>
        <select class="form-control" style="width:auto" id="statusFilter">
          <option>All Statuses</option>
          <option>Pending</option>
          <option>In Progress</option>
          <option>Resolved</option>
        </select>
      </div>

      <div class="card">
        <div class="card-body p-0">
          <div class="table-wrapper">
            <table class="rms-table" id="maintTable">
              <thead>
                <tr><th>#</th><th>Issue</th><th>Tenant</th><th>Unit</th><th>Apartment</th><th>Category</th><th>Priority</th><th>Reported</th><th>Assigned To</th><th>Status</th><th class="col-actions">Actions</th></tr>
              </thead>
              <tbody>
                <?php
                
                try {
                  $pdo = getDB();
                  $stmt = $pdo->query("
                    SELECT 
                      mr.id,
                      mr.title,
                      mr.description,
                      u.full_name as tenant_name,
                      un.unit_number,
                      a.name as apartment_name,
                      mr.category,
                      mr.priority,
                      DATE_FORMAT(mr.created_at, '%b %d, %Y') as reported_date,
                      COALESCE(c.full_name, 'Unassigned') as assigned_to,
                      mr.status
                    FROM maintenance_requests mr
                    LEFT JOIN users u ON u.id = mr.tenant_id
                    LEFT JOIN units un ON un.id = mr.unit_id
                    LEFT JOIN apartments a ON a.id = un.apartment_id
                    LEFT JOIN users c ON c.id = a.caretaker_id
                    ORDER BY mr.created_at DESC
                  ");
                  $reqs = $stmt->fetchAll(PDO::FETCH_ASSOC);
                } catch (Exception $e) {
                  $reqs = [];
                }
                
                $sc = ['Pending'=>'badge-pending','In Progress'=>'badge-progress','Resolved'=>'badge-resolved','Cancelled'=>'badge-inactive'];
                $pc = ['Urgent'=>'badge-overdue','Normal'=>'badge-info','Emergency'=>'badge-overdue'];
                
                if (empty($reqs)): ?>
                  <tr>
                    <td colspan="11" style="text-align:center;padding:40px;color:var(--text-muted);">
                      <i class="bi bi-wrench-adjustable" style="font-size:2.5rem;display:block;margin-bottom:10px;"></i>
                      No maintenance requests yet.
                    </td>
                  </tr>
                <?php else:
                  foreach($reqs as $r): ?>
                  <tr>
                    <td><?= $r['id'] ?></td>
                    <td class="fw-bold"><?= htmlspecialchars($r['title']) ?></td>
                    <td><?= htmlspecialchars($r['tenant_name']) ?></td>
                    <td><span class="badge badge-info"><?= htmlspecialchars($r['unit_number']) ?></span></td>
                    <td><?= htmlspecialchars($r['apartment_name']) ?></td>
                    <td><?= htmlspecialchars($r['category']) ?></td>
                    <td><span class="badge <?= $pc[$r['priority']] ?>"><?= $r['priority'] ?></span></td>
                    <td><?= $r['reported_date'] ?></td>
                    <td><?= htmlspecialchars($r['assigned_to']) ?></td>
                    <td><span class="badge <?= $sc[$r['status']] ?>"><?= $r['status'] ?></span></td>
                    <td class="col-actions">
                      <div class="d-flex gap-2" style="justify-content:flex-end">
                        <button type="button" class="btn btn-secondary btn-sm btn-icon" title="View"
                          onclick="openViewMaintenance(<?= htmlspecialchars(json_encode($r), ENT_QUOTES, 'UTF-8') ?>)">
                          <i class="bi bi-eye"></i>
                        </button>
                        <button type="button" class="btn btn-outline btn-sm btn-icon" title="Update Status"
                          onclick="openAdminUpdate(<?= htmlspecialchars(json_encode($r), ENT_QUOTES, 'UTF-8') ?>)">
                          <i class="bi bi-pencil"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                  <?php endforeach;
                endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>


<div class="modal-overlay" id="viewMaintModal">
  <div class="modal modal-lg">
    <div class="modal-header">
      <h3><i class="bi bi-wrench-adjustable"></i> Maintenance Request Details</h3>
      <button class="modal-close" data-modal-close><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="modal-body">
      <div class="d-flex justify-between align-center mb-4" style="flex-wrap:wrap;gap:8px;">
        <div><h3 id="viewMaintTitle">—</h3><div class="d-flex gap-2 mt-2"><span class="badge" id="viewMaintStatus">—</span><span class="badge" id="viewMaintPriority">—</span><span class="badge badge-info" id="viewMaintCategory">—</span></div></div>
      </div>
      <div class="form-row">
        <div><p class="fs-sm text-muted">Tenant</p><p class="fw-bold" id="viewMaintTenant">—</p></div>
        <div><p class="fs-sm text-muted">Unit</p><p class="fw-bold" id="viewMaintUnit">—</p></div>
        <div><p class="fs-sm text-muted">Reported</p><p class="fw-bold" id="viewMaintReported">—</p></div>
        <div><p class="fs-sm text-muted">Assigned To</p><p class="fw-bold" id="viewMaintAssigned">—</p></div>
      </div>
      <div class="form-group mt-4">
        <label class="form-label">Description</label>
        <div id="viewMaintDescription" style="background:var(--bg);padding:12px;border-radius:var(--radius-md);font-size:.875rem;color:var(--text-secondary);">
          —
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" data-modal-close>Close</button>
      <button type="button" class="btn btn-primary" id="viewMaintUpdateBtn"><i class="bi bi-pencil"></i> Update Status</button>
    </div>
  </div>
</div>


<div class="modal-overlay" id="updateMaintModal">
  <div class="modal">
    <div class="modal-header">
      <h3><i class="bi bi-pencil-square"></i> Update Maintenance Status</h3>
      <button class="modal-close" data-modal-close><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="modal-body">
      <form action="../includes/save_maintenance.php" method="POST" data-validate id="adminMaintForm">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(rmsCsrfToken()) ?>">
        <input type="hidden" name="edit_id" id="adminMaintEditId" value="">
        <div class="form-group">
          <label class="form-label">Reassign To</label>
          <input type="text" class="form-control" id="adminMaintAssigned" readonly>
        </div>
        <div class="form-group">
          <label class="form-label">New Status <span class="required">*</span></label>
          <select name="new_status" class="form-control" id="adminMaintStatus" required>
            <option value="Pending">Pending</option>
            <option value="In Progress">In Progress</option>
            <option value="Resolved">Resolved</option>
            <option value="Cancelled">Cancelled</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Admin Notes</label>
          <textarea name="notes" class="form-control" rows="3" placeholder="Add notes or instructions…"></textarea>
        </div>
      </form>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" data-modal-close>Cancel</button>
      <button type="submit" form="adminMaintForm" class="btn btn-primary">
        <i class="bi bi-check-lg"></i> Update
      </button>
    </div>
  </div>
</div>

<script src="../assets/js/main.js"></script>
<script>
function openViewMaintenance(r) {
  window.currentMaintenanceRequest = r;
  document.getElementById('viewMaintTitle').textContent = r.title || 'Maintenance request';
  document.getElementById('viewMaintStatus').textContent = r.status || '—';
  document.getElementById('viewMaintPriority').textContent = r.priority || '—';
  document.getElementById('viewMaintCategory').textContent = r.category || '—';
  document.getElementById('viewMaintTenant').textContent = r.tenant_name || '—';
  document.getElementById('viewMaintUnit').textContent =
    (r.unit_number || '—') + (r.apartment_name ? ' — ' + r.apartment_name : '');
  document.getElementById('viewMaintReported').textContent = r.reported_date || '—';
  document.getElementById('viewMaintAssigned').textContent = r.assigned_to || 'Unassigned';
  document.getElementById('viewMaintDescription').textContent = r.description || '—';
  openModal('viewMaintModal');
}

function openAdminUpdate(r) {
  if (!r) return;
  document.getElementById('adminMaintEditId').value = r.id;
  document.getElementById('adminMaintStatus').value = r.status || 'Pending';
  document.getElementById('adminMaintAssigned').value = r.assigned_to || 'Unassigned';
  openModal('updateMaintModal');
}

document.getElementById('viewMaintUpdateBtn').addEventListener('click', function() {
  closeModal('viewMaintModal');
  openAdminUpdate(window.currentMaintenanceRequest);
});

<?php

try {

  $monthlyData = $pdo->query("
    SELECT DATE_FORMAT(created_at, '%b') as month, COUNT(*) as count
    FROM maintenance_requests
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY created_at ASC
  ")->fetchAll(PDO::FETCH_ASSOC);
  
  $months = array_column($monthlyData, 'month');
  $counts = array_column($monthlyData, 'count');
  

  if (empty($months)) {
    $months = ['Jan','Feb','Mar','Apr','May','Jun'];
    $counts = [0,0,0,0,0,0];
  }
  
  $categoryData = $pdo->query("
    SELECT category, COUNT(*) as count
    FROM maintenance_requests
    GROUP BY category
    ORDER BY count DESC
  ")->fetchAll(PDO::FETCH_ASSOC);
  
  $categories = array_column($categoryData, 'category');
  $catCounts = array_column($categoryData, 'count');
  
  
  if (empty($categories)) {
    $categories = ['No Data'];
    $catCounts = [1];
  }
  
} catch (Exception $e) {
  $months = ['Jan','Feb','Mar','Apr','May','Jun'];
  $counts = [0,0,0,0,0,0];
  $categories = ['No Data'];
  $catCounts = [1];
}
?>


new Chart(document.getElementById('maintChart'), {
  type:'bar',
  data:{
    labels:<?= json_encode($months) ?>,
    datasets:[{
      label:'Requests',
      data:<?= json_encode($counts) ?>,
      backgroundColor:'rgba(26,86,219,.75)',
      borderRadius:6
    }]
  },
  options:{
    responsive:true,
    maintainAspectRatio:false,
    plugins:{legend:{display:false}},
    scales:{
      y:{beginAtZero:true,ticks:{stepSize:1}},
      x:{grid:{display:false}}
    }
  }
});

new Chart(document.getElementById('catChart'), {
  type:'doughnut',
  data:{
    labels:<?= json_encode($categories) ?>,
    datasets:[{
      data:<?= json_encode($catCounts) ?>,
      backgroundColor:['#3b82f6','#f59e0b','#ef4444','#10b981','#8b5cf6','#ec4899'],
      borderWidth:0,
      cutout:'65%'
    }]
  },
  options:{
    responsive:true,
    maintainAspectRatio:false,
    plugins:{legend:{position:'bottom'}}
  }
});
</script>
</body>
</html>
