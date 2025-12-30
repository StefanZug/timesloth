<?php
// --- USER API ---

function api_get_entries() {
    $db = get_db();
    $user_id = $_SESSION['user']['id'];
    $month = $_GET['month'] ?? date('Y-m');
    
    $stmt = $db->prepare("SELECT * FROM entries WHERE user_id = ? AND date_str LIKE ?");
    $stmt->execute([$user_id, "$month%"]);
    $rows = $stmt->fetchAll();
    
    $entries = [];
    foreach ($rows as $row) {
        $entries[] = [
            'date' => $row['date_str'],
            'blocks' => json_decode($row['data']),
            'status' => $row['status'],
            'comment' => $row['comment']
        ];
    }
    
    $stmtUser = $db->prepare("SELECT settings FROM users WHERE id = ?");
    $stmtUser->execute([$user_id]);
    $userSettings = json_decode($stmtUser->fetchColumn() ?: '{}', true);
    
    // Feiertage laden
    $stmtHol = $db->prepare("SELECT date_str, name FROM global_holidays WHERE date_str LIKE ?");
    $stmtHol->execute(["$month%"]);
    $holidaysRaw = $stmtHol->fetchAll();
    $holidayMap = [];
    foreach($holidaysRaw as $h) { $holidayMap[$h['date_str']] = $h['name']; }
    
    echo json_encode(['entries' => $entries, 'settings' => $userSettings, 'holidays' => $holidayMap]);
}

function api_save_entry() {
    $data = json_decode(file_get_contents('php://input'), true);
    $db = get_db();
    $user_id = $_SESSION['user']['id'];
    
    $stmt = $db->prepare("INSERT INTO entries (user_id, date_str, data, status, comment) 
                          VALUES (:uid, :date, :data, :status, :comment)
                          ON CONFLICT(user_id, date_str) DO UPDATE SET
                          data = :data, status = :status, comment = :comment");
    
    $stmt->execute([
        ':uid' => $user_id,
        ':date' => $data['date'],
        ':data' => json_encode($data['blocks']),
        ':status' => $data['status'],
        ':comment' => $data['comment'] ?? ''
    ]);
    echo json_encode(['status' => 'Saved']);
}

function api_save_settings() {
    $data = json_decode(file_get_contents('php://input'), true);
    $user_id = $_SESSION['user']['id'];
    $db = get_db();
    
    $stmt = $db->prepare("SELECT settings FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $current = json_decode($stmt->fetchColumn() ?: '{}', true);
    
    foreach($data as $k => $v) { $current[$k] = $v; }
    $newJson = json_encode($current);
    
    $stmtUpd = $db->prepare("UPDATE users SET settings = ? WHERE id = ?");
    $stmtUpd->execute([$newJson, $user_id]);
    $_SESSION['user']['settings'] = $newJson;
    
    echo json_encode(['status' => 'Saved']);
}

function api_reset_month() {
    $data = json_decode(file_get_contents('php://input'), true);
    $month = $data['month'] ?? '';
    if (strlen($month) !== 7) { http_response_code(400); echo json_encode(['error' => 'Invalid month']); return; }
    
    $db = get_db();
    $stmt = $db->prepare("DELETE FROM entries WHERE user_id = ? AND date_str LIKE ?");
    $stmt->execute([$_SESSION['user']['id'], "$month%"]);
    echo json_encode(['status' => 'Deleted']);
}

// --- ADMIN API ---

function api_admin_create_user() {
    if (!($_SESSION['user']['is_admin'] ?? false)) { http_response_code(403); exit; }
    $data = json_decode(file_get_contents('php://input'), true);
    $db = get_db();
    
    $username = strtolower(trim($data['username']));
    $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if($stmt->fetch()) { http_response_code(400); echo json_encode(['error' => 'User existiert schon']); return; }
    
    $hash = password_hash($data['password'], PASSWORD_BCRYPT);
    $isAdmin = !empty($data['is_admin']) ? 1 : 0;
    
    $stmtIns = $db->prepare("INSERT INTO users (username, password_hash, is_admin) VALUES (?, ?, ?)");
    $stmtIns->execute([$username, $hash, $isAdmin]);
    
    echo json_encode(['status' => 'Created']);
}

function api_admin_delete_user($id) {
    if (!($_SESSION['user']['is_admin'] ?? false)) { http_response_code(403); exit; }
    if ($id == $_SESSION['user']['id']) { http_response_code(400); echo json_encode(['error' => 'Nicht selbst löschen']); return; }
    
    $db = get_db();
    // User und seine Einträge werden durch Foreign Key Constraints (falls aktiv) oder manuell gelöscht
    // SQLite Standard FK ist oft off, also löschen wir manuell Entries
    $db->prepare("DELETE FROM entries WHERE user_id = ?")->execute([$id]);
    $db->prepare("DELETE FROM login_log WHERE user_id = ?")->execute([$id]);
    $db->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
    
    echo json_encode(['status' => 'Deleted']);
}

function api_admin_add_holiday() {
    if (!($_SESSION['user']['is_admin'] ?? false)) { http_response_code(403); exit; }
    $data = json_decode(file_get_contents('php://input'), true);
    $db = get_db();
    
    try {
        $stmt = $db->prepare("INSERT INTO global_holidays (date_str, name) VALUES (?, ?)");
        $stmt->execute([$data['date'], $data['name']]);
        echo json_encode(['status' => 'Created', 'id' => $db->lastInsertId()]);
    } catch(Exception $e) {
        http_response_code(400); echo json_encode(['error' => 'Datum existiert schon']);
    }
}

function api_admin_delete_holiday($id) {
    if (!($_SESSION['user']['is_admin'] ?? false)) { http_response_code(403); exit; }
    $db = get_db();
    $db->prepare("DELETE FROM global_holidays WHERE id = ?")->execute([$id]);
    echo json_encode(['status' => 'Deleted']);
}