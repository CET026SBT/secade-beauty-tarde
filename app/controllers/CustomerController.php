<?php

require_once __DIR__ . "/BaseController.php";
require_once APP_PATH . "/services/CustomerService.php";
require_once APP_PATH . "/services/UserPhotoService.php";
require_once APP_PATH . "/repositories/UserRepository.php";

class CustomerController extends BaseController {
    private CustomerService $customerService;

    public function __construct() {
        $this->customerService = new CustomerService();
    }

    public function profile(): array {
        Session::requireProfileApi(["cliente"]);

        $profile = $this->customerService->getCustomerProfile(Session::userId());

        if (!$profile) {
            throw new Exception("Perfil não encontrado.", 404);
        }

        return ["profile" => $profile];
    }

    /** F9: o cliente edita os seus dados de contacto (nome, telemóvel, NIF). */
    public function update(): array {
        Session::requireProfileApi(["cliente"]);

        $result = $this->customerService->updateProfile(Session::userId(), $this->getRequestData());

        // O nome aparece no menu de topo: refresca-se a sessão para não ficar obsoleto.
        (new UserRepository())->refreshSessionName(Session::userId());

        return $result;
    }
}