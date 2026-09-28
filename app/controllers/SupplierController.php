<?php

require_once __DIR__ . "/BaseController.php";
require_once APP_PATH . "/services/SupplierService.php";

/**
 * Fase 6.1 — Backoffice do GESTOR: fornecedores (RF-85 · §25.1).
 */
class SupplierController extends BaseController {
    private SupplierService $supplierService;

    public function __construct() {
        $this->supplierService = new SupplierService();
    }

    public function list(): array {
        Session::requireProfileApi(["gestor"]);
        return $this->supplierService->listSuppliers($_GET);
    }

    public function store(): array {
        Session::requireProfileApi(["gestor"]);
        return $this->supplierService->createSupplier($this->getRequestData());
    }

    public function update(): array {
        Session::requireProfileApi(["gestor"]);
        return $this->supplierService->updateSupplier($this->extractSupplierId(), $this->getRequestData());
    }

    public function setActive(): array {
        Session::requireProfileApi(["gestor"]);

        $data = $this->getRequestData();
        $supplierId = (int)($data["supplierId"] ?? $data["id"] ?? 0);
        $active = (bool)($data["active"] ?? false);

        return $this->supplierService->setActive($supplierId, $active);
    }

    private function extractSupplierId(): int {
        $data = $this->getRequestData();
        $supplierId = (int)($data["supplierId"] ?? $data["id"] ?? 0);

        if ($supplierId <= 0) {
            throw new Exception("Identificador de fornecedor inválido.", 422);
        }

        return $supplierId;
    }
}