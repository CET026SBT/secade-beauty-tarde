<?php

require_once __DIR__ . "/BaseController.php";
require_once APP_PATH . "/services/CustomerAddressService.php";

class CustomerAddressController extends BaseController {
    private CustomerAddressService $addressService;

    public function __construct() {
        $this->addressService = new CustomerAddressService();
    }

    public function index(): array {
        $customerId = $this->requireCustomer();
        return $this->addressService->findCustomerAddresses($customerId);
    }

    public function store(): array {
        $customerId = $this->requireCustomer();
        return $this->addressService->createAddress($customerId, $this->getRequestData());
    }

    public function setPrincipal(): array {
        $customerId = $this->requireCustomer();
        return $this->addressService->setPrincipalAddress($customerId, $this->extractAddressId());
    }

    public function delete(): array {
        $customerId = $this->requireCustomer();
        return $this->addressService->deleteAddress($customerId, $this->extractAddressId());
    }

    private function extractAddressId(): int {
        $data = $this->getRequestData();
        $addressId = (int)($data["addressId"] ?? $data["id"] ?? 0);

        if ($addressId <= 0) {
            throw new Exception("Identificador de morada inválido.", 422);
        }

        return $addressId;
    }
}