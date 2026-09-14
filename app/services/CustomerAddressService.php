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
        $supportedCities = $this->cityRepository->findAllNames();

        $this->validate($data, function($v) use ($data, $supportedCities) {
            $v  ->required("morada", "A morada é obrigatória.")
                ->required("numPorta", "O número de porta é obrigatório.")
                ->required("codigoPostal", "O código postal é obrigatório.")
                ->zipCode("codigoPostal", "Insira um código postal válido no formato 0000-000.")
                ->contains("cidade", $supportedCities, "Lamentamos, mas de momento apenas aceitamos moradas nas cidades suportadas.");
        });
    }

    public function createAddress(int $customerId, array $data): array {
        $this->validateInput($data);

        $cityId = $this->cityRepository->findIdByName($data['cidade']);
        $addressId = $this->addressRepository->create($customerId, $cityId, $data);

        return [
            "addressId" => $addressId,
            "message" => "Morada adicionada ao cliente com sucesso!"
        ];
    }

    /**
     * Fetch all addresses of a customer, enriching each one with the city name
     * (composition done here, since the repository must not JOIN cross-context tables)
     * @param int $customerId
     * @return array
     */
    public function fetchCustomerAddresses(int $customerId): array {
        $addresses = $this->addressRepository->findByCustomerId($customerId);
        $cities = $this->cityRepository->findAll();

        $citiesById = [];
        foreach ($cities as $city) {
            $citiesById[$city["id"]] = $city;
        }

        foreach ($addresses as &$address) {
            $city = $citiesById[$address["cidade_id"]] ?? null;
            $address["cidade_nome"] = $city["nome"] ?? null;
            $address["distrito"] = $city["distrito"] ?? null;
        }

        return $addresses;
    }

    /**
     * Set an address as the customer's principal one.
     * Transaction managed here, coordinating two granular repository calls.
     * @param int $addressId
     * @param int $customerId
     * @return array
     */
    public function setPrincipalAddress(int $addressId, int $customerId): array {
        return $this->executeTransactional(function() use ($addressId, $customerId) {
            $address = $this->addressRepository->findByIdAndCustomerId($addressId, $customerId);
            if (!$address) {
                throw new Exception("Morada não encontrada.");
            }

            $this->addressRepository->unsetPrincipalForCustomer($customerId);
            $this->addressRepository->setPrincipal($addressId, $customerId);

            return [
                "message" => "Morada principal atualizada com sucesso!"
            ];
        });
    }

    /**
     * Delete an address belonging to a customer
     * @param int $addressId
     * @param int $customerId
     * @return array
     */
    public function deleteAddress(int $addressId, int $customerId): array {
        $address = $this->addressRepository->findByIdAndCustomerId($addressId, $customerId);
        if (!$address) {
            throw new Exception("Morada não encontrada.");
        }

        $this->addressRepository->delete($addressId, $customerId);

        return [
            "message" => "Morada removida com sucesso!"
        ];
    }
}
