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
        $cities = $this->cityRepository->find();
        $supportedCityNames = array_column($cities, 'name');

        $this->validate($data, function($v) use ($data, $supportedCityNames) {
            $v  ->required("street", "A morada é obrigatória.")
                ->required("doorNumber", "O número de porta é obrigatório.")
                ->required("zipCode", "O código postal é obrigatório.")
                ->zipCode("zipCode", "Insira um código postal válido no formato 0000-000.")
                ->contains("cityName", $supportedCityNames, "Lamentamos, mas de momento apenas aceitamos moradas nas cidades suportadas.");
        });
    }

    public function createAddress(int $customerId, array $data): array {
        $this->validateInput($data);

        $city = $this->cityRepository->find(null, $data['cityName']);
        if (!$city) {
            throw new Exception("Cidade não encontrada.", 404);
        }

        $addressId = $this->addressRepository->create($customerId, $city['id'], $data);

        return [
            "addressId" => $addressId,
            "message" => "Morada adicionada ao cliente com sucesso!"
        ];
    }

    public function findCustomerAddresses(int $customerId): array {
        return [
            "addresses" => $this->addressRepository->find($customerId)
        ];
    }

    public function setPrincipalAddress(int $customerId, int $addressId): array {
        return $this->executeTransactional(function() use ($customerId, $addressId) {
            $address = $this->addressRepository->find($customerId, $addressId);
            if (!$address) {
                throw new Exception("Morada não encontrada.", 404);
            }

            $this->addressRepository->updatePrincipal($customerId, 0);
            $this->addressRepository->updatePrincipal($customerId, 1, $addressId);

            return [
                "message" => "Morada principal atualizada com sucesso!"
            ];
        });
    }

    public function deleteAddress(int $customerId, int $addressId): array {
        $address = $this->addressRepository->find($customerId, $addressId);
        if (!$address) {
            throw new Exception("Morada não encontrada.", 404);
        }

        $this->addressRepository->delete($customerId, $addressId);

        return [
            "message" => "Morada removida com sucesso!"
        ];
    }
}
