<?php
function api_get_entries() {
    $db = get_db();
    $user_id = $_SESSION['user']['id'];
    $month = $_GET['month'] ?? date('Y-m'); // '2025-01'
    
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
    
    // Settings laden
    $stmtUser = $db->prepare("SELECT settings FROM users WHERE id = ?");
    $stmtUser->execute([$user_id]);
    $userSettings = json_decode($stmtUser->fetchColumn(), true);
    
    // Feiertage könnte man hier auch noch aus einer Tabelle laden
    
    echo json_encode(['entries' => $entries, 'settings' => $userSettings, 'holidays' => []]);
}

function api_save_entry() {
    $data = json_decode(file_get_contents('php://input'), true);
    $db = get_db();
    $user_id = $_SESSION['user']['id'];
    
    // Upsert Logic (SQLite supportet ON CONFLICT)
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