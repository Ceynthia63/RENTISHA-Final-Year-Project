<?php

session_start();
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/db_helpers.php';
requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../admin/apartments.php'); exit;
}
requireCsrfToken();


if (isset($_POST['action']) && $_POST['action'] === 'delete') {
    $aptId = (int)($_POST['apartment_id'] ?? 0);
    
    if (!$aptId) {
        header('Location: ../admin/apartments.php?error=' . urlencode('Invalid apartment ID.'));
        exit;
    }
    
    try {
        $pdo = getDB();
        
        
        $aptName = $pdo->prepare("SELECT name FROM apartments WHERE id = :id LIMIT 1");
        $aptName->execute([':id' => $aptId]);
        $name = $aptName->fetchColumn();
        
        if (!$name) {
            header('Location: ../admin/apartments.php?error=' . urlencode('Apartment not found.'));
            exit;
        }
        
       
        $occupiedCheck = $pdo->prepare(
            "SELECT COUNT(*) FROM units WHERE apartment_id = :id AND status = 'Occupied'"
        );
        $occupiedCheck->execute([':id' => $aptId]);
        $occupiedCount = (int)$occupiedCheck->fetchColumn();
        
        if ($occupiedCount > 0) {
            header('Location: ../admin/apartments.php?error=' . urlencode(
                "Cannot delete \"{$name}\". It has {$occupiedCount} occupied unit(s). Please move out all tenants first."
            ));
            exit;
        }
        
        
        $tenantCheck = $pdo->prepare(
            "SELECT COUNT(*) FROM tenant_units 
             WHERE unit_id IN (SELECT id FROM units WHERE apartment_id = :id) 
             AND status = 'active'"
        );
        $tenantCheck->execute([':id' => $aptId]);
        $tenantCount = (int)$tenantCheck->fetchColumn();
        
        if ($tenantCount > 0) {
            header('Location: ../admin/apartments.php?error=' . urlencode(
                "Cannot delete \"{$name}\". It has {$tenantCount} active tenant(s). Please move them out first."
            ));
            exit;
        }
        
        
        $pdo->prepare("DELETE FROM caretaker_assignments WHERE apartment_id = :id")->execute([':id' => $aptId]);
        $pdo->prepare("DELETE FROM caretaker_apartments WHERE apartment_id = :id")->execute([':id' => $aptId]);
        
        
        $pdo->prepare("DELETE FROM tenant_units WHERE unit_id IN (SELECT id FROM units WHERE apartment_id = :id)")->execute([':id' => $aptId]);
        
        
        $pdo->prepare("DELETE FROM units WHERE apartment_id = :id")->execute([':id' => $aptId]);
        
        
        $pdo->prepare("DELETE FROM payments WHERE apartment_id = :id")->execute([':id' => $aptId]);
        

        $hasUtilityTable = $pdo->query("SHOW TABLES LIKE 'utility_bills'")->fetchColumn();
        if ($hasUtilityTable) {
            $pdo->prepare("DELETE FROM utility_bills WHERE apartment_id = :id")->execute([':id' => $aptId]);
        }
        
        
        $pdo->prepare("DELETE FROM apartments WHERE id = :id")->execute([':id' => $aptId]);
        
        
        logActivity(
            $_SESSION['user_id'],
            'delete_apartment',
            'apartments',
            $aptId,
            "Deleted apartment: {$name}"
        );
        
        header('Location: ../admin/apartments.php?success=' . urlencode("Apartment \"{$name}\" deleted successfully."));
        exit;
        
    } catch (Exception $e) {
        error_log('delete_apartment error: ' . $e->getMessage());
        header('Location: ../admin/apartments.php?error=' . urlencode('Failed to delete apartment. Please try again.'));
        exit;
    }
}


$edit_id      = (int)($_POST['edit_id']      ?? 0) ?: null;
$name         = trim($_POST['name']          ?? '');
$location     = trim($_POST['location']      ?? '');
$county       = trim($_POST['county']        ?? '');
$town         = trim($_POST['town']          ?? '');
$estate       = trim($_POST['estate']        ?? '');
$street       = trim($_POST['street']        ?? '');
$description  = trim($_POST['description']   ?? '');
$caretaker_id = (int)($_POST['caretaker_id'] ?? 0) ?: null;
$status       = in_array($_POST['status'] ?? '', ['Active','Inactive'])
                ? $_POST['status'] : 'Active';
$num_units       = $edit_id ? 0 : (int)($_POST['num_units'] ?? 0);
$units_per_floor = (int)($_POST['units_per_floor'] ?? 8);
$ground_type     = trim($_POST['ground_floor_type'] ?? 'none');
$unit_type       = trim($_POST['unit_type']         ?? '1 Bedroom');
$defaultRentInput = trim((string)($_POST['default_rent'] ?? ''));
$default_rent    = $defaultRentInput === '' ? 0 : filter_var($defaultRentInput, FILTER_VALIDATE_FLOAT);


