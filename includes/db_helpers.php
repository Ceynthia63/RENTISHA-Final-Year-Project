<?php


require_once __DIR__ . '/db.php';

function rmsNormalizePhone(string $phone): ?string {
    $phone = preg_replace('/[\s().-]+/', '', trim($phone));
    if (preg_match('/^(\+?254|00254)([17]\d{8})$/', $phone, $match)) {
        $phone = '0' . $match[2];
    }
    return preg_match('/^0[17]\d{8}$/', $phone) ? $phone : null;
}

function rmsValidEmail(string $email): bool {
    return strlen($email) <= 180
        && !preg_match('/\s/', $email)
        && filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}


$_RENTISHA_COLS = [];
function _hasCol(string $table, string $col): bool {
    global $_RENTISHA_COLS;
    if (!isset($_RENTISHA_COLS[$table])) {
        try {
            $pdo = getDB();
            $_RENTISHA_COLS[$table] = $pdo->query("SHOW COLUMNS FROM `{$table}`")->fetchAll(PDO::FETCH_COLUMN);
        } catch (Exception $e) {
            $_RENTISHA_COLS[$table] = [];
        }
    }
    return in_array($col, $_RENTISHA_COLS[$table], true);
}

function _hasTable(string $table): bool {
    try {
        $pdo = getDB();
        return (bool)$pdo->query("SHOW TABLES LIKE '{$table}'")->fetchColumn();
    } catch (Exception $e) {
        return false;
    }
}


if (!function_exists('str_starts_with')) {
    function str_starts_with(string $h, string $n): bool { return strncmp($h,$n,strlen($n))===0; }
}
if (!function_exists('str_contains')) {
    function str_contains(string $h, string $n): bool { return $n===''||strpos($h,$n)!==false; }
}


