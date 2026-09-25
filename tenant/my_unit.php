<?php
session_start();
require_once '../includes/auth_check.php';
require_once '../includes/db_helpers.php';
requireActiveTenant();

$pageTitle  = 'My Unit';
$activePage = 'my_unit';

$tenantId = (int)$_SESSION['user_id'];
$tenant   = getTenantById($tenantId);

if (!$tenant) { header('Location: dashboard.php'); exit; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>My Unit — Rentisha</title>
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

      <div class="page-header">
        <div><h1>My Unit</h1><p>Your rental unit and lease information</p></div>
        <?php if ($tenant['unit_id']): ?>
        <a href="payments.php" class="btn btn-primary">
          <i class="bi bi-cash-coin"></i> Pay Rent
        </a>
        <?php endif; ?>
      </div>

      <?php if (!$tenant['unit_id']): ?>
      <div style="text-align:center;padding:60px 20px;color:var(--text-muted);">
        <i class="bi bi-house-slash" style="font-size:3.5rem;display:block;margin-bottom:12px;color:var(--primary);"></i>
        <h2>No Unit Assigned</h2>
        <p style="margin-top:8px;">You have not been assigned a unit yet.<br>Contact your caretaker or administrator.</p>
      </div>
      <?php else: ?>

      <div class="grid-2">

        <!-- Unit banner + details -->
        <div class="card">
          <div class="card-header"><h3 class="card-title"><i class="bi bi-house-fill"></i> Unit Information</h3></div>
          <div class="card-body">

            <!-- Visual banner -->
            <div style="background:linear-gradient(135deg,#001A3D,#007BFF);border-radius:var(--radius-lg);padding:22px;color:#fff;margin-bottom:20px;position:relative;overflow:hidden;">
              <div style="position:absolute;width:160px;height:160px;background:rgba(255,255,255,.07);border-radius:50%;top:-50px;right:-30px;"></div>
              <div style="font-size:.75rem;opacity:.7;margin-bottom:4px;"><?= htmlspecialchars($tenant['apartment_name']) ?></div>
              <div style="font-size:2rem;font-weight:900;">Unit <?= htmlspecialchars($tenant['unit_number']) ?></div>
              <div style="font-size:.82rem;opacity:.8;margin-top:2px;">
                <?= htmlspecialchars($tenant['unit_type'] ?? '') ?>
                <?= $tenant['location'] ? ' · '.$tenant['location'] : '' ?>
              </div>
              <div style="margin-top:14px;font-size:1.1rem;font-weight:700;">
                <?= formatKES($tenant['unit_rent'] ?? $tenant['assigned_rent'] ?? 0) ?>
                <span style="font-size:.75rem;font-weight:400;opacity:.7;">/ month</span>
              </div>
            </div>

            <!-- Info table -->
            <?php $rows = [
              ['Apartment',        $tenant['apartment_name'],    'bi-building'],
              ['Unit Number',      $tenant['unit_number'],       'bi-door-open'],
              ['Unit Type',        $tenant['unit_type'] ?? '—',  'bi-house'],
              ['Monthly Rent',     formatKES($tenant['unit_rent'] ?? $tenant['assigned_rent'] ?? 0), 'bi-cash'],
              ['Security Deposit', formatKES(($tenant['unit_rent'] ?? 0) * 2), 'bi-safe'],
              ['Move-in Date',     formatDate($tenant['move_in_date']), 'bi-calendar-check'],
              ['Rent Due',         '5th of every month',         'bi-calendar-event'],
              ['Caretaker',        $tenant['caretaker_name'] ?? '—', 'bi-person-badge'],
              ['Caretaker Phone',  $tenant['caretaker_phone'] ?? '—', 'bi-telephone'],
            ]; ?>
            <table style="width:100%;border-collapse:collapse;font-size:.85rem;">
              <?php foreach ($rows as [$label,$val,$icon]): ?>
              <tr style="border-bottom:1px solid var(--border);">
                <td style="padding:8px 0;color:var(--text-muted);width:40%;"><i class="bi <?= $icon ?>" style="margin-right:5px;"></i><?= $label ?></td>
                <td style="padding:8px 0;font-weight:600;"><?= htmlspecialchars($val ?? '—') ?></td>
              </tr>
              <?php endforeach; ?>
            </table>
          </div>
        </div>

        <!-- Lease + House Rules -->
        <div>
          <!-- Lease card -->
          <div class="card mb-4">
            <div class="card-header"><h3 class="card-title"><i class="bi bi-file-earmark-text"></i> Lease Details</h3></div>
            <div class="card-body">
              <?php
                $moveIn  = $tenant['move_in_date'] ? new DateTime($tenant['move_in_date']) : null;
                $today   = new DateTime();
                $months  = $moveIn ? (int)$moveIn->diff($today)->m + ($moveIn->diff($today)->y * 12) : 0;
                $leaseEnd = !empty($tenant['assignment_id']) ? null : null;
                $pdo2 = getDB();
                $leaseRow = $pdo2->prepare("SELECT move_in_date, move_out_date FROM tenant_units WHERE id=:aid LIMIT 1");
                $leaseRow->execute([':aid' => $tenant['assignment_id'] ?? 0]);
                $lease = $leaseRow->fetch(PDO::FETCH_ASSOC) ?: [];
              ?>
              <div class="form-row">
                <div><p class="fs-sm text-muted">Move-in</p><p class="fw-bold"><?= formatDate($lease['move_in_date'] ?? $tenant['move_in_date']) ?></p></div>
                <div><p class="fs-sm text-muted">Lease End</p><p class="fw-bold"><?= $lease['move_out_date'] ? formatDate($lease['move_out_date']) : 'Ongoing' ?></p></div>
                <div><p class="fs-sm text-muted">Duration</p><p class="fw-bold"><?= $months ?> month<?= $months!==1?'s':'' ?></p></div>
              </div>
              <div class="alert alert-info mt-4" style="font-size:.82rem;margin-bottom:0;">
                <i class="bi bi-info-circle-fill"></i>
                <div>To update lease details, contact your caretaker or administrator.</div>
              </div>
            </div>
          </div>

          <!-- House Rules -->
          <div class="card">
            <div class="card-header"><h3 class="card-title"><i class="bi bi-journal-text"></i> House Rules</h3></div>
            <div class="card-body">
              <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
                <?php $rules = [
                  ['bi-volume-mute','No loud music after 10 PM'],
                  ['bi-trash3','Garbage disposal: Tue & Fri'],
                  ['bi-person-plus','Register guests with caretaker'],
                  ['bi-car-front','One parking space per unit'],
                  ['bi-droplet','Report leaks immediately'],
                  ['bi-lock','Lock unit when away'],
                  ['bi-fire','No open fires indoors'],
                  ['bi-people','Respect communal areas'],
                ]; foreach ($rules as [$ic,$rule]): ?>
                <div style="display:flex;align-items:center;gap:8px;padding:7px 10px;background:var(--bg);border-radius:6px;font-size:.8rem;">
                  <i class="bi <?= $ic ?>" style="color:var(--primary);flex-shrink:0;"></i>
                  <span><?= $rule ?></span>
                </div>
                <?php endforeach; ?>
              </div>
            </div>
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
