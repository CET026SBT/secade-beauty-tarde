<?php

require_once __DIR__ . "/BaseController.php";
require_once APP_PATH . "/services/EmployeeService.php";
require_once APP_PATH . "/services/EmployeePhotoService.php";

/**
 * F7 — Recursos Humanos (§7 · §25). Só o gestor.
 */
class EmployeeController extends BaseController {

    private EmployeeService $employeeService;
    private EmployeePhotoService $photoService;

    public function __construct() {
        $this->employeeService = new EmployeeService();
        $this->photoService = new EmployeePhotoService();
    }

    public function list(): array {
        Session::requireProfileApi(["gestor"]);
        return $this->employeeService->listEmployees();
    }

    public function store(): array {
        Session::requireProfileApi(["gestor"]);
        return $this->employeeService->createEmployee($this->getRequestData());
    }

    public function update(): array {
        Session::requireProfileApi(["gestor"]);
        return $this->employeeService->updateEmployee($this->requireEmployeeId(), $this->getRequestData());
    }

    public function deactivateImpact(): array {
        Session::requireProfileApi(["gestor"]);
        return $this->employeeService->deactivateImpact($this->requireEmployeeId());
    }

    public function setActive(): array {
        Session::requireProfileApi(["gestor"]);

        $data = $this->getRequestData();
        $active = filter_var($data["active"] ?? false, FILTER_VALIDATE_BOOLEAN);

        return $this->employeeService->setActive($this->requireEmployeeId(), $active);
    }

    public function uploadPhoto(): array {
        Session::requireProfileApi(["gestor"]);
        $employeeId = (int)($_POST["employeeId"] ?? 0);

        if ($employeeId <= 0) {
            throw new Exception("Identificador de funcionário inválido.", 422);
        }

        return $this->photoService->upload($employeeId, $_FILES["photo"] ?? null);
    }

    private function requireEmployeeId(): int {
        $data = $this->getRequestData();
        $employeeId = (int)($data["employeeId"] ?? $data["id"] ?? 0);

        if ($employeeId <= 0) {
            throw new Exception("Identificador de funcionário inválido.", 422);
        }

        return $employeeId;
    }
}