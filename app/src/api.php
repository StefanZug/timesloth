<?php
// Services laden
require_once __DIR__ . '/Services/EntryService.php';
require_once __DIR__ . '/Services/UserService.php';
require_once __DIR__ . '/Services/AdminService.php';

function json_response($data) {
    echo json_encode($data);
    exit;
}

function json_error($msg, $code = 400) {
    http_response_code($code);
    echo json_encode(['error' => $msg]);
    exit;
}

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// 1. ENTRY API
if ($uri === '/api/get_entries') {
    if (!is_logged_in()) { json_error('Unauthorized', 401); }
    $service = new EntryService();
    $data = $service->getMonthEntries($_SESSION['user']['id'], $_GET['month'] ?? date('Y-m'));
    json_response($data);
}
if ($uri === '/api/save_entry' && $method === 'POST') {
    if (!is_logged_in()) { json_error('Unauthorized', 401); }
    $input = json_decode(file_get_contents('php://input'), true);
    $service = new EntryService();
    $res = $service->saveEntry($_SESSION['user']['id'], $input);
    json_response($res);
}
if ($uri === '/api/reset_month' && $method === 'POST') {
    if (!is_logged_in()) { json_error('Unauthorized', 401); }
    $input = json_decode(file_get_contents('php://input'), true);
    $service = new EntryService();
    try {
        $res = $service->resetMonth($_SESSION['user']['id'], $input['month'] ?? '');
        json_response($res);
    } catch (Exception $e) { json_error($e->getMessage()); }
}
// NEU: Year Stats für Kalender
if ($uri === '/api/get_year_stats') {
    if (!is_logged_in()) { json_error('Unauthorized', 401); }
    $service = new EntryService();
    $year = $_GET['year'] ?? date('Y');
    json_response($service->getYearStats($_SESSION['user']['id'], $year));
}

// 2. USER API
if ($uri === '/api/settings' && $method === 'POST') {
    if (!is_logged_in()) { json_error('Unauthorized', 401); }
    $input = json_decode(file_get_contents('php://input'), true);
    $service = new UserService();
    $res = $service->updateSettings($_SESSION['user']['id'], $input);
    json_response($res);
}

// 3. ADMIN API
if (str_starts_with($uri, '/admin/')) {
    if (!($_SESSION['user']['is_admin'] ?? false)) { json_error('Forbidden', 403); }
    
    $service = new AdminService();
    $input = json_decode(file_get_contents('php://input'), true);

    if ($method === 'POST' && $uri === '/admin/create_user') { 
        try {
            $res = $service->createUser($input['username'], $input['password'], !empty($input['is_admin']));
            json_response($res);
        } catch (Exception $e) { json_error($e->getMessage()); }
    }
    if ($method === 'POST' && $uri === '/admin/holiday') { 
        try {
            $res = $service->addHoliday($input['date'], $input['name']);
            json_response($res);
        } catch (Exception $e) { json_error($e->getMessage()); }
    }
    
    if ($uri === '/admin/stats') {
        json_response($service->getSystemStats());
    }
    if ($method === 'POST' && $uri === '/admin/cleanup') {
        json_response($service->clearOldLogs());
    }

    if (preg_match('#^/admin/delete_user/(\d+)$#', $uri, $m)) { 
        try { json_response($service->deleteUser($m[1], $_SESSION['user']['id'])); }
        catch (Exception $e) { json_error($e->getMessage()); }
    }
    if (preg_match('#^/admin/holiday/(\d+)$#', $uri, $m) && $method === 'DELETE') { 
        json_response($service->deleteHoliday($m[1])); 
    }
    if (preg_match('#^/admin/toggle_active/(\d+)$#', $uri, $m) && $method === 'POST') {
        try { json_response($service->toggleActive($m[1], $_SESSION['user']['id'])); }
        catch (Exception $e) { json_error($e->getMessage()); }
    }
    if (preg_match('#^/admin/user_logs/(\d+)$#', $uri, $m)) {
        json_response($service->getUserLogs($m[1]));
    }
    if (preg_match('#^/admin/reset_password/(\d+)$#', $uri, $m) && $method === 'POST') {
        try { json_response($service->resetUserPassword($m[1])); }
        catch (Exception $e) { json_error($e->getMessage()); }
    }
}