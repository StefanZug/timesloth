<?php
// 1. Setup
date_default_timezone_set('Europe/Vienna');
session_start();
ini_set('display_errors', 0);
ini_set('log_errors', 1);

define('APP_ROOT', dirname(__DIR__));
define('DB_PATH', getenv('DB_FOLDER') . '/timesloth.sqlite');

// 2. Core Dependencies
require_once APP_ROOT . '/src/Autoloader.php'; // Der neue Magier 🪄

// 3. Routing
$router = new Router(); // Autoloader findet 'Router' in /src/Router.php

// Auth Pages
$router->get('/login', 'AuthController', 'showLogin'); // Findet 'AuthController' in /src/Controllers/
$router->post('/login', 'AuthController', 'login');
$router->get('/logout', 'AuthController', 'logout');
$router->post('/change_password', 'AuthController', 'changePassword', true);

// App Pages (Protected)
$router->get('/', 'PageController', 'dashboard', true);
$router->get('/dashboard', 'PageController', 'dashboard', true);
$router->get('/settings', 'PageController', 'settings', true);
$router->get('/admin', 'PageController', 'admin', true, true);

// User API (Protected)
$router->get('/api/get_entries', 'ApiController', 'getEntries', true);
$router->post('/api/save_entry', 'ApiController', 'saveEntry', true);
$router->post('/api/reset_month', 'ApiController', 'resetMonth', true);
$router->get('/api/get_year_stats', 'ApiController', 'getYearStats', true);
$router->post('/api/settings', 'ApiController', 'updateSettings', true);

// Admin API (Admin only)
$router->post('/admin/create_user', 'AdminController', 'createUser', true, true);
$router->post('/admin/delete_user/(\d+)', 'AdminController', 'deleteUser', true, true);
$router->post('/admin/toggle_active/(\d+)', 'AdminController', 'toggleActive', true, true);
$router->post('/admin/reset_password/(\d+)', 'AdminController', 'resetPassword', true, true);
$router->get('/admin/user_logs/(\d+)', 'AdminController', 'getUserLogs', true, true);
$router->post('/admin/holiday', 'AdminController', 'addHoliday', true, true);
$router->delete('/admin/holiday/(\d+)', 'AdminController', 'deleteHoliday', true, true);
$router->get('/admin/stats', 'AdminController', 'stats', true, true);
$router->post('/admin/cleanup', 'AdminController', 'cleanup', true, true);
$router->post('/admin/toggle_admin/(\d+)', 'AdminController', 'toggleAdmin', true, true);
$router->post('/admin/toggle_cats/(\d+)', 'AdminController', 'toggleCats', true, true);

// --- CATSloth Frontend ---
// Ruft PageController->catsDashboard() auf. 
// "true" am Ende bedeutet: Nur eingeloggte User (Protected).
$router->get('/cats', 'PageController', 'catsDashboard', true);

// --- CATSloth API (JSON) ---
// Zugriff auf Projekte
$router->get('/api/cats/projects', 'CatsController', 'index', true);
$router->get('/api/cats/project/(\d+)', 'CatsController', 'show', true); // Regex für ID
$router->post('/api/cats/project', 'CatsController', 'create', true);
$router->post('/api/cats/project/(\d+)', 'CatsController', 'update', true);
$router->delete('/api/cats/project/(\d+)', 'CatsController', 'delete', true);

// Zugriff auf Team & Zuweisung
$router->post('/api/cats/allocation', 'CatsController', 'saveAllocation', true);
$router->delete('/api/cats/allocation', 'CatsController', 'deleteAllocation', true);

// Zugriff auf Buchungen
$router->post('/api/cats/booking', 'CatsController', 'saveBooking', true);

$router->get('/api/cats/users', 'CatsController', 'getUsers', true);

// 4. Run
$router->run();