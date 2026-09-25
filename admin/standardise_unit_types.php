<?php

session_start();
require_once '../includes/auth_check.php';
require_once '../includes/db_helpers.php';
requireRole('admin');

$pageTitle  = 'Standardise Unit Types';
$activePage = 'apartments';

$pdo = getDB();

$validTypes = ['Bedsitter','Studio','Self-Contained','1 Bedroom','2 Bedroom',
               '3 Bedroom','4 Bedroom','Penthouse','Maisonette','Shop','Office','Other'];


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();
    $results  = [];
    $totalUpd = 0;

    
    foreach ($_POST as $key => $val) {
        
        if (!preg_match('/^apt_type_(\d+)$/', $key, $m)) continue;
        $aptId   = (int)$m[1];
        $aptType = trim($val);
        if (!in_array($aptType, $validTypes)) continue;

        
        $floorOverrides = [];
        foreach ($_POST as $fk => $fv) {
            if (preg_match('/^floor_type_' . $aptId . '_(\d+)$/', $fk, $fm)) {
                $fl = (int)$fm[1];
                if (in_array(trim($fv), $validTypes)) {
                    $floorOverrides[$fl] = trim($fv);
                }
            }
        }

        
        $stmt = $pdo->prepare("SELECT id, floor FROM units WHERE apartment_id = :apt");
        $stmt->execute([':apt' => $aptId]);
        $unitRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $updStmt = $pdo->prepare("UPDATE units SET unit_type=:type, updated_at=NOW() WHERE id=:id");
        $aptUpd  = 0;
        foreach ($unitRows as $row) {
            $fl      = (int)$row['floor'];
            $useType = $floorOverrides[$fl] ?? $aptType;
            $updStmt->execute([':type' => $useType, ':id' => $row['id']]);
            $aptUpd++;
        }

        $totalUpd += $aptUpd;
        $results[] = ['apt_id' => $aptId, 'updated' => $aptUpd, 'type' => $aptType,
                      'overrides' => $floorOverrides];

        logActivity($_SESSION['user_id'], 'bulk_standardise_types', 'apartments', $aptId,
            "Standardised {$aptUpd} units → {$aptType}" .
            (!empty($floorOverrides) ? ' (+floor overrides)' : ''));
    }

    $msg = "Done. {$totalUpd} unit(s) updated across " . count($results) . " apartment(s).";
    header('Location: standardise_unit_types.php?success=' . urlencode($msg));
    exit;
}


$aptsStmt = $pdo->query(
    "SELECT a.id, a.name,
            COUNT(u.id) AS total_units,
            COUNT(DISTINCT u.unit_type) AS distinct_types
     FROM apartments a
     LEFT JOIN units u ON u.apartment_id = a.id
     WHERE a.status = 'Active'
     GROUP BY a.id
     ORDER BY a.name"
);
$apartments = $aptsStmt->fetchAll(PDO::FETCH_ASSOC);


$aptFloors = [];
foreach ($apartments as $apt) {
    $fs = $pdo->prepare(
        "SELECT floor,
                GROUP_CONCAT(DISTINCT unit_type ORDER BY unit_type) AS types,
                COUNT(*) AS cnt
         FROM units
         WHERE apartment_id = :apt AND unit_type != '' AND unit_type IS NOT NULL
         GROUP BY floor
         ORDER BY floor"
    );
    $fs->execute([':apt' => $apt['id']]);
    $aptFloors[$apt['id']] = $fs->fetchAll(PDO::FETCH_ASSOC);
}

function mostCommonType(int $aptId, PDO $pdo): string {
    $st = $pdo->prepare(
        "SELECT unit_type, COUNT(*) AS cnt
         FROM units WHERE apartment_id=:apt AND unit_type != '' AND unit_type IS NOT NULL
         GROUP BY unit_type ORDER BY cnt DESC LIMIT 1"
    );
    $st->execute([':apt' => $aptId]);
    return (string)($st->fetchColumn() ?: '1 Bedroom');
}

