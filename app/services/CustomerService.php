<?php

require_once __DIR__ . "/BaseService.php";
require_once __DIR__ . "/UserService.php";
require_once __DIR__ . "/CustomerAddressService.php";
require_once APP_PATH . "/repositories/CustomerRepository.php";

class CustomerService extends BaseService {
    private UserService $userService;
    private CustomerAddressService $customerAddressService;
    private CustomerRepository $customerRepository;

    public function __construct() {
        parent::__construct();
        $this->userService = new UserService();
        $this->customerAddressService = new CustomerAddressService();
        $this->customerRepository = new CustomerRepository();
    }

    public function validateInput(array $data): void {
        $this->validate($data, function($v) use ($data) {
            $v->accepted("termsAccepted", "Deve aceitar os termos e condições para continuar.");
        });
    }

    public function getCustomerProfile(int $userId): ?array {
        $user = $this->userService->find($userId);
        $customer = $this->customerRepository->find($userId);

        if (!$user || !$customer) {
            return null;
        }

        $addressesData = $this->customerAddressService->findCustomerAddresses($userId);

        return [
            "id"            => $customer["id"],
            "phoneVerified" => $customer["isMobileValidated"] ?? false,
            "createdAt"     => $customer["createdAt"] ?? null,
            "name"          => $user["name"],
            "email"         => $user["email"],
            "phone"         => $user["phone"],
            "nif"           => $user["nif"] ?? null,
            "profileType"   => $user["profileType"],
            "addresses"     => $addressesData["addresses"] ?? []
        ];
    }

    public function createCustomer(array $data): array {
        return $this->executeTransactional(function() use ($data) {
            $this->validateInput($data);

            $userResult = $this->userService->createUser($data);
            $userId = $userResult["userId"];

            $this->customerRepository->create($userId, $data);

            // A primeira morada do cliente passa a ser a principal (é a que o
            // wizard de agendamento pré-seleciona via `isMain`).
            $addressData = $data;
            $addressData["isMain"] = 1;

            $this->customerAddressService->createAddress($userId, $addressData);

            return [
                "id" => $userId,
                "message" => "Cliente registado com sucesso!"
            ];
        });
    }
}
