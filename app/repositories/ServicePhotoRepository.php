<?php

require_once __DIR__ . "/BaseRepository.php";
require_once APP_PATH . "/mappers/ServicePhotoMapper.php";

/**
 * Fotos dos serviços (`servico_foto`) — §3.4.4 / F3.1.
 *
 * A imagem do card é a de `destaque = 1`; as restantes servem o carousel dos detalhes.
 */
class ServicePhotoRepository extends BaseRepository {

    protected ?string $mapper = ServicePhotoMapper::class;

    /** Todas as fotos de um serviço (destaque primeiro, depois a ordem do carousel). */
    public function findByService(int $serviceId): array {
        $sql = "SELECT id, servico_id, url_foto, destaque, ordem_exibicao
                FROM servico_foto
                WHERE servico_id = :servico_id
                ORDER BY destaque DESC, ordem_exibicao ASC, id ASC";

        $rows = $this->fetchAll($sql, ["servico_id" => $serviceId]);
        return is_array($rows) ? $rows : [];
    }

    /** Foto de destaque (a do card) de um serviço, ou `null`. */
    public function findFeatured(int $serviceId): ?array {
        $sql = "SELECT id, servico_id, url_foto, destaque, ordem_exibicao
                FROM servico_foto
                WHERE servico_id = :servico_id
                ORDER BY destaque DESC, ordem_exibicao ASC, id ASC
                LIMIT 1";

        $row = $this->fetch($sql, ["servico_id" => $serviceId]);
        return is_array($row) ? $row : null;
    }

    public function findById(int $id): mixed {
        $sql = "SELECT id, servico_id, url_foto, destaque, ordem_exibicao
                FROM servico_foto
                WHERE id = :id LIMIT 1";

        return $this->fetch($sql, ["id" => $id]);
    }

    public function countByService(int $serviceId): int {
        $row = $this->fetchRaw(
            "SELECT COUNT(*) AS total FROM servico_foto WHERE servico_id = :servico_id",
            ["servico_id" => $serviceId]
        );
        return (int)($row["total"] ?? 0);
    }

    /** Próxima ordem de exibição livre (para acrescentar ao fim do carousel). */
    public function nextDisplayOrder(int $serviceId): int {
        $row = $this->fetchRaw(
            "SELECT COALESCE(MAX(ordem_exibicao), 0) + 1 AS proxima FROM servico_foto WHERE servico_id = :servico_id",
            ["servico_id" => $serviceId]
        );
        return (int)($row["proxima"] ?? 1);
    }

    public function create(int $serviceId, string $url, bool $featured, int $displayOrder): int {
        $sql = "INSERT INTO servico_foto (servico_id, url_foto, destaque, ordem_exibicao)
                VALUES (:servico_id, :url_foto, :destaque, :ordem_exibicao)";

        $this->execute($sql, [
            "servico_id"     => $serviceId,
            "url_foto"       => $url,
            "destaque"       => $featured ? 1 : 0,
            "ordem_exibicao" => $displayOrder
        ]);

        return (int)$this->lastInsertId();
    }

    /** Remove o destaque de todas as fotos do serviço (só uma pode ser a principal). */
    public function clearFeatured(int $serviceId): void {
        $this->execute(
            "UPDATE servico_foto SET destaque = 0 WHERE servico_id = :servico_id",
            ["servico_id" => $serviceId]
        );
    }

    public function setFeatured(int $id): void {
        $this->execute("UPDATE servico_foto SET destaque = 1 WHERE id = :id", ["id" => $id]);
    }

    public function delete(int $id): bool {
        return $this->execute("DELETE FROM servico_foto WHERE id = :id", ["id" => $id]) > 0;
    }
}