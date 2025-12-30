<?php
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function handle_login() {
    $username = strtolower(trim($_POST['username'] ?? ''));
    $password = $_POST['password'] ?? '';
    
    $db = get_db();
    $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user'] = $user;
        
        // Logging
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Unbekannt';
        
        // DB Log
        $stmtLog = $db->prepare("INSERT INTO login_log (user_id, ip_address, user_agent) VALUES (?, ?, ?)");
        $stmtLog->execute([$user['id'], $ip, $ua]);
        
        // HA Console Log
        error_log("✅ LOGIN SUCCESS: User '{$user['username']}' from {$ip}");
        
        header('Location: /');
    } else {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];
        error_log("❌ LOGIN FAILED: User '{$username}' from {$ip}");
        
        $_SESSION['flash_error'] = "Login fehlgeschlagen.";
        header('Location: /login');
    }
}

function logout() {
    session_destroy();
    header('Location: /login');
}

function api_change_password() {
    header('Content-Type: application/json');
    if (!is_logged_in()) { http_response_code(401); exit; }
    
    $data = json_decode(file_get_contents('php://input'), true);
    $db = get_db();
    
    $stmt = $db->prepare("SELECT password_hash FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $currentHash = $stmt->fetchColumn();
    
    if(!password_verify($data['old_password'], $currentHash)) {
        http_response_code(400); echo json_encode(["error" => "Altes Passwort falsch"]); return;
    }
    
    $newHash = password_hash($data['new_password'], PASSWORD_BCRYPT);
    $stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
    $stmt->execute([$newHash, $_SESSION['user_id']]);
    
    error_log("🔑 PASSWORD CHANGED: User ID {$_SESSION['user_id']}");
    echo json_encode(["status" => "success"]);
}

function get_login_logs($userId) {
    $db = get_db();
    $stmt = $db->prepare("SELECT * FROM login_log WHERE user_id = ? ORDER BY timestamp DESC LIMIT 30");
    $stmt->execute([$userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}