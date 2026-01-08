<?php
class Router {
    private $routes = [];

    public function add($method, $path, $controller, $action, $isProtected = false, $isAdmin = false) {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'controller' => $controller,
            'action' => $action,
            'protected' => $isProtected,
            'admin' => $isAdmin
        ];
    }

    public function get($path, $controller, $action, $isProtected = false, $isAdmin = false) {
        $this->add('GET', $path, $controller, $action, $isProtected, $isAdmin);
    }

    public function post($path, $controller, $action, $isProtected = false, $isAdmin = false) {
        $this->add('POST', $path, $controller, $action, $isProtected, $isAdmin);
    }

    public function delete($path, $controller, $action, $isProtected = false, $isAdmin = false) {
        $this->add('DELETE', $path, $controller, $action, $isProtected, $isAdmin);
    }

    public function run() {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $method = $_SERVER['REQUEST_METHOD'];

        foreach ($this->routes as $route) {
            // Prüfe Methode
            if ($route['method'] !== $method) continue;

            // Prüfe Pfad (Regex Support)
            $pattern = "#^" . $route['path'] . "$#";
            if (preg_match($pattern, $uri, $matches)) {
                
                // 1. Auth Check
                if ($route['protected'] && !isset($_SESSION['user_id'])) {
                    if ($method === 'GET' && !str_starts_with($uri, '/api')) {
                        header('Location: /login');
                        exit;
                    }
                    http_response_code(401);
                    echo json_encode(['error' => 'Unauthorized']);
                    exit;
                }

                // 2. Admin Check
                if ($route['admin'] && empty($_SESSION['user']['is_admin'])) {
                    http_response_code(403);
                    echo json_encode(['error' => 'Forbidden']);
                    exit;
                }

                // Controller instanziieren und Action aufrufen
                array_shift($matches); // Den vollen Match entfernen, nur Gruppen behalten
                $controllerName = $route['controller'];
                $controller = new $controllerName();
                call_user_func_array([$controller, $route['action']], $matches);
                return;
            }
        }

        // 404 Not Found
        http_response_code(404);
        if (str_starts_with($uri, '/api')) {
            echo json_encode(['error' => 'Not found']);
        } else {
            echo "404 - Nothing to see here but sloths.";
        }
    }
}