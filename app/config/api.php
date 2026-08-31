<?php

$action = $_GET['action'] ?? '';

$routes = [
    'auth-register' => ['controller' => 'AuthController', 'method' => 'register', 'http' => 'POST'],
    'auth-login'    => ['controller' => 'AuthController', 'method' => 'login', 'http' => 'POST'],
    'auth-logout'   => ['controller' => 'AuthController', 'method' => 'logout', 'http' => 'POST'],
];

try {
    if (!isset($routes[$action])) {
        throw new Exception("Invalid endpoint.", 404);
    }

    $route = $routes[$action];

    if ($_SERVER['REQUEST_METHOD'] !== $route['http']) {
        throw new Exception("Invalid HTTP method.", 405);
    }

    $controllerName = $route['controller'];
    require_once APP_PATH . "/controllers/{$controllerName}.php";

    if (!class_exists($controllerName)) {
        throw new Exception("Invalid controller.", 500);
    }
    
    $controllerInstance = new $controllerName();
    $methodName = $route['method'];

    if (!method_exists($controllerInstance, $methodName)) {
        throw new Exception("Method not found in controller.", 500);
    }

    $responsedata = $controllerInstance->$methodName();

    http_response_code(200);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge(['success' => true], $responsedata));

} catch (ValidationException $e) {
    http_response_code(422);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'errors'  => $e->getErrors()
    ]);

} catch (Exception $e) {
    $statusCode = $e->getCode() >= 400 && $e->getCode() <= 599 ? $e->getCode() : 400;
    
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}
