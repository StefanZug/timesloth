<?php
// Services laden
require_once __DIR__ . '/Services/EntryService.php';
require_once __DIR__ . '/Services/UserService.php';
require_once __DIR__ . '/Services/AdminService.php';

// Hilfsfunktion für JSON Response
function json_response($data) {
    echo json_encode($data);
    exit;
}

function json_error($msg, $code = 400) {
    http_response_code($code);
    echo json_encode(['error' => $msg]);
    exit;
}

// --- ROUTING ---

// 1. ENTRY API
function api_get_entries() {
    $service = new EntryService();
    $data = $service->getMonthEntries($_SESSION['user']['id'], $_GET['month'] ?? date('Y-m'));
    json_response($data);
}

function api_save_entry() {
    $input = json_decode(file_get_contents('php://input'), true);
    $service = new EntryService();
    $res = $service->saveEntry($_SESSION['user']['id'], $input);
    json_response($res);
}

function api_reset_month() {
    $input = json_decode(file_get_contents('php://input'), true);
    $service = new EntryService();
    try {
        $res = $service->resetMonth($_SESSION['user']['id'], $input['month'] ?? '');
        json_response($res);
    } catch (Exception $e) {
        json_error($e->getMessage());
    }
}

// 2. USER API
function api_save_settings() {
    $input = json_decode(file_get_contents('php://input'), true);
    $service = new UserService();
    $res = $service->updateSettings($_SESSION['user']['id'], $input);
    json_response($res);
}

// 3. ADMIN API
function api_admin_create_user() {
    if (!($_SESSION['user']['is_admin'] ?? false)) json_error('Forbidden', 403);
    
    $input = json_decode(file_get_contents('php://input'), true);
    $service = new AdminService();
    try {
        $res = $service->createUser($input['username'], $input['password'], !empty($input['is_admin']));
        json_response($res);
    } catch (Exception $e) {
        json_error($e->getMessage());
    }
}

function api_admin_delete_user($id) {
    if (!($_SESSION['user']['is_admin'] ?? false)) json_error('Forbidden', 403);
    
    $service = new AdminService();
    try {
        $res = $service->deleteUser($id, $_SESSION['user']['id']);
        json_response($res);
    } catch (Exception $e) {
        json_error($e->getMessage());
    }
}

function api_admin_add_holiday() {
    if (!($_SESSION['user']['is_admin'] ?? false)) json_error('Forbidden', 403);
    
    $input = json_decode(file_get_contents('php://input'), true);
    $service = new AdminService();
    try {
        $res = $service->addHoliday($input['date'], $input['name']);
        json_response($res);
    } catch (Exception $e) {
        json_error($e->getMessage());
    }
}

function api_admin_delete_holiday($id) {
    if (!($_SESSION['user']['is_admin'] ?? false)) json_error('Forbidden', 403);
    
    $service = new AdminService();
    $res = $service->deleteHoliday($id);
    json_response($res);
}