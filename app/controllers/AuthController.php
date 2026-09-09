<?php

require_once __DIR__ . "/BaseController.php";
require_once APP_PATH . "/services/AuthService.php";
require_once APP_PATH . "/services/CustomerService.php";

class AuthController extends BaseController {
    private $authService;
    private $customerService;

    public function __construct() {
        $this->authService = new AuthService();
        $this->customerService = new CustomerService();
    }

    public function register() {
        return $this->authService->register($this->getRequestData());
    }

    public function login() {
        return $this->authService->login($this->getRequestData());
    }

    public function logout() {
        return $this->authService->logout();
    }
}
