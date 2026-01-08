<?php
class BaseController {
    
    protected function json($data) {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    protected function jsonError($msg, $code = 400) {
        header('Content-Type: application/json');
        http_response_code($code);
        echo json_encode(['error' => $msg]);
        exit;
    }

    protected function render($template, $data = []) {
        extract($data);
        ob_start();
        include APP_ROOT . "/templates/$template.php";
        $content = ob_get_clean();
        include APP_ROOT . '/templates/base.php';
    }

    protected function getPostData() {
        $input = json_decode(file_get_contents('php://input'), true);
        return is_array($input) ? $input : [];
    }
}