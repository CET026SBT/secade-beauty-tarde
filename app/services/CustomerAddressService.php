<?php

require_once __DIR__ . "/BaseService.php";
require_once APP_PATH . "/repositories/CustomerAddressRepository.php";
require_once APP_PATH . "/repositories/CityRepository.php";

class CustomerAddressService extends BaseService {
    private CustomerAddressRepository $addressRepository;
    private CityRepository $cityRepository;

    public function __construct() {
        parent::__construct();
        $this->addressRepository = new CustomerAddressRepository();
        $this->cityRepository = new CityRepository();
    }

    public function validateInput(array $data): void {
        $supportedCities = $this->cityRepository->getAllNames();

        $this->validate($data, function($v) use ($data, $supportedCities) {
            $v  ->required("morada", "A morada é obrigatória.")
                ->required("numPorta", "O número de porta é obrigatório.")
                ->required("codigoPostal", "O código postal é obrigatório.")
                ->zipCode("codigoPostal", "Insira um código postal válido no formato 0000-000.")
                ->contains("cidade", $supportedCities, "Lamentamos, mas de momento apenas aceitamos moradas nas cidades suportadas.");
        });
    }

    public function addAddress(int $userId, array $data): array {
        $this->validateInput($data);

        $cityId = $this->cityRepository->getCityIdByName($data['cidade']);
        $this->addressRepository->create($userId, $cityId, $data);

        return [ "message" => "Morada adicionada ao cliente com sucesso!" ];
    }
}
