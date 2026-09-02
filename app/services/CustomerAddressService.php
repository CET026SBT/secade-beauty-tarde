<?php

require_once __DIR__ . '/BaseService.php';
require_once APP_PATH . '/repositories/CustomerAddressRepository.php';

class CustomerAddressService extends BaseService {
    private $addressRepository;
    // sbASK Puxar da base de dados
    private static $supportedCities = ['Évora'];

    public function __construct() {
        parent::__construct();
        $this->addressRepository = new CustomerAddressRepository();
    }

    public function validateInput(array $data): void {
        $this->validate($data, function($v) use ($data) {
            $v  ->required('morada', 'A morada é obrigatória.')
                ->required('numPorta', 'O número de porta é obrigatório.')
                ->required('codigoPostal', 'O código postal é obrigatório.')
                ->zipCode('codigoPostal', "Insira um código postal válido no formato 0000-000.")
                ->contains('cidade', self::$supportedCities, "Lamentamos, mas de momento apenas aceitamos moradas nas cidades suportadas.");
        });
    }

    public function addAddress(int $userId, array $data): array {
        $this->validateInput($data);
        $this->addressRepository->create($userId, 10, $data);

        return [ 'message' => 'Morada adicionada ao cliente com sucesso!' ];
    }
}
