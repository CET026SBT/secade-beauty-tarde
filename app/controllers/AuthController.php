<?php

require_once __DIR__ . "/BaseController.php";
require_once APP_PATH . "/services/AuthService.php";
require_once APP_PATH . "/services/CustomerService.php";

class AuthController extends BaseController {
    private AuthService $authService;
    private CustomerService $customerService;

    public function __construct() {
        $this->authService = new AuthService();
        $this->customerService = new CustomerService();
    }

    public function register(): array {
        return $this->authService->register($this->getRequestData());
    }

    public function login(): array {
        return $this->authService->authenticateLogin($this->getRequestData());
    }

    public function logout(): array {
        return $this->authService->terminateSession();
    }
}
