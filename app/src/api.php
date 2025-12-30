<?php
function api_get_entries() {
    $db = get_db();
    $user_id = $_SESSION['user']['id'];
    $month = $_GET['month'] ?? date('Y-m');
    
    // Einträge laden
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
    
    // User Settings neu laden (falls geändert)
    $stmtUser = $db->prepare("SELECT settings FROM users WHERE id = ?");
    $stmtUser->execute([$user_id]);
    $userSettings = json_decode($stmtUser->fetchColumn() ?: '{}', true);
    
    // Leeres Array für Feiertage (können wir später erweitern)
    echo json_encode(['entries' => $entries, 'settings' => $userSettings, 'holidays' => []]);
}

function api_save_entry() {
    $data = json_decode(file_get_contents('php://input'), true);
    $db = get_db();
    $user_id = $_SESSION['user']['id'];
    
    // SQLite Upsert
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
    
    // Alte Settings laden und mergen
    $stmt = $db->prepare("SELECT settings FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $current = json_decode($stmt->fetchColumn() ?: '{}', true);
    
    // Neue Werte überschreiben
    foreach($data as $k => $v) { $current[$k] = $v; }
    
    $newJson = json_encode($current);
    
    $stmtUpd = $db->prepare("UPDATE users SET settings = ? WHERE id = ?");
    $stmtUpd->execute([$newJson, $user_id]);
    
    // Session Update nicht vergessen!
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