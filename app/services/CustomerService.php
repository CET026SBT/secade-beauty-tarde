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
            $v->accepted("termosCondicoes", "Deve aceitar os termos e condições para continuar.");
        });
    }

    /**
     * Fetch full customer profile, composing data from utilizador + cliente
     * (composition done here, since each Repository must stay scoped to its own table)
     * @param int $userId
     * @return array|null
     */
    public function fetchCustomerProfile(int $userId): ?array {
        $user = $this->userService->findById($userId);
        $customer = $this->customerRepository->findById($userId);

        if (!$user || !$customer) {
            return null;
        }

        return [
            "id"                     => $customer["id"],
            "morada"                 => $customer["morada"],
            "telemovel_validado_otp" => $customer["telemovel_validado_otp"],
            "data_registo"           => $customer["data_registo"],
            "nome"                   => $user["nome"],
            "email"                  => $user["email"],
            "telemovel"              => $user["telemovel"],
            "nif"                    => $user["nif"] ?? null,
            "tipo_perfil"            => $user["tipo_perfil"]
        ];
    }

    public function createCustomer(array $data): array {
        return $this->executeTransactional(function() use ($data) {
            $this->validateInput($data);

            $userResult = $this->userService->createUser($data);
            $userId = $userResult["userId"];

            $this->customerRepository->create($userId, $data);
            $this->customerAddressService->createAddress($userId, $data);

            return [
                "userId" => $userId,
                "message" => "Cliente registado com sucesso!"
            ];
        });
    }
}
