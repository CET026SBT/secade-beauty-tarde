<?php

require_once __DIR__ . "/BaseController.php";
require_once APP_PATH . "/services/CustomerService.php";

class CustomerController extends BaseController {
    private CustomerService $customerService;

    public function __construct() {
        $this->customerService = new CustomerService();
    }

    public function profile(): array {
        $customerId = $this->requireCustomer();

        $profile = $this->customerService->getCustomerProfile($customerId);

        if (!$profile) {
            throw new Exception("Perfil não encontrado.", 404);
        }

        return ["profile" => $profile];
    }
}