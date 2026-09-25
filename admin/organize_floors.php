<?php
session_start();
require_once '../includes/auth_check.php';
require_once '../includes/db_helpers.php';
requireRole('admin');

$pageTitle  = 'Organize Units by Floor';
$activePage = 'apartments';

$aptId = (int)($_GET['apt'] ?? 0);
if (!$aptId) {
    header('Location: apartments.php?error=' . urlencode('Invalid apartment ID.'));
    exit;
}


$apt = getApartmentById($aptId);
if (!$apt) {
    header('Location: apartments.php?error=' . urlencode('Apartment not found.'));
    exit;
}


$pdo = getDB();
$unitsStmt = $pdo->prepare(
    "SELECT u.*, COALESCE(t.full_name, '—') as tenant_name
     FROM units u
     LEFT JOIN tenant_units tu ON tu.unit_id = u.id AND tu.status = 'active'
     LEFT JOIN users t ON t.id = tu.tenant_id
     WHERE u.apartment_id = :apt
     ORDER BY u.floor, CAST(u.unit_number AS UNSIGNED), u.unit_number"
);
$unitsStmt->execute([':apt' => $aptId]);
$units = $unitsStmt->fetchAll(PDO::FETCH_ASSOC);


$unitsByFloor = [];
foreach ($units as $unit) {
    $floor = $unit['floor'] ?? 0;
    if (!isset($unitsByFloor[$floor])) {
        $unitsByFloor[$floor] = [];
    }
    $unitsByFloor[$floor][] = $unit;
}
ksort($unitsByFloor);