$success = $_GET['success'] ?? '';
$error   = $_GET['error']   ?? '';
$highlight = (int)($_GET['highlight'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Standardise Unit Types — Rentisha Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="../assets/css/style.css">
  <style>
    .apt-card { background:var(--card-bg); border:1px solid var(--border); border-radius:8px; padding:20px; margin-bottom:20px; }
    .apt-card-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:14px; }
    .apt-title { font-size:1.05rem; font-weight:700; }
    .floor-row { display:grid; grid-template-columns:100px 1fr auto; gap:10px; align-items:center;
                 padding:6px 0; border-bottom:1px solid var(--border); }
    .floor-row:last-child { border-bottom:none; }
    .mixed-tag { background:var(--warning); color:#fff; font-size:.65rem; padding:2px 6px;
                 border-radius:10px; font-weight:700; margin-left:6px; }
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
          <h1><i class="bi bi-grid-fill"></i> Standardise Unit Types</h1>
          <p>Make all units on every floor the same type — one apartment at a time or all at once</p>
        </div>
        <a href="apartments.php" class="btn btn-outline btn-sm">
          <i class="bi bi-arrow-left"></i> Back to Apartments
        </a>
      </div>

      <?php if (empty($apartments)): ?>
      <div class="card">
        <div class="card-body" style="text-align:center;padding:40px;color:var(--text-muted);">
          No active apartments found.
        </div>
      </div>
      <?php else: ?>

      <form method="POST" action="standardise_unit_types.php" id="bulkForm">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(rmsCsrfToken()) ?>">
        <?php foreach ($apartments as $apt):
          $aptId      = $apt['id'];
          $floors     = $aptFloors[$aptId] ?? [];
          $common     = mostCommonType($aptId, $pdo);
          $mixedCount = 0;
          foreach ($floors as $f) {
              if (substr_count($f['types'] ?? '', ',') >= 1) $mixedCount++;
          }
        ?>
        <div class="apt-card" id="apt-<?= $aptId ?>"
             style="<?= $highlight === $aptId ? 'border-color:var(--primary);box-shadow:0 0 0 3px rgba(0,123,255,.15);' : '' ?>">
          <div class="apt-card-header">
            <div>
              <div class="apt-title">
                <i class="bi bi-building"></i> <?= htmlspecialchars($apt['name']) ?>
                <a href="organize_floors.php?apt=<?= $aptId ?>" class="btn btn-secondary btn-sm" style="margin-left:8px;font-size:.72rem;">
                  <i class="bi bi-eye"></i> View Floors
                </a>
              </div>
              <div style="font-size:.8rem;color:var(--text-muted);margin-top:3px;">
                <?= $apt['total_units'] ?> units · <?= $apt['distinct_types'] ?> distinct type(s) currently
                <?php if ($mixedCount > 0): ?>
                <span class="mixed-tag"><i class="bi bi-exclamation-triangle-fill"></i> <?= $mixedCount ?> mixed floor(s)</span>
                <?php endif; ?>
              </div>
            </div>

            <!-- Global type for this apartment -->
            <div style="display:flex;align-items:center;gap:10px;">
              <label style="font-size:.85rem;font-weight:600;white-space:nowrap;">All floors →</label>
              <select name="apt_type_<?= $aptId ?>" id="global_<?= $aptId ?>"
                      class="form-control" style="width:160px;"
                      onchange="propagateGlobal(<?= $aptId ?>)">
                <optgroup label="Residential">
                  <?php foreach (['Bedsitter','Studio','Self-Contained','1 Bedroom','2 Bedroom','3 Bedroom','4 Bedroom','Penthouse','Maisonette'] as $t): ?>
                  <option value="<?= $t ?>" <?= $t === $common ? 'selected' : '' ?>><?= $t ?></option>
                  <?php endforeach; ?>
                </optgroup>
                <optgroup label="Commercial">
                  <?php foreach (['Shop','Office','Other'] as $t): ?>
                  <option value="<?= $t ?>" <?= $t === $common ? 'selected' : '' ?>><?= $t ?></option>
                  <?php endforeach; ?>
                </optgroup>
              </select>
            </div>
          </div>

          <!-- Per-floor breakdown -->
          <?php if (!empty($floors)): ?>
          <details>
            <summary style="cursor:pointer;font-size:.83rem;font-weight:600;color:var(--text-secondary);">
              <i class="bi bi-layers"></i> Floor-by-floor overrides
            </summary>
            <div style="margin-top:12px;">
              <?php foreach ($floors as $f):
                $floorLabel = (int)$f['floor'] === 0 ? 'Ground' : 'Floor ' . $f['floor'];
                $typeList   = explode(',', $f['types'] ?? '');
                $isMixed    = count($typeList) > 1;
                $floorCurrent = count($typeList) === 1 ? trim($typeList[0]) : '';
              ?>
              <div class="floor-row">
                <div style="font-weight:600;font-size:.85rem;">
                  <?= $isMixed ? '<span class="mixed-tag" style="background:var(--warning);">mixed</span> ' : '' ?>
                  <?= $floorLabel ?>
                  <span style="font-weight:400;font-size:.72rem;color:var(--text-muted);">(<?= $f['cnt'] ?> units)</span>
                </div>
                <div style="font-size:.78rem;color:var(--text-muted);">
                  Current: <?= htmlspecialchars($f['types'] ?: '—') ?>
                </div>
                <select name="floor_type_<?= $aptId ?>_<?= $f['floor'] ?>"
                        class="form-control floor-override-<?= $aptId ?>"
                        style="width:160px;font-size:.8rem;">
                  <option value="">— Use global —</option>
                  <?php foreach ($validTypes as $t): ?>
                  <option value="<?= $t ?>" <?= $t === $floorCurrent && !$isMixed ? 'selected' : '' ?>><?= $t ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <?php endforeach; ?>
            </div>
          </details>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>

        <div style="position:sticky;bottom:0;background:var(--bg);padding:16px 0;border-top:2px solid var(--border);display:flex;justify-content:flex-end;gap:12px;">
          <a href="apartments.php" class="btn btn-outline">Cancel</a>
          <button type="submit" class="btn btn-primary btn-lg"
            onclick="return confirm('Apply unit type changes to ALL listed apartments? This updates type labels only.')">
            <i class="bi bi-check-all"></i> Apply to All Apartments
          </button>
        </div>
      </form>
      <?php endif; ?>

    </div>
  </div>
</div>

<script src="../assets/js/main.js"></script>
<script>

document.addEventListener('DOMContentLoaded', function() {
  const el = document.getElementById('apt-<?= $highlight ?>');
  if (el) { el.scrollIntoView({behavior:'smooth', block:'start'}); }
});

function propagateGlobal(aptId) {
  
}
</script>
</body>
</html>
