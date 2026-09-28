<?php

require_once __DIR__ . "/BaseService.php";
require_once APP_PATH . "/repositories/SupplierRepository.php";

/**
 * Fornecedores — Fase 6.1 (RF-85 · §25.1 · §17.9).
 *
 * A página trabalha sobre a tabela `fornecedor` já carregada com os **43
 * fornecedores reais** (migração v4). Não há eliminação física: um fornecedor
 * pode ser citado por despesas (contabilidade, 6.2), pelo que "remover" é
 * **desativar** — o registo fica e sai das listagens ativas.
 */
class SupplierService extends BaseService {

    private SupplierRepository $supplierRepository;

    public function __construct() {
        parent::__construct();
        $this->supplierRepository = new SupplierRepository();
    }

    public function listSuppliers(array $query): array {
        $filters = [
            "term"   => trim((string)($query["term"] ?? "")) ?: null,
            "active" => isset($query["active"]) && $query["active"] !== "" ? $query["active"] : null
        ];

        $suppliers = $this->supplierRepository->search($filters);

        return [
            "suppliers" => $suppliers,
            "summary"   => $this->supplierRepository->summary(),
            "filters"   => ["term" => $filters["term"], "active" => $filters["active"]]
        ];
    }

    public function createSupplier(array $data): array {
        $this->validate($data, $this->rules());

        $data = $this->normalize($data);

        $supplierId = $this->supplierRepository->create($data);

        return [
            "supplierId" => $supplierId,
            "message"    => "Fornecedor criado com sucesso."
        ];
    }

    public function updateSupplier(int $supplierId, array $data): array {
        if ($supplierId <= 0) {
            throw new Exception("Identificador de fornecedor inválido.", 422);
        }

        $existing = $this->supplierRepository->find($supplierId);

        if (!$existing) {
            throw new Exception("Fornecedor não encontrado.", 404);
        }

        $this->validate($data, $this->rules());

        // Uma edição que não fale do estado NÃO reativa o fornecedor por engano.
        $hasActive = array_key_exists("active", $data);

        $data = $this->normalize($data);

        if (!$hasActive) {
            $data["active"] = !empty($existing["active"]) ? 1 : 0;
        }

        $this->supplierRepository->update($supplierId, $data);

        return [
            "supplierId" => $supplierId,
            "message"    => "Fornecedor atualizado com sucesso."
        ];
    }

    /**
     * Desativar/reativar (o botão "remover" das listagens é uma desativação).
     */
    public function setActive(int $supplierId, bool $active): array {
        if ($supplierId <= 0) {
            throw new Exception("Identificador de fornecedor inválido.", 422);
        }

        if (!$this->supplierRepository->find($supplierId)) {
            throw new Exception("Fornecedor não encontrado.", 404);
        }

        $this->supplierRepository->setActive($supplierId, $active);

        return [
            "supplierId" => $supplierId,
            "active"     => $active,
            "message"    => $active
                ? "Fornecedor reativado."
                : "Fornecedor desativado. O registo mantém-se (pode estar citado em despesas)."
        ];
    }

    /**
     * Regras de validação do fornecedor (uma só fonte para criar e editar).
     *
     * Os campos opcionais aceitam **vazio** (`?`) — um fornecedor real pode não
     * ter NIF (5 dos 43 entregues pelo cliente vêm sem NIF — §24.11).
     */
    private function rules(): callable {
        return function ($v) {
            $v->required("name", "Indique o nome do fornecedor.")
              ->regex("name", "/^.{2,150}$/u", "O nome deve ter entre 2 e 150 caracteres.")
              ->regex("nif", "/^([0-9A-Za-z]{1,20})?$/", "O NIF deve ter até 20 caracteres (números e letras).")
              ->regex("phone", "/^([0-9+()\\s\\-]{6,20})?$/", "O telemóvel deve ter entre 6 e 20 caracteres.")
              ->regex("email", "/^([^\\s@]+@[^\\s@]+\\.[^\\s@]+)?$/", "Endereço de email inválido.");
        };
    }

    /**
     * Campos ausentes ficam em branco (`NULL`) — nunca se inventa um valor.
     */
    private function normalize(array $data): array {
        return [
            "name"   => trim((string)($data["name"] ?? "")),
            "nif"    => trim((string)($data["nif"] ?? "")) ?: null,
            "email"  => trim((string)($data["email"] ?? "")) ?: null,
            "phone"  => trim((string)($data["phone"] ?? "")) ?: null,
            "active" => array_key_exists("active", $data) ? (int)(bool)$data["active"] : 1,
            "notes"  => trim((string)($data["notes"] ?? "")) ?: null
        ];
    }
}