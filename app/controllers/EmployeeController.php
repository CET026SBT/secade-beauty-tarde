<?php

require_once __DIR__ . "/BaseController.php";
require_once APP_PATH . "/services/EmployeeService.php";
require_once APP_PATH . "/services/UserPhotoService.php";

/**
 * F7 — Recursos Humanos (só o GESTOR).
 *
 * Criar/editar funcionário, desativar com **fluxo de impacto** (§7.5) e gerir a
 * foto de perfil (reutiliza o componente de §4.6).
 */
class EmployeeController extends BaseController {
    private EmployeeService $employeeService;
    private UserPhotoService $photoService;

    public function __construct() {
        $this->employeeService = new EmployeeService();
        $this->photoService = new UserPhotoService();
    }

    public function list(): array {
        Session::requireProfileApi(["gestor"]);
        return $this->employeeService->formContext();
    }

    public function create(): array {
        Session::requireProfileApi(["gestor"]);
        return $this->employeeService->createEmployee($this->getRequestData());
    }

    public function update(): array {
        Session::requireProfileApi(["gestor"]);
        return $this->employeeService->updateEmployee($this->extractEmployeeId(), $this->getRequestData());
    }

    /** Pré-visualização do impacto da desativação (§7.5) — não altera nada. */
    public function impact(): array {
        Session::requireProfileApi(["gestor"]);
        return $this->employeeService->deactivationImpact($this->extractEmployeeId());
    }

    public function deactivate(): array {
        Session::requireProfileApi(["gestor"]);
        return $this->employeeService->deactivateEmployee($this->extractEmployeeId());
    }

    public function activate(): array {
        Session::requireProfileApi(["gestor"]);
        return $this->employeeService->activateEmployee($this->extractEmployeeId());
    }

    public function uploadPhoto(): array {
        Session::requireProfileApi(["gestor"]);
        return $this->photoService->upload($this->extractEmployeeId(), $this->getUploadedFile("photo"));
    }

    public function removePhoto(): array {
        Session::requireProfileApi(["gestor"]);
        return $this->photoService->remove($this->extractEmployeeId());
    }

    private function extractEmployeeId(): int {
        $data = $this->getRequestData();
        $employeeId = (int)($data["employeeId"] ?? $data["id"] ?? $_GET["employeeId"] ?? 0);

        if ($employeeId <= 0) {
            throw new Exception("Identificador de funcionário inválido.", 422);
        }

        return $employeeId;
    }
}