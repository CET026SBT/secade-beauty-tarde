<?php

$action = $_GET["action"] ?? "";

$routes = [
    "auth-login"     => ["controller" => "AuthController", "method" => "login", "http" => "POST"],
    "auth-logout"    => ["controller" => "AuthController", "method" => "logout", "http" => "POST"],
    "auth-register"  => ["controller" => "AuthController", "method" => "register", "http" => "POST"],
    
        "city-supported" => ["controller" => "CityController", "method" => "findSupportedCities", "http" => "GET"],
        "category-all"   => ["controller" => "CategoryController", "method" => "findAll", "http" => "GET"],

        "booking-services"       => ["controller" => "BookingController", "method" => "serviceList", "http" => "GET"],
        "booking-availability"   => ["controller" => "BookingController", "method" => "availability", "http" => "GET"],
        "booking-otp-request"    => ["controller" => "BookingController", "method" => "otpRequest", "http" => "POST"],
        "booking-create-store"   => ["controller" => "BookingController", "method" => "createStoreBooking", "http" => "POST"],
        "booking-create-amb"     => ["controller" => "BookingController", "method" => "createAmbulatoryBooking", "http" => "POST"],
        "booking-my"             => ["controller" => "BookingController", "method" => "myBookings", "http" => "GET"],

        "customer-profile"            => ["controller" => "CustomerController", "method" => "profile", "http" => "GET"],
        "customer-address-list"       => ["controller" => "CustomerAddressController", "method" => "index", "http" => "GET"],
        "customer-address-store"      => ["controller" => "CustomerAddressController", "method" => "store", "http" => "POST"],
        "customer-address-set-principal" => ["controller" => "CustomerAddressController", "method" => "setPrincipal", "http" => "POST"],
        "customer-address-delete"     => ["controller" => "CustomerAddressController", "method" => "delete", "http" => "POST"],

        "admin-appointments-list"  => ["controller" => "AdminController", "method" => "appointmentsList", "http" => "GET"],
        "admin-appointment-cancel" => ["controller" => "AdminController", "method" => "appointmentCancel", "http" => "POST"],
        "admin-appointment-details"=> ["controller" => "AdminController", "method" => "appointmentDetails", "http" => "GET"],
        "admin-appointment-execute"=> ["controller" => "AdminController", "method" => "appointmentExecute", "http" => "POST"],

        "admin-routes-list"        => ["controller" => "RotaController", "method" => "routesList", "http" => "GET"],
        "admin-route-decide"       => ["controller" => "RotaController", "method" => "decideRoute", "http" => "POST"],

        "admin-service-pending-list" => ["controller" => "ServiceController", "method" => "pendingList", "http" => "GET"],
        "admin-service-accepted-list"=> ["controller" => "ServiceController", "method" => "acceptedList", "http" => "GET"],
        "admin-service-accept"       => ["controller" => "ServiceController", "method" => "accept", "http" => "POST"],
        "admin-service-unaccept"     => ["controller" => "ServiceController", "method" => "unaccept", "http" => "POST"],

        "admin-fiscal-calendar-list" => ["controller" => "FiscalController", "method" => "calendar", "http" => "GET"],
        "admin-fiscal-alert-list"    => ["controller" => "FiscalController", "method" => "alerts", "http" => "GET"],
        "admin-fiscal-obligation-create" => ["controller" => "FiscalController", "method" => "createObligation", "http" => "POST"],
        "admin-fiscal-obligation-paid"   => ["controller" => "FiscalController", "method" => "markPaid", "http" => "POST"],
        "admin-fiscal-alert-read"        => ["controller" => "FiscalController", "method" => "markAlertsRead", "http" => "POST"],
        "admin-green-receipt-config"     => ["controller" => "FiscalController", "method" => "greenReceiptConfig", "http" => "GET"],
        "admin-green-receipt-config-save"=> ["controller" => "FiscalController", "method" => "saveGreenReceiptConfig", "http" => "POST"],
        "admin-green-receipt-simulate"   => ["controller" => "FiscalController", "method" => "greenReceiptSimulate", "http" => "GET"],

        "feedback-list"   => ["controller" => "FeedbackController", "method" => "publicList", "http" => "GET"],
        "feedback-my"     => ["controller" => "FeedbackController", "method" => "myState", "http" => "GET"],
        "feedback-create" => ["controller" => "FeedbackController", "method" => "create", "http" => "POST"]
    ];

try {
    if (!isset($routes[$action])) {
        throw new Exception("Invalid endpoint.", 404);
    }

    $route = $routes[$action];

    if ($_SERVER["REQUEST_METHOD"] !== $route["http"]) {
        throw new Exception("Invalid HTTP method.", 405);
    }

    $controllerName = $route["controller"];
    require_once APP_PATH . "/controllers/{$controllerName}.php";

    if (!class_exists($controllerName)) {
        throw new Exception("Invalid controller.", 500);
    }
    
    $controllerInstance = new $controllerName();
    $methodName = $route["method"];

    if (!method_exists($controllerInstance, $methodName)) {
        throw new Exception("Method not found in controller.", 500);
    }

    $responsedata = $controllerInstance->$methodName();

    http_response_code(200);
    header("Content-Type: application/json; charset=utf-8");
    echo json_encode(array_merge(["success" => true], $responsedata));

} catch (ValidationException $e) {
    http_response_code(422);
    header("Content-Type: application/json; charset=utf-8");
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage(),
        "errors"  => $e->getErrors()
    ]);

} catch (Exception $e) {
    $statusCode = $e->getCode() >= 400 && $e->getCode() <= 599 ? $e->getCode() : 400;
    
    http_response_code($statusCode);
    header("Content-Type: application/json; charset=utf-8");
    echo json_encode([
        "success" => false, 
        "message" => $e->getMessage()
    ]);
}
