<?php
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function parse_user_agent($ua) {
    $platform = 'Unbekannt';
    $browser = 'Unbekannt';
    
    if (preg_match('/windows|win32/i', $ua)) $platform = 'Windows';
    elseif (preg_match('/android/i', $ua)) $platform = 'Android';
    elseif (preg_match('/iphone|ipad|ios/i', $ua)) $platform = 'iOS';
    elseif (preg_match('/macintosh|mac os x/i', $ua)) $platform = 'Mac';
    elseif (preg_match('/linux/i', $ua)) $platform = 'Linux';
    
    if (preg_match('/firefox/i', $ua)) $browser = 'Firefox';
    elseif (preg_match('/edg/i', $ua)) $browser = 'Edge';
    elseif (preg_match('/chrome|crios/i', $ua)) $browser = 'Chrome';
    elseif (preg_match('/safari/i', $ua)) $browser = 'Safari';
    
    return "$platform / $browser";
}

function handle_login() {
    $username = strtolower(trim($_POST['username'] ?? ''));
    $password = $_POST['password'] ?? '';
    
    $db = get_db();
    $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password_hash'])) {
        if (isset($user['is_active']) && $user['is_active'] == 0) {
            error_log("🚫 LOGIN BLOCKED: Inactive User '{$username}'");
            $_SESSION['flash_error'] = "Account deaktiviert. Bitte Admin kontaktieren.";
            header('Location: /login');
            exit;
        }

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user'] = $user;
        
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Unbekannt';
        
        // FIX: Zeitstempel explizit speichern (in lokaler Zeit)
        // Statt sich auf DB DEFAULT CURRENT_TIMESTAMP (UTC) zu verlassen
        $stmtLog = $db->prepare("INSERT INTO login_log (user_id, ip_address, user_agent, timestamp) VALUES (?, ?, ?, ?)");
        $stmtLog->execute([$user['id'], $ip, $ua, date('Y-m-d H:i:s')]);
        
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
    // FIX: Auch hier explizite Zeit für konsistente Anzeige
    $stmt = $db->prepare("UPDATE users SET password_hash = ?, pw_last_changed = ? WHERE id = ?");
    $stmt->execute([$newHash, date('Y-m-d H:i:s'), $_SESSION['user_id']]);
    
    error_log("🔑 PASSWORD CHANGED: User ID {$_SESSION['user_id']}");
    echo json_encode(["status" => "success"]);
}

function get_login_logs($userId) {
    $db = get_db();
    $stmt = $db->prepare("SELECT * FROM login_log WHERE user_id = ? ORDER BY timestamp DESC LIMIT 30");
    $stmt->execute([$userId]);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach($logs as &$log) {
        $log['browser_short'] = parse_user_agent($log['user_agent']);
    }
    return $logs;
}