$success = $_GET['success'] ?? '';
$error   = $_GET['error']   ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Organize Floors — <?= htmlspecialchars($apt['name']) ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="../assets/css/style.css">
  <style>
    .floor-section {
      background: var(--card-bg);
      border: 1px solid var(--border);
      border-radius: 8px;
      margin-bottom: 16px;
      padding: 16px;
    }
    .floor-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 12px;
      padding-bottom: 8px;
      border-bottom: 2px solid var(--border);
    }
    .floor-title {
      font-size: 1.1rem;
      font-weight: 700;
      color: var(--primary);
    }
    .floor-count {
      font-size: 0.85rem;
      color: var(--text-muted);
      background: var(--bg-secondary);
      padding: 4px 12px;
      border-radius: 12px;
    }
    .unit-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
      gap: 12px;
    }
    .unit-card {
      background: var(--bg-secondary);
      border: 1px solid var(--border);
      border-radius: 6px;
      padding: 12px;
      cursor: move;
      transition: all 0.2s;
    }
    .unit-card:hover {
      border-color: var(--primary);
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    .unit-card.occupied {
      border-left: 3px solid #7ED321;
    }
    .unit-number {
      font-size: 1rem;
      font-weight: 700;
      color: var(--primary);
      margin-bottom: 4px;
    }
    .unit-type {
      font-size: 0.75rem;
      color: var(--text-muted);
      margin-bottom: 8px;
    }
    .unit-tenant {
      font-size: 0.8rem;
      color: var(--text-secondary);
    }
    .bulk-actions {
      background: var(--card-bg);
      border: 2px dashed var(--border);
      border-radius: 8px;
      padding: 20px;
      margin-bottom: 24px;
    }
    .floor-input-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
      gap: 12px;
      margin-top: 12px;
    }
  </style>
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

      <div class="page-header">
        <div>
          <h1><i class="bi bi-buildings"></i> Organize Floors</h1>
          <p>
            <?= htmlspecialchars($apt['name']) ?> 
            <span class="badge badge-info"><?= count($units) ?> units</span>
          </p>
        </div>
        <div class="d-flex gap-2">
          <a href="apartments.php?view=<?= $aptId ?>" class="btn btn-outline btn-sm">
            <i class="bi bi-arrow-left"></i> Back
          </a>
          <button class="btn btn-primary btn-sm" data-modal-open="autoDistributeModal">
            <i class="bi bi-grid-3x3"></i> Auto-Distribute
          </button>
        </div>
      </div>

      
      <div class="bulk-actions">
        <h3 style="margin:0 0 8px 0;font-size:1rem;">
          <i class="bi bi-lightning-charge"></i> Quick Actions
        </h3>

        <?php
        
        $stuckOnZero = array_filter($units, fn($u) => (int)$u['floor'] === 0);
        $stuckCount  = count($stuckOnZero);
        ?>

        <?php if ($stuckCount > 0): ?>
        <div class="alert alert-warning" style="margin-bottom:16px;">
          <i class="bi bi-exclamation-triangle-fill"></i>
          <div>
            <strong><?= $stuckCount ?> unit(s) are showing as Ground Floor (floor = 0).</strong>
            Use <strong>Smart Auto-Fix</strong> below to automatically assign correct floors.
          </div>
        </div>
        <?php endif; ?>

        <
        <div style="background:var(--primary-pale);border:1px solid var(--primary);border-radius:8px;padding:16px;margin-bottom:16px;">
          <div style="font-weight:700;color:var(--primary);margin-bottom:6px;">
            <i class="bi bi-magic"></i> Smart Auto-Fix
            <span style="font-weight:400;font-size:.83rem;color:var(--text-muted);margin-left:8px;">
              Infers floor from unit number (101→Floor 1, 205→Floor 2, G01→Ground). Falls back to even distribution for plain numbers.
            </span>
          </div>
          <form action="../includes/reorganize_floors.php" method="POST" style="margin:0;">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(rmsCsrfToken()) ?>">
            <input type="hidden" name="apartment_id" value="<?= $aptId ?>">
            <input type="hidden" name="action" value="smart_fix">
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr auto;gap:12px;align-items:end;">
              <div class="form-group" style="margin:0;">
                <label class="form-label">Units Per Floor <span style="font-size:.75rem;color:var(--text-muted);">(for plain numbers)</span></label>
                <input type="number" name="units_per_floor" class="form-control" value="<?= max(1, (int)ceil(count($units) / max(1, ($apt['floors'] ?? 1)))) ?>" min="1" max="50" required>
              </div>
              <div class="form-group" style="margin:0;">
                <label class="form-label">Start From Floor</label>
                <input type="number" name="start_floor" class="form-control" value="1" min="1" max="100">
              </div>
              <div class="form-group" style="margin:0;">
                <label class="form-label">Ground Floor Units <span style="font-size:.75rem;color:var(--text-muted);">(plain numbers only)</span></label>
                <input type="number" name="ground_units" class="form-control" value="0" min="0" max="50">
              </div>
              <button type="submit" class="btn btn-primary"
                onclick="return confirm('Smart Fix will update floor numbers for all <?= count($units) ?> units. Continue?')">
                <i class="bi bi-magic"></i> Smart Fix
              </button>
            </div>
          </form>
        </div>

        
        <details>
          <summary style="cursor:pointer;font-weight:600;font-size:.9rem;color:var(--text-secondary);margin-bottom:8px;">
            <i class="bi bi-sliders"></i> Manual Distribution (custom config)
          </summary>
          <form action="../includes/reorganize_floors.php" method="POST" style="margin-top:12px;">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(rmsCsrfToken()) ?>">
            <input type="hidden" name="apartment_id" value="<?= $aptId ?>">
            <input type="hidden" name="action" value="auto_distribute">
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr auto;gap:12px;align-items:end;">
              <div class="form-group" style="margin:0;">
                <label class="form-label">Units Per Floor</label>
                <input type="number" name="units_per_floor" class="form-control" value="8" min="1" max="50" required>
              </div>
              <div class="form-group" style="margin:0;">
                <label class="form-label">Start From Floor</label>
                <input type="number" name="start_floor" class="form-control" value="1" min="0" max="100">
              </div>
              <div class="form-group" style="margin:0;">
                <label class="form-label">Ground Floor Units</label>
                <input type="number" name="ground_units" class="form-control" value="0" min="0" max="50">
                <div class="form-hint">0 = skip ground floor</div>
              </div>
              <div class="form-group" style="margin:0;">
                <label class="form-label">Renumber Units</label>
                <label style="display:flex;align-items:center;gap:8px;padding-top:8px;">
                  <input type="checkbox" name="renumber" value="1" style="width:16px;height:16px;">
                  <span style="font-size:.85rem;">Update unit numbers (101, 201…)</span>
                </label>
              </div>
              <button type="submit" class="btn btn-success"
                onclick="return confirm('This will redistribute ALL units across floors. Continue?')">
                <i class="bi bi-shuffle"></i> Redistribute
              </button>
            </div>
          </form>
        </details>
      </div>

      
      <?php
      
      $typesByFloor = [];
      foreach ($units as $u) {
          $fl = (int)$u['floor'];
          $ut = $u['unit_type'] ?? '';
          if (!isset($typesByFloor[$fl])) $typesByFloor[$fl] = [];
          if ($ut && !in_array($ut, $typesByFloor[$fl])) $typesByFloor[$fl][] = $ut;
      }

      
      $mixedFloors = 0;
      foreach ($typesByFloor as $types) {
          if (count($types) > 1 || (count($types) === 1 && !reset($types))) $mixedFloors++;
      }
      $allUnitTypes = ['Bedsitter','Studio','Self-Contained','1 Bedroom','2 Bedroom',
                       '3 Bedroom','4 Bedroom','Penthouse','Maisonette','Shop','Office','Other'];
      ?>

      
      <?php if (false): ?>
      <div class="bulk-actions" style="border-color:var(--primary);margin-bottom:24px;">
        <h3 style="margin:0 0 8px 0;font-size:1rem;color:var(--primary);">
          <i class="bi bi-grid-fill"></i> Standardise Unit Types
          <?php if ($mixedFloors > 0): ?>
          <span class="badge badge-warning" style="font-size:.7rem;vertical-align:middle;margin-left:6px;"><?= $mixedFloors ?> floor(s) with mixed types</span>
          <?php endif; ?>
        </h3>
        <p style="font-size:.83rem;color:var(--text-muted);margin-bottom:12px;">
          Set one unit type for all floors, or override specific floors individually. Affects unit type label only — not rent or tenants.
        </p>
        <form action="../includes/reorganize_floors.php" method="POST">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(rmsCsrfToken()) ?>">
          <input type="hidden" name="apartment_id" value="<?= $aptId ?>">
          <input type="hidden" name="action" value="standardise_types">

        
          <div style="display:flex;align-items:flex-end;gap:12px;flex-wrap:wrap;margin-bottom:16px;">
            <div class="form-group" style="margin:0;min-width:200px;">
              <label class="form-label">Apply to ALL floors</label>
              <select name="default_unit_type" class="form-control" required>
                <?php
                
                $typeCounts = [];
                foreach ($units as $u) {
                    if ($u['unit_type']) $typeCounts[$u['unit_type']] = ($typeCounts[$u['unit_type']] ?? 0) + 1;
                }
                arsort($typeCounts);
                $mostCommon = $typeCounts ? array_key_first($typeCounts) : '1 Bedroom';
                ?>
                <optgroup label="Residential">
                  <?php foreach (['Bedsitter','Studio','Self-Contained','1 Bedroom','2 Bedroom','3 Bedroom','4 Bedroom','Penthouse','Maisonette'] as $t): ?>
                  <option value="<?= $t ?>" <?= $t === $mostCommon ? 'selected' : '' ?>><?= $t ?></option>
                  <?php endforeach; ?>
                </optgroup>
                <optgroup label="Commercial">
                  <?php foreach (['Shop','Office','Other'] as $t): ?>
                  <option value="<?= $t ?>" <?= $t === $mostCommon ? 'selected' : '' ?>><?= $t ?></option>
                  <?php endforeach; ?>
                </optgroup>
              </select>
            </div>
            <button type="submit" class="btn btn-primary"
              onclick="return confirm('This will update the unit type for all <?= count($units) ?> units in this apartment. Continue?')">
              <i class="bi bi-check-all"></i> Apply to All Units
            </button>
          </div>

          
          <?php if (count($unitsByFloor) > 1): ?>
          <details>
            <summary style="cursor:pointer;font-weight:600;font-size:.85rem;color:var(--text-secondary);margin-bottom:10px;">
              <i class="bi bi-sliders"></i> Per-floor overrides (optional — override specific floors)
            </summary>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:10px;margin-top:10px;">
              <?php foreach ($unitsByFloor as $floor => $floorUnits):
                $floorLabel = $floor === 0 ? 'Ground Floor' : 'Floor ' . $floor;
                $currentTypes = array_unique(array_filter(array_column($floorUnits, 'unit_type')));
                $isMixed = count($currentTypes) > 1;
              ?>
              <div style="background:var(--bg-secondary);border:1px solid <?= $isMixed ? 'var(--warning)' : 'var(--border)' ?>;border-radius:6px;padding:10px;">
                <div style="font-weight:700;font-size:.85rem;margin-bottom:6px;">
                  <?= $isMixed ? '<i class="bi bi-exclamation-triangle-fill" style="color:var(--warning);"></i> ' : '' ?>
                  <?= $floorLabel ?>
                  <span style="font-weight:400;font-size:.75rem;color:var(--text-muted);">(<?= count($floorUnits) ?> units)</span>
                </div>
                <div style="font-size:.75rem;color:var(--text-muted);margin-bottom:6px;">
                  Current: <?= $currentTypes ? implode(', ', $currentTypes) : '<em>empty</em>' ?>
                </div>
                <select name="floor_type_<?= $floor ?>" class="form-control" style="font-size:.8rem;">
                  <option value="">— Use global type —</option>
                  <?php foreach ($allUnitTypes as $t): ?>
                  <option value="<?= $t ?>" <?= (count($currentTypes) === 1 && reset($currentTypes) === $t) ? 'selected' : '' ?>><?= $t ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <?php endforeach; ?>
            </div>
            <button type="submit" class="btn btn-success" style="margin-top:12px;"
              onclick="return confirm('Apply floor-specific types? Floors without an override will use the global type.')">
              <i class="bi bi-check-all"></i> Apply with Per-Floor Overrides
            </button>
          </details>
          <?php endif; ?>
        </form>
      </div>
      <?php endif; ?>
      <?php if (empty($units)): ?>
      <div class="card">
        <div class="card-body" style="text-align:center;padding:40px;">
          <i class="bi bi-inbox" style="font-size:3rem;color:var(--text-muted);display:block;margin-bottom:12px;"></i>
          <p style="color:var(--text-muted);">No units found. <a href="apartments.php?view=<?= $aptId ?>">Add units</a> first.</p>
        </div>
      </div>
      <?php else: ?>
        <?php
        
        $unitsByFloor = [];
        foreach ($units as $unit) {
            $fl = (int)($unit['floor'] ?? 0);
            $unitsByFloor[$fl][] = $unit;
        }
        ksort($unitsByFloor);
        ?>
        <?php foreach ($unitsByFloor as $floor => $floorUnits): ?>
        <div class="floor-section">
          <div class="floor-header">
            <div class="floor-title">
              <?php if ($floor === 0): ?>
                <i class="bi bi-shop"></i> Ground Floor
              <?php else: ?>
                <i class="bi bi-building"></i> Floor <?= $floor ?>
              <?php endif; ?>
            </div>
            <div class="d-flex gap-2" style="align-items:center;">
              <div class="floor-count"><?= count($floorUnits) ?> unit(s)</div>
              <?php if ($floor === 0 && count($unitsByFloor) === 1): ?>
              <span class="badge badge-warning" style="font-size:.7rem;">⚠ All units on Ground Floor — use Smart Fix above</span>
              <?php endif; ?>
            </div>
          </div>

          <div class="unit-grid">
            <?php foreach ($floorUnits as $unit): ?>
            <div class="unit-card <?= ($unit['tenant_name'] ?? '—') !== '—' ? 'occupied' : '' ?>"
                 onclick="openEditUnitFloor(<?= $unit['id'] ?>, '<?= htmlspecialchars($unit['unit_number'],ENT_QUOTES) ?>', <?= $unit['floor'] ?>)">
              <div class="unit-number"><?= htmlspecialchars($unit['unit_number']) ?></div>
              <div class="unit-type"><?= htmlspecialchars($unit['unit_type']) ?></div>
              <div class="unit-tenant">
                <?php if (($unit['tenant_name'] ?? '—') !== '—'): ?>
                  <i class="bi bi-person-fill"></i> <?= htmlspecialchars($unit['tenant_name']) ?>
                <?php else: ?>
                  <span style="color:var(--text-muted);"><i class="bi bi-door-closed"></i> Vacant</span>
                <?php endif; ?>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>

    </div>
  </div>