$validUnitTypes = [
    'Bedsitter','Studio','Self-Contained',
    '1 Bedroom','2 Bedroom','3 Bedroom','4 Bedroom',
    'Penthouse','Maisonette','Shop','Office','Other'
];
if (!in_array($unit_type, $validUnitTypes)) { $unit_type = '1 Bedroom'; }

if (empty($name) || empty($location)) {
    header('Location: ../admin/apartments.php?error=' . urlencode('Apartment name and location are required.'));
    exit;
}
if ((!$edit_id || $num_units > 0) && ($default_rent === false || $default_rent <= 0)) {
    header('Location: ../admin/apartments.php?error=' . urlencode('Monthly rent per unit is required.'));
    exit;
}

try {
    $pdo = getDB();

    if ($caretaker_id) {
        
        $check = $pdo->prepare(
            "SELECT ca.apartment_id, a.name FROM caretaker_assignments ca
             JOIN apartments a ON a.id = ca.apartment_id
             WHERE ca.caretaker_id = :cid AND ca.status = 'active'
             AND (:edit_id IS NULL OR ca.apartment_id != :edit_id2)
             LIMIT 1"
        );
        $check->execute([
            ':cid'     => $caretaker_id,
            ':edit_id' => $edit_id,
            ':edit_id2'=> $edit_id,
        ]);
        $conflict = $check->fetch();
        if ($conflict) {
            $cName = getCaretakerById($caretaker_id)['full_name'] ?? 'This caretaker';
            header('Location: ../admin/apartments.php?error=' . urlencode(
                "{$cName} is already assigned to \"{$conflict['name']}\". " .
                "Please remove that assignment first."
            ));
            exit;
        }
    }

    if ($edit_id) {
        // ── UPDATE existing apartment ─────────────────────────
        $pdo->prepare(
            "UPDATE apartments SET
               name=:name, location=:location, county=:county, town=:town,
               estate=:estate, street=:street, description=:desc,
               caretaker_id=:cid, status=:status, updated_at=NOW()
             WHERE id=:id"
        )->execute([
            ':name'    => $name,     ':location' => $location,
            ':county'  => $county,   ':town'     => $town,
            ':estate'  => $estate,   ':street'   => $street,
            ':desc'    => $description,
            ':cid'     => $caretaker_id,
            ':status'  => $status,   ':id'       => $edit_id,
        ]);
        $aptId = $edit_id;
        $msg   = 'Apartment updated successfully.';

        if ($default_rent !== false && $default_rent > 0) {
            $pdo->prepare(
                "UPDATE units SET monthly_rent=:rent, updated_at=NOW()
                 WHERE apartment_id=:apt"
            )->execute([':rent' => $default_rent, ':apt' => $edit_id]);
            $msg .= ' Existing unit rents were updated.';
        }

        // Add additional units if requested (during edit) - with floor distribution
        if ($num_units > 0) {
            // Get current max unit number and floor
            $maxStmt = $pdo->prepare(
                "SELECT 
                    COALESCE(MAX(floor), 0) as max_floor,
                    COUNT(*) as unit_count,
                    MAX(CASE WHEN unit_number LIKE 'G%' THEN 1 ELSE 0 END) as has_ground
                 FROM units WHERE apartment_id = :apt"
            );
            $maxStmt->execute([':apt' => $aptId]);
            $maxData = $maxStmt->fetch(PDO::FETCH_ASSOC);
            $currentMaxFloor = (int)$maxData['max_floor'];
            $hasGround = (int)$maxData['has_ground'];
            $existingUnits = (int)$maxData['unit_count'];
            
            $unitsCreated = 0;
            $units_per_floor = max(1, min(50, $units_per_floor));
            
            // If apartment has NO units yet, allow ground floor creation
            if ($existingUnits == 0 && $ground_type !== 'none') {
                // Create ground floor units
                $groundUnits = min($units_per_floor, $num_units);
                
                for ($u = 1; $u <= $groundUnits; $u++) {
                    try {
                        $unitNum = 'G' . str_pad($u, 2, '0', STR_PAD_LEFT);
                        
                        // Determine unit type
                        if ($ground_type === 'shops') {
                            $gType = 'Shop';
                        } elseif ($ground_type === 'offices') {
                            $gType = 'Office';
                        } elseif ($ground_type === 'mixed') {
                            $gType = ($u % 2 === 0) ? 'Shop' : 'Office';
                        } else {
                            $gType = $unit_type; // residential same as upper floors
                        }
                        $rent = $default_rent;
                        
                        $stmt = $pdo->prepare(
                            "INSERT INTO units 
                             (apartment_id, unit_number, unit_type, floor, monthly_rent, status, created_at)
                             VALUES (:apt_id, :unit_num, :unit_type, 0, :rent, 'Vacant',
                                     (SELECT created_at FROM apartments WHERE id=:apt_id))"
                        );
                        $stmt->execute([
                            ':apt_id'    => $aptId,
                            ':unit_num'  => $unitNum,
                            ':unit_type' => $gType,
                            ':rent'      => $rent
                        ]);
                        $unitsCreated++;
                    } catch (Exception $ue) {
                        error_log("Failed to create ground unit {$unitNum}: " . $ue->getMessage());
                    }
                }
                
                $num_units -= $groundUnits; // Reduce remaining units to create
            }
            
            // Create regular floor units (starting from next available floor)
            if ($num_units > 0) {
                $startFloor = $currentMaxFloor + 1;
                $floorsNeeded = ceil($num_units / $units_per_floor);
                
                $unitCounter = 0;
                for ($floor = $startFloor; $floor < $startFloor + $floorsNeeded; $floor++) {
                    $unitsThisFloor = min($units_per_floor, $num_units - $unitCounter);
                    
                    for ($u = 1; $u <= $unitsThisFloor; $u++) {
                        try {
                            $unitNum = ($floor * 100) + $u;
                            $rent = $default_rent;
                            
                            $stmt = $pdo->prepare(
                                "INSERT INTO units 
                                 (apartment_id, unit_number, unit_type, floor, monthly_rent, status, created_at)
                                 VALUES (:apt_id, :unit_num, :unit_type, :floor, :rent, 'Vacant',
                                         (SELECT created_at FROM apartments WHERE id=:apt_id))"
                            );
                            $stmt->execute([
                                ':apt_id'    => $aptId,
                                ':unit_num'  => (string)$unitNum,
                                ':unit_type' => $unit_type,
                                ':floor'     => $floor,
                                ':rent'      => $rent
                            ]);
                            $unitsCreated++;
                            $unitCounter++;
                        } catch (Exception $ue) {
                            error_log("Failed to create unit {$unitNum}: " . $ue->getMessage());
                        }
                    }
                    
                    if ($unitCounter >= $num_units) break;
                }
            }
            
            if ($unitsCreated > 0) {
                $floorsAdded = ceil($unitsCreated / $units_per_floor);
                if ($floorsAdded == 1) {
                    $msg .= " {$unitsCreated} unit(s) added on 1 floor.";
                } else {
                    $msg .= " {$unitsCreated} unit(s) added across {$floorsAdded} floor(s).";
                }
            }
        }

        // Recalculate and update total_units from actual database count
        $pdo->prepare(
            "UPDATE apartments SET total_units = (
                SELECT COUNT(*) FROM units WHERE apartment_id = :apt
            ) WHERE id = :apt"
        )->execute([':apt' => $aptId]);

        // Deactivate any previous caretaker assignment for this apartment
        $pdo->prepare(
            "UPDATE caretaker_assignments SET status='inactive', removed_date=CURDATE()
             WHERE apartment_id=:aid AND status='active'"
            . ($caretaker_id ? " AND caretaker_id != :cid" : "")
        )->execute($caretaker_id
            ? [':aid' => $aptId, ':cid' => $caretaker_id]
            : [':aid' => $aptId]);

    } else {
        // ── INSERT new apartment ──────────────────────────────
        $pdo->prepare(
            "INSERT INTO apartments
               (name, location, county, town, estate, street, description,
                caretaker_id, status, created_at)
             VALUES
               (:name, :location, :county, :town, :estate, :street, :desc,
                :cid, :status, NOW())"
        )->execute([
            ':name'    => $name,     ':location' => $location,
            ':county'  => $county,   ':town'     => $town,
            ':estate'  => $estate,   ':street'   => $street,
            ':desc'    => $description,
            ':cid'     => $caretaker_id,
            ':status'  => $status,
        ]);
        $aptId = (int)$pdo->lastInsertId();
        $msg   = 'Apartment "' . $name . '" added successfully.';
    }

    // ── Create/update caretaker_assignments record ────────────
    if ($caretaker_id && $aptId) {
        // Close any existing active assignment for this caretaker elsewhere
        $pdo->prepare(
            "UPDATE caretaker_assignments SET status='inactive', removed_date=CURDATE()
             WHERE caretaker_id=:cid AND status='active' AND apartment_id != :aid"
        )->execute([':cid' => $caretaker_id, ':aid' => $aptId]);

        // Upsert the new assignment
        $pdo->prepare(
            "INSERT INTO caretaker_assignments (caretaker_id, apartment_id, assigned_date, status)
             VALUES (:cid, :aid, CURDATE(), 'active')
             ON DUPLICATE KEY UPDATE status='active', removed_date=NULL"
        )->execute([':cid' => $caretaker_id, ':aid' => $aptId]);

        // Also update caretaker_apartments (legacy many-to-many)
        $pdo->prepare(
            "INSERT IGNORE INTO caretaker_apartments (caretaker_id, apartment_id)
             VALUES (:cid, :aid)"
        )->execute([':cid' => $caretaker_id, ':aid' => $aptId]);
    }

    // ── Activity log ─────────────────────────────────────────
    logActivity(
        $_SESSION['user_id'],
        $edit_id ? 'update_apartment' : 'create_apartment',
        'apartments',
        $aptId,
        $name
    );

    // ── Auto-create units with floor distribution (only for new apartments) ────
    if (!$edit_id && $num_units > 0 && $aptId) {
        $unitsCreated = 0;
        $units_per_floor = max(1, min(50, $units_per_floor));
        
        // Calculate floors needed
        $groundUnits = 0;
        if ($ground_type !== 'none') {
            $groundUnits = min($units_per_floor, $num_units);
        }
        
        $regularUnits = $num_units - $groundUnits;
        $floorsNeeded = $regularUnits > 0 ? (int)ceil($regularUnits / $units_per_floor) : 0;
        
        // Create ground floor units
        if ($groundUnits > 0) {
            for ($u = 1; $u <= $groundUnits; $u++) {
                try {
                    $unitNum = 'G' . str_pad($u, 2, '0', STR_PAD_LEFT);
                    
                    if ($ground_type === 'shops') {
                        $gType = 'Shop';
                    } elseif ($ground_type === 'offices') {
                        $gType = 'Office';
                    } elseif ($ground_type === 'mixed') {
                        $gType = ($u % 2 === 0) ? 'Shop' : 'Office';
                    } else {
                        // residential — same type as upper floors
                        $gType = $unit_type;
                    }
                    $rent = $default_rent;
                    
                    $stmt = $pdo->prepare(
                        "INSERT INTO units 
                         (apartment_id, unit_number, unit_type, floor, monthly_rent, status, created_at)
                         VALUES (:apt_id, :unit_num, :unit_type, 0, :rent, 'Vacant',
                                 (SELECT created_at FROM apartments WHERE id=:apt_id))"
                    );
                    $stmt->execute([
                        ':apt_id'    => $aptId,
                        ':unit_num'  => $unitNum,
                        ':unit_type' => $gType,
                        ':rent'      => $rent
                    ]);
                    $unitsCreated++;
                } catch (Exception $ue) {
                    error_log("Failed to create ground unit {$unitNum}: " . $ue->getMessage());
                }
            }
        }
        
        // Create regular floor units with the apartment's uniform rent
        if ($regularUnits > 0) {
            $unitCounter = 0;
            for ($floor = 1; $floor <= $floorsNeeded; $floor++) {
                $unitsThisFloor = min($units_per_floor, $regularUnits - $unitCounter);
                $rent = $default_rent;
                
                for ($u = 1; $u <= $unitsThisFloor; $u++) {
                    try {
                        $unitNum = ($floor * 100) + $u;
                        
                        $stmt = $pdo->prepare(
                            "INSERT INTO units 
                             (apartment_id, unit_number, unit_type, floor, monthly_rent, status, created_at)
                             VALUES (:apt_id, :unit_num, :unit_type, :floor, :rent, 'Vacant',
                                     (SELECT created_at FROM apartments WHERE id=:apt_id))"
                        );
                        $stmt->execute([
                            ':apt_id'    => $aptId,
                            ':unit_num'  => (string)$unitNum,
                            ':unit_type' => $unit_type,
                            ':floor'     => $floor,
                            ':rent'      => $rent
                        ]);
                        $unitsCreated++;
                        $unitCounter++;
                    } catch (Exception $ue) {
                        error_log("Failed to create unit {$unitNum}: " . $ue->getMessage());
                    }
                }
                
                if ($unitCounter >= $regularUnits) break;
            }
        }
        
        if ($unitsCreated > 0) {
            // Update apartment's total_units count
            $pdo->prepare("UPDATE apartments SET total_units = :count WHERE id = :id")
                ->execute([':count' => $unitsCreated, ':id' => $aptId]);
            
            // Build friendly message
            $totalFloors = $floorsNeeded + ($groundUnits > 0 ? 1 : 0);
            if ($totalFloors == 1) {
                $floorText = " on 1 floor";
            } else if ($totalFloors > 1) {
                $floorText = " across {$totalFloors} floors";
            } else {
                $floorText = "";
            }
            $msg .= " {$unitsCreated} unit(s) created{$floorText}.";
        }
    }

} catch (Exception $e) {
    error_log('save_apartment error: ' . $e->getMessage());
    header('Location: ../admin/apartments.php?error=' . urlencode('Failed to save apartment. Please try again.'));
    exit;
}

header('Location: ../admin/apartments.php?success=' . urlencode($msg));
exit;
