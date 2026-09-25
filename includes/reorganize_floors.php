<?php
session_start();
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/db_helpers.php';
requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../admin/apartments.php');
    exit;
}
requireCsrfToken();

$apartment_id = (int)($_POST['apartment_id'] ?? 0);
$action       = trim($_POST['action'] ?? '');

if (!$apartment_id) {
    header('Location: ../admin/apartments.php?error=' . urlencode('Invalid apartment ID.'));
    exit;
}


function inferFloor(string $unitNumber): ?int {
    $n = trim($unitNumber);

    
    if (preg_match('/^G\-?(\d+)$/i', $n)) {
        return 0;
    }

    
    if (preg_match('/^\d+$/', $n)) {
        $num = (int)$n;
        if ($num >= 100) {
            $unitPos = $num % 100;
            if ($unitPos >= 1 && $unitPos <= 99) {
                return intdiv($num, 100);
            }
        }
       
        return null;
    }

    return null;
}

try {
    $pdo = getDB();

    
    if ($action === 'move_unit') {
        $unit_id   = (int)($_POST['unit_id']   ?? 0);
        $new_floor = (int)($_POST['new_floor'] ?? 0);

        if (!$unit_id) {
            header('Location: ../admin/organize_floors.php?apt=' . $apartment_id . '&error=' . urlencode('Invalid unit ID.')); exit;
        }
        if ($new_floor < 0 || $new_floor > 200) {
            header('Location: ../admin/organize_floors.php?apt=' . $apartment_id . '&error=' . urlencode('Floor must be between 0 and 200.')); exit;
        }

        $pdo->prepare("UPDATE units SET floor=:floor, updated_at=NOW() WHERE id=:id AND apartment_id=:apt")
            ->execute([':floor' => $new_floor, ':id' => $unit_id, ':apt' => $apartment_id]);

        $num = $pdo->prepare("SELECT unit_number FROM units WHERE id=:id")->execute([':id'=>$unit_id]);
        $num = $pdo->query("SELECT unit_number FROM units WHERE id={$unit_id}")->fetchColumn();

        logActivity($_SESSION['user_id'], 'reorganize_floor', 'units', $unit_id,
            "Moved unit {$num} to floor {$new_floor}");

        $floorText = $new_floor == 0 ? 'Ground Floor' : "Floor {$new_floor}";
        header('Location: ../admin/organize_floors.php?apt=' . $apartment_id . '&success=' . urlencode("Unit {$num} moved to {$floorText}.")); exit;
    }

    
    if ($action === 'smart_fix') {
        $units_per_floor = max(1, min(50, (int)($_POST['units_per_floor'] ?? 8)));
        $start_floor     = max(1, min(100, (int)($_POST['start_floor']     ?? 1)));
        $ground_units    = max(0, min(200, (int)($_POST['ground_units']    ?? 0)));


        $unitsStmt = $pdo->prepare(
            "SELECT id, unit_number
             FROM units
             WHERE apartment_id = :apt
             ORDER BY
               CASE WHEN unit_number REGEXP '^G' THEN 0 ELSE 1 END,
               CASE WHEN unit_number REGEXP '^[0-9]+$' THEN CAST(unit_number AS UNSIGNED) ELSE 99999 END,
               unit_number"
        );
        $unitsStmt->execute([':apt' => $apartment_id]);
        $allUnits = $unitsStmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($allUnits)) {
            header('Location: ../admin/organize_floors.php?apt=' . $apartment_id . '&error=' . urlencode('No units to fix.')); exit;
        }

        $updateStmt = $pdo->prepare("UPDATE units SET floor=:floor, updated_at=NOW() WHERE id=:id");
        $updated    = 0;
        $inferred   = 0;
        $distributed= 0;

        
        $unresolved = []; 
        foreach ($allUnits as $unit) {
            $guessed = inferFloor($unit['unit_number']);
            if ($guessed !== null) {
                $updateStmt->execute([':floor' => $guessed, ':id' => $unit['id']]);
                $updated++;
                $inferred++;
            } else {
                $unresolved[] = $unit;
            }
        }

        // Second pass: distribute remaining (unresolved) units evenly
        // Starting from $start_floor, treating the first $ground_units as Ground Floor
        if (!empty($unresolved)) {
            $groundCount  = min($ground_units, count($unresolved));
            $regularCount = count($unresolved) - $groundCount;
            $floorsNeeded = $regularCount > 0 ? (int)ceil($regularCount / $units_per_floor) : 0;

            // Ground units first
            for ($i = 0; $i < $groundCount; $i++) {
                $updateStmt->execute([':floor' => 0, ':id' => $unresolved[$i]['id']]);
                $updated++;
                $distributed++;
            }

            // Regular floors ascending
            $unitIdx = $groundCount;
            for ($floor = $start_floor; $floor < $start_floor + $floorsNeeded; $floor++) {
                $onThisFloor = min($units_per_floor, count($unresolved) - $unitIdx);
                for ($u = 0; $u < $onThisFloor; $u++) {
                    $updateStmt->execute([':floor' => $floor, ':id' => $unresolved[$unitIdx]['id']]);
                    $updated++;
                    $distributed++;
                    $unitIdx++;
                }
            }
        }

        logActivity($_SESSION['user_id'], 'smart_fix_floors', 'apartments', $apartment_id,
            "Smart floor fix: {$inferred} inferred from unit numbers, {$distributed} distributed");

        $msg = "{$updated} unit(s) updated.";
        if ($inferred > 0)    $msg .= " {$inferred} floor(s) inferred from unit numbers.";
        if ($distributed > 0) $msg .= " {$distributed} distributed evenly (couldn't infer).";

        header('Location: ../admin/organize_floors.php?apt=' . $apartment_id . '&success=' . urlencode($msg)); exit;
    }

    // ── ACTION: Auto-distribute — evenly spread units ─────────
    if ($action === 'auto_distribute') {
        $units_per_floor = max(1, min(50,  (int)($_POST['units_per_floor'] ?? 8)));
        $start_floor     = max(0, min(100, (int)($_POST['start_floor']     ?? 1)));
        $ground_units    = max(0, min(200, (int)($_POST['ground_units']    ?? 0)));
        $renumber        = (bool)($_POST['renumber'] ?? false);

        // Fetch all units numerically sorted (Ground-first, then ascending by number)
        $unitsStmt = $pdo->prepare(
            "SELECT id, unit_number
             FROM units
             WHERE apartment_id = :apt
             ORDER BY
               CASE WHEN unit_number REGEXP '^G' THEN 0 ELSE 1 END,
               CASE WHEN unit_number REGEXP '^[0-9]+$' THEN CAST(unit_number AS UNSIGNED) ELSE 99999 END,
               unit_number"
        );
        $unitsStmt->execute([':apt' => $apartment_id]);
        $allUnits = $unitsStmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($allUnits)) {
            header('Location: ../admin/organize_floors.php?apt=' . $apartment_id . '&error=' . urlencode('No units to organize.')); exit;
        }

        $updateFloor  = $pdo->prepare("UPDATE units SET floor=:floor, updated_at=NOW() WHERE id=:id");
        $updateNum    = $pdo->prepare("UPDATE units SET unit_number=:num, updated_at=NOW() WHERE id=:id");

        $totalUnits   = count($allUnits);
        $regularCount = $totalUnits - min($ground_units, $totalUnits);
        $floorsNeeded = $regularCount > 0 ? (int)ceil($regularCount / $units_per_floor) : 0;
        $updated = 0;

        // Assign ground floor to first $ground_units
        $groundCount = min($ground_units, $totalUnits);
        for ($i = 0; $i < $groundCount; $i++) {
            $updateFloor->execute([':floor' => 0, ':id' => $allUnits[$i]['id']]);
            if ($renumber) {
                $updateNum->execute([':num' => 'G' . str_pad($i + 1, 2, '0', STR_PAD_LEFT), ':id' => $allUnits[$i]['id']]);
            }
            $updated++;
        }

        // Assign regular floors ascending: lowest remaining numbers → lowest floor
        $idx = $groundCount;
        for ($floor = $start_floor; $floor < $start_floor + $floorsNeeded; $floor++) {
            $onThisFloor = min($units_per_floor, $totalUnits - $idx);
            for ($u = 1; $u <= $onThisFloor; $u++) {
                $updateFloor->execute([':floor' => $floor, ':id' => $allUnits[$idx]['id']]);
                if ($renumber) {
                    $updateNum->execute([':num' => (string)(($floor * 100) + $u), ':id' => $allUnits[$idx]['id']]);
                }
                $updated++;
                $idx++;
            }
        }

        logActivity($_SESSION['user_id'], 'auto_distribute_floors', 'apartments', $apartment_id,
            "Auto-distributed {$updated} units across floors (lowest numbers on bottom)");

        $floorsUsed = $floorsNeeded + ($groundCount > 0 ? 1 : 0);
        $msg = "{$updated} unit(s) distributed across {$floorsUsed} floor(s).";
        if ($renumber) $msg .= " Unit numbers have been updated.";

        header('Location: ../admin/organize_floors.php?apt=' . $apartment_id . '&success=' . urlencode($msg)); exit;
    }

    // ── ACTION: Standardise unit types ────────────────────────
    // Apply one unit_type to ALL units, or per-floor overrides
    if ($action === 'standardise_types') {
        $defaultType   = trim($_POST['default_unit_type'] ?? '');
        $validTypes    = ['Bedsitter','Studio','Self-Contained','1 Bedroom','2 Bedroom',
                          '3 Bedroom','4 Bedroom','Penthouse','Maisonette','Shop','Office','Other'];
        if (!in_array($defaultType, $validTypes)) {
            header('Location: ../admin/organize_floors.php?apt=' . $apartment_id . '&error=' . urlencode('Invalid unit type selected.')); exit;
        }

        // Collect per-floor overrides (floor_type_N where N is floor number)
        $floorOverrides = [];
        foreach ($_POST as $key => $val) {
            if (preg_match('/^floor_type_(\d+)$/', $key, $m)) {
                $fl = (int)$m[1];
                if (in_array(trim($val), $validTypes)) {
                    $floorOverrides[$fl] = trim($val);
                }
            }
        }

        // Fetch all units for this apartment
        $allUnits = $pdo->prepare(
            "SELECT id, floor FROM units WHERE apartment_id = :apt"
        );
        $allUnits->execute([':apt' => $apartment_id]);
        $rows = $allUnits->fetchAll(PDO::FETCH_ASSOC);

        if (empty($rows)) {
            header('Location: ../admin/organize_floors.php?apt=' . $apartment_id . '&error=' . urlencode('No units found.')); exit;
        }

        $updateStmt = $pdo->prepare("UPDATE units SET unit_type=:type, updated_at=NOW() WHERE id=:id");
        $updated = 0;
        foreach ($rows as $row) {
            $floor   = (int)$row['floor'];
            $useType = $floorOverrides[$floor] ?? $defaultType;
            $updateStmt->execute([':type' => $useType, ':id' => $row['id']]);
            $updated++;
        }

        logActivity($_SESSION['user_id'], 'standardise_unit_types', 'apartments', $apartment_id,
            "Standardised {$updated} units to {$defaultType}" . (!empty($floorOverrides) ? ' with per-floor overrides' : ''));

        header('Location: ../admin/organize_floors.php?apt=' . $apartment_id . '&success=' . urlencode(
            "{$updated} unit(s) updated. All floors now have uniform unit types."
        )); exit;
    }

    // Unknown action
    header('Location: ../admin/organize_floors.php?apt=' . $apartment_id . '&error=' . urlencode('Invalid action.')); exit;

} catch (Exception $e) {
    error_log('reorganize_floors error: ' . $e->getMessage());
    header('Location: ../admin/organize_floors.php?apt=' . $apartment_id . '&error=' . urlencode('Floor update failed: ' . $e->getMessage())); exit;
}