</div>


<div class="modal-overlay" id="editFloorModal">
  <div class="modal">
    <div class="modal-header">
      <h3><i class="bi bi-arrows-move"></i> Move Unit to Floor</h3>
      <button class="modal-close" data-modal-close><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="modal-body">
      <form action="../includes/reorganize_floors.php" method="POST">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(rmsCsrfToken()) ?>">
        <input type="hidden" name="apartment_id" value="<?= $aptId ?>">
        <input type="hidden" name="action" value="move_unit">
        <input type="hidden" name="unit_id" id="moveUnitId">
        
        <div class="form-group">
          <label class="form-label">Unit Number</label>
          <input type="text" id="moveUnitNumber" class="form-control" readonly style="background:var(--bg-secondary);">
        </div>
        
        <div class="form-group">
          <label class="form-label">Current Floor</label>
          <input type="text" id="moveCurrentFloor" class="form-control" readonly style="background:var(--bg-secondary);">
        </div>
        
        <div class="form-group">
          <label class="form-label">Move to Floor <span class="required">*</span></label>
          <input type="number" name="new_floor" id="moveNewFloor" class="form-control" min="0" max="200" required>
          <div class="form-hint">0 = Ground Floor, 1+ = Regular Floors</div>
        </div>
        
        <div class="modal-footer" style="padding:0;margin-top:16px;">
          <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-lg"></i> Move Unit
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="../assets/js/main.js"></script>
<script>
function openEditUnitFloor(unitId, unitNumber, currentFloor) {
  document.getElementById('moveUnitId').value = unitId;
  document.getElementById('moveUnitNumber').value = unitNumber;
  document.getElementById('moveCurrentFloor').value = currentFloor == 0 ? 'Ground Floor' : 'Floor ' + currentFloor;
  document.getElementById('moveNewFloor').value = currentFloor;
  openModal('editFloorModal');
}
</script>
</body>
</html>
