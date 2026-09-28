<?php

require_once __DIR__ . "/BaseController.php";
require_once APP_PATH . "/services/CustomerAddressService.php";

class CustomerAddressController extends BaseController {
    private CustomerAddressService $addressService;

    public function __construct() {
        $this->addressService = new CustomerAddressService();
    }

    public function index(): array {
        Session::requireProfileApi(["cliente"]);
        return $this->addressService->findCustomerAddresses(Session::userId());
    }

    public function store(): array {
        Session::requireProfileApi(["cliente"]);
        return $this->addressService->createAddress(Session::userId(), $this->getRequestData());
    }

    public function setPrincipal(): array {
        Session::requireProfileApi(["cliente"]);
        return $this->addressService->setPrincipalAddress(Session::userId(), $this->extractAddressId());
    }

    public function delete(): array {
        Session::requireProfileApi(["cliente"]);
        return $this->addressService->deleteAddress(Session::userId(), $this->extractAddressId());
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