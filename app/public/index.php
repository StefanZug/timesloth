<?php
session_start();
ini_set('display_errors', 0);
ini_set('log_errors', 1);

define('APP_ROOT', dirname(__DIR__));
define('TEMPLATE_PATH', APP_ROOT . '/templates');
define('DB_PATH', getenv('DB_FOLDER') . '/timesloth.sqlite');

require_once APP_ROOT . '/src/db.php';
require_once APP_ROOT . '/src/auth.php';
require_once APP_ROOT . '/src/api.php';

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// --- API (JSON) ---
if (str_starts_with($uri, '/api/')) {
    header('Content-Type: application/json');
    if (!is_logged_in()) { http_response_code(401); echo json_encode(['error' => 'Unauthorized']); exit; }

    if ($uri === '/api/get_entries') { api_get_entries(); }
    elseif ($uri === '/api/save_entry' && $method === 'POST') { api_save_entry(); }
    elseif ($uri === '/api/settings' && $method === 'POST') { api_save_settings(); }
    elseif ($uri === '/api/reset_month' && $method === 'POST') { api_reset_month(); }
    else { http_response_code(404); echo json_encode(['error' => 'Not found']); }
    exit;
}

// --- ADMIN API ---
if (str_starts_with($uri, '/admin/')) {
    if (!is_logged_in()) { http_response_code(401); exit; }
    
    // Einfaches Routing für Admin Actions
    if ($method === 'POST' && $uri === '/admin/create_user') { api_admin_create_user(); exit; }
    if ($method === 'POST' && $uri === '/admin/holiday') { api_admin_add_holiday(); exit; }
    
    // DELETE Handling (/admin/delete_user/123)
    if (preg_match('#^/admin/delete_user/(\d+)$#', $uri, $m)) { api_admin_delete_user($m[1]); exit; }
    if (preg_match('#^/admin/holiday/(\d+)$#', $uri, $m) && $method === 'DELETE') { api_admin_delete_holiday($m[1]); exit; }
}

// --- AUTH PAGES ---
if ($uri === '/login') {
    if ($method === 'POST') { handle_login(); }
    else { render_view('login', ['hide_nav' => true]); }
    exit;
}
if ($uri === '/logout') { logout(); exit; }
if ($uri === '/change_password' && $method === 'POST') { api_change_password(); exit; }

// --- APP PAGES ---
if (!is_logged_in()) { header('Location: /login'); exit; }

if ($uri === '/' || $uri === '/dashboard') {
    render_view('dashboard', ['user' => $_SESSION['user']]);
} elseif ($uri === '/settings') {
    $logs = get_login_logs($_SESSION['user']['id']);
    render_view('settings', ['user' => $_SESSION['user'], 'logs' => $logs]);
} elseif ($uri === '/admin') {
    // Admin Check
    if(!($_SESSION['user']['is_admin'] ?? false)) { header('Location: /'); exit; }
    
    $db = get_db();
    $users = $db->query("SELECT * FROM users ORDER BY username")->fetchAll();
    $holidays = $db->query("SELECT * FROM global_holidays ORDER BY date_str")->fetchAll();
    
    render_view('admin', ['user' => $_SESSION['user'], 'users' => $users, 'holidays' => $holidays]);
} else {
    header('Location: /');
}

function render_view($template, $data = []) {
    extract($data);
    ob_start();
    include TEMPLATE_PATH . "/$template.php";
    $content = ob_get_clean();
    include TEMPLATE_PATH . '/base.php';
}