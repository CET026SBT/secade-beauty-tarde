<?php

require_once __DIR__ . '/BaseService.php';
require_once __DIR__ . '/UserService.php';
require_once __DIR__ . '/CustomerAddressService.php';
require_once APP_PATH . '/repositories/CustomerRepository.php';

class CustomerService extends BaseService {
    private $userService;
    private $customerAddressService;
    private $customerRepository;

    public function __construct() {
        parent::__construct();
        $this->userService = new UserService();
        $this->customerAddressService = new CustomerAddressService();
        $this->customerRepository = new CustomerRepository();
    }

    public function validateInput(array $data): void {
        $this->validate($data, function($v) use ($data) {
            $v->accepted('termosCondicoes', 'Deve aceitar os termos e condições para continuar.');
        });
    }

    public function registerCustomer(array $data): array {
        return $this->executeTransactional(function() use ($data) {
            $this->validateInput($data);

            $userId = $this->userService->createUser($data);
            $this->customerRepository->create($userId, $data);
            $this->customerAddressService->addAddress($userId, $data);

            return ['message' => 'Cliente registado com sucesso!'];
        });
    }
}