function getDashboardStats(): array {
    $pdo = getDB();
    $s   = [];

    $s['total_apartments']    = (int)$pdo->query("SELECT COUNT(*) FROM apartments WHERE status='Active'")->fetchColumn();
    $s['total_units']         = (int)$pdo->query("SELECT COUNT(*) FROM units")->fetchColumn();
    $s['occupied_units']      = (int)$pdo->query("SELECT COUNT(*) FROM units WHERE status='Occupied'")->fetchColumn();
    $s['vacant_units']        = (int)$pdo->query("SELECT COUNT(*) FROM units WHERE status='Vacant'")->fetchColumn();
    $s['reserved_units']      = (int)$pdo->query("SELECT COUNT(*) FROM units WHERE status='Reserved'")->fetchColumn();
    $s['occupancy_rate']      = $s['total_units']>0 ? round($s['occupied_units']/$s['total_units']*100,1) : 0;
    $s['total_tenants']       = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='tenant'")->fetchColumn();
    $s['active_tenants']      = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='tenant' AND status='active'")->fetchColumn();
    $s['prospective_tenants'] = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='tenant' AND status='prospective'")->fetchColumn();
    $s['pending_tenant_applications'] = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='tenant' AND status='pending_approval'")->fetchColumn();
    $s['moved_out_tenants']   = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='tenant' AND status='moved_out'")->fetchColumn();
    $s['active_caretakers']   = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='caretaker' AND status='active'")->fetchColumn();
    $s['total_caretakers']    = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='caretaker'")->fetchColumn();


    $pm = $pdo->prepare("SELECT
        COALESCE(SUM(CASE WHEN status='Paid'    THEN amount ELSE 0 END),0) AS collected,
        COALESCE(SUM(CASE WHEN status='Pending' THEN amount ELSE 0 END),0) AS pending,
        COALESCE(SUM(CASE WHEN status='Overdue' THEN amount ELSE 0 END),0) AS overdue
        FROM payments WHERE month=:m AND year=:y");
    $pm->execute([':m'=>date('F'),':y'=>(int)date('Y')]);
    $pr = $pm->fetch(PDO::FETCH_ASSOC) ?: [];
    $s['rent_collected'] = (float)($pr['collected']??0);
    $s['rent_pending']   = (float)($pr['pending']??0);
    $s['rent_overdue']   = (float)($pr['overdue']??0);

    
    try {
        $s['utility_pending'] = (int)$pdo->query("SELECT COUNT(*) FROM utility_bills WHERE status='Pending'")->fetchColumn();
        $s['utility_overdue'] = (int)$pdo->query("SELECT COUNT(*) FROM utility_bills WHERE status='Overdue'")->fetchColumn();
    } catch (Exception $e) { $s['utility_pending']=0; $s['utility_overdue']=0; }

    
    try {
        $s['maintenance_pending']    = (int)$pdo->query("SELECT COUNT(*) FROM maintenance_requests WHERE status='Pending'")->fetchColumn();
        $s['maintenance_inprogress'] = (int)$pdo->query("SELECT COUNT(*) FROM maintenance_requests WHERE status='In Progress'")->fetchColumn();
        $s['maintenance_resolved']   = (int)$pdo->query("SELECT COUNT(*) FROM maintenance_requests WHERE status='Resolved'")->fetchColumn();
    } catch (Exception $e) { $s['maintenance_pending']=0; $s['maintenance_inprogress']=0; $s['maintenance_resolved']=0; }

    
    $uid = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
    try {
        if ($uid) {
            $n = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id=:uid AND is_read=0");
            $n->execute([':uid'=>$uid]);
            $s['unread_notifications'] = (int)$n->fetchColumn();
        } else { $s['unread_notifications']=0; }
    } catch (Exception $e) { $s['unread_notifications']=0; }

    return $s;
}


function getApartments(string $status = 'Active'): array {
    $pdo   = getDB();
    $extra = '';
    foreach (['county','town','estate','street','description'] as $c) {
        $extra .= _hasCol('apartments',$c) ? ", a.{$c}" : ", NULL AS {$c}";
    }
    $st = $pdo->prepare(
        "SELECT a.id, a.name, a.location{$extra}, a.status, a.created_at, a.caretaker_id,
                COALESCE(u.full_name,'— Unassigned —') AS caretaker_name, u.phone AS caretaker_phone,
                (SELECT COUNT(*) FROM units un WHERE un.apartment_id=a.id) AS total_units,
                (SELECT COUNT(*) FROM units un WHERE un.apartment_id=a.id AND un.status='Occupied') AS occupied,
                (SELECT COUNT(*) FROM units un WHERE un.apartment_id=a.id AND un.status='Vacant')   AS vacant
         FROM apartments a
         LEFT JOIN users u ON u.id=a.caretaker_id AND u.role='caretaker'
         WHERE a.status=:status ORDER BY a.name ASC"
    );
    $st->execute([':status'=>$status]);
    return $st->fetchAll(PDO::FETCH_ASSOC);
}

function getApartmentById(int $id): ?array {
    $pdo   = getDB();
    $extra = '';
    foreach (['county','town','estate','street','description'] as $c) {
        $extra .= _hasCol('apartments',$c) ? ", a.{$c}" : ", NULL AS {$c}";
    }
    $st = $pdo->prepare(
        "SELECT a.id, a.name, a.location{$extra}, a.status, a.created_at, a.caretaker_id,
                COALESCE(u.full_name,'— Unassigned —') AS caretaker_name, u.phone AS caretaker_phone,
                (SELECT COUNT(*) FROM units un WHERE un.apartment_id=a.id) AS total_units,
                (SELECT COUNT(*) FROM units un WHERE un.apartment_id=a.id AND un.status='Occupied') AS occupied,
                (SELECT COUNT(*) FROM units un WHERE un.apartment_id=a.id AND un.status='Vacant')   AS vacant
         FROM apartments a
         LEFT JOIN users u ON u.id=a.caretaker_id AND u.role='caretaker'
         WHERE a.id=:id LIMIT 1"
    );
    $st->execute([':id'=>$id]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}


function getUnits(?int $apartmentId = null): array {
    $pdo    = getDB();
    $miCol  = _hasCol('tenant_units','move_in_date') ? 'tu.move_in_date,' : 'NULL AS move_in_date,';
    $sql    = "SELECT u.id, u.unit_number, u.unit_type, u.floor, u.monthly_rent,
                      u.deposit, u.status, u.features, u.notes, u.apartment_id,
                      a.name AS apartment_name,
                      COALESCE(ten.full_name,'—') AS tenant_name,
                      ten.id AS tenant_id, ten.phone AS tenant_phone,
                      {$miCol}
                      (SELECT p.status FROM payments p
                       WHERE p.unit_id=u.id AND p.month=:cm AND p.year=:cy LIMIT 1) AS rent_status
               FROM units u
               JOIN apartments a ON a.id=u.apartment_id
               LEFT JOIN tenant_units tu ON tu.unit_id=u.id AND tu.status='active'
               LEFT JOIN users ten ON ten.id=tu.tenant_id AND ten.role='tenant'";
    $params = [':cm'=>date('F'),':cy'=>(int)date('Y')];
    if ($apartmentId) { $sql .= " WHERE u.apartment_id=:apt_id"; $params[':apt_id']=$apartmentId; }
    // Sort: apartment name → floor number (numeric) → unit_number (numeric then alpha)
    $sql .= " ORDER BY a.name, u.floor,
              CASE WHEN u.unit_number REGEXP '^[0-9]+$' THEN CAST(u.unit_number AS UNSIGNED) ELSE 99999 END,
              u.unit_number";
    $st = $pdo->prepare($sql); $st->execute($params);
    return $st->fetchAll(PDO::FETCH_ASSOC);
}

function getVacantUnits(?int $apartmentId = null): array {
    $pdo = getDB();
    $sql = "SELECT u.id, u.unit_number, u.unit_type, u.monthly_rent,
                   a.name AS apartment_name, a.id AS apartment_id
            FROM units u JOIN apartments a ON a.id=u.apartment_id
            WHERE u.status='Vacant'";
    $params = [];
    if ($apartmentId) { $sql .= " AND u.apartment_id=:apt_id"; $params[':apt_id']=$apartmentId; }
    $sql .= " ORDER BY a.name, u.unit_number";
    $st = $pdo->prepare($sql); $st->execute($params);
    return $st->fetchAll(PDO::FETCH_ASSOC);
}

// ─────────────────────────────────────────────────────────────
// TENANTS
// ─────────────────────────────────────────────────────────────
function getTenants(?string $status = null, ?int $apartmentId = null): array {
    $pdo   = getDB();
    $hasMi = _hasCol('tenant_units','move_in_date');
    $hasMo = _hasCol('tenant_units','move_out_date');
    $miSel = $hasMi ? 'tu.move_in_date,' : 'NULL AS move_in_date,';
    $moSel = $hasMo ? 'tu.move_out_date,' : 'NULL AS move_out_date,';
    $arSel = _hasCol('tenant_units','monthly_rent') ? 'tu.monthly_rent AS assigned_rent,' : 'NULL AS assigned_rent,';

    if ($status === 'moved_out') {
        $tuJoin = "LEFT JOIN tenant_units tu ON tu.tenant_id=u.id AND tu.id=(SELECT MAX(tu2.id) FROM tenant_units tu2 WHERE tu2.tenant_id=u.id)";
    } else {
        $tuJoin = "LEFT JOIN tenant_units tu ON tu.tenant_id=u.id AND tu.status='active'";
    }

    $rentStatusFilter = _hasCol('payments', 'payment_type')
        ? " AND p2.payment_type = 'Rent'"
        : '';
    $sql = "SELECT u.id, u.full_name, u.email, u.phone, u.status, u.avatar,
                   u.created_at AS registered_at,
                   COALESCE(un.unit_number,'—') AS unit_number,
                   un.id AS unit_id,
                   COALESCE(a.name,'—') AS apartment_name,
                   a.id AS apartment_id,
                   {$miSel} {$moSel} {$arSel}
                   un.monthly_rent AS unit_rent,
                   (SELECT p2.status FROM payments p2
                    WHERE p2.tenant_id=u.id AND p2.month=:cm AND p2.year=:cy{$rentStatusFilter}
                    ORDER BY p2.created_at DESC, p2.id DESC LIMIT 1) AS rent_status
            FROM users u
            {$tuJoin}
            LEFT JOIN units un     ON un.id=tu.unit_id
            LEFT JOIN apartments a ON a.id=un.apartment_id
            WHERE u.role='tenant'";
    $params = [':cm'=>date('F'),':cy'=>(int)date('Y')];
    if ($status === 'active_or_prospective') {
        $sql .= " AND u.status IN ('active','prospective')";
    } elseif ($status) {
        $sql .= " AND u.status=:status"; $params[':status']=$status;
    } else {
        $sql .= " AND u.status NOT IN ('pending_approval','pending_setup','pending')";
    }
    if ($apartmentId) { $sql .= " AND a.id=:apt_id"; $params[':apt_id']=$apartmentId; }
    $sql .= " ORDER BY u.created_at ASC, u.id ASC";
    $st = $pdo->prepare($sql); $st->execute($params);
    return $st->fetchAll(PDO::FETCH_ASSOC);
}

function getTenantById(int $id): ?array {
    $pdo   = getDB();
    $hasMi = _hasCol('tenant_units','move_in_date');
    $hasMo = _hasCol('tenant_units','move_out_date');
    $miSel = $hasMi ? 'tu.move_in_date,' : 'NULL AS move_in_date,';
    $moSel = $hasMo ? 'tu.move_out_date,' : 'NULL AS move_out_date,';

    $st = $pdo->prepare(
        "SELECT u.id, u.full_name, u.email, u.phone, u.status, u.avatar,
                u.emergency_contact, u.created_at,
                COALESCE(un.unit_number,'—') AS unit_number,
                un.id AS unit_id, un.unit_type, un.monthly_rent AS unit_rent,
                COALESCE(a.name,'—') AS apartment_name,
                a.id AS apartment_id, a.location,
                tu.id AS assignment_id,
                {$miSel} {$moSel}
                tu.monthly_rent AS assigned_rent,
                ca_u.full_name AS caretaker_name, ca_u.phone AS caretaker_phone
         FROM users u
         LEFT JOIN tenant_units tu ON tu.tenant_id=u.id AND tu.status='active'
         LEFT JOIN units un        ON un.id=tu.unit_id
         LEFT JOIN apartments a    ON a.id=un.apartment_id
         LEFT JOIN users ca_u      ON ca_u.id=a.caretaker_id AND ca_u.role='caretaker'
         WHERE u.id=:id AND u.role='tenant' LIMIT 1"
    );
    $st->execute([':id'=>$id]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function getTenantPaymentHistory(int $tenantId, ?int $year = null): array {
    $pdo  = getDB();
    $rcSel = _hasCol('payments','receipt_number') ? 'p.receipt_number,' : 'NULL AS receipt_number,';
    $chSel = _hasCol('payments','cheque_status')  ? 'p.cheque_status,'  : 'NULL AS cheque_status,';
    $balSel = _hasCol('payments','balance') ? 'p.expected_amount, p.balance, p.carried_credit,' : 'NULL AS expected_amount, NULL AS balance, NULL AS carried_credit,';
    $ptSel = _hasCol('payments','payment_type') ? 'p.payment_type,' : "'Rent' AS payment_type,";
    $sql  = "SELECT p.id, p.month, p.year, p.amount, p.payment_method,
                    p.reference_number, {$rcSel} {$ptSel} p.payment_date,
                    p.status, p.notes, {$chSel} {$balSel}
                    u.full_name AS recorded_by_name
             FROM payments p LEFT JOIN users u ON u.id=p.recorded_by
             WHERE p.tenant_id=:tid";
    $params = [':tid'=>$tenantId];
    if ($year) { $sql .= " AND p.year=:year"; $params[':year']=$year; }
    $sql .= " ORDER BY p.year DESC, FIELD(p.month,'January','February','March','April','May','June','July','August','September','October','November','December') DESC";
    $st = $pdo->prepare($sql); $st->execute($params);
    return $st->fetchAll(PDO::FETCH_ASSOC);
}

function getTenantYearlyPayments(int $tenantId, int $year): array {
    $months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
    $pdo    = getDB();
    $rcSel  = _hasCol('payments','receipt_number') ? 'receipt_number,' : 'NULL AS receipt_number,';
    $ptFilter = _hasCol('payments','payment_type') ? " AND payment_type = 'Rent'" : '';
    $st = $pdo->prepare("SELECT month, amount, status, payment_date, payment_method, reference_number, {$rcSel} id FROM payments WHERE tenant_id=:tid AND year=:year{$ptFilter} ORDER BY created_at DESC, id DESC");
    $st->execute([':tid'=>$tenantId,':year'=>$year]);
    $rows = [];
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) { $rows[$r['month']]=$r; }
    $grid = [];
    foreach ($months as $m) {
        $grid[$m] = isset($rows[$m]) ? $rows[$m] : ['month'=>$m,'amount'=>null,'status'=>null,'payment_date'=>null,'payment_method'=>null,'reference_number'=>null,'receipt_number'=>null,'id'=>null];
    }
    return $grid;
}

function getTenantOutstanding(int $tenantId): array {
    $pdo  = getDB();
    $ptFilter = _hasCol('payments','payment_type') ? " AND payment_type <> 'Deposit'" : '';
    $months = "'January','February','March','April','May','June','July','August','September','October','November','December'";
    $st   = $pdo->prepare("SELECT month, year, amount FROM payments WHERE tenant_id=:tid AND status IN ('Pending','Overdue','Partial'){$ptFilter} ORDER BY year ASC, FIELD(month,{$months}) ASC");
    $st->execute([':tid'=>$tenantId]);
    $rows  = $st->fetchAll(PDO::FETCH_ASSOC);
    $total = 0.0;
    foreach ($rows as &$row) {
        $row['amount'] = rentAmountDue(
            (float)$row['amount'],
            (string)$row['month'],
            (int)$row['year']
        );
        $total += $row['amount'];
    }
    unset($row);

    
    $currentMonth = date('F');
    $currentYear  = (int)date('Y');
    $currentRentStmt = $pdo->prepare(
        "SELECT COALESCE(tu.monthly_rent, un.monthly_rent) AS rent
         FROM tenant_units tu
         JOIN units un ON un.id=tu.unit_id
         WHERE tu.tenant_id=:tid AND tu.status='active'
         LIMIT 1"
    );
    $currentRentStmt->execute([':tid'=>$tenantId]);
    $currentRent = (float)$currentRentStmt->fetchColumn();
    $currentPaymentTypeFilter = _hasCol('payments', 'payment_type')
        ? " AND payment_type='Rent'"
        : '';
    $currentPaymentStmt = $pdo->prepare(
        "SELECT COUNT(*) FROM payments
         WHERE tenant_id=:tid AND month=:month AND year=:year{$currentPaymentTypeFilter}"
    );
    $currentPaymentStmt->execute([
        ':tid'=>$tenantId, ':month'=>$currentMonth, ':year'=>$currentYear
    ]);
    if ($currentRent > 0 && !(int)$currentPaymentStmt->fetchColumn()) {
        $credit = 0.0;
        if (_hasCol('payments', 'payment_type') && _hasCol('payments', 'balance')) {
            $creditStmt = $pdo->prepare(
                "SELECT balance FROM payments
                 WHERE tenant_id=:tid AND payment_type='Rent' AND balance < 0
                   AND (year < :year OR (year=:year AND FIELD(month,{$months}) < FIELD(:month,{$months})))
                 ORDER BY year DESC, FIELD(month,{$months}) DESC
                 LIMIT 1"
            );
            $creditStmt->execute([
                ':tid'=>$tenantId, ':year'=>$currentYear, ':month'=>$currentMonth
            ]);
            $credit = abs((float)($creditStmt->fetchColumn() ?: 0));
        }
        $currentOutstanding = max(
            0.0,
            rentAmountDue($currentRent, $currentMonth, $currentYear) - $credit
        );
        if ($currentOutstanding > 0) {
            $rows[] = [
                'month' => $currentMonth,
                'year' => $currentYear,
                'amount' => $currentOutstanding,
            ];
            $total += $currentOutstanding;
        }
    }

    return ['total'=>$total,'rows'=>$rows,'earliest'=>isset($rows[0])?$rows[0]:null];
}


function _caretakerAptJoin(): array {
    
    if (_hasTable('caretaker_assignments')) {
        return [
            "LEFT JOIN caretaker_assignments ca ON ca.caretaker_id=u.id AND ca.status='active'
             LEFT JOIN apartments a ON a.id=ca.apartment_id",
            'ca.assigned_date'
        ];
    }
    return [
        "LEFT JOIN apartments a ON a.caretaker_id=u.id AND a.status='Active'",
        'NULL AS assigned_date'
    ];
}

function getCaretakers(?string $status = null): array {
    $pdo = getDB();
    list($join, $dateCol) = _caretakerAptJoin();
    $sql = "SELECT u.id, u.full_name, u.email, u.phone, u.status, u.avatar, u.created_at,
                   COALESCE(a.name,'—') AS apartment_name,
                   a.id AS apartment_id,
                   COALESCE(a.location,'—') AS apartment_location,
                   {$dateCol},
                   (SELECT COUNT(*) FROM units un WHERE un.apartment_id=a.id) AS total_units,
                   (SELECT COUNT(*) FROM units un WHERE un.apartment_id=a.id AND un.status='Occupied') AS occupied_units,
                   (SELECT COUNT(*) FROM maintenance_requests mr JOIN units un2 ON un2.id=mr.unit_id WHERE un2.apartment_id=a.id AND mr.status='Pending') AS pending_maintenance
            FROM users u {$join}
            WHERE u.role='caretaker'";
    $params = [];
    if ($status) { $sql .= " AND u.status=:status"; $params[':status']=$status; }
    $sql .= " ORDER BY u.created_at ASC, u.id ASC";
    $st = $pdo->prepare($sql); $st->execute($params);
    return $st->fetchAll(PDO::FETCH_ASSOC);
}

function getCaretakerById(int $id): ?array {
    $pdo = getDB();
    list($join, $dateCol) = _caretakerAptJoin();
    $st = $pdo->prepare(
        "SELECT u.id, u.full_name, u.email, u.phone, u.status, u.avatar, u.created_at,
                COALESCE(a.name,'—') AS apartment_name, a.id AS apartment_id,
                COALESCE(a.location,'—') AS apartment_location, {$dateCol}
         FROM users u {$join}
         WHERE u.id=:id AND u.role='caretaker' LIMIT 1"
    );
    $st->execute([':id'=>$id]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function getCaretakerApartment(int $caretakerId): ?array {
    $pdo = getDB();
    if (_hasTable('caretaker_assignments')) {
        $st = $pdo->prepare("SELECT a.* FROM caretaker_assignments ca JOIN apartments a ON a.id=ca.apartment_id WHERE ca.caretaker_id=:cid AND ca.status='active' LIMIT 1");
    } else {
        $st = $pdo->prepare("SELECT * FROM apartments WHERE caretaker_id=:cid AND status='Active' LIMIT 1");
    }
    $st->execute([':cid'=>$caretakerId]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function caretakerIsAssigned(int $caretakerId): ?string {
    $pdo = getDB();
    if (_hasTable('caretaker_assignments')) {
        $st = $pdo->prepare("SELECT a.name FROM caretaker_assignments ca JOIN apartments a ON a.id=ca.apartment_id WHERE ca.caretaker_id=:cid AND ca.status='active' LIMIT 1");
    } else {
        $st = $pdo->prepare("SELECT name FROM apartments WHERE caretaker_id=:cid AND status='Active' LIMIT 1");
    }
    $st->execute([':cid'=>$caretakerId]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    return $row ? $row['name'] : null;
}


function getPayments(array $filters = []): array {
    $pdo    = getDB();
    $rcSel  = _hasCol('payments','receipt_number') ? 'p.receipt_number,' : 'NULL AS receipt_number,';
    $chSel  = _hasCol('payments','cheque_number')  ? 'p.cheque_number, p.cheque_bank, p.cheque_status,' : 'NULL AS cheque_number, NULL AS cheque_bank, NULL AS cheque_status,';
    $balSel = _hasCol('payments','balance')        ? 'p.expected_amount, p.balance, p.carried_credit,' : 'NULL AS expected_amount, NULL AS balance, NULL AS carried_credit,';
    $ptSel  = _hasCol('payments','payment_type')   ? 'p.payment_type,' : "'Rent' AS payment_type,";

    $sql  = "SELECT p.id, p.month, p.year, p.amount, p.payment_method,
                    p.reference_number, {$rcSel} {$chSel} {$balSel} {$ptSel}
                    p.payment_date, p.status, p.notes, p.created_at,
                    u.full_name AS tenant_name, u.phone AS tenant_phone,
                    un.unit_number, a.name AS apartment_name,
                    rb.full_name AS recorded_by_name
             FROM payments p
             JOIN users u ON u.id=p.tenant_id
             LEFT JOIN units un ON un.id=p.unit_id
             LEFT JOIN apartments a ON a.id=p.apartment_id
             LEFT JOIN users rb ON rb.id=p.recorded_by
             WHERE 1=1";

    
    $positional = [];   // values in order

    if (!empty($filters['status']))       { $sql .= " AND p.status=?";       $positional[] = $filters['status']; }
    if (!empty($filters['month']))        { $sql .= " AND p.month=?";         $positional[] = $filters['month']; }
    if (!empty($filters['year']))         { $sql .= " AND p.year=?";          $positional[] = $filters['year']; }
    if (!empty($filters['apartment_id'])) { $sql .= " AND p.apartment_id=?";  $positional[] = $filters['apartment_id']; }
    if (!empty($filters['tenant_id']))    { $sql .= " AND p.tenant_id=?";     $positional[] = $filters['tenant_id']; }

    if (!empty($filters['payment_type']) && _hasCol('payments','payment_type')) {
        $types = (array)$filters['payment_type'];
        $sql  .= " AND p.payment_type IN (" . implode(',', array_fill(0, count($types), '?')) . ")";
        foreach ($types as $t) { $positional[] = $t; }
    }

    $sql .= " ORDER BY p.year DESC, FIELD(p.month,'January','February','March','April','May','June','July','August','September','October','November','December') DESC, p.created_at DESC";

    $st = $pdo->prepare($sql);
    $st->execute($positional);
    return $st->fetchAll(PDO::FETCH_ASSOC);
}

function getMonthlyTotals(?int $apartmentId = null): array {
    $pdo  = getDB();
    $sql  = "SELECT COALESCE(SUM(CASE WHEN p.status='Paid' THEN p.amount ELSE 0 END),0) AS collected,
                    COALESCE(SUM(CASE WHEN p.status='Pending' THEN p.amount ELSE 0 END),0) AS pending,
                    COALESCE(SUM(CASE WHEN p.status='Overdue' THEN p.amount ELSE 0 END),0) AS overdue,
                    COUNT(CASE WHEN p.status='Paid'    THEN 1 END) AS paid_count,
                    COUNT(CASE WHEN p.status='Pending' THEN 1 END) AS pending_count,
                    COUNT(CASE WHEN p.status='Overdue' THEN 1 END) AS overdue_count
             FROM payments p WHERE p.month=:m AND p.year=:y";
    $params = [':m'=>date('F'),':y'=>(int)date('Y')];
    if ($apartmentId) { $sql .= " AND p.apartment_id=:apt_id"; $params[':apt_id']=$apartmentId; }
    $st = $pdo->prepare($sql); $st->execute($params);
    return $st->fetch(PDO::FETCH_ASSOC) ?: [];
}

function getYearlyRevenue(int $year): array {
    $months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
    $pdo    = getDB();
    $st     = $pdo->prepare("SELECT month, COALESCE(SUM(CASE WHEN status='Paid' THEN amount ELSE 0 END),0) AS collected, COALESCE(SUM(CASE WHEN status='Pending' THEN amount ELSE 0 END),0) AS pending, COALESCE(SUM(CASE WHEN status='Overdue' THEN amount ELSE 0 END),0) AS overdue FROM payments WHERE year=:year GROUP BY month");
    $st->execute([':year'=>$year]);
    $rows = [];
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) { $rows[$r['month']]=$r; }
    $result = [];
    foreach ($months as $m) { $result[] = isset($rows[$m]) ? $rows[$m] : ['month'=>$m,'collected'=>0,'pending'=>0,'overdue'=>0]; }
    return $result;
}

// ─────────────────────────────────────────────────────────────
// MAINTENANCE
// ─────────────────────────────────────────────────────────────
function getMaintenanceRequests(array $filters = []): array {
    try {
        $pdo = getDB();
        $sql = "SELECT mr.id, mr.category, mr.priority, mr.title, mr.description,
                       mr.status, mr.photos, mr.caretaker_notes, mr.resolved_at,
                       mr.created_at, mr.updated_at,
                       u.full_name AS tenant_name,
                       un.unit_number,
                       a.name AS apartment_name, a.id AS apartment_id
                FROM maintenance_requests mr
                JOIN users u ON u.id=mr.tenant_id
                LEFT JOIN units un ON un.id=mr.unit_id
                LEFT JOIN apartments a ON a.id=un.apartment_id
                WHERE 1=1";
        $params = [];
        if (!empty($filters['status']))       { $sql .= " AND mr.status=:status";      $params[':status']=$filters['status']; }
        if (!empty($filters['apartment_id'])) { $sql .= " AND a.id=:apt_id";           $params[':apt_id']=$filters['apartment_id']; }
        if (!empty($filters['tenant_id']))    { $sql .= " AND mr.tenant_id=:tenant_id"; $params[':tenant_id']=$filters['tenant_id']; }
        $sql .= " ORDER BY FIELD(mr.status,'Pending','In Progress','Resolved','Cancelled'), FIELD(mr.priority,'Emergency','Urgent','Normal'), mr.created_at DESC";
        $st = $pdo->prepare($sql); $st->execute($params);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) { return []; }
}

// ─────────────────────────────────────────────────────────────
// ANNOUNCEMENTS
// ─────────────────────────────────────────────────────────────
function getAnnouncementsForTenant(int $tenantId, int $apartmentId): array {
    try {
        $pdo = getDB();
        $st  = $pdo->prepare("SELECT a.id,a.title,a.body,a.type,a.created_at,a.expires_at,u.full_name AS author_name FROM announcements a JOIN users u ON u.id=a.created_by WHERE (a.expires_at IS NULL OR a.expires_at>NOW()) AND (a.target='all_tenants' OR (a.target='specific_apartment' AND a.target_id=:apt_id) OR (a.target='specific_tenant' AND a.target_id=:tenant_id)) ORDER BY a.created_at DESC LIMIT 20");
        $st->execute([':apt_id'=>$apartmentId,':tenant_id'=>$tenantId]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) { return []; }
}

function getAnnouncementsForCaretaker(int $apartmentId): array {
    try {
        $pdo = getDB();
        $st  = $pdo->prepare("SELECT a.id,a.title,a.body,a.type,a.created_at,u.full_name AS author_name FROM announcements a JOIN users u ON u.id=a.created_by WHERE (a.expires_at IS NULL OR a.expires_at>NOW()) AND (a.target='all_caretakers' OR (a.target='specific_apartment' AND a.target_id=:apt_id) OR a.target='all_tenants') ORDER BY a.created_at DESC LIMIT 20");
        $st->execute([':apt_id'=>$apartmentId]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) { return []; }
}

function getAllAnnouncements(): array {
    try {
        $pdo = getDB();
        $st  = $pdo->query("SELECT a.id,a.title,a.body,a.type,a.target,a.target_id,a.created_at,a.expires_at,u.full_name AS author_name,ap.name AS apartment_name FROM announcements a JOIN users u ON u.id=a.created_by LEFT JOIN apartments ap ON ap.id=a.target_id AND a.target='specific_apartment' ORDER BY a.created_at DESC");
        return $st->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) { return []; }
}

// ─────────────────────────────────────────────────────────────
// UTILITY BILLS
// ─────────────────────────────────────────────────────────────
function getUtilityBillsForTenant(int $tenantId): array {
    if (!_hasTable('utility_bills')) return [];
    $pdo = getDB();
    $st  = $pdo->prepare("SELECT ub.id,ub.bill_type,ub.description,ub.amount,ub.billing_month,ub.billing_year,ub.due_date,ub.status,ub.paid_date,ub.reference,u.full_name AS issued_by_name FROM utility_bills ub LEFT JOIN users u ON u.id=ub.issued_by WHERE ub.tenant_id=:tid ORDER BY ub.billing_year DESC, FIELD(ub.billing_month,'January','February','March','April','May','June','July','August','September','October','November','December') DESC");
    $st->execute([':tid'=>$tenantId]);
    return $st->fetchAll(PDO::FETCH_ASSOC);
}

function getUtilityBillsByApartment(int $apartmentId): array {
    if (!_hasTable('utility_bills')) return [];
    $pdo = getDB();
    $st  = $pdo->prepare("SELECT ub.*,u.full_name AS tenant_name,un.unit_number FROM utility_bills ub JOIN users u ON u.id=ub.tenant_id LEFT JOIN units un ON un.id=ub.unit_id WHERE ub.apartment_id=:apt_id ORDER BY ub.created_at DESC");
    $st->execute([':apt_id'=>$apartmentId]);
    return $st->fetchAll(PDO::FETCH_ASSOC);
}

// ─────────────────────────────────────────────────────────────
// NOTIFICATIONS
// ─────────────────────────────────────────────────────────────
function getUnreadNotificationCount(int $userId): int {
    if (!_hasTable('notifications')) return 0;
    try {
        $pdo = getDB();
        $st  = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id=:uid AND is_read=0");
        $st->execute([':uid'=>$userId]);
        return (int)$st->fetchColumn();
    } catch (Exception $e) { return 0; }
}

function getNotifications(int $userId, int $limit = 10): array {
    if (!_hasTable('notifications')) return [];
    try {
        $pdo = getDB();
        $st  = $pdo->prepare("SELECT id,type,title,body,link,is_read,created_at FROM notifications WHERE user_id=:uid ORDER BY created_at DESC LIMIT :lim");
        $st->bindValue(':uid',$userId,PDO::PARAM_INT);
        $st->bindValue(':lim',$limit,PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) { return []; }
}

function createNotification(int $userId, string $type, string $title, string $body='', string $link=''): void {
    if (!_hasTable('notifications')) return;
    $notificationPreference = [
        'tenant_registration' => 'new_tenant_registration',
        'maintenance_request' => 'maintenance_request_alerts',
        'move_in' => 'move_in_notifications',
        'move_out' => 'move_out_notifications',
        'rent_due' => 'rent_due_reminders',
        'overdue_payment' => 'overdue_payment_alerts',
    ][$type] ?? null;
    if ($notificationPreference !== null
        && getSetting('notification_' . $notificationPreference, '1') !== '1') {
        return;
    }
    try {
        $pdo = getDB();
        $pdo->prepare("INSERT INTO notifications (user_id,type,title,body,link) VALUES (:uid,:type,:title,:body,:link)")
            ->execute([':uid'=>$userId,':type'=>$type,':title'=>$title,':body'=>$body,':link'=>$link]);
    } catch (Exception $e) { error_log('createNotification: '.$e->getMessage()); }
}

function notificationLink(string $link): string {
    $link = trim($link);
    if ($link === '' || preg_match('#^(?:[a-z][a-z0-9+.-]*:|/)#i', $link)) {
        return $link;
    }

    $link = preg_replace('#^(?:\.\./)+#', '', $link);
    $link = ltrim($link, './');
    return baseUrl() . '/' . $link;
}

function markNotificationsRead(int $userId): void {
    if (!_hasTable('notifications')) return;
    try {
        $pdo = getDB();
        $pdo->prepare("
            UPDATE notifications 
            SET is_read = 1, read_at = NOW() 
            WHERE user_id = :uid AND is_read = 0
        ")->execute([':uid'=>$userId]);
    } catch (Exception $e) {
        error_log('Mark notifications read error: ' . $e->getMessage());
    }
}

// ─────────────────────────────────────────────────────────────
// CARETAKER DASHBOARD STATS
// ─────────────────────────────────────────────────────────────
function getCaretakerDashboardStats(int $apartmentId): array {
    $pdo    = getDB();
    $params = [':apt_id'=>$apartmentId];
    $s      = [];
    $s['apartment'] = getApartmentById($apartmentId) ?: [];

    $q = function($sql, $p=[]) use ($pdo) {
        $st = $pdo->prepare($sql); $st->execute($p); return $st->fetchColumn();
    };

    $s['total_units']      = (int)$q("SELECT COUNT(*) FROM units WHERE apartment_id=:apt_id",$params);
    $s['occupied_units']   = (int)$q("SELECT COUNT(*) FROM units WHERE apartment_id=:apt_id AND status='Occupied'",$params);
    $s['vacant_units']     = (int)$q("SELECT COUNT(*) FROM units WHERE apartment_id=:apt_id AND status='Vacant'",$params);
    $s['active_tenants']   = (int)$q("SELECT COUNT(*) FROM tenant_units tu JOIN units u ON u.id=tu.unit_id WHERE u.apartment_id=:apt_id AND tu.status='active'",$params);
    $s['pending_maint']    = (int)$q("SELECT COUNT(*) FROM maintenance_requests mr JOIN units u ON u.id=mr.unit_id WHERE u.apartment_id=:apt_id AND mr.status='Pending'",$params);
    $s['inprogress_maint'] = (int)$q("SELECT COUNT(*) FROM maintenance_requests mr JOIN units u ON u.id=mr.unit_id WHERE u.apartment_id=:apt_id AND mr.status='In Progress'",$params);
    $s['resolved_maint']   = (int)$q("SELECT COUNT(*) FROM maintenance_requests mr JOIN units u ON u.id=mr.unit_id WHERE u.apartment_id=:apt_id AND mr.status='Resolved'",$params);

    $pr = $pdo->prepare("SELECT COALESCE(SUM(CASE WHEN p.status='Paid' THEN p.amount ELSE 0 END),0) AS collected, COALESCE(SUM(CASE WHEN p.status='Pending' THEN p.amount ELSE 0 END),0) AS pending, COALESCE(SUM(CASE WHEN p.status='Overdue' THEN p.amount ELSE 0 END),0) AS overdue FROM payments p WHERE p.apartment_id=:apt_id AND p.month=:m AND p.year=:y");
    $pr->execute([':apt_id'=>$apartmentId,':m'=>date('F'),':y'=>(int)date('Y')]);
    $pay = $pr->fetch(PDO::FETCH_ASSOC) ?: [];
    $s['rent_collected'] = (float)($pay['collected']??0);
    $s['rent_pending']   = (float)($pay['pending']??0);
    $s['rent_overdue']   = (float)($pay['overdue']??0);

    return $s;
}

// ─────────────────────────────────────────────────────────────
// TENANT DASHBOARD DATA
// ─────────────────────────────────────────────────────────────
function getTenantDashboardData(int $tenantId): array {
    $data = getTenantById($tenantId) ?: [];
    $data += [
        'unit_id' => null,
        'unit_rent' => null,
        'assigned_rent' => null,
        'apartment_name' => '',
        'unit_number' => '',
        'apartment_id' => 0,
    ];
    $data['payments']    = getTenantYearlyPayments($tenantId,(int)date('Y'));
    $data['outstanding'] = getTenantOutstanding($tenantId);
    $data['bills']       = getUtilityBillsForTenant($tenantId);
    $data['maintenance'] = getMaintenanceRequests(['tenant_id'=>$tenantId]);
    $aptId = isset($data['apartment_id']) ? (int)$data['apartment_id'] : 0;
    $data['announcements'] = $aptId ? getAnnouncementsForTenant($tenantId,$aptId) : [];
    $data['current_month_payment'] = isset($data['payments'][date('F')]) ? $data['payments'][date('F')] : null;
    return $data;
}

// ─────────────────────────────────────────────────────────────
// SETTINGS
// ─────────────────────────────────────────────────────────────
function getSetting(string $key, string $default=''): string {
    static $cache=[];
    if (!isset($cache[$key])) {
        try {
            $pdo = getDB();
            $st  = $pdo->prepare("SELECT value FROM system_settings WHERE `key`=:key LIMIT 1");
            $st->execute([':key'=>$key]);
            $val = $st->fetchColumn();
            $cache[$key] = ($val!==false) ? $val : $default;
        } catch(Exception $e){ return $default; }
    }
    return $cache[$key];
}

function getAllSettings(): array {
    try {
        $pdo=getDB();
        $rows=$pdo->query("SELECT `key`,`value` FROM system_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
        return $rows?:[];
    } catch(Exception $e){return [];}
}

function rentAmountDue(float $baseAmount, string $month, int $year): float {
    if ($baseAmount <= 0) return 0.0;
    $dueDay = max(1, min(28, (int)getSetting('rent_due_day', '5')));
    $penaltyPercent = max(0.0, min(100.0, (float)getSetting('penalty_percent', '10')));
    $monthNumber = DateTime::createFromFormat('!F', $month);
    if (!$monthNumber) return $baseAmount;
    $dueDate = DateTime::createFromFormat('Y-n-j', "{$year}-{$monthNumber->format('n')}-{$dueDay}");
    $today = new DateTime('today');
    return ($dueDate && $today > $dueDate)
        ? round($baseAmount * (1 + ($penaltyPercent / 100)), 2)
        : $baseAmount;
}

// ─────────────────────────────────────────────────────────────
// HELPERS
// ─────────────────────────────────────────────────────────────
function rmsSessionTimeout(): int { return 1800; }
function rmsBruteForceEnabled(): bool { return true; }
function rmsMaxLoginAttempts(): int { return 10; }
function rmsLoginActivityEnabled(): bool { return true; }
function rmsPasswordMinLength(): int { return 8; }

function formatDate(?string $date): string {
    if (!$date) return '—';
    try { return (new DateTime($date))->format('j F Y'); }
    catch (Exception $e) { return $date; }
}

function formatKES($amount): string {
    return 'KES '.number_format((float)$amount,0);
}

function statusBadgeClass(string $status): string {
    $map = [
        'paid'=>'badge-paid','active'=>'badge-paid','resolved'=>'badge-paid',
        'occupied'=>'badge-paid','cleared'=>'badge-paid',
        'pending'=>'badge-pending',
        'overdue'=>'badge-overdue','bounced'=>'badge-overdue','terminated'=>'badge-overdue',
        'vacant'=>'badge-progress','reserved'=>'badge-progress','in progress'=>'badge-progress',
        'inactive'=>'badge-inactive','moved_out'=>'badge-inactive','moved out'=>'badge-inactive',
        'prospective'=>'badge-prospective',
    ];
    $key = strtolower($status);
    return isset($map[$key]) ? $map[$key] : 'badge-info';
}

function logActivity(int $userId, string $action, string $target='', $targetId=null, string $details=''): void {
    try {
        $pdo=getDB();
        $pdo->prepare("INSERT INTO activity_logs (user_id,action,target,target_id,details,ip) VALUES (:uid,:action,:target,:tid,:details,:ip)")
            ->execute([':uid'=>$userId,':action'=>$action,':target'=>$target,':tid'=>$targetId,':details'=>$details,':ip'=>isset($_SERVER['REMOTE_ADDR'])?$_SERVER['REMOTE_ADDR']:'']);
    } catch(Exception $e){error_log('logActivity: '.$e->getMessage());}
}